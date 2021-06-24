<?php

namespace ipl\Web\Widget;

use Exception;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\Tabextension;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlString;
use ipl\Web\Url;

/**
 * @TODO(el): Don't depend on Icinga Web's Tabs
 */
class Tabs extends BaseHtmlElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'tabs primary-nav nav'];

    /** @var \Icinga\Web\Widget\Tabs */
    protected $tabs;

    /** @var bool Whether data exports are enabled */
    protected $dataExportsEnabled = false;

    /** @var bool Whether the legacy extensions should be shown by default */
    protected $legacyExtensionsEnabled = true;

    /** @var Url */
    protected $refreshUrl;

    public function __construct()
    {
        $this->tabs = new \Icinga\Web\Widget\Tabs();
    }

    /**
     * Don't show legacy extensions by default
     */
    public function disableLegacyExtensions()
    {
        $this->legacyExtensionsEnabled = false;
    }

    /**
     * Show export actions for JSON and CSV
     */
    public function enableDataExports()
    {
        $this->dataExportsEnabled = true;
    }

    /**
     * Set the url for the refresh button
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setRefreshUrl(Url $url)
    {
        $this->refreshUrl = $url;

        return $this;
    }

    protected function assemble()
    {
        if ($this->legacyExtensionsEnabled) {
            $this->tabs->extend(new OutputFormat(
                $this->dataExportsEnabled
                    ? []
                    : [OutputFormat::TYPE_CSV, OutputFormat::TYPE_JSON]
            ))
                ->extend(new DashboardAction())
                ->extend(new MenuAction());
        }

        $tabHtml = substr($this->tabs->render(), 34, -5);
        if ($this->refreshUrl !== null) {
            $tabHtml = preg_replace(
                '/(?<=class="refresh-container-control spinner" href=")([^"]*)/',
                $this->refreshUrl->getAbsoluteUrl(),
                $tabHtml
            );
        }

        parent::add(HtmlString::create($tabHtml));
    }

    /**
     * Activate the tab with the given name
     *
     * @param string $name
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function activate($name)
    {
        try {
            $this->tabs->activate($name);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        return $this;
    }

    /**
     * Get active tab
     *
     * @return \Icinga\Web\Widget\Tab
     */
    public function getActiveTab()
    {
        return $this->tabs->get($this->tabs->getActiveName());
    }

    /**
     * Add the given tab
     *
     * @param string $name
     * @param mixed  $tab
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add($name, $tab = null)
    {
        if ($tab === null) {
            throw new InvalidArgumentException('Argument $tab is required');
        }

        try {
            $this->tabs->add($name, $tab);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        if (is_array($tab) && isset($tab['active']) && $tab['active']) {
            // Otherwise Tabs::getActiveName() returns null
            $this->tabs->activate($name);
        }

        return $this;
    }

    /**
     * Get a tab
     *
     * @param string $name
     *
     * @return \Icinga\Web\Widget\Tab|null
     */
    public function get($name)
    {
        return $this->tabs->get($name);
    }

    /**
     * Count tabs
     *
     * @return int
     */
    public function count()
    {
        return $this->tabs->count();
    }

    /**
     * Apply a Tabextension on $this->tabs object not on this class
     *
     * @param   Tabextension $extension
     *
     * @return  $this
     */
    public function extend(Tabextension $extension)
    {
        $this->tabs->extend($extension);

        return $this;
    }
}
