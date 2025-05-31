<?php

namespace ipl\Web\Common;

use Error;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormElement\HiddenElement;

trait CsrfCounterMeasure
{
    /**
     * Create a form element to countermeasure CSRF attacks
     *
     * @param string $uniqueId A unique ID that persists through different requests
     *
     * @return FormElement
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
}
