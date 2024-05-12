<?php
namespace app\service;


use app\model\User;
use app\validate\UserValidate;
use rabbitmq\rabbitmq;
use think\captcha\facade\Captcha;
use think\exception\ValidateException;

class UserService {

    /**
     * 输出验证码
     * @return array
     */
    public function getCaptchaService(): array
    {
        $rs = Captcha::create();
        $base64_image = "data:image/png;base64," . base64_encode($rs->getData());
        $data['image'] = $base64_image;
        return dataReturn(SUCCESS, $data, '验证码');
    }

    /**
     * 登录接口
     * @param $code         string  验证码
     * @param $username     string  用户名
     * @param $password     string  密码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginService(string $code, string $username, string $password): array
    {
        try {
            // 验证数据是否符合要求
            validate(UserValidate::class)->check([
                'username' => $username,
                'code' => $code,
                'password' => $password
            ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return dataReturn(PARAM_ERROR, [], $e->getMessage());
        }

        // 验证码
        if (!Captcha::check($code)) {
            return dataReturn(VERIFY_ERROR, [], '验证码错误');
        }

        $userModel = new User();
        // 获取用户信息
        $uinfo = $userModel->field('id,password,username')->where('username', $username)->find()->toArray();
        // 验证密码
        if (!password_verify($password, $uinfo['password'])) {
            return dataReturn(PASSWORD_ERROR, [],'密码错误');
        }
        return dataReturn(SUCCESS, [],'登录成功');
    }

    /**
     * 注册用户
     * @param $code         string  验证码
     * @param $username     string  用户名
     * @param $password     string  密码
     * @param $email        string  邮箱
     * @param $repassword   string  重复密码
     * @return array
     * @throws \Exception
     */
    public function registerService($code, $username, $password, $email, $repassword): array
    {
        try {
            // 验证数据是否符合要求
            validate(UserValidate::class)->check([
                'username' => $username,
                'code' => $code,
                'password' => $password,
                'email' => $email,
            ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return dataReturn(PARAM_ERROR, [], $e->getMessage());
        }

        // 验证码
        if (!Captcha::check($code)) {
            return dataReturn(VERIFY_ERROR, [], '验证码错误');
        }

        // 验证密码是否一致
        if ($password != $repassword) {
            return dataReturn(PASSWORD_ERROR, [], '两次密码不一致');
        }

        $userModel = new User();
        $data = array(
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'email' => $email
        );
        $userModel->save($data);
        unset($data['password']);
        $data['id'] = $userModel->id;

        // 用户创建成功，异步写入redis、ES
        $amqp = new rabbitmq();
        $amqp->simpleSend('User', json_encode($data));
        $amqp->close();


        return dataReturn(SUCCESS, $data, '创建成功');
    }



    /**
     * RabbitMQ User消费者，写入Redis和ES
     */
    public function createUserInfoToRedis() {
        $amqp = new rabbitmq();
        $amqp->simpleReceiveUser('User');
    }
}