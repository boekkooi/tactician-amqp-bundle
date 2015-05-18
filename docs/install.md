## Setup
First add this bundle to your composer dependencies:

```BASH
composer require boekkooi/amqp-bundle`
```

Then register it in your AppKernel.php.

```PHP
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

Now we can continue to [configure the bundle](configure.md).
