<?php

namespace ipl\Orm;

use ArrayIterator;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Relation\BelongsTo;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Relation\HasMany;
use ipl\Orm\Relation\HasOne;
use IteratorAggregate;
use Traversable;

use function ipl\Stdlib\get_php_type;

/**
 * Collection of a model's relations.
 */
class Relations implements IteratorAggregate
{
    /** @var Relation[] */
    protected $relations = [];

    /**
     * Get whether a relation with the given name exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->relations[$name]);
    }

    /**
     * Get the relation with the given name
     *
     * @param string $name
     *
     * @return Relation
     *
     * @throws \InvalidArgumentException If the relation with the given name does not exist
     */
    public function get($name)
    {
        $this->assertRelationExists($name);

        return $this->relations[$name];
    }

    /**
     * Add the given relation to the collection
     *
     * @param Relation $relation
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If a relation with the given name already exists
     */
    public function add(Relation $relation)
    {
        $name = $relation->getName();

        $this->assertRelationDoesNotYetExist($name);

        $this->relations[$name] = $relation;

        return $this;
    }

    /**
     * Create a new relation from the given class, name and target model class
     *
     * @param string $class       Class of the relation to create
     * @param string $name        Name of the relation
     * @param string $targetClass Target model class
     *
     * @return BelongsTo|BelongsToMany|HasMany|HasOne|Relation
     *
     * @throws \InvalidArgumentException If the target model class is not of type string
     */
    public function create($class, $name, $targetClass)
    {
        $relation = new $class();

        if (! $relation instanceof Relation) {
            throw new \InvalidArgumentException(sprintf(
                '%s() expects parameter 1 to be a subclass of %s, %s given',
                __METHOD__,
                Relation::class,
                get_php_type($relation)
            ));
        }

        // Test target model
        $target = new $targetClass();
        if (! $target instanceof Model) {
            throw new \InvalidArgumentException(sprintf(
                '%s() expects parameter 3 to be a subclass of %s, %s given',
                __METHOD__,
                Model::class,
                get_php_type($target)
            ));
        }

        /** @var Relation $relation */
        $relation
            ->setName($name)
            ->setTarget($target)
            ->setTargetClass($targetClass);

        return $relation;
    }

    /**
     * Define a one-to-one relationship
     *
     * @param string $name        Name of the relation
     * @param string $targetClass Target model class
     *
     * @return HasOne
     */
    public function hasOne($name, $targetClass)
    {
        $relation = $this->create(HasOne::class, $name, $targetClass);

        $this->add($relation);

        return $relation;
    }

    /**
     * Define a one-to-many relationship
     *
     * @param string $name        Name of the relation
     * @param string $targetClass Target model class
     *
     * @return HasMany
     */
    public function hasMany($name, $targetClass)
    {
        $relation = $this->create(HasMany::class, $name, $targetClass);

        $this->add($relation);

        return $relation;
    }

    /**
     * Define the inverse of a one-to-one or one-to-many relationship
     *
     * @param string $name        Name of the relation
     * @param string $targetClass Target model class
     *
     * @return BelongsTo
     */
    public function belongsTo($name, $targetClass)
    {
        $relation = $this->create(BelongsTo::class, $name, $targetClass);

        $this->add($relation);

        return $relation;
    }

    /**
     * Define a many-to-many relationship
     *
     * @param string $name        Name of the relation
     * @param string $targetClass Target model class
     *
     * @return BelongsToMany
     */
    public function belongsToMany($name, $targetClass)
    {
        $relation = $this->create(BelongsToMany::class, $name, $targetClass);

        $this->add($relation);

        return $relation;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->relations);
    }

    /**
     * Throw exception if a relation with the given name already exists
     *
     * @param string $name
     */
    protected function assertRelationDoesNotYetExist($name)
    {
        if ($this->has($name)) {
            throw new \InvalidArgumentException("Relation '$name' already exists");
        }
    }

    /**
     * Throw exception if a relation with the given name does not exist
     *
     * @param string $name
     */
    protected function assertRelationExists($name)
    {
        if (! $this->has($name)) {
            throw new InvalidRelationException($name);
        }
    }
}
