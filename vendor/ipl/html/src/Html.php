<?php

namespace ipl\Html;

use InvalidArgumentException;

use function ipl\Stdlib\get_php_type;
use function ipl\Stdlib\iterable_key_first;

/**
 * Main utility class when working with ipl\Html
 */
abstract class Html
{
    /**
     * Create a HTML element from the given tag, attributes and content
     *
     * This method does not render the HTML element but creates a {@link HtmlElement}
     * instance from the given tag, attributes and content
     *
     * @param string $name       The desired HTML tag name
     * @param mixed  $attributes HTML attributes or content for the element
     * @param mixed  $content    The content of the element if no attributes have been given
     *
     * @return HtmlElement The created element
     */
    public static function tag($name, $attributes = null, $content = null)
    {
        if ($content !== null) {
            // If not null, it's html content, no question
            $content = static::wantHtmlList($content);
        } elseif ($attributes instanceof ValidHtml || is_scalar($attributes)) {
            // Otherwise $attributes may be $content, but only if definitely **NOT** attributes
            $content = static::wantHtmlList($attributes);
            $attributes = null;
        }

        if ($attributes !== null) {
            if (! is_iterable($attributes) || ! is_int(iterable_key_first($attributes))) {
                // Not an array (e.g. instance of Attributes) or an associative array
                $attributes = Attributes::wantAttributes($attributes);
            } elseif (is_iterable($attributes)) {
                // $attributes may still be $content, in case of a sequenced array
                if ($content !== null) {
                    // But not if there's already $content
                    throw new InvalidArgumentException('Value of argument $attributes are no attributes');
                }

                $content = static::wantHtmlList($attributes);
                $attributes = null;
            }
        }

        return new HtmlElement($name, $attributes, ...($content ?: []));
    }

    /**
     * Convert special characters to HTML5 entities using the UTF-8 character
     * set for encoding
     *
     * This method internally uses {@link htmlspecialchars} with the following
     * flags:
     *
     * * Single quotes are not escaped (ENT_COMPAT)
     * * Uses HTML5 entities, disallowing &#013; (ENT_HTML5)
     * * Invalid characters are replaced with � (ENT_SUBSTITUTE)
     *
     * Already existing HTML entities will be encoded as well.
     *
     * @param string $content The content to encode
     *
     * @return string The encoded content
     */
    public static function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Factory for {@link sprintf()}-like formatted HTML strings
     *
     * This allows to use {@link sprintf()}-like format strings with {@link ValidHtml} element arguments, but with the
     * advantage that they'll not be rendered immediately.
     *
     * # Example Usage
     * ```
     * echo Html::sprintf('Hello %s!', Html::tag('strong', $name));
     * ```
     *
     * @param string $format
     * @param mixed  ...$args
     *
     * @return FormattedString
     */
    public static function sprintf($format, ...$args)
    {
        return new FormattedString($format, $args);
    }

    /**
     * Wrap each item of then given list
     *
     * $wrapper is a simple HTML tag per entry if a string is given,
     * otherwise the given callable is called with key and value of each list item as parameters.
     *
     * @param iterable        $list
     * @param string|callable $wrapper
     *
     * @return HtmlDocument
     */
    public static function wrapEach($list, $wrapper)
    {
        if (! is_iterable($list)) {
            throw new InvalidArgumentException(sprintf(
                'Html::wrapEach() requires a traversable list, got "%s"',
                get_php_type($list)
            ));
        }
        $result = new HtmlDocument();
        foreach ($list as $name => $value) {
            if (is_string($wrapper)) {
                $result->addHtml(Html::tag($wrapper, $value));
            } elseif (is_callable($wrapper)) {
                $result->add($wrapper($name, $value));
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Wrapper must be callable or a string in Html::wrapEach(), got "%s"',
                    get_php_type($wrapper)
                ));
            }
        }

        return $result;
    }

    /**
     * Ensure that the given content of mixed type is converted to an instance of {@link ValidHtml}
     *
     * Returns the very same element in case it's already an instance of {@link ValidHtml}.
     *
     * @param mixed $any
     *
     * @return ValidHtml
     *
     * @throws InvalidArgumentException In case the given content is of an unsupported type
     */
    public static function wantHtml($any)
    {
        if ($any instanceof ValidHtml) {
            return $any;
        } elseif (static::canBeRenderedAsString($any)) {
            return new Text($any);
        } elseif (is_iterable($any)) {
            $html = new HtmlDocument();
            foreach ($any as $el) {
                if ($el !== null) {
                    $html->addHtml(static::wantHtml($el));
                }
            }

            return $html;
        } else {
            throw new InvalidArgumentException(sprintf(
                'String, Html Element or Array of such expected, got "%s"',
                get_php_type($any)
            ));
        }
    }

    /**
     * Accept any input and return it as list of ValidHtml
     *
     * @param mixed $content
     *
     * @return ValidHtml[]
     */
    public static function wantHtmlList($content)
    {
        $list = [];

        if ($content === null) {
            return $list;
        } elseif (! is_iterable($content)) {
            $list[] = static::wantHtml($content);
        } elseif ($content instanceof ValidHtml) {
            $list[] = $content;
        } else {
            foreach ($content as $part) {
                $list = array_merge($list, static::wantHtmlList($part));
            }
        }

        return $list;
    }

    /**
     * Get whether the given variable be rendered as a string
     *
     * @param mixed $any
     *
     * @return bool
     */
    public static function canBeRenderedAsString($any)
    {
        return is_scalar($any) || is_null($any) || (
            is_object($any) && method_exists($any, '__toString')
        );
    }

    /**
     * Forward inaccessible static method calls to {@link Html::tag()} with the method's name as tag
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return HtmlElement
     */
    public static function __callStatic($name, $arguments)
    {
        $attributes = array_shift($arguments);
        $content = array_shift($arguments);

        return static::tag($name, $attributes, $content);
    }

    /**
     * @deprecated Use {@link Html::encode()} instead
     */
    public static function escapeForHtml($content)
    {
        return static::escape($content);
    }

    /**
     * @deprecated Use {@link Error::render()} instead
     */
    public static function renderError($error)
    {
        return Error::render($error);
    }
}
