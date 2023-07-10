<?php

namespace app\admin\controller\system\skc;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\SkcService;

/**
 * Class Kzconfig
 * 鞋架陈列标准配置
 */
class Shoeconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new SkcService;
    }

    /**
     * 鞋架陈列标准配置
     */
    public function index() {

        $shoe_num_list = $this->service->get_skc_shoe_num();
        $shoe_num_head = ['area_range', 'shoe_num', 'skc_zt', 'skc_xx', 'skc_ydx', 'skc_lx'];
        $this->assign([
            'shoe_num_list' => $shoe_num_list,
            'shoe_num_head' => $shoe_num_head,
        ]);
        
        $shoeskc_config = $this->service->get_skc_shoe_config();
        $this->assign([
            'shoeskc_config' => $shoeskc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 鞋架陈列标准配置
     * @return mixed
     */
    public function shoe_num() {

        $shoe_num_list = $this->service->get_skc_shoe_num();
        $shoe_num_head = ['key_str', 'area_range', 'shoe_num', 'skc_zt', 'skc_xx', 'skc_ydx', 'skc_lx'];
        $this->assign([
            'shoe_num_list' => $shoe_num_list,
            'shoe_num_head' => $shoe_num_head,
        ]);

        return $this->fetch();
    }

    /**
     * SKC价格配置
     * @return mixed
     */
    public function kzskc_config() {

        $shoeskc_config = $this->service->get_skc_shoe_config();
        $this->assign([
            'shoeskc_config' => $shoeskc_config,
        ]);

        return $this->fetch();
    }

    /**
     * 保存鞋架陈列标准配置
     */
    public function saveConfig() {

        $post = $this->request->post();
        if (!$post['area_range'] || !$post['shoe_num']) {
            return $this->error('仓库面积 或 鞋柜数必填，请检查');
        }

        if ( $post['id']=='' && $this->service->check_skc_shoe_num($post['area_range'].$post['shoe_num']) ) {
            return $this->error('配置已存在，请检查');
        }

        $res_id = $this->service->save_skc_shoe_num($post);

        return $this->success('成功',['id' => $res_id]);

    }

    /**
     * 保存skc价格配置
     */
    public function saveSkcConfig() {

        $post = $this->request->post();
        if (!$post['shoe_price']) {
            return $this->error('鞋履价格必填，请检查');
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
            $this->service->del_skc_shoe_num($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
