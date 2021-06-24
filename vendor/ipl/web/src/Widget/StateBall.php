<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * State ball element that supports different sizes and colors
 */
class StateBall extends BaseHtmlElement
{
    const SIZE_TINY = 'xs';
    const SIZE_SMALL = 's';
    const SIZE_MEDIUM = 'm';
    const SIZE_BIG = 'l';
    const SIZE_LARGE = 'xl';

    protected $tag = 'span';

    /**
     * Create a new state ball element
     *
     * @param string $state
     * @param string $size
     */
    public function __construct($state = 'none', $size = self::SIZE_SMALL)
    {
        $state = trim($state);

        if (empty($state)) {
            $state = 'none';
        }

        $size = trim($size);

        if (empty($size)) {
            $size = self::SIZE_MEDIUM;
        }

        $this->defaultAttributes = ['class' => "state-ball state-$state ball-size-$size"];
    }
}
