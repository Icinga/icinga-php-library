<?php

namespace ipl\Web\FormElement;

use DateTime;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\Cron;
use ipl\Scheduler\OneOff;
use ipl\Scheduler\RRule;
use ipl\Validator\BetweenValidator;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormElement\ScheduleElement\AnnuallyFields;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;
use ipl\Web\FormElement\ScheduleElement\MonthlyFields;
use ipl\Web\FormElement\ScheduleElement\Recurrence;
use ipl\Web\FormElement\ScheduleElement\WeeklyFields;
use LogicException;
use Psr\Http\Message\RequestInterface;

class ScheduleElement extends FieldsetElement
{
    use FieldsProtector;

    /** @var string Plain cron expressions */
    protected const CRON_EXPR = 'cron_expr';

    /** @var string Configure the individual expression parts manually */
    protected const CUSTOM_EXPR = 'custom';

    /** @var string Used to run a one-off task */
    protected const NO_REPEAT = 'none';

    protected $defaultAttributes = ['class' => 'schedule-element'];

    /** @var array A list of allowed frequencies used to configure custom expressions */
    protected $customFrequencies = [];

    /** @var array */
    protected $advanced = [];

    /** @var array */
    protected $regulars = [];

    /** @var string Schedule frequency of this element */
    protected $frequency = self::NO_REPEAT;

    /** @var string */
    protected $customFrequency;

    /** @var DateTime */
    protected $start;

    /** @var WeeklyFields Weekly parts of this schedule element */
    protected $weeklyField;

    /** @var MonthlyFields Monthly parts of this schedule element */
    protected $monthlyFields;

    /** @var AnnuallyFields Annually parts of this schedule element */
    protected $annuallyFields;

    protected function init(): void
    {
        $this->start = new DateTime();
        $this->weeklyField = new WeeklyFields('weekly-fields', [
            'default'   => $this->start->format('D'),
            'protector' => function (string $day) {
                return $this->protectId($day);
            },
        ]);

        $this->monthlyFields = new MonthlyFields('monthly-fields', [
            'default'         => $this->start->format('j'),
            'availableFields' => (int) $this->start->format('t'),
            'protector'       => function ($day) {
                return $this->protectId($day);
            }
        ]);

        $this->annuallyFields = new AnnuallyFields('annually-fields', [
            'default'   => $this->start->format('M'),
            'protector' => function ($month) {
                return $this->protectId($month);
            }
        ]);


        $this->regulars = [
            RRule::MINUTELY  => $this->translate('Minutely'),
            RRule::HOURLY    => $this->translate('Hourly'),
            RRule::DAILY     => $this->translate('Daily'),
            RRule::WEEKLY    => $this->translate('Weekly'),
            RRule::MONTHLY   => $this->translate('Monthly'),
            RRule::QUARTERLY => $this->translate('Quarterly'),
            RRule::YEARLY    => $this->translate('Annually'),
        ];

        $this->customFrequencies = array_slice($this->regulars, 2);
        unset($this->customFrequencies[RRule::QUARTERLY]);

        $this->advanced = [
            static::CUSTOM_EXPR => $this->translate('Custom…'),
            static::CRON_EXPR   => $this->translate('Cron Expression…')
        ];
    }

    /**
     * Get whether this element is rendering a cron expression
     *
     * @return bool
     */
    public function hasCronExpression(): bool
    {
        return $this->getFrequency() === static::CRON_EXPR;
    }

    /**
     * Get the frequency of this element
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->getPopulatedValue('frequency', $this->frequency);
    }

    /**
     * Set the custom frequency of this schedule element
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setFrequency(string $frequency): self
    {
        if (
            $frequency !== static::NO_REPEAT
            && ! isset($this->regulars[$frequency])
            && ! isset($this->advanced[$frequency])
        ) {
            throw new InvalidArgumentException(sprintf('Invalid frequency provided: %s', $frequency));
        }

        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get custom frequency of this element
     *
     * @return ?string
     */
    public function getCustomFrequency(): ?string
    {
        return $this->getValue('custom-frequency', $this->customFrequency);
    }

    /**
     * Set custom frequency of this element
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setCustomFrequency(string $frequency): self
    {
        if (! isset($this->customFrequencies[$frequency])) {
            throw new InvalidArgumentException(sprintf('Invalid custom frequency provided: %s', $frequency));
        }

        $this->customFrequency = $frequency;

        return $this;
    }

    /**
     * Set start time of the parsed expressions
     *
     * @param DateTime $start
     *
     * @return $this
     */
    public function setStart(DateTime $start): self
    {
        $this->start = $start;

        // Forward the start time update to the sub elements as well!
        $this->weeklyField->setDefault($start->format('D'));
        $this->annuallyFields->setDefault($start->format('M'));
        $this->monthlyFields
            ->setDefault((int) $start->format('j'))
            ->setAvailableFields((int) $start->format('t'));

        return $this;
    }

    public function getValue($name = null, $default = null)
    {
        if ($name !== null || ! $this->hasBeenValidated()) {
            return parent::getValue($name, $default);
        }

        $frequency = $this->getFrequency();
        $start = parent::getValue('start');
        switch ($frequency) {
            case static::NO_REPEAT:
                return new OneOff($start);
            case static::CRON_EXPR:
                $rule = new Cron(parent::getValue('cron_expression'));

                break;
            case RRule::MINUTELY:
            case RRule::HOURLY:
            case RRule::DAILY:
            case RRule::WEEKLY:
            case RRule::MONTHLY:
            case RRule::QUARTERLY:
            case RRule::YEARLY:
                $rule = RRule::fromFrequency($frequency);

                break;
            default: // static::CUSTOM_EXPR
                $interval = parent::getValue('interval', 1);
                $customFrequency = parent::getValue('custom-frequency', RRule::DAILY);
                switch ($customFrequency) {
                    case RRule::DAILY:
                        if ($interval === '*') {
                            $interval = 1;
                        }

                        $rule = new RRule("FREQ=DAILY;INTERVAL=$interval");

                        break;
                    case RRule::WEEKLY:
                        $byDay = implode(',', $this->weeklyField->getSelectedWeekDays());

                        $rule = new RRule("FREQ=WEEKLY;INTERVAL=$interval;BYDAY=$byDay");

                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case RRule::MONTHLY:
                        $runsOn = $this->monthlyFields->getValue('runsOn', MonthlyFields::RUNS_EACH);
                        if ($runsOn === MonthlyFields::RUNS_EACH) {
                            $byMonth = implode(',', $this->monthlyFields->getSelectedDays());

                            $rule = new RRule("FREQ=MONTHLY;INTERVAL=$interval;BYMONTHDAY=$byMonth");

                            break;
                        }
                    // Fall-through to the next switch case
                    case RRule::YEARLY:
                        $rule = "FREQ=MONTHLY;INTERVAL=$interval;";
                        if ($customFrequency === RRule::YEARLY) {
                            $runsOn = $this->annuallyFields->getValue('runsOnThe', 'n');
                            $month = $this->annuallyFields->getValue('month', (int) $this->start->format('m'));
                            if (is_string($month)) {
                                $datetime = DateTime::createFromFormat('!M', $month);
                                if (! $datetime) {
                                    throw new InvalidArgumentException(sprintf('Invalid month provided: %s', $month));
                                }

                                $month = (int) $datetime->format('m');
                            }

                            $rule = "FREQ=YEARLY;INTERVAL=1;BYMONTH=$month;";
                            if ($runsOn === 'n') {
                                $rule = new RRule($rule);

                                break;
                            }
                        }

                        $element = $this->monthlyFields;
                        if ($customFrequency === RRule::YEARLY) {
                            $element = $this->annuallyFields;
                        }

                        $runDay = $element->getValue('day', $element::$everyDay);
                        $ordinal = $element->getValue('ordinal', $element::$first);
                        $position = $element->getOrdinalAsInteger($ordinal);

                        if ($runDay === $element::$everyDay) {
                            $rule .= "BYDAY=MO,TU,WE,TH,FR,SA,SU;BYSETPOS=$position";
                        } elseif ($runDay === $element::$everyWeekday) {
                            $rule .= "BYDAY=MO,TU,WE,TH,FR;BYSETPOS=$position";
                        } elseif ($runDay === $element::$everyWeekend) {
                            $rule .= "BYDAY=SA,SU;BYSETPOS=$position";
                        } else {
                            $rule .= sprintf('BYDAY=%d%s', $position, $runDay);
                        }

                        $rule = new RRule($rule);

                        break;
                    default:
                        throw new LogicException(sprintf('Custom frequency %s is not supported!', $customFrequency));
                }
        }

        $rule->startAt($start);
        if (parent::getValue('use-end-time', 'n') === 'y') {
            $rule->endAt(parent::getValue('end'));
        }

        return $rule;
    }

    public function setValue($value)
    {
        $values = $value;
        $rule = $value;
        if ($rule instanceof Frequency) {
            if ($rule->getStart()) {
                $this->setStart($rule->getStart());
            }

            $values = [];
            if ($rule->getEnd() && ! $rule instanceof OneOff) {
                $values['use-end-time'] = 'y';
                $values['end'] = $rule->getEnd();
            }

            if ($rule instanceof OneOff) {
                $values['frequency'] = static::NO_REPEAT;
            } elseif ($rule instanceof Cron) {
                $values['cron_expression'] = $rule->getExpression();
                $values['frequency'] = static::CRON_EXPR;

                $this->setFrequency(static::CRON_EXPR);
            } elseif ($rule instanceof RRule) {
                $values['interval'] = $rule->getInterval();
                switch ($rule->getFrequency()) {
                    case RRule::DAILY:
                        if ($rule->getInterval() <= 1 && strpos($rule->getString(), 'INTERVAL=') === false) {
                            $this->setFrequency(RRule::DAILY);
                        } else {
                            $this
                                ->setFrequency(static::CUSTOM_EXPR)
                                ->setCustomFrequency(RRule::DAILY);
                        }

                        break;
                    case RRule::WEEKLY:
                        if (! $rule->getByDay() || empty($rule->getByDay())) {
                            $this->setFrequency(RRule::WEEKLY);
                        } else {
                            $values['weekly-fields'] = $this->weeklyField->loadWeekDays($rule->getByDay());
                            $this
                                ->setFrequency(static::CUSTOM_EXPR)
                                ->setCustomFrequency(RRule::WEEKLY);
                        }

                        break;
                    case RRule::MONTHLY:
                    case RRule::YEARLY:
                        $isMonthly = $rule->getFrequency() === RRule::MONTHLY;
                        if ($rule->getByDay() || $rule->getByMonthDay() || $rule->getByMonth()) {
                            $this->setFrequency(static::CUSTOM_EXPR);

                            if ($isMonthly) {
                                $values['monthly-fields'] = $this->monthlyFields->loadRRule($rule);
                                $this->setCustomFrequency(RRule::MONTHLY);
                            } else {
                                $values['annually-fields'] = $this->annuallyFields->loadRRule($rule);
                                $this->setCustomFrequency(RRule::YEARLY);
                            }
                        } elseif ($isMonthly && $rule->getInterval() === 3) {
                            $this->setFrequency(RRule::QUARTERLY);
                        } else {
                            $this->setFrequency($rule->getFrequency());
                        }

                        break;
                    default:
                        $this->setFrequency($rule->getFrequency());
                }

                $values['frequency'] = $this->getFrequency();
                $values['custom-frequency'] = $this->getCustomFrequency();
            }
        }

        return parent::setValue($values);
    }

    protected function assemble()
    {
        $start = $this->getPopulatedValue('start', $this->start);
        if (! $start instanceof DateTime) {
            $start = new DateTime($start);
        }
        $this->setStart($start);

        $autosubmit = ! $this->hasCronExpression() && $this->getFrequency() !== static::NO_REPEAT;
        $this->addElement('localDateTime', 'start', [
            'class'       => $autosubmit ? 'autosubmit' : null,
            'required'    => true,
            'label'       => $this->translate('Start'),
            'value'       => $start,
            'description' => $this->translate('Start time of this schedule')
        ]);

        $this->addElement('checkbox', 'use-end-time', [
            'required' => false,
            'class'    => 'autosubmit',
            'disabled' => $this->getPopulatedValue('frequency', static::NO_REPEAT) === static::NO_REPEAT ?: null,
            'value'    => $this->getPopulatedValue('use-end-time', 'n'),
            'label'    => $this->translate('Use End Time')
        ]);

        if ($this->getPopulatedValue('use-end-time', 'n') === 'y') {
            $end = $this->getPopulatedValue('end', new DateTime());
            if (! $end instanceof DateTime) {
                $end = new DateTime($end);
            }

            $this->addElement('localDateTime', 'end', [
                'class'       => ! $this->hasCronExpression() ? 'autosubmit' : null,
                'required'    => true,
                'value'       => $end,
                'label'       => $this->translate('End'),
                'description' => $this->translate('End time of this schedule')
            ]);
        }

        $this->addElement('select', 'frequency', [
            'required'    => false,
            'class'       => 'autosubmit',
            'label'       => $this->translate('Frequency'),
            'description' => $this->translate('Specifies how often this job run should be recurring'),
            'options'     => [
                static::NO_REPEAT            => $this->translate('None'),
                $this->translate('Regular')  => $this->regulars,
                $this->translate('Advanced') => $this->advanced
            ],
        ]);

        if ($this->getFrequency() === static::CUSTOM_EXPR) {
            $this->addElement('select', 'custom-frequency', [
                'required'    => false,
                'class'       => 'autosubmit',
                'value'       => parent::getValue('custom-frequency'),
                'options'     => $this->customFrequencies,
                'label'       => $this->translate('Custom Frequency'),
                'description' => $this->translate('Specifies how often this job run should be recurring')
            ]);

            switch (parent::getValue('custom-frequency', RRule::DAILY)) {
                case RRule::DAILY:
                    $this->assembleCommonElements();

                    break;
                case RRule::WEEKLY:
                    $this->assembleCommonElements();
                    $this->addElement($this->weeklyField);

                    break;
                case RRule::MONTHLY:
                    $this->assembleCommonElements();
                    $this->addElement($this->monthlyFields);

                    break;
                case RRule::YEARLY:
                    $this->addElement($this->annuallyFields);
            }
        } elseif ($this->hasCronExpression()) {
            $this->addElement('text', 'cron_expression', [
                'required'    => true,
                'label'       => $this->translate('Cron Expression'),
                'description' => $this->translate('Job cron Schedule'),
                'validators' => [
                    new CallbackValidator(function ($value, CallbackValidator $validator) {
                        if ($value && ! Cron::isValid($value)) {
                            $validator->addMessage($this->translate('Invalid CRON expression'));

                            return false;
                        }

                        return true;
                    })
                ]
            ]);
        }

        if ($this->getFrequency() !== static::NO_REPEAT && ! $this->hasCronExpression()) {
            $this->addElement(
                new Recurrence('schedule-recurrences', [
                    'id'        => $this->protectId('schedule-recurrences'),
                    'label'     => $this->translate('Next occurrences'),
                    'validate'  => function (): array {
                        $isValid = $this->isValid();
                        $reason = null;
                        if (! $isValid && $this->getFrequency() === static::CUSTOM_EXPR) {
                            if (! $this->getElement('interval')->isValid()) {
                                $reason = current($this->getElement('interval')->getMessages());
                            } else {
                                $frequency = $this->getCustomFrequency();
                                switch ($frequency) {
                                    case RRule::WEEKLY:
                                        $reason = current($this->weeklyField->getMessages());

                                        break;
                                    default: // monthly
                                        $reason = current($this->monthlyFields->getMessages());

                                        break;
                                }
                            }
                        }

                        return [$isValid, $reason];
                    },
                    'frequency' => function (): Frequency {
                        if ($this->getFrequency() === static::CUSTOM_EXPR) {
                            $rule = $this->getValue();
                        } else {
                            $rule = RRule::fromFrequency($this->getFrequency());
                        }

                        $now = new DateTime();
                        $start = $this->getValue('start');
                        if ($start < $now) {
                            $now->setTime($start->format('H'), $start->format('i'), $start->format('s'));
                            $start = $now;
                        }

                        $rule->startAt($start);
                        if ($this->getPopulatedValue('use-end-time') === 'y') {
                            $rule->endAt($this->getValue('end'));
                        }

                        return $rule;
                    }
                ])
            );
        }
    }

    /**
     * Assemble common parts for all the frequencies
     */
    private function assembleCommonElements(): void
    {
        $repeat = $this->getCustomFrequency();
        if ($repeat === RRule::WEEKLY) {
            $text = $this->translate('week(s) on');
            $max = 53;
        } elseif ($repeat === RRule::MONTHLY) {
            $text = $this->translate('month(s)');
            $max = 12;
        } else {
            $text = $this->translate('day(s)');
            $max = 31;
        }

        $options = ['min' => 1, 'max' => $max];
        $this->addElement('number', 'interval', [
            'class'      => 'autosubmit',
            'value'      => 1,
            'min'        => 1,
            'max'        => $max,
            'validators' => [new BetweenValidator($options)]
        ]);

        $numberSpecifier = HtmlElement::create('div', ['class' => 'number-specifier']);
        $element = $this->getElement('interval');
        $element->prependWrapper($numberSpecifier);

        $numberSpecifier->prependHtml(HtmlElement::create('span', null, $this->translate('Every')));
        $numberSpecifier->addHtml($element);
        $numberSpecifier->addHtml(HtmlElement::create('span', null, $text));
    }

    /**
     * Get prepared multipart updates
     *
     * @param RequestInterface $request
     *
     * @return array
     */
    public function prepareMultipartUpdate(RequestInterface $request): array
    {
        $autoSubmittedBy = $request->getHeader('X-Icinga-AutoSubmittedBy');
        $pattern = '/\[(weekly-fields|monthly-fields|annually-fields)]\[(ordinal|month|day(\d+)?|[A-Z]{2})]$/';

        $partUpdates = [];
        if (
            $autoSubmittedBy
            && (
                preg_match('/\[(start|end)]$/', $autoSubmittedBy[0], $matches)
                || preg_match($pattern, $autoSubmittedBy[0])
                || preg_match('/\[interval]/', $autoSubmittedBy[0])
            )
        ) {
            $this->ensureAssembled();

            $partUpdates[] = $this->getElement('schedule-recurrences');
            if (
                $this->getFrequency() === static::CUSTOM_EXPR
                && $this->getCustomFrequency() === RRule::MONTHLY
                && isset($matches[1])
                && $matches[1] === 'start'
            ) {
                // To update the available fields/days based on the provided start time
                $partUpdates[] = $this->monthlyFields;
            }
        }

        return $partUpdates;
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}
