<?php

namespace ipl\Web\FormElement\ScheduleElement\Common;

use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use ipl\Html\Contract\FormElement;
use ipl\Scheduler\RRule;
use ipl\Web\FormElement\ScheduleElement\MonthlyFields;

trait FieldsUtils
{
    // Non-standard frequency options
    public static $everyDay = 'day';
    public static $everyWeekday = 'weekday';
    public static $everyWeekend = 'weekend';

    // Enumerators for the monthly and annually schedule of a custom frequency
    public static $first = 'first';
    public static $second = 'second';
    public static $third = 'third';
    public static $fourth = 'fourth';
    public static $fifth = 'fifth';
    public static $last = 'last';

    private $regulars = [];

    protected function initUtils(): void
    {
        $this->regulars = [
            'MO' => $this->translate('Monday'),
            'TU' => $this->translate('Tuesday'),
            'WE' => $this->translate('Wednesday'),
            'TH' => $this->translate('Thursday'),
            'FR' => $this->translate('Friday'),
            'SA' => $this->translate('Saturday'),
            'SU' => $this->translate('Sunday')
        ];
    }

    protected function createOrdinalElement(): FormElement
    {
        return $this->createElement('select', 'ordinal', [
            'class'   => 'autosubmit',
            'value'   => $this->getPopulatedValue('ordinal', static::$first),
            'options' => [
                static::$first  => $this->translate('First'),
                static::$second => $this->translate('Second'),
                static::$third  => $this->translate('Third'),
                static::$fourth => $this->translate('Fourth'),
                static::$fifth  => $this->translate('Fifth'),
                static::$last   => $this->translate('Last')
            ]
        ]);
    }

    protected function createOrdinalSelectableDays(): FormElement
    {
        $select = $this->createElement('select', 'day', [
            'class'   => 'autosubmit',
            'value'   => $this->getPopulatedValue('day', static::$everyDay),
            'options' => $this->regulars + [
                'separator' => '──────────────────────────',
                static::$everyDay     => $this->translate('Day'),
                static::$everyWeekday => $this->translate('Weekday (Mon - Fri)'),
                static::$everyWeekend => $this->translate('WeekEnd (Sat or Sun)')
            ]
        ]);
        $select->getOption('separator')->getAttributes()->set('disabled', true);

        return $select;
    }

    /**
     * Load the given RRule instance into a list of key=>value pairs
     *
     * @param RRule $rule
     *
     * @return array
     */
    public function loadRRule(RRule $rule): array
    {
        $values = [];
        $isMonthly = $rule->getFrequency() === RRule::MONTHLY;
        if ($isMonthly && (! empty($rule->getByMonthDay()) || empty($rule->getByDay()))) {
            $monthDays = $rule->getByMonthDay() ?? [];
            foreach (range(1, $this->availableFields) as $value) {
                $values["day$value"] = in_array((string) $value, $monthDays, true) ? 'y' : 'n';
            }

            $values['runsOn'] = MonthlyFields::RUNS_EACH;
        } else {
            $position = $rule->getBySetPosition();
            $byDay = $rule->getByDay() ?? [];

            if ($isMonthly) {
                $values['runsOn'] = MonthlyFields::RUNS_ONTHE;
            } else {
                $months = $rule->getByMonth();
                if (empty($months) && $rule->getStart()) {
                    $months[] = $rule->getStart()->format('m');
                } elseif (empty($months)) {
                    $months[] = date('m');
                }

                $values['month'] = strtoupper($this->getMonthByNumber((int) $months[0]));
                $values['runsOnThe'] = ! empty($byDay) ? 'y' : 'n';
            }

            if (count($byDay) == 1 && preg_match('/^(-?\d)(\w.*)$/', $byDay[0], $matches)) {
                $values['ordinal'] = $this->getOrdinalString($matches[1]);
                $values['day'] = $this->getWeekdayName($matches[2]);
            } elseif (! empty($byDay)) {
                $values['ordinal'] = $this->getOrdinalString(current($position));
                switch (count($byDay)) {
                    case MonthlyFields::WEEK_DAYS:
                        $values['day'] = static::$everyDay;

                        break;
                    case MonthlyFields::WEEK_DAYS - 2:
                        $values['day'] = static::$everyWeekday;

                        break;
                    case 1:
                        $values['day'] = current($byDay);

                        break;
                    case 2:
                        $byDay = array_flip($byDay);
                        if (isset($byDay['SA']) && isset($byDay['SU'])) {
                            $values['day'] = static::$everyWeekend;
                        }
                }
            }
        }

        return $values;
    }

    /**
     * Transform the given expression part into a valid week day string representation
     *
     * @param string $day
     *
     * @return string
     */
    public function getWeekdayName(string $day): string
    {
        // Not transformation is needed when the given day is part of the valid weekdays
        if (isset($this->regulars[strtoupper($day)])) {
            return $day;
        }

        try {
            // Try to figure it out using date time before raising an error
            $datetime = new DateTime('Sunday');
            $datetime->add(new DateInterval("P$day" . 'D'));

            return $datetime->format('D');
        } catch (Exception $_) {
            throw new InvalidArgumentException(sprintf('Invalid weekday provided: %s', $day));
        }
    }

    /**
     * Transform the given integer enums into something like first,second...
     *
     * @param string $ordinal
     *
     * @return string
     */
    public function getOrdinalString(string $ordinal): string
    {
        switch ($ordinal) {
            case '1':
                return static::$first;
            case '2':
                return static::$second;
            case '3':
                return static::$third;
            case '4':
                return static::$fourth;
            case '5':
                return static::$fifth;
            case '-1':
                return static::$last;
            default:
                throw new InvalidArgumentException(
                    sprintf('Invalid ordinal string representation provided: %s', $ordinal)
                );
        }
    }

    /**
     * Get the string representation of the given ordinal to an integer
     *
     * This transforms the given ordinal such as (first, second...) into its respective
     * integral representation. At the moment only (1..5 + the non-standard "last") options
     * are supported. So if this method returns the character "-1", is meant the last option.
     *
     * @param string $ordinal
     *
     * @return int
     */
    public function getOrdinalAsInteger(string $ordinal): int
    {
        switch ($ordinal) {
            case static::$first:
                return 1;
            case static::$second:
                return 2;
            case static::$third:
                return 3;
            case static::$fourth:
                return 4;
            case static::$fifth:
                return 5;
            case static::$last:
                return -1;
            default:
                throw new InvalidArgumentException(sprintf('Invalid enumerator provided: %s', $ordinal));
        }
    }

    /**
     * Get a short textual representation of the given month
     *
     * @param int $month
     *
     * @return string
     */
    public function getMonthByNumber(int $month): string
    {
        $time = DateTime::createFromFormat('!m', $month);
        if ($time) {
            return $time->format('M');
        }

        throw new InvalidArgumentException(sprintf('Invalid month number provided: %d', $month));
    }
}
