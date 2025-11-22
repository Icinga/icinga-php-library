<?php

namespace ipl\Web\FormElement\TermInput;

use BadMethodCallException;

class ValidatedTerm extends \ipl\Web\Control\SearchBar\ValidatedTerm implements Term
{
    const DEFAULT_PATTERN = Term::DEFAULT_CONSTRAINT;

    /** @var ?string The CSS class */
    protected $class;

    public function setClass(string $class): Term
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function toTermData()
    {
        $data = parent::toTermData();
        $data['class'] = $this->getClass();

        return $data;
    }

    public function toMetaData()
    {
        throw new BadMethodCallException(self::class . '::toTermData() not implemented yet');
    }
}
