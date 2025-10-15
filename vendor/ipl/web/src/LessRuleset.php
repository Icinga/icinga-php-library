<?php

namespace ipl\Web;

use ArrayObject;
use Less_Parser;

/**
 * @extends ArrayObject<string, string>
 */
class LessRuleset extends ArrayObject
{
    /** @var ?string */
    protected $selector;

    /** @var array<LessRuleset> */
    protected $children = [];

    /**
     * Create a new LessRuleset
     *
     * @param string $selector Selector to use
     * @param array<string, string> $properties CSS properties
     *
     * @return self
     */
    public static function create(string $selector, array $properties): self
    {
        $ruleset = new static();
        $ruleset->selector = $selector;
        $ruleset->exchangeArray($properties);

        return $ruleset;
    }

    /**
     * Get the selector
     *
     * @return ?string
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }

    /**
     * Set the selector
     *
     * @param string $selector
     *
     * @return $this
     */
    public function setSelector(string $selector): self
    {
        $this->selector = $selector;

        return $this;
    }

    /**
     * Get a property value
     *
     * @param string $property Name of the property
     *
     * @return string
     */
    public function getProperty(string $property): string
    {
        return (string) $this[$property];
    }

    /**
     * Set a property
     *
     * @param string $property Name to use
     * @param string $value Value to set
     *
     * @return $this
     */
    public function setProperty(string $property, string $value): self
    {
        $this[$property] = $value;

        return $this;
    }

    /**
     * Get all properties
     *
     * @return array<string, string>
     */
    public function getProperties(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * Set properties
     *
     * @param array<string, string> $properties
     *
     * @return $this
     */
    public function setProperties(array $properties): self
    {
        $this->exchangeArray($properties);

        return $this;
    }

    /**
     * Create and add a ruleset
     *
     * @param string $selector Selector to use
     * @param array<string, string> $properties CSS properties
     *
     * @return $this
     */
    public function add(string $selector, array $properties): self
    {
        $this->children[] = static::create($selector, $properties);

        return $this;
    }

    /**
     * Add a ruleset
     *
     * @param LessRuleset $ruleset
     *
     * @return $this
     */
    public function addRuleset(LessRuleset $ruleset): self
    {
        $this->children[] = $ruleset;

        return $this;
    }

    /**
     * Compile the ruleset to CSS
     *
     * @return string
     */
    public function renderCss(): string
    {
        $parser = new Less_Parser(['compress' => true]);
        $parser->parse($this->renderLess());

        return $parser->getCss();
    }

    /**
     * Render the ruleset to LESS
     *
     * @return string
     */
    protected function renderLess(): string
    {
        $less = [];

        foreach ($this as $property => $value) {
            $less[] = "$property: $value;";
        }

        foreach ($this->children as $ruleset) {
            $less[] = $ruleset->renderLess();
        }

        if ($this->selector !== null) {
            array_unshift($less, "$this->selector {");
            $less[] = '}';
        }

        return implode("\n", $less);
    }
}
