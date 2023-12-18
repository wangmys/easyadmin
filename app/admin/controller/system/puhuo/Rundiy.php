<?php

namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\model\bi\SpLypPuhuoRunModel;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Rundiy
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="手动执行铺货")
 */
class Rundiy extends AdminController
{

    protected $service;
    protected $Selecttype;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * @NodeAnotation(title="手动执行铺货")
     */
    public function index() {

        $res = $this->service->get_puhuo_run();
        $res_dingding_user = $this->service->get_dingding_user();

        if(!$res){
            $res['update_time']='';
        }

        $this->assign(
        array_merge(
        [
            'res' => $res,
            'res_dingding_user' => $res_dingding_user,
        ])
        );

        return $this->fetch();
    }

    /**
     * 手动执行铺货
     * @return mixed
     */
    public function rundiy() {

        $res = $this->service->get_puhuo_run();
        $res_dingding_user = $this->service->get_dingding_user();
        // print_r($res_dingding_user);die;

        $this->assign(
        array_merge(
        [
            'res' => $res,
            'res_dingding_user' => $res_dingding_user,
        ])
        );

        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="获取手动执行铺货耗时")
     */
    public function getPuhuoRun() {

        $post = $this->request->post();

        $res = $this->service->get_puhuo_run();

        if ($res && ($res['puhuo_status']==SpLypPuhuoRunModel::PUHUO_STATUS['running'])) {//铺货中，则不能进行铺货
            return $this->error('有铺货任务正在进行中...，请稍后再试');
        }

        $puhuo_goods_count = $this->service->get_puhuo_goods_count();

        if ($puhuo_goods_count <= 0) {
            return $this->error('没找到铺货货号，请先检查是否配置成功');
        }

        $need_time = 0;
        $local_sign = '';

        if (env('ENV_SIGN') == 'local') {

            $need_time = 50 * $puhuo_goods_count;        
            $local_sign = '我本地电脑 ';
            
        } elseif (env('ENV_SIGN') == 'product') {
            
            $need_time = 15 * $puhuo_goods_count;
            
        }

        $msg = '';
        if ($need_time <= 60) {

            $msg = $local_sign.'大约需要 '.$need_time.' 秒 铺完，请耐心等待';

        } else {

            $minute = floor($need_time/60);
            $second = $need_time%60;
            $msg = $local_sign.'大约需要 '.$minute.' 分 '.$second.' 秒 铺完，请耐心等待';

        }

        //保存钉钉推送用户
        if ($post['dingding']) {
            $this->service->save_dingding_user($post['dingding']);
        }

        return $this->success('成功', ['msg' => $msg]);

    }

    /**
     * @NodeAnotation(title="保存手动执行铺货记录")
     */
    public function savePuhuoRun() {

        $url = env('APP.APP_DOMAIN').'/api/puhuo.run/puhuo?admin_id='.session('admin.id');

        $res = curl_post_pro($url, json_encode([]), '', 1800);

        dd($res);
        return $this->success('成功', ['msg' => 'okkk']);

    }    

}
