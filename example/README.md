# TacticianAMQPBundle Example

This is a very basic example of the TacticianAMQPBundle usage.

## Requirements

Make sure you have *RabbitMQ* installed or edit `app/config/config.yml` to point to you RabbitMQ server.

No you only need to install the dev dependencies of the bundle:
```BASH
cd /my/path/to/TacticianAMQPBundle
composer install
```

*As of writing this the TacticianBundle (v0.2) requires `doctrine.orm.entity_manager` to you will need to edit `vendor/league/tactician-bundle/Resources/config/services/services.yml` and comment out the `tactician.middleware.doctrine` service*

## Running
To run the example you need to first declare the exchanges and queues using:
```BASH
php app/console amqp:declare
```

After this you can start the webserver (`php -S 0.0.0.0:8000 -t web/`) and go to the url `localhost:8000`.
At the same time you can run `php app/console amqp:consume main direct_queue` to start consuming the commands.
