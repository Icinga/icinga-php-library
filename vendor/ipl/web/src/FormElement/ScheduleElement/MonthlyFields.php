<?php

namespace ipl\Web\FormElement\ScheduleElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Validator\CallbackValidator;
use ipl\Validator\InArrayValidator;
use ipl\Validator\ValidatorChain;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsUtils;

class MonthlyFields extends FieldsetElement
{
    use FieldsUtils;
    use FieldsProtector;

    /** @var string Used as radio option to run each selected days/months */
    public const RUNS_EACH = 'each';

    /** @var string Used as radio option to build complex job schedules */
    public const RUNS_ONTHE = 'onthe';

    /** @var int Number of days in a week */
    public const WEEK_DAYS = 7;

    /** @var int Day of the month to preselect by default */
    protected $default = 1;

    /** @var int Number of fields to render */
    protected $availableFields;

    protected function init(): void
    {
        parent::init();
        $this->initUtils();

        $this->availableFields = (int) date('t');
    }

    /**
     * Set the available fields/days of the month to be rendered
     *
     * @param int $fields
     *
     * @return $this
     */
    public function setAvailableFields(int $fields): self
    {
        $this->availableFields = $fields;

        return $this;
    }

    /**
     * Set the default field/day to be selected
     *
     * @param int $default
     *
     * @return $this
     */
    public function setDefault(int $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get all the selected weekdays
     *
     * @return array
     */
    public function getSelectedDays(): array
    {
        $selectedDays = [];
        foreach (range(1, $this->availableFields) as $day) {
            if ($this->getValue("day$day", 'n') === 'y') {
                $selectedDays[] = $day;
            }
        }

        if (empty($selectedDays)) {
            $selectedDays[] = $this->default;
        }

        return $selectedDays;
    }

    protected function assemble()
    {
        $this->getAttributes()->set('id', $this->protectId('monthly-fields'));

        $runsOn = $this->getPopulatedValue('runsOn', static::RUNS_EACH);
        $this->addElement('radio', 'runsOn', [
            'required' => true,
            'class'    => 'autosubmit',
            'value'    => $runsOn,
            'options'  => [static::RUNS_EACH => $this->translate('Each')],
        ]);

        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'multiple-fields']]);
        if ($runsOn === static::RUNS_ONTHE) {
            $listItems->getAttributes()->add('class', 'disabled');
        }

        foreach (range(1, $this->availableFields) as $day) {
            $checkbox = $this->createElement('checkbox', "day$day", [
                'class' => ['autosubmit', 'sr-only'],
                'value' => $day === $this->default && $runsOn === static::RUNS_EACH
            ]);
            $this->registerElement($checkbox);

            $htmlId = $this->protectId("day$day");
            $checkbox->getAttributes()->set('id', $htmlId);

            $listItem = HtmlElement::create('li');
            $listItem->addHtml($checkbox, HtmlElement::create('label', ['for' => $htmlId], $day));
            $listItems->addHtml($listItem);
        }

        $monthlyWrapper = HtmlElement::create('div', ['class' => 'monthly']);
        $runsEach = $this->getElement('runsOn');
        $runsEach->prependWrapper($monthlyWrapper);
        $monthlyWrapper->addHtml($runsEach, $listItems);

        $this->addElement('radio', 'runsOn', [
            'required'   => $runsOn !== static::RUNS_EACH,
            'class'      => 'autosubmit',
            'options'    => [static::RUNS_ONTHE => $this->translate('On the')],
            'validators' => [
                new InArrayValidator([
                    'strict'   => true,
                    'haystack' => [static::RUNS_EACH, static::RUNS_ONTHE]
                ])
            ]
        ]);

        $ordinalWrapper = HtmlElement::create('div', ['class' => 'ordinal']);
        $runsOnThe = $this->getElement('runsOn');
        $runsOnThe->prependWrapper($ordinalWrapper);
        $ordinalWrapper->addHtml($runsOnThe);

        $enumerations = $this->createOrdinalElement();
        $enumerations->getAttributes()->set('disabled', $runsOn === static::RUNS_EACH);
        $this->registerElement($enumerations);

        $selectableDays = $this->createOrdinalSelectableDays();
        $selectableDays->getAttributes()->set('disabled', $runsOn === static::RUNS_EACH);
        $this->registerElement($selectableDays);

        $ordinalWrapper->addHtml($enumerations, $selectableDays);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('default', null, [$this, 'setDefault'])
            ->registerAttributeCallback('availableFields', null, [$this, 'setAvailableFields'])
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }

    protected function addDefaultValidators(ValidatorChain $chain): void
    {
        $chain->add(
            new CallbackValidator(function ($_, CallbackValidator $validator): bool {
                if ($this->getValue('runsOn', static::RUNS_EACH) !== static::RUNS_EACH) {
                    return true;
                }

                $valid = false;
                foreach (range(1, $this->availableFields) as $day) {
                    if ($this->getValue("day$day") === 'y') {
                        $valid = true;

                        break;
                    }
                }

                if (! $valid) {
                    $validator->addMessage($this->translate('You must select at least one of these days'));
                }

                return $valid;
            })
        );
    }
}
