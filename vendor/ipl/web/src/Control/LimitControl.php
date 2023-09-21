<?php

namespace ipl\Web\Control;

use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Allows to adjust the limit of the number of items to display
 */
class LimitControl extends CompatForm
{
    /** @var int Default limit */
    const DEFAULT_LIMIT = 25;

    /** @var string Default limit param */
    const DEFAULT_LIMIT_PARAM = 'limit';

    /** @var int[] Selectable default limits */
    public static $limits = [
        '25'  => '25',
        '50'  => '50',
        '100' => '100',
        '500' => '500'
    ];

    /** @var string Name of the URL parameter which stores the limit */
    protected $limitParam = self::DEFAULT_LIMIT_PARAM;

    /** @var int */
    protected $defaultLimit;

    /** @var Url */
    protected $url;

    protected $method = 'GET';

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * Get the name of the URL parameter which stores the limit
     *
     * @return string
     */
    public function getLimitParam()
    {
        return $this->limitParam;
    }

    /**
     * Set the name of the URL parameter which stores the limit
     *
     * @param string $limitParam
     *
     * @return $this
     */
    public function setLimitParam($limitParam)
    {
        $this->limitParam = $limitParam;

        return $this;
    }

    /**
     * Get the default limit
     *
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit ?: static::DEFAULT_LIMIT;
    }

    /**
     * Set the default limit
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setDefaultLimit($limit)
    {
        $this->defaultLimit = $limit;

        return $this;
    }

    /**
     * Get the limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->url->getParam($this->getLimitParam(), $this->getDefaultLimit());
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => 'limit-control inline']);

        $limits = static::$limits;
        if ($this->defaultLimit && ! isset($limits[$this->defaultLimit])) {
            $limits[$this->defaultLimit] = $this->defaultLimit;
        }

        $limit = $this->getLimit();
        if (! isset($limits[$limit])) {
            $limits[$limit] = $limit;
        }

        $this->addElement('select', $this->getLimitParam(), [
            'class'   => 'autosubmit',
            'label'   => '#',
            'options' => $limits,
            'title'   => t('Change item count per page'),
            'value'   => $limit
        ]);
    }
}
