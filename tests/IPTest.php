<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Piwik\Network;

use Piwik\Network\IP;
use Piwik\Network\IPUtils;

/**
 * @covers \Piwik\Network\IP
 */
class IPTest extends \PHPUnit_Framework_TestCase
{
    public function ipData()
    {
        return array(
            // IPv4
            array('0.0.0.0', "\x00\x00\x00\x00", 'IPv4'),
            array('127.0.0.1', "\x7F\x00\x00\x01", 'IPv4'),
            array('192.168.1.12', "\xc0\xa8\x01\x0c", 'IPv4'),
            array('255.255.255.255', "\xff\xff\xff\xff", 'IPv4'),

            // IPv6
            array('::', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 'IPv6'),
            array('::1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", 'IPv6'),
            array('::fffe:7f00:1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xfe\x7f\x00\x00\x01", 'IPv6'),
            array('::ffff:127.0.0.1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x7f\x00\x00\x01", 'IPv6'),
            array('2001:5c0:1000:b::90f8', "\x20\x01\x05\xc0\x10\x00\x00\x0b\x00\x00\x00\x00\x00\x00\x90\xf8", 'IPv6'),
        );
    }

    /**
     * @dataProvider ipData
     */
    public function testFromBinaryIP($str, $binary, $class)
    {
        $ip = IP::fromBinaryIP($binary);

        $this->assertInstanceOf('Piwik\Network\\' . $class, $ip);

        $this->assertEquals($binary, $ip->toBinary());
        $this->assertEquals($str, $ip->toString());
        $this->assertEquals($str, (string) $ip);
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

    public function testGetHostnameIPv4()
    {
        $hosts = array('localhost', 'localhost.localdomain', strtolower(@php_uname('n')), '127.0.0.1');

        $ip = IP::fromStringIP('127.0.0.1');
        $this->assertContains($ip->getHostname(), $hosts, '127.0.0.1 -> localhost');
    }

    public function testGetHostnameIPv6()
    {
        $hosts = array('ip6-localhost', 'localhost', 'localhost.localdomain', strtolower(@php_uname('n')), '::1');

        if(self::isTravisCI()) {
            // Reverse lookup  does not work on Travis for ::1 ipv6 address
            $hosts[] = null;
        }

        $ip = IP::fromStringIP('::1');
        $this->assertContains($ip->getHostname(), $hosts, '::1 -> ip6-localhost');
    }

    /**
     * Returns true if continuous integration running this request
     * Useful to exclude tests which may fail only on this setup
     */
    public static function isTravisCI()
    {
        $travis = getenv('TRAVIS');
        return !empty($travis);
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

    public function testIsInRangeWithInvalidRange()
    {
        $ip = IP::fromStringIP('127.0.0.1');

        $this->assertFalse($ip->isInRange('foo-bar'));
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
