<?php

namespace ipl\Orm;

use Generator;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Rule;
use LogicException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Relations represent the connection between models, i.e. the association between rows in one or more tables
 * on the basis of matching key columns. The relationships are defined using candidate key-foreign key constructs.
 */
class Relation
{
    /** @var string Name of the relation */
    protected $name;

    /** @var ?string Name of the reversed relation */
    protected ?string $reverseName = null;

    /** @var ?class-string The class to reverse the relation  */
    protected ?string $reverseClass = null;

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

    /** @var ?Filter\Chain Additional JOIN conditions */
    protected ?Filter\Chain $filter = null;

    /** @var ?array<string, Model> Models additional JOIN conditions may reference, keyed by their alias */
    protected ?array $filterSubjects = null;

    /** @var ?string The name of the relation prior reversal */
    private ?string $forwardRelationName = null;

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
     * Get the reverse name of the relation
     *
     * @return ?string
     */
    public function getReverseName(): ?string
    {
        return $this->reverseName;
    }

    /**
     * Set the reverse name of the relation
     *
     * The source's table alias is used by default.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setReverseName(string $name): static
    {
        $this->reverseName = $name;

        return $this;
    }

    /**
     * Get the class to reverse the relation
     *
     * @return class-string
     */
    public function getReverseClass(): string
    {
        return $this->reverseClass ?? static::class;
    }

    /**
     * Set the class to reverse the relation
     *
     * @param class-string $reverseClass
     *
     * @return $this
     */
    public function setReverseClass(string $reverseClass): static
    {
        $this->reverseClass = $reverseClass;

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
     * Get the filter to constrain results of the target model
     *
     * @return Filter\Chain
     */
    public function getFilter(): Filter\Chain
    {
        return $this->filter ?? Filter::all();
    }

    /**
     * Set a filter that constraints results of the target model
     *
     * Only actual columns of the source's or target's table itself are allowed. Qualification happens at runtime.
     * Use the source's table alias or the relation name (default) to reference one or the other. Comparison values are
     * passed as-is to ipl-sql's query builder, thus any behaviors by either the source or target are not applied.
     * Custom filter types other than those extending {@see Filter\Condition} are not allowed. Condition values of
     * type {@see ExpressionInterface} are supported and must adhere to the same assumptions.
     *
     * @param Rule $filter
     *
     * @return $this
     */
    public function setFilter(Filter\Rule $filter): static
    {
        if (! $filter instanceof Filter\Chain) {
            $filter = Filter::all($filter);
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * Get subjects the relation filter may reference
     *
     * @return array<string, Model>
     */
    public function getFilterSubjects(): array
    {
        return $this->filterSubjects ?? throw new LogicException(sprintf(
            'Cannot get filter subjects of an unbound relation. Please call %s::bindTo() first.',
            static::class
        ));
    }

    /**
     * Add subjects the relation filter may reference, while keeping existing ones
     *
     * @param array<string, Model> ...$subjects
     *
     * @return $this
     */
    public function addFilterSubjects(Model ...$subjects): static
    {
        $this->filterSubjects ??= [];
        $this->filterSubjects += $subjects;

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
     * Bind the relation to the given source using the passed resolver
     *
     * @param Model $source The model to use as source
     * @param string $path The path the relation has been resolved at
     * @param Resolver $resolver The resolver to register the relation's target alias
     *
     * @return $this
     */
    public function bindTo(Model $source, string $path, Resolver $resolver): static
    {
        $this->setSource($source);
        $target = $this->getTarget();

        $subjects = [
            $this->getName() => $target,
            $target->getTableAlias() => $target,
            $source->getTableAlias() => $source
        ];
        if ($this->forwardRelationName !== null) {
            $subjects[$this->forwardRelationName] = $source;
        }

        $this->addFilterSubjects(...$subjects);

        $resolver->resolveRelationFilter($this->getFilter(), $this->getName(), ...$subjects);
        $resolver->setAlias($target, str_replace('.', '_', $path));

        return $this;
    }

    /**
     * Resolve the relation
     *
     * Yields the relation to join as key and a three-element array consisting of the source model,
     * target model and the join keys as value.
     *
     * @return Generator<mixed, static, array{0: Model, 1: Model, 2: array<string, string>}, void>
     * @phpstan-return Generator<static, array{0: Model, 1: Model, 2: array<string, string>}, mixed, void>
     */
    public function resolve(): Generator
    {
        $source = $this->getSource();

        yield $this => [$source, $this->getTarget(), $this->determineKeys($source)];
    }

    /**
     * Reverse the relation
     *
     * Uses the passed resolver to eagerly create a relation on the reversed path. Either way,
     * the result is still unknown to the given resolver and must be registered with it.
     *
     * @param Resolver $resolver
     *
     * @return Relation The reversed relation
     *
     * @throws LogicException In case the relation is not bound yet (has no source) or has already been reversed
     * @throws RuntimeException In case the model of the forward relation is incompatible with the reversed relation's
     */
    public function reverse(Resolver $resolver): Relation
    {
        if ($this->getSource() === null) {
            throw new LogicException('Cannot reverse an unbound relation.');
        } elseif (isset($this->forwardRelationName)) {
            throw new LogicException('Cannot undo a reverse.');
        }

        $reverseName = $this->getReverseName() ?? $this->getSource()->getTableAlias();

        $relations = $resolver->getRelations($this->getTarget());

        $exists = false;
        if ($relations->has($reverseName)) {
            if (! is_a($relations->get($reverseName), $this->getReverseClass())) {
                trigger_error(sprintf(
                    'The relation "%s" (%s) already exists but is not compatible with the expected reverse'
                    . ' relation class (%s != %s). To solve this, either use the same relation class, a different'
                    . ' reverse name or a different relation name on the target. You may also remove the relation'
                    . ' altogether, if the only reason it exists was to support sub-queries.',
                    $reverseName,
                    get_class($this->getTarget()),
                    get_class($relations->get($reverseName)),
                    $this->getReverseClass()
                ), E_USER_DEPRECATED);
            } else {
                $exists = true;
            }
        }

        if ($exists) {
            // Explicit reverse relations must be properly set up with corresponding key pairs
            $relation = clone $relations->get($reverseName);

            if (! $this->getSource() instanceof ($relation->getTargetClass())) {
                throw new RuntimeException(sprintf(
                    'The source model of the relation "%s" (%s) is not compatible'
                    . ' with the target model of the inverse relation (%s)',
                    $this->getName(),
                    get_class($this->getSource()),
                    $relation->getTargetClass()
                ));
            }
        } else {
            // Eagerly create the relation in case it's only necessary during reversal
            $relation = $relations->create(
                $this->getReverseClass(),
                $reverseName,
                get_class($this->getSource())
            );

            // Pass on custom configuration
            $relation->setCandidateKey($this->getForeignKey());
            $relation->setForeignKey($this->getCandidateKey());
            $relation->setJoinType($this->getJoinType());
        }

        // The previous relation name must be kept for reference as relation filters
        // may require it but need to be resolved to the source model instead.
        $relation->forwardRelationName = $this->getName();

        $relation->setTarget($this->getSource()); // Propagates the same instance

        if (! $this->getFilter()->isEmpty()) {
            // Do not override set filters with an empty set, however, if the set is not empty
            // the forward relation is expected to carry the same semantics as the inverse.
            $relation->setFilter(clone $this->getFilter());
        }

        return $relation;
    }
}
