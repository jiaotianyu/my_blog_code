<?php
namespace lock;
use redis\RedisLock;

class Lock{
    public static function lockType($type) {
        if ($type == 'redis') {
            return new RedisLock();
        } else if( $type == 'mysql' ) {
            // TODO mysql Lock
        } else {
            return null;
        }
    }
}