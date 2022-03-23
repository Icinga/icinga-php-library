<?php

namespace ipl\Web\Control\SearchBar;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Icon;

class Terms extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'terms'];

    /** @var callable|Filter\Rule */
    protected $filter;

    /** @var array */
    protected $changes;

    /** @var int */
    private $changeIndexCorrection = 0;

    /** @var int */
    private $currentIndex = 0;

    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Apply term changes
     *
     * @param array $changes
     *
     * @return $this
     */
    public function applyChanges(array $changes)
    {
        $this->changes = $changes;

        return $this;
    }

    protected function assemble()
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter();
        }

        if ($filter === null) {
            return;
        }

        if ($filter instanceof Filter\Chain) {
            if ($filter->isEmpty()) {
                return;
            }

            if ($filter instanceof Filter\None) {
                $this->assembleChain($filter, $this, $filter->count() > 1);
            } else {
                $this->assembleConditions($filter, $this);
            }
        } else {
            /** @var Filter\Condition $filter */
            $this->assembleCondition($filter, $this);
        }
    }

    protected function assembleConditions(Filter\Chain $filters, BaseHtmlElement $where)
    {
        foreach ($filters as $i => $filter) {
            if ($i > 0) {
                $logicalOperator = QueryString::getRuleSymbol($filters);
                $this->assembleTerm([
                    'class'  => 'logical_operator',
                    'type'   => 'logical_operator',
                    'search' => $logicalOperator,
                    'label'  => $logicalOperator
                ], $where);
            }

            if ($filter instanceof Filter\Chain) {
                $this->assembleChain($filter, $where, $filter->count() > 1);
            } else {
                /** @var Filter\Condition $filter */
                $this->assembleCondition($filter, $where);
            }
        }
    }

    protected function assembleChain(Filter\Chain $chain, BaseHtmlElement $where, $wrap = false)
    {
        if ($wrap) {
            $group = new HtmlElement(
                'div',
                Attributes::create(['class' => 'filter-chain', 'data-group-type' => 'chain'])
            );
        } else {
            $group = $where;
        }

        if ($chain instanceof Filter\None) {
            $this->assembleTerm([
                'class'  => 'logical_operator',
                'type'   => 'negation_operator',
                'search' => '!',
                'label'  => '!'
            ], $where);
        }

        if ($wrap) {
            $opening = $this->assembleTerm([
                'class'  => 'grouping_operator_open',
                'type'   => 'grouping_operator',
                'search' => '(',
                'label'  => '('
            ], $group);
        }

        $this->assembleConditions($chain, $group);

        if ($wrap) {
            $closing = $this->assembleTerm([
                'class'  => 'grouping_operator_close',
                'type'   => 'grouping_operator',
                'search' => ')',
                'label'  => ')'
            ], $group);

            $opening->addAttributes([
                'data-counterpart' => $closing->getAttributes()->get('data-index')->getValue()
            ]);
            $closing->addAttributes([
                'data-counterpart' => $opening->getAttributes()->get('data-index')->getValue()
            ]);

            $where->addHtml($group);
        }
    }

    protected function assembleCondition(Filter\Condition $filter, BaseHtmlElement $where)
    {
        $column = $filter->getColumn();
        $operator = QueryString::getRuleSymbol($filter);
        $value = $filter->getValue();
        $columnLabel = $filter->metaData()->get('columnLabel', $column);

        $group = new HtmlElement(
            'div',
            Attributes::create(['class' => 'filter-condition', 'data-group-type' => 'condition']),
            new HtmlElement('button', Attributes::create(['type' => 'button']), new Icon('trash'))
        );

        $columnData = [
            'class'  => 'column',
            'type'   => 'column',
            'search' => rawurlencode($column),
            'label'  => $columnLabel
        ];
        if ($filter->metaData()->has('invalidColumnPattern')) {
            $columnData['pattern'] = $filter->metaData()->get('invalidColumnPattern');
            if ($filter->metaData()->has('invalidColumnMessage')) {
                $columnData['invalidMsg'] = $filter->metaData()->get('invalidColumnMessage');
            }
        }

        $this->assembleTerm($columnData, $group);

        if ($value !== true) {
            $operatorData = [
                'class'  => 'operator',
                'type'   => 'operator',
                'search' => $operator,
                'label'  => $operator
            ];
            if ($filter->metaData()->has('invalidOperatorPattern')) {
                $operatorData['pattern'] = $filter->metaData()->get('invalidOperatorPattern');
                if ($filter->metaData()->has('invalidOperatorMessage')) {
                    $operatorData['invalidMsg'] = $filter->metaData()->get('invalidOperatorMessage');
                }
            }

            $this->assembleTerm($operatorData, $group);

            if (! empty($value) || ctype_digit($value)) {
                $valueData = [
                    'class'  => 'value',
                    'type'   => 'value',
                    'search' => rawurlencode($value),
                    'label'  => $value
                ];
                if ($filter->metaData()->has('invalidValuePattern')) {
                    $valueData['pattern'] = $filter->metaData()->get('invalidValuePattern');
                    if ($filter->metaData()->has('invalidValueMessage')) {
                        $valueData['invalidMsg'] = $filter->metaData()->get('invalidValueMessage');
                    }
                }

                $this->assembleTerm($valueData, $group);
            }
        }

        $where->addHtml($group);
    }

    protected function assembleTerm(array $data, BaseHtmlElement $where)
    {
        if (isset($this->changes[$this->currentIndex - $this->changeIndexCorrection])) {
            $change = $this->changes[$this->currentIndex - $this->changeIndexCorrection];
            if ($change['type'] !== $data['type']) {
                // This can happen because the user didn't insert parentheses but the parser did
                $this->changeIndexCorrection++;
            } else {
                $data = array_merge($data, $change);
            }
        }

        $term = new HtmlElement('label', Attributes::create([
            'class'         => $data['class'],
            'data-index'    => $this->currentIndex++,
            'data-type'     => $data['type'],
            'data-search'   => $data['search'],
            'data-label'    => $data['label']
        ]), new HtmlElement('input', Attributes::create([
            'type'  => 'text',
            'value' => $data['label']
        ])));

        if (isset($data['pattern'])) {
            $term->getFirst('input')->setAttribute('pattern', $data['pattern']);

            if (isset($data['invalidMsg'])) {
                $term->getFirst('input')->setAttribute('data-invalid-msg', $data['invalidMsg']);
            }
        }

        $where->addHtml($term);

        return $term;
    }
}
