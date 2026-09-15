<?php

namespace ipl\Web\Widget;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

class Time extends BaseHtmlElement
{
    use Translation;

    /** @var int Format relative */
    public const RELATIVE = 0;

    /** @var int Format time */
    public const TIME = 1;

    /** @var int Format date */
    public const DATE = 2;

    /** @var DateTime Time of this widget */
    protected DateTime $dateTime;

    /** @var string Default formatted datetime string */
    protected string $timeString;

    /** @var IntlDateFormatter|null Formatter to create the time string */
    protected ?IntlDateFormatter $formatter = null;

    /** @var DateTime|null Time to compare with, null takes current time */
    protected ?DateTime $compareTime = null;

    /** @var string Tag of element. */
    protected $tag = 'time';

    /**
     * Create a time widget for the given time
     *
     * @param DateTime $time
     */
    public function __construct(DateTime $time)
    {
        $this->dateTime = $time;
        $this->timeString = $time->format('Y-m-d H:i:s');
        $this->addAttributes(Attributes::create(['title' => $this->timeString]));
    }

    /**
     * Set the formatter to use for formatting the time
     *
     * @param IntlDateFormatter $formatter
     *
     * @return $this
     */
    public function setFormatter(IntlDateFormatter $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Compute the difference between the given time and the compare time
     *
     * Returns an array with the interval, the formatted string, and the type of difference
     * Type can be one of the constants RELATIVE, TIME, DATE
     *
     * @param ?DateTime $compareTime time to compare with, or null for now
     *
     * @return array{0: string, 1: self::TIME|self::DATE|self::RELATIVE, 2: DateInterval}
     */
    public function diff(?DateTime $compareTime = null): array
    {
        $compareTime = $compareTime ?? new DateTime();
        $time = $this->dateTime;
        $interval = $time->diff($compareTime);

        return [...match (true) {
            $interval->days > 2 => [
                $time->format($this->isSameByFormat($compareTime, 'Y') ? 'M j' : 'Y-m'),
                static::DATE
            ],
            $interval->days > 0 => [$interval->format('%dd %hh'), static::RELATIVE],
            $interval->h > 0    => $this->isSameByFormat($compareTime, 'd')
                ? [$time->format('H:i'), static::TIME]
                : [$time->format('M j H:i'), static::DATE],
            default             => [$interval->format('%im %ss'), static::RELATIVE],
        }, $interval];
    }

    /**
     * Get formatted time
     *
     * Override this method to customize the format of the time widget
     * i.e., to use custom formatting, add attributes, etc.
     *
     * This method is only used by {@see assemble()}
     *
     * @return string
     */
    protected function format(): string
    {
        return $this->formatter ? $this->formatter->format($this->dateTime) : $this->timeString;
    }

    /**
     * Return a relative time widget
     *
     * @param DateTime $time
     * @param DateTime|null $compareTime null takes current time
     *
     * @return static
     *
     * @throws Exception
     */
    public static function relative(DateTime $time, ?DateTime $compareTime = null): static
    {
        if ($compareTime === null) {
            $compareTime = new DateTime();
        }

        return $time->getTimestamp() <= $compareTime->getTimestamp()
            ? new TimeAgo($time, $compareTime)
            : new TimeUntil($time, $compareTime);
    }

    /**
     * Assemble the content of the time widget
     *
     * @return void
     */
    protected function assemble(): void
    {
        $this->addAttributes(Attributes::create(['datetime' => $this->timeString]));
        $this->addHtml(Text::create($this->format()));
    }

    /**
     * Convert a value to a DateTime object
     *
     * @param int|float|DateTime|null $value
     *
     * @return DateTime
     *
     * @throws Exception
     *
     * @internal This method is for backwards compatibility
     */
    protected function castToDateTime(int|float|DateTime|null $value = null): DateTime
    {
        if ($value === null) {
            return new DateTime();
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        $value = (int) ($value > 9999999999 ? ($value / 1000) : $value);

        return (new DateTime('@' . $value))->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Checks whether the widget time and the compare time share the same value for a given format.
     *
     * @param DateTime $compareTime  DateTime to compare with the widget time
     * @param string   $format  A format string accepted by DateTime::format() (e.g. 'Y', 'd', 'Y-m-d').
     *
     * @return bool True if both date/times produce the same string for the given format, false otherwise.
     *
     * @internal
     */
    protected function isSameByFormat(DateTime $compareTime, string $format): bool
    {
        return $this->dateTime->format($format) === $compareTime->format($format);
    }
}
