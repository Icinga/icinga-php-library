<?php

namespace ipl\Html\Contract;

use Evenement\EventEmitterInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Form extends EventEmitterInterface
{
    /** @var string Event emitted when the form is associated with a request but is not sent */
    public const ON_REQUEST = 'request';

    /** @var string Event emitted when the form has been sent */
    public const ON_SENT = 'sent';

    /** @var string Event emitted when the form is validated */
    public const ON_VALIDATE = 'validate';

    /** @var string Event emitted when the form has been submitted */
    public const ON_SUBMIT = 'success';

    /** @var string Event emitted in case of an error */
    public const ON_ERROR = 'error';

    /**
     * Get the Form submission URL
     *
     * @return ?string
     */
    public function getAction();

    /**
     * Get the HTTP method the form accepts
     *
     * @return string
     */
    public function getMethod();

    /**
     * Get the request associated with the form
     *
     * @return ?ServerRequestInterface
     */
    public function getRequest();

    /**
     * Handle the given request
     *
     * The following events will be emitted:
     * - {@see self::ON_REQUEST} when the form is associated with a request but is not sent
     * - {@see self::ON_SENT} when the form has been sent
     * - {@see self::ON_SUBMIT} when the form has been submitted
     * - {@see self::ON_ERROR} in case of an error
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function handleRequest(ServerRequestInterface $request);

    /**
     * Get whether the form has been sent
     *
     * A form is considered sent if the request's method equals the form's method.
     *
     * @return bool
     */
    public function hasBeenSent();

    /**
     * Get whether the form has been submitted
     *
     * A form is submitted when it has been sent and a submit button, if set, has been pressed.
     *
     * @return bool
     */
    public function hasBeenSubmitted();

    /**
     * Check if the form is valid
     *
     * Emits the {@see self::ON_VALIDATE} event if the form has not been validated before.
     *
     * @return bool
     */
    public function isValid();
}
