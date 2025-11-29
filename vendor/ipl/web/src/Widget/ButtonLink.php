<?php

namespace ipl\Web\Widget;

use ipl\Html\Attribute;

/**
 * Button like link generally pointing to CRUD actions
 */
class ButtonLink extends ActionLink
{
    protected $defaultAttributes = [
        'role'              => 'button',
        'class'             => 'button-link',
        'data-base-target'  => '_main'
    ];

    /** @var ?string The explanation why the button is disabled */
    protected ?string $disabledExplanation = null;

    /**
     * Disable the button with explanation
     *
     * @param string $explanation Why the button is disabled
     *
     * @return $this
     */
    public function disable(string $explanation): self
    {
        $this->disabledExplanation = $explanation;

        return $this;
    }

    public function createHrefAttribute(): ?Attribute
    {
        if ($this->disabledExplanation) {
            return null;
        }

        return parent::createHrefAttribute();
    }

    public function assemble(): void
    {
        parent::assemble();

        if ($this->disabledExplanation) {
            $this->addAttributes([
                'aria-disabled' => 'true',
                'title'         => $this->disabledExplanation,
            ]);
        }
    }
}
