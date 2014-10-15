<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Tests\Piwik\IP;

use Piwik\IP\IP;
use Piwik\IP\IPUtils;

/**
 * @covers \Piwik\IP\IP
 */
class IPTest extends \PHPUnit_Framework_TestCase
{
    public function ipData()
    {
        return array(
            // IPv4
            array('0.0.0.0', "\x00\x00\x00\x00"),
            array('127.0.0.1', "\x7F\x00\x00\x01"),
            array('192.168.1.12', "\xc0\xa8\x01\x0c"),
            array('255.255.255.255', "\xff\xff\xff\xff"),

            // IPv6
            array('::', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array('::1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01"),
            array('::fffe:7f00:1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xfe\x7f\x00\x00\x01"),
            array('::ffff:127.0.0.1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x7f\x00\x00\x01"),
            array('2001:5c0:1000:b::90f8', "\x20\x01\x05\xc0\x10\x00\x00\x0b\x00\x00\x00\x00\x00\x00\x90\xf8"),
        );
    }

    /**
     * @dataProvider ipData
     */
    public function testFromStringIP($str, $binary)
    {
        $ip = IP::fromStringIP($str);

        $this->assertEquals($binary, $ip->toBinary());
        $this->assertEquals($str, $ip->toString());
        $this->assertEquals($str, (string) $ip);
    }

    /**
     * @dataProvider ipData
     */
    public function testFromBinaryIP($str, $binary)
    {
        $ip = IP::fromBinaryIP($binary);

        $this->assertEquals($binary, $ip->toBinary());
        $this->assertEquals($str, $ip->toString());
        $this->assertEquals($str, (string) $ip);
    }

    public function getIPv4Data()
    {
        // a valid network address is either 4 or 16 bytes; those lines are intentionally left blank ;)
        return array(
            // invalid
            array(null, false),
            array("", false),

            // IPv4
            array("\x00\x00\x00\x00", true),
            array("\x7f\x00\x00\x01", true),

            // IPv4-compatible (this transitional format is deprecated in RFC 4291, section 2.5.5.1)
            array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xc0\xa8\x01\x01", true),

            // IPv4-mapped (RFC 4291, 2.5.5.2)
            array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\xc0\xa8\x01\x02", true),

            // other IPv6 address
            array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\x00\xc0\xa8\x01\x03", false),
            array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\xc0\xa8\x01\x04", false),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xc0\xa8\x01\x05", false),

            /*
             * We assume all stored IP addresses (pre-Piwik 1.4) were converted from UNSIGNED INT to VARBINARY.
             * The following is just for informational purposes.
             */

            // 192.168.1.0
            array('-1062731520', false),
            array('3232235776', false),

            // 10.10.10.10
            array('168430090', false),

            // 0.0.39.15 - this is the ambiguous case (i.e., 4 char string)
            array('9999', true),
            array("\x39\x39\x39\x39", true),

            // 0.0.3.231
            array('999', false),
            array("\x39\x39\x39", false),
        );
    }

    /**
     * @dataProvider getIPv4Data
     */
    public function testIsIPv4($ip, $expected)
    {
        $ip = IP::fromBinaryIP($ip);

        $this->assertEquals($expected, $ip->isIPv4(), $ip->toString());
    }

    public function getMappedIPv4Data()
    {
        return array(
            array(null, false, '0.0.0.0'),
            array('', false, '0.0.0.0'),
            array('192.168.0.1', false, '192.168.0.1'),
            array('::ffff:192.168.0.1', true, '192.168.0.1'),
            array('2001:5c0:1000:b::90f8', false, null),
        );
    }

    /**
     * @dataProvider getMappedIPv4Data
     */
    public function testIsMappedIPv4($stringIp, $expected)
    {
        $ip = IP::fromStringIP($stringIp);

        $this->assertEquals($expected, $ip->isMappedIPv4(), $stringIp);
    }

    /**
     * @dataProvider getMappedIPv4Data
     */
    public function testToIPv4String($stringIp, $isMappedIPv4, $stringIPv4)
    {
        $ip = IP::fromStringIP($stringIp);

        $this->assertEquals($stringIPv4, $ip->toIPv4String(), $stringIp);
    }

    public function testGetHostnameIPv4()
    {
        $hosts = array('localhost', 'localhost.localdomain', strtolower(@php_uname('n')), '127.0.0.1');

        $ip = IP::fromStringIP('127.0.0.1');
        $this->assertContains($ip->getHostname(), $hosts, '127.0.0.1 -> localhost');
    }

    public function testGetHostnameIPv6()
    {
        $hosts = array('ip6-localhost', 'localhost', 'localhost.localdomain', strtolower(@php_uname('n')), '::1');

        $ip = IP::fromStringIP('::1');
        $this->assertContains($ip->getHostname(), $hosts, '::1 -> ip6-localhost');
    }

    public function testGetHostnameFailure()
    {
        $ip = IP::fromStringIP('0.1.2.3');
        $this->assertSame(null, $ip->getHostname());
    }

    public function getIpsInRangeData()
    {
        return array(
            array('192.168.1.10', array(
                '192.168.1.9'         => false,
                '192.168.1.10'        => true,
                '192.168.1.11'        => false,

                // IPv6 addresses (including IPv4 mapped) have to be compared against IPv6 address ranges
                '::ffff:192.168.1.10' => false,
            )),

            array('::ffff:192.168.1.10', array(
                '::ffff:192.168.1.9'                      => false,
                '::ffff:192.168.1.10'                     => true,
                '::ffff:c0a8:010a'                        => true,
                '0000:0000:0000:0000:0000:ffff:c0a8:010a' => true,
                '::ffff:192.168.1.11'                     => false,

                // conversely, IPv4 addresses have to be compared against IPv4 address ranges
                '192.168.1.10'                            => false,
            )),

            array('192.168.1.10/32', array(
                '192.168.1.9'  => false,
                '192.168.1.10' => true,
                '192.168.1.11' => false,
            )),

            array('192.168.1.10/31', array(
                '192.168.1.9'  => false,
                '192.168.1.10' => true,
                '192.168.1.11' => true,
                '192.168.1.12' => false,
            )),

            array('192.168.1.128/25', array(
                '192.168.1.127' => false,
                '192.168.1.128' => true,
                '192.168.1.255' => true,
                '192.168.2.0'   => false,
            )),

            array('192.168.1.10/24', array(
                '192.168.0.255' => false,
                '192.168.1.0'   => true,
                '192.168.1.1'   => true,
                '192.168.1.2'   => true,
                '192.168.1.3'   => true,
                '192.168.1.4'   => true,
                '192.168.1.7'   => true,
                '192.168.1.8'   => true,
                '192.168.1.15'  => true,
                '192.168.1.16'  => true,
                '192.168.1.31'  => true,
                '192.168.1.32'  => true,
                '192.168.1.63'  => true,
                '192.168.1.64'  => true,
                '192.168.1.127' => true,
                '192.168.1.128' => true,
                '192.168.1.255' => true,
                '192.168.2.0'   => false,
            )),

            array('192.168.1.*', array(
                '192.168.0.255' => false,
                '192.168.1.0'   => true,
                '192.168.1.1'   => true,
                '192.168.1.2'   => true,
                '192.168.1.3'   => true,
                '192.168.1.4'   => true,
                '192.168.1.7'   => true,
                '192.168.1.8'   => true,
                '192.168.1.15'  => true,
                '192.168.1.16'  => true,
                '192.168.1.31'  => true,
                '192.168.1.32'  => true,
                '192.168.1.63'  => true,
                '192.168.1.64'  => true,
                '192.168.1.127' => true,
                '192.168.1.128' => true,
                '192.168.1.255' => true,
                '192.168.2.0'   => false,
            )),
        );
    }

    /**
     * @dataProvider getIpsInRangeData
     */
    public function testIsInRange($range, $test)
    {
        foreach ($test as $stringIp => $expected) {
            $ip = IP::fromStringIP($stringIp);

            // range as a string
            $this->assertEquals($expected, $ip->isInRange($range), "$ip in $range");

            // range as an array(low, high)
            $arrayRange = IPUtils::getIPRangeBounds($range);
            $arrayRange[0] = IPUtils::binaryToStringIP($arrayRange[0]);
            $arrayRange[1] = IPUtils::binaryToStringIP($arrayRange[1]);
            $this->assertEquals($expected, $ip->isInRange($arrayRange), "$ip in $range");
        }
    }

    /**
     * @dataProvider getIpsInRangeData
     */
    public function testIsInRanges($range, $test)
    {
        foreach ($test as $stringIp => $expected) {
            $ip = IP::fromStringIP($stringIp);

            // range as a string
            $this->assertEquals($expected, $ip->isInRanges(array($range)), "$ip in $range");

            // range as an array(low, high)
            $arrayRange = IPUtils::getIPRangeBounds($range);
            $arrayRange[0] = IPUtils::binaryToStringIP($arrayRange[0]);
            $arrayRange[1] = IPUtils::binaryToStringIP($arrayRange[1]);
            $this->assertEquals($expected, $ip->isInRanges(array($arrayRange)), "$ip in $range");
        }
    }
}
