<?php

namespace ipl\Html\FormElement;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Validator\DeferredInArrayValidator;
use ipl\Validator\ValidatorChain;

class RadioElement extends BaseFormElement
{
    use Translation;

    /** @var string The element type */
    protected $type = 'radio';

    /** @var RadioOption[] Radio options */
    protected $options = [];

    /** @var array Disabled radio options */
    protected $disabledOptions = [];

    protected function tag(): string
    {
        // In order to be able to decorate this element, we need to return a tag.
        // If we'd have a distinct form element base implementation, that doesn't
        // extend BaseHtmlElement, this wouldn't be necessary.
        return 'bogus';
    }

    /**
     * Set the options
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = [];
        foreach ($options as $value => $label) {
            $option = (new RadioOption($value, $label))
                ->setDisabled(
                    in_array($value, $this->disabledOptions, ! is_int($value))
                    || ($value === '' && in_array(null, $this->disabledOptions, true))
                );

            $this->options[$value] = $option;
        }

        $this->disabledOptions = [];

        return $this;
    }

    /**
     * Get the option with specified value
     *
     * @param string|int $value
     *
     * @return RadioOption
     *
     * @throws InvalidArgumentException If no option with the specified value exists
     */
    public function getOption($value): RadioOption
    {
        if (! isset($this->options[$value])) {
            throw new InvalidArgumentException(sprintf('There is no such option "%s"', $value));
        }

        return $this->options[$value];
    }

    /**
     * Set the specified options as disable
     *
     * @param array $disabledOptions
     *
     * @return $this
     */
    public function setDisabledOptions(array $disabledOptions): self
    {
        if (! empty($this->options)) {
            foreach ($this->options as $value => $option) {
                $option->setDisabled(
                    in_array($value, $disabledOptions, ! is_int($value))
                    || ($value === '' && in_array(null, $disabledOptions, true))
                );
            }

            $this->disabledOptions = [];
        } else {
            $this->disabledOptions = $disabledOptions;
        }

        return $this;
    }

    public function renderUnwrapped()
    {
        // Parent::renderUnwrapped() requires $tag and the content should be empty. However, since we are wrapping
        // each button in a label, the call to parent cannot work here and must be overridden.
        return HtmlDocument::renderUnwrapped();
    }

    protected function assemble()
    {
        // To avoid duplicate ids for options. Required for tests or if someone sets explicitly.
        $this->getAttributes()->remove('id');
        foreach ($this->options as $option) {
            $radio = (new InputElement($this->getValueOfNameAttribute()))
                ->setType($this->type)
                ->setValue($option->getValue());

            // Only add the non-callback attributes to all options
            foreach ($this->getAttributes() as $attribute) {
                $radio->getAttributes()->addAttribute(clone $attribute);
            }

            $radio->getAttributes()
                ->merge($option->getAttributes())
                ->registerAttributeCallback(
                    'checked',
                    function () use ($option) {
                        $optionValue = $option->getValue();

                        return ! is_int($optionValue)
                            ? $this->getValue() === $optionValue
                            : $this->getValue() == $optionValue;
                    }
                )
                ->registerAttributeCallback(
                    'disabled',
                    function () use ($option) {
                        return $this->getAttributes()->get('disabled')->getValue() || $option->isDisabled();
                    }
                );

            $label = new HtmlElement(
                'label',
                new Attributes(['class' => $option->getLabelCssClass()]),
                $radio,
                Text::create($option->getLabel())
            );

            $this->addHtml($label);
        }
    }

    protected function addDefaultValidators(ValidatorChain $chain): void
    {
        $chain->add(new DeferredInArrayValidator(function (): array {
            $possibleValues = [];

            foreach ($this->options as $option) {
                if ($option->isDisabled()) {
                    continue;
                }

                $possibleValues[] = $option->getValue();
            }

            return $possibleValues;
        }));
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $this->getAttributes()->registerAttributeCallback(
            'options',
            null,
            [$this, 'setOptions']
        );

        $this->getAttributes()->registerAttributeCallback(
            'disabledOptions',
            null,
            [$this, 'setDisabledOptions']
        );
    }
}
