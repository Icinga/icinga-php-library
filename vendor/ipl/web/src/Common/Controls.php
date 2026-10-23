<?php

namespace ipl\Web\Common;

use ipl\Html\Form;
use ipl\Stdlib\Events;
use ipl\Web\Control\ViewModeSwitcher;
use ipl\Web\Url;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides factory methods to prepare reusable web controls and allows to handle their requests
 *
 * {@see static::handleControls()} can be called to call {@see Form::handleRequest()} for all Controls created by
 * this trait.
 */
trait Controls
{
    use Events;

    /** @var string Event emitted when the view mode has been changed */
    public const ON_VIEW_MODE_CHANGE = 'view-mode-change';

    /** @var string Event emmitted when the created {@see ViewModeSwitcher} is populated with a view mode */
    public const ON_VIEW_MODE_SET = 'view-mode-set';

    /** @var array<Form> Controls for which {@see self::handleControls()} must call {@see Form::handleRequest()} */
    private array $trackedControls = [];

    /**
     * Redirect to the given Url
     *
     * @param Url|string $url
     *
     * @return never
     */
    abstract protected function redirectNow($url);

    /**
     * Create a {@see ViewModeSwitcher} control
     *
     * On {@see ViewModeSwitcher::ON_REQUEST} the created instance is populated with the view mode from the url
     * and {@see static::ON_VIEW_MODE_SET} is emmitted.
     *
     * On {@see ViewModeSwitcher::ON_SUBMIT}, if the view mode was changed, {@see static::ON_VIEW_MODE_CHANGE}
     * is emmitted with the {@see ViewModeSwitcher}, its previous view mode and a redirect url, which listeners
     * may modify.
     *
     * @return ViewModeSwitcher
     */
    public function createViewModeSwitcher(): ViewModeSwitcher
    {
        $viewModeSwitcher = new ViewModeSwitcher();

        $this->trackControl($viewModeSwitcher);

        $viewModeSwitcher->on(
            ViewModeSwitcher::ON_REQUEST,
            function (
                ServerRequestInterface $request,
                ViewModeSwitcher $viewModeSwitcher
            ) {
                $viewModeSwitcher->populate([
                    $viewModeSwitcher->getViewModeParam()
                        => $request->getQueryParams()[$viewModeSwitcher->getViewModeParam()] ?? null
                ]);

                $this->emit(static::ON_VIEW_MODE_SET, [$viewModeSwitcher]);
            }
        );

        $viewModeSwitcher->on(
            ViewModeSwitcher::ON_SUBMIT,
            function (
                ViewModeSwitcher $viewModeSwitcher
            ): void {
                $previousViewModeName =
                    $viewModeSwitcher->getRequest()->getQueryParams()[$viewModeSwitcher->getViewModeParam()]
                    ?? $viewModeSwitcher->getDefaultViewMode()->getName();
                $previousViewMode = $viewModeSwitcher->getViewModes()[$previousViewModeName];

                if ($viewModeSwitcher->getViewMode()->getName() !== $previousViewMode->getName()) {
                    $redirectUrl = Url::fromRequest();
                    $this->emit(static::ON_VIEW_MODE_CHANGE, [$viewModeSwitcher, $previousViewMode, $redirectUrl]);
                    $redirectUrl->setParam(
                        $viewModeSwitcher->getViewModeParam(),
                        $viewModeSwitcher->getViewMode()->getName()
                    );
                    $this->redirectNow($redirectUrl);
                }
            }
        );

        return $viewModeSwitcher;
    }

    /**
     * Register the given control for lookup by {@see self::getTrackedControl()} and for {@see self::handleControls()}
     *
     * @param Form $control
     *
     * @return $this
     */
    protected function trackControl(Form $control): static
    {
        $this->trackedControls[] = $control;

        return $this;
    }

    /**
     * Get the tracked control of the given type
     *
     * @template TControl of Form
     *
     * @param class-string<TControl> $type
     *
     * @return ?TControl
     */
    protected function getTrackedControl(string $type): ?Form
    {
        foreach ($this->trackedControls as $control) {
            if ($control instanceof $type) {
                return $control;
            }
        }

        return null;
    }

    /**
     * Call {@see Form::handleRequest()} on every control in {@see self::$trackedControls}.
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    protected function handleControls(ServerRequestInterface $request): static
    {
        foreach ($this->trackedControls as $control) {
            $control->handleRequest($request);
        }

        return $this;
    }
}
