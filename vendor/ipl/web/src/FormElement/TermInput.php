<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Stdlib\Events;
use ipl\Web\FormElement\TermInput\RegisteredTerm;
use ipl\Web\FormElement\TermInput\TermContainer;
use ipl\Web\FormElement\TermInput\ValidatedTerm;
use ipl\Web\Url;
use Psr\Http\Message\ServerRequestInterface;

class TermInput extends FieldsetElement
{
    use Events;

    /** @var string Emitted in case the user added new terms */
    const ON_ADD = 'on_add';

    /** @var string Emitted in case the user inserted new terms */
    const ON_PASTE = 'on_paste';

    /** @var string Emitted in case the user changed existing terms */
    const ON_SAVE = 'on_save';

    /** @var string Emitted in case the user removed terms */
    const ON_REMOVE = 'on_remove';

    /** @var string Emitted in case terms need to be enriched */
    const ON_ENRICH = 'on_enrich';

    /** @var Url The suggestion url */
    protected $suggestionUrl;

    /** @var bool Whether term direction is vertical */
    protected $verticalTermDirection = false;

    /** @var bool Whether term order is significant */
    protected $ordered = false;

    /** @var bool Whether registered terms are read-only */
    protected $readOnly = false;

    /** @var array Changes to transmit to the client */
    protected $changes = [];

    /** @var RegisteredTerm[] The terms */
    protected $terms = [];

    /** @var bool Whether this input has been automatically submitted */
    private $hasBeenAutoSubmitted = false;

    /** @var bool Whether the term input value has been pasted */
    private $valueHasBeenPasted;

    /** @var TermContainer The term container */
    protected $termContainer;

    /**
     * Set the suggestion url
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setSuggestionUrl(Url $url): self
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    /**
     * Get the suggestion url
     *
     * @return ?Url
     */
    public function getSuggestionUrl(): ?Url
    {
        return $this->suggestionUrl;
    }

    /**
     * Set whether term direction should be vertical
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setVerticalTermDirection(bool $state = true): self
    {
        $this->verticalTermDirection = $state;

        return $this;
    }

    /**
     * Get the desired term direction
     *
     * @return ?string
     */
    public function getTermDirection(): ?string
    {
        return $this->verticalTermDirection || $this->ordered ? 'vertical' : null;
    }

    /**
     * Set whether term order is significant
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setOrdered(bool $state = true): self
    {
        $this->ordered = $state;

        return $this;
    }

    /**
     * Get whether term order is significant
     *
     * @return bool
     */
    public function getOrdered(): bool
    {
        return $this->ordered;
    }

    /**
     * Set whether registered terms are read-only
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setReadOnly(bool $state = true): self
    {
        $this->readOnly = $state;

        return $this;
    }

    /**
     * Get whether registered terms are read-only
     *
     * @return bool
     */
    public function getReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Set terms
     *
     * @param RegisteredTerm ...$terms
     *
     * @return $this
     */
    public function setTerms(RegisteredTerm ...$terms): self
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Get the terms
     *
     * @return RegisteredTerm[]
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    public function getElements()
    {
        // TODO: Only a quick-fix. Remove once fieldsets are properly partially validated
        $this->ensureAssembled();

        return parent::getElements();
    }

    public function getValue($name = null, $default = null)
    {
        if ($name !== null) {
            return parent::getValue($name, $default);
        }

        $terms = [];
        foreach ($this->getTerms() as $term) {
            $terms[] = $term->render(',');
        }

        return implode(',', $terms);
    }

    public function setValue($value)
    {
        $separatedTerms = $value;
        if (is_array($value)) {
            $separatedTerms = $value['value'] ?? '';
            parent::setValue($value);
        }

        $terms = [];
        foreach ($this->parseValue((string) $separatedTerms) as $term) {
            $terms[] = new RegisteredTerm($term);
        }

        return $this->setTerms(...$terms);
    }

    /**
     * Parse the given separated string of terms
     *
     * @param string $value
     *
     * @return string[]
     */
    public function parseValue(string $value): array
    {
        $terms = [];

        $term = '';
        $ignoreSeparator = false;
        for ($i = 0; $i <= strlen($value); $i++) {
            if (! isset($value[$i])) {
                if (! empty($term)) {
                    $terms[] = rawurldecode($term);
                }

                break;
            }

            $c = $value[$i];
            if ($c === '"') {
                $ignoreSeparator = ! $ignoreSeparator;
            } elseif (! $ignoreSeparator && $c === ',') {
                $terms[] = rawurldecode($term);
                $term = '';
            } else {
                $term .= $c;
            }
        }

        return $terms;
    }

    /**
     * Prepare updates to transmit for this input during multipart responses
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    public function prepareMultipartUpdate(ServerRequestInterface $request): array
    {
        $updates = [];
        if ($this->valueHasBeenPasted()) {
            $updates[] = $this->termContainer();
            $updates[] = [
                HtmlString::create(json_encode(['#' . $this->getName() . '-search-input', []])),
                'Behavior:InputEnrichment'
            ];
        } elseif (! empty($this->changes)) {
            $updates[] = [
                HtmlString::create(json_encode(['#' . $this->getName() . '-search-input', $this->changes])),
                'Behavior:InputEnrichment'
            ];
        }

        if (empty($updates) && $this->hasBeenAutoSubmitted()) {
            $updates[] = $updates[] = [
                HtmlString::create(json_encode(['#' . $this->getName() . '-search-input', 'bogus'])),
                'Behavior:InputEnrichment'
            ];
        }

        return $updates;
    }

    /**
     * Get whether this input has been automatically submitted
     *
     * @return bool
     */
    private function hasBeenAutoSubmitted(): bool
    {
        return $this->hasBeenAutoSubmitted;
    }

    /**
     * Get whether the term input value has been pasted
     *
     * @return bool
     */
    private function valueHasBeenPasted(): bool
    {
        if ($this->valueHasBeenPasted === null) {
            $this->valueHasBeenPasted = ($this->getElement('data')->getValue()['type'] ?? null) === 'paste';
        }

        return $this->valueHasBeenPasted;
    }

    public function onRegistered(Form $form)
    {
        $termContainerId = $this->getName() . '-terms';
        $mainInputId = $this->getName() . '-search-input';
        $autoSubmittedBy = $form->getRequest()->getHeader('X-Icinga-Autosubmittedby');

        $this->hasBeenAutoSubmitted = in_array($mainInputId, $autoSubmittedBy, true)
            || in_array($termContainerId, $autoSubmittedBy, true);

        parent::onRegistered($form);
    }

    /**
     * Validate the given terms
     *
     * @param string $type The type of change to validate
     * @param array $terms The terms affected by the change
     * @param array $changes Potential changes made by validators
     *
     * @return bool
     */
    private function validateTerms(string $type, array $terms, array &$changes): bool
    {
        $validatedTerms = [];
        foreach ($terms as $index => $data) {
            $validatedTerms[$index] = ValidatedTerm::fromTermData($data);
        }

        switch ($type) {
            case 'submit':
            case 'exchange':
                $type = self::ON_ADD;

                break;
            case 'paste':
                $type = self::ON_PASTE;

                break;
            case 'save':
                $type = self::ON_SAVE;

                break;
            case 'remove':
            default:
                return true;
        }

        $this->emit($type, [$validatedTerms]);

        $invalid = false;
        foreach ($validatedTerms as $index => $term) {
            if (! $term->isValid()) {
                $invalid = true;
            }

            if (! $term->isValid() || $term->hasBeenChanged()) {
                $changes[$index] = $term->toTermData();
            }
        }

        return $invalid;
    }

    /**
     * Get the term container
     *
     * @return TermContainer
     */
    protected function termContainer(): TermContainer
    {
        if ($this->termContainer === null) {
            $this->termContainer = (new TermContainer($this))
                ->setAttribute('id', $this->getName() . '-terms');
        }

        return $this->termContainer;
    }

    protected function assemble()
    {
        $myName = $this->getName();

        $termInputId = $myName . '-term-input';
        $dataInputId = $myName . '-data-input';
        $searchInputId = $myName . '-search-input';
        $suggestionsId = $myName . '-suggestions';

        $termContainer = $this->termContainer();

        $suggestions = (new HtmlElement('div'))
            ->setAttribute('id', $suggestionsId)
            ->setAttribute('class', 'search-suggestions');

        $termInput = $this->createElement('hidden', 'value', [
            'id' => $termInputId,
            'disabled' => true
        ]);

        $dataInput = new class ('data', [
            'ignore' => true,
            'id' => $dataInputId,
            'validators' => ['callback' => function ($data) use ($termContainer) {
                $changes = [];
                $invalid = $this->validateTerms($data['type'], $data['terms'] ?? [], $changes);
                $this->changes = $changes;

                $terms = $this->getTerms();
                foreach ($changes as $index => $termData) {
                    $terms[$index]->applyTermData($termData);
                }

                return ! $invalid;
            }]
        ]) extends HiddenElement {
            /** @var TermInput */
            private $parent;

            public function setParent(TermInput $parent): void
            {
                $this->parent = $parent;
            }

            public function setValue($value)
            {
                $data = json_decode($value, true);
                if (($data['type'] ?? null) === 'paste') {
                    array_push($data['terms'], ...array_map(function ($t) {
                        return ['search' => $t];
                    }, $this->parent->parseValue($data['input'])));
                }

                return parent::setValue($data);
            }

            public function getValueAttribute()
            {
                return null;
            }
        };
        $dataInput->setParent($this);

        $label = $this->getLabel();
        $this->setLabel(null);

        // TODO: Separator customization
        $mainInput = $this->createElement('text', 'value', [
            'id' => $searchInputId,
            'label' => $label,
            'required' => $this->isRequired(),
            'placeholder' => $this->translate('Type to search. Separate multiple terms by comma.'),
            'class' => 'term-input',
            'autocomplete' => 'off',
            'data-term-separator' => ',',
            'data-enrichment-type' => 'terms',
            'data-with-multi-completion' => true,
            'data-no-auto-submit-on-remove' => true,
            'data-term-direction' => $this->getTermDirection(),
            'data-maintain-term-order' => $this->getOrdered() && ! $this->getAttribute('disabled')->getValue(),
            'data-read-only-terms' => $this->getReadOnly(),
            'data-data-input' => '#' . $dataInputId,
            'data-term-input' => '#' . $termInputId,
            'data-term-container' => '#' . $termContainer->getAttribute('id')->getValue(),
            'data-term-suggestions' => '#' . $suggestionsId
        ]);
        $mainInput->getAttributes()
            ->registerAttributeCallback('value', function () {
                return null;
            });
        if ($this->getSuggestionUrl() !== null) {
            $mainInput->getAttributes()->registerAttributeCallback('data-suggest-url', function () {
                return (string) $this->getSuggestionUrl();
            });
        }

        $this->addElement($termInput);
        $this->addElement($dataInput);
        $this->addElement($mainInput);

        $mainInput->prependWrapper((new HtmlElement(
            'div',
            Attributes::create(['class' => [
                'term-input-area',
                $this->getTermDirection(),
                $this->getReadOnly() ? 'read-only' : null
            ]]),
            $termContainer,
            new HtmlElement('label', null, $mainInput)
        )));

        $this->addHtml($suggestions);

        if (! $this->hasBeenAutoSubmitted()) {
            $this->emit(self::ON_ENRICH, [$this->getTerms()]);
        }
    }
}
