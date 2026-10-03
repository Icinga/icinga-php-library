<?php

namespace ipl\Web\Control\ViewModeSwitcher;

use ipl\Web\Control\LimitControl;
use ipl\Web\Control\ViewModeSwitcher;
use ipl\Web\Widget\IcingaIcon;

/**
 * A view mode for the {@see ViewModeSwitcher}
 */
class ViewMode
{
    /**
     * Create a new view mode
     *
     * @param string $name The identifier of the view mode, which is used as value of the view mode url param
     * @param string $icon The name of the {@see IcingaIcon} of the view mode
     * @param string $activeTitle The title to show while the view mode is active
     * @param string $inactiveTitle The title to show while the view mode is inactive
     * @param int $pageSize The default page size for this view mode
     */
    public function __construct(
        protected string $name,
        protected string $icon,
        protected string $activeTitle,
        protected string $inactiveTitle,
        protected int $pageSize = LimitControl::DEFAULT_LIMIT
    ) {
    }

    /**
     * Get the name of this view mode
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the {@see IcingaIcon} to show for this view mode
     *
     * @return IcingaIcon
     */
    public function getIcon(): IcingaIcon
    {
        return new IcingaIcon($this->icon);
    }

    /**
     * Get the title for this view mode
     *
     * @param bool $active Whether this mode is currently active
     *
     * @return string
     */
    public function getTitle(bool $active): string
    {
        return $active ? $this->activeTitle : $this->inactiveTitle;
    }

    /**
     * Get the default page size for this view mode
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
