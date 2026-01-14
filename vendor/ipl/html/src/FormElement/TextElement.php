<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attributes;

class TextElement extends InputElement
{
    protected $type = 'text';

    /** @var ?string Placeholder text for the input */
    protected ?string $placeholder = null;

    /**
     * Get the placeholder
     *
     * @return ?string
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Set the placeholder
     *
     * @param ?string $placeholder
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback('placeholder', [$this, 'getPlaceholder'], [$this, 'setPlaceholder']);
    }
}
