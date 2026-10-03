<?php

namespace ipl\Web\Common;

use Error;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormElement\HiddenElement;

trait CsrfCounterMeasure
{
    /** @var ?string The ID of the CSRF form element */
    private ?string $csrfCounterMeasureId = null;

    /** @var bool Whether to actually add the CSRF element to the form */
    private bool $csrfCounterMeasureEnabled = true;

    /**
     * Set the ID for the CSRF form element
     *
     * @param string $id A unique ID that persists through different requests
     *
     * @return $this
     */
    public function setCsrfCounterMeasureId(string $id): static
    {
        $this->csrfCounterMeasureId = $id;

        return $this;
    }

    /**
     * Disable the CSRF form element
     *
     * @return void
     */
    public function disableCsrfCounterMeasure(): void
    {
        $this->csrfCounterMeasureEnabled = false;
    }

    /**
     * Create a form element to countermeasure CSRF attacks
     *
     * If the {@see requestIsSafe()} check concludes the request is safe, returns a dummy element that accepts any
     * value. If it concludes the request is unsafe, throws an {@see Error}. If the check is inconclusive (legacy
     * browser), creates a token-based CSRF element validated against $uniqueId.
     *
     * @param string $uniqueId A unique ID that persists through different requests
     *
     * @return FormElement
     *
     * @throws Error If {@see requestIsSafe()} returns false
     *
     * @deprecated Use {@see addCsrfCounterMeasure()} instead
     */
    protected function createCsrfCounterMeasure($uniqueId)
    {
        $requestIsSafe = $this->requestIsSafe();

        if ($requestIsSafe !== null) {
            if (! $requestIsSafe) {
                throw new Error('Rejecting cross-site request');
            }

            return new HiddenElement('CSRFToken', [
                'ignore'     => true,
                'validators' => ['Callback' => function () {
                    return true;
                }]
            ]);
        }

        $hashAlgo = in_array('sha3-256', hash_algos(), true) ? 'sha3-256' : 'sha256';

        $seed = random_bytes(16);
        $token = base64_encode($seed) . '|' . hash($hashAlgo, $uniqueId . $seed);

        $options = [
            'ignore'        => true,
            'required'      => true,
            'validators'    => ['Callback' => function ($token) use ($uniqueId, $hashAlgo) {
                if (empty($token) || strpos($token, '|') === false) {
                    throw new Error('Invalid CSRF token provided');
                }

                list($seed, $hash) = explode('|', $token);

                if ($hash !== hash($hashAlgo, $uniqueId . base64_decode($seed))) {
                    throw new Error('Invalid CSRF token provided');
                }

                return true;
            }]
        ];

        $element = new class ('CSRFToken', $options) extends HiddenElement {
            public function hasValue(): bool
            {
                return true; // The validator must run even if the value is empty
            }
        };

        $element->getAttributes()->registerAttributeCallback('value', function () use ($token) {
            return $token;
        });

        return $element;
    }

    /**
     * Add the CSRF form element to this form
     *
     * Does nothing if disabled via {@see disableCsrfCounterMeasure()}.
     * Unless passed as argument, requires a unique ID to be set via {@see setCsrfCounterMeasureId()}.
     *
     * @param ?string $uniqueId A unique ID that persists through different requests
     *
     * @return void
     */
    protected function addCsrfCounterMeasure(?string $uniqueId = null): void
    {
        if (! $this->csrfCounterMeasureEnabled) {
            return;
        }

        if ($uniqueId === null && $this->csrfCounterMeasureId === null) {
            throw new Error('No CSRF counter measure ID set');
        }

        $this->addElement($this->createCsrfCounterMeasure($uniqueId ?? $this->csrfCounterMeasureId));
    }

    /**
     * Get whether the request is safe from CSRF based on the Sec-Fetch-Site header
     *
     * Returns true if the header indicates a same-origin request or a user-originated operation (e.g. typing a URL),
     * both of which cannot be forged by a cross-site attacker. Returns false if the header indicates a cross-site
     * request. Returns null in two cases where a token-based fallback should be used instead: when the request uses an
     * RFC 9110 safe method (GET, HEAD, OPTIONS, TRACE), which cannot carry CSRF side effects by definition, or when
     * the header is absent, indicating a legacy browser that must be validated via the CSRF token.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc9110#section-9.2.1
     *
     * @return ?bool
     */
    protected function requestIsSafe(): ?bool
    {
        if (in_array($_SERVER['REQUEST_METHOD'] ?? null, ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true)) {
            return null;
        }

        return match ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? null) {
            'same-origin' => true, // same scheme, host and port
            'none'        => true, // a user-originated operation
            null          => null, // legacy browser without Sec-Fetch-Site support
            default       => false,
        };
    }
}
