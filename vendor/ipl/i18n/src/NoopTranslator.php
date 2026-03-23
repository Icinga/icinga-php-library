<?php

namespace ipl\I18n;

use ipl\Stdlib\Contract\Translator;

/**
 * Translator that just returns the original messages
 */
class NoopTranslator implements Translator
{
    public function translate($message, $context = null)
    {
        return $message;
    }

    public function translateInDomain($domain, $message, $context = null)
    {
        return $message;
    }

    public function translatePlural($singular, $plural, $number, $context = null)
    {
        return $number === 1 ? $singular : $plural;
    }

    public function translatePluralInDomain($domain, $singular, $plural, $number, $context = null)
    {
        return $number === 1 ? $singular : $plural;
    }
}
