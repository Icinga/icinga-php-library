<?php

namespace ipl\Scheduler\Common;

use LogicException;
use Ramsey\Uuid\UuidInterface;

/**
 * Common property helpers for {@see \ipl\Scheduler\Contract\Task} implementations
 *
 * Provides storage and accessors for the name, description, and UUID of a task.
 * Getters throw a {@see LogicException} when the corresponding property has not been set.
 */
trait TaskProperties
{
    protected ?string $description = null;

    protected ?string $name = null;

    protected ?UuidInterface $uuid = null;

    /**
     * Set the description of this task
     *
     * @param ?string $desc
     *
     * @return $this
     */
    public function setDescription(?string $desc): static
    {
        $this->description = $desc;

        return $this;
    }

    /**
     * Get the description of this task
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get the name of this task
     *
     * @return string
     *
     * @throws LogicException If the name has not been set
     */
    public function getName(): string
    {
        if (! $this->name) {
            throw new LogicException('Task name must not be null');
        }

        return $this->name;
    }

    /**
     * Set the name of this task
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
     * Get the unique identifier of this task
     *
     * @return UuidInterface
     *
     * @throws LogicException If the UUID has not been set
     */
    public function getUuid(): UuidInterface
    {
        if (! $this->uuid) {
            throw new LogicException('Task UUID must not be null');
        }

        return $this->uuid;
    }

    /**
     * Set the UUID of this task
     *
     * @param UuidInterface $uuid
     *
     * @return $this
     */
    public function setUuid(UuidInterface $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }
}
