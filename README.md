# Piwik/IP

Component providing tools to work with IP addresses.

[![Build Status](https://travis-ci.org/piwik/component-decompress.svg?branch=master)](https://travis-ci.org/piwik/component-decompress)
[![Coverage Status](https://coveralls.io/repos/piwik/component-decompress/badge.png?branch=master)](https://coveralls.io/r/piwik/component-decompress?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/piwik/component-decompress/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/piwik/component-decompress/?branch=master)

## Installation

With Composer:

```json
{
    "require": {
        "piwik/ip": "*"
    }
}
```

## Usage

Creating an `IP` object:

```php
$ip = IP::fromStringIP('127.0.0.1');
// IPv6
$ip = IP::fromStringIP('::1');
// In binary format:
$ip = IP::fromBinaryIP("\x7F\x00\x00\x01");

echo $ip->toString(); // 127.0.0.1
echo $ip->toBinary();

// IPv4 & IPv6
if ($ip->isIPv4()) {}
if ($ip->isIPv6()) {}

// Hostname reverse lookup
echo $ip->getHostname();

if ($ip->isInRange('192.168.1.1/32')) {}
if ($ip->isInRange('192.168.*.*')) {}
```

The `IPUtils` provide utility methods:

```php
echo IPUtils::binaryToStringIP("\x7F\x00\x00\x01");
echo IPUtils::stringToBinaryIP('127.0.0.1');

// Sanitization methods
$sanitizedIp = IPUtils::sanitizeIp($_GET['ip']);
$sanitizedIpRange = IPUtils::sanitizeIpRange($_GET['ipRange']);

// IP range
$bounds = IPUtils::getIPRangeBounds('192.168.1.*');
echo $bounds[0]; // 192.168.1.0
echo $bounds[1]; // 192.168.1.255
```

## License

The IP component is released under the [LGPL v3.0](http://choosealicense.com/licenses/lgpl-3.0/).
