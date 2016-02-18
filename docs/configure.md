## Configuring AMQP

Since you are using a AMQP server the bundle will need to know about these.

A simple example of what your `app/config.yml` for a simple setup is as followed.

```YAML
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

```YAML
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

```YAML
tactician:
  commandbus:
    amqp_publish:
      middleware:
        - boekkooi.amqp.middleware.command_transformer
        - boekkooi.amqp.middleware.publish
        - tactician.middleware.command_handler
```

To configure a command bus to handle messages from a AMQP server when using `app/console amqp:handle` you can configure the middleware in your `app/config.yml` as followed.

```YAML
tactician:
  commandbus:
    amqp_consume:
      middleware:
        - boekkooi.amqp.middleware.consume
        - boekkooi.amqp.middleware.envelope_transformer
        - tactician.middleware.command_handler

boekkooi_amqp:
  command_bus: tactician.commandbus.amqp_consume
```

## Publish transactions

Due to the way AMQP handles transactions there is currently only one way (in memory) to handle transactions for publishing commands.
To use a commandbus with the transaction middleware we need to use a different middleware to publish and one to manage these transactions.

To use transactions we exchange the `boekkooi.amqp.middleware.publish` with the `boekkooi.amqp.middleware.transaction_publish` middleware and add the `boekkooi.amqp.middleware.transaction_transaction` to our middleware.
This will make the config look as followed.

```YAML
tactician:
  commandbus:
    amqp_publish:
      middleware:
        - boekkooi.amqp.middleware.transaction_transaction
        - boekkooi.amqp.middleware.command_transformer
        - boekkooi.amqp.middleware.transaction_publish
        - tactician.middleware.command_handler
```

To use multiple commandbuses with multiple transactions you can configure the as followed.

```YAML
boekkooi_amqp:
  # AMQP connection & vhost & command config
  # ...
  publisher:
    transaction:
      - <my_name>
```

Now you can use the `boekkooi.amqp.middleware.transaction.<my_name>_transaction` and `boekkooi.amqp.middleware.transaction.<my_name>_publish` middleware.
