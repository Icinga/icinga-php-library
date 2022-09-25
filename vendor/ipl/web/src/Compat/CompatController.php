<?php

namespace ipl\Web\Compat;

use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;
use Icinga\Web\Controller;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\SearchBar;
use ipl\Web\Control\SortControl;
use ipl\Web\Layout\Content;
use ipl\Web\Layout\Controls;
use ipl\Web\Layout\Footer;
use ipl\Web\Url;
use ipl\Web\Widget\Tabs;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

class CompatController extends Controller
{
    /** @var Content */
    protected $content;

    /** @var Controls */
    protected $controls;

    /** @var HtmlDocument */
    protected $document;

    /** @var Footer */
    protected $footer;

    /** @var Tabs */
    protected $tabs;

    /** @var array */
    protected $parts;

    protected function prepareInit()
    {
        parent::prepareInit();

        $this->params->shift('isIframe');
        $this->params->shift('showFullscreen');
        $this->params->shift('showCompact');
        $this->params->shift('renderLayout');
        $this->params->shift('_disableLayout');
        $this->params->shift('_dev');
        if ($this->params->get('view') === 'compact') {
            $this->params->remove('view');
        }

        $this->document = new HtmlDocument();
        $this->document->setSeparator("\n");
        $this->controls = new Controls();
        $this->controls->setAttribute('id', $this->getRequest()->protectId('controls'));
        $this->content = new Content();
        $this->content->setAttribute('id', $this->getRequest()->protectId('content'));
        $this->footer = new Footer();
        $this->footer->setAttribute('id', $this->getRequest()->protectId('footer'));
        $this->tabs = new Tabs();
        $this->tabs->setAttribute('id', $this->getRequest()->protectId('tabs'));
        $this->parts = [];

        $this->view->tabs = $this->tabs;
        $this->controls->setTabs($this->tabs);

        ViewRenderer::inject();

        $this->view->document = $this->document;
    }

    /**
     * Get the current server request
     *
     * @return ServerRequestInterface
     */
    public function getServerRequest()
    {
        return ServerRequest::fromGlobals();
    }

    /**
     * Get the document
     *
     * @return HtmlDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Get the tabs
     *
     * @return Tabs
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Add content
     *
     * @param ValidHtml $content
     *
     * @return $this
     */
    protected function addContent(ValidHtml $content)
    {
        $this->content->add($content);

        return $this;
    }

    /**
     * Add a control
     *
     * @param ValidHtml $control
     *
     * @return $this
     */
    protected function addControl(ValidHtml $control)
    {
        $this->controls->add($control);

        return $this;
    }

    /**
     * Add footer
     *
     * @param ValidHtml $footer
     *
     * @return $this
     */
    protected function addFooter(ValidHtml $footer)
    {
        $this->footer->add($footer);

        return $this;
    }

    /**
     * Add a part to be served as multipart-content
     *
     * If an id is passed the element is used as-is as the part's content.
     * Otherwise (no id given) the element's content is used instead.
     *
     * @param ValidHtml $element
     * @param string    $id      If not given, this is taken from $element
     *
     * @throws InvalidArgumentException If no id is given and the element also does not have one
     *
     * @return $this
     */
    protected function addPart(ValidHtml $element, $id = null)
    {
        $part = new Multipart();

        if ($id === null) {
            if (! $element instanceof BaseHtmlElement) {
                throw new InvalidArgumentException('If no id is given, $element must be a BaseHtmlElement');
            }

            $id = $element->getAttributes()->get('id')->getValue();
            if (! $id) {
                throw new InvalidArgumentException('Element has no id');
            }

            $part->addFrom($element);
        } else {
            $part->add($element);
        }

        $this->parts[] = $part->setFor($id);

        return $this;
    }

    /**
     * Set the given title as the window's title
     *
     * @param string $title
     * @param mixed  ...$args
     *
     * @return $this
     */
    protected function setTitle($title, ...$args)
    {
        if (! empty($args)) {
            $title = vsprintf($title, $args);
        }

        $this->view->title = $title;

        return $this;
    }

    /**
     * Add an active tab with the given title and set it as the window's title too
     *
     * @param string $title
     * @param mixed  ...$args
     *
     * @return $this
     */
    protected function addTitleTab($title, ...$args)
    {
        $this->setTitle($title, ...$args);

        $tabName = uniqid();
        $this->getTabs()->add($tabName, [
            'label'     => $this->view->title,
            'url'       => $this->getRequest()->getUrl()
        ])->activate($tabName);

        return $this;
    }

    /**
     * Create and return the LimitControl
     *
     * This automatically shifts the limit URL parameter from {@link $params}.
     *
     * @return LimitControl
     */
    public function createLimitControl(): LimitControl
    {
        $limitControl = new LimitControl(Url::fromRequest());
        $limitControl->setDefaultLimit($this->getPageSize(null));

        $this->params->shift($limitControl->getLimitParam());

        return $limitControl;
    }

    /**
     * Create and return the PaginationControl
     *
     * This automatically shifts the pagination URL parameters from {@link $params}.
     *
     * @param Paginatable $paginatable
     *
     * @return PaginationControl
     */
    public function createPaginationControl(Paginatable $paginatable): PaginationControl
    {
        $paginationControl = new PaginationControl($paginatable, Url::fromRequest());
        $paginationControl->setDefaultPageSize($this->getPageSize(null));
        $paginationControl->setAttribute('id', $this->getRequest()->protectId('pagination-control'));

        $this->params->shift($paginationControl->getPageParam());
        $this->params->shift($paginationControl->getPageSizeParam());

        return $paginationControl->apply();
    }

    /**
     * Create and return the SortControl
     *
     * This automatically shifts the sort URL parameter from {@link $params}.
     *
     * @param Query $query
     * @param array $columns Possible sort columns as sort string-label pairs
     *
     * @return SortControl
     */
    public function createSortControl(Query $query, array $columns): SortControl
    {
        $sortControl = SortControl::create($columns);

        $this->params->shift($sortControl->getSortParam());

        return $sortControl->apply($query);
    }

    /**
     * Send a multipart update instead of a standard response
     *
     * As part of a multipart update, the tabs, content and footer as well as selected controls are
     * transmitted in a way the client can render them exclusively instead of a full column reload.
     *
     * By default the only control included in the response is the pagination control, if added.
     *
     * @param BaseHtmlElement ...$additionalControls Additional controls to include
     *
     * @throws LogicException In case an additional control has not been added
     */
    public function sendMultipartUpdate(BaseHtmlElement ...$additionalControls)
    {
        $searchBar = null;
        $pagination = null;
        $redirectUrl = null;
        foreach ($this->controls->getContent() as $control) {
            if ($control instanceof PaginationControl) {
                $pagination = $control;
            } elseif ($control instanceof SearchBar) {
                $searchBar = $control;
                $redirectUrl = $control->getRedirectUrl(); /** @var Url $redirectUrl */
            }
        }

        if ($searchBar !== null && ($changes = $searchBar->getChanges()) !== null) {
            $this->addPart(HtmlString::create(json_encode($changes)), 'Behavior:InputEnrichment');
        }

        foreach ($additionalControls as $control) {
            $this->addPart($control);
        }

        if ($searchBar !== null && $this->content->isEmpty() && ! $searchBar->isValid()) {
            // No content and an invalid search bar? That's it then, further updates are not required
            return;
        }

        if ($this->tabs->count() > 0) {
            if ($redirectUrl !== null) {
                $this->tabs->setRefreshUrl($redirectUrl);
                $this->tabs->getActiveTab()->setUrl($redirectUrl);

                // As long as we still depend on the legacy tab implementation
                // there is no other way to influence what the tab extensions
                // use as url. (https://github.com/Icinga/icingadb-web/issues/373)
                $oldPathInfo = $this->getRequest()->getPathInfo();
                $oldQuery = $_SERVER['QUERY_STRING'];
                $this->getRequest()->setPathInfo('/' . $redirectUrl->getPath());
                $_SERVER['QUERY_STRING'] = $redirectUrl->getParams()->toString();
                $this->tabs->ensureAssembled();
                $this->getRequest()->setPathInfo($oldPathInfo);
                $_SERVER['QUERY_STRING'] = $oldQuery;
            }

            $this->addPart($this->tabs);
        }

        if ($pagination !== null) {
            if ($redirectUrl !== null) {
                $pagination->setUrl(clone $redirectUrl);
            }

            $this->addPart($pagination);
        }

        if (! $this->content->isEmpty()) {
            $this->addPart($this->content);
        }

        if (! $this->footer->isEmpty()) {
            $this->addPart($this->footer);
        }

        if ($redirectUrl !== null) {
            $this->getResponse()->setHeader('X-Icinga-Location-Query', $redirectUrl->getParams()->toString());
        }
    }

    public function postDispatch()
    {
        if (empty($this->parts)) {
            if (! $this->content->isEmpty()) {
                $this->document->prepend($this->content);

                if (! $this->view->compact && ! $this->controls->isEmpty()) {
                    $this->document->prepend($this->controls);
                }

                if (! $this->footer->isEmpty()) {
                    $this->document->add($this->footer);
                }
            }
        } else {
            $partSeparator = base64_encode(random_bytes(16));
            $this->getResponse()->setHeader('X-Icinga-Multipart-Content', $partSeparator);

            $this->document->setSeparator("\n$partSeparator\n");
            $this->document->add($this->parts);
        }

        parent::postDispatch();
    }
}
