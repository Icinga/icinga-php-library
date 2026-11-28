<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\TextElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use RuntimeException;

class SuggestionElement extends TextElement
{
    protected $defaultAttributes = [
        'autocomplete'          => 'off',
        'class'                 => 'suggestion-element',
        'data-enrichment-type'  => 'completion'
    ];

    /** @var ?Url URL to fetch suggestions from */
    protected ?Url $suggestionsUrl = null;

    /**
     * Get the URL to fetch suggestions from
     *
     * @return Url
     *
     * @throws RuntimeException if the suggestionsUrl is not set
     */
    public function getSuggestionsUrl(): Url
    {
        if ($this->suggestionsUrl === null) {
            throw new RuntimeException('SuggestionsUrl is not set');
        }

        return $this->suggestionsUrl;
    }

    /**
     * Set the URL to fetch suggestions from
     *
     * @param Url $suggestionsUrl
     *
     * @return $this
     */
    public function setSuggestionsUrl(Url $suggestionsUrl): static
    {
        $this->suggestionsUrl = $suggestionsUrl;

        return $this;
    }

    /**
     * @return string If not set, returns a default placeholder
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder ?? $this->translate('Start typing to see suggestions…');
    }

    protected function assemble(): void
    {
        $suggestionsId = uniqid('search-suggestions-');

        $this->prependWrapper(
            new HtmlElement(
                'div',
                new Attributes(['class' => 'suggestion-element-group']),
                new HtmlElement('div', new Attributes(['id' => $suggestionsId, 'class' => 'search-suggestions'])),
                new HtmlElement('span', new Attributes(['class' => 'suggestion-element-icon']), new Icon('search'))
            )
        );

        $this->getAttributes()->add([
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-suggest-url'      => $this->getSuggestionsUrl()
        ]);
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback('suggestionsUrl', null, $this->setSuggestionsUrl(...));
    }
}
