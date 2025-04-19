<?php

namespace App\MessageHandler;

use App\Service\RedisService;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;

class RabbitMqToRedisConsumer implements Processor, TopicSubscriberInterface
{
    public function __construct(protected RedisService $redis)
    {
    }

    public function process(Message $message, Context $context): string
    {
        $data = json_decode($message->getBody(), true);
        $this->redis->set('message:' . $data['id'], json_encode($data));

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return ['redis_messages'];
    }



}