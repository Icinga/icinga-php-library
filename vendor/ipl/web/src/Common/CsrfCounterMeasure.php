<?php

namespace ipl\Web\Common;

use ipl\Html\Contract\FormElement;
use ipl\Html\Form;

trait CsrfCounterMeasure
{
    /**
     * Create a form element to counter measure CSRF attacks
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

        /** @var Form $this */
        return $this->createElement(
            'hidden',
            'CSRFToken',
            [
                'ignore'        => true,
                'required'      => true,
                'value'         => $token,
                'validators'    => ['Callback' => function ($token) use ($uniqueId, $hashAlgo) {
                    if (strpos($token, '|') === false) {
                        die('Invalid CSRF token provided');
                    }

                    list($seed, $hash) = explode('|', $token);

                    if ($hash !== hash($hashAlgo, $uniqueId . base64_decode($seed))) {
                        die('Invalid CSRF token provided');
                    }

                    return true;
                }]
            ]
        );
    }
}
