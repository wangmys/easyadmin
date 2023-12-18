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

        $post_goods1 = $post['GoodsNo'] ? explode(' ', trim($post['GoodsNo'])) : [];
        $post_goods1 = array_count_values($post_goods1);
        $post_goods_repeat = [];
        foreach ($post_goods1 as $k_goods_num => $v_goods_num) {
            if ($v_goods_num > 1) {
                $post_goods_repeat[] = $k_goods_num;
            }
        }
        if ($post_goods_repeat) {
            return $this->error('检测到有重复货号，请剔除:'.implode(',', $post_goods_repeat));
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
     * @NodeAnotation(title="一键保存铺货配置(多店/多省/商品专员/经营模式)")
     */
    public function savePuhuoZdySetAll() {

        $post_all = $this->request->post();

        //判断所传参数是否符合
        if ($post_all) {
            $post_all_arr = [];
            $post_goods1 = $post_goods_repeat = [];
            foreach ($post_all['GoodsNo'] as $k_goods=>$v_goods) {
                $post_goods1 = array_merge($post_goods1, $v_goods ? explode(' ', trim($v_goods)) : []);

                $post_all_arr[] = [
                    'Yuncang' => $post_all['Yuncang'][$k_goods],
                    'GoodsNo' => $v_goods,
                    'Selecttype' => $post_all['Selecttype'][$k_goods],
                    'Commonfield' => $post_all['Commonfield'][$k_goods],
                    'rule_type' => $post_all['rule_type'][$k_goods],
                    'remain_store' => $post_all['remain_store'][$k_goods],
                    'remain_rule_type' => $post_all['remain_rule_type'][$k_goods],
                    'if_taozhuang' => $post_all['if_taozhuang'][$k_goods],
                    'if_zdmd' => $post_all['if_zdmd'][$k_goods],
                    'id' => $post_all['id'][$k_goods],
                ];
            }

            $post_goods1 = array_count_values($post_goods1);
            foreach ($post_goods1 as $k_goods_num => $v_goods_num) {
                if ($v_goods_num > 1) {
                    $post_goods_repeat[] = $k_goods_num;
                }
            }
            if ($post_goods_repeat) {
                return $this->error('检测到有重复货号，请剔除:'.implode(',', $post_goods_repeat));
            }

            foreach ($post_all_arr as $post) {

                if (!$post['Yuncang'] || !$post['GoodsNo'] || !$post['Commonfield']) {
                    return $this->error('存在货号或店铺为空的情况，请检查');
                }
        
                if (($post['remain_store'] == SpLypPuhuoZdySet2Model::REMAIN_STORE['puhuo']) && ($post['remain_rule_type']==SpLypPuhuoZdySet2Model::REMAIN_RULE_TYPE['no_select'])) {//剩余门店铺货，则铺货规则必选
                    return $this->error('您已选择剩余门店铺货，请选择剩余门店铺货方案');
                }
        
                //检测是否GoodsNo已经存在
                $check_info = $this->service->checkPuhuoZdySetGoods2($post);
        
                if ($check_info['error'] == 2) {
                    return $this->error('套装套西的货号个数必须是双数，请检查');
                }
    
            }

            //没有问题再入库
            $res_arr = [];
            foreach ($post_all_arr as $post) {
                $res_id = $this->service->savePuhuoZdySet2($post);                
                $res_arr[] = [
                    'id' => $res_id,
                    'Yuncang' => $post_all['Yuncang'][0] ?? '',
                ];
            }

            return $this->success('成功',['id' => $res_id, 'Yuncang'=>$post_all['Yuncang'][0] ?? '',  'post_all_arr' => $post_all_arr, 'res_arr'=>$res_arr]);

        } else {

            return $this->error('无数据');

        }

    }

    /**
     * @return null
     * @NodeAnotation(title="一键删除全部",auth=false)
     */
    public function delPuhuoZdySetAll(){

        $post = $this->request->post();
        try {
            $db=Db::connect('mysql');
            $resID=$db->table('sp_lyp_puhuo_zdy_set2')->where(['admin_id'=>session('admin.id'),'Yuncang'=>$post['Yuncang'],'Selecttype'=>$post['Selecttype']])->column('id');
            $db->table('sp_lyp_puhuo_zdy_set2')->whereIn('id',$resID)->delete();
            $db->table('sp_lyp_puhuo_zdy_yuncang_goods2')->whereIn('set_id',$resID)->delete();
            $this->service->delPuhuoZdySet2($post);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

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
