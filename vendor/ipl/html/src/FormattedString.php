<?php

namespace ipl\Html;

use InvalidArgumentException;
use Throwable;

use function ipl\Stdlib\get_php_type;

/**
 * {@link sprintf()}-like formatted HTML string supporting lazy rendering of {@link ValidHtml} element arguments
 *
 * # Example Usage
 * ```
 * $info = new FormattedString(
 *     'Follow the %s for more information on %s',
 *     [
 *         new Link('doc/html', 'HTML documentation'),
 *         Html::tag('strong', 'HTML elements')
 *     ]
 * );
 * ```
 */
class FormattedString implements ValidHtml
{
    /** @var ValidHtml[] */
    protected $args = [];

    /** @var ValidHtml */
    protected $format;

    /**
     * Create a new {@link sprintf()}-like formatted HTML string
     *
     * @param string   $format
     * @param iterable $args
     *
     * @throws InvalidArgumentException If arguments given but not iterable
     */
    public function __construct($format, $args = null)
    {
        $this->format = Html::wantHtml($format);

        if ($args !== null) {
            if (! is_iterable($args)) {
                throw new InvalidArgumentException(sprintf(
                    '%s expects parameter two to be iterable, got %s instead',
                    __METHOD__,
                    get_php_type($args)
                ));
            }

            foreach ($args as $key => $val) {
                if (! is_scalar($val) || (is_string($val) && ! is_numeric($val))) {
                    $val = Html::wantHtml($val);
                }

                $this->args[$key] = $val;
            }
        }
    }


    /**
     * Create a new {@link sprintf()}-like formatted HTML string
     *
     * @param string $format
     * @param mixed  ...$args
     *
     * @return static
     */
    public static function create($format, ...$args)
    {
        return new static($format, $args);
    }

    /**
     * Render text to HTML when treated like a string
     *
     * Calls {@link render()} internally in order to render the text to HTML.
     * Exceptions will be automatically caught and returned as HTML string as well using {@link Error::render()}.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Throwable $e) {
            return Error::render($e);
        }
    }

    public function render()
    {
        return vsprintf(
            $this->format->render(),
            $this->args
        );
    }
}
