<?php

namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\model\bi\SpLypPuhuoZdySet2Model;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Zdconfig2zh
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="指定铺货货品配置2zh")
 */
class Zdconfig2zh extends AdminController
{

    protected $service;
    protected $Selecttype;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
        $this->Selecttype = [['name'=>'组合', 'value'=>'1'], ['name'=>'单店', 'value'=>'2']];
    }

    /**
     * @NodeAnotation(title="指定铺货货品配置zh")
     */
    public function index() {

        $res_guiyang = $this->service->get_zdy_goods2zh('贵阳云仓');
        $res_wuhan = $this->service->get_zdy_goods2zh('武汉云仓');
        $res_guangzhou = $this->service->get_zdy_goods2zh('广州云仓');
        $res_nanchang = $this->service->get_zdy_goods2zh('南昌云仓');
        $res_changsha = $this->service->get_zdy_goods2zh('长沙云仓');

        $this->assign(
        array_merge(
        [

            'guiyang_goods_config' => $res_guiyang['guiyang_goods_config'],
            'guiyang_select_list' => $res_guiyang['guiyang_select_list'],

            'wuhan_goods_config' => $res_wuhan['wuhan_goods_config'],
            'wuhan_select_list' => $res_wuhan['wuhan_select_list'],

            'guangzhou_goods_config' => $res_guangzhou['guangzhou_goods_config'],
            'guangzhou_select_list' => $res_guangzhou['guangzhou_select_list'],

            'nanchang_goods_config' => $res_nanchang['nanchang_goods_config'],
            'nanchang_select_list' => $res_nanchang['nanchang_select_list'],

            'changsha_goods_config' => $res_changsha['changsha_goods_config'],
            'changsha_select_list' => $res_changsha['changsha_select_list'],

            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
        ])
        );

        return $this->fetch();
    }

    /**
     * 贵阳云仓参数配置
     * @return mixed
     */
    public function guiyang_goods_config() {

        $res = $this->service->get_zdy_goods2zh('贵阳云仓');
        $this->assign(
        array_merge(
        [
            'guiyang_goods_config' => $res['guiyang_goods_config'],
            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
            'guiyang_select_list' => $res['guiyang_select_list'],
        ])
        );

        return $this->fetch();

    }

    /**
     * 武汉云仓参数配置
     * @return mixed
     */
    public function wuhan_goods_config() {

        $res = $this->service->get_zdy_goods2zh('武汉云仓');
        // print_r($res);die;
        $this->assign(
        array_merge(
        [
            'wuhan_goods_config' => $res['wuhan_goods_config'],
            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
            'wuhan_select_list' => $res['wuhan_select_list'],
        ])
        );

        return $this->fetch();
    }

    /**
     * 广州云仓参数配置
     * @return mixed
     */
    public function guangzhou_goods_config() {

        $res = $this->service->get_zdy_goods2zh('广州云仓');
        $this->assign(
        array_merge(
        [
            'guangzhou_goods_config' => $res['guangzhou_goods_config'],
            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
            'guangzhou_select_list' => $res['guangzhou_select_list'],
        ])
        );

        return $this->fetch();

    }

    /**
     * 南昌云仓参数配置
     * @return mixed
     */
    public function nanchang_goods_config() {

        $res = $this->service->get_zdy_goods2zh('南昌云仓');
        $this->assign(
        array_merge(
        [
            'nanchang_goods_config' => $res['nanchang_goods_config'],
            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
            'nanchang_select_list' => $res['nanchang_select_list'],
        ])
        );

        return $this->fetch();

    }

    /**
     * 长沙云仓参数配置
     * @return mixed
     */
    public function changsha_goods_config() {

        $res = $this->service->get_zdy_goods2zh('长沙云仓');
        $this->assign(
        array_merge(
        [
            'changsha_goods_config' => $res['changsha_goods_config'],
            'Selecttype' => $this->Selecttype,
            'rule_type' => SpLypPuhuoZdySet2Model::RULE_TYPE_TEXT,
            'changsha_select_list' => $res['changsha_select_list'],
        ])
        );

        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="保存仓库指定铺货货品配置")
     */
    public function saveZhidingGoodsConfig() {

        $post = $this->request->post();
        if (!$post['Yuncang']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $res_id = $this->service->saveZhidingGoodsConfig($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['Yuncang']]);

    }

    /**
     * @NodeAnotation(title="保存铺货配置(多店/多省/商品专员/经营模式)")
     */
    public function savePuhuoZdySet() {

        $post = $this->request->post();

        //test...
        // return $this->success('成功', $post);die;

        if (!$post['Yuncang'] || !$post['GoodsNo'] || !$post['Commonfield']) {
            return $this->error('存在货号或店铺为空的情况，请检查');
        }

        if (($post['remain_store'] == SpLypPuhuoZdySet2Model::REMAIN_STORE['puhuo']) && ($post['remain_rule_type']==SpLypPuhuoZdySet2Model::REMAIN_RULE_TYPE['no_select'])) {//剩余门店铺货，则铺货规则必选
            return $this->error('您已选择剩余门店铺货，请选择剩余门店铺货方案');
        }

        //检测是否GoodsNo已经存在
        $check_info = $this->service->checkPuhuoZdySetGoods2($post);

        if ($check_info['error'] == 1) {
            return $this->error('以下货号在该云仓已存在，请剔除:'.$check_info['goodsno_str']);
        }

        if ($check_info['error'] == 2) {
            return $this->error('套装套西的货号个数必须是双数，请检查');
        }

        $res_id = $this->service->savePuhuoZdySet2($post);

        return $this->success('成功',['id' => $res_id, 'Yuncang'=>$post['Yuncang']]);

    }

    /**
     * @NodeAnotation(title="删除铺货配置(多店/多省/商品专员/经营模式)")
     */
    public function delPuhuoZdySet() {

        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->service->delPuhuoZdySet2($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }
    

}