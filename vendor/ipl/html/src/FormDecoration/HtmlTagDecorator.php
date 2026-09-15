<?php

namespace ipl\Html\FormDecoration;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\DecoratorOptions;
use ipl\Html\Contract\DecoratorOptionsInterface;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\HtmlElement;
use RuntimeException;
use Throwable;

use function ipl\Stdlib\get_php_type;

/**
 * Decorates the form element with an HTML tag
 */
class HtmlTagDecorator implements FormElementDecoration, DecoratorOptionsInterface
{
    use DecoratorOptions;

    /**
     * Describes how the HTML tag should transform the content. Default: {@see Transformation::Wrap}
     *
     * @var Transformation
     */
    protected Transformation $transformation = Transformation::Wrap;

    /** @var string HTML tag to use for the decoration. */
    protected string $tag;

    /** @var ?callable(FormElement): bool Callable to decide whether to decorate the element */
    protected $condition;

    /** @var ?(string|string[]) CSS classes to apply */
    protected null|string|array $class = null;

    /** @var array<string, mixed> Attributes to apply */
    protected array $attrs = [];

    /**
     * Get the HTML tag to use for the decoration
     *
     * @return string
     *
     * @throws RuntimeException if the tag is not set
     */
    public function getTag(): string
    {
        if (empty($this->tag)) {
            throw new RuntimeException('Option "tag" must be set');
        }

        return $this->tag;
    }

    /**
     * Set the HTML tag to use for the decoration
     *
     * @param string $tag
     *
     * @return $this
     */
    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Set the transformation type of the HTML tag
     *
     * @param Transformation $transformation
     *
     * @return $this
     */
    public function setTransformation(Transformation $transformation): static
    {
        $this->transformation = $transformation;

        return $this;
    }

    /**
     * Get the transformation type of the HTML tag
     *
     * @return Transformation
     */
    public function getTransformation(): Transformation
    {
        return $this->transformation;
    }

    /**
     * Get the condition callable to decide whether to decorate the element
     *
     * @return ?callable(FormElement): bool
     */
    public function getCondition(): ?callable
    {
        return $this->condition;
    }

    /**
     * Set the condition callable to decide whether to decorate the element
     *
     * @param callable(FormElement): bool $condition
     *
     * @return $this
     */
    public function setCondition(callable $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get the css class(es)
     *
     * @return ?(string|string[])
     */
    public function getClass(): string|array|null
    {
        return $this->class;
    }

    /**
     * Set the css class(es)
     *
     * @param string|string[] $class
     *
     * @return $this
     */
    public function setClass(string|array $class): static
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get attributes to apply
     *
     * @return array<string, mixed>
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * Set attributes to apply
     *
     * @param array<string, mixed> $attrs
     *
     * @return $this
     */
    public function setAttrs(array $attrs): static
    {
        $this->attrs = $attrs;

        return $this;
    }

    /**
     * @throws InvalidArgumentException if the condition callback does not return a boolean
     * @throws RuntimeException if the condition callback throws an exception
     */
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        $condition = $this->getCondition();
        if ($condition !== null) {
            try {
                $shouldDecorate = $condition($formElement);
            } catch (Throwable $e) {
                throw new RuntimeException('Condition callback failed', previous:  $e);
            }

            if (! is_bool($shouldDecorate)) {
                throw new InvalidArgumentException(sprintf(
                    'Condition callback must return a boolean, got %s',
                    get_php_type($shouldDecorate)
                ));
            }

            if (! $shouldDecorate) {
                return;
            }
        }

        $class = $this->getClass();
        $this->getTransformation()->apply(
            $result,
            new HtmlElement(
                $this->getTag(),
                $class === null
                    ? new Attributes($this->getAttrs())
                    : new Attributes(['class' => $class] + $this->getAttrs())
            )
        );
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes
            ->registerAttributeCallback('tag', null, $this->setTag(...))
            ->registerAttributeCallback('transformation', null, $this->setTransformation(...))
            ->registerAttributeCallback('condition', null, $this->setCondition(...))
            ->registerAttributeCallback('class', null, $this->setClass(...))
            ->registerAttributeCallback('attrs', null, $this->setAttrs(...));
    }
}
