<?php

namespace ipl\Web\FormElement\ScheduleElement;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\RRule;

class Recurrence extends BaseFormElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'schedule-recurrences'];

    /** @var callable A callable that generates a frequency instance */
    protected $frequencyCallback;

    /** @var callable A validation callback for the schedule element */
    protected $validateCallback;

    /**
     * Set a validation callback that will be called when assembling this element
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setValid(callable $callback): self
    {
        $this->validateCallback = $callback;

        return $this;
    }

    /**
     * Set a callback that generates an {@see Frequency} instance
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setFrequency(callable $callback): self
    {
        $this->frequencyCallback = $callback;

        return $this;
    }

    protected function assemble()
    {
        list($isValid, $reason) = ($this->validateCallback)();
        if (! $isValid) {
            // Render why we can't generate the recurrences
            $this->addHtml(Text::create($reason));

            return;
        }

        /** @var RRule $frequency */
        $frequency = ($this->frequencyCallback)();
        $recurrences = $frequency->getNextRecurrences(new DateTime(), 3);
        if (! $recurrences->valid()) {
            // Such a situation can be caused by setting an invalid end time
            $this->addHtml(HtmlElement::create('p', null, Text::create($this->translate('Never'))));

            return;
        }

        foreach ($recurrences as $recurrence) {
            $this->addHtml(HtmlElement::create('p', null, $recurrence->format($this->translate('D, Y/m/d, H:i:s'))));
        }
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('frequency', null, [$this, 'setFrequency'])
            ->registerAttributeCallback('validate', null, [$this, 'setValid']);
    }
}
