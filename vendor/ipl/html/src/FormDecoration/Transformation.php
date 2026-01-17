<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\ValidHtml;

/**
 * Describes how a decoration result should be transformed
 */
enum Transformation
{
    /** Add HTML to the end of the result */
    case Append;

    /** Prepend HTML to the beginning of the result */
    case Prepend;

    /** Set HTML as the container of the result */
    case Wrap;

    /**
     * Transform the given results according to this case with the given HTML
     *
     * @param DecorationResult $result
     * @param ValidHtml|MutableHtml $html
     *
     * @return DecorationResult
     */
    public function apply(DecorationResult $result, ValidHtml|MutableHtml $html): DecorationResult
    {
        return match ($this) {
             self::Append  => $result->append($html),
             self::Prepend => $result->prepend($html),
             self::Wrap    => $result->wrap($html)
        };
    }
}
