<?php

namespace ipl\Web\Control;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Web\Common\FormUid;
use ipl\Web\Control\ViewModeSwitcher\ViewMode;
use LogicException;

class ViewModeSwitcher extends Form
{
    use FormUid;
    use Translation;

    protected $defaultAttributes = [
        'class' => 'view-mode-switcher',
        'name' => 'view-mode-switcher'
    ];

    /** @var string Default view mode */
    public const DEFAULT_VIEW_MODE = 'common';

    /** @var string Default view mode param */
    public const DEFAULT_VIEW_MODE_PARAM = 'view';

    /** @var array<string, ViewMode> The available view modes, keyed by their name */
    protected array $viewModes;

    /** @var ?string Name of the default view mode */
    protected ?string $defaultViewMode = null;

    /** @var string */
    protected $method = 'POST';

    /** @var callable */
    protected $protector;

    protected string $viewModeParam = self::DEFAULT_VIEW_MODE_PARAM;

    /**
     * Create a view mode switcher providing the `minimal`, `common` and `detailed` view modes
     */
    public function __construct()
    {
        $this->setViewModes(
            new ViewMode(
                'minimal',
                'minimal',
                $this->translate('Minimal view active'),
                $this->translate('Switch to minimal view'),
                50
            ),
            new ViewMode(
                'common',
                'default',
                $this->translate('Common view active'),
                $this->translate('Switch to common view')
            ),
            new ViewMode(
                'detailed',
                'detailed',
                $this->translate('Detailed view active'),
                $this->translate('Switch to detailed view')
            )
        );
    }

    /**
     * Get the available view modes
     *
     * @return array<string, ViewMode>
     */
    public function getViewModes(): array
    {
        return $this->viewModes;
    }

    /**
     * Set the available view modes, replacing any existing ones
     *
     * @param ViewMode ...$viewModes
     *
     * @return $this
     *
     * @throws InvalidArgumentException If $viewModes is empty
     */
    public function setViewModes(ViewMode ...$viewModes): static
    {
        if (empty($viewModes)) {
            throw new InvalidArgumentException('At least one view mode is required.');
        }

        $this->viewModes = [];
        foreach ($viewModes as $viewMode) {
            $this->viewModes[$viewMode->getName()] = $viewMode;
        }

        return $this;
    }

    /**
     * Add a view mode, replacing an existing one if the name is already used
     *
     * @param ViewMode $viewMode
     *
     * @return $this
     */
    public function addViewMode(ViewMode $viewMode): static
    {
        $this->viewModes[$viewMode->getName()] = $viewMode;

        return $this;
    }

    /**
     * Remove the view mode with the given name
     *
     * @param string $name
     *
     * @return $this
     *
     * @throws LogicException When attempting to remove the last view mode
     */
    public function removeViewMode(string $name): static
    {
        if (isset($this->viewModes[$name]) && count($this->viewModes) === 1) {
            throw new LogicException('Cannot remove the last view mode.');
        }

        unset($this->viewModes[$name]);

        return $this;
    }

    /**
     * Get the default view mode
     *
     * Falls back to the first view mode if no default view mode was set and {@see self::DEFAULT_VIEW_MODE} was removed
     *
     * @return ViewMode
     */
    public function getDefaultViewMode(): ViewMode
    {
        if ($this->defaultViewMode !== null) {
            return $this->viewModes[$this->defaultViewMode];
        }

        return $this->viewModes[static::DEFAULT_VIEW_MODE] ?? $this->viewModes[array_key_first($this->viewModes)];
    }

    /**
     * Set the default view mode by its name
     *
     * @param string $defaultViewMode
     *
     * @return $this
     */
    public function setDefaultViewMode(string $defaultViewMode): static
    {
        $this->defaultViewMode = $defaultViewMode;

        return $this;
    }

    /**
     * Get the view mode URL parameter
     *
     * @return string
     */
    public function getViewModeParam(): string
    {
        return $this->viewModeParam;
    }

    /**
     * Set the view mode URL parameter
     *
     * @param string $viewModeParam
     *
     * @return $this
     */
    public function setViewModeParam(string $viewModeParam): static
    {
        $this->viewModeParam = $viewModeParam;

        return $this;
    }

    /**
     * Get the active view mode
     *
     * @return ViewMode
     */
    public function getViewMode(): ViewMode
    {
        $viewModeName = $this->getPopulatedValue($this->getViewModeParam(), $this->getDefaultViewMode()->getName());

        // View mode stays null if explicitly populated with null.
        if ($viewModeName !== null && array_key_exists($viewModeName, $this->viewModes)) {
            return $this->viewModes[$viewModeName];
        }

        return $this->getDefaultViewMode();
    }

    /**
     * Set the active view mode by name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setViewMode(string $name): static
    {
        $this->populate([$this->getViewModeParam() => $name]);

        return $this;
    }

    /**
     * Set callback to protect ids with
     *
     * @param callable(string): string $protector
     *
     * @return $this
     */
    public function setIdProtector(callable $protector): static
    {
        $this->protector = $protector;

        return $this;
    }

    private function protectId(string $id): string
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }

    protected function assemble(): void
    {
        $viewModeParam = $this->getViewModeParam();

        $this->addElement($this->createUidElement());
        $this->addElement(new HiddenElement($viewModeParam));

        foreach ($this->viewModes as $name => $viewMode) {
            $protectedId = $this->protectId('view-mode-switcher-' . $name);
            $input = new InputElement($viewModeParam, [
                'class' => 'autosubmit',
                'id' => $protectedId,
                'name' => $viewModeParam,
                'type' => 'radio',
                'value' => $name
            ]);
            $input->getAttributes()->registerAttributeCallback('checked', function () use ($name) {
                return $name === $this->getViewMode()->getName();
            });

            $label = new HtmlElement(
                'label',
                Attributes::create([
                    'for' => $protectedId
                ]),
                $viewMode->getIcon()
            );
            $label->getAttributes()->registerAttributeCallback('title', function () use ($name, $viewMode) {
                return $viewMode->getTitle($name === $this->getViewMode()->getName());
            });

            $this->addHtml($input, $label);
        }
    }
}
