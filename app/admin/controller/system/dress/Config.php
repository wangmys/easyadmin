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
    protected $db_easyA = '';
    protected $db_bi = '';

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
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
    }

    /**
     * * @NodeAnotation(title="引流配置")
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

        
        // dump($head_field);die;
        // 传值 1
        $this->assign([
            'head_field' => $head_field,
            'province' => $this->logic->getProvince(),
            'list' => $list
        ]);
        // 传值 2
        $this->assign([
            'field' => $head,
            '_field' => array_column($head,'name'),
            'd_field' => $d_field2,
        ]);
        // 传值 3
        $this->assign([
            // 'field3' => [],
            'list3' => $this->zhouzhuanField(),
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


    // cwl 新增周转默认值   ea_customer_yinliu_zzconfig
    public function addZZdefault() {
        // $name = "偏热地区下装（春和秋）";

        $find_head = $this->db_easyA->table('ea_yinliu_dress_head')->where(['id' => input('id')])->find();

        if ($find_head) {
            $data = [
                ['店铺等级' => 'SS', '字段名' => $find_head['name'], 'index' => 10],
                ['店铺等级' => 'S', '字段名'  => $find_head['name'], 'index' => 9],
                ['店铺等级' => 'A', '字段名'  => $find_head['name'], 'index' => 8],
                ['店铺等级' => 'B', '字段名'  => $find_head['name'], 'index' => 7],
                ['店铺等级' => 'C', '字段名'  => $find_head['name'], 'index' => 6],
                ['店铺等级' => 'D', '字段名'  => $find_head['name'], 'index' => 5],
            ];
            foreach ($data as $key => $val) {
                $data[$key]['季节集合'] = $find_head['field'];
            }
            // 删除周转config
            $this->db_easyA->table('ea_customer_yinliu_zzconfig')->where(['字段名' => $find_head['name']])->delete();

            $this->db_easyA->table('ea_customer_yinliu_zzconfig')->strict(false)->insertAll($data);
        } else {
            echo '没找到';
        }
    }

    // 周转动态表头查询
    public function zhouzhuanField() {
        /*
        select 
            店铺等级
            ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='偏热地区下装（春和秋）' AND 店铺等级=m1.店铺等级) as `偏热地区下装（春和秋）`
            ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='春秋内搭' AND 店铺等级=m1.店铺等级) as `春秋内搭`
            ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='秋和冬内搭' AND 店铺等级=m1.店铺等级) as `秋和冬内搭`
            ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='偏冷地区下装（秋和冬）' AND 店铺等级=m1.店铺等级) as `偏冷地区下装（秋和冬）`
        from ea_customer_yinliu_zzconfig as m1
        group by 店铺等级 
        order by `index` DESC 
        */
        $select_head_field = $this->db_easyA->table('ea_yinliu_dress_head')->field('name')->where(1)->group('name')->order('id ASC')->select();
        // dump($select_head_field);
        $field = '';
        foreach ($select_head_field as $key => $val) {
            $field .= " ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='{$val['name']}' AND 店铺等级=m1.店铺等级) as `{$val['name']}`";
            // if ($key + 1 < count($select_head_field)) {
            //     $field .= "`" . $val['name'] . "`,";   
            // } else {
            //     $field .= "`" . $val['name'] . "`";   
            // }
        }

        $sql_动态表头 = "
            select 
                店铺等级
                {$field}
            from ea_customer_yinliu_zzconfig as m1
            group by 店铺等级 
            order by `index` DESC 
        ";
        $res = $this->db_easyA->query($sql_动态表头);
        // dump($res);
        return $res;
    }
}
