<?php

namespace ipl\Orm;

use Generator;
use UnexpectedValueException;

/**
 * Relations represent the connection between models, i.e. the association between rows in one or more tables
 * on the basis of matching key columns. The relationships are defined using candidate key-foreign key constructs.
 */
class Relation
{
    /** @var string Name of the relation */
    protected $name;

    /** @var Model Source model */
    protected $source;

    /** @var string|array|null Column name(s) of the foreign key found in the target table */
    protected string|array|null $foreignKey = null;

    /** @var string|array|null Column name(s) of the candidate key in the source table which references the foreign key */
    protected string|array|null $candidateKey = null;

    /** @var string Target model class */
    protected $targetClass;

    /** @var Model Target model */
    protected $target;

    /** @var string Type of the JOIN used in the query */
    protected string $joinType = 'INNER';

    /** @var bool Whether this is the inverse of a relationship */
    protected bool $inverse = false;

    /** @var bool Whether this is a to-one relationship */
    protected bool $isOne = true;

    /**
     * Get the default column name(s) in the source table used to match the foreign key
     *
     * The default candidate key is the primary key column name(s) of the given model.
     *
     * @param Model $source
     *
     * @return array
     */
    public static function getDefaultCandidateKey(Model $source): array
    {
        return (array) $source->getKeyName();
    }

    /**
     * Get the default column name(s) of the foreign key found in the target table
     *
     * The default foreign key is the given model's primary key column name(s) prefixed with its table name.
     *
     * @param Model $source
     *
     * @return array
     */
    public static function getDefaultForeignKey(Model $source): array
    {
        $tableName = $source->getTableName();

        return array_map(
            function ($key) use ($tableName) {
                return "{$tableName}_{$key}";
            },
            (array) $source->getKeyName()
        );
    }

    /**
     * Get whether this is a to-one relationship
     *
     * @return bool
     */
    public function isOne(): bool
    {
        return $this->isOne;
    }

    /**
     * Get the name of the relation
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the relation
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the source model of the relation
     *
     * @return Model
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the source model of the relation
     *
     * @param Model $source
     *
     * @return $this
     */
    public function setSource(Model $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get the column name(s) of the foreign key found in the target table
     *
     * @return string|array|null Array if the foreign key is compound, string otherwise
     */
    public function getForeignKey(): string|array|null
    {
        return $this->foreignKey;
    }

    /**
     * Set the column name(s) of the foreign key found in the target table
     *
     * @param string|array|null $foreignKey Array if the foreign key is compound, string otherwise
     *
     * @return $this
     */
    public function setForeignKey(string|array|null $foreignKey): static
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * Get the column name(s) of the candidate key in the source table which references the foreign key
     *
     * @return string|array|null Array if the candidate key is compound, string otherwise
     */
    public function getCandidateKey(): string|array|null
    {
        return $this->candidateKey;
    }

    /**
     * Set the column name(s) of the candidate key in the source table which references the foreign key
     *
     * @param string|array|null $candidateKey Array if the candidate key is compound, string otherwise
     *
     * @return $this
     */
    public function setCandidateKey(string|array|null $candidateKey): static
    {
        $this->candidateKey = $candidateKey;

        return $this;
    }

    /**
     * Get the target model class
     *
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }

    /**
     * Set the target model class
     *
     * @param string $targetClass
     *
     * @return $this
     */
    public function setTargetClass(string $targetClass): static
    {
        $this->targetClass = $targetClass;

        return $this;
    }

    /**
     * Get the target model
     *
     * Returns the model from {@link setTarget()} or an instance of {@link getTargetClass()}.
     * Note that multiple calls to this method always returns the very same model instance.
     *
     * @return Model
     */
    public function getTarget()
    {
        if ($this->target === null) {
            $targetClass = $this->getTargetClass();
            $this->target = new $targetClass();
        }

        return $this->target;
    }

    /**
     * Set the the target model
     *
     * @param Model $target
     *
     * @return $this
     */
    public function setTarget(Model $target): static
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get the type of the JOIN used in the query
     *
     * @return string
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    /**
     * Set the type of the JOIN used in the query
     *
     * @param string $joinType
     *
     * @return Relation
     */
    public function setJoinType(string $joinType): static
    {
        $this->joinType = $joinType;

        return $this;
    }

    /**
     * Determine the candidate key-foreign key construct of the relation
     *
     * @param Model $source
     *
     * @return array Candidate key-foreign key column name pairs
     *
     * @throws UnexpectedValueException If there's no candidate key to be found
     *                                   or the foreign key count does not match the candidate key count
     */
    public function determineKeys(Model $source): array
    {
        $candidateKey = (array) $this->getCandidateKey();

        if (empty($candidateKey)) {
            $candidateKey = $this->inverse
                ? static::getDefaultForeignKey($this->getTarget())
                : static::getDefaultCandidateKey($source);
        }

        if (empty($candidateKey)) {
            throw new UnexpectedValueException(sprintf(
                "Can't join relation '%s' in model '%s'. No candidate key found.",
                $this->getName(),
                get_class($source)
            ));
        }

        $foreignKey = (array) $this->getForeignKey();

        if (empty($foreignKey)) {
            $foreignKey = $this->inverse
                ? static::getDefaultCandidateKey($this->getTarget())
                : static::getDefaultForeignKey($source);
        }

        if (count($foreignKey) !== count($candidateKey)) {
            throw new UnexpectedValueException(sprintf(
                "Can't join relation '%s' in model '%s'."
                . " Foreign key count (%s) does not match candidate key count (%s).",
                $this->getName(),
                get_class($source),
                implode(', ', $foreignKey),
                implode(', ', $candidateKey)
            ));
        }

        return array_combine($foreignKey, $candidateKey);
    }

    /**
     * Resolve the relation
     *
     * Yields a three-element array consisting of the source model, target model and the join keys.
     *
     * @return Generator
     */
    public function resolve(): Generator
    {
        $source = $this->getSource();

        yield [$source, $this->getTarget(), $this->determineKeys($source)];
    }
}
