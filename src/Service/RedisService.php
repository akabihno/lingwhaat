<?php

namespace App\Service;

use Predis\Client;

class RedisService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
            'password' => 'qwerty' //TODO
        ]);
    }

    public function set(string $key, mixed $value, int $ttl = null): void
    {
        if ($ttl) {
            $this->client->setex($key, $ttl, json_encode($value));
        } else {
            $this->client->set($key, json_encode($value));
        }
    }

    public function get(string $key): mixed
    {
        $value = $this->client->get($key);
        return $value ? json_decode($value, true) : null;
    }

    public function delete(string $key): void
    {
        $this->client->del([$key]);
    }

    public function exists(string $key): bool
    {
        return $this->client->exists($key) > 0;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

}