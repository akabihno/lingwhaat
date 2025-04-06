<?php

namespace App\MessageHandler;

use App\Service\RedisService;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Predis\Client;

class RabbitMqToRedisConsumer implements TopicSubscriberInterface
{
    public function __construct(protected RedisService $redis)
    {
    }

    public function __invoke(Message $message): void
    {
        $data = json_decode($message->getBody(), true);

        $this->redis->set('message:' . $data['id'], json_encode($data));
    }

    public static function getSubscribedTopics(): array
    {
        return ['redis_messages'];
    }


}