<?php

declare(strict_types=1);


namespace App\Consumer;

use joshtronic\LoremIpsum;
use League\Flysystem\FilesystemOperator;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use phpseclib3\Net\SSH2;

define('NET_SSH2_LOGGING', SSH2::LOG_REALTIME_FILE);
define('NET_SSH2_LOG_REALTIME_FILENAME', 'log.txt');

class TaskConsumer implements ConsumerInterface
{
    public function __construct(private readonly FilesystemOperator $defaultStorage)
    {
    }

    public function execute(AMQPMessage $msg)
    {
        $lipsum = new LoremIpsum();
        $filePath = bin2hex(random_bytes(32));

        try {
            $this->defaultStorage->write($filePath, $lipsum->paragraphs(1));
            $this->defaultStorage->fileExists($filePath);
            var_dump('SUCCESS');
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            return ConsumerInterface::MSG_REJECT;
        }


        return ConsumerInterface::MSG_ACK;
    }
}