<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\IP;

/**
 * IP address.
 */
class IP
{
    const MAPPED_IPv4_START = '::ffff:';

    /**
     * Binary representation of the IP.
     *
     * @var string
     */
    private $ip;

    /**
     * @see fromBinaryIP
     * @see fromStringIP
     *
     * @param string $ip Binary representation of the IP.
     */
    private function __construct($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @see fromStringIP
     *
     * @param string $ip IP address in a binary format.
     * @return IP
     */
    public static function fromBinaryIP($ip)
    {
        return new static($ip);
    }

    /**
     * @see fromBinaryIP
     *
     * @param string $ip IP address in a string format (X.X.X.X).
     * @return IP
     */
    public static function fromStringIP($ip)
    {
        return new static(IPUtils::stringToBinaryIP($ip));
    }

    /**
     * Returns the IP address in a binary format.
     *
     * @return string
     */
    public function toBinary()
    {
        return $this->ip;
    }

    /**
     * Returns the IP address in a string format (X.X.X.X).
     *
     * @return string
     */
    public function toString()
    {
        return IPUtils::binaryToStringIP($this->ip);
    }

    /**
     * Returns the IP address as an IPv4 string when possible.
     *
     * Some IPv6 can be transformed to IPv4 addresses, for example
     * IPv4-mapped IPv6 addresses: `::ffff:192.168.0.1` will return `192.168.0.1`.
     *
     * @return string|null IPv4 string address e.g. `'192.0.2.128'` or null if this is not an IPv4 address.
     */
    public function toIPv4String()
    {
        $str = $this->toString();

        if ($this->isMappedIPv4()) {
            return substr($str, strlen(self::MAPPED_IPv4_START));
        }

        if (! $this->isIPv4()) {
            return null;
        }

        return $str;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Returns true if this is an IPv4, IPv4-compat, or IPv4-mapped address, false otherwise.
     *
     * @return bool
     */
    public function isIPv4()
    {
        // in case mbstring overloads strlen function
        $strlen = function_exists('mb_orig_strlen') ? 'mb_orig_strlen' : 'strlen';

        // IPv4
        if ($strlen($this->ip) == 4) {
            return true;
        }

        // IPv6 - transitional address?
        if ($strlen($this->ip) == 16) {
            if (substr_compare($this->ip, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff", 0, 12) === 0
                || substr_compare($this->ip, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 0, 12) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if this is an IPv6 address, false otherwise.
     *
     * @return bool
     */
    public function isIPv6()
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Returns true if this is a IPv4 mapped address, false otherwise.
     *
     * @return bool
     */
    public function isMappedIPv4()
    {
        return substr($this->toString(), 0, strlen(self::MAPPED_IPv4_START)) === self::MAPPED_IPv4_START;
    }

    /**
     * Tries to return the hostname associated to the IP.
     *
     * @return string|null The hostname or null if the hostname can't be resolved.
     */
    public function getHostname()
    {
        $stringIp = $this->toString();

        $host = strtolower(@gethostbyaddr($stringIp));

        if ($host === '' || $host === $stringIp) {
            return null;
        }

        return $host;
    }

    /**
     * Determines if the IP address is in a specified IP address range.
     *
     * An IPv4-mapped address should be range checked with an IPv4-mapped address range.
     *
     * @param array|string $ipRange IP address range (string or array containing min and max IP addresses)
     * @return bool
     */
    public function isInRange($ipRange)
    {
        $ipLen = strlen($this->ip);
        if (empty($this->ip) || empty($ipRange) || ($ipLen != 4 && $ipLen != 16)) {
            return false;
        }

        if (is_array($ipRange)) {
            // already split into low/high IP addresses
            $ipRange[0] = IPUtils::stringToBinaryIP($ipRange[0]);
            $ipRange[1] = IPUtils::stringToBinaryIP($ipRange[1]);
        } else {
            // expect CIDR format but handle some variations
            $ipRange = IPUtils::getIPRangeBounds($ipRange);
        }
        if ($ipRange === false) {
            return false;
        }

        $low = $ipRange[0];
        $high = $ipRange[1];
        if (strlen($low) != $ipLen) {
            return false;
        }

        // binary-safe string comparison
        if ($this->ip >= $low && $this->ip <= $high) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the IP address is in a specified IP address range.
     *
     * An IPv4-mapped address should be range checked with IPv4-mapped address ranges.
     *
     * @param array $ipRanges List of IP address ranges (strings or arrays containing min and max IP addresses).
     * @return bool True if in any of the specified IP address ranges; false otherwise.
     */
    public function isInRanges(array $ipRanges)
    {
        $ipLen = strlen($this->ip);
        if (empty($this->ip) || empty($ipRanges) || ($ipLen != 4 && $ipLen != 16)) {
            return false;
        }

        foreach ($ipRanges as $ipRange) {
            if ($this->isInRange($ipRange)) {
                return true;
            }
        }

        return false;
    }
}
