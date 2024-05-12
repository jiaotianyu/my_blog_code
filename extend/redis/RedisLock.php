<?php
namespace redis;

use think\facade\Cache;


class RedisLock {
    private $redis;
    private $setUnlockLua = <<<LUA
if (redis.call('get', KEYS[1]) == ARGV[1]) then
    return redis.call('del', KEYS[1])
else 
    return 0
end
LUA;
    private $luaHashSetLock = <<<LUA
if (redis.call('exists', KEYS[1]) == 0) or (redis.call('hexists', KEYS[1], ARGV[1]) == 1) then
    redis.call('hincrby', KEYS[1], ARGV[1], 1)
    redis.call('expire', KEYS[1], ARGV[2])
    return 1
else 
    return 0
end
LUA;
    private $luaHashSetUnlock = <<<LUA
if (redis.call('hexists', KEYS[1], ARGV[1]) == 0) then
    return -1
elseif (redis.call('hincrby', KEYS[1], ARGV[1], -1) == 0) then
    return redis.call('del', KEYS[1])
else 
    return 0
end
LUA;


    public function __construct() {
       $this->redis = Cache::store('redis')->handler();
    }

    /**
     * 使用字符串加分布式锁
     * @param $key  string  key名
     * @param $value mixed  值
     * @param $expiration   int  过期时间默认10秒
     * @return bool
     */
    public function setLock(string $key, $value, int $expiration = 10): bool
    {
        while(!$this->redis->set($key, $value, ['nx', 'ex' => $expiration])) {
            usleep(200000);
        }
        return true;
    }

    /**
     * 使用字符串分布式解锁（只有自己可以解锁）
     * @param $key  string  key名
     * @param $value    mixed   值
     * @return bool
     */
    public function setUnlock($key, $value): bool
    {
        $this->redis->eval($this->setUnlockLua, [$key, $value], 1);
        return true;
    }

    /**
     * 哈希方式加锁，可重入锁
     * @param string $lockKey  锁名称
     * @param string $key   自己唯一标识
     * @param int $expiration   过期时间（默认10秒）
     * @return bool
     */
    public function hashSetLock(string $lockKey, string $key, int $expiration = 10): bool
    {
//        echo $this->luaHashSetLock;die;
        while(!$this->redis->eval($this->luaHashSetLock, [$lockKey, $key, $expiration], 1)) {
            usleep(200000);
        }
        return true;
    }

    /**
     * 哈希加锁方式解锁
     * @param string $lockKey  锁名称
     * @param string $key   自己的唯一标识
     * @return int
     */
    public function hashSetUnlock(string $lockKey, string $key): int
    {
        return $this->redis->eval($this->luaHashSetUnlock, [$lockKey, $key], 1);
    }

}