<?php

namespace app\admin\controller\system\puhuo;

use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use app\admin\service\PuhuoService;

/**
 * Class Tigtconfig
 * 指定款货品等级配置
 */
class Tigtconfig extends AdminController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new PuhuoService;
    }

    /**
     * 指定款货品等级配置
     */
    public function index() {

        $ti_goods_type = $this->service->get_ti_goods_type();
        $ti_goods_type_head = ['GoodsLevel'];
        
        $this->assign([
            'ti_goods_type_head' => $ti_goods_type_head,
            'ti_goods_type_list' => $ti_goods_type,
        ]);

        return $this->fetch();
    }

    /**
     * 指定款货品等级配置
     * @return mixed
     */
    public function ti_goods_type() {

        $ti_goods_type = $this->service->get_ti_goods_type();
        $ti_goods_type_head = ['GoodsLevel'];
        
        $this->assign([
            'ti_goods_type_head' => $ti_goods_type_head,
            'ti_goods_type_list' => $ti_goods_type,
        ]);

        return $this->fetch();
    }

    /**
     * 保存配置
     */
    public function saveConfig() {

        $post = $this->request->post();
        if (!$post['GoodsLevel']) {
            return $this->error('存在值为空的情况，请检查');
        }

        if ( $post['id']=='' && $this->service->check_ti_goods_type($post) ) {
            return $this->error('配置已存在，请检查');
        }

        $res = $this->service->save_ti_goods_type($post);
        if ($res['msg']) {
            return $this->error($res['msg']);
        } else {
            return $this->success('成功',['id' => $res['id']]);
        }

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
            $this->service->del_ti_goods_type($id);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');

    }

}
