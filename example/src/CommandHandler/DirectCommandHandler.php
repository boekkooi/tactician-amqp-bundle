<?php
namespace Example\CommandHandler;

use Example\Command\DirectCommand;

class DirectCommandHandler
{
    public function handle(DirectCommand $command)
    {
        // Do your core application logic here. Don't actually echo stuff. :)
        echo "Consumed the soul of {$command->getName()}\n";
    }
}
