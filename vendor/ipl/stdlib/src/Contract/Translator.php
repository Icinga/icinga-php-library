<?php

namespace ipl\Stdlib\Contract;

/**
 * Translate messages with optional context, domain scope, and plural forms
 */
interface Translator
{
    /**
     * Translate a message
     *
     * @param string $message
     * @param ?string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translate(string $message, ?string $context = null): string;

    /**
     * Translate a message in the given domain
     *
     * If no translation is found in the specified domain, the translation is also searched for in the default domain.
     *
     * @param string $domain
     * @param string $message
     * @param ?string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translateInDomain(string $domain, string $message, ?string $context = null): string;

    /**
     * Translate a plural message
     *
     * The returned message is based on the given number to decide between the singular and plural forms.
     * That is also the case if no translation is found.
     *
     * @param string $singular Singular message
     * @param string $plural Plural message
     * @param int $number Number to decide between the returned singular and plural forms
     * @param ?string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translatePlural(string $singular, string $plural, int $number, ?string $context = null): string;

    /**
     * Translate a plural message in the given domain
     *
     * If no translation is found in the specified domain, the translation is also searched for in the default domain.
     *
     * The returned message is based on the given number to decide between the singular and plural forms.
     * That is also the case if no translation is found.
     *
     * @param string $domain
     * @param string $singular Singular message
     * @param string $plural Plural message
     * @param int $number Number to decide between the returned singular and plural forms
     * @param ?string $context Message context
     *
     * @return string Translated message or original message if no translation is found
     */
    public function translatePluralInDomain(
        string $domain,
        string $singular,
        string $plural,
        int $number,
        ?string $context = null
    ): string;
}
