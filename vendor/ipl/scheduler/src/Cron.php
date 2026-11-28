<?php

namespace ipl\Scheduler;

use Cron\CronExpression;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

use function ipl\Stdlib\get_php_type;

/**
 * Cron-expression-based scheduling frequency
 *
 * Schedules a task based on a standard five-field cron expression (minute, hour, day-of-month, month, day-of-week).
 * Supports optional start and end bounds via {@see Cron::startAt()} and {@see Cron::endAt()}.
 */
class Cron implements Frequency
{
    /** @var int Position of the minute field in the cron expression */
    public const PART_MINUTE = 0;

    /** @var int Position of the hour field in the cron expression */
    public const PART_HOUR = 1;

    /** @var int Position of the day-of-month field in the cron expression */
    public const PART_DAY = 2;

    /** @var int Position of the month field in the cron expression */
    public const PART_MONTH = 3;

    /** @var int Position of the day-of-week field in the cron expression */
    public const PART_WEEKDAY = 4;

    protected CronExpression $cron;

    /** @var ?DateTime Start time of this frequency */
    protected ?DateTime $start = null;

    /** @var ?DateTime End time of this frequency */
    protected ?DateTime $end = null;

    /** @var string String representation of the cron expression */
    protected string $expression;

    /**
     * Create frequency from the specified cron expression
     *
     * @param string $expression
     *
     * @throws InvalidArgumentException If expression is not a valid cron expression
     */
    public function __construct(string $expression)
    {
        $this->cron = new CronExpression($expression);
        $this->expression = $expression;
    }

    /**
     * Create a {@see Cron} instance from its stored JSON representation
     *
     * The JSON must decode to an array with at least an `expression` key, and optionally `start` and `end` keys
     * formatted according to {@see Frequency::SERIALIZED_DATETIME_FORMAT}.
     *
     * @param string $json
     *
     * @return static
     *
     * @throws InvalidArgumentException If the JSON does not decode to an array
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s expects json decoded value to be an array, got %s instead',
                    __METHOD__,
                    get_php_type($data)
                )
            );
        }

        $self = new static($data['expression']);
        if (isset($data['start'])) {
            $self->startAt(new DateTime($data['start']));
        }

        if (isset($data['end'])) {
            $self->endAt(new DateTime($data['end']));
        }

        return $self;
    }

    /**
     * Get whether the given cron expression is valid
     *
     * @param string $expression
     *
     * @return bool
     */
    public static function isValid(string $expression): bool
    {
        return CronExpression::isValidExpression($expression);
    }

    public function isDue(DateTimeInterface $dateTime): bool
    {
        if ($this->isExpired($dateTime) || $dateTime < $this->start) {
            return false;
        }

        return $this->cron->isDue($dateTime);
    }

    /**
     * Get the next due date relative to the given time
     *
     * Returns the start time if the given time is before the start, or the end time if the frequency is expired.
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeInterface
     */
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        if ($this->isExpired($dateTime)) {
            return $this->end;
        }

        if ($dateTime < $this->start) {
            return $this->start;
        }

        return $this->cron->getNextRunDate($dateTime);
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return $this->end !== null && $this->end < $dateTime;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    /**
     * Set the start time of this frequency
     *
     * @param DateTimeInterface $start
     *
     * @return $this
     */
    public function startAt(DateTimeInterface $start): static
    {
        $this->start = DateTime::createFromInterface($start);
        $this->start->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $this;
    }

    /**
     * Set the end time of this frequency
     *
     * @param DateTimeInterface $end
     *
     * @return $this
     */
    public function endAt(DateTimeInterface $end): static
    {
        $this->end = DateTime::createFromInterface($end);
        $this->end->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $this;
    }

    /**
     * Get the configured cron expression
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get the given part of the underlying cron expression
     *
     * @param int $part One of the classes `PART_*` constants
     *
     * @return string
     *
     * @throws InvalidArgumentException If the given part is invalid
     */
    public function getPart(int $part): string
    {
        $value = $this->cron->getExpression($part);
        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Invalid expression part specified: %d', $part));
        }

        return $value;
    }

    /**
     * Get the parts of the underlying cron expression as an array
     *
     * @return string[]
     */
    public function getParts(): array
    {
        return $this->cron->getParts();
    }

    /**
     * Serialize this frequency to a JSON-encodable array
     *
     * Always includes the `expression` key; `start` and `end` are included only when set,
     * formatted according to {@see Frequency::SERIALIZED_DATETIME_FORMAT}.
     *
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        $data = ['expression' => $this->getExpression()];
        if ($this->start) {
            $data['start'] = $this->start->format(static::SERIALIZED_DATETIME_FORMAT);
        }

        if ($this->end) {
            $data['end'] = $this->end->format(static::SERIALIZED_DATETIME_FORMAT);
        }

        return $data;
    }
}
