<?php
namespace app\controller;

use app\BaseController;
use think\facade\Request;
use app\service\UserService;

class User extends BaseController {
    private $service;

    public function initialize() {
        $this->service = new UserService();
    }

    /**
     * 获取验证码
     * @return \think\response\Json
     */
    public function getCaptcha() {
        $res = $this->service->getCaptchaService();
        return json($res);
    }

    /**
     * 登录
     * @return \think\response\Json
     */
    public function login() {
        $code = Request::param('code');
        $username = Request::param('username');
        $password = Request::param('password');

        $res = $this->service->loginService($code, $username, $password);
        return json($res);
    }

    /**
     * 注册
     * @return \think\response\Json
     */
    public function register() {
        $email = Request::param('email');
        $code = Request::param('code');
        $username = Request::param('username');
        $password = Request::param('password');
        $repassword = Request::param('repassword');

        $res = $this->service->registerService($code, $username, $password, $email, $repassword);
        return json($res);
    }
}