<?php

namespace ipl\Scheduler;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

use function ipl\Stdlib\get_php_type;

/**
 * Single-run scheduling frequency
 *
 * Schedules a task to run exactly once at the given point in time.
 * The frequency is considered expired as soon as that time has passed.
 */
class OneOff implements Frequency
{
    /** @var DateTime Start time of this frequency */
    protected DateTime $dateTime;

    /**
     * Create a one-off frequency for the given point in time
     *
     * The datetime is cloned and normalized to the default system timezone.
     *
     * @param DateTimeInterface $dateTime The exact time at which the task should run
     */
    public function __construct(DateTimeInterface $dateTime)
    {
        $this->dateTime = DateTime::createFromInterface($dateTime);
        $this->dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Create a {@see OneOff} instance from its stored JSON representation
     *
     * The JSON must decode to a datetime string formatted according to {@see Frequency::SERIALIZED_DATETIME_FORMAT}.
     *
     * @param string $json
     *
     * @return static
     *
     * @throws InvalidArgumentException If the JSON does not decode to a string
     */
    public static function fromJson(string $json): static
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

    public function isDue(DateTimeInterface $dateTime): bool
    {
        return ! $this->isExpired($dateTime) && $this->dateTime == $dateTime;
    }

    /**
     * Get the next due date relative to the given time
     *
     * Always returns the configured run time, regardless of the given time.
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeInterface
     */
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return $this->dateTime;
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return $this->dateTime < $dateTime;
    }

    /**
     * Get the start time of this frequency, which equals the configured run time
     *
     * @return ?DateTimeInterface
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->dateTime;
    }

    /**
     * Get the end time of this frequency, which equals the configured run time
     *
     * @return ?DateTimeInterface
     */
    public function getEnd(): ?DateTimeInterface
    {
        return $this->getStart();
    }

    /**
     * Serialize this frequency to a datetime string
     *
     * Returns the configured run time formatted according to {@see Frequency::SERIALIZED_DATETIME_FORMAT}.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->dateTime->format(static::SERIALIZED_DATETIME_FORMAT);
    }
}
