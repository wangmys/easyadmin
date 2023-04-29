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

        // 查询表头
        $table_head = $this->logic->getHead();
        echo '<pre>';
        print_r(array_column($table_head,'name'));
        die;
        echo '<pre>';
        print_r($table_head);
        die;



        $this->assign([
            'get' => json_encode($get)
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
