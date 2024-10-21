<?php

namespace ipl\I18n;

/**
 * Translate a message
 *
 * @param string $message
 * @param string $context Message context
 *
 * @return string Translated message or original message if no translation is found
 */
function t($message, $context = null)
{
    return StaticTranslator::$instance->translate($message, $context);
}

/**
 * Translate a plural message
 *
 * The returned message is based on the given number to decide between the singular and plural forms.
 * That is also the case if no translation is found.
 *
 * @param string $singular Singular message
 * @param string $plural   Plural message
 * @param int    $number   Number to decide between the returned singular and plural forms
 * @param string $context  Message context
 *
 * @return string Translated message or original message if no translation is found
 */
function tp($singular, $plural, $number, $context = null)
{
    return StaticTranslator::$instance->translatePlural($singular, $plural, $number, $context);
}
