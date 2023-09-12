<?php

namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Scconfig
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="店铺等级&&skc满足率&&动销率评分")
 */
class Scconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * @NodeAnotation(title="店铺等级&&skc满足率&&动销率评分")
     */
    public function index() {

        $customer_level_list = $this->service->get_puhuo_score('customer_level');
        $fill_rate_list = $this->service->get_puhuo_score('fill_rate');
        $dongxiao_rate_list = $this->service->get_puhuo_score('dongxiao_rate');
        $customer_level_head = ['key', 'score'];
        $fill_rate_head = ['key', 'score'];
        $dongxiao_rate_head = ['key', 'score'];
        
        $this->assign([
            'customer_level_list' => $customer_level_list,
            'fill_rate_list' => $fill_rate_list,
            'dongxiao_rate_list' => $dongxiao_rate_list,
            'customer_level_head' => $customer_level_head,
            'fill_rate_head' => $fill_rate_head,
            'dongxiao_rate_head' => $dongxiao_rate_head,
        ]);

        return $this->fetch();
    }

    /**
     * 店铺等级评分配置
     * @return mixed
     */
    public function customer_level() {

        $customer_level_list = $this->service->get_puhuo_score('customer_level');
        $customer_level_head = ['key', 'score'];
        $this->assign([
            'customer_level_list' => $customer_level_list,
            'customer_level_head' => $customer_level_head,
        ]);

        return $this->fetch();
    }

    /**
     * 满足率评分配置
     * @return mixed
     */
    public function fill_rate() {

        $fill_rate_list = $this->service->get_puhuo_score('fill_rate');
        $fill_rate_head = ['key', 'score'];
        $this->assign([
            'fill_rate_list' => $fill_rate_list,
            'fill_rate_head' => $fill_rate_head,
        ]);

        return $this->fetch();
    }

    /**
     * 动销率评分配置
     * @return mixed
     */
    public function dongxiao_rate() {

        $dongxiao_rate_list = $this->service->get_puhuo_score('dongxiao_rate');
        $dongxiao_rate_head = ['key', 'score'];
        $this->assign([
            'dongxiao_rate_list' => $dongxiao_rate_list,
            'dongxiao_rate_head' => $dongxiao_rate_head,
        ]);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存评分标准配置")
     */
    public function saveConfig() {

        $post = $this->request->post();
        // echo json_encode($post);die;
        if (!$post['key'] || !$post['score'] || !$post['config_str']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['id']=='' && $this->service->check_customer_level($post) ) {
            return $this->error('配置已存在，请检查');
        }

        $res_id = $this->service->save_customer_level($post);

        return $this->success('成功',['id' => $res_id]);

    }

    /**
     * @NodeAnotation(title="删除配置")
     */
    public function delConfig() {

        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->service->del_customer_level($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
