<?php
namespace Example\CommandHandler;

use Example\Command\RpcCommand;

class RpcCommandHandler
{
    public function handle(RpcCommand $command)
    {
        echo "Starting soul consumption\n";

        // Do your core application logic here.
        return "Consumed the soul of {$command->getName()}\n";
    }
}
