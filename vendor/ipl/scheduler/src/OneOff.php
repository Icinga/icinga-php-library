<?php

namespace ipl\Scheduler;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

class OneOff implements Frequency
{
    /** @var DateTime Start time of this frequency */
    protected $dateTime;

    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = clone $dateTime;
    }

    public function isDue(DateTimeInterface $dateTime): bool
    {
        return ! $this->isExpired($dateTime) && $this->dateTime == $dateTime;
    }

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return $this->dateTime;
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return $this->dateTime < $dateTime;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->dateTime;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->getStart();
    }

    public static function fromJson(string $json): Frequency
    {
        $data = json_decode($json, true);

        return new static(new DateTime($data));
    }

    public function jsonSerialize(): string
    {
        return $this->dateTime->format(static::SERIALIZED_DATETIME_FORMAT);
    }
}
