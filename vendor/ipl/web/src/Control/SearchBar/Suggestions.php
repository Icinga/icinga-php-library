<?php

namespace ipl\Web\Control\SearchBar;

use Countable;
use Generator;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormElement\ButtonElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Stdlib\Filter;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Filter\QueryString;
use IteratorIterator;
use LimitIterator;
use OuterIterator;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

abstract class Suggestions extends BaseHtmlElement
{
    const DEFAULT_LIMIT = 50;
    const SUGGESTION_TITLE_CLASS = 'suggestion-title';

    protected $tag = 'ul';

    /** @var string */
    protected $searchTerm;

    /** @var Traversable */
    protected $data;

    /** @var array */
    protected $default;

    /** @var string */
    protected $type;

    /** @var string */
    protected $failureMessage;

    public function setSearchTerm($term)
    {
        $this->searchTerm = $term;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setFailureMessage($message)
    {
        $this->failureMessage = $message;

        return $this;
    }

    /**
     * Create a filter to provide as default for column suggestions
     *
     * @param string $searchTerm
     *
     * @return Filter\Rule
     */
    abstract protected function createQuickSearchFilter($searchTerm);

    /**
     * Fetch value suggestions for a particular column
     *
     * @param string $column
     * @param string $searchTerm
     * @param Filter\Chain $searchFilter
     *
     * @return Traversable
     */
    abstract protected function fetchValueSuggestions($column, $searchTerm, Filter\Chain $searchFilter);

    /**
     * Fetch column suggestions
     *
     * @param string $searchTerm
     *
     * @return Traversable
     */
    abstract protected function fetchColumnSuggestions($searchTerm);

    protected function filterToTerms(Filter\Chain $filter)
    {
        $logicalSep = [
            'label'     => QueryString::getRuleSymbol($filter),
            'search'    => QueryString::getRuleSymbol($filter),
            'class'     => 'logical_operator',
            'type'      => 'logical_operator'
        ];

        $terms = [];
        foreach ($filter as $child) {
            if ($child instanceof Filter\Chain) {
                $terms[] = [
                    'search'    => '(',
                    'label'     => '(',
                    'type'      => 'grouping_operator',
                    'class'     => 'grouping_operator_open'
                ];
                $terms = array_merge($terms, $this->filterToTerms($child));
                $terms[] = [
                    'search'    => ')',
                    'label'     => ')',
                    'type'      => 'grouping_operator',
                    'class'     => 'grouping_operator_close'
                ];
            } else {
                /** @var Filter\Condition $child */

                $terms[] = [
                    'search'    => $child->getColumn(),
                    'label'     => $child->metaData()->get('columnLabel'),
                    'type'      => 'column'
                ];
                $terms[] = [
                    'search'    => QueryString::getRuleSymbol($child),
                    'label'     => QueryString::getRuleSymbol($child),
                    'type'      => 'operator'
                ];
                $terms[] = [
                    'search'    => $child->getValue(),
                    'label'     => $child->getValue(),
                    'type'      => 'value'
                ];
            }

            $terms[] = $logicalSep;
        }

        array_pop($terms);
        return $terms;
    }

    protected function assembleDefault()
    {
        if ($this->default === null) {
            return;
        }

        $attributes = [
            'type'          => 'button',
            'tabindex'      => -1,
            'data-label'    => $this->default['search'],
            'value'         => $this->default['search']
        ];
        if (isset($this->default['type'])) {
            $attributes['data-type'] = $this->default['type'];
        } elseif ($this->type !== null) {
            $attributes['data-type'] = $this->type;
        }

        $button = new ButtonElement(null, $attributes);
        $button->addHtml(
            Text::create(sprintf('%s ', t('Search for'))),
            new HtmlElement('em', null, Text::create($this->default['search']))
        );
        if (isset($this->default['type']) && $this->default['type'] === 'terms') {
            $terms = $this->filterToTerms($this->default['terms']);
            $list = new HtmlElement('ul', Attributes::create(['class' => 'comma-separated']));
            foreach ($terms as $data) {
                if ($data['type'] === 'column') {
                    $list->addHtml(new HtmlElement(
                        'li',
                        null,
                        new HtmlElement('em', null, Text::create($data['label']))
                    ));
                }
            }

            $button->setAttribute('data-terms', json_encode($terms));
            $button->addHtml(
                Text::create(sprintf(' %s ', t('in:'))),
                $list
            );
        }

        $this->prependHtml(new HtmlElement('li', Attributes::create(['class' => 'default']), $button));
    }

    protected function assemble()
    {
        if ($this->failureMessage !== null) {
            $this->addHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => 'failure-message']),
                new HtmlElement('em', null, Text::create(t('Can\'t search:'))),
                Text::create($this->failureMessage)
            ));
            return;
        }

        if ($this->data === null) {
            $data = [];
        } elseif ($this->data instanceof Paginatable) {
            $this->data->limit(self::DEFAULT_LIMIT);
            $data = $this->data;
        } else {
            $data = new LimitIterator(new IteratorIterator($this->data), 0, self::DEFAULT_LIMIT);
        }

        foreach ($data as $term => $meta) {
            if (is_int($term)) {
                $term = $meta;
            }

            $attributes = [
                'type'          => 'button',
                'tabindex'      => -1,
                'data-search'   => $term
            ];
            if ($this->type !== null) {
                $attributes['data-type'] = $this->type;
            }

            if (is_array($meta)) {
                foreach ($meta as $key => $value) {
                    if ($key === 'label') {
                        $attributes['value'] = $value;
                    }

                    $attributes['data-' . $key] = $value;
                }
            } else {
                $attributes['value'] = $meta;
                $attributes['data-label'] = $meta;
            }

            $this->addHtml(new HtmlElement('li', null, new InputElement(null, $attributes)));
        }

        if ($this->hasMore($data, self::DEFAULT_LIMIT)) {
            $this->getAttributes()->add('class', 'has-more');
        }

        $showDefault = true;
        if ($this->searchTerm && $this->count() === 1) {
            // The default option is only shown if the user's input does not result in an exact match
            $input = $this->getFirst('li')->getFirst('input');
            $showDefault = $input->getValue() != $this->searchTerm
                && $input->getAttributes()->get('data-search')->getValue() != $this->searchTerm;
        }

        if ($this->type === 'column' && ! $this->isEmpty() && ! $this->getFirst('li')->getAttributes()->has('class')) {
            // The column title is only added if there are any suggestions and the first item is not a title already
            $this->prependHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => static::SUGGESTION_TITLE_CLASS]),
                Text::create(t('Columns'))
            ));
        }

        if ($showDefault) {
            $this->assembleDefault();
        }
    }

    /**
     * Load suggestions as requested by the client
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function forRequest(ServerRequestInterface $request)
    {
        if ($request->getMethod() !== 'POST') {
            return $this;
        }

        $requestData = json_decode($request->getBody()->read(8192), true);
        if (empty($requestData)) {
            return $this;
        }

        $search = $requestData['term']['search'];
        $label = $requestData['term']['label'];
        $type = $requestData['term']['type'];

        $this->setSearchTerm($search);
        $this->setType($type);

        switch ($type) {
            case 'value':
                if (! $requestData['column'] || $requestData['column'] === SearchEditor::FAKE_COLUMN) {
                    $this->setFailureMessage(t('Missing column name'));
                    break;
                }

                $searchFilter = QueryString::parse(
                    isset($requestData['searchFilter'])
                        ? $requestData['searchFilter']
                        : ''
                );
                if ($searchFilter instanceof Filter\Condition) {
                    $searchFilter = Filter::all($searchFilter);
                }

                try {
                    $this->setData($this->fetchValueSuggestions($requestData['column'], $label, $searchFilter));
                } catch (SearchException $e) {
                    $this->setFailureMessage($e->getMessage());
                }

                if ($search) {
                    $this->setDefault(['search' => $search]);
                }

                break;
            case 'column':
                $this->setData($this->filterColumnSuggestions($this->fetchColumnSuggestions($label), $label));

                if ($search && isset($requestData['showQuickSearch']) && $requestData['showQuickSearch']) {
                    $quickFilter = $this->createQuickSearchFilter($label);
                    if (! $quickFilter instanceof Filter\Chain || ! $quickFilter->isEmpty()) {
                        $this->setDefault([
                            'search'    => $label,
                            'type'      => 'terms',
                            'terms'     => $quickFilter
                        ]);
                    }
                }
        }

        return $this;
    }

    protected function hasMore($data, $than)
    {
        if (is_array($data)) {
            return count($data) > $than;
        } elseif ($data instanceof Countable) {
            return $data->count() > $than;
        } elseif ($data instanceof OuterIterator) {
            return $this->hasMore($data->getInnerIterator(), $than);
        }

        return false;
    }

    /**
     * Filter the given suggestions by the client's input
     *
     * @param Traversable $data
     * @param string $searchTerm
     *
     * @return Generator
     */
    protected function filterColumnSuggestions($data, $searchTerm)
    {
        foreach ($data as $key => $value) {
            if ($this->matchSuggestion($key, $value, $searchTerm)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Get whether the given suggestion should be provided to the client
     *
     * @param string $path
     * @param string $label
     * @param string $searchTerm
     *
     * @return bool
     */
    protected function matchSuggestion($path, $label, $searchTerm)
    {
        return fnmatch($searchTerm, $label, FNM_CASEFOLD) || fnmatch($searchTerm, $path, FNM_CASEFOLD);
    }

    public function renderUnwrapped()
    {
        $this->ensureAssembled();

        if ($this->isEmpty()) {
            return '';
        }

        return parent::renderUnwrapped();
    }
}
