<?php
namespace Boekkooi\Bundle\AMQP\Command;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command class for declaring durable exchanges and queues
 */
class DeclareCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('amqp:declare')
            ->setDescription('Declare the exchanges and queues defined in the application configuration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vhosts = $this->getVHostNames();
        foreach ($vhosts as $vhost) {
            $this->setupVHost($vhost, $output);
        }
    }

    /**
     * @param $vhost
     */
    protected function setupVHost($vhost, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Setting up vhost %s</info>', $vhost));

        $output->writeln('Declaring durable exchanges:');
        $exchanges = $this->getVHostExchanges($vhost);
        foreach ($exchanges as $exchange) {
            if (($exchange->getFlags() & AMQP_DURABLE) !== AMQP_DURABLE) {
                $output->writeln(sprintf("<comment>- %s: skipped</comment>", $exchange->getName()));
                continue;
            }

            $output->write(sprintf("- %s ...\r", $exchange->getName()));
            $exchange->declareExchange();
            $output->writeln(sprintf("- %s: <info>done</info>", $exchange->getName()));
        }

        $output->writeln('Declaring durable queues:');
        $queues = $this->getVHostQueues($vhost);
        foreach ($queues as $queue) {
            if (($queue->getFlags() & AMQP_DURABLE) !== AMQP_DURABLE) {
                $output->writeln(sprintf("<comment>- %s: skipped</comment>", $queue->getName()));
                return;
            }

            $output->write(sprintf("- %s ...\r", $queue->getName()));
            $this->setupQueue($queue, $vhost);
            $output->writeln(sprintf("- %s: <info>done</info>", $queue->getName()));
        }
    }

    private function setupQueue(\AMQPQueue $queue, $vhost)
    {
        $queue->declareQueue();

        $container = $this->getContainer();
        $queueBinds = $container->getParameter(
            sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_QUEUE_BINDS, $vhost, $queue->getName())
        );
        foreach ($queueBinds as $bindExchange => $bindParams) {
            $queue->bind($bindExchange, $bindParams['routing_key'], $bindParams['arguments']);
        }
    }

    /**
     * @return string[]
     */
    private function getVHostNames()
    {
        return $this->getContainer()
            ->getParameter(BoekkooiAMQPExtension::PARAMETER_VHOST_LIST);
    }

    /**
     * @return \AMQPExchange[]
     */
    private function getVHostExchanges($vhost)
    {
        $container = $this->getContainer();
        $names = $container->getParameter(sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_EXCHANGE_LIST, $vhost));

        $exchanges = [];
        foreach ($names as $name) {
            $exchanges[] = $container->get(sprintf(
                BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID,
                $vhost,
                $name
            ));
        }

        return $exchanges;
    }

    /**
     * @return \AMQPQueue[]
     */
    private function getVHostQueues($vhost)
    {
        $container = $this->getContainer();
        $names = $container->getParameter(sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_QUEUE_LIST, $vhost));

        $queues = [];
        foreach ($names as $name) {
            $queues[] = $container->get(sprintf(
                BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
                $vhost,
                $name
            ));
        }

        return $queues;
    }
}
