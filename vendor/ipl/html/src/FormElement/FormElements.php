<?php

namespace ipl\Html\FormElement;

use InvalidArgumentException;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\Contract\ValueCandidates;
use ipl\Html\Form;
use ipl\Html\FormDecorator\DecoratorInterface;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Events;
use ipl\Stdlib\Plugins;
use UnexpectedValueException;

use function ipl\Stdlib\get_php_type;

trait FormElements
{
    use Events;
    use Plugins;

    /** @var FormElementDecorator|null */
    private $defaultElementDecorator;

    /** @var bool Whether the default element decorator loader has been registered */
    private $defaultElementDecoratorLoaderRegistered = false;

    /** @var bool Whether the default element loader has been registered */
    private $defaultElementLoaderRegistered = false;

    /** @var FormElement[] */
    private $elements = [];

    /** @var array */
    private $populatedValues = [];

    /**
     * Get all elements
     *
     * @return FormElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Get whether the given element exists
     *
     * @param string|FormElement $element
     *
     * @return bool
     */
    public function hasElement($element)
    {
        if (is_string($element)) {
            return array_key_exists($element, $this->elements);
        }

        if ($element instanceof FormElement) {
            return in_array($element, $this->elements, true);
        }

        return false;
    }

    /**
     * Get the element by the given name
     *
     * @param string $name
     *
     * @return FormElement
     *
     * @throws InvalidArgumentException If no element with the given name exists
     */
    public function getElement($name)
    {
        if (! array_key_exists($name, $this->elements)) {
            throw new InvalidArgumentException(sprintf(
                "Can't get element '%s'. Element does not exist",
                $name
            ));
        }

        return $this->elements[$name];
    }

    /**
     * Add an element
     *
     * @param string|FormElement $typeOrElement Type of the element as string or an instance of FormElement
     * @param string             $name          Name of the element
     * @param mixed              $options       Element options as key-value pairs
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $typeOrElement is neither a string nor an instance of FormElement
     *                                  or if $typeOrElement is a string and $name is not set
     *                                  or if $typeOrElement is a string but type is unknown
     *                                  or if $typeOrElement is an instance of FormElement but does not have a name
     */
    public function addElement($typeOrElement, $name = null, $options = null)
    {
        if (is_string($typeOrElement)) {
            if ($name === null) {
                throw new InvalidArgumentException(sprintf(
                    '%s expects parameter 2 to be set if parameter 1 is a string',
                    __METHOD__
                ));
            }

            $element = $this->createElement($typeOrElement, $name, $options);
        } elseif ($typeOrElement instanceof FormElement) {
            $element = $typeOrElement;
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s() expects parameter 1 to be a string or an instance of %s, %s given',
                __METHOD__,
                FormElement::class,
                get_php_type($typeOrElement)
            ));
        }

        $this
            ->registerElement($element) // registerElement() must be called first because of the name check
            ->decorate($element)
            ->addHtml($element);

        return $this;
    }

    /**
     * Create an element
     *
     * @param string $type    Type of the element
     * @param string $name    Name of the element
     * @param mixed  $options Element options as key-value pairs
     *
     * @return FormElement
     *
     * @throws InvalidArgumentException If the type of the element is unknown
     */
    public function createElement($type, $name, $options = null)
    {
        $this->ensureDefaultElementLoaderRegistered();

        $class = $this->loadPlugin('element', $type);

        if (! $class) {
            throw new InvalidArgumentException(sprintf(
                "Can't create element of unknown type '%s",
                $type
            ));
        }

        /** @var FormElement $element */
        $element = new $class($name);

        if ($options !== null) {
            $element->addAttributes($options);
        }

        return $element;
    }

    /**
     * Register an element
     *
     * Registers the element for value and validation handling but does not add it to the render stack.
     *
     * @param FormElement $element
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $element does not provide a name
     */
    public function registerElement(FormElement $element)
    {
        $name = $element->getName();

        if ($name === null) {
            throw new InvalidArgumentException(sprintf(
                '%s expects the element to provide a name',
                __METHOD__
            ));
        }

        $this->elements[$name] = $element;

        if (array_key_exists($name, $this->populatedValues)) {
            $element->setValue($this->populatedValues[$name][count($this->populatedValues[$name]) - 1]);

            if ($element instanceof ValueCandidates) {
                $element->setValueCandidates($this->populatedValues[$name]);
            }
        }

        $this->onElementRegistered($element);
        $this->emit(Form::ON_ELEMENT_REGISTERED, [$element]);

        return $this;
    }

    /**
     * Get whether a default element decorator exists
     *
     * @return bool
     */
    public function hasDefaultElementDecorator()
    {
        return $this->defaultElementDecorator !== null;
    }

    /**
     * Get the default element decorator, if any
     *
     * @return FormElementDecorator|null
     */
    public function getDefaultElementDecorator()
    {
        return $this->defaultElementDecorator;
    }

    /**
     * Set the default element decorator
     *
     * If $decorator is a string, the decorator will be automatically created from a registered decorator loader.
     * A loader for the namespace ipl\\Html\\FormDecorator is automatically registered by default.
     * See {@link addDecoratorLoader()} for registering a custom loader.
     *
     * @param FormElementDecorator|string $decorator
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $decorator is a string and can't be loaded from registered decorator loaders
     *                                  or if a decorator loader does not return an instance of
     *                                  {@link FormElementDecorator}
     */
    public function setDefaultElementDecorator($decorator)
    {
        if ($decorator instanceof FormElementDecorator || $decorator instanceof DecoratorInterface) {
            $this->defaultElementDecorator = $decorator;
        } else {
            $this->ensureDefaultElementDecoratorLoaderRegistered();

            $d = $this->loadPlugin('decorator', $decorator);

            if (! $d instanceof FormElementDecorator && ! $d instanceof DecoratorInterface) {
                throw new InvalidArgumentException(sprintf(
                    "Expected instance of %s for decorator '%s',"
                    . " got %s from a decorator loader instead",
                    FormElementDecorator::class,
                    $decorator,
                    get_php_type($d)
                ));
            }

            $this->defaultElementDecorator = $d;
        }

        return $this;
    }

    /**
     * Get the value of the element specified by name
     *
     * Returns $default if the element does not exist or has no value.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getValue($name, $default = null)
    {
        if ($this->hasElement($name)) {
            $value = $this->getElement($name)->getValue();
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the values for all but ignored elements
     *
     * @return array Values as name-value pairs
     */
    public function getValues()
    {
        $values = [];
        foreach ($this->getElements() as $element) {
            if (! $element->isIgnored()) {
                $values[$element->getName()] = $element->getValue();
            }
        }

        return $values;
    }

    /**
     * Populate values of registered elements
     *
     * @param iterable $values Values as name-value pairs
     *
     * @return $this
     */
    public function populate($values)
    {
        foreach ($values as $name => $value) {
            $this->populatedValues[$name][] = $value;
            if ($this->hasElement($name)) {
                $this->getElement($name)->setValue($value);
            }
        }

        return $this;
    }

    /**
     * Get the populated value of the element specified by name
     *
     * Returns $default if there is no populated value for this element.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPopulatedValue($name, $default = null)
    {
        return isset($this->populatedValues[$name])
            ? $this->populatedValues[$name][count($this->populatedValues[$name]) - 1]
            : $default;
    }

    /**
     * Clear populated value of the given element
     *
     * @param string $name
     *
     * @return $this
     */
    public function clearPopulatedValue($name)
    {
        if (! $this->hasBeenSubmitted() && isset($this->populatedValues[$name])) {
            unset($this->populatedValues[$name]);
        }

        return $this;
    }

    /**
     * Add all elements from the given element collection
     *
     * @param Form|SubFormElement $form
     *
     * @return $this
     */
    public function addElementsFrom($form)
    {
        foreach ($form->getElements() as $element) {
            $this->addElement($element);
        }

        return $this;
    }

    /**
     * Add a decorator loader
     *
     * @param string $namespace Namespace of the decorators
     * @param string $postfix   Decorator name postfix, if any
     *
     * @return $this
     */
    public function addDecoratorLoader($namespace, $postfix = null)
    {
        $this->addPluginLoader('decorator', $namespace, $postfix);

        return $this;
    }

    /**
     * Add an element loader
     *
     * @param string $namespace Namespace of the elements
     * @param string $postfix   Element name postfix, if any
     *
     * @return $this
     */
    public function addElementLoader($namespace, $postfix = null)
    {
        $this->addPluginLoader('element', $namespace, $postfix);

        return $this;
    }

    /**
     * Ensure that our default element decorator loader is registered
     *
     * @return $this
     */
    protected function ensureDefaultElementDecoratorLoaderRegistered()
    {
        if (! $this->defaultElementDecoratorLoaderRegistered) {
            $this->addDefaultPluginLoader(
                'decorator',
                'ipl\\Html\\FormDecorator',
                'Decorator'
            );

            $this->defaultElementDecoratorLoaderRegistered = true;
        }

        return $this;
    }

    /**
     * Ensure that our default element loader is registered
     *
     * @return $this
     */
    protected function ensureDefaultElementLoaderRegistered()
    {
        if (! $this->defaultElementLoaderRegistered) {
            $this->addDefaultPluginLoader('element', __NAMESPACE__, 'Element');

            $this->defaultElementLoaderRegistered = true;
        }

        return $this;
    }

    /**
     * Decorate the given element
     *
     * @param FormElement $element
     *
     * @return $this
     *
     * @throws UnexpectedValueException If the default decorator is set but not an instance of
     *                                  {@link FormElementDecorator}
     */
    protected function decorate(FormElement $element)
    {
        if ($this->hasDefaultElementDecorator()) {
            $decorator = $this->getDefaultElementDecorator();

            if (! $decorator instanceof FormElementDecorator && ! $decorator instanceof DecoratorInterface) {
                throw new UnexpectedValueException(sprintf(
                    '%s expects the default decorator to be an instance of %s, got %s instead',
                    __METHOD__,
                    FormElementDecorator::class,
                    get_php_type($decorator)
                ));
            }

            $decorator->decorate($element);
        }

        return $this;
    }

    public function isValidEvent($event)
    {
        return in_array($event, [
            Form::ON_SUCCESS,
            Form::ON_SENT,
            Form::ON_ERROR,
            Form::ON_REQUEST,
            Form::ON_VALIDATE,
            Form::ON_ELEMENT_REGISTERED,
        ]);
    }

    public function remove(ValidHtml $elementOrHtml)
    {
        if ($elementOrHtml instanceof FormElement) {
            if ($this->hasElement($elementOrHtml)) {
                $name = array_search($elementOrHtml, $this->elements, true);
                if ($name !== false) {
                    unset($this->elements[$name]);
                }
            }
        }

        parent::remove($elementOrHtml);
    }

    /**
     * Handler which is called after an element has been registered
     *
     * @param FormElement $element
     */
    protected function onElementRegistered(FormElement $element)
    {
    }
}
