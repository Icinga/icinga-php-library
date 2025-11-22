<?php

namespace ipl\Web\Control;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\FormUid;
use ipl\Web\Control\SearchBar\Terms;
use ipl\Web\Control\SearchBar\ValidatedColumn;
use ipl\Web\Control\SearchBar\ValidatedOperator;
use ipl\Web\Control\SearchBar\ValidatedValue;
use ipl\Web\Filter\ParseException;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SearchBar extends Form
{
    use FormUid;

    /** @var string Emitted in case the user added a new condition */
    const ON_ADD = 'on_add';

    /** @var string Emitted in case the user inserted a new condition */
    const ON_INSERT = 'on_insert';

    /** @var string Emitted in case the user changed an existing condition */
    const ON_SAVE = 'on_save';

    /** @var string Emitted in case the user removed a condition */
    const ON_REMOVE = 'on_remove';

    protected $defaultAttributes = [
        'data-enrichment-type'  => 'search-bar',
        'class'                 => 'search-bar',
        'name'                  => 'search-bar',
        'role'                  => 'search'
    ];

    /** @var Url */
    protected $editorUrl;

    /** @var Filter\Rule */
    protected $filter;

    /** @var string */
    protected $searchParameter;

    /** @var Url */
    protected $suggestionUrl;

    /** @var string */
    protected $submitLabel;

    /** @var callable */
    protected $protector;

    /** @var array */
    protected $changes;

    /**
     * Set the url from which to load the editor
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setEditorUrl(Url $url)
    {
        $this->editorUrl = $url;

        return $this;
    }

    /**
     * Get the url from which to load the editor
     *
     * @return Url
     */
    public function getEditorUrl()
    {
        return $this->editorUrl;
    }

    /**
     * Set the filter to use
     *
     * @param   Filter\Rule $filter
     * @return  $this
     */
    public function setFilter(Filter\Rule $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get the filter in use
     *
     * @return Filter\Rule
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the search parameter to use
     *
     * @param   string $name
     * @return  $this
     */
    public function setSearchParameter($name)
    {
        $this->searchParameter = $name;

        return $this;
    }

    /**
     * Get the search parameter in use
     *
     * @return string
     */
    public function getSearchParameter()
    {
        return $this->searchParameter ?: 'q';
    }

    /**
     * Set the suggestion url
     *
     * @param   Url $url
     * @return  $this
     */
    public function setSuggestionUrl(Url $url)
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    /**
     * Get the suggestion url
     *
     * @return Url
     */
    public function getSuggestionUrl()
    {
        return $this->suggestionUrl;
    }

    /**
     * Set the submit label
     *
     * @param   string $label
     * @return  $this
     */
    public function setSubmitLabel($label)
    {
        $this->submitLabel = $label;

        return $this;
    }

    /**
     * Get the submit label
     *
     * @return string
     */
    public function getSubmitLabel()
    {
        return $this->submitLabel;
    }

    /**
     * Set callback to protect ids with
     *
     * @param   callable $protector
     *
     * @return  $this
     */
    public function setIdProtector($protector)
    {
        $this->protector = $protector;

        return $this;
    }

    /**
     * Get changes to be applied on the client
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    private function protectId($id)
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }

    public function populate($values)
    {
        if (array_key_exists($this->getSearchParameter(), (array) $values)) {
            // If a filter is set, it must be reset in case new data arrives. The new data controls the filter,
            // though if no data is sent, (populate() is only called if the form is sent) then the filter must
            // be reset explicitly here to not keep the outdated filter.
            $this->filter = Filter::all();
        }

        parent::populate($values);
    }

    public function isValidEvent($event)
    {
        switch ($event) {
            case self::ON_ADD:
            case self::ON_SAVE:
            case self::ON_INSERT:
            case self::ON_REMOVE:
                return true;
            default:
                return parent::isValidEvent($event);
        }
    }

    private function validateCondition($eventType, $indices, $termsData, &$changes)
    {
        // TODO: In case of the query string validation, all three are guaranteed to be set.
        //       The Parser also provides defaults, why shouldn't we here?
        $column = ValidatedColumn::fromTermData($termsData[0]);
        $operator = isset($termsData[1])
            ? ValidatedOperator::fromTermData($termsData[1])
            : null;
        $value = isset($termsData[2])
            ? ValidatedValue::fromTermData($termsData[2])
            : null;

        $this->emit($eventType, [$column, $operator, $value]);

        if ($eventType !== self::ON_REMOVE) {
            if (! $column->isValid() || $column->hasBeenChanged()) {
                $changes[$indices[0]] = array_merge($termsData[0], $column->toTermData());
            }

            if ($operator && ! $operator->isValid()) {
                $changes[$indices[1]] = array_merge($termsData[1], $operator->toTermData());
            }

            if ($value && (! $value->isValid() || $value->hasBeenChanged())) {
                $changes[$indices[2]] = array_merge($termsData[2], $value->toTermData());
            }
        }

        return $column->isValid() && (! $operator || $operator->isValid()) && (! $value || $value->isValid());
    }


    protected function assemble()
    {
        $termContainerId = $this->protectId('terms');
        $termInputId = $this->protectId('term-input');
        $dataInputId = $this->protectId('data-input');
        $searchInputId = $this->protectId('search-input');
        $suggestionsId = $this->protectId('suggestions');

        $termContainer = (new Terms())->setAttribute('id', $termContainerId);
        $termInput = new HiddenElement($this->getSearchParameter(), [
            'id'        => $termInputId,
            'disabled'  => true
        ]);

        if (! $this->getRequest()->getHeaderLine('X-Icinga-Autorefresh')) {
            $termContainer->setFilter(function () {
                return $this->getFilter();
            });
            $termInput->getAttributes()->registerAttributeCallback('value', function () {
                return QueryString::render($this->getFilter());
            });
        }

        $dataInput = new HiddenElement('data', [
            'id'            => $dataInputId,
            'validators'    => [
                new CallbackValidator(function ($data, CallbackValidator $_) use ($termContainer, $searchInputId) {
                    $data = $data ? json_decode($data, true) : null;
                    if (empty($data)) {
                        return true;
                    }

                    switch ($data['type']) {
                        case 'add':
                        case 'exchange':
                            $type = self::ON_ADD;

                            break;
                        case 'insert':
                            $type = self::ON_INSERT;

                            break;
                        case 'save':
                            $type = self::ON_SAVE;

                            break;
                        case 'remove':
                            $type = self::ON_REMOVE;

                            break;
                        default:
                            return true;
                    }

                    $changes = [];
                    $invalid = false;
                    $indices = [null, null, null];
                    $termsData = [null, null, null];
                    foreach (isset($data['terms']) ? $data['terms'] : [] as $termIndex => $termData) {
                        switch ($termData['type']) {
                            case 'column':
                                $indices[0] = $termIndex;
                                $termsData[0] = $termData;

                                break;
                            case 'operator':
                                $indices[1] = $termIndex;
                                $termsData[1] = $termData;

                                break;
                            case 'value':
                                $indices[2] = $termIndex;
                                $termsData[2] = $termData;

                                break;
                            default:
                                if ($termsData[0] !== null) {
                                    if (! $this->validateCondition($type, $indices, $termsData, $changes)) {
                                        $invalid = true;
                                    }
                                }

                                $indices = $termsData = [null, null, null];
                        }
                    }

                    if ($termsData[0] !== null) {
                        if (! $this->validateCondition($type, $indices, $termsData, $changes)) {
                            $invalid = true;
                        }
                    }

                    if (! empty($changes)) {
                        $this->changes = ['#' . $searchInputId, $changes];
                        $termContainer->applyChanges($changes);
                    }

                    return ! $invalid;
                })
            ]
        ]);
        $this->registerElement($dataInput);

        $filterInput = new InputElement($this->getSearchParameter(), [
            'type'                  => 'text',
            'placeholder'           => 'Type to search. Use * as wildcard.',
            'class'                 => 'filter-input',
            'id'                    => $searchInputId,
            'autocomplete'          => 'off',
            'data-enrichment-type'  => 'filter',
            'data-data-input'       => '#' . $dataInputId,
            'data-term-input'       => '#' . $termInputId,
            'data-term-container'   => '#' . $termContainerId,
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-missing-log-op'   => t('Please add a logical operator on the left.'),
            'data-incomplete-group' => t('Please close or remove this group.'),
            'data-choose-template'  => t('Please type one of: %s', '..<comma separated list>'),
            'data-choose-column'    => t('Please enter a valid column.'),
            'validators'            => [
                new CallbackValidator(function ($q, CallbackValidator $validator) use ($searchInputId) {
                    $submitted = $this->hasBeenSubmitted();
                    $invalid = false;
                    $changes = [];

                    $parser = QueryString::fromString($q);
                    $parser->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use (
                        &$invalid,
                        &$changes,
                        $submitted
                    ) {
                        $columnIndex = $condition->metaData()->get('columnIndex');
                        if (isset($this->changes[1][$columnIndex])) {
                            $change = $this->changes[1][$columnIndex];
                            $condition->setColumn($change['search']);
                        } elseif (empty($this->changes)) {
                            $column = ValidatedColumn::fromFilterCondition($condition);
                            $operator = ValidatedOperator::fromFilterCondition($condition);
                            $value = ValidatedValue::fromFilterCondition($condition);
                            $this->emit(self::ON_ADD, [$column, $operator, $value]);

                            $condition->setColumn($column->getSearchValue());
                            $condition->setValue($value->getSearchValue());

                            if (! $column->isValid()) {
                                $invalid = true;

                                if ($submitted) {
                                    $condition->metaData()->merge($column->toMetaData());
                                } else {
                                    $changes[$columnIndex] = $column->toTermData();
                                }
                            }

                            if (! $operator->isValid()) {
                                $invalid = true;

                                if ($submitted) {
                                    $condition->metaData()->merge($operator->toMetaData());
                                } else {
                                    $changes[$condition->metaData()->get('operatorIndex')] = $operator->toTermData();
                                }
                            }

                            if (! $value->isValid()) {
                                $invalid = true;

                                if ($submitted) {
                                    $condition->metaData()->merge($value->toMetaData());
                                } else {
                                    $changes[$condition->metaData()->get('valueIndex')] = $value->toTermData();
                                }
                            }
                        }
                    });

                    try {
                        $filter = $parser->parse();
                    } catch (ParseException $e) {
                        $charAt = $e->getCharPos() - 1;
                        $char = $e->getChar();

                        $this->getElement($this->getSearchParameter())
                            ->addAttributes([
                                'title'     => sprintf(t('Unexpected %s at start of input'), $char),
                                'pattern'   => sprintf('^(?!%s).*', $char === ')' ? '\)' : $char),
                                'data-has-syntax-error' => true
                            ])
                            ->getAttributes()
                            ->registerAttributeCallback('value', function () use ($q, $charAt) {
                                return substr($q, $charAt);
                            });

                        $probablyValidQueryString = substr($q, 0, $charAt);
                        $this->setFilter(QueryString::parse($probablyValidQueryString));
                        return false;
                    }

                    $this->getElement($this->getSearchParameter())
                        ->getAttributes()
                        ->registerAttributeCallback('value', function () {
                            return '';
                        });
                    $this->setFilter($filter);

                    if (! empty($changes)) {
                        $this->changes = ['#' . $searchInputId, $changes];
                    }

                    return ! $invalid;
                })
            ]
        ]);
        if ($this->getSuggestionUrl() !== null) {
            $filterInput->getAttributes()->registerAttributeCallback('data-suggest-url', function () {
                return (string) $this->getSuggestionUrl();
            });
        }

        $this->registerElement($filterInput);

        $submitButton = new SubmitElement('submit', ['label' => $this->getSubmitLabel() ?: 'hidden']);
        $this->registerElement($submitButton);

        $editorOpener = null;
        if ($this->getEditorUrl() !== null) {
            $editorOpener = new HtmlElement(
                'button',
                Attributes::create([
                    'type'                      => 'button',
                    'class'                     => 'search-editor-opener control-button',
                    'title'                     => t('Adjust Filter')
                ])->registerAttributeCallback('data-search-editor-url', function () {
                    return (string) $this->getEditorUrl();
                }),
                new Icon('cog')
            );
        }

        $this->addHtml(
            new HtmlElement(
                'button',
                Attributes::create(['type' => 'button', 'class' => 'search-options']),
                new Icon('search')
            ),
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'filter-input-area']),
                $termContainer,
                new HtmlElement('label', Attributes::create(['data-label' => '']), $filterInput)
            ),
            $dataInput,
            $termInput,
            $submitButton,
            $this->createUidElement(),
            new HtmlElement('div', Attributes::create([
                'id'                => $suggestionsId,
                'class'             => 'search-suggestions',
                'data-base-target'  => $suggestionsId
            ]))
        );

        // Render the editor container outside of this form. It will contain a form as well later on
        // loaded by XHR and HTML prohibits nested forms. It's style-wise also better...
        $doc = new HtmlDocument();
        $this->prependWrapper($doc);
        $doc->addHtml($this, ...($editorOpener ? [$editorOpener] : []));
    }
}
