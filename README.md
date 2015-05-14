# TacticianAMQPBundle
[![Build Status](https://travis-ci.org/boekkooi/tactician-amqp-bundle.svg?branch=master)](https://travis-ci.org/boekkooi/tactician-amqp-bundle)

Symfony2 Bundle extending for the [Tactician bundle](https://github.com/thephpleague/tactician-bundle) with AMQP powers.

## Setup 
First add this bundle to your composer dependencies:

`> composer require boekkooi/amqp-bundle`

Then register it in your AppKernel.php.

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new League\Tactician\Bundle\TacticianBundle(),
            new Boekkooi\Bundle\AMQP\BoekkooiAMQPBundle(),
            // ...
```

That's it!
 
## Configuring AMQP

Since you are using a AMQP server the bundle will need to know about these.

A simple example of what your `app/config.yml` for a simple setup is as followed.

```yaml
boekkooi_amqp:
  connections:
    default:
      host: localhost
      port: 5672
      # timeout: 10 # or
      timeout:
        read: 10
        write: 10
        connect: 10
      login: guest
      password: guest

  vhosts:
    '/':
      connection: default
      exchanges:
        'my_exchange':
          type: 'direct' #required ('direct', 'fanout', 'headers', 'topic') 
          passive: false
          durable: true
          arguments: []
      queues:
        'my_queue':
          passive: false
          durable: true
          exclusive: false
          auto_delete: false
          arguments: []
          binds: # required
            'my_exchange': 
              routing_key: 'my_command'
              arguments: []
```

## Configuring AMQP commands

To publish a command to the AMQP server we need to configure some extra metadata for the command.

This configuration is done in the `app/config.yml` and looks as followed.

```
boekkooi_amqp:
  # AMQP connection & vhost config
  # ...
  commands:
    'my\command': 
      vhost: '/' # required
      exchange: 'my_exchange' # required
      routing_key: 'my_command'
      mandatory: true
      immediate: false
      attributes: []
```

## Configuring Tactician

Everything inside Tactician is a middleware plugin. So to use AMQP you will just need to add the correct middleware.

To enable a command bus that can publish you can configure the middleware in your `app/config.yml` as followed.

```yaml
tactician:
    commandbus:
        amqp_publish:
            middleware:
                - boekkooi.amqp.middleware.command_transformer                
                - boekkooi.amqp.middleware.publish
                - tactician.middleware.command_handler
```

To configure a command bus to handle messages from a AMQP server when using `app/console amqp:handle` you can configure the middleware in your `app/config.yml` as followed.
