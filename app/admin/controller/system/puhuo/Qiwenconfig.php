<?php

namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Qiwenconfig
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="气温评分")
 */
class Qiwenconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * @NodeAnotation(title="气温评分")
     */
    public function index() {

        $coldtohot_list = $this->service->get_qiwen_score('coldtohot');
        $hottocold_list = $this->service->get_qiwen_score('hottocold');
        $qiwen_head = ['yuncang', 'province', 'wenqu', 'qiwen_score'];
        $qiwen_head_hottocold = ['yuncang', 'province', 'wenqu', 'qiwen_score'];
        $this->assign([
            'coldtohot_list' => $coldtohot_list,
            'hottocold_list' => $hottocold_list,
            'qiwen_head' => $qiwen_head,
            'qiwen_head_hottocold' => $qiwen_head_hottocold,
        ]);

        return $this->fetch();
    }

    /**
     * 气温评分配置（冷到热）
     * @return mixed
     */
    public function coldtohot() {

        $coldtohot_list = $this->service->get_qiwen_score('coldtohot');
        $qiwen_head = ['yuncang', 'province', 'wenqu', 'qiwen_score'];
        $this->assign([
            'coldtohot_list' => $coldtohot_list,
            'qiwen_head' => $qiwen_head,
        ]);

        return $this->fetch();
    }

    /**
     * 气温评分配置（热到冷）
     * @return mixed
     */
    public function hottocold() {

        $hottocold_list = $this->service->get_qiwen_score('hottocold');
        $qiwen_head_hottocold = ['yuncang', 'province', 'wenqu', 'qiwen_score'];
        $this->assign([
            'hottocold_list' => $hottocold_list,
            'qiwen_head_hottocold' => $qiwen_head_hottocold,
        ]);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存气温评分标准配置(冷到热)")
     */
    public function saveColdtohot() {

        $post = $this->request->post();
        // echo json_encode($post);die;
        if (!$post['yuncang'] || !$post['province'] || !$post['wenqu'] || !$post['qiwen_score']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['id']=='' && $this->service->check_coldtohot($post) ) {
            return $this->error('配置已存在，请检查');
        }

        $res = $this->service->save_coldtohot($post);
        if ($res['msg']) {
            return $this->error('配置已存在，请检查');
        } else {
            return $this->success('成功',['id' => $res['id']]);
        }

    }

    /**
     * @NodeAnotation(title="保存气温评分标准配置（热到冷）")
     */
    public function saveHottocold() {

        $post = $this->request->post();
        // echo json_encode($post);die;
        if (!$post['yuncang'] || !$post['province'] || !$post['wenqu'] || !$post['qiwen_score']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['id']=='' && $this->service->check_hottocold($post) ) {
            return $this->error('配置已存在，请检查');
        }
        
        $res = $this->service->save_hottocold($post);
        if ($res['msg']) {
            return $this->error('配置已存在，请检查');
        } else {
            return $this->success('成功',['id' => $res['id']]);
        }

    }

    /**
     * @NodeAnotation(title="删除配置(冷到热)")
     */
    public function delColdtohot() {

        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->service->del_coldtohot($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

    /**
     * @NodeAnotation(title="删除配置(热到冷)")
     */
    public function delHottocold() {

        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->service->del_hottocold($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
