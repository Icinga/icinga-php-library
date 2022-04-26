<?php

namespace ipl\Web\Compat;

use GuzzleHttp\Psr7\ServerRequest;
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
     * Fetch meta-data for the given query
     *
     * @param Query $query
     *
     * @return array
     */
    abstract public function fetchMetaData(Query $query);

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
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchBar
     */
    public function createSearchBar(Query $query, array $preserveParams = null): SearchBar
    {
        $requestUrl = Url::fromRequest();
        $redirectUrl = $preserveParams !== null
            ? $requestUrl->onlyWith($preserveParams)
            : (clone $requestUrl)->setParams([]);

        $filter = QueryString::fromString((string) $this->params)
            ->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use ($query) {
                $this->enrichFilterCondition($condition, $query);
            })
            ->parse();

        $searchBar = new SearchBar();
        $searchBar->setFilter($filter);
        $searchBar->setAction($redirectUrl->getAbsoluteUrl());
        $searchBar->setIdProtector([$this->getRequest(), 'protectId']);

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

        $metaData = $this->fetchMetaData($query);
        $columnValidator = function (SearchBar\ValidatedColumn $column) use ($query, $metaData) {
            $columnPath = $column->getSearchValue();
            if (strpos($columnPath, '.') === false) {
                $columnPath = $query->getResolver()->qualifyPath($columnPath, $query->getModel()->getTableName());
            }

            if (! isset($metaData[$columnPath])) {
                list($columnPath, $columnLabel) = Seq::find($metaData, $column->getSearchValue(), false);
                if ($columnPath === null) {
                    $column->setMessage(t('Is not a valid column'));
                } else {
                    $column->setSearchValue($columnPath);
                    $column->setLabel($columnLabel);
                }
            } else {
                $column->setLabel($metaData[$columnPath]);
            }
        };

        $searchBar->on(SearchBar::ON_ADD, $columnValidator)
            ->on(SearchBar::ON_INSERT, $columnValidator)
            ->on(SearchBar::ON_SAVE, $columnValidator)
            ->on(SearchBar::ON_SENT, function (SearchBar $form) use ($redirectUrl) {
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
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchEditor
     */
    public function createSearchEditor(Query $query, array $preserveParams = null): SearchEditor
    {
        $requestUrl = Url::fromRequest();
        $moduleName = $this->getRequest()->getModuleName();
        $controllerName = $this->getRequest()->getControllerName();
        $redirectUrl = Url::fromPath("$moduleName/$controllerName");
        if (! empty($preserveParams)) {
            $redirectUrl->setParams($requestUrl->onlyWith($preserveParams)->getParams());
        }

        $editor = new SearchEditor();
        $editor->setQueryString((string) $this->params->without($preserveParams));
        $editor->setAction($requestUrl->getAbsoluteUrl());

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

        $metaData = $this->fetchMetaData($query);
        $editor->on(SearchEditor::ON_VALIDATE_COLUMN, function (Filter\Condition $condition) use ($query, $metaData) {
            $column = $condition->getColumn();
            if (! isset($metaData[$column])) {
                $path = Seq::findKey($metaData, $condition->metaData()->get('columnLabel', $column), false);
                if ($path === null) {
                    throw new SearchBar\SearchException(t('Is not a valid column'));
                } else {
                    $condition->setColumn($path);
                }
            }
        })->on(SearchEditor::ON_SUCCESS, function (SearchEditor $form) use ($redirectUrl) {
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
            $path = $query->getResolver()->qualifyPath($path, $query->getModel()->getTableName());
            $condition->setColumn($path);
        }

        $metaData = $this->fetchMetaData($query);
        if (isset($metaData[$path])) {
            $condition->metaData()->set('columnLabel', $metaData[$path]);
        }
    }
}
