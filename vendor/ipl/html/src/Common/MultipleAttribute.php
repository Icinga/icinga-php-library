<?php

namespace ipl\Html\Common;

use ipl\Html\Attributes;
use ipl\Html\Contract\FormElement;

/**
 * Trait for form elements that can have the `multiple` attribute
 *
 * **Example usage:**
 *
 * ```
 * namespace ipl\Html\FormElement;
 *
 * use ipl\Html\Common\MultipleAttribute;
 *
 * class SelectElement extends BaseFormElement
 * {
 *     protected function registerAttributeCallbacks(Attributes $attributes)
 *     {
 *         // ...
 *         $this->registerMultipleAttributeCallback($attributes);
 *     }
 * }
 * ```
 */
trait MultipleAttribute
{
    /** @var bool Whether the attribute `multiple` is set to `true` */
    protected $multiple = false;

    /**
     * Get whether the attribute `multiple` is set to `true`
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Set the `multiple` attribute
     *
     * @param bool $multiple
     *
     * @return $this
     */
    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * Register the callback for `multiple` Attribute
     *
     * @param Attributes $attributes
     */
    protected function registerMultipleAttributeCallback(Attributes $attributes): void
    {
        $attributes->registerAttributeCallback(
            'multiple',
            [$this, 'isMultiple'],
            [$this, 'setMultiple']
        );
    }
}
