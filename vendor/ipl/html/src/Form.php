<?php

namespace ipl\Html;

use Exception;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\FormElement\FormElements;
use ipl\Stdlib\Messages;
use Psr\Http\Message\ServerRequestInterface;

class Form extends BaseHtmlElement
{
    use FormElements {
        FormElements::remove as private removeElement;
    }
    use Messages;

    public const ON_ELEMENT_REGISTERED = 'elementRegistered';
    public const ON_ERROR = 'error';
    public const ON_REQUEST = 'request';
    public const ON_SUCCESS = 'success';
    public const ON_SENT = 'sent';
    public const ON_VALIDATE = 'validate';

    /** @var string Form submission URL */
    protected $action;

    /** @var string HTTP method to submit the form with */
    protected $method = 'POST';

    /** @var FormSubmitElement Primary submit button */
    protected $submitButton;

    /** @var FormSubmitElement[] Other elements that may submit the form */
    protected $submitElements = [];

    /** @var bool Whether the form is valid */
    protected $isValid;

    /** @var ServerRequestInterface The server request being processed */
    protected $request;

    /** @var string */
    protected $redirectUrl;

    protected $tag = 'form';

    /**
     * Get whether the given value is empty
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isEmptyValue($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Get the Form submission URL
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the Form submission URL
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the HTTP method to submit the form with
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the HTTP method to submit the form with
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Get whether the form has a primary submit button
     *
     * @return bool
     */
    public function hasSubmitButton()
    {
        return $this->submitButton !== null;
    }

    /**
     * Get the primary submit button
     *
     * @return FormSubmitElement|null
     */
    public function getSubmitButton()
    {
        return $this->submitButton;
    }

    /**
     * Set the primary submit button
     *
     * @param FormSubmitElement $element
     *
     * @return $this
     */
    public function setSubmitButton(FormSubmitElement $element)
    {
        $this->submitButton = $element;

        return $this;
    }

    /**
     * Get the submit element used to send the form
     *
     * @return FormSubmitElement|null
     */
    public function getPressedSubmitElement()
    {
        foreach ($this->submitElements as $submitElement) {
            if ($submitElement->hasBeenPressed()) {
                return $submitElement;
            }
        }

        return null;
    }

    /**
     * @return ServerRequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;
        $this->emit(Form::ON_REQUEST, [$request]);

        return $this;
    }

    /**
     * Get the url to redirect to on success
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set the url to redirect to on success
     *
     * @param string $url
     *
     * @return $this
     */
    public function setRedirectUrl($url)
    {
        $this->redirectUrl = $url;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->setRequest($request);

        if (! $this->hasBeenSent()) {
            // Always assemble
            $this->ensureAssembled();

            return $this;
        }

        switch ($request->getMethod()) {
            case 'POST':
                $params = $request->getParsedBody();

                break;
            case 'GET':
                parse_str($request->getUri()->getQuery(), $params);

                break;
            default:
                $params = [];
        }

        $params = array_merge_recursive($params, $request->getUploadedFiles());
        $this->populate($params);

        // Assemble after populate in order to conditionally provide form elements
        $this->ensureAssembled();

        if ($this->hasBeenSubmitted()) {
            if ($this->isValid()) {
                try {
                    $this->emit(Form::ON_SENT, [$this]);
                    $this->onSuccess();
                    $this->emitOnce(Form::ON_SUCCESS, [$this]);
                } catch (Exception $e) {
                    $this->addMessage($e);
                    $this->onError();
                    $this->emit(Form::ON_ERROR, [$e, $this]);
                }
            } else {
                $this->onError();
            }
        } else {
            $this->validatePartial();
            $this->emit(Form::ON_SENT, [$this]);
        }

        return $this;
    }

    /**
     * Get whether the form has been sent
     *
     * A form is considered sent if the request's method equals the form's method.
     *
     * @return bool
     */
    public function hasBeenSent()
    {
        if ($this->request === null) {
            return false;
        }

        return $this->request->getMethod() === $this->getMethod();
    }

    /**
     * Get whether the form has been submitted
     *
     * A form is submitted when it has been sent and when the primary submit button, if set, has been pressed.
     * This method calls {@link hasBeenSent()} in order to detect whether the form has been sent.
     *
     * @return bool
     */
    public function hasBeenSubmitted()
    {
        if (! $this->hasBeenSent()) {
            return false;
        }

        if ($this->hasSubmitButton()) {
            return $this->getSubmitButton()->hasBeenPressed();
        }

        return true;
    }

    /**
     * Get whether the form is valid
     *
     * {@link validate()} is called automatically if the form has not been validated before.
     *
     * @return bool
     */
    public function isValid()
    {
        if ($this->isValid === null) {
            $this->validate();

            $this->emit(self::ON_VALIDATE, [$this]);
        }

        return $this->isValid;
    }

    /**
     * Validate all elements
     *
     * @return $this
     */
    public function validate()
    {
        $this->ensureAssembled();

        $valid = true;
        foreach ($this->getElements() as $element) {
            $element->validate();
            if (! $element->isValid()) {
                $valid = false;
            }
        }

        $this->isValid = $valid;

        return $this;
    }

    /**
     * Validate all elements that have a value
     *
     * @return $this
     */
    public function validatePartial()
    {
        $this->ensureAssembled();

        foreach ($this->getElements() as $element) {
            if ($element->hasValue()) {
                $element->validate();
            }
        }

        return $this;
    }

    public function remove(ValidHtml $elementOrHtml)
    {
        if ($this->submitButton === $elementOrHtml) {
            $this->submitButton = null;
        }

        $this->removeElement($elementOrHtml);
    }

    protected function onError()
    {
        $errors = Html::tag('ul', ['class' => 'errors']);
        foreach ($this->getMessages() as $message) {
            if ($message instanceof Exception) {
                $message = $message->getMessage();
            }

            $errors->addHtml(Html::tag('li', $message));
        }

        if (! $errors->isEmpty()) {
            $this->prependHtml($errors);
        }
    }

    protected function onSuccess()
    {
        // $this->redirectOnSuccess();
    }

    protected function onElementRegistered(FormElement $element)
    {
        if ($element instanceof FormSubmitElement) {
            $this->submitElements[$element->getName()] = $element;

            if (! $this->hasSubmitButton()) {
                $this->setSubmitButton($element);
            }
        }

        $element->onRegistered($this);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        $attributes
            ->registerAttributeCallback('action', [$this, 'getAction'], [$this, 'setAction'])
            ->registerAttributeCallback('method', [$this, 'getMethod'], [$this, 'setMethod']);
    }
}
