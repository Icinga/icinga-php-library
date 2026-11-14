<?php

namespace ipl\Sql;

/**
 * Interface for pagination via {@link limit()} and {@link offset()}
 */
interface LimitOffsetInterface
{
    /**
     * Get whether a limit is configured
     *
     * @return bool
     */
    public function hasLimit(): bool;

    /**
     * Get the limit
     *
     * @return ?int
     */
    public function getLimit(): ?int;

    /**
     * Set the limit
     *
     * @param ?int $limit Maximum number of items to return. To disable the limit, use null or a negative value
     *
     * @return $this
     */
    public function limit(?int $limit): static;

    /**
     * Reset the limit
     *
     * @return $this
     */
    public function resetLimit(): static;

    /**
     * Get whether an offset is configured
     *
     * @return bool
     */
    public function hasOffset(): bool;

    /**
     * Get the offset
     *
     * @return ?int
     */
    public function getOffset(): ?int;

    /**
     * Set the offset
     *
     * @param ?int $offset Start result set after this many rows. If you want to disable the offset, use null, 0, or
     *   a negative value
     *
     * @return $this
     */
    public function offset(?int $offset): static;

    /**
     * Reset the offset
     *
     * @return $this
     */
    public function resetOffset(): static;
}
