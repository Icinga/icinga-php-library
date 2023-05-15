<?php

namespace ipl\Web\FormElement\TermInput;

class RegisteredTerm implements Term
{
    /** @var string The search value */
    protected $value;

    /** @var ?string The label */
    protected $label;

    /** @var ?string The CSS class */
    protected $class;

    /** @var string The failure message */
    protected $message;

    /** @var string The validation constraint */
    protected $pattern;

    /**
     * Create a new RegisteredTerm
     *
     * @param string $value The search value
     */
    public function __construct(string $value)
    {
        $this->setSearchValue($value);
    }

    public function setSearchValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getSearchValue(): string
    {
        return $this->value;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function getPattern(): ?string
    {
        if ($this->message === null) {
            return null;
        }

        return $this->pattern ?? sprintf(Term::DEFAULT_CONSTRAINT, $this->getSearchValue());
    }

    /**
     * Render this term as a string
     *
     * Pass the separator being used to separate multiple terms. If the term's value contains it,
     * the result will be automatically quoted.
     *
     * @param string $separator
     *
     * @return string
     */
    public function render(string $separator): string
    {
        if (strpos($this->value, $separator) !== false) {
            return '"' . $this->value . '"';
        }

        return $this->value;
    }

    /**
     * Apply the given term data to this term
     *
     * @param array $termData
     *
     * @return void
     */
    public function applyTermData(array $termData): void
    {
        if (isset($termData['search'])) {
            $this->value = $termData['search'];
        }

        if (isset($termData['label'])) {
            $this->setLabel($termData['label']);
        }

        if (isset($termData['class'])) {
            $this->setClass($termData['class']);
        }

        if (isset($termData['invalidMsg'])) {
            $this->setMessage($termData['invalidMsg']);
        }

        if (isset($termData['pattern'])) {
            $this->setPattern($termData['pattern']);
        }
    }
}
