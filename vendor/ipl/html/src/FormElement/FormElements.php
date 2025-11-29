<?php

namespace ipl\Html\FormElement;

use InvalidArgumentException;
use ipl\Html\Contract\DecorableFormElement;
use ipl\Html\Contract\DefaultFormElementDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\Contract\ValueCandidates;
use ipl\Html\Form;
use ipl\Html\FormDecoration\DecoratorChain;
use ipl\Html\FormDecorator\DecoratorInterface;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Events;
use ipl\Stdlib\Plugins;
use RuntimeException;
use UnexpectedValueException;
use WeakMap;

use function ipl\Stdlib\get_php_type;

/**
 * @phpstan-import-type decoratorsFormat from DecoratorChain
 * @phpstan-import-type loaderPaths from DefaultFormElementDecoration
 */
trait FormElements
{
    use Events;
    use Plugins;

    /** @var FormElementDecorator|null */
    private $defaultElementDecorator;

    /** @var bool Whether the default element decorator loader has been registered */
    protected $defaultElementDecoratorLoaderRegistered = false;

    /** @var bool Whether the default element loader has been registered */
    protected $defaultElementLoaderRegistered = false;

    /** @var ?WeakMap<FormElement & DecorableFormElement, bool> The decorated elements */
    private ?WeakMap $decoratedElements = null;

    /**
     * Custom Element decorator loader paths
     *
     * Override this property to add custom decorator loader paths.
     *
     * @var loaderPaths
     */
    protected array $elementDecoratorLoaderPaths = [];

    /**
     * Default element decorators
     *
     * Override this property to change the default decorators of the elements.
     *
     * Please see {@see DecoratorChain::addDecorators()} for the supported array formats.
     *
     * @var decoratorsFormat
     */
    protected array $defaultElementDecorators = [];

    /** @var FormElement[] */
    private $elements = [];

    /** @var array<string, array<int, mixed>> */
    private $populatedValues = [];

    /**
     * Get the default element decorators.
     *
     * @return decoratorsFormat
     */
    public function getDefaultElementDecorators(): array
    {
        return $this->defaultElementDecorators;
    }

    public function setDefaultElementDecorators(array $decorators): static
    {
        if ($this->hasDefaultElementDecorator()) {
            throw new RuntimeException(sprintf(
                'Cannot use element decorators of type %s and legacy decorator of type %s together',
                FormElementDecoration::class,
                FormElementDecorator::class
            ));
        }

        $this->defaultElementDecorators = $decorators;

        return $this;
    }

    public function addElementDecoratorLoaderPaths(array $loaderPaths): static
    {
        $this->elementDecoratorLoaderPaths = $loaderPaths;

        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }

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

        if ($element instanceof DecorableFormElement) {
            $customDecoratorPaths = $this->elementDecoratorLoaderPaths;
            $elementDecoratorChain = $element->getDecorators();
            if (! empty($customDecoratorPaths)) {
                if ($element instanceof DefaultFormElementDecoration) {
                    $element->addElementDecoratorLoaderPaths($customDecoratorPaths);
                }

                foreach ($customDecoratorPaths as $path) {
                    $elementDecoratorChain->addDecoratorLoader($path[0], $path[1] ?? '');
                }
            }

            $defaultDecorators = $this->getDefaultElementDecorators();
            if (
                ! empty($defaultDecorators)
                && ! $this->hasDefaultElementDecorator()
                && empty($options['hidden'])
                && ! $element instanceof HiddenElement
                && $element->getAttributes()->get('hidden')->isEmpty()
            ) {
                if ($element instanceof DefaultFormElementDecoration) {
                    $element->setDefaultElementDecorators($defaultDecorators);
                }

                if (! isset($options['decorators'])) {
                    $elementDecoratorChain->addDecorators($defaultDecorators);
                }
            }
        }

        if ($options !== null) {
            $element->addAttributes($options);
        }

        return $element;
    }

    public function registerElement(FormElement $element)
    {
        $name = $element->getName();

        // This check is required as the getEscapedName method is not implemented in
        // the FormElement interface
        if ($element instanceof BaseFormElement) {
            $escapedName = $element->getEscapedName();
        } else {
            $escapedName = $name;
        }

        if ($name === null) {
            throw new InvalidArgumentException(sprintf(
                '%s expects the element to provide a name',
                __METHOD__
            ));
        }

        $this->elements[$name] = $element;

        if (array_key_exists($escapedName, $this->populatedValues)) {
            $element->setValue(
                $this->populatedValues[$escapedName][count($this->populatedValues[$escapedName]) - 1]
            );

            if ($element instanceof ValueCandidates) {
                $element->setValueCandidates($this->populatedValues[$escapedName]);
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
     * @deprecated This is not of general use anymore. The new decorator implementation handles defaults entirely
     *             internally now. Use {@see getDefaultElementDecorators()} instead only if you absolutely have to.
     */
    public function hasDefaultElementDecorator()
    {
        return $this->defaultElementDecorator !== null;
    }

    /**
     * Get the default element decorator, if any
     *
     * @return FormElementDecorator|null
     * @deprecated Use {@see getDefaultElementDecorators()} instead
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
     * @deprecated Use {@see setDefaultElementDecorators()} instead
     * @throws InvalidArgumentException If $decorator is a string and can't be loaded from registered decorator loaders
     *                                  or if a decorator loader does not return an instance of
     *                                  {@link FormElementDecorator}
     */
    public function setDefaultElementDecorator($decorator)
    {
        if (! empty($this->getDefaultElementDecorators())) {
            throw new RuntimeException(sprintf(
                'Cannot use element decorators of type %s and legacy decorator of type %s together',
                FormElementDecoration::class,
                FormElementDecorator::class
            ));
        }

        if ($decorator instanceof FormElementDecorator || $decorator instanceof DecoratorInterface) {
            $this->defaultElementDecorator = $decorator;
        } else {
            $this->ensureDefaultElementDecoratorLoaderRegistered();

            $class = $this->loadPlugin('decorator', $decorator);
            if (! $class) {
                throw new InvalidArgumentException(sprintf(
                    "Can't create decorator of unknown type '%s",
                    $decorator
                ));
            }

            $d = new $class();
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

    public function populate($values)
    {
        foreach ($values as $name => $value) {
            $this->populatedValues[Form::escapeReservedChars($name)][] = $value;
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
        $name = Form::escapeReservedChars($name);
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
        $name = Form::escapeReservedChars($name);
        if (isset($this->populatedValues[$name])) {
            unset($this->populatedValues[$name]);
        }

        return $this;
    }

    /**
     * Add all elements from the given element collection
     *
     * @param Form $form
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
     * @deprecated Use {@see addElementDecoratorLoaderPaths()} instead
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
        if ($element instanceof DecorableFormElement && $element->hasDecorators()) {
            // new decorator implementation in use
            $this->decoratedElements ??= new WeakMap();
            if (! isset($this->decoratedElements[$element])) {
                $this->decoratedElements[$element] = false;
            }

            return $this;
        }

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
            Form::ON_SUBMIT,
            Form::ON_SENT,
            Form::ON_ERROR,
            Form::ON_REQUEST,
            Form::ON_VALIDATE,
            Form::ON_ELEMENT_REGISTERED,
        ]);
    }

    public function removeElement(string|FormElement $element)
    {
        if (is_string($element)) {
            if (! $this->hasElement($element)) {
                return $this;
            }

            $element = $this->getElement($element);
        }

        return $this->remove($element);
    }

    public function remove(ValidHtml $content)
    {
        if ($content instanceof FormElement) {
            if ($this->hasElement($content)) {
                if (isset($this->decoratedElements[$content])) {
                    unset($this->decoratedElements[$content]);
                }

                $name = array_search($content, $this->elements, true);
                if ($name !== false) {
                    unset($this->elements[$name]);
                }
            }
        }

        return parent::remove($content);
    }

    protected function beforeRender(): void
    {
        if ($this instanceof HtmlDocument) {
            parent::beforeRender();
        }

        foreach ($this->decoratedElements ?? [] as $element => &$decorated) {
            if (! $decorated) {
                $element->applyDecoration();
                $decorated = true;
            }
        }
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
