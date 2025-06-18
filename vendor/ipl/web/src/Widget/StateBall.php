<?php

namespace ipl\Web\Widget;

/**
 * State ball element that supports different sizes and colors
 *
 * @phpstan-import-type SIZE from Ball
 * @phpstan-type STATE 'none'|'pending'|'up'|'down'|'ok'|'critical'|'warning'|'unknown'|'unreachable'
 */
class StateBall extends Ball
{
    protected $defaultAttributes = ['class' => 'state-ball'];

    /** @var STATE */
    protected $state = 'none';

    /** @var bool */
    protected $handled = false;

    /**
     * Create a new state ball element
     *
     * @param STATE $state
     * @param SIZE $size
     */
    public function __construct(string $state = 'none', string $size = self::SIZE_SMALL)
    {
        $state = trim($state);
        if (empty($state)) {
            $state = 'none';
        }

        $this->state = $state;

        parent::__construct($size);
    }

    /**
     * Show the handled state instead
     *
     * @param bool $handled
     *
     * @return $this
     */
    public function setHandled(bool $handled): self
    {
        $this->handled = $handled;

        return $this;
    }

    protected function assembleCssClasses(): array
    {
        $classes = parent::assembleCssClasses();
        $classes[] = 'state-' . $this->state;
        if ($this->handled) {
            $classes[] = 'handled';
        }

        return $classes;
    }
}
