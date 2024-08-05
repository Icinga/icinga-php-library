<?php

namespace ipl\Scheduler;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

use function ipl\Stdlib\get_php_type;

class OneOff implements Frequency
{
    /** @var DateTimeInterface Start time of this frequency */
    protected $dateTime;

    public function __construct(DateTimeInterface $dateTime)
    {
        $this->dateTime = clone $dateTime;
        $this->dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
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
        if (! is_string($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s expects json decoded value to be string, got %s instead',
                    __METHOD__,
                    get_php_type($data)
                )
            );
        }

        return new static(new DateTime($data));
    }

    public function jsonSerialize(): string
    {
        return $this->dateTime->format(static::SERIALIZED_DATETIME_FORMAT);
    }
}
