<?php

namespace ipl\Web\FormElement\ScheduleElement;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Validator\CallbackValidator;
use ipl\Validator\ValidatorChain;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;

class WeeklyFields extends FieldsetElement
{
    use FieldsProtector;

    /** @var array A list of valid week days */
    protected $weekdays = [];

    /** @var string A valid weekday to be selected by default */
    protected $default = 'MO';

    public function __construct($name, $attributes = null)
    {
        $this->weekdays = [
            'MO' => $this->translate('Mon'),
            'TU' => $this->translate('Tue'),
            'WE' => $this->translate('Wed'),
            'TH' => $this->translate('Thu'),
            'FR' => $this->translate('Fri'),
            'SA' => $this->translate('Sat'),
            'SU' => $this->translate('Sun')
        ];

        parent::__construct($name, $attributes);
    }

    /**
     * Set the default weekday to be preselected
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): self
    {
        $weekday = strlen($default) > 2 ? substr($default, 0, -1) : $default;
        if (! isset($this->weekdays[strtoupper($weekday)])) {
            throw new InvalidArgumentException(sprintf('Invalid weekday provided: %s', $default));
        }

        $this->default = strtoupper($weekday);

        return $this;
    }

    /**
     * Get all the selected weekdays
     *
     * @return array
     */
    public function getSelectedWeekDays(): array
    {
        $selectedDays = [];
        foreach ($this->weekdays as $day => $_) {
            if ($this->getValue($day, 'n') === 'y') {
                $selectedDays[] = $day;
            }
        }

        if (empty($selectedDays)) {
            $selectedDays[] = $this->default;
        }

        return $selectedDays;
    }

    /**
     * Transform the given weekdays into key=>value array that can be populated
     *
     * @param array $weekdays
     *
     * @return array
     */
    public function loadWeekDays(array $weekdays): array
    {
        $values = [];
        foreach ($this->weekdays as $weekday => $_) {
            $values[$weekday] = in_array($weekday, $weekdays, true) ? 'y' : 'n';
        }

        return $values;
    }

    protected function assemble()
    {
        $this->getAttributes()->set('id', $this->protectId('weekly-fields'));

        $fieldsWrapper = HtmlElement::create('div', ['class' => 'weekly']);
        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'multiple-fields']]);

        foreach ($this->weekdays as $day => $value) {
            $checkbox = $this->createElement('checkbox', $day, [
                'class' => ['autosubmit', 'sr-only'],
                'value' => $day === $this->default
            ]);
            $this->registerElement($checkbox);

            $htmlId = $this->protectId("weekday-$day");
            $checkbox->getAttributes()->set('id', $htmlId);

            $listItem = HtmlElement::create('li');
            $listItem->addHtml($checkbox, HtmlElement::create('label', ['for' => $htmlId], $value));
            $listItems->addHtml($listItem);
        }

        $fieldsWrapper->addHtml($listItems);
        $this->addHtml($fieldsWrapper);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('default', null, [$this, 'setDefault'])
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }

    protected function addDefaultValidators(ValidatorChain $chain): void
    {
        $chain->add(
            new CallbackValidator(function ($_, CallbackValidator $validator): bool {
                $valid = false;
                foreach ($this->weekdays as $weekday => $_) {
                    if ($this->getValue($weekday) === 'y') {
                        $valid = true;

                        break;
                    }
                }

                if (! $valid) {
                    $validator->addMessage($this->translate('You must select at least one of these weekdays'));
                }

                return $valid;
            })
        );
    }
}
