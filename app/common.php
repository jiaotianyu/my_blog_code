<?php

// 应用公共文件
define("OFFSET", pow(2, 25));
const SYSTEM_ERROR = 90060;
const PARAM_ERROR = 90061;
const SUCCESS = 200;
const NOT_FIND = 90404;
const VERIFY_ERROR = 90602;
const PASSWORD_ERROR = 90603;
const ADMIN_LOGIN_VERIFY = "user_login_verify";

/**
 * 创建布隆过滤器
 * @param $redis
 * @param $key
 * @return bool
 */
function createBoolmFilter(&$redis, $key) {
    $key = crc32($key) % OFFSET;
    $redis->setBit('redisBloomFilter', $key, 1);

    $key = crc32(sha1($key)) % OFFSET;
    $redis->setBit('redisBloomFilter', $key, 1);

    $key = crc32(md5($key)) % OFFSET;
    $redis->setBit('redisBloomFilter', $key, 1);
    return true;
}

/**
 * 获取布隆过滤器
 * @param $redis
 * @param $key
 * @return bool  true 为存在， false，为不存在
 */
function getBoolmFilter(&$redis, $key) {
    $data = 0;
    $key = crc32($key) % OFFSET;
    $res = $redis->getbit('redisBloomFilter', $key);
    if ($res) {
        $data++;
    }
    $key = crc32(sha1($key)) % OFFSET;
    $res = $redis->getbit('redisBloomFilter', $key);
    if ($res) {
        $data++;
    }
    $key = crc32(md5($key)) % OFFSET;
    $res = $redis->getbit('redisBloomFilter', $key);
    if ($res) {
        $data++;
    }
    if ($data == 3) {
        // 可能存在
        return true;
    } else {
        return false;
    }

}

/*生成唯一标志
*标准的UUID格式为：xxxxxxxx-xxxx-xxxx-xxxxxx-xxxxxxxxxx(8-4-4-4-12)
*/

function  uuid()
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid = substr ( $chars, 0, 8 ) . '-'
        . substr ( $chars, 8, 4 ) . '-'
        . substr ( $chars, 12, 4 ) . '-'
        . substr ( $chars, 16, 4 ) . '-'
        . substr ( $chars, 20, 12 );
    return $uuid ;
}

/**
 * 返回数据格式化
 * @param $code     integer     状态码
 * @param $data     array       返回数据
 * @param $message  string      信息描述
 * @return array
 */
function dataReturn(int $code=200, array $data=[], string $message='') {
    return ['code' => $code, 'data' => $data, 'message' => $message];
}
