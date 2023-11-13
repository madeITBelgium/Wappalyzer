# PHP Wappalyzer library

[![Build Status](https://travis-ci.org/madeITBelgium/Wappalyzer.svg?branch=master)](https://travis-ci.org/madeITBelgium/Wappalyzer)
[![Coverage Status](https://coveralls.io/repos/github/madeITBelgium/Wappalyzer/badge.svg?branch=master)](https://coveralls.io/github/madeITBelgium/Wappalyzer?branch=master)
[![Latest Stable Version](https://poser.pugx.org/madeITBelgium/Wappalyzer/v/stable.svg)](https://packagist.org/packages/madeITBelgium/Wappalyzer)
[![Latest Unstable Version](https://poser.pugx.org/madeITBelgium/Wappalyzer/v/unstable.svg)](https://packagist.org/packages/madeITBelgium/Wappalyzer)
[![Total Downloads](https://poser.pugx.org/madeITBelgium/Wappalyzer/d/total.svg)](https://packagist.org/packages/madeITBelgium/Wappalyzer)
[![License](https://poser.pugx.org/madeITBelgium/Wappalyzer/license.svg)](https://packagist.org/packages/madeITBelgium/Wappalyzer)


This library is a PHP version Fork of the Wappalyzer utility that uncovers the technologies used on websites. It detects content management systems, eCommerce platforms, web servers, JavaScript frameworks, analytics tools and many more.

Up-to-date until 2023-08-23 https://github.com/dochne/wappalyzer

# Installation

Require this package in your `composer.json` and update composer.

```php
"madeitbelgium/wappalyzer": "^2.0"
```

# Documentation
## Usage
```php
use MadeITBelgium\Wappalyzer\Wappalyzer;
$wappalyzer = new Wappalyzer();
$wappalyzer->analyze('http://www.example.com');
```

## Usage Laravel Facade
```php
use MadeITBelgium\Wappalyzer\WappalyzerFacade as Wappalyzer;

$analyze = Wappalyzer::analyze('http://www.example.com');
```

The complete documentation can be found at: [http://www.madeit.be/](http://www.madeit.be/)

# Upgrade from v1 to v2
The json file containing all the data is removed and replaced with multiple json files. Due to this change the config file isn't used any more.

# Support
Support github or mail: tjebbe.lievens@madeit.be

# Contributing
Please try to follow the psr-2 coding style guide. http://www.php-fig.org/psr/psr-2/

# License
This package is licensed under LGPL. You are free to use it in personal and commercial projects. The code can be forked and modified, but the original copyright author should always be included!
