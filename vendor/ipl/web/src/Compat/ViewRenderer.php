<?php

namespace ipl\Web\Compat;

use Icinga\Web\View;
use ipl\Html\HtmlDocument;
use Zend_Controller_Action_Helper_ViewRenderer as Zf1ViewRenderer;
use Zend_Controller_Action_HelperBroker as Zf1HelperBroker;

class ViewRenderer extends Zf1ViewRenderer
{
    /** @var View */
    public $view;

    /**
     * Inject the view renderer
     */
    public static function inject()
    {
        /** @var \Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zf1HelperBroker::getStaticHelper('ViewRenderer');

        $inject = new static();

        foreach (get_object_vars($viewRenderer) as $property => $value) {
            if ($property === '_inflector') {
                continue;
            }

            $inject->$property = $value;
        }

        Zf1HelperBroker::removeHelper('ViewRenderer');
        Zf1HelperBroker::addHelper($inject);
    }

    public function getName()
    {
        return 'ViewRenderer';
    }

    /**
     * Render the view w/o using a view script
     *
     * {@inheritdoc}
     */
    public function render($action = null, $name = null, $noController = null)
    {
        /** @var HtmlDocument $document */
        $document = $this->view->document;

        if ($document->isEmpty() || $this->getRequest()->getParam('error_handler') !== null) {
            parent::render($action, $name, $noController);

            return;
        }

        if ($name === null) {
            $name = $this->getResponseSegment();
        }

        $this->getResponse()->appendBody($document->render(), $name);

        $this->setNoRender();
    }
}
