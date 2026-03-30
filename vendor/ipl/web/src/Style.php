<?php

namespace ipl\Web;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;
use Throwable;

class Style extends LessRuleset implements ValidHtml
{
    /** @var ?string */
    protected $module;

    /** @var ?string */
    protected $nonce;

    /**
     * Get the used CSP nonce
     *
     * @return ?string
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Set the CSP nonce to use
     *
     * @param ?string $nonce
     *
     * @return $this
     */
    public function setNonce(?string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Get the Icinga module name the ruleset is scoped to
     *
     * @return ?string
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    /**
     * Set the Icinga module name to use as scope for the ruleset
     *
     * @param ?string $name
     *
     * @return $this
     */
    public function setModule(?string $name): self
    {
        $this->module = $name;

        return $this;
    }

    /**
     * Add CSS properties for the given element
     *
     * The created ruleset will be applied by an `#ID` selector. If the given
     * element does not have an ID set yet, one is automatically set.
     *
     * @param BaseHtmlElement $element Element to apply the properties to
     * @param array<string, string> $properties CSS properties
     *
     * @return $this
     */
    public function addFor(BaseHtmlElement $element, array $properties): self
    {
        /** @var ?string $id */
        $id = $element->getAttribute('id')->getValue();

        if ($id === null) {
            $id = uniqid('csp-style', false);
            $element->setAttribute('id', $id);
        }

        return $this->add('#' . $id, $properties);
    }

    public function render(): string
    {
        if ($this->module !== null) {
            $ruleset = (new static())
                ->setSelector(".icinga-module.module-$this->module")
                ->addRuleset($this);
        } else {
            $ruleset = $this;
        }

        return (new HtmlElement(
            'style',
            (new Attributes())->addAttribute(new Attribute('nonce', $this->getNonce())),
            HtmlString::create($ruleset->renderCss())
        ))->render();
    }

    /**
     * Render to HTML
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Throwable $e) {
            return sprintf('<!-- Failed to render style: %s -->', $e->getMessage());
        }
    }
}
