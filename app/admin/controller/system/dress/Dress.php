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
use app\common\logic\inventory\DressLogic;
use jianyan\excel\Excel;


/**
 * Class Dress
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="引流库存预警")
 */
class Dress extends AdminController
{

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
     * @NodeAnotation(title="引流总览")
     * 按照自定义省份进行筛选
     * @return mixed|\think\response\Json
     */
    public function list()
    {
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $Date = date('Y-m-d');
        // 定义固定字段
        $defaultFields  = ['省份','店铺名称','商品负责人'];
        $dynamic_head = array_column($head,'name');
        // 合并字段成完整表头
        $_field = array_merge($defaultFields,$dynamic_head);
         // 获取预警库存查询条件
        $warStockItem = $this->logic->warStockItem();
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 固定字段
            $field = implode(',',$defaultFields);
            foreach ($head as $k=>$v){
                // 计算字段合并,多字段相加
                $field_str = str_replace(',',' + ',$v['field']);
                // 拼接查询字段
                $field .= ",( $field_str ) as {$v['name']}";
            }
            // 清空多余字符串
            $field = trim($field,',');
            // 数据集
            $list_all = [];
            // 根据每个省份设置的筛选查询
            foreach($warStockItem as $kk => $vv){
                // 查询条件
                $having = '';
                foreach ($vv['_data'] as $k=>$v){
                    // 表头有的字段才能筛选
                    if(in_array($k,$dynamic_head)){
                        // 拼接过滤条件
                        $having .= " {$k} < {$v} or ";
                    }
                }
                $having = "(".trim($having,'or ').")";

                // 筛选门店
                $list = $this->logic->setStoreFilter($this->model);
                // 查询数据
                $list = $list->field($field)->where([
                    'Date' => $Date
                ])->where(function ($q)use($vv,$filters,$where){
                    if(!empty($vv['省份'])){
                       $q->whereIn('省份',$vv['省份']);
                    }
                    if(!empty($filters['省份'])){
                       $q->whereIn('省份',$filters['省份']);
                    }
                    if(!empty($where['商品负责人'])){
                       $q->whereIn('商品负责人',$where['商品负责人']);
                    }
                })->whereNotIn('店铺名称&省份&商品负责人','合计')->order('省份,店铺名称,商品负责人')->select()->toArray();
                // 根据筛选条件,设置颜色是否标红
                $this->setStyle($list,$vv['_data']);
                $list_all = array_merge($list_all,$list);
            }
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list_all),
                    'data'  => $list_all
                ];
            return json($data);
        }
        // 获取搜索条件列表(省列表,店铺列表,商品负责人列表)
        $getSelectList = $this->getSelectList();
        // 前端表格数据
        $cols = [];
        // 根据数据,渲染前端表格表头
        foreach ($_field as $k=>$v){
            $length = substr_count($v,'_');
            $item = [
                'field' => $v,
                'minWidth' => 134,
                'search' => false,
                'title' => $v,
                'align' => 'center',
            ];
            // 固定字段可筛选
            if(in_array($v,$defaultFields)){
                $item['fixed'] = 'left';
                if($v == '省份'){
                    $item['search'] = 'xmSelect';
                    $item['laySearch'] = true;
                }
                // 设置条件下拉列表数据(省份/店铺名称/商品负责人)
                $item['selectList'] = $getSelectList[$v];
            };
            $cols[] = $item;
        }
        // 标准
        $standard = [];
        foreach ($warStockItem as $key => $v){
            $num = count($v['省份']);
            if($num > 2){
                $item = $v['省份'];
                $standard[$key]['省份'] = implode(',',[$item[0],$item[1]]).'...';
            }else{
                $standard[$key]['省份'] = implode(',',$v['省份']);
            }
            $standard[$key]['省份数量'] = $num;
            $standard[$key]['描述'] = '省份库存标准';
            $standard[$key] = array_merge($standard[$key],$v['_data']);
        }
        return $this->fetch('',['cols' => $cols,'_field' => $standard,'where' => $where]);
    }

    /**
     * 引流服饰导出
     */
    public function list_export()
    {
        // 获取参数
        $where = $this->request->get();
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
        // 获取今日日期
        $Date = date('Y-m-d');
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        // 固定字段
        $defaultFields = ['省份','店铺名称','商品负责人'];
        // 固定字段
        $field = implode(',',$defaultFields);
        foreach ($head as $k=>$v){
            // 计算字段合并,多字段相加
            $field_str = str_replace(',',' + ',$v['field']);
            // 拼接查询字段
            $field .= ",( $field_str ) as {$v['name']}";
        }
        // 数据集
        $list_all = [];
        // 省查询
        $warStockItem = $this->logic->warStockItem();
        // 导出表头
        $table_head = [];
        // 根据每个省份设置的筛选查询
        foreach($warStockItem as $kk => $vv){
            // 增加排除门店筛选
            $list = $this->logic->setStoreFilter($this->model);
            // 查询数据
            $list = $list->field($field)->where([
                'Date' => $Date
            ])->where(function ($q)use($vv,$filters,$where){
                if(!empty($vv['省份'])){
                   $q->whereIn('省份',$vv['省份']);
                }
                if(!empty($filters['省份'])){
                   $q->whereIn('省份',$filters['省份']);
                }
                if(!empty($where['商品负责人'])){
                   $q->whereIn('商品负责人',$where['商品负责人']);
                }
            })->whereNotIn('店铺名称&省份&商品负责人','合计')->order('省份,店铺名称,商品负责人')->select()->toArray();
            if(!empty($list) && empty($table_head)){
                $table_head = array_keys($list[0]);
            }
            // 根据筛选条件,设置颜色是否标红
            $this->setStyle($list,$vv['_data']);
            $list_all = array_merge($list_all,$list);
        }
        // 设置标题头
        $header = [];
        if($list_all){
            $header = array_map(function($v){ return [$v,$v]; },$table_head);
        }
        $fileName = time();
        return Excel::exportData($list_all, $header, $fileName, 'xlsx','',[],'dress');
    }


    /**
     * @NodeAnotation(title="店铺引流结果")
     * 按照自定义省份进行筛选
     * @return mixed|\think\response\Json
     */
    public function index()
    {
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $Date = date('Y-m-d');
        // 定义固定字段
        $defaultFields  = ['省份','店铺名称','商品负责人'];
        $dynamic_head = array_column($head,'name');
        // 合并字段成完整表头
        $_field = array_merge($defaultFields,$dynamic_head);
         // 获取预警库存查询条件
        $warStockItem = $this->logic->warStockItem();
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 固定字段
            $field = implode(',',$defaultFields);
            foreach ($head as $k=>$v){
                // 计算字段合并,多字段相加
                $field_str = str_replace(',',' + ',$v['field']);
                // 拼接查询字段
                $field .= ",( $field_str ) as {$v['name']}";
            }
            // 清空多余字符串
            $field = trim($field,',');
            // 数据集
            $list_all = [];
            // 根据每个省份设置的筛选查询
            foreach($warStockItem as $kk => $vv){
                // 查询条件
                $having = '';
                foreach ($vv['_data'] as $k=>$v){
                    // 表头有的字段才能筛选
                    if(in_array($k,$dynamic_head)){
                        // 拼接过滤条件
                        $having .= " ({$k} < {$v} or $k is null) or ";
                    }
                }
                $having = "(".trim($having,'or ').")";

                // 筛选门店
                $list = $this->logic->setStoreFilter($this->model);
                // 查询数据
                $list = $list->field($field)->where([
                    'Date' => $Date
                ])->where(function ($q)use($vv,$filters,$where){
                    if(!empty($vv['省份'])){
                       $q->whereIn('省份',$vv['省份']);
                    }
                    if(!empty($filters['省份'])){
                       $q->whereIn('省份',$filters['省份']);
                    }
                    if(!empty($where['商品负责人'])){
                       $q->whereIn('商品负责人',$where['商品负责人']);
                    }
                })->whereNotIn('店铺名称&省份&商品负责人','合计')->having($having)->order('省份,店铺名称,商品负责人')->select()->toArray();
                // 根据筛选条件,设置颜色是否标红
                $this->setStyle($list,$vv['_data']);
                $list_all = array_merge($list_all,$list);
            }
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list_all),
                    'data'  => $list_all
                ];
            return json($data);
        }
        // 获取搜索条件列表(省列表,店铺列表,商品负责人列表)
        $getSelectList = $this->getSelectList();
        // 前端表格数据
        $cols = [];
        // 根据数据,渲染前端表格表头
        foreach ($_field as $k=>$v){
            $length = substr_count($v,'_');
            $item = [
                'field' => $v,
                'minWidth' => 134,
                'search' => false,
                'title' => $v,
                'align' => 'center',
            ];
            // 固定字段可筛选
            if(in_array($v,$defaultFields)){
                $item['fixed'] = 'left';
                if($v == '省份'){
                    $item['search'] = 'xmSelect';
                    $item['laySearch'] = true;
                }
                // 设置条件下拉列表数据(省份/店铺名称/商品负责人)
                $item['selectList'] = $getSelectList[$v];
            };
            $cols[] = $item;
        }
        // 标准
        $standard = [];
        foreach ($warStockItem as $key => $v){
            $num = count($v['省份']);
            if($num > 2){
                $item = $v['省份'];
                $standard[$key]['省份'] = implode(',',[$item[0],$item[1]]).'...';
            }else{
                $standard[$key]['省份'] = implode(',',$v['省份']);
            }
            $standard[$key]['省份数量'] = $num;
            $standard[$key]['描述'] = '省份库存标准';
            $standard[$key] = array_merge($standard[$key],$v['_data']);
        }
        return $this->fetch('',['cols' => $cols,'_field' => $standard,'where' => $where]);
    }


    /**
     * 引流服饰的可用库存与在途库存
     * @return \think\response\Json
     */
    public function stock()
    {
        // 表头
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $data = [$this->config($head)];
        $list = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($data),
                'data'  => $data,
            ];
        return json($list);
    }

    /**
     * 引流服饰导出
     */
    public function index_export()
    {
        // 获取参数
        $where = $this->request->get();
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
        // 获取今日日期
        $Date = date('Y-m-d');
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        // 固定字段
        $defaultFields = ['省份','店铺名称','商品负责人'];
        // 固定字段
        $field = implode(',',$defaultFields);
        foreach ($head as $k=>$v){
            // 计算字段合并,多字段相加
            $field_str = str_replace(',',' + ',$v['field']);
            // 拼接查询字段
            $field .= ",( $field_str ) as {$v['name']}";
        }
        // 数据集
        $list_all = [];
        // 省查询
        $warStockItem = $this->logic->warStockItem();
        // 导出表头
        $table_head = [];
        // 根据每个省份设置的筛选查询
        foreach($warStockItem as $kk => $vv){
            // 查询条件
            $having = '';
            foreach ($vv['_data'] as $k=>$v){
                // 拼接过滤条件
                $having .= " ({$k} < {$v} or $k is null) or ";
            }
            $having = "(".trim($having,'or ').")";
            // 增加排除门店筛选
            $list = $this->logic->setStoreFilter($this->model);
            // 查询数据
            $list = $list->field($field)->where([
                'Date' => $Date
            ])->where(function ($q)use($vv,$filters,$where){
                if(!empty($vv['省份'])){
                   $q->whereIn('省份',$vv['省份']);
                }
                if(!empty($filters['省份'])){
                   $q->whereIn('省份',$filters['省份']);
                }
                if(!empty($where['商品负责人'])){
                   $q->whereIn('商品负责人',$where['商品负责人']);
                }
            })->whereNotIn('店铺名称&省份&商品负责人','合计')->having($having)->order('省份,店铺名称,商品负责人')->select()->toArray();
            if(!empty($list) && empty($table_head)){
                $table_head = array_keys($list[0]);
            }
            // 根据筛选条件,设置颜色是否标红
            $this->setStyle($list,$vv['_data']);
            $list_all = array_merge($list_all,$list);
        }
        // 设置标题头
        $header = [];
        if($list_all){
            $header = array_map(function($v){ return [$v,$v]; },$table_head);
        }
        $fileName = time();
        return Excel::exportData($list_all, $header, $fileName, 'xlsx','',[],'dress');
    }

    /**
     * 获取条件列表
     * @return array
     */
    public function getSelectList()
    {
        $default_select = [];
        $fields = [
             // 设置省份列表
            'province_list' => '省份',
            // 设置省份列表
            'shop_list' => '店铺名称',
            // 设置省份列表
            'charge_list' => '商品负责人'
        ];
        $model = (new \app\admin\model\dress\Accessories);
        foreach ($fields as $k => $v){
            $list = $model->group($v)->whereNotIn($v,'合计')->column($v);
            $default_select[$v] =  array_combine($list,$list);
        }
        return $default_select;
    }

    /**
     * 提取引流配置
     * @param $data
     * @return array|false
     */
    public function config($data)
    {
        $key = array_column($data,'name');
        $val = array_column($data,'stock');
        return array_combine($key,$val);
    }
    
    /**
     * 根据引流配置,判断库存是否标红
     */
    public function setStyle(&$list,$config)
    {
        if(empty($list)) return $list;
        foreach ($list as $k => $v){
            foreach ($v as $kk => $vv){
                if(isset($config[$kk]) && !empty($config[$kk])){
                    $vv = intval($vv);
                    if($vv < $config[$kk]){
                        $list[$k]["_{$kk}"] = true;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 根据引流配置,判断库存是否标红
     */
    public function setStyle2(&$list,$config)
    {
        $d_field = sysconfig('site','dress_field');
        $d_field = json_decode($d_field,true);
        if(empty($list)) return $list;
        foreach ($list as $k => $v){
            foreach ($v as $kk => $vv){
                $config = $d_field[$v['省份']];
                if(isset($config[$kk]) && !empty($config[$kk])){
                    $vv = intval($vv);
                    if($vv < $config[$kk]){
                        $list[$k]["_{$kk}"] = true;
                    }
                }
            }
        }
        return $list;
    }
}
