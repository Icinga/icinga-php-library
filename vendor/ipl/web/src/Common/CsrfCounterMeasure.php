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
     * @param string $uniqueId A unique ID that persists through different requests
     *
     * @return FormElement
     *
     * @deprecated Use {@see addCsrfCounterMeasure()} instead
     */
    protected function createCsrfCounterMeasure($uniqueId)
    {
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
}
