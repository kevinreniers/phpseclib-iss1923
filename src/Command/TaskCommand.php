<?php

declare(strict_types=1);


namespace App\Command;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:task')]
class TaskCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'old_sound_rabbit_mq.task_producer')]
        private readonly ProducerInterface $taskProducer,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->addArgument('tasks', InputArgument::OPTIONAL, '', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (range(1, $input->getArgument('tasks')) as $_) {
            $this->taskProducer->publish(serialize([]));
        }

        return Command::SUCCESS;
    }
}