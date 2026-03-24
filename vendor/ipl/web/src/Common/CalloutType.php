<?php

namespace ipl\Web\Common;

use ipl\Web\Widget\Icon;

/**
 * An enum containing all possible callout types for the {@see Callout} widget.
 */
enum CalloutType: string
{
    case Info = 'callout-type-info';
    case Success = 'callout-type-success';
    case Warning = 'callout-type-warning';
    case Error = 'callout-type-error';

    /**
     * Get the icon element for use in the callout
     *
     * @return Icon
     */
    public function getIcon(): Icon
    {
        return new Icon(match ($this) {
            self::Info => 'circle-info',
            self::Success => 'circle-check',
            self::Warning => 'warning',
            self::Error => 'circle-xmark',
        });
    }
}
