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
     * @NodeAnotation(title="数据总览")
     */
    public function index()
    {
        $get = $this->request->get();
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
                    'width' => 95,
                    'align' => 'center'
                ];
                if($k=='店铺ID'){
                    $item['width'] = 115;
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
        
        $cols = [
            $table_head_1,
            $table_head_2
        ];

        $data = "[
            {field: '商品负责人', width: 200, title: '商品负责人', rowspan: 2,fixed:'left'},
            {title: '背包', colspan: 3},
            {title: '挎包', colspan: 3},
            {title: '领带', colspan: 3},
            {title: '帽子', colspan: 3},
            {title: '内裤', colspan: 3},
            {title: '皮带', colspan: 3},
            {title: '袜子', colspan: 3},
            {title: '手包', colspan: 3},
            {title: '胸包', colspan: 3}
        ],
        [
            {field: '背包', width:100, title: '问题店铺',event:'pp',border: {style: 'solid',color: '1E9FFF'}},
            {field: '背包_1', width:100, title: '已完成'},
            {field: '背包_2', width:150, title: '未完成店铺数'},
            {field: '挎包', width:100, title: '问题店铺'},
            {field: '挎包_1', width:100, title: '已完成'},
            {field: '挎包_2', width:150, title: '未完成店铺数'},
            {field: '领带', width:100, title: '问题店铺'},
            {field: '领带_1', width:100, title: '已完成'},
            {field: '领带_2', width:150, title: '未完成店铺数'},
            {field: '帽子', width:100, title: '问题店铺'},
            {field: '帽子_1', width:100, title: '已完成'},
            {field: '帽子_2', width:150, title: '未完成店铺数'},
            {field: '内裤', width:100, title: '问题店铺'},
            {field: '内裤_1', width:100, title: '已完成'},
            {field: '内裤_2', width:150, title: '未完成店铺数'},
            {field: '皮带', width:100, title: '问题店铺'},
            {field: '皮带_1', width:100, title: '已完成'},
            {field: '皮带_2', width:150, title: '未完成店铺数'},
            {field: '袜子', title: '问题店铺', width:100},
            {field: '袜子_1', title: '已完成', width:100},
            {field: '袜子_2', title: '未完成店铺数', width:150},
            {field: '手包', title: '问题店铺', width:100},
            {field: '手包_1', title: '已完成', width:100},
            {field: '手包_2', title: '未完成店铺数', width:150},
            {field: '胸包', title: '问题店铺', width:100},
            {field: '胸包_1', title: '已完成', width:100},
            {field: '胸包_2', title: '未完成店铺数', width:150}
        ]";

        $this->assign([
            'get' => json_encode($get),
            'cols' => json_encode($cols)
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
