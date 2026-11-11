<?php

namespace ipl\Web\Widget;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;

/**
 * Ball element that supports different sizes
 *
 * The LESS mixins `ball-solid($color)` and `ball-outline($color)` must be used to style the ball,
 * otherwise only the ball content is visible but not the ball itself.
 *
 * @phpstan-type SIZE self::SIZE_TINY|self::SIZE_SMALL|self::SIZE_MEDIUM|_SIZE2
 * @phpstan-type _SIZE2 self::SIZE_MEDIUM_LARGE|self::SIZE_BIG|self::SIZE_LARGE
 */
class Ball extends BaseHtmlElement
{
    /** Tiny, no content supported */
    public const SIZE_TINY = 'xs';

    /** Small, no content supported */
    public const SIZE_SMALL = 's';

    /** Medium, Icon as content only */
    public const SIZE_MEDIUM = 'm';

    /** Medium large, Icon as content only */
    public const SIZE_MEDIUM_LARGE = 'ml';

    /** Big, Icon and text as content */
    public const SIZE_BIG = 'l';

    /** Large, Icon and text as content */
    public const SIZE_LARGE = 'xl';

    protected $tag = 'span';

    protected $defaultAttributes = ['class' => ['ball']];

    /** @var SIZE */
    protected $size = self::SIZE_LARGE;

    /**
     * Create a new ball element
     *
     * @param SIZE $size
     */
    public function __construct(string $size = self::SIZE_LARGE)
    {
        $size = trim($size);
        if (empty($size)) {
            $size = self::SIZE_MEDIUM;
        }

        $this->size = $size;
    }

    /**
     * Register the attribute callbacks
     *
     * @param Attributes $attributes
     */
    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes->registerAttributeCallback('class', function () {
            return new Attribute('class', $this->assembleCssClasses());
        });
    }

    /**
     * Assemble the CSS classes for the ball
     *
     * @return array
     */
    protected function assembleCssClasses(): array
    {
        return ['ball-size-' . $this->size];
    }

    /**
     * Add content to the ball
     *
     * Automatically wraps text content in a span element.
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function addHtml(ValidHtml ...$content): static
    {
        if (count($content) === 1 && $content[0] instanceof Text) {
            $content[0] = new HtmlElement('span', null, $content[0]);
        }

        return parent::addHtml(...$content);
    }
}
