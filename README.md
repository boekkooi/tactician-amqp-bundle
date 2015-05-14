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

## Configuring Tactician
Everything inside Tactician is a middleware plugin. Without any middleware configured, nothing will happen when you pass a command to `handle()`.

By default, the only Middleware enabled is the Command Handler support. You can override this and add your own middleware in the `app/config.yml`.

```yaml
tactician:
    commandbus:
        default:
            middleware:
                # service ids for all your middlewares, top down. First in, first out.
                - tactician.middleware.locking
                - boekkooi.amqp.middleware.command_transformer                
                - boekkooi.amqp.middleware.publish
                - boekkooi.amqp.middleware.consume
                - boekkooi.amqp.middleware.envelope_transformer
                - tactician.middleware.command_handler
```

```yaml
boekkooi_amqp:
  connections:
    host: localhost
    port: 5672
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
          type: AMQP_EX_TYPE_DIRECT, AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_HEADERS or AMQP_EX_TYPE_TOPIC
          passive: false
          durable: true          
          # flags: AMQP_DURABLE, AMQP_PASSIVE
          arguments:
            'key': 'value'
      queues:
        'my_queue':
          passive: false
          durable: true
          exclusive: false
          auto_delete: false
          # flags: AMQP_DURABLE, AMQP_PASSIVE,AMQP_EXCLUSIVE, AMQP_AUTODELETE.
          binds: 
            'my_exchange': 
              routing_key: [] | ''
              arguments: []
          
  commands:
    'my\command': 
      vhost: ''
      exchange: ''
      routing_key: ''
      mandatory: true
      immediate: false
      # flags: AMQP_MANDATORY, AMQP_IMMEDIATE.
      attributes: ''

  command_bus: tactician.commandbus # or tactician.commandbus.my_amqp_bus
  command_transformer: boekkooi.amqp.tactician.transformer
  command_serializer: boekkooi.amqp.tactician.serializer
  command_serializer_format: 'json'
```
