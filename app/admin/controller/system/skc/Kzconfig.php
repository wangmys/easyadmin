<?php

namespace app\admin\controller\system\skc;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\SkcService;

/**
 * Class Kzconfig
 * 裤台陈列标准配置
 */
class Kzconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new SkcService;
    }

    /**
     * 裤台陈列标准配置
     */
    public function index() {

        $kz_num_list = $this->service->get_skc_kz_num();
        $kz_num_head = ['kt_num',  'skc_cknz', 'skc_ckxx', 'skc_cksj'];
        $this->assign([
            'kz_num_list' => $kz_num_list,
            'kz_num_head' => $kz_num_head,
        ]);

        $kzskc_config = $this->service->get_skc_kz_config();
        $this->assign([
            'kzskc_config' => $kzskc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 裤台陈列标准配置
     * @return mixed
     */
    public function kz_num() {

        $kz_num_list = $this->service->get_skc_kz_num();
        $kz_num_head = ['kt_num',  'skc_cknz', 'skc_ckxx', 'skc_cksj'];
        $this->assign([
            'kz_num_list' => $kz_num_list,
            'kz_num_head' => $kz_num_head,
        ]);

        return $this->fetch();
    }

    /**
     * SKC价格配置
     * @return mixed
     */
    public function kzskc_config() {

        $kzskc_config = $this->service->get_skc_kz_config();
        $this->assign([
            'kzskc_config' => $kzskc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 保存裤台陈列标准配置
     */
    public function saveConfig() {

        $post = $this->request->post();
        if (!$post['kt_num'] || !$post['skc_cknz'] || !$post['skc_ckxx'] || !$post['skc_cksj']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['id']=='' && $this->service->check_skc_kz_num($post['kt_num']) ) {
            return $this->error('配置已存在，请检查');
        }

        $res_id = $this->service->save_skc_kz_num($post);

        return $this->success('成功',['id' => $res_id]);

    }

    /**
     * 保存skc价格配置
     */
    public function saveSkcConfig() {

        $post = $this->request->post();
        if (!$post['dk_price'] || !$post['ck_price']) {
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
            $this->service->del_skc_kz_num($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
