<?php

namespace ipl\Scheduler\Common;

use LogicException;
use Ramsey\Uuid\UuidInterface;

trait TaskProperties
{
    /** @var ?string */
    protected ?string $description = null;

    /** @var ?string Name of this task */
    protected ?string $name = null;

    /** @var ?UuidInterface Unique identifier of this task */
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): string
    {
        if (! $this->name) {
            throw new LogicException('Task name must not be null');
        }

        return $this->name;
    }

    /**
     * Set the name of this Task
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
