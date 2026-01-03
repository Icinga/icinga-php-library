<?php

namespace ipl\Validator;

use Exception;
use ipl\I18n\Translation;

/**
 * Validates an email address
 *
 * Email Address syntax: (<local part>@<domain-literal part>)
 *
 * We currently do not support dot-atom syntax (refer RFC 2822 [https://www.ietf.org/rfc/rfc2822.txt]
 * documentation for more details) for domain-literal part of an email address
 *
 */
class EmailAddressValidator extends BaseValidator
{
    use Translation;

    /**
     * If MX check should be enabled
     *
     * @var bool
     */
    protected $mx = false;

    /**
     * If a deep MX check should be enabled
     *
     * @var bool
     */
    protected $deep = false;

    /**
     * Create a new E-mail address validator with optional options
     *
     * Optional options:
     *
     * 'mx'   => If an MX check should be enabled, boolean
     * 'deep' => If a deep MX check should be enabled, boolean
     *
     * @param array{max?: bool, deep?: bool} $options
     *
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        if (array_key_exists('mx', $options)) {
            $this->setEnableMxCheck($options['mx']);
        }

        if (array_key_exists('deep', $options)) {
            $this->setEnableDeepMxCheck($options['deep']);
        }
    }

    /**
     * Set MX check
     *
     * To validate if the hostname is a DNS mail exchange (MX) record set it to true
     *
     * @param bool $mx if MX check should be enabled
     *
     * @return $this
     */
    public function setEnableMxCheck(bool $mx = true): self
    {
        $this->mx = $mx;

        return $this;
    }

    /**
     * Set Deep MX check
     *
     * To validate if the hostname is a DNS mail exchange (MX) record, and it points to an A record (for IPv4) or
     * an AAAA / A6 record (for IPv6) set it to true
     *
     * @param bool $deep if deep MX check should be enabled
     *
     * @return $this
     *
     * @throws Exception in case MX check has not been enabled
     */
    public function setEnableDeepMxCheck(bool $deep = true): self
    {
        if (! $this->mx) {
            throw new Exception("MX record check has to be enabled to enable deep MX record check");
        }

        $this->deep = $deep;

        return $this;
    }

    /**
     * Validate the local part (username / the part before '@') of the email address
     *
     * @param string $localPart
     * @param string $email
     *
     * @return bool
     */
    private function validateLocalPart(string $localPart, string $email): bool
    {
        // First try to match the local part on the common dot-atom format
        $result = false;

        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        if (preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $localPart)) {
            $result = true;
        } else {
            // Try quoted string format (RFC 5321 Chapter 4.1.2)

            // Quoted-string characters are: DQUOTE *(qtext/quoted-pair) DQUOTE
            $qtext = '\x20-\x21\x23-\x5b\x5d-\x7e'; // %d32-33 / %d35-91 / %d93-126
            $quotedPair = '\x20-\x7e'; // %d92 %d32-126
            if (preg_match('/^"([' . $qtext . ']|\x5c[' . $quotedPair . '])*"$/', $localPart)) {
                $result = true;
            } else {
                $this->addMessage(sprintf(
                    $this->translate(
                        "'%s' can not be matched against dot-atom format or quoted-string format"
                    ),
                    $localPart
                ));
                $this->addMessage(sprintf(
                    $this->translate("Hence '%s' is not a valid local part for email address '%s'"),
                    $localPart,
                    $email
                ));
            }
        }

        return $result;
    }

    /**
     * Validate the hostname part of the email address
     *
     * @param string $hostname
     * @param string $email
     *
     * @return bool
     */
    private function validateHostnamePart(string $hostname, string $email): bool
    {
        $hostValidator = new HostnameValidator();

        if ($this->validateIp($hostname)) {
            return true;
        }

        if (preg_match('/^\[([^\]]*)\]$/i', $hostname, $matches)) {
            $validHostname = $matches[1];
            if (! $this->validateIp($validHostname)) {
                $this->addMessage(sprintf(
                    $this->translate("host name %s is a domain literal and is invalid"),
                    $hostname
                ));

                return false;
            }

            return true;
        }

        if (! $hostValidator->isValid($hostname)) {
            $this->addMessage(sprintf(
                $this->translate('%s is not a valid domain name for email address %s.'),
                $hostname,
                $email
            ));

            return false;
        } elseif ($this->mx) {
            // MX check on hostname
            return $this->validateMXRecords($hostname, $email);
        }

        return true;
    }

    /**
     * Check if the given IP address is valid
     *
     * @param string $value
     *
     * @return bool
     */
    private function validateIp(string $value): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->clearMessages();

        $matches = [];
        $length = true;

        // Split email address up and disallow '..'
        if (
            (strpos($value, '..') !== false)
            || (! preg_match('/^(.+)@([^@]+)$/', $value, $matches))
        ) {
            $this->addMessage(sprintf(
                $this->translate("'%s' is not a valid email address in the basic format local-part@hostname"),
                $value
            ));
            return false;
        }

        $localPart = $matches[1];
        $hostname = $matches[2];

        if ((strlen($localPart) > 64) || (strlen($hostname) > 255)) {
            $length = false;
            $this->addMessage(sprintf(
                $this->translate("'%s' exceeds the allowed length"),
                $value
            ));
        }

        $local = $this->validateLocalPart($localPart, $value);

        // If both parts valid, return true
        if (($local && $this->validateHostnamePart($hostname, $value)) && $length) {
            return true;
        }

        return false;
    }

    /**
     * Perform deep MX record validation
     *
     * Check if the hostname is a valid DNS mail exchange (MX) record in case deep MX record check is enabled,
     * also checks if the corresponding MX record points to an A record (for IPv4) or an AAAA / A6 record (for IPv6)
     *
     * @param string $hostname
     * @param string $email
     *
     * @return bool
     */
    private function validateMXRecords(string $hostname, string $email): bool
    {
        $mxHosts = [];
        //decode IDN domain name
        $decodedHostname = idn_to_ascii($hostname, 0, INTL_IDNA_VARIANT_UTS46);

        $result = $decodedHostname && getmxrr($decodedHostname, $mxHosts);

        if (! $result) {
            $this->addMessage(sprintf(
                $this->translate("'%s' does not appear to have a valid MX record for the email address '%s'"),
                $hostname,
                $email
            ));
        } elseif ($this->deep) {
            $validAddress = false;
            $reserved     = true;
            foreach ($mxHosts as $decodedHostname) {
                $res = $this->isReserved($decodedHostname);
                if (! $res) {
                    $reserved = false;
                }

                if (
                    ! $res
                    && (
                        checkdnsrr($decodedHostname, "A")
                        || checkdnsrr($decodedHostname, "AAAA")
                        || checkdnsrr($decodedHostname, "A6")
                    )
                ) {
                    $validAddress = true;
                    break;
                }
            }

            if (! $validAddress) {
                $result = false;
                if ($reserved) {
                    $this->addMessage(sprintf(
                        $this->translate(
                            "'%s' is not in a routable network segment." .
                            " The email address '%s' should not be resolved from public network"
                        ),
                        $hostname,
                        $email
                    ));
                } else {
                    $this->addMessage(sprintf(
                        $this->translate("'%s' does not appear to have a valid MX record for the email address '%s'"),
                        $hostname,
                        $email
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * Validate whether the given host is reserved
     *
     * @param string $host host name or ip address
     *
     * @return bool
     */
    private function isReserved(string $host): bool
    {
        if (! preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $host)) {
            $host = gethostbyname($host);
        }

        if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE)) {
            return true;
        }

        return false;
    }
}
