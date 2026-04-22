<?php

namespace ipl\Sql;

/**
 * Implementation for the {@link LimitOffsetInterface} to allow pagination via {@link limit()} and {@link offset()}
 */
trait LimitOffset
{
    /**
     * The maximum number of how many items to return
     *
     * If unset or lower than 0, no limit will be applied.
     *
     * @var ?int
     */
    protected ?int $limit = null;

    /**
     * Offset from where to start the result set
     *
     * If unset or lower than 0, the result set will start from the beginning.
     *
     * @var ?int
     */
    protected ?int $offset = null;

    public function hasLimit(): bool
    {
        return $this->limit !== null;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit < 0 ? null : $limit;

        return $this;
    }

    public function resetLimit(): static
    {
        $this->limit = null;

        return $this;
    }

    public function hasOffset(): bool
    {
        return $this->offset !== null;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function offset(?int $offset): static
    {
        $this->offset = $offset <= 0 ? null : $offset;

        return $this;
    }

    public function resetOffset(): static
    {
        $this->offset = null;

        return $this;
    }
}
