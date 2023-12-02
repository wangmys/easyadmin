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



// use EasyAdmin\annotation\ControllerAnnotation;
// use EasyAdmin\annotation\NodeAnotation;
// use app\common\controller\AdminController;


/**
 * Class Dress
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="引流库存预警")
 */
class Dress extends AdminController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    // public function __construct()
    {
        parent::__construct($app);
        $this->model = new Accessories;
        // 实例化逻辑类
        $this->logic = new DressLogic;
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
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
        $defaultFields  = ['省份','店铺名称','商品负责人','经营模式'];
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
                // $field_str = str_replace(',',' + ',$v['field']);
                $field_arr = explode(',',$v['field']);
                $field_str = '';
                foreach ($field_arr as $fk =>$fv){
                    $field_str .= " IFNULL($fv,0) +";
                }
                // 清空多余字符串
                $field_str = trim($field_str,'+');
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
                    $item['width'] = 100;
                    $item['laySearch'] = true;
                }
                // 设置条件下拉列表数据(省份/店铺名称/商品负责人)
                $item['selectList'] = $getSelectList[$v]??[];
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
//          $field_str = str_replace(',',' + ',$v['field']);
            $field_arr = explode(',',$v['field']);
            $field_str = '';
            foreach ($field_arr as $fk =>$fv){
                $field_str .= " IFNULL($fv,0) +";
            }
            // 清空多余字符串
            $field_str = trim($field_str,'+');
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
        // 动态表头字段 ea_yinliu_dress_head
        $head = $this->logic->dressHead->column('name,field,stock','id');
        // echo $head = $this->logic->dressHead->fetchSql()->column('name,field,stock','id');

        // dump($head);
        // die;

        $Date = date('Y-m-d');
        // 定义固定字段
        $defaultFields  = ['省份','店铺名称','商品负责人','经营模式'];
        $dynamic_head = array_column($head,'name');
        // dump($dynamic_head);
        // die;
        // 周转字段
        $zhouzhuan_head = [];
        foreach($dynamic_head as $k => $v) {
            $zhouzhuan_head[$k] = '周转' . $v;
        }
        // dump($dynamic_head);
        // dump($zhouzhuan_head);
        // die;
        // 合并字段成完整表头
        // $_field = array_merge($defaultFields, $dynamic_head);
        $_field = array_merge($defaultFields, $dynamic_head, $zhouzhuan_head);

        // dump($_field );die;
         // 获取预警库存查询条件
        $warStockItem = $this->logic->warStockItem();
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 固定字段
            $field = implode(',',$defaultFields);

            // 梦拿自己的动态头
            foreach ($head as $k=>$v){
                // 计算字段合并,多字段相加
//                $field_str = str_replace(',',' + ',$v['field']);
                // 计算字段合并,多字段相加
                $field_arr = explode(',',$v['field']);
                $field_str = '';
                foreach ($field_arr as $fk =>$fv){
                    $field_str .= " IFNULL($fv,0) +";
                }
                // 清空多余字符串
                $field_str = trim($field_str,'+');
                // 拼接查询字段
                $field .= ",( $field_str ) as {$v['name']}";
            }
            // 清空多余字符串
            $field = trim($field,',');
            // 数据集
            $list_all = [];
            // 根据每个省份设置的筛选查询

            // 周转配置
            $zhouzhuanFieldSql_1 = $this->zhouzhuanFieldSql_1($head);
            $zhouzhuanFieldSql_2 = $this->zhouzhuanFieldSql_2($head);
            // 周转
            $zhouzhuanFieldSql_3 = $this->zhouzhuanFieldSql_3($head);
            $zhouzhuanFieldSql_4 = $this->zhouzhuanFieldSql_4($head);
            $havingSql_1 = $this->havingSql_1($head);
            
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

                // 获取配饰门店排除列表 表：ea_system_config name = yinliu_store_list，过滤不要的门店
                $storeList = xmSelectInput(sysconfig('site', 'yinliu_store_list'));


                // die;
                // dump($vv['省份']);
                // echo arrToStr($vv['省份']);
                // dump($filters['商品负责人']);die;
                $map0 = " AND 店铺名称 <> '合计' AND 省份 <> '合计' AND 商品负责人 <> '合计'";
                
                // 系统配置的省份
                if (!empty($vv['省份'])) {
                    $map1Str = arrToStr($vv['省份']);
                    $map1 = " AND 省份 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }

    
                // echo $filters;die;
                if (!empty($filters['省份'])) {
                    // echo 1222222;die;
                // $q->whereIn('省份',$filters['省份']);
                    $map2Str = xmSelectInput($filters['省份']);
                    $map2 = " AND 省份 IN ({$map2Str})";
                } else {
                    $map2 = "";
                }

                // dump($map2);
                // die;
                if (!empty($filters['店铺名称'])) {
                    $map3Str = xmSelectInput($filters['店铺名称']);
                    $map3 = " AND 店铺名称 IN ({$map3Str})";
                } else {
                    $map3 = "";
                }
                if (!empty($filters['经营模式'])) {
                    $map4Str = xmSelectInput($filters['经营模式']);
                    $map4 = " AND 经营模式 IN ({$map4Str})";
                } else {
                    $map4 = "";
                }
                if (!empty($where['商品负责人'])) {
                    $map5Str = xmSelectInput($where['商品负责人']);
                    $map5 = " AND 商品负责人 IN ({$map5Str})";
                } else {
                    $user = session('admin');
                    if($user['id'] != AdminConstant::SUPER_ADMIN_ID) {
                        $map5Str = xmSelectInput($user['name']);
                        $map5 = " AND 商品负责人 IN ({$map5Str})";
                    } else {
                        if(!empty($filters['商品负责人'])){
                            $map5Str = xmSelectInput($filters['商品负责人']);
                            $map5 = " AND 商品负责人 IN ({$map5Str})";
                        } else {
                            $map5 = "";
                        }
                    }
                }
                
                $sql_主体 = "
                    select
                        y.省份,y.店铺名称,y.店铺等级,y.商品负责人,y.经营模式
                        {$zhouzhuanFieldSql_1}
                        {$zhouzhuanFieldSql_2}
                        {$zhouzhuanFieldSql_3}
                    from sp_customer_yinliu as y
                    left join (
                        {$zhouzhuanFieldSql_4}
                    ) as c on y.店铺等级 = c.店铺等级
                    where 1
                        and `Date` = '{$Date}'
                        and y.店铺名称 not in ($storeList)
                        {$map1}
                        {$map2}
                        {$map3}
                        {$map4}
                        {$map5}
                    group by 
                        y.店铺名称,y.店铺等级
                    HAVING
                        {$having}
                        {$havingSql_1}
                ";

                $list = $this->db_bi->query($sql_主体);

                // echo '<pre>';        
                // $vv['_data']['周转test2'] = 5;
                // print_r($vv['_data']);
                // 根据筛选条件,设置颜色是否标红
                $this->setStyle($list,$vv['_data']);
                $this->setStyleCwl($list, $dynamic_head);

                // die;
                // print_r($list);
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
        // dump($_field);die;
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
                if(in_array($v,['省份', '店铺名称', '商品负责人', '经营模式'])){
                    $item['search'] = 'xmSelect';
                    $item['width'] = 100;
                    $item['laySearch'] = true;
                }
                // 设置条件下拉列表数据(省份/店铺名称/商品负责人)
                $item['selectList'] = $getSelectList[$v]??[];
            };
            $cols[] = $item;
        }
        // dump($cols);die;
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

        // dump($cols);
        return $this->fetch('',['cols' => $cols,'_field' => $standard,'where' => $where]);
    }

    // 汇总表用 index的copy
    public function index_api()
    {
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $Date = date('Y-m-d');
        // 定义固定字段
        $defaultFields  = ['省份','店铺名称','商品负责人','经营模式'];
        $dynamic_head = array_column($head,'name');
        // 合并字段成完整表头
        $_field = array_merge($defaultFields,$dynamic_head);
         // 获取预警库存查询条件
        $warStockItem = $this->logic->warStockItem();
        // 获取参数
        $where = $this->request->get();
        // $where = input();

        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
        // 固定字段
        $field = implode(',',$defaultFields);
        foreach ($head as $k=>$v){
            // 计算字段合并,多字段相加
//                $field_str = str_replace(',',' + ',$v['field']);
            // 计算字段合并,多字段相加
            $field_arr = explode(',',$v['field']);
            $field_str = '';
            foreach ($field_arr as $fk =>$fv){
                $field_str .= " IFNULL($fv,0) +";
            }
            // 清空多余字符串
            $field_str = trim($field_str,'+');
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
            // print_r($list);
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
                if(!empty($filters['店铺名称'])){
                    $q->whereIn('店铺名称',$filters['店铺名称']);
                }
                if(!empty($filters['经营模式'])){
                    $q->whereIn('经营模式',$filters['经营模式']);
                }
                if(!empty($where['商品负责人'])){
                    $q->whereIn('商品负责人',$where['商品负责人']);
                }else{
                    $user = session('admin');
                    if($user['id'] != AdminConstant::SUPER_ADMIN_ID) {
                        $q->whereIn('商品负责人',$user['name']);
                    } else {
                        if(!empty($filters['商品负责人'])){
                            $q->whereIn('商品负责人',$filters['商品负责人']);
                        }
                    }
                }
            })->whereNotIn('店铺名称&省份&商品负责人','合计')->having($having)->order('省份,店铺名称,商品负责人')->select()->toArray();

            // echo $this->model->getLastSql();
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
        // echo '<pre>';
        // dump($list_all);
        foreach ($list_all as $key => $val) {
            $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val['店铺名称']])->update(['引流是否提醒' => '是']);
        }

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
            // $field_str = str_replace(',',' + ',$v['field']);
            $field_arr = explode(',',$v['field']);
            $field_str = '';
            foreach ($field_arr as $fk =>$fv){
                $field_str .= " IFNULL($fv,0) +";
            }
            // 清空多余字符串
            $field_str = trim($field_str,'+');
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
            'charge_list' => '商品负责人',
            'mathod_list' => '经营模式',
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

    /**
     * 根据引流配置,判断库存是否标红 cwl
     */
    public function setStyleCwl(&$list,$config)
    {
        // echo '<pre>';
        // print_r($list);

        // $new_config = [];
        // foreach ($config as $key => $val) {
        //     $config[$key] = '周转配置' . 
        // }
        // print_r($config); 
        // $d_field = sysconfig('site','dress_field');
        // $d_field = json_decode($d_field,true);
        // if(empty($list)) return $list;
        // foreach ($list as $k => $v){
        //     foreach ($v as $kk => $vv){
        //         $config = $d_field[$v['省份']];
        //         if(isset($config[$kk]) && !empty($config[$kk])){
        //             $vv = intval($vv);
        //             if($vv < $config[$kk]){
        //                 $list[$k]["_{$kk}"] = true;
        //             }
        //         }
        //     }
        // }

        if(empty($list)) return $list;

        foreach ($list as $key => $val) {
            /*
                ^ array:6 [▼
                    0 => "偏热地区下装（春和秋）"
                    1 => "春秋内搭"
                    2 => "秋和冬内搭"
                    3 => "偏冷地区下装（秋和冬）"
                    4 => "test2"
                    5 => "test3"
                ]
            */
            foreach ($config as $key2 => $val2) {
                if ($val['周转' . $val2] < $val['周转配置' . $val2]) {
                    $list[$key]["_周转" . $val2] = true;
                }
            }
            
        }
        // print_r($list);die;
        return $list;
    }

    public function test() {
        $Date = date('Y-m-d');
        $head = $this->logic->dressHead->column('name,field,stock','id');
        // 定义固定字段
        $defaultFields  = ['省份','店铺名称','商品负责人','经营模式'];
        $dynamic_head = array_column($head,'name');
        // 合并字段成完整表头
        $_field = array_merge($defaultFields,$dynamic_head);
         // 获取预警库存查询条件
        $warStockItem = $this->logic->warStockItem();
        // dump($warStockItem);die;
        // 获取参数
        $where = $this->request->get();
        if (1) {
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 固定字段
            $field = implode(',',$defaultFields);

            // 梦拿自己的动态头
            foreach ($head as $k=>$v){
                // 计算字段合并,多字段相加
//                $field_str = str_replace(',',' + ',$v['field']);
                // 计算字段合并,多字段相加
                $field_arr = explode(',',$v['field']);
                $field_str = '';
                foreach ($field_arr as $fk =>$fv){
                    $field_str .= " IFNULL($fv,0) +";
                }
                // 清空多余字符串
                $field_str = trim($field_str,'+');
                // 拼接查询字段
                $field .= ",( $field_str ) as {$v['name']}";
            }
            // 清空多余字符串
            $field = trim($field,',');
            // 数据集
            $list_all = [];
            // 根据每个省份设置的筛选查询

            // 周转配置
            $zhouzhuanFieldSql_1 = $this->zhouzhuanFieldSql_1($head);
            $zhouzhuanFieldSql_2 = $this->zhouzhuanFieldSql_2($head);
            // 周转
            $zhouzhuanFieldSql_3 = $this->zhouzhuanFieldSql_3($head);
            $zhouzhuanFieldSql_4 = $this->zhouzhuanFieldSql_4($head);
            $havingSql_1 = $this->havingSql_1($head);

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

                // 获取配饰门店排除列表 表：ea_system_config name = yinliu_store_list，过滤不要的门店
                $storeList = xmSelectInput(sysconfig('site', 'yinliu_store_list'));


                // die;
                // dump($vv['省份']);
                // echo arrToStr($vv['省份']);
                // dump($filters['商品负责人']);die;
                $map0 = " AND 店铺名称 <> '合计' AND 省份 <> '合计' AND 商品负责人 <> '合计'";
                if (!empty($vv['省份'])) {
                    $map1Str = arrToStr($vv['省份']);
                    $map1 = " AND 省份 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }

                if (!empty($filters['省份'])) {
                // $q->whereIn('省份',$filters['省份']);
                    $map2Str = arrToStr($filters['省份']);
                    $map2 = " AND 省份 IN ({$map2Str})";
                } else {
                    $map2 = "";
                }
                if (!empty($filters['店铺名称'])) {
                    // $q->whereIn('店铺名称',$filters['店铺名称']);
                    $map3Str = arrToStr($filters['店铺名称']);
                    $map3 = " AND 店铺名称 IN ({$map3Str})";
                } else {
                    $map3 = "";
                }
                if (!empty($filters['经营模式'])) {
                    // $q->whereIn('经营模式',$filters['经营模式']);
                    $map4Str = arrToStr($filters['经营模式']);
                    $map4 = " AND 经营模式 IN ({$map4Str})";
                } else {
                    $map4 = "";
                }
                if (!empty($where['商品负责人'])) {
                    // $q->whereIn('商品负责人',$where['商品负责人']);
                    $map5Str = arrToStr($where['商品负责人']);
                    $map5 = " AND 商品负责人 IN ({$map5Str})";
                } else {
                    $user = session('admin');
                    if($user['id'] != AdminConstant::SUPER_ADMIN_ID) {
                        // $q->whereIn('商品负责人',$user['name']);
                        $map5Str = arrToStr($user['name']);
                        $map5 = " AND 商品负责人 IN ({$map5Str})";
                    } else {
                        if(!empty($filters['商品负责人'])){
                            $q->whereIn('商品负责人',$filters['商品负责人']);
                            $map5Str = arrToStr($filters['商品负责人']);
                            $map5 = " AND 商品负责人 IN ({$map5Str})";
                        } else {
                            $map5 = "";
                        }
                    }
                }

                $sql_主体 = "
                    select
                        y.店铺名称,y.店铺等级,y.店铺名称,y.店铺等级,y.商品负责人,y.经营模式
                        {$zhouzhuanFieldSql_1}
                        {$zhouzhuanFieldSql_2}
                        {$zhouzhuanFieldSql_3}
                    from sp_customer_yinliu as y
                    left join (
                        {$zhouzhuanFieldSql_4}
                    ) as c on y.店铺等级 = c.店铺等级
                    where 1
                        and `Date` = '{$Date}'
                        and y.店铺名称 not in ($storeList)
                        {$map1}
                        {$map2}
                        {$map3}
                        {$map4}
                        {$map5}
                    group by 
                        y.店铺名称,y.店铺等级
                    HAVING
                        {$having}
                        {$havingSql_1}
                ";

                echo '<br>------<br>';
                // $list = $this->db_bi->query($sql_梦园的转sql字符串);
                // echo $this->model->getLastSql();
                // 根据筛选条件,设置颜色是否标红
                // $this->setStyle($list,$vv['_data']);
                // $list_all = array_merge($list_all,$list);
            }
        }
    }

    public function test2() {
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $Date = date('Y-m-d');
        echo '<pre>';
        // print_r($head);
        // 周转配置
        $zhouzhuanFieldSql_1 = $this->zhouzhuanFieldSql_1($head);

        $zhouzhuanFieldSql_2 = $this->zhouzhuanFieldSql_2($head);

        // 周转
        $zhouzhuanFieldSql_3 = $this->zhouzhuanFieldSql_3($head);

        $zhouzhuanFieldSql_4 = $this->zhouzhuanFieldSql_4($head);

        $havingSql_1 = $this->havingSql_1($head);
        echo $sql_主体 = "
            select
                y.店铺名称,y.店铺等级,y.店铺名称,y.店铺等级,y.商品负责人,y.经营模式
                {$zhouzhuanFieldSql_1}

                {$zhouzhuanFieldSql_2}
                    
                {$zhouzhuanFieldSql_3}
            from sp_customer_yinliu as y
            left join (
                {$zhouzhuanFieldSql_4}
            ) as c on y.店铺等级 = c.店铺等级
            where 1
                and y.店铺名称 in ('海口一店','三亚一店')
                and `Date` = '2023-11-22'
            group by y.店铺名称,y.店铺等级

            HAVING
                ((偏热地区下装（春和秋） < 150 
                        OR 偏热地区下装（春和秋） IS NULL 
                        ) 
                    OR (春秋内搭 < 100 OR 春秋内搭 IS NULL ) 
                    OR (秋和冬内搭 < - 10 OR 秋和冬内搭 IS NULL ) 
                OR (偏冷地区下装（秋和冬） < - 10 OR 偏冷地区下装（秋和冬） IS NULL )) 

                {$havingSql_1}
        ";
        // $select = $this->db_bi->query($sql);
        // dump($select);
    }

    // 周转配置拼接
    private function zhouzhuanFieldSql_1($select_head_field = []) {
        // 重置数组下标
        // $select_head_field = array_merge($select_head_field);
        $field = '';
        foreach ($select_head_field as $key => $val) {
            $field .= ",c.`{$val['name']}` as `周转配置{$val['name']}`";
        }
        return $field;
    }

    // IFNULL拼接
    private function zhouzhuanFieldSql_2($select_head_field = []) {
        // 重置数组下标
        // $select_head_field = array_merge($select_head_field);
        $field = '';
        foreach ($select_head_field as $key => $val) {
            $res = explode(',', $val['field']);

            $str1 = "";
            $str2 =  $val['name'];
            foreach ($res as $k2 => $v2) {
                if ($k2 + 1 < count($res)) {
                    $str1 .= "IFNULL(`{$v2}`, 0 )+";
                } else {
                    $str1 .= "IFNULL(`{$v2}`, 0 )";
                }
            }
            // $field .= " ,( IFNULL(春季下装, 0 )+ IFNULL(秋季下装, 0 )) AS 偏热地区下装（春和秋）";
            $field .= " ,( $str1 ) AS `$str2`";

        }
        return $field;
    }

    // 周转拼接
    private function zhouzhuanFieldSql_3($select_head_field = []) {
        // 重置数组下标
        // $select_head_field = array_merge($select_head_field);
        $field = '';
        foreach ($select_head_field as $key => $val) {
            $res = explode(',', $val['field']);

            $str1 = "";
            $str2 = "";
            $str3 = '周转' . $val['name'];
            foreach ($res as $k2 => $v2) {
                if ($k2 + 1 < count($res)) {
                    $str1 .= '`' . $v2 . '` + ';
                    $str2 .= '`' . $v2 . '周销` + ';
                } else {
                    $str1 .= '`' . $v2 . '`';
                    $str2 .= '`' . $v2 . '周销`';
                }
            }
            $field .= " ,ROUND(({$str1}) / ({$str2}), 1) as `{$str3}`";
        }
        return $field;
    }

    // 配置附表拼接
    private function zhouzhuanFieldSql_4($select_head_field = []) {
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
        // $select_head_field = $this->db_easyA->table('ea_yinliu_dress_head')->field('name')->where(1)->group('name')->order('id ASC')->select();
        // dump($select_head_field);
        $field = '';
        foreach ($select_head_field as $key => $val) {
            $field .= " ,(select 周转 from ea_customer_yinliu_zzconfig where 字段名='{$val['name']}' AND 店铺等级=m1.店铺等级) as `{$val['name']}`";
        }

        $sql_动态表头 = "
            select 
                店铺等级
                {$field}
            from ea_customer_yinliu_zzconfig as m1
            group by 店铺等级 
            order by `index` DESC 
        ";
        // $res = $this->db_easyA->query($sql_动态表头);
        // dump($res);
        return $sql_动态表头;
    }

    // having拼接
    private function havingSql_1($select_head_field = []) {
        // 重置数组下标
        // $select_head_field = array_merge($select_head_field);
        // dump($select_head_field);die;
        $field = '';
        foreach ($select_head_field as $key => $val) {
            // echo $key;
            // echo '<br>';
            $field .= " OR `周转{$val['name']}` < `周转配置{$val['name']}` ";
        }


        // die;
        return $field;
    }

}
