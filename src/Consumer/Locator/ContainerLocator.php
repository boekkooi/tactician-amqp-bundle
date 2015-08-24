<?php
namespace Boekkooi\Bundle\AMQP\Consumer\Locator;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerLocator implements QueueLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $vhost;

    public function __construct(ContainerInterface $container, $vhost)
    {
        $this->container = $container;
        $this->vhost = $vhost;
    }

    /**
     * @inheritdoc
     */
    public function getQueueByName($queue)
    {
        $serviceId = $this->getQueueServiceId($this->vhost, $queue);
        if (!$this->container->has($serviceId)) {
            throw MissingQueueException::noService($serviceId);
        }

        return $this->container->get($serviceId);
    }


    /**
     * @param string $vhost
     * @param string $queue
     * @return string
     */
    protected function getQueueServiceId($vhost, $queue)
    {
        return sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
            $vhost,
            $queue
        );
    }
}
