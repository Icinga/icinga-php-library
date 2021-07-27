<?php

namespace ipl\Web\Filter;

use ipl\Stdlib\Events;
use ipl\Stdlib\Filter;

class Parser
{
    use Events;

    /** @var string Emitted for every completely parsed condition */
    const ON_CONDITION = 'on_condition';

    /** @var string Emitted for every completely parsed chain */
    const ON_CHAIN = 'on_chain';

    /** @var string */
    protected $string;

    /** @var int */
    protected $pos;

    /** @var int */
    protected $length;

    /** @var bool Whether strict mode is enabled */
    protected $strict = false;

    /**
     * Create a new Parser
     *
     * @param string $queryString The string to parse
     */
    public function __construct($queryString = null)
    {
        if ($queryString !== null) {
            $this->setQueryString($queryString);
        }
    }

    /**
     * Set the query string to parse
     *
     * @param string $queryString
     *
     * @return $this
     */
    public function setQueryString($queryString)
    {
        $this->string = (string) $queryString;
        $this->length = strlen($queryString);

        return $this;
    }

    /**
     * Set whether strict mode is enabled
     *
     * @param bool $strict
     *
     * @return $this
     */
    public function setStrict($strict = true)
    {
        $this->strict = (bool) $strict;

        return $this;
    }

    /**
     * Parse the string and derive a filter rule from it
     *
     * @return Filter\Rule
     */
    public function parse()
    {
        if ($this->length === 0) {
            return Filter::all();
        }

        $this->pos = 0;

        return $this->readFilters();
    }

    /**
     * Read filters
     *
     * @param int $nestingLevel
     * @param string $op
     * @param array $filters
     * @param bool $explicit
     *
     * @return Filter\Chain|Filter\Condition
     * @throws ParseException
     */
    protected function readFilters($nestingLevel = 0, $op = null, $filters = null, $explicit = true)
    {
        $filters = empty($filters) ? [] : $filters;
        $isNone = false;

        while ($this->pos < $this->length) {
            $filter = $this->readCondition();
            $next = $this->readChar();

            if ($filter === false) {
                if ($next === '!') {
                    $isNone = true;
                    continue;
                }

                if ($op === null && ($this->strict || count($filters) > 0) && ($next === '&' || $next === '|')) {
                    $op = $next;
                    continue;
                }

                if ($next === false) {
                    // Nothing more to read
                    break;
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        if (! $explicit) {
                            // The current chain was not initiated by a `(`,
                            // so this `)` does not belong to it, but still ends it
                            $this->pos--;
                        }

                        break;
                    }

                    $this->parseError($next);
                }

                if ($next === '(') {
                    $rule = $this->readFilters($nestingLevel + 1, $isNone ? '!' : null);
                    if ($this->strict || ! $rule instanceof Filter\Chain || ! $rule->isEmpty()) {
                        $filters[] = $rule;
                    }

                    $isNone = false;
                    continue;
                }

                if ($next === $op) {
                    continue;
                }

                if (in_array($next, ['&', '|'])) {
                    // It's a different logical operator, continue parsing based on its precedence
                    if ($op === '&') {
                        if (! empty($filters)) {
                            if (count($filters) > 1) {
                                $all = Filter::all(...$filters);
                                $filters = [$all];

                                $this->emit(self::ON_CHAIN, [$all]);
                            } else {
                                $filters = [$filters[0]];
                            }
                        }

                        $op = $next;
                    } elseif ($op === '|' || ($op === '!' && $next === '&')) {
                        $rule = $this->readFilters(
                            $nestingLevel + 1,
                            $next,
                            [array_pop($filters)],
                            false
                        );
                        if (! $rule instanceof Filter\Chain || ! $rule->isEmpty()) {
                            $filters[] = $rule;
                        }
                    }

                    continue;
                }

                $this->parseError($next, "$op level $nestingLevel");
            } else {
                if ($isNone) {
                    $isNone = false;
                    if ($filter->getValue() === true) {
                        // $filter is a result of `!column`
                        $filter->setValue(false);
                        $filters[] = $filter;

                        $this->emit(self::ON_CONDITION, [$filter]);
                    } else {
                        // $filter is a result of `!column=[value]`
                        $none = Filter::none($filter);
                        $filters[] = $none;

                        $this->emit(self::ON_CONDITION, [$filter]);
                        $this->emit(self::ON_CHAIN, [$none]);
                    }
                } else {
                    $filters[] = $filter;
                    $this->emit(self::ON_CONDITION, [$filter]);
                }

                if ($next === false) {
                    // Got filter, nothing more to read
                    break;
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        if (! $explicit) {
                            // The current chain was not initiated by a `(`,
                            // so this `)` does not belong to it, but still ends it
                            $this->pos--;
                        }

                        break;
                    }

                    $this->parseError($next);
                }

                if ($next === $op) {
                    continue;
                }

                if (in_array($next, ['&', '|'])) {
                    // It's a different logical operator, continue parsing based on its precedence
                    if ($op === null || $op === '&') {
                        if ($op === '&') {
                            if (count($filters) > 1) {
                                $all = Filter::all(...$filters);
                                $filters = [$all];

                                $this->emit(self::ON_CHAIN, [$all]);
                            } else {
                                $filters = [$filters[0]];
                            }
                        }

                        $op = $next;
                    } elseif ($op === '|' || ($op === '!' && $next === '&')) {
                        $rule = $this->readFilters(
                            $nestingLevel + 1,
                            $next,
                            [array_pop($filters)],
                            false
                        );
                        if (! $rule instanceof Filter\Chain || ! $rule->isEmpty()) {
                            $filters[] = $rule;
                        }
                    }

                    continue;
                }

                $this->parseError($next);
            }
        }

        if ($nestingLevel === 0 && $this->pos < $this->length) {
            $this->parseError($op, 'Did not read full filter');
        }

        switch ($op) {
            case '&':
                $chain = Filter::all(...$filters);
                break;
            case '|':
                $chain = Filter::any(...$filters);
                break;
            case '!':
                $chain = Filter::none(...$filters);
                break;
            case null:
                if ((! $this->strict || $nestingLevel === 0) && ! empty($filters)) {
                    // There is only one filter expression, no chain
                    return $filters[0];
                }

                $chain = Filter::all(...$filters);
                break;
            default:
                $this->parseError($op);
        }

        $this->emit(self::ON_CHAIN, [$chain]);

        return $chain;
    }

    /**
     * Read the next condition
     *
     * @return false|Filter\Condition
     *
     * @throws ParseException
     */
    protected function readCondition()
    {
        if ('' === ($column = $this->readColumn())) {
            return false;
        }

        foreach (['<', '>'] as $operator) {
            if (($pos = strpos($column, $operator)) !== false) {
                if ($this->nextChar() === '=') {
                    break;
                }

                $value = substr($column, $pos + 1);
                $column = substr($column, 0, $pos);

                if (ctype_digit($value)) {
                    $value = (float) $value;
                }

                return $this->createCondition($column, $operator, $value);
            }
        }

        if (in_array($this->nextChar(), ['=', '>', '<', '!'], true)) {
            $operator = $this->readChar();
        } else {
            $operator = false;
        }

        if ($operator === false) {
            return Filter::equal($column, true);
        }

        $toFloat = false;
        if ($operator === '=') {
            $last = substr($column, -1);
            if ($last === '>' || $last === '<') {
                $operator = $last . $operator;
                $column = substr($column, 0, -1);
                $toFloat = true;
            }
        } elseif (in_array($operator, ['>', '<', '!'], true)) {
            $toFloat = $operator === '>' || $operator === '<';
            if ($this->nextChar() === '=') {
                $operator .= $this->readChar();
            }
        }

        $value = $this->readValue();
        if ($toFloat && ctype_digit($value)) {
            $value = (float) $value;
        }

        return $this->createCondition($column, $operator, $value);
    }

    /**
     * Read the next column
     *
     * @return false|string false if there is none
     */
    protected function readColumn()
    {
        $str = $this->readUntil('=', '(', ')', '&', '|', '>', '<', '!');

        if ($str === false) {
            return $str;
        }

        return rawurldecode($str);
    }

    /**
     * Read the next value
     *
     * @return string|string[]
     *
     * @throws ParseException In case there's a missing `)`
     */
    protected function readValue()
    {
        if ($this->nextChar() === '(') {
            $this->readChar();
            $var = array_map('rawurldecode', preg_split('~\|~', $this->readUntil(')')));

            if ($this->readChar() !== ')') {
                $this->parseError(null, 'Expected ")"');
            }
        } else {
            $var = rawurldecode($this->readUntil(')', '&', '|', '>', '<'));
        }

        return $var;
    }

    /**
     * Read until any of the given chars appears
     *
     * @param string ...$chars
     *
     * @return string
     */
    protected function readUntil(...$chars)
    {
        $buffer = '';
        while (($c = $this->readChar()) !== false) {
            if (in_array($c, $chars, true)) {
                $this->pos--;
                break;
            }

            $buffer .= $c;
        }

        return $buffer;
    }

    /**
     * Read a single character
     *
     * @return false|string false if there is no character left
     */
    protected function readChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos++];
        }

        return false;
    }

    /**
     * Look at the next character
     *
     * @return false|string false if there is no character left
     */
    protected function nextChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos];
        }

        return false;
    }

    /**
     * Create and return a condition
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Filter\Condition
     */
    protected function createCondition($column, $operator, $value)
    {
        $column = trim($column);

        switch ($operator) {
            case '=':
                return Filter::equal($column, $value);
            case '!=':
                return Filter::unequal($column, $value);
            case '>':
                return Filter::greaterThan($column, $value);
            case '>=':
                return Filter::greaterThanOrEqual($column, $value);
            case '<':
                return Filter::lessThan($column, $value);
            case '<=':
                return Filter::lessThanOrEqual($column, $value);
        }
    }

    /**
     * Throw a parse exception
     *
     * @param string $char
     * @param string $extraMsg
     *
     * @throws ParseException
     */
    protected function parseError($char = null, $extraMsg = null)
    {
        if ($extraMsg === null) {
            $extra = '';
        } else {
            $extra = ': ' . $extraMsg;
        }

        if ($char === null) {
            if ($this->pos < $this->length) {
                $char = $this->string[$this->pos];
            } else {
                $char = $this->string[--$this->pos];
            }
        }

        throw new ParseException(
            $this->string,
            $char,
            $this->pos,
            $extra
        );
    }
}
