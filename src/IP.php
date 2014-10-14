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
     * @param string $ip IP address in a binary format.
     *
     * @return IP
     */
    public static function fromBinaryIP($ip)
    {
        return new static($ip);
    }

    /**
     * @see fromBinaryIP
     * @param string $ip IP address in a string format (X.X.X.X).
     *
     * @return IP
     */
    public static function fromStringIP($ip)
    {
        return new static(IPUtils::P2N($ip));
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
        return IPUtils::N2P($this->ip);
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
}
