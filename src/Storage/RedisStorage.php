<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-06-17 20:14
 */
declare(strict_types=1);

namespace Pudongping\HyperfThrottleRequests\Storage;

use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisStorage implements StorageInterface
{

    /**
     * @var Redis
     */
    protected Redis $redis;

    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(RedisFactory::class)->get('default');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);

        if (false === $value) {
            return $default;
        }

        return $value;
    }

    public function put(string $key, string $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->redis->setex($key, max(1, $ttl), $value);
        }

        return $this->redis->set($key, $value);
    }

    public function add(string $key, string $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->redis->set($key, $value, ['NX', 'EX' => $ttl]);
        }

        return $this->redis->set($key, $value, ['NX']);
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($key, $value);
    }

    public function forget(string $key): bool
    {
        return (bool)$this->redis->del($key);
    }

    public function has(string $key): bool
    {
        return (bool)$this->redis->exists($key);
    }

    public function clearPrefix(string $prefix): bool
    {
        $iterator = null;
        $key = $prefix . '*';

        while ($iterator !== 0) {
            $keys = $this->redis->scan($iterator, $key, 10000);
            if ($keys) {
                $this->redis->del(...$keys);
            }
        }

        return true;
    }

}