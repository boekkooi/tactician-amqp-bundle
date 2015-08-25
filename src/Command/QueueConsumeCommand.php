<?php
namespace Boekkooi\Bundle\AMQP\Command;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Tactician\AMQP\Command;
use League\Tactician\CommandBus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command class for consuming one or multiple amqp queues
 */
class QueueConsumeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('amqp:consume')
            ->setDescription('Handle messages in a AMQP queue')
            ->addOption('amount', 'a', InputOption::VALUE_OPTIONAL, 'The amount of messages to consume', 100)
            ->addArgument('vhost', InputArgument::REQUIRED, 'The vhost where the queue is located')
            ->addArgument('queues', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The names of the queues to consume')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $amount = $input->getOption('amount');
        if (strval(intval($amount)) !== strval($amount) || intval($amount) <= 0) {
            throw new \InvalidArgumentException('An option "amount" must be a positive integer.');
        }

        $limit = intval($amount);

        $commandBus = $this->getCommandBus();

        $consumer = $this->getConsumer($input->getArgument('vhost'));
        $consumer->consume(
            (array)$input->getArgument('queues'),
            function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($commandBus, &$limit) {
                $commandBus->handle(new Command($envelope, $queue));

                $limit--;
                return $limit > 0;
            }
        );
    }

    /**
     * @param string $vhost
     * @return \Boekkooi\Bundle\AMQP\Consumer\Consumer
     */
    private function getConsumer($vhost)
    {
        return $this->getContainer()->get(sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_CONSUMER_ID,
            $vhost
        ));
    }

    /**
     * @return CommandBus
     */
    private function getCommandBus()
    {
        return $this->getContainer()->get('boekkooi.amqp.consume_command_bus');
    }
}
