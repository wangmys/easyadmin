<?php


namespace app\admin\controller\system\dress;


use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\admin\service\accessories\AccessoriesService;
use app\common\logic\accessories\AccessoriesLogic;
use jianyan\excel\Excel;
use app\common\logic\execl\PHPExecl;


/**
 * Class Accessories
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰2.0预警")
 */
class Index extends AdminController
{

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = AccessoriesService::instance();
        $this->logic = new AccessoriesLogic;
    }

    /**
     * @NodeAnotation(title="配饰总览2.0")
     */
    public function index()
    {
        $get = $this->request->get();
        // 请求
        if ($this->request->isAjax()) {
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 查询数据
            $table_data = $this->service->getTableBody();
            // 返回数据
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($table_data),
                'data'  => $table_data
            ];
            return json($data);
        }

        // 一级表头
        $table_head_1 = [];
        // 二级表头
        $table_head_2 = [];
        $head_field_1 = $this->service->getFixField('',1);
        $head_field_2 = $this->service->getTableField();
        $head_field = array_merge($head_field_1,$head_field_2);
        foreach ($head_field as $k => $v){
            if(is_array($v)){
                $item = [
                    'title' => $v['name'],
                    'colspan' => 2,
                    'align' => 'center'
                ];
            }else{
                $item = [
                    'field' => $v,
                    'title' => $k,
                    'rowspan' => 2,
                    'fixed' => 'left',
                    'width' => 90,
                    'align' => 'center'
                ];
                if(in_array($k,['店铺ID'])){
                    $item['width'] = 115;
                    continue;
                }
            }
            $table_head_1[] = $item;
        }

        foreach ($head_field_2 as $key => $val){
            $table_head_2[] = [
                'field' => $val['name'],
                'title' => '库存',
                'width' => 80,
                'align' => 'center'
            ];
            $table_head_2[] = [
                'field' => '_'.$val['name'],
                'title' => '周转',
                'width' => 80,
                'align' => 'center',
            ];;
        }
        $cols = [
            $table_head_1,
            $table_head_2
        ];

        $this->assign([
            'get' => json_encode($get),
            'cols' => json_encode($cols),
            'head_field_2' => $head_field_2
        ]);
        return $this->fetch();
    }

    /**
     * 配饰总览2.0导出
     */
    public function index_export()
    {
        // 查询数据
        $list = $this->service->getTableBody();
        // 固定表头
        $column_1 = $this->service->getFixField('',1);
        // 动态表头
        $column_2 = array_column($this->service->getTableField(),'name');
        // 设置标题头
        $header = [
            'column_1' => $column_1,
            'column_2' => $column_2
        ];
        $exec = new PHPExecl();
        // 导出
        $exec->export($header,$list);
        exit();
    }

    /**
    * @NodeAnotation(title="配饰结果2.0")
    */
    public function list()
    {
        $get = $this->request->get();
        // 请求
        if ($this->request->isAjax()) {
            // 筛选
            $filters = json_decode($this->request->get('filter', '{}',null), true);
            // 查询数据
            $table_data = $this->service->getTableBody('',1);
            // 返回数据
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($table_data),
                'data'  => $table_data
            ];
            return json($data);
        }

        // 一级表头
        $table_head_1 = [];
        // 二级表头
        $table_head_2 = [];
        $head_field_1 = $this->service->getFixField('',1);
        $head_field_2 = $this->service->getTableField();
        $head_field = array_merge($head_field_1,$head_field_2);
        foreach ($head_field as $k => $v){
            if(is_array($v)){
                $item = [
                    'title' => $v['name'],
                    'colspan' => 2,
                    'align' => 'center'
                ];
            }else{
                $item = [
                    'field' => $v,
                    'title' => $k,
                    'rowspan' => 2,
                    'fixed' => 'left',
                    'width' => 90,
                    'align' => 'center'
                ];
                if(in_array($k,['店铺ID'])){
                    $item['width'] = 115;
                    continue;
                }
            }
            $table_head_1[] = $item;
        }

        foreach ($head_field_2 as $key => $val){
            $table_head_2[] = [
                'field' => $val['name'],
                'title' => '库存',
                'width' => 80,
                'align' => 'center'
            ];
            $table_head_2[] = [
                'field' => '_'.$val['name'],
                'title' => '周转',
                'width' => 80,
                'align' => 'center',
            ];;
        }
        $cols = [
            $table_head_1,
            $table_head_2
        ];

        $this->assign([
            'get' => json_encode($get),
            'cols' => json_encode($cols),
            'head_field_2' => $head_field_2
        ]);
        return $this->fetch();
    }

    /**
     * 配饰结果2.0导出
     */
    public function list_export()
    {
        // 查询数据
        $list = $this->service->getTableBody('',1);
        // 固定表头
        $column_1 = $this->service->getFixField('',1);
        // 动态表头
        $column_2 = array_column($this->service->getTableField(),'name');
        // 设置标题头
        $header = [
            'column_1' => $column_1,
            'column_2' => $column_2
        ];
        $exec = new PHPExecl();
        // 导出
        $exec->export($header,$list);
        exit();
    }

    /**
     * 配饰的可用库存与在途库存
     * @return \think\response\Json
     */
    public function stock()
    {
        // 表头数据
        $header = $this->service->getTableField();
        $head = array_column($header,'name');
        $order = '';
        foreach ($head as $key => $val){
            $order .= "'$val',";
        }
        $order = trim($order,',');
        $Date = date('Y-m-d');
        // 查询表数据
        $data = Db::connect("mysql2")
            ->table('accessories_warehouse_stock_2')
            ->where([
                'Date' => $Date
            ])->column("可用库存Quantity as available_stock,采购在途库存Quantity as transit_stock,分类 as cate",'分类');
        $res = ['available_stock' => [],'transit_stock' => []];
        foreach ($res as $k => $v){
            foreach ($header as $key => $val){
                $key_arr = explode(',',$val['field']);
                $sum_total = 0;
                foreach ($key_arr as $kk => $vv){
                    if(empty($data[$vv])){
                        $data[$vv] = [
                            'available_stock' => 0,
                            'transit_stock' => 0
                        ];
                    }
                    $sum_total += $data[$vv][$k];
                }

                $res[$k]['Date'] = date('Y-m-d',strtotime('-1day'));
                $res[$k]['type'] = $k=='available_stock'?'仓库可用库存':'仓库在途库存';
                $res[$k]['text'] = '配饰';
                $res[$k][$val['name']] = $sum_total;
            }
        }
        $list = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($res),
                'data'  => $res,
            ];
        return json($list);
    }

    /**
     * @NodeAnotation(title="配饰预警2.0标准")
     */
    public function standard()
    {
        // 查询所有的店铺等级
        $levelList = $this->logic->getLevel();
        if ($this->request->isAjax()) {
            // 字段
            $field = $this->logic->getTableRow();
            $data = [
                'provinceList' => $levelList,
                'field' => $field
            ];
            return json($data);
        }
        // 查询配置项表头
        $head = $this->logic->getSysHead();
        // 查询已保存数据
        $data = $this->logic->warStock->column('id,level,content','level');
        $d_field = [];
        foreach ($levelList as $kk => $vv){
            $_kk = $vv['name'];
            $d_field[$_kk]['店铺等级'] = $_kk;
            // 已保存数值
            $item = !empty($data[$_kk])?json_decode($data[$_kk]['content'],true):[];
            // 获取省份
            foreach ($head as $k=>$v){
                $v_key = $v['name'];
                if(isset($item[$v_key])){
                    $d_field[$_kk][$v['name']] = $item[$v_key];
                }else{
                    $d_field[$_kk][$v['name']] = $v['stock']??0;
                }
            }
        }

        $this->assign([
            'field' => $head,
            '_field' => array_column($head,'name'),
            'd_field' => $d_field,
        ]);
        return $this->fetch();
    }
}