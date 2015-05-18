# TacticianAMQPBundle
[![Latest Version](https://img.shields.io/github/release/boekkooi/tactician-amqp-bundle.svg?style=flat-square)](https://github.com/boekkooi/tactician-amqp-bundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/boekkooi/tactician-amqp-bundle/master.svg?style=flat-square)](https://travis-ci.org/boekkooi/tactician-amqp-bundle)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/boekkooi/tactician-amqp-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/boekkooi/tactician-amqp-bundle/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/boekkooi/tactician-amqp-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/boekkooi/tactician-amqp-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/boekkooi/amqp-bundle.svg?style=flat-square)](https://packagist.org/packages/boekkooi/amqp-bundle)

Symfony2 Bundle extending for the [Tactician bundle](https://github.com/thephpleague/tactician-bundle) with AMQP powers.

*This bundle is not yet stable to use at your own digression but please report any problem*

See the [docs](docs/install.md) or the [example](example/README.md) directory to get started.

## Testing
To run all unit tests, use the locally installed PHPUnit:

```BASH
./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Special thanks
Special thanks go to:
- [Ross Tuck](http://rosstuck.com/) for creating [Tactician](https://github.com/thephpleague/tactician)
- All the contributers to the  [php-amqp](https://github.com/pdezwart/php-amqp) pecl extension

## TODO
- Support for none durable exchange and queues
- Add more documentation and examples
- Kick @rosstuck because he made `doctrine.orm.entity_manager` required for the [TacticianBundle](https://github.com/thephpleague/tactician-bundle)
- Thank @rosstuck for is great work on tactician and buy him a drink or 2

