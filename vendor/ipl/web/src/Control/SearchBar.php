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
use ipl\Web\Filter\ParseException;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SearchBar extends Form
{
    use FormUid;

    /** @var string Emitted in case of an auto submit */
    const ON_CHANGE = 'on_change';

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

    public function isValidEvent($event)
    {
        if ($event !== self::ON_CHANGE) {
            return parent::isValidEvent($event);
        }

        return true;
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
                    $data = json_decode($data, true);
                    if (empty($data)) {
                        return true;
                    }

                    $validatedData = $data;
                    // TODO: I'd like to not pass a ref here, but the Events trait does not support event return values
                    $this->emit(self::ON_CHANGE, [&$validatedData]);

                    $changes = [];
                    foreach (isset($validatedData['terms']) ? $validatedData['terms'] : [] as $termIndex => $termData) {
                        if ($termData !== $data['terms'][$termIndex]) {
                            if (isset($termData['invalidMsg']) && ! isset($termData['pattern'])) {
                                $termData['pattern'] = sprintf(
                                    '^\s*(?!%s\b).*\s*$',
                                    $data['terms'][$termIndex]['label']
                                );
                            }

                            $changes[$termIndex] = $termData;
                        }
                    }

                    if (! empty($changes)) {
                        $this->changes = ['#' . $searchInputId, $changes];
                        $termContainer->applyChanges($changes);
                        // TODO: Not every change must be invalid, change this once there are Event objects
                        return false;
                    }

                    return true;
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
                new CallbackValidator(function ($q, CallbackValidator $validator) {
                    try {
                        $filter = QueryString::parse($q);
                    } catch (ParseException $e) {
                        $charAt = $e->getCharPos() - 1;
                        $char = $e->getChar();

                        $this->getElement($this->getSearchParameter())
                            ->setValue(substr($q, $charAt))
                            ->addAttributes([
                                'title'     => sprintf(t('Unexpected %s at start of input'), $char),
                                'pattern'   => sprintf('^(?!%s).*', $char === ')' ? '\)' : $char)
                            ]);

                        $probablyValidQueryString = substr($q, 0, $charAt);
                        $this->setFilter(QueryString::parse($probablyValidQueryString));
                        return false;
                    }

                    $this->getElement($this->getSearchParameter())->setValue('');
                    $this->setFilter($filter);
                    return true;
                })
            ]
        ]);
        if (($suggestionUrl = $this->getSuggestionUrl()) !== null) {
            $filterInput->setAttribute('data-suggest-url', $suggestionUrl);
        }

        $this->registerElement($filterInput);

        $submitButton = new SubmitElement('submit', ['label' => $this->getSubmitLabel() ?: 'hidden']);
        $this->registerElement($submitButton);

        $editorOpener = null;
        if (($editorUrl = $this->getEditorUrl()) !== null) {
            $editorOpener = new HtmlElement(
                'button',
                Attributes::create([
                    'type'                      => 'button',
                    'class'                     => 'search-editor-opener',
                    'title'                     => t('Adjust Filter'),
                    'data-search-editor-url'    => $editorUrl
                ]),
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
        $this->setWrapper($doc);
        $doc->addHtml($this, ...($editorOpener ? [$editorOpener] : []));
    }
}
