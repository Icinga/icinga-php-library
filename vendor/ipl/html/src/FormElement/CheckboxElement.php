<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attributes;

class CheckboxElement extends InputElement
{
    /** @var bool Whether the checkbox is checked */
    protected $checked = false;

    /** @var string Value of the checkbox when it is checked */
    protected $checkedValue = 'y';

    /** @var string Value of the checkbox when it is not checked */
    protected $uncheckedValue = 'n';

    protected $type = 'checkbox';

    /**
     * Get whether the checkbox is checked
     *
     * @return bool
     */
    public function isChecked()
    {
        return $this->checked;
    }

    /**
     * Set whether the checkbox is checked
     *
     * @param bool $checked
     *
     * @return $this
     */
    public function setChecked($checked)
    {
        $this->checked = (bool) $checked;

        return $this;
    }

    /**
     * Get the value of the checkbox when it is checked
     *
     * @return string
     */
    public function getCheckedValue()
    {
        return $this->checkedValue;
    }

    /**
     * Set the value of the checkbox when it is checked
     *
     * @param string $checkedValue
     *
     * @return $this
     */
    public function setCheckedValue($checkedValue)
    {
        $this->checkedValue = $checkedValue;

        return $this;
    }

    /**
     * Get the value of the checkbox when it is not checked
     *
     * @return string
     */
    public function getUncheckedValue()
    {
        return $this->uncheckedValue;
    }

    /**
     * Set the value of the checkbox when it is not checked
     *
     * @param string $uncheckedValue
     *
     * @return $this
     */
    public function setUncheckedValue($uncheckedValue)
    {
        $this->uncheckedValue = $uncheckedValue;

        return $this;
    }

    public function setValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? $this->getCheckedValue() : $this->getUncheckedValue();
        }

        $this->setChecked($value === $this->getCheckedValue());

        return parent::setValue($value);
    }

    public function getValueAttribute()
    {
        return $this->getCheckedValue();
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback(
            'checked',
            [$this, 'isChecked'],
            [$this, 'setChecked']
        );
    }

    public function renderUnwrapped()
    {
        $html = parent::renderUnwrapped();

        return (new HiddenElement($this->getName(), ['value' => $this->getUncheckedValue()])) . $html;
    }
}
