<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\admin\model\SystemConfig;
use app\common\logic\inventory\DressLogic;
use think\facade\Cache;


/**
 * Class Config
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="引流配置")
 */
class Config extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Accessories;
        // 实例化逻辑类
        $this->logic = new DressLogic;
    }

    /**
     * 数据筛选配置
     */
    public function index()
    {
        // 查询所有的省份
        $provinceList = $this->logic->getProvince();
        if ($this->request->isAjax()) {
            // 字段
            $field = AdminConstant::YINLIU_COLUMN;
            $data = [
                'provinceList' => $provinceList,
                'field' => $field
            ];
            return json($data);
        }

        // 查询表头
        $head = $this->logic->getHead();
        // 获取搜索条件列表(省列表,店铺列表,商品负责人列表)
        $getSelectList = $this->logic->getSelectList()['省份'];
        $d_field = sysconfig('site','dress_field');
        $d_field = json_decode($d_field,true);

        $d_field2 = [];
        foreach ($provinceList as $kk => $vv){
            $_kk = $vv['name'];
            $d_field2[$_kk]['省份'] = $_kk;
            // 已保存数值
            $item = $d_field[$_kk]??[];
            // 获取省份
            foreach ($head as $k=>$v){
                $v_key = $v['name'];
                if(isset($item[$v_key])){
                    $d_field2[$_kk][$v['name']] = $item[$v_key];
                }else{
                    $d_field2[$_kk][$v['name']] = $v['stock'];
                }
            }
        }

        $head_field = array_column($head,'name');
        $data = $this->logic->warStock->select()->toArray();
        $list = [];
        $province = $this->logic->getProvince();
        foreach ($data as $k => $v){
            $item = json_decode($v['content'],true);
            $new_item = ['省份' => $item['省份']];
            foreach ($head_field as $j => $jv){
                $new_item[$jv] = $item[$jv]??0;
            }
            $new_item['_province'] = $this->logic->setProvince($new_item['省份'],$province);
            $list[] = $new_item;
        }
        // 传值
        $this->assign([
            'head_field' => $head_field,
            'province' => $this->logic->getProvince(),
            'list' => $list
        ]);

        $this->assign([
            'field' => $head,
            '_field' => array_column($head,'name'),
            'd_field' => $d_field2,
        ]);
        return $this->fetch();
    }

     /**
     * 库存预警配置
     * @return mixed
     */
    public function waring_stock()
    {
        // 查询表头
        $head = $this->logic->getHead();
        $head_field = array_column($head,'name');
        $data = $this->logic->warStock->select()->toArray();
        $list = [];
        $province = $this->logic->getProvince();
        foreach ($data as $k => $v){
            $item = json_decode($v['content'],true);
            $new_item = ['省份' => $item['省份']];
            foreach ($head_field as $j => $jv){
                $new_item[$jv] = $item[$jv]??0;
            }
            $new_item['_province'] = $this->logic->setProvince($new_item['省份'],$province);
            $list[] = $new_item;
        }
        // 传值
        $this->assign([
            'head_field' => $head_field,
            'province' => $this->logic->getProvince(),
            'list' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 保存库存预警配置
     */
    public function waring_stock_save()
    {
        $provinceAll = [];
        $post = $this->request->post();
        $provinceList = $post['省份'];
        $item_all = [];
        foreach ($provinceList as $k => $v){
            $province_item = explode(',',$v);
            if($k>0){
                if($this->checkProvince($provinceAll,$province_item)){
                    return $this->error('省份重复,请重新选择');
                }
            }
            $provinceAll = array_merge($provinceAll,$province_item);

            foreach ($post as $kk => $vv){
                $new[$kk] = $vv[$k];
            }
            $_province = $new['省份'];
            $item_all[] = [
                'province' => $_province,
                'content' => json_encode($new)
            ];
        }
        $rs = $this->logic->saveWarStock($item_all);
        if($rs){
            return $this->success('成功');
        }
        return $this->error('失败');
    }

    /**
     * 检测有没有重复省
     * @param $all
     * @param $check_arr
     * @return bool
     */
    public function checkProvince($all,$check_arr)
    {
        foreach ($check_arr as $k => $v){
            if(in_array($v,$all)){
                return true;
            }
        }
        return false;
    }

    /**
     * 保存引流表头配置
     */
    public function saveConfig()
    {
        // 获取提交数据
        $post = $this->request->post();
        $check_list = ['id','field'];
        foreach ($post as $k=>$v){
            if(empty($v) && in_array($k,$check_list)){
                return $this->error('参数不能为空');
            }
            if($k == 'stock' && $v==''){
                return $this->error('请填写库存值');
            }
            if($k == 'name' && $v==''){
                $post[$k] = str_replace(',','_',$post['field']);
            }
        }
        try {
            if(!empty($post['id'])){
                $model = $this->logic->dressHead->find($post['id']);
                $model->save($post);
                $res_id = $model->id;
            }else{
                $res_id = $this->logic->saveHead($post);
            }
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('成功',['id' => $res_id]);
    }

    /**
     * 删除配置
     */
    public function delConfig()
    {
        // ID
        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->logic->dressHead->where('id',$id)->delete();
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');
    }

    /**
     * 保存配置
     */
    public function save()
    {
        // 数据
        $post = $this->request->post();
        $data = [];
        $model  = new SystemConfig();
        foreach ($post['省份'] as $k => $v){
            foreach ($post as $kk => $vv){
                $item[$kk] = $vv[$k];
            }
            $data[$v] = $item;
        }
        $save_data = [
            'name' => 'dress_field',
            'value' => json_encode($data),
            'group' => 'site'
        ];
        if(sysconfig('site','dress_field')){
            $model->where(['name' => 'dress_field'])->save($save_data);
        }else{
            $model->save($save_data);
        }
        Cache::clear();
        return $this->success('成功');
    }
}
