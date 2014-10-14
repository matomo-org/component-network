<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Tests\Piwik\IP;

use Piwik\IP\IP;

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
}
