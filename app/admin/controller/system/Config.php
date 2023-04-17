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

namespace app\admin\controller\system;


use app\admin\model\SystemConfig;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use app\common\constants\AdminConstant;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\logic\inventory\DressLogic;
use think\App;

/**
 * Class Config
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统配置管理")
 */
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
        // 实例化逻辑类
        $this->logic = new DressLogic;
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        // 字段
        $field = AdminConstant::STOCK_FIELD;
        // 门店列表
        $store_list = $yinliu_store_list = $this->logic->getStore();
        // 已保存门店
        $save_store = sysconfig('site','accessories_store_list');
        if($save_store){
            $save_store_arr = explode(',',$save_store);
            foreach ($save_store_arr as $k => $v){
                $val_list = array_column($store_list,'name');
                $rs_key = array_search($v,$val_list);
                if($rs_key !== false){
                    if($store_list[$rs_key]){
                        $store_list[$rs_key]['selected'] = true;
                    }
                }
            }
        }
        // 已保存门店
        $yinliu_store = sysconfig('site','yinliu_store_list');
        if($yinliu_store){
            $save_yinliu_store = explode(',',$yinliu_store);
            foreach ($save_yinliu_store as $k => $v){
                $val_list = array_column($yinliu_store_list,'name');
                $rs_key = array_search($v,$val_list);
                if($rs_key !== false){
                    if($yinliu_store_list[$rs_key]){
                        $yinliu_store_list[$rs_key]['selected'] = true;
                    }
                }
            }
        }
        $this->assign([
            'field' => $field,
            'store_list' => $store_list,
            'yinliu_store_list' => $yinliu_store_list
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存")
     */
    public function save()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        try {
            $group = 'site';
            if(isset($post['sysconfig_group'])){
                $group = $post['sysconfig_group'];
                unset($post['sysconfig_group']);
            }
            $data = [];
            foreach ($post as $key => $val) {
                $id = $this->model->where('name', $key)->value('id');
                $d = [
                    'name' => $key,
                    'value' => $val,
                    'group' => $group
                ];
                if(in_array($key,['accessories_store_list','yinliu_store_list'])){
                    $d['group'] = 'site';
                }
                if($id){
                    $d['id'] = $id;
                }
                $data[] = $d;
            }
            $this->model->saveAll($data);
            TriggerService::updateMenu();
            TriggerService::updateSysconfig();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

    public function save_copy()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        try {
            foreach ($post as $key => $val) {
                $this->model
                    ->where('name', $key)
                    ->update([
                        'value' => $val,
                    ]);
            }
            TriggerService::updateMenu();
            TriggerService::updateSysconfig();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }
}