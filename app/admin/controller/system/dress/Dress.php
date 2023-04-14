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
 * @ControllerAnnotation(title="配饰库存")
 */
class Dress extends AdminController
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
     * 数据筛选
     */
    public function index()
    {
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        $Date = date('Y-m-d');
        // 固定字段
        $_field_default = ['省份','店铺名称','商品负责人'];
        // 合并字段成完整表头
        $_field = array_merge($_field_default,array_column($head,'name'));
        if ($this->request->isAjax()) {
            $get = $this->request->get('', null, null);
            // 筛选
            $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
            $filters = json_decode($filters, true);
            // 查询字段
            $field = implode(',',$_field_default);
            // 查询条件
            $having = '';
            foreach ($head as $k=>$v){
                // 计算字段合并,多字段相加
                $field_str = str_replace(',',' + ',$v['field']);
                // 拼接查询字段
                $field .= ",( $field_str ) as {$v['name']}";
                // 拼接过滤条件
                $having .= " {$v['name']} < {$v['stock']} or ";
            }
            // 清空多余字符串
            $field = trim($field,',');
            $having = "(".trim($having,'or ').")";


            // 省查询


            // 查询数据
            $list = $this->model->field($field)->where([
                'Date' => $Date
            ])->where(function ($q)use($filters){
                if(!empty($filters['省份'])){
                   $q->whereIn('省份',$filters['省份']);
                }
                if(!empty($filters['店铺名称'])){
                   $q->whereIn('店铺名称',$filters['店铺名称']);
                }
                if(!empty($filters['商品负责人'])){
                   $q->whereIn('商品负责人',$filters['商品负责人']);
                }
            })->whereNotIn('店铺名称&省份&商品负责人','合计')->having($having)->order('省份,店铺名称,商品负责人')->select()->toArray();





            // 提取库存筛选条件
            $config = $this->config($head);
            // 根据筛选条件,设置颜色是否标红
            $this->setStyle($list,$config);
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list),
                    'data'  => $list,
                    'config' => $config
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
                'width' => 134,
                'search' => false,
                'title' => $v,
                'align' => 'center',
            ];
            // 固定字段可筛选
            if(in_array($v,$_field_default)){
                $item['fixed'] = 'left';
                if($v == '省份'){
                    $item['search'] = 'xmSelect';
                }
                // 设置条件下拉列表数据(省份/店铺名称/商品负责人)
                $item['selectList'] = $getSelectList[$v];
            };
            $cols[] = $item;
        }
        return $this->fetch('',['cols' => $cols,'_field' => $_field,'config' => $this->config($head)]);
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
     * 导出
     */
    public function index_export()
    {
        $get = $this->request->get('', null, null);
        // 筛选
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $filters = json_decode($filters, true);
        // 获取今日日期
        $Date = date('Y-m-d');
        // 动态表头字段
        $head = $this->logic->dressHead->column('name,field,stock','id');
        // 固定字段
        $_field_default = ['省份','店铺名称','商品负责人'];
        // 合并字段成完整表头
        $_field = array_merge($_field_default,array_column($head,'name'));
        $where = [
            'Date' => $Date
        ];
        // 查询字段
        $field = implode(',',$_field_default);
        // 查询条件
        $having = '';
        foreach ($head as $k=>$v){
            // 计算字段合并,多字段相加
            $field_str = str_replace(',',' + ',$v['field']);
            // 拼接查询字段
            $field .= ",( $field_str ) as {$v['name']}";
            // 拼接过滤条件
            $having .= " {$v['name']} < {$v['stock']} or ";
        }
        // 清空多余字符串
        $field = trim($field,',');
        $having = "(".trim($having,'or ').")";

        // 查询数据
        $list = $this->model->field($field)->where([
            'Date' => $Date
        ])->where(function ($q)use($filters){
            if(!empty($filters['省份'])){
               $q->whereIn('省份',$filters['省份']);
            }
            if(!empty($filters['店铺名称'])){
               $q->whereIn('店铺名称',$filters['店铺名称']);
            }
            if(!empty($filters['商品负责人'])){
               $q->whereIn('商品负责人',$filters['商品负责人']);
            }
        })->whereNotIn('店铺名称&省份&商品负责人','合计')->having($having)->order('省份,店铺名称,商品负责人')->select()->toArray();
        // 设置标题头
        $header = [];
        if($list){
            $header = array_map(function($v){ return [$v,$v]; }, array_keys($list[0]));
        }
        // 提取库存筛选条件
        $config = $this->config($head);
        // 根据筛选条件,设置颜色是否标红
        $this->setStyle($list,$config);
        $fileName = time();
        return Excel::exportData($list, $header, $fileName, 'xlsx','',[],'dress');
    }

    /**
     * 获取条件列表
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
