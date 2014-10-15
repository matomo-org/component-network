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
// or in binary format:
$ip = IP::fromBinaryIP("\x7F\x00\x00\x01");

echo $ip->toString(); // 127.0.0.1
echo $ip->toBinary();

// IPv4 & IPv6
if ($ip->isIPv4()) {}
if ($ip->isIPv6()) {}

// Hostname reverse lookup
echo $ip->getHostname();

if ($ip->isInRange('192.168.1.1/32')) {}
```

## License

The IP component is released under the [LGPL v3.0](http://choosealicense.com/licenses/lgpl-3.0/).
