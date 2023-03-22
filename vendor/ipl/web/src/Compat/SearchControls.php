<?php

namespace ipl\Web\Compat;

use GuzzleHttp\Psr7\ServerRequest;
use ipl\Html\Html;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Query;
use ipl\Stdlib\Seq;
use ipl\Web\Control\SearchBar;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Stdlib\Filter;

trait SearchControls
{
    /**
     * Fetch available filter columns for the given query
     *
     * @param Query $query
     *
     * @return array<string, string> Keys are column paths, values are labels
     */
    public function fetchFilterColumns(Query $query)
    {
        $columns = [];
        foreach ($query->getResolver()->getColumnDefinitions($query->getModel()) as $name => $definition) {
            $columns[$name] = $definition->getLabel();
        }

        return $columns;
    }

    /**
     * Get whether {@see SearchControls::createSearchBar()} and {@see SearchControls::createSearchEditor()}
     * should handle form submits.
     *
     * @return bool
     */
    private function callHandleRequest()
    {
        return true;
    }

    /**
     * Create and return the SearchBar
     *
     * @param Query $query The query being filtered
     * @param Url $redirectUrl Url to redirect to upon success
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchBar
     */
    public function createSearchBar(Query $query, ...$params): SearchBar
    {
        $requestUrl = Url::fromRequest();
        $preserveParams = array_pop($params) ?? [];
        $redirectUrl = array_pop($params);

        if ($redirectUrl !== null) {
            $redirectUrl->addParams($requestUrl->onlyWith($preserveParams)->getParams()->toArray(false));
        } else {
            $redirectUrl = $requestUrl->onlyWith($preserveParams);
        }

        $filter = QueryString::fromString((string) $this->params)
            ->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use ($query) {
                $this->enrichFilterCondition($condition, $query);
            })
            ->parse();

        $searchBar = new SearchBar();
        $searchBar->setFilter($filter);
        $searchBar->setRedirectUrl($redirectUrl);
        $searchBar->setAction($redirectUrl->getAbsoluteUrl());
        $searchBar->setIdProtector([$this->getRequest(), 'protectId']);
        $searchBar->addWrapper(Html::tag('div', ['class' => 'search-controls']));

        $moduleName = $this->getRequest()->getModuleName();
        $controllerName = $this->getRequest()->getControllerName();

        if (method_exists($this, 'completeAction')) {
            $searchBar->setSuggestionUrl(Url::fromPath(
                "$moduleName/$controllerName/complete",
                ['_disableLayout' => true, 'showCompact' => true]
            ));
        }

        if (method_exists($this, 'searchEditorAction')) {
            $searchBar->setEditorUrl(Url::fromPath(
                "$moduleName/$controllerName/search-editor"
            )->setParams($redirectUrl->getParams()));
        }

        $filterColumns = $this->fetchFilterColumns($query);
        $columnValidator = function (SearchBar\ValidatedColumn $column) use ($query, $filterColumns) {
            $searchPath = $column->getSearchValue();
            if (strpos($searchPath, '.') === false) {
                $column->setSearchValue($query->getResolver()->qualifyPath(
                    $searchPath,
                    $query->getModel()->getTableAlias()
                ));
            }

            try {
                $definition = $query->getResolver()->getColumnDefinition($searchPath);
            } catch (InvalidRelationException $_) {
                list($columnPath, $columnLabel) = Seq::find($filterColumns, $searchPath, false);
                if ($columnPath === null) {
                    $column->setMessage(t('Is not a valid column'));
                    $column->setSearchValue($searchPath); // Resets the qualification made above
                } else {
                    $column->setSearchValue($columnPath);
                    $column->setLabel($columnLabel);
                }
            }

            if (isset($definition)) {
                $column->setLabel($definition->getLabel());
            }
        };

        $searchBar->on(SearchBar::ON_ADD, $columnValidator)
            ->on(SearchBar::ON_INSERT, $columnValidator)
            ->on(SearchBar::ON_SAVE, $columnValidator)
            ->on(SearchBar::ON_SENT, function (SearchBar $form) {
                /** @var Url $redirectUrl */
                $redirectUrl = $form->getRedirectUrl();
                $existingParams = $redirectUrl->getParams();
                $redirectUrl->setQueryString(QueryString::render($form->getFilter()));
                foreach ($existingParams->toArray(false) as $name => $value) {
                    if (is_int($name)) {
                        $name = $value;
                        $value = true;
                    }

                    $redirectUrl->getParams()->addEncoded($name, $value);
                }

                $form->setRedirectUrl($redirectUrl);
            })->on(SearchBar::ON_SUCCESS, function (SearchBar $form) {
                $this->getResponse()->redirectAndExit($form->getRedirectUrl());
            });

        if ($this->callHandleRequest()) {
            $searchBar->handleRequest(ServerRequest::fromGlobals());
        }

        return $searchBar;
    }

    /**
     * Create and return the SearchEditor
     *
     * @param Query $query The query being filtered
     * @param Url $redirectUrl Url to redirect to upon success
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchEditor
     */
    public function createSearchEditor(Query $query, ...$params): SearchEditor
    {
        $requestUrl = Url::fromRequest();
        $preserveParams = array_pop($params) ?? [];
        $redirectUrl = array_pop($params);
        $moduleName = $this->getRequest()->getModuleName();
        $controllerName = $this->getRequest()->getControllerName();

        if ($redirectUrl !== null) {
            $redirectUrl->addParams($requestUrl->onlyWith($preserveParams)->getParams()->toArray(false));
        } else {
            $redirectUrl = Url::fromPath("$moduleName/$controllerName");
            if (! empty($preserveParams)) {
                $redirectUrl->setParams($requestUrl->onlyWith($preserveParams)->getParams());
            }
        }

        $editor = new SearchEditor();
        $editor->setRedirectUrl($redirectUrl);
        $editor->setAction($requestUrl->getAbsoluteUrl());
        $editor->setQueryString((string) $this->params->without($preserveParams));

        if (method_exists($this, 'completeAction')) {
            $editor->setSuggestionUrl(Url::fromPath(
                "$moduleName/$controllerName/complete",
                ['_disableLayout' => true, 'showCompact' => true]
            ));
        }

        $editor->getParser()->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use ($query) {
            if ($condition->getColumn()) {
                $this->enrichFilterCondition($condition, $query);
            }
        });

        $filterColumns = $this->fetchFilterColumns($query);
        $editor->on(SearchEditor::ON_VALIDATE_COLUMN, function (
            Filter\Condition $condition
        ) use (
            $query,
            $filterColumns
        ) {
            $searchPath = $condition->getColumn();
            if (strpos($searchPath, '.') === false) {
                $condition->setColumn($query->getResolver()->qualifyPath(
                    $searchPath,
                    $query->getModel()->getTableAlias()
                ));
            }

            try {
                $query->getResolver()->getColumnDefinition($searchPath);
            } catch (InvalidRelationException $_) {
                $columnPath = Seq::findKey(
                    $filterColumns,
                    $condition->metaData()->get('columnLabel', $searchPath),
                    false
                );
                if ($columnPath === null) {
                    $condition->setColumn($searchPath);
                    throw new SearchBar\SearchException(t('Is not a valid column'));
                } else {
                    $condition->setColumn($columnPath);
                }
            }
        })->on(SearchEditor::ON_SUCCESS, function (SearchEditor $form) {
            /** @var Url $redirectUrl */
            $redirectUrl = $form->getRedirectUrl();
            $existingParams = $redirectUrl->getParams();
            $redirectUrl->setQueryString(QueryString::render($form->getFilter()));
            foreach ($existingParams->toArray(false) as $name => $value) {
                if (is_int($name)) {
                    $name = $value;
                    $value = true;
                }

                $redirectUrl->getParams()->addEncoded($name, $value);
            }

            $this->getResponse()
                ->setHeader('X-Icinga-Container', '_self')
                ->redirectAndExit($redirectUrl);
        });

        if ($this->callHandleRequest()) {
            $editor->handleRequest(ServerRequest::fromGlobals());
        }

        return $editor;
    }

    /**
     * Enrich the filter condition with meta data from the query
     *
     * @param Filter\Condition $condition
     * @param Query $query
     *
     * @return void
     */
    protected function enrichFilterCondition(Filter\Condition $condition, Query $query)
    {
        $path = $condition->getColumn();
        if (strpos($path, '.') === false) {
            $path = $query->getResolver()->qualifyPath($path, $query->getModel()->getTableAlias());
            $condition->setColumn($path);
        }

        try {
            $label = $query->getResolver()->getColumnDefinition($path)->getLabel();
        } catch (InvalidRelationException $_) {
            $label = null;
        }

        if (isset($label)) {
            $condition->metaData()->set('columnLabel', $label);
        }
    }
}
