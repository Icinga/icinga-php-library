<?php

namespace ipl\Web\Widget;

/**
 * Button like link generally pointing to CRUD actions
 */
class ButtonLink extends ActionLink
{
    protected $defaultAttributes = [
        'class'            => 'button-link',
        'data-base-target' => '_main'
    ];
}
