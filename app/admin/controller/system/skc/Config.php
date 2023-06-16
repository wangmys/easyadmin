<?php

namespace app\admin\controller\system\skc;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\model\SystemConfig;
use app\admin\service\SkcService;

/**
 * Class Config
 * 窗数标准配置
 */
class Config extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new SkcService;
    }

    /**
     * 窗数标准配置
     */
    public function index() {

        $win_num_list = $this->service->get_skc_win_num();
        $win_num_head = ['area_range',  'win_num', 'skc_fl', 'skc_yl', 'skc_xxdc', 'skc_num'];
        $this->assign([
            'win_num_list' => $win_num_list,
            'win_num_head' => $win_num_head,
        ]);

        $skc_config = $this->service->get_skc_config();
        $this->assign([
            'skc_config' => $skc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 窗数陈列标准配置
     * @return mixed
     */
    public function win_num() {

        $win_num_list = $this->service->get_skc_win_num();
        $win_num_head = ['area_range',  'win_num', 'skc_fl', 'skc_yl', 'skc_xxdc', 'skc_num'];
        $this->assign([
            'win_num_list' => $win_num_list,
            'win_num_head' => $win_num_head,
        ]);

        return $this->fetch();
    }

    /**
     * SKC价格配置
     * @return mixed
     */
    public function skc_config() {

        $skc_config = $this->service->get_skc_config();
        $this->assign([
            'skc_config' => $skc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 保存窗数标准配置
     */
    public function saveConfig() {

        $post = $this->request->post();
        if (!$post['area_range'] || !$post['win_num'] || !$post['skc_fl'] || !$post['skc_yl'] || !$post['skc_xxdc'] || !$post['skc_num']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['sign_id']=='' && $this->service->check_skc_win_num($post['win_num'].$post['area_range']) ) {
            return $this->error('配置已存在，请检查');
        }

        $res_id = $this->service->save_skc_win_num($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['win_num'].$post['area_range']]);

    }

    /**
     * 保存skc价格配置
     */
    public function saveSkcConfig() {

        $post = $this->request->post();
        if (!$post['dt_price'] || !$post['dc_price']) {
            return $this->error('存在值为空的情况，请检查');
        }
        $res_id = $this->service->save_skc_config($post);

        return $this->success('成功',['id' => $res_id, 'sign_id'=>$post['sign_id']]);

    }


    /**
     * 删除配置
     */
    public function delConfig() {

        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->service->del_skc_win_num($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
