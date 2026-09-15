<?php

namespace ipl\Scheduler\Contract;

use DateTimeInterface;
use JsonSerializable;

/**
 * Contract for a scheduling frequency
 *
 * A frequency determines when a task is due to run, what the next run time is,
 * whether it has expired, and its optional start and end bounds.
 * Implementations must also support round-trip JSON serialization via
 * {@see Frequency::fromJson()} and {@see JsonSerializable::jsonSerialize()}.
 */
interface Frequency extends JsonSerializable
{
    /** @var string Format for representing datetimes when serializing the frequency to JSON */
    public const SERIALIZED_DATETIME_FORMAT = 'Y-m-d\TH:i:s.ue';

    /**
     * Create a frequency from its stored JSON representation
     *
     * The representation must have been encoded with {@see json_encode()}.
     *
     * @param string $json
     *
     * @return $this
     */
    public static function fromJson(string $json): static;

    /**
     * Get whether the frequency is due at the specified time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isDue(DateTimeInterface $dateTime): bool;

    /**
     * Get the next due date relative to the given time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeInterface
     */
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface;

    /**
     * Get whether the specified time is beyond the frequency's expiry time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isExpired(DateTimeInterface $dateTime): bool;

    /**
     * Get the start time of this frequency
     *
     * @return ?DateTimeInterface
     */
    public function getStart(): ?DateTimeInterface;

    /**
     * Get the end time of this frequency
     *
     * @return ?DateTimeInterface
     */
    public function getEnd(): ?DateTimeInterface;
}
