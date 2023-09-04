<?php

namespace app\admin\controller\system\puhuo;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Zhidingconfig
 * 指定铺货货品配置
 */
class Zhidingconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * 指定铺货货品配置
     */
    public function index() {

        $guiyang_goods_config = $this->service->get_zhiding_goods('贵阳云仓');
        $wuhan_goods_config = $this->service->get_zhiding_goods('武汉云仓');
        $guangzhou_goods_config = $this->service->get_zhiding_goods('广州云仓');
        $nanchang_goods_config = $this->service->get_zhiding_goods('南昌云仓');
        $changsha_goods_config = $this->service->get_zhiding_goods('长沙云仓');
        $this->assign([
            'guiyang_goods_config' => $guiyang_goods_config,
            'wuhan_goods_config' => $wuhan_goods_config,
            'guangzhou_goods_config' => $guangzhou_goods_config,
            'nanchang_goods_config' => $nanchang_goods_config,
            'changsha_goods_config' => $changsha_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 贵阳云仓参数配置
     * @return mixed
     */
    public function guiyang_goods_config() {

        $guiyang_goods_config = $this->service->get_zhiding_goods('贵阳云仓');
        $this->assign([
            'guiyang_goods_config' => $guiyang_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 武汉云仓参数配置
     * @return mixed
     */
    public function wuhan_goods_config() {

        $wuhan_goods_config = $this->service->get_zhiding_goods('武汉云仓');
        $this->assign([
            'wuhan_goods_config' => $wuhan_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 广州云仓参数配置
     * @return mixed
     */
    public function guangzhou_goods_config() {

        $guangzhou_goods_config = $this->service->get_zhiding_goods('广州云仓');
        $this->assign([
            'guangzhou_goods_config' => $guangzhou_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 南昌云仓参数配置
     * @return mixed
     */
    public function nanchang_goods_config() {

        $nanchang_goods_config = $this->service->get_zhiding_goods('南昌云仓');
        $this->assign([
            'nanchang_goods_config' => $nanchang_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 长沙云仓参数配置
     * @return mixed
     */
    public function changsha_goods_config() {

        $changsha_goods_config = $this->service->get_zhiding_goods('长沙云仓');
        $this->assign([
            'changsha_goods_config' => $changsha_goods_config,
        ]);

        return $this->fetch();
    }

    /**
     * 保存仓库指定铺货货品配置
     */
    public function saveZhidingGoodsConfig() {

        $post = $this->request->post();
        if (!$post['Yuncang']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $res_id = $this->service->saveZhidingGoodsConfig($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['Yuncang']]);

    }
    

}
