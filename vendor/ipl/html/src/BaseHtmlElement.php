<?php

namespace ipl\Html;

use InvalidArgumentException;
use RuntimeException;

/**
 * Base class for HTML elements
 *
 * Extend this class in order to provide concrete HTML elements or series of HTML elements, e.g. widgets.
 * When extending this class you should provide the element's tag with {@link $tag}. Setting default attributes is
 * possible via {@link $defaultAttributes}. And the content of the element is provided in {@link assemble()}.
 *
 * # Example Usage
 * ```
 * namespace Acme\Widgets;
 *
 * use ipl\Html\BaseHtmlElement;
 *
 * class Dashboard extends BaseHtmlElement
 * {
 *     protected $defaultAttributes = ['class' => 'acme-dashboard'];
 *
 *     protected $tag = 'div';
 *
 *     protected function assemble()
 *     {
 *         // ...
 *         $this->add($content);
 *     }
 * }
 * ```
 */
abstract class BaseHtmlElement extends HtmlDocument
{
    /**
     * List of void elements which must not contain end tags or content
     *
     * If {@link $isVoid} is null, this property should be used to decide whether the content and end tag has to be
     * rendered.
     *
     * @var array
     *
     * @see https://www.w3.org/TR/html5/syntax.html#void-elements
     */
    protected static $voidElements = [
        'area'   => 1,
        'base'   => 1,
        'br'     => 1,
        'col'    => 1,
        'embed'  => 1,
        'hr'     => 1,
        'img'    => 1,
        'input'  => 1,
        'link'   => 1,
        'meta'   => 1,
        'param'  => 1,
        'source' => 1,
        'track'  => 1,
        'wbr'    => 1
    ];

    /** @var Attributes */
    protected $attributes;

    /** @var bool Whether possible attribute callbacks have been registered */
    protected $attributeCallbacksRegistered = false;

    /** @var bool|null Whether the element is void. If null, void check should use {@link $voidElements} */
    protected $isVoid;

    /** @var array<string, mixed> You may want to set default attributes when extending this class */
    protected $defaultAttributes;

    /** @var string Tag of element. Set this property in order to provide the element's tag when extending this class */
    protected $tag;

    /**
     * Get the attributes of the element
     *
     * @return Attributes
     */
    public function getAttributes()
    {
        if ($this->attributes === null) {
            $default = $this->getDefaultAttributes();
            if (empty($default)) {
                $this->attributes = new Attributes();
            } else {
                $this->attributes = Attributes::wantAttributes($default);
            }

            $this->ensureAttributeCallbacksRegistered();
        }

        return $this->attributes;
    }

    /**
     * Set the attributes of the element
     *
     * @param Attributes|array|null $attributes
     *
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->attributes = Attributes::wantAttributes($attributes);

        $this->attributeCallbacksRegistered = false;
        $this->ensureAttributeCallbacksRegistered();

        return $this;
    }

    /**
     * Return true if the attribute with the given name exists, false otherwise
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return $this->getAttributes()->has($name);
    }

    /**
     * Get the attribute with the given name
     *
     * If the attribute does not already exist, an empty one is automatically created and added to the attributes.
     *
     * @param string $name
     *
     * @return Attribute
     *
     * @throws InvalidArgumentException If the attribute does not yet exist and its name contains special characters
     */
    public function getAttribute(string $name): Attribute
    {
        return $this->getAttributes()->get($name);
    }

    /**
     * Set the attribute with the given name and value
     *
     * If the attribute with the given name already exists, it gets overridden.
     *
     * @param string            $name  The name of the attribute
     * @param string|bool|array $value The value of the attribute
     *
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->getAttributes()->set($name, $value);

        return $this;
    }

    /**
     * Remove the attribute with the given name or remove the given value from the attribute
     *
     * @param string $name  The name of the attribute
     * @param null|string|array $value The value to remove if specified
     *
     * @return ?Attribute The removed or changed attribute, if any, otherwise null
     */
    public function removeAttribute(string $name, $value = null): ?Attribute
    {
        return $this->getAttributes()->remove($name, $value);
    }

    /**
     * Add the given attributes
     *
     * @param Attributes|array $attributes
     *
     * @return $this
     */
    public function addAttributes($attributes)
    {
        $this->getAttributes()->add($attributes);

        return $this;
    }

    /**
     * Get the default attributes of the element
     *
     * @return array
     */
    public function getDefaultAttributes()
    {
        return $this->defaultAttributes;
    }

    /**
     * Get the tag of the element
     *
     * Since HTML Elements must have a tag, this method throws an exception if the element does not have a tag.
     *
     * @return string
     *
     * @throws RuntimeException If the element does not have a tag
     */
    final public function getTag()
    {
        $tag = $this->tag();

        if (! $tag) {
            throw new RuntimeException('Element must have a tag');
        }

        return $tag;
    }

    /**
     * Set the tag of the element
     *
     * @param string $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get whether the element is void
     *
     * The default void detection which checks whether the element's tag is in the list of void elements according to
     * https://www.w3.org/TR/html5/syntax.html#void-elements.
     *
     * If you want to override this behavior, use {@link setVoid()}.
     *
     * @return bool
     */
    public function isVoid()
    {
        if ($this->isVoid !== null) {
            return $this->isVoid;
        }

        $tag = $this->getTag();

        return isset(self::$voidElements[$tag]);
    }

    /**
     * Set whether the element is void
     *
     * You may use this method to override the default void detection which checks whether the element's tag is in the
     * list of void elements according to https://www.w3.org/TR/html5/syntax.html#void-elements.
     *
     * If you specify null, void detection is reset to its default behavior.
     *
     * @param bool|null $void
     *
     * @return $this
     */
    public function setVoid($void = true)
    {
        $this->isVoid = $void === null ?: (bool) $void;

        return $this;
    }

    /**
     * Ensure that possible attribute callbacks have been registered
     *
     * Note that this method is automatically called in {@link getAttributes()} and {@link setAttributes()}.
     *
     * @return $this
     */
    public function ensureAttributeCallbacksRegistered()
    {
        if (! $this->attributeCallbacksRegistered) {
            $this->attributeCallbacksRegistered = true;
            $this->registerAttributeCallbacks($this->attributes);
        }

        return $this;
    }

    /**
     * Render the content of the element to HTML
     *
     * @return string
     */
    public function renderContent()
    {
        return parent::renderUnwrapped();
    }

    /**
     * Get whether the closing tag should be rendered
     *
     * @return bool True for void elements, false otherwise
     */
    public function wantsClosingTag()
    {
        // TODO: There is more. SVG and MathML namespaces
        return ! $this->isVoid();
    }

    /**
     * Use this element to wrap the given document
     *
     * @param HtmlDocument $document
     *
     * @return $this
     */
    public function wrap(HtmlDocument $document)
    {
        $document->addWrapper($this);

        return $this;
    }

    /**
     * Internal method for accessing the tag
     *
     * You may override this method in order to provide the tag dynamically
     *
     * @return string
     */
    protected function tag()
    {
        return $this->tag;
    }

    /**
     * Register attribute callbacks
     *
     * Override this method in order to register attribute callbacks in concrete classes.
     */
    protected function registerAttributeCallbacks(Attributes $attributes)
    {
    }

    public function addHtml(ValidHtml ...$content)
    {
        $this->ensureAssembled();

        parent::addHtml(...$content);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws RuntimeException If the element does not have a tag or is void but has content
     */
    public function renderUnwrapped()
    {
        $this->ensureAssembled();

        $tag = $this->getTag();
        $content = $this->renderContent();
        $attributes = $this->getAttributes()->render();

        if (strlen($this->contentSeparator)) {
            $length = strlen($content);
            if ($length > 0) {
                if ($content[0] === '<') {
                    $content = $this->contentSeparator . $content;
                    $length++;
                }
                if ($content[$length - 1] === '>') {
                    $content .= $this->contentSeparator;
                }
            }
        }

        if (! $this->wantsClosingTag()) {
            if (strlen($content)) {
                throw new RuntimeException('Void elements must not have content');
            }

            return sprintf('<%s%s />', $tag, $attributes);
        }

        return sprintf(
            '<%s%s>%s</%s>',
            $tag,
            $attributes,
            $content,
            $tag
        );
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->attributes !== null) {
            $this->attributes = clone $this->attributes;
        }
    }
}
