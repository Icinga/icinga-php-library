<?php

namespace ipl\Html;

use ipl\Html\Contract\DefaultFormElementDecoration;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\FormDecoration\DecoratorChain;
use ipl\Html\FormDecoration\FormDecorationResult;
use ipl\Html\FormElement\FormElements;
use ipl\Stdlib\Messages;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Form extends BaseHtmlElement implements Contract\Form, Contract\FormElements, DefaultFormElementDecoration
{
    use FormElements {
        FormElements::remove as private baseRemove;
        FormElements::beforeRender as private baseBeforeRender;
    }
    use Messages;

    /** @deprecated Use {@see Contract\Form::ON_SUBMIT} instead */
    public const ON_SUCCESS = 'success';

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

    /** @var ?DecoratorChain<FormDecoration> */
    protected ?DecoratorChain $decorators = null;

    /** @var bool Whether the form has been decorated */
    protected bool $decorated = false;

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
        return $value === null || $value === [] || (is_string($value) && trim($value) === '');
    }

    /**
     * Escape reserved chars in the given string
     *
     * The characters '.', ' ' and optionally brackets are converted to unused control characters
     * File Separator: ␜, Group Separator: ␝, Record Separator: ␝, and Unit Separator: ␟ respectively.
     *
     * This is done because:
     * PHP converts dots and spaces in form element names to underscores by default in the request data.
     * For example, <input name="a.b" /> becomes $_REQUEST["a_b"].
     *
     * And if an external variable name begins with a valid array syntax, trailing characters are silently ignored.
     * For example, <input name="foo[bar]baz"> becomes $_REQUEST['foo']['bar'].
     * See https://www.php.net/manual/en/language.variables.external.php
     *
     * @param string $string The string to escape
     * @param bool   $escapeBrackets Whether to escape brackets
     *
     * @return string
     */
    public static function escapeReservedChars(string $string, bool $escapeBrackets = true): string
    {
        $escapeMap = [
            '.' => chr(28), // File Separator
            ' ' => chr(29)  // Group Separator
        ];

        if ($escapeBrackets) {
            $escapeMap['['] = chr(30); // Record Separator
            $escapeMap[']'] = chr(31); // Unit Separator
        }

        return strtr($string, $escapeMap);
    }

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

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;

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
     * Get the decorators of this form
     *
     * @return DecoratorChain<FormDecoration>
     */
    public function getDecorators(): DecoratorChain
    {
        if ($this->decorators === null) {
            $this->decorators = new DecoratorChain(FormDecoration::class);
        }

        return $this->decorators;
    }

    public function handleRequest(ServerRequestInterface $request)
    {
        $this->setRequest($request);

        if (! $this->hasBeenSent()) {
            $this->emit(Contract\Form::ON_REQUEST, [$request, $this]);

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
                    $this->emit(Contract\Form::ON_SENT, [$this]);
                    $this->onSuccess();
                    $this->emitOnce(Contract\Form::ON_SUBMIT, [$this]);
                } catch (Throwable $e) {
                    $this->addMessage($e);
                    $this->onError();
                    $this->emit(Contract\Form::ON_ERROR, [$e, $this]);
                }
            } else {
                $this->onError();
            }
        } else {
            $this->validatePartial();
            $this->emit(Contract\Form::ON_SENT, [$this]);
        }

        return $this;
    }

    public function hasBeenSent()
    {
        if ($this->request === null) {
            return false;
        }

        return $this->request->getMethod() === $this->getMethod();
    }

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

    public function isValid()
    {
        if ($this->isValid === null) {
            $this->validate();

            $this->emit(Contract\Form::ON_VALIDATE, [$this]);
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

    public function remove(ValidHtml $content)
    {
        if ($this->submitButton === $content) {
            $this->submitButton = null;
        }

        $this->baseRemove($content);

        return $this;
    }

    /**
     * Apply the form decoration
     *
     * @return void
     */
    protected function applyDecoration(): void
    {
        if ($this->decorated) {
            return;
        }

        $result = new FormDecorationResult($this);
        foreach ($this->getDecorators() as $decorator) {
            $decorator->decorateForm($result, $this);
        }

        $this->decorated = true;
    }

    protected function onError()
    {
        $errors = Html::tag('ul', ['class' => 'errors']);
        foreach ($this->getMessages() as $message) {
            if ($message instanceof Throwable) {
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

    protected function beforeRender(): void
    {
        $this->baseBeforeRender();

        $this->applyDecoration();
    }
}
