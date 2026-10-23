<?php

namespace ipl\Orm\Relation;

use Generator;
use ipl\Orm\Model;
use ipl\Orm\Relation;
use ipl\Orm\Relations;
use ipl\Orm\Resolver;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Rule;
use LogicException;
use RuntimeException;

/**
 * Many-to-many relationship
 */
class BelongsToMany extends Relation
{
    /** @var string Relation class */
    protected const RELATION_CLASS = HasMany::class;

    protected bool $isOne = false;

    /** @var ?string Name of the join table or junction model class */
    protected ?string $throughClass = null;

    /** @var ?string Alias for the join table or junction model class */
    protected ?string $throughAlias = null;

    /** @var ?Model The junction model */
    protected ?Model $through = null;

    /** @var string|array|null Column name(s) of the target model's foreign key found in the join table */
    protected string|array|null $targetForeignKey = null;

    /** @var string|array|null Candidate key column name(s) in the target table which references the target foreign key */
    protected string|array|null $targetCandidateKey = null;

    /** @var ?Filter\Chain Additional JOIN conditions for the join table */
    protected ?Filter\Chain $throughFilter = null;

    /** @var ?array<string, Model> Models additional join table conditions may reference, keyed by their alias */
    protected ?array $throughFilterSubjects = null;

    /**
     * Get the name of the join table or junction model class
     *
     * @return ?string
     */
    public function getThroughClass(): ?string
    {
        return $this->throughClass;
    }

    /**
     * Set the join table name or junction model class
     *
     * @param string $through
     *
     * @return $this
     */
    public function through(string $through): static
    {
        $this->throughClass = $through;

        return $this;
    }

    /**
     * Get the alias for the join table or junction model class
     *
     * @return string
     */
    public function getThroughAlias(): string
    {
        return $this->throughAlias ?? $this->getThrough()->getTableAlias();
    }

    /**
     * Set the alias for the join table or junction model class
     *
     * @param string $throughAlias
     *
     * @return $this
     */
    public function setThroughAlias(string $throughAlias): static
    {
        $this->throughAlias = $throughAlias;

        return $this;
    }

    /**
     * Get the junction model
     *
     * @return Model
     *
     * @throws LogicException If no through class is configured
     */
    public function getThrough(): Model
    {
        if ($this->through === null) {
            $throughClass = $this->getThroughClass();
            if ($throughClass === null) {
                throw new LogicException(
                    'You cannot use a many-to-many relation without a through class or a table name for the'
                    . ' junction model'
                );
            }

            if (class_exists($throughClass)) {
                $this->through = new $throughClass();
            } else {
                $this->through = (new Junction())
                    ->setTableName($throughClass);
            }
        }

        return $this->through;
    }

    /**
     * Set the junction model
     *
     * @param Model $through
     *
     * @return $this
     */
    public function setThrough(Model $through): static
    {
        $this->through = $through;

        return $this;
    }

    /**
     * Get the column name(s) of the target model's foreign key found in the join table
     *
     * @return string|array|null Array if the foreign key is compound, string otherwise
     */
    public function getTargetForeignKey(): string|array|null
    {
        return $this->targetForeignKey;
    }

    /**
     * Set the column name(s) of the target model's foreign key found in the join table
     *
     * @param string|array|null $targetForeignKey Array if the foreign key is compound, string otherwise
     *
     * @return $this
     */
    public function setTargetForeignKey(string|array|null $targetForeignKey): static
    {
        $this->targetForeignKey = $targetForeignKey;

        return $this;
    }

    /**
     * Get the candidate key column name(s) in the target table which references the target foreign key
     *
     * @return string|array|null Array if the foreign key is compound, string otherwise
     */
    public function getTargetCandidateKey(): string|array|null
    {
        return $this->targetCandidateKey;
    }

    /**
     * Set the candidate key column name(s) in the target table which references the target foreign key
     *
     * @param string|array|null $targetCandidateKey Array if the foreign key is compound, string otherwise
     *
     * @return $this
     */
    public function setTargetCandidateKey(string|array|null $targetCandidateKey): static
    {
        $this->targetCandidateKey = $targetCandidateKey;

        return $this;
    }

    /**
     * Get the filter to constrain results of the join table or junction model
     *
     * @return Filter\Chain
     */
    public function getThroughFilter(): Filter\Chain
    {
        return $this->throughFilter ?? Filter::all();
    }

    /**
     * Set a filter that constraints results of the join table or junction model
     *
     * Only actual columns of the source's or junction's table itself are allowed. Qualification happens at runtime.
     * Use the source's table alias or the junction's one (default) to reference one or the other. Comparison values
     * are passed as-is to ipl-sql's query builder, thus any behaviors by either the source or junction are not applied.
     * Custom filter types other than those extending {@see Filter\Condition} are not allowed. Condition values of
     * type {@see ExpressionInterface} are supported and must adhere to the same assumptions.
     *
     * @param Rule $filter
     *
     * @return $this
     */
    public function setThroughFilter(Filter\Rule $filter): static
    {
        if (! $filter instanceof Filter\Chain) {
            $filter = Filter::all($filter);
        }

        $this->throughFilter = $filter;

        return $this;
    }

    /**
     * Get subjects the join table filter may reference
     *
     * @return array<string, Model>
     */
    public function getThroughFilterSubjects(): array
    {
        return $this->throughFilterSubjects ?? throw new LogicException(sprintf(
            'Cannot get filter subjects of an unbound relation. Please call %s::bindTo() first.',
            static::class
        ));
    }

    /**
     * Add subjects the join table filter may reference, while keeping existing ones
     *
     * @param array<string, Model> ...$subjects
     *
     * @return $this
     */
    public function addThroughFilterSubjects(Model ...$subjects): static
    {
        $this->throughFilterSubjects ??= [];
        $this->throughFilterSubjects += $subjects;

        return $this;
    }

    public function bindTo(Model $source, string $path, Resolver $resolver): static
    {
        // Allow to reference the join table in the second hop
        $this->addFilterSubjects(...[$this->getThroughAlias() => $this->getThrough()]);

        parent::bindTo($source, $path, $resolver);

        $this->addThroughFilterSubjects(...[
            $this->getSource()->getTableAlias() => $this->getSource(),
            $this->getThrough()->getTableAlias() => $this->getThrough(),
            $this->getThroughAlias() => $this->getThrough()
        ]);

        $resolver->resolveRelationFilter(
            $this->getThroughFilter(),
            $this->getThroughAlias(),
            ...$this->getThroughFilterSubjects()
        );

        $resolver->setAlias($this->getThrough(), join('_', array_merge(
            array_slice(explode('.', $path), 0, -1),
            [$this->getThroughAlias()]
        )));

        return $this;
    }

    public function resolve(): Generator
    {
        $source = $this->getSource();

        $possibleCandidateKey = [$this->getCandidateKey()];
        $possibleForeignKey = [$this->getForeignKey()];

        $target = $this->getTarget();

        $possibleTargetCandidateKey = [$this->getTargetForeignKey() ?: static::getDefaultForeignKey($target)];
        $possibleTargetForeignKey = [$this->getTargetCandidateKey() ?: static::getDefaultCandidateKey($target)];

        $junction = $this->getThrough();

        if (! $junction instanceof Junction) {
            $relations = new Relations();
            $junction->createRelations($relations);

            if ($relations->has($source->getTableAlias())) {
                $sourceRelation = $relations->get($source->getTableAlias());

                $possibleCandidateKey[] = $sourceRelation->getForeignKey();
                $possibleForeignKey[] = $sourceRelation->getCandidateKey();
            }

            if ($relations->has($target->getTableAlias())) {
                $targetRelation = $relations->get($target->getTableAlias());

                $possibleTargetCandidateKey[] = $targetRelation->getCandidateKey();
                $possibleTargetForeignKey[] = $targetRelation->getForeignKey();
            }
        }

        $junctionClass = static::RELATION_CLASS;
        $toJunction = (new $junctionClass())
            ->setName($this->getThroughAlias())
            ->setSource($source)
            ->setTarget($junction)
            ->setFilter($this->getThroughFilter())
            ->addFilterSubjects(...$this->getThroughFilterSubjects())
            ->setCandidateKey($this->extractKey($possibleCandidateKey))
            ->setForeignKey($this->extractKey($possibleForeignKey))
            ->setJoinType($this->getJoinType());

        yield from $toJunction->resolve();

        $targetClass = static::RELATION_CLASS;
        $toTarget = (new $targetClass())
            ->setName($this->getName())
            ->setSource($junction)
            ->setTarget($target)
            ->setFilter($this->getFilter())
            ->addFilterSubjects(...$this->getFilterSubjects())
            ->setCandidateKey($this->extractKey($possibleTargetCandidateKey))
            ->setForeignKey($this->extractKey($possibleTargetForeignKey))
            ->setJoinType($this->getJoinType());

        yield from $toTarget->resolve();
    }

    public function reverse(Resolver $resolver): Relation
    {
        $relation = parent::reverse($resolver);
        if ($relation->getThroughClass() !== null && $relation->getThroughClass() !== $this->getThroughClass()) {
            throw new RuntimeException(sprintf(
                'The junction model of the relation "%s" (%s) is not compatible'
                . ' with the junction model of the inverse relation (%s != %s)',
                $this->getName(),
                get_class($this->getSource()),
                $relation->getThroughClass(),
                $this->getThroughClass()
            ));
        }

        $relation->through($this->getThroughClass());
        $relation->setThrough($this->getThrough());
        $relation->setThroughAlias($this->getThroughAlias());

        // The source table is allowed to reference in a join filter so this must ensure that this works on
        // the way back as well. Since the source's instance is kept by parent::reverse() this should be safe.
        $relation->addThroughFilterSubjects(...[$relation->getTarget()->getTableAlias() => $relation->getTarget()]);

        if (! $this->getThroughFilter()->isEmpty()) {
            $relation->setThroughFilter(clone $this->getThroughFilter());
        }

        if (! $resolver->getRelations($this->getTarget())->has($relation->getName())) {
            // The relation is eagerly set up and thus needs proper key pairs,
            // but reversed as only the forward relation's pairs are known.
            $relation->setCandidateKey($this->getTargetCandidateKey());
            $relation->setForeignKey($this->getTargetForeignKey());
            $relation->setTargetCandidateKey($this->getCandidateKey());
            $relation->setTargetForeignKey($this->getForeignKey());
        }

        return $relation;
    }

    protected function extractKey(array $possibleKey): string|array|null
    {
        $filtered = array_filter($possibleKey);

        return array_pop($filtered);
    }
}
