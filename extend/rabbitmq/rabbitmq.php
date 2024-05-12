<?php
namespace rabbitmq;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;
use think\facade\Cache;
use elasticsearch\Elastic;


class rabbitmq {
    private $connection;
    private $channel;

    public function __construct() {
        $params = config('rabbitmq.amqp');
        try {
            $this->connection = new AMQPStreamConnection($params['host'], $params['port'], $params['login'], $params['password']);
            $this->channel = $this->connection->channel();
        } catch (\Exception $e) {
            Log::write($e->getMessage());
        }
    }

    /**
     * 简单模式 生产者
     * @param $queue    string  队列名称
     * @param $message  string  发送的消息
     * @return void
     */
    public function simpleSend(string $queue, string $message) {
        $this->channel->queue_declare($queue, false, false, false, false);
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', $queue);
    }

    /**
     * 简单模式 消费者
     * @param string $queue string  队列名称
     * @return void
     */
    public function simpleReceiveArticle(string $queue) {
        $this->channel->queue_declare($queue, false, false, false, false);

        // 回调函数
        $callback = function ($msg) {
            // 消息内容
            $message = $msg->body;
            // 文章信息
            $articleInfo = json_decode($message, true);

            // 循环3次写入Redis（错误重试机制）
            $redisIsOk = false;
            for ($i = 0; $i < 3; $i++) {
                // 连接redis
                $redis = Cache::store('redis')->handler();

                // 写入Redis
                $redisKey = 'Article:'.$articleInfo['id'];
                // 创建布隆过滤器
                createBoolmFilter($redis, $redisKey);
                // json化文章信息存入redis
                $redisRes = $redis->set($redisKey, $msg->body);
                if ($redisRes) { // 写入成功跳出循环
                    $redisIsOk = true;
                    break;
                }
            }

            // 判断Redis是否成功写入
            // 如果未写入，那么记录日志，触发告警等
            if (!$redisIsOk) {
                Log::write(date("Y-m-d H:i:s")."数据写入Redis失败，数据内容: ".$msg->body);
                return;
            }

            $es = new Elastic('article');
            // 循环3次写入ES（错误重试机制）
            $id = $articleInfo['id'];
            unset($articleInfo['id']);
            $esIsOk = false;
            for ($i = 0; $i < 3; $i++) {
                $res = $es->createDoc($id, $articleInfo);
                if (!$res['_shards']['failed']) { // 写入成功跳出循环
                    $esIsOk = true;
                    break;
                }
            }
            if (!$esIsOk) {
                Log::write(date("Y-m-d H:i:s")."数据写入ES失败，数据内容: ".$msg->body);
            }
        };

        $this->channel->basic_consume($queue, '', false, true, false, false, $callback);

        try {
            $this->channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * 简单模式 消费者
     * @param string $queue string  队列名称
     * @return void
     */
    public function simpleReceiveUser(string $queue) {
        $this->channel->queue_declare($queue, false, false, false, false);

        // 回调函数
        $callback = function ($msg) {
            // 消息内容
            $message = $msg->body;
            // 文章信息
            $articleInfo = json_decode($message, true);

            // 循环3次写入Redis（错误重试机制）
            $redisIsOk = false;
            for ($i = 0; $i < 3; $i++) {
                // 连接redis
                $redis = Cache::store('redis')->handler();

                // 写入Redis
                $redisKey = 'User:'.$articleInfo['id'];
                // 创建布隆过滤器
                createBoolmFilter($redis, $redisKey);
                // json化文章信息存入redis
                $redisRes = $redis->set($redisKey, $msg->body);
                if ($redisRes) { // 写入成功跳出循环
                    $redisIsOk = true;
                    break;
                }
            }

            // 判断Redis是否成功写入
            // 如果未写入，那么记录日志，触发告警等
            if (!$redisIsOk) {
                Log::write(date("Y-m-d H:i:s")."数据写入Redis失败，数据内容: ".$msg->body);
                return;
            }

            $es = new Elastic('user');
            // 循环3次写入ES（错误重试机制）
            $id = $articleInfo['id'];
            unset($articleInfo['id']);
            $esIsOk = false;
            for ($i = 0; $i < 3; $i++) {
                $res = $es->createDoc($id, $articleInfo);
                if (!$res['_shards']['failed']) { // 写入成功跳出循环
                    $esIsOk = true;
                    break;
                }
            }
            if (!$esIsOk) {
                Log::write(date("Y-m-d H:i:s")."数据写入ES失败，数据内容: ".$msg->body);
            }
        };

        $this->channel->basic_consume($queue, '', false, true, false, false, $callback);

        try {
            $this->channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * 释放资源
     * @return void
     * @throws \Exception
     */
    public function close() {
        $this->channel->close();
        $this->connection->close();
    }
}