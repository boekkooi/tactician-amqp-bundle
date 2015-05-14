<?php
namespace Boekkooi\Bundle\AMQP\Command;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Tactician\AMQP\AMQPCommand;
use League\Tactician\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A command class for consuming one or multiple amqp queues
 */
class HandleCommand extends Command
{
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(CommandBus $commandBus, ContainerInterface $container)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->setName('amqp:handle')
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

        $queues = $this->getQueues($input->getArgument('vhost'), (array)$input->getArgument('queues'));

        $commandBus = $this->commandBus;
        $consumeQueue = array_pop($queues);

        # In case of multiple queues we bind without any arguments
        # This allows multiple to be consumed by one callback
        foreach ($queues as $queue) {
            $queue->consume();
        }

        # The last queue is bound to the callback
        $consumeQueue->consume(
            function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($commandBus, &$limit) {
                $commandBus->handle(new AMQPCommand($envelope, $queue));

                $limit--;
                return $limit > 0;
            }
        );
    }

    /**
     * @param string $vhost
     * @param array $names
     * @return \AMQPQueue[]
     */
    private function getQueues($vhost, array $names)
    {
        $queues = [];
        foreach ($names as $name) {
            $queues[] = $this->container->get(sprintf(
                BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
                $vhost,
                $name
            ));
        }

        return $queues;
    }
}
