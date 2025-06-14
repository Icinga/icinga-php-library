<?php

namespace ipl\Web\Control\SearchBar;

use ipl\Stdlib\Data;

abstract class ValidatedTerm
{
    /** @var string The default validation constraint */
    const DEFAULT_PATTERN = '^\s*(?!%s\b).*\s*$';

    /** @var string The search value */
    protected $searchValue;

    /** @var ?string The label */
    protected $label;

    /** @var ?string The validation message */
    protected $message;

    /** @var ?string The validation constraint */
    protected $pattern;

    /** @var bool Whether the term has been adjusted */
    protected $changed = false;

    /**
     * Create a new ValidatedTerm
     *
     * @param string $searchValue The search value
     * @param ?string $label The label
     */
    public function __construct(string $searchValue, ?string $label = null)
    {
        $this->searchValue = $searchValue;
        $this->label = $label;
    }

    /**
     * Create a new ValidatedTerm from the given data
     *
     * @param array $data
     *
     * @return static
     */
    public static function fromTermData(array $data)
    {
        return new static($data['search'], isset($data['label']) ? $data['label'] : null);
    }

    /**
     * Check whether the term is valid
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->message === null;
    }

    /**
     * Check whether the term has been adjusted
     *
     * @return bool
     */
    public function hasBeenChanged()
    {
        return $this->changed;
    }

    /**
     * Get the search value
     *
     * @return string
     */
    public function getSearchValue(): string
    {
        return $this->searchValue;
    }

    /**
     * Set the search value
     *
     * @param string $searchValue
     *
     * @return $this
     */
    public function setSearchValue(string $searchValue): self
    {
        $this->searchValue = $searchValue;
        $this->changed = true;

        return $this;
    }

    /**
     * Get the label
     *
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the label
     *
     * @param ?string $label
     *
     * @return $this
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;
        $this->changed = true;

        return $this;
    }

    /**
     * Get the validation message
     *
     * @return ?string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set the validation message
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the validation constraint
     *
     * Returns the default constraint if none is set.
     *
     * @return string
     */
    public function getPattern(): ?string
    {
        if ($this->message === null) {
            return null;
        }

        return $this->pattern ?? sprintf(self::DEFAULT_PATTERN, $this->getLabel() ?: $this->getSearchValue());
    }

    /**
     * Set the validation constraint
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get this term's data
     *
     * @return array
     */
    public function toTermData()
    {
        return [
            'search'     => $this->getSearchValue(),
            'label'      => $this->getLabel() ?: $this->getSearchValue(),
            'invalidMsg' => $this->getMessage(),
            'pattern'    => $this->getPattern()
        ];
    }

    /**
     * Get this term's metadata
     *
     * @return Data
     */
    abstract public function toMetaData();
}
