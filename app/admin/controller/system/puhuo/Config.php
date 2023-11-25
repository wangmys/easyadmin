<?php

namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Config
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="仓库&门店&最终铺货连码配置")
 */
class Config extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * @NodeAnotation(title="仓库&门店&最终铺货连码配置")
     */
    public function index() {

        $puhuo_config = $this->service->get_puhuo_config();
        $puhuo_config2 = $this->service->get_puhuo_config2();
        $this->assign([
            'puhuo_config' => $puhuo_config,
            'puhuo_config2' => $puhuo_config2,
        ]);

        return $this->fetch();
    }

    /**
     * 仓库预留参数配置
     * @return mixed
     */
    public function warehouse_config() {

        $puhuo_config = $this->service->get_puhuo_config();
        $this->assign([
            'puhuo_config' => $puhuo_config,
        ]);

        return $this->fetch();
    }

    /**
     * 仓库预留参数配置2
     * @return mixed
     */
    public function warehouse_config2() {

        $puhuo_config2 = $this->service->get_puhuo_config2();
        $this->assign([
            'puhuo_config2' => $puhuo_config2,
        ]);

        return $this->fetch();
    }

    /**
     * 门店上铺货连码标准配置
     * @return mixed
     */
    public function lianma_config() {

        $puhuo_config = $this->service->get_puhuo_config();
        $this->assign([
            'puhuo_config' => $puhuo_config,
        ]);

        return $this->fetch();
    }

    /**
     * 仓库齐码参数配置
     * @return mixed
     */
    public function warehouse_qima_config() {

        $puhuo_config = $this->service->get_puhuo_config();
        $this->assign([
            'puhuo_config' => $puhuo_config,
        ]);

        return $this->fetch();
    }

    /**
     * 单店上市天数不再铺限制配置
     * @return mixed
     */
    public function listing_days_config() {

        $puhuo_config = $this->service->get_puhuo_config();
        $this->assign([
            'puhuo_config' => $puhuo_config,
        ]);

        return $this->fetch();
    }

    /**
     * 最终铺货连码标准配置
     * @return mixed
     */
    public function end_lianma_config() {

        $puhuo_config = $this->service->get_puhuo_config();
        $this->assign([
            'puhuo_config' => $puhuo_config,
        ]);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存仓库预留参数配置")
     */
    public function saveWarehouseConfig() {

        $post = $this->request->post();
        if (!$post['warehouse_reserve_smallsize'] || !$post['warehouse_reserve_mainsize'] || !$post['warehouse_reserve_bigsize']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $res_id = $this->service->save_warehouse_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }

    /**
     * @NodeAnotation(title="保存仓库预留参数配置2")
     */
    public function saveWarehouseConfig2() {

        $post = $this->request->post();
        $res_id = $this->service->save_warehouse_config2($post);

        return $this->success('成功',['id' => $res_id, 'config_str'=>$post['config_str']]);

    }

    /**
     * @NodeAnotation(title="保存门店上铺货连码标准配置")
     */
    public function saveLianmaConfig() {

        $post = $this->request->post();
        if (!$post['store_puhuo_lianma_nd'] || !$post['store_puhuo_lianma_xz']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $post['store_puhuo_lianma_wt'] = $post['store_puhuo_lianma_nd'];
        $post['store_puhuo_lianma_xl'] = $post['store_puhuo_lianma_nd'];
        $post['store_puhuo_lianma_sjk'] = $post['store_puhuo_lianma_nd'];
        $res_id = $this->service->save_warehouse_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }


    /**
     * @NodeAnotation(title="保存仓库齐码参数配置")
     */
    public function saveWarehouseQimaConfig() {

        $post = $this->request->post();
        if (!$post['warehouse_qima_nd'] || !$post['warehouse_qima_xz']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $post['warehouse_qima_wt'] = $post['warehouse_qima_nd'];
        $post['warehouse_qima_xl'] = $post['warehouse_qima_nd'];
        $post['warehouse_qima_sjk'] = $post['warehouse_qima_nd'];
        $res_id = $this->service->save_warehouse_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }

    /**
     * @NodeAnotation(title="保存单店上市天数不再铺限制配置")
     */
    public function saveListingDaysConfig() {

        $post = $this->request->post();
        if (!$post['listing_days']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $res_id = $this->service->save_warehouse_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }

    /**
     * @NodeAnotation(title="保存最终连码标准配置")
     */
    public function saveEndLianmaConfig() {

        $post = $this->request->post();
        if (!$post['end_puhuo_lianma_nd'] || !$post['end_puhuo_lianma_sjdk'] || !$post['end_puhuo_lianma_xz']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $post['end_puhuo_lianma_nd'] = $post['end_puhuo_lianma_nd'];
        $post['end_puhuo_lianma_wt'] = $post['end_puhuo_lianma_nd'];
        $post['end_puhuo_lianma_xl'] = $post['end_puhuo_lianma_nd'];
        $post['end_puhuo_lianma_sjdk'] = $post['end_puhuo_lianma_sjdk'];
        $post['end_puhuo_lianma_sjck'] = $post['end_puhuo_lianma_sjdk'];
        $post['end_puhuo_lianma_xz'] = $post['end_puhuo_lianma_xz'];
        $res_id = $this->service->save_warehouse_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }

}
