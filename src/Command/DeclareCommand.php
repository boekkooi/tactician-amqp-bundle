<?php
namespace Boekkooi\Bundle\AMQP\Command;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Tools\SchemaTool;
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
     * @param OutputInterface $output
     */
    protected function setupVHost($vhost, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Setting up vhost %s</info>', $vhost));

        $output->writeln('Found exchanges:');
        $exchanges = $this->getVHostExchanges($vhost);
        foreach ($exchanges as $exchange) {
            $output->writeln(sprintf("- %s", $exchange->getName()));
        }

        $output->writeln('Found queues:');
        $queues = $this->getVHostQueues($vhost);
        foreach ($queues as $queue) {
            $output->writeln(sprintf("- %s", $queue->getName()));
        }

        // Do some actual work
        $output->writeln('Declaring exchanges & queues');
        $schemaTool = new SchemaTool($this->getVhostConnection($vhost));
        $schemaTool->declareDefinitions($exchanges, $queues);

        $output->writeln("<info>done</info>");
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
     * @param string $vhost
     * @return \Boekkooi\Bundle\AMQP\LazyConnection
     */
    private function getVhostConnection($vhost)
    {
        return $this->getContainer()->get(sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_CONNECTION_ID,
            $vhost
        ));
    }

    /**
     * @param string $vhost
     * @return \Boekkooi\Bundle\AMQP\LazyExchange[]
     */
    private function getVHostExchanges($vhost)
    {
        return $this->getServicesByParameterWithNames(
            sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_EXCHANGE_LIST, $vhost),
            BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID,
            [ $vhost ]
        );
    }

    /**
     * @param string $vhost
     * @return \Boekkooi\Bundle\AMQP\LazyQueue[]
     */
    private function getVHostQueues($vhost)
    {
        return $this->getServicesByParameterWithNames(
            sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_QUEUE_LIST, $vhost),
            BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
            [ $vhost ]
        );
    }

    private function getServicesByParameterWithNames($parameter, $serviceNameFormat, array $serviceNameArgs = [])
    {
        $container = $this->getContainer();
        $names = $container->getParameter($parameter);

        $services = [];
        foreach ($names as $name) {
            $services[] = $container->get(vsprintf(
                $serviceNameFormat,
                array_merge($serviceNameArgs, [ $name ])
            ));
        }

        return $services;
    }
}
