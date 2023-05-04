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


/**
 * Class Accessories
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰库存/周转预警")
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
                    'width' => 110,
                    'align' => 'center'
                ];
                if($k=='店铺ID'){
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
            'cols' => json_encode($cols)
        ]);
        return $this->fetch();
    }

    /**
     * 展示配饰标准
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

    /**
     * 配饰问题导出
     */
    public function index_export()
    {
        $get = $this->request->get();
        // 获取今日日期
        $Date = date('Y-m-d');
        // 指定查询字段
        $field = array_merge(['省份','店铺名称','商品负责人'],AdminConstant::ACCESSORIES_LIST);
        $where = [
            'Date' => $Date
        ];
        if(!empty($get['商品负责人'])){
            $where['商品负责人'] = $get['商品负责人'];
        }
        // 查询指定
        $list = $this->model->field($field)->where($where)->whereNotIn('店铺名称&省份&商品负责人','合计');
        $list = $this->logic->setStoreFilter($list,'accessories_store_list');
        $list = $list->order('省份,店铺名称,商品负责人')->select()->toArray();
        // 设置标题头
        $header = [];
        if($list){
            $header = array_map(function($v){ return [$v,$v]; }, $field);
        }
        $fileName = time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }
}
