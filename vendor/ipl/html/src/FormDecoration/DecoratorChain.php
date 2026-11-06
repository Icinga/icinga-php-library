<?php

namespace ipl\Html\FormDecoration;

use Generator;
use InvalidArgumentException;
use ipl\Html\Contract\DecoratorOptionsInterface;
use ipl\Stdlib\Plugins;
use IteratorAggregate;
use UnexpectedValueException;
use ValueError;

use function ipl\Stdlib\get_php_type;

/**
 * DecoratorChain for form elements
 *
 * @template TDecorator of object
 * @implements IteratorAggregate<int, TDecorator>
 *
 * @phpstan-type Ident string|class-string
 * @phpstan-type decoratorOptionsFormat array<string, mixed>
 * @phpstan-type _decoratorsFormat1 array<Ident, decoratorOptionsFormat>
 * @phpstan-type _decoratorsFormat2 array<int, Ident|TDecorator|array{name: Ident, options?: decoratorOptionsFormat}>
 * @phpstan-type decoratorsFormat _decoratorsFormat1 | _decoratorsFormat2
 */
class DecoratorChain implements IteratorAggregate
{
    use Plugins;

    /** @var class-string<TDecorator> The type of decorator to accept */
    private string $decoratorType;

    /** @var array<string, TDecorator> All registered decorators */
    private array $decorators = [];

    /**
     * Create a new decorator chain
     *
     * @param class-string<TDecorator> $decoratorType The type of decorator to accept
     */
    public function __construct(string $decoratorType)
    {
        $this->decoratorType = $decoratorType;

        $this->addDefaultPluginLoader('decorator', __NAMESPACE__, 'Decorator');
    }

    /**
     * Add a decorator loader
     *
     * @param string $namespace Namespace of the decorator(s)
     * @param string $suffix    Decorator class name suffix, if any
     *
     * @return $this
     */
    public function addDecoratorLoader(string $namespace, string $suffix = ''): static
    {
        $this->addPluginLoader('decorator', $namespace, $suffix);

        return $this;
    }

    /**
     * Get whether the chain has a decorator with the given identifier
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function hasDecorator(string $identifier): bool
    {
        return isset($this->decorators[$identifier]);
    }

    /**
     * Get the decorator with the given identifier
     *
     * @param string $identifier
     *
     * @return TDecorator
     *
     * @throws InvalidArgumentException If the given identifier is unknown
     */
    public function getDecorator(string $identifier): object
    {
        if (! $this->hasDecorator($identifier)) {
            throw new InvalidArgumentException(sprintf('No decorator with identifier "%s" registered', $identifier));
        }

        return $this->decorators[$identifier];
    }

    /**
     * Add a decorator to the chain
     *
     * @param string                 $identifier
     * @param TDecorator|Ident       $decorator
     * @param decoratorOptionsFormat $options Only allowed if parameter #2 is a string
     *
     * @return $this
     *
     * @throws ValueError If the decorator with the given identifier already exists
     * @throws InvalidArgumentException If the decorator specification is invalid
     */
    public function addDecorator(string $identifier, object|string $decorator, array $options = []): static
    {
        if (isset($this->decorators[$identifier])) {
            throw new ValueError(sprintf(
                'Decorator with identifier "%s" already exists. Use replaceDecorator() to replace it.',
                $identifier
            ));
        } elseif (! empty($options) && ! is_string($decorator)) {
            throw new InvalidArgumentException('No options are allowed with parameter #2 of type Decorator');
        }

        if (is_string($decorator)) {
            $decorator = $this->createDecorator($decorator, $options);
        } elseif (! $decorator instanceof $this->decoratorType) {
            throw new InvalidArgumentException(sprintf(
                'Expects parameter #2 to be a string or an instance of %s, got %s instead',
                $this->decoratorType,
                get_php_type($decorator)
            ));
        }

        $this->decorators[$identifier] = $decorator;

        return $this;
    }

    /**
     * Replace a decorator in the chain with a new one
     *
     * @param string                 $identifier
     * @param TDecorator|Ident       $decorator
     * @param decoratorOptionsFormat $options Only allowed if parameter #2 is a string
     *
     * @return $this
     */
    public function replaceDecorator(string $identifier, object|string $decorator, array $options = []): static
    {
        $this->decorators[$identifier] = null; // Preserve the placement of the previous decorator

        return $this->addDecorator($identifier, $decorator, $options);
    }

    /**
     * Add the decorators from the given decorator specification to the chain
     *
     * The order of the decorators is important, as it determines the rendering order.
     *
     * > NOTE: Decorators are registered with unique identifiers to enable easy replacement. If the decorator is
     * provided as a string and no identifier is given, the decorator name is used as a fallback. In all other cases,
     * an identifier must be specified explicitly.
     *
     *  *It is recommended to use the decorator name or its purpose as the identifier, for example, 'container' for an
     * 'HtmlTag' decorator that wraps content.*
     *
     *   *The following array formats are supported:*
     *
     * ```
     * // (1) When no options are required or defaults are sufficient
     * $decorators = [
     *     'Label',
     *     'Description'
     * ];
     *
     * // For the array above, the identifiers are 'Label' and 'Description'.
     *
     *  // (2) Use Custom identifiers
     *  $decorators = [
     *     'your-custom-identifier' => 'your-custom-decorator',
     *  ];
     *
     * // NOTE: For the following formats, identifiers MUST be defined manually.
     *
     * // (3) Override default options, key: decorator identifier, value: name and options
     * $decorators = [
     *     'Label' => 'Label',
     *     'container' => [
     *          'name'      => 'HtmlTag',
     *          'options'   => ['tag' => 'div']
     *      ]
     * ];
     *
     * // (4) Add Decorator instances
     * $decorators = [
     *     'Label'      => new LabelDecorator(),
     *     'container'  => (new HtmlTagDecorator())->setTag('div')->setAttrs(['class' => 'container'])
     * ];
     *
     * // (5) Class paths are also supported
     * $decorators = [
     *     'Label'      => LabelDecorator::class,
     *     'container'  => ['name' => HtmlTagDecorator::class, 'options' => ['tag' => 'div']]
     * ];
     * ```
     *
     * @param static<TDecorator>|decoratorsFormat $decorators
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the decorator specification is invalid
     */
    public function addDecorators(DecoratorChain|array $decorators): static
    {
        if ($decorators instanceof static) {
            foreach ($decorators->decorators as $identifier => $decorator) {
                $this->addDecorator($identifier, $decorator);
            }

            return $this;
        }

        foreach ($decorators as $decoratorName => $decoratorOptions) {
            $identifier = $decoratorName;
            if (is_int($identifier)) {
                if (! is_string($decoratorOptions)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unexpected type at position %d, string expected, got %s instead.'
                        . ' Either provide an $identifier (string) as the key, or ensure the value is of type string',
                        $identifier,
                        get_php_type($decoratorOptions)
                    ));
                } elseif (class_exists($decoratorOptions)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unexpected type at position %d, string expected, got class %s instead.'
                        . ' Either provide an $identifier (string) as the key, or ensure the value is of type string',
                        $identifier,
                        $decoratorOptions
                    ));
                }

                $decoratorName = $decoratorOptions;
                $identifier = $decoratorName;
                $decoratorOptions = [];
            } elseif (is_string($decoratorOptions) || $decoratorOptions instanceof $this->decoratorType) {
                $decoratorName = $decoratorOptions;
                $decoratorOptions = [];
            } elseif (is_array($decoratorOptions)) {
                if (! isset($decoratorOptions['name'])) {
                    throw new InvalidArgumentException(sprintf(
                        "Invalid decorator '%s'. Key 'name' is missing",
                        $identifier
                    ));
                } elseif (! is_string($decoratorOptions['name'])) {
                    throw new InvalidArgumentException(sprintf(
                        "Invalid decorator '%s'. Value of the 'name' key must be a string, got %s instead",
                        $identifier,
                        get_php_type($decoratorOptions['name'])
                    ));
                }

                $decoratorName = $decoratorOptions['name'];
                unset($decoratorOptions['name']);

                $options = [];
                if (isset($decoratorOptions['options'])) {
                    $options = $decoratorOptions['options'];

                    unset($decoratorOptions['options']);
                }

                if (! empty($decoratorOptions)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "No other keys except 'name' and 'options' are allowed, got '%s'",
                            implode("', '", array_keys($decoratorOptions))
                        )
                    );
                }

                $decoratorOptions = $options;
            } else {
                throw new InvalidArgumentException(sprintf(
                    "Invalid type for identifier '%s', expected an array,"
                    . " a string or an instance of %s, got %s instead",
                    $identifier,
                    $this->decoratorType,
                    get_php_type($decoratorOptions)
                ));
            }

            if ($this->hasDecorator($identifier)) {
                throw new InvalidArgumentException(sprintf(
                    "Decorator with identifier '%s' already exists. Duplicate identifiers are not allowed.",
                    $identifier
                ));
            }

            $this->addDecorator($identifier, $decoratorName, $decoratorOptions);
        }

        return $this;
    }

    /**
     * Clear all decorators from the chain
     *
     * @return $this
     */
    public function clearDecorators(): static
    {
        $this->decorators = [];

        return $this;
    }

    /**
     * Create a decorator from the given name and options
     *
     * @param Ident $name
     * @param decoratorOptionsFormat $options
     *
     * @return TDecorator
     *
     * @throws InvalidArgumentException If the given decorator is unknown or not an instance of the expected type
     * @throws UnexpectedValueException If the loaded decorator is not an instance of the expected type
     */
    protected function createDecorator(string $name, array $options = []): object
    {
        if (class_exists($name)) {
            $decorator = new $name();
            if (! $decorator instanceof $this->decoratorType) {
                throw new InvalidArgumentException(sprintf(
                    "Invalid decorator class '%s'. decorator must be an instance of %s",
                    $name,
                    $this->decoratorType,
                ));
            }
        } else {
            $class = $this->loadPlugin('decorator', $name);
            if (! $class) {
                throw new InvalidArgumentException(sprintf(
                    "Can't load decorator '%s'. decorator unknown",
                    $name
                ));
            }

            $decorator = new $class();
            if (! $decorator instanceof $this->decoratorType) {
                throw new UnexpectedValueException(sprintf(
                    "%s expects loader to return an instance of %s for decorator '%s', got %s instead",
                    __METHOD__,
                    $this->decoratorType,
                    $name,
                    get_php_type($decorator)
                ));
            }
        }

        if (! empty($options)) {
            if (! $decorator instanceof DecoratorOptionsInterface) {
                throw new InvalidArgumentException(sprintf("Decorator '%s' does not support options", $name));
            }

            $decorator->getAttributes()->add($options);
        }

        return $decorator;
    }

    /**
     * Get whether the chain has decorators
     *
     * @return bool
     */
    public function hasDecorators(): bool
    {
        return ! empty($this->decorators);
    }

    /**
     * Iterate over all decorators
     *
     * @return Generator<TDecorator>
     */
    #[\Override]
    public function getIterator(): Generator
    {
        foreach ($this->decorators as $decorator) {
            yield $decorator;
        }
    }
}
