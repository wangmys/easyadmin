<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\shop\controller;


use app\admin\model\ShopUser;
use app\common\controller\ShopController;
use think\captcha\facade\Captcha;
use think\facade\Env;

/**
 * Class Login
 * @package app\shop\controller
 */
class Login extends ShopController
{

    /**
     * 初始化方法
     */
    public function initialize()
    {
        parent::initialize();
        $action = $this->request->action();
        if (!empty(session('shop')) && !in_array($action, ['out'])) {
            $shopModuleName = app('http')->getName();
            $this->success('已登录，无需再次登录', [], __url("@{$shopModuleName}"));
        }
    }

    /**
     * 用户登录
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        $captcha = Env::get('easyadmin.captcha', 1);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'username|用户名'      => 'require',
                'password|密码'       => 'require',
                'keep_login|是否保持登录' => 'require',
            ];
            $captcha == 1 && $rule['captcha|验证码'] = 'require|captcha';
//            $this->validate($post, $rule);
            $admin = ShopUser::where(['username' => $post['username']])->find();
            if (empty($admin)) {
                $this->error('用户不存在');
            }
            if (password($post['password']) != $admin->password) {
                $this->error('密码输入有误');
            }
            if ($admin->status == 0) {
                $this->error('账号已被禁用');
            }
            $admin->login_num += 1;
            $admin->save();
            $admin = $admin->toArray();
            unset($admin['password']);
            $admin['expire_time'] = $post['keep_login'] == 1 ? true : time() + 7200;
            session('shop', $admin);
            $this->success('登录成功');
        }
        $this->assign('captcha', $captcha);
        $this->assign('demo', $this->isDemo);
        return $this->fetch();
    }

    /**
     * 用户退出
     * @return mixed
     */
    public function out()
    {
        session('shop', null);
        $this->success('退出登录成功');
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }
}
