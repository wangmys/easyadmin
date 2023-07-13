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

namespace app\admin\controller;


use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use think\captcha\facade\Captcha;
use think\facade\Env;
use app\admin\service\LoginService;

/**
 * Class Login
 * @package app\admin\controller
 */
class Login extends AdminController
{

    /**
     * 初始化方法
     */
    public function initialize()
    {
        parent::initialize();
        $action = $this->request->action();
        if (!empty(session('admin')) && !in_array($action, ['out'])) {
            $adminModuleName = config('app.admin_alias_name');
            $this->success('已登录，无需再次登录', [], __url("@{$adminModuleName}"));
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
            $this->validate($post, $rule);
            $admin = SystemAdmin::where(['username' => $post['username']])->find();
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
            session('admin', $admin);
            $login_cache = cache($post['username']);
            $APP_DOMAIN = $_SERVER['SERVER_NAME'];
            $SERVER_ADDR = $_SERVER['SERVER_ADDR'];
            if (!$login_cache && strstr($APP_DOMAIN, 'im.babiboy.com') && $SERVER_ADDR=='42.193.181.241') {//只在线上环境作统计
                //登录计数
                $month = date('Y-m');
                $if_exist = LoginService::getInstance()->get_login_count([['username', '=', $post['username']], ['month', '=', $month]]);
                if ($if_exist) {
                    $login_count = $if_exist['login_count'] + 1;
                    LoginService::getInstance()->add_login_count([['username', '=', $post['username']], ['month', '=', $month]], ['login_count' => $login_count]);
                } else {
                    LoginService::getInstance()->save_login_count(['username'=>$post['username'], 'name'=>$admin['name'], 'login_count'=>1, 'month'=>$month]);
                }
                //记录登录缓存计数
                cache($post['username'], json_encode($post['username']), strtotime(date('Y-m-d').' 23:59:59')-time());
            }

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
        session('admin', null);
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
