<?php

namespace ipl\Web\FormElement\TermInput;

interface Term
{
    /** @var string The default validation constraint */
    public const DEFAULT_CONSTRAINT = '^\s*(?!%s\b).*\s*$';

    /**
     * Set the search value
     *
     * @param string $value
     *
     * @return $this
     */
    public function setSearchValue(string $value);

    /**
     * Get the search value
     *
     * @return string
     */
    public function getSearchValue(): string;

    /**
     * Set the label
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(string $label);

    /**
     * Get the label
     *
     * @return ?string
     */
    public function getLabel(): ?string;

    /**
     * Set the CSS class
     *
     * @param string $class
     *
     * @return $this
     */
    public function setClass(string $class);

    /**
     * Get the CSS class
     *
     * @return ?string
     */
    public function getClass(): ?string;

    /**
     * Set the failure message
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage(string $message);

    /**
     * Get the failure message
     *
     * @return ?string
     */
    public function getMessage(): ?string;

    /**
     * Set the validation constraint
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern(string $pattern);

    /**
     * Get the validation constraint
     *
     * @return ?string
     */
    public function getPattern(): ?string;
}
