<?php

namespace ipl\Validator;

use Countable;
use InvalidArgumentException;
use ipl\Stdlib\Contract\Validator;
use ipl\Stdlib\Messages;
use ipl\Stdlib\Plugins;
use ipl\Stdlib\PriorityQueue;
use IteratorAggregate;
use SplObjectStorage;
use UnexpectedValueException;

use function ipl\Stdlib\get_php_type;

class ValidatorChain implements Countable, IteratorAggregate, Validator
{
    use Messages;
    use Plugins;

    /** Default priority at which validators are added */
    const DEFAULT_PRIORITY = 1;

    /** @var PriorityQueue Validator chain */
    protected $validators;

    /** @var SplObjectStorage Validators that break the chain on failure */
    protected $validatorsThatBreakTheChain;

    /**
     * Create a new validator chain
     */
    public function __construct()
    {
        $this->validators = new PriorityQueue();
        $this->validatorsThatBreakTheChain = new SplObjectStorage();

        $this->addDefaultPluginLoader('validator', __NAMESPACE__, 'Validator');
    }

    /**
     * Get the validators that break the chain
     *
     * @return SplObjectStorage
     */
    public function getValidatorsThatBreakTheChain()
    {
        return $this->validatorsThatBreakTheChain;
    }

    /**
     * Add a validator to the chain
     *
     * If $breakChainOnFailure is true and the validator fails, subsequent validators won't be executed.
     *
     * @param Validator $validator
     * @param bool      $breakChainOnFailure
     * @param int       $priority            Priority at which to add validator
     *
     * @return $this
     *
     */
    public function add(Validator $validator, $breakChainOnFailure = false, $priority = self::DEFAULT_PRIORITY)
    {
        $this->validators->insert($validator, $priority);

        if ($breakChainOnFailure) {
            $this->validatorsThatBreakTheChain->attach($validator);
        }

        return $this;
    }

    /**
     * Add the validators from the given validator specification to the chain
     *
     * @param iterable $validators
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $validators is not iterable or if the validator specification is invalid
     */
    public function addValidators($validators)
    {
        if ($validators instanceof static) {
            return $this->merge($validators);
        }

        if (! is_iterable($validators)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter one to be iterable, got %s instead',
                __METHOD__,
                get_php_type($validators)
            ));
        }

        foreach ($validators as $name => $validator) {
            $breakChainOnFailure = false;

            if (! $validator instanceof Validator) {
                if (is_int($name)) {
                    if (! is_array($validator)) {
                        $name = $validator;
                        $validator = null;
                    } else {
                        if (! isset($validator['name'])) {
                            throw new InvalidArgumentException(
                                'Invalid validator array specification: Key "name" is missing'
                            );
                        }

                        $name = $validator['name'];
                        unset($validator['name']);
                    }
                }

                if (is_array($validator)) {
                    if (isset($validator['options'])) {
                        $options = $validator['options'];

                        unset($validator['options']);

                        $validator = array_merge($validator, $options);
                    }

                    if (isset($validator['break_chain_on_failure'])) {
                        $breakChainOnFailure = $validator['break_chain_on_failure'];

                        unset($validator['break_chain_on_failure']);
                    }
                }

                $validator = $this->createValidator($name, $validator);
            }

            $this->add($validator, $breakChainOnFailure);
        }

        return $this;
    }

    /**
     * Add a validator loader
     *
     * @param string $namespace Namespace of the validators
     * @param string $postfix   Validator name postfix, if any
     *
     * @return $this
     */
    public function addValidatorLoader($namespace, $postfix = null)
    {
        $this->addPluginLoader('validator', $namespace, $postfix);

        return $this;
    }

    /**
     * Remove all validators from the chain
     *
     * @return $this
     */
    public function clearValidators()
    {
        $this->validators = new PriorityQueue();
        $this->validatorsThatBreakTheChain = new SplObjectStorage();

        return $this;
    }

    /**
     * Create a validator from the given name and options
     *
     * @param string $name
     * @param mixed  $options
     *
     * @return Validator
     *
     * @throws InvalidArgumentException If the validator to load is unknown
     * @throws UnexpectedValueException If a validator loader did not return an instance of {@link Validator}
     */
    public function createValidator($name, $options = null)
    {
        $class = $this->loadPlugin('validator', $name);

        if (! $class) {
            throw new InvalidArgumentException(sprintf(
                "Can't load validator '%s'. Validator unknown",
                $name
            ));
        }

        if (empty($options)) {
            $validator = new $class();
        } else {
            $validator = new $class($options);
        }

        if (! $validator instanceof Validator) {
            throw new UnexpectedValueException(sprintf(
                "%s expects loader to return an instance of %s for validator '%s', got %s instead",
                __METHOD__,
                Validator::class,
                $name,
                get_php_type($validator)
            ));
        }

        return $validator;
    }

    /**
     * Merge all validators from the given chain into this one
     *
     * @param ValidatorChain $validatorChain
     *
     * @return $this
     */
    public function merge(ValidatorChain $validatorChain)
    {
        $validatorsThatBreakTheChain = $validatorChain->getValidatorsThatBreakTheChain();

        foreach ($validatorChain->validators->yieldAll() as $priority => $validator) {
            $this->add($validator, $validatorsThatBreakTheChain->contains($validator), $priority);
        }

        return $this;
    }

    public function __clone()
    {
        $this->validators = clone $this->validators;
    }

    /**
     * Export the chain as array
     *
     * @return array
     */
    public function toArray()
    {
        return array_values(iterator_to_array($this));
    }

    public function count()
    {
        return count($this->validators);
    }

    /**
     * Get an iterator for traversing the validators
     *
     * @return Validator[]|PriorityQueue
     */
    public function getIterator()
    {
         // Clone validators because the PriorityQueue acts as a heap and thus items are removed upon iteration
        return clone $this->validators;
    }

    public function isValid($value)
    {
        $this->clearMessages();

        $valid = true;

        foreach ($this as $validator) {
            if ($validator->isValid($value)) {
                continue;
            }

            $valid = false;

            $this->addMessages($validator->getMessages());

            if ($this->validatorsThatBreakTheChain->contains($validator)) {
                break;
            }
        }

        return $valid;
    }
}
