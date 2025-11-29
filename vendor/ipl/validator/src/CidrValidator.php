<?php

namespace ipl\Validator;

use ipl\I18n\Translation;
use ipl\Stdlib\Str;

/**
 * Validate a classless inter-domain routing (CIDR)
 */
class CidrValidator extends BaseValidator
{
    use Translation;

    public function isValid($value): bool
    {
        $this->clearMessages();

        $pieces = Str::trimSplit($value, '/');
        if (count($pieces) !== 2) {
            $this->addMessage(sprintf(
                $this->translate('CIDR "%s" does not conform to the required format $address/$prefix'),
                $value
            ));

            return false;
        }

        list($address, $prefix) = $pieces;
        $inaddr = @inet_pton($address);
        if ($inaddr === false) {
            $this->addMessage(sprintf($this->translate('CIDR "%s" contains an invalid address'), $value));

            return false;
        }

        if (! is_numeric($prefix)) {
            $this->addMessage(sprintf($this->translate('Prefix of CIDR "%s" must be a number'), $value));

            return false;
        }

        $isIPv6 = isset($inaddr[4]);
        $prefix = (int) $prefix;
        $maxPrefixLength = $isIPv6 ? 128 : 32;

        if ($prefix < 0 || $prefix > $maxPrefixLength) {
            $this->addMessage(sprintf(
                $this->translate('Prefix length of CIDR "%s" must be between 0 and %d for IPv%d addresses'),
                $value,
                $maxPrefixLength,
                $isIPv6 ? 6 : 4
            ));

            return false;
        }

        return true;
    }
}
