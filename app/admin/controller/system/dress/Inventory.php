<?php

namespace app\admin\controller\system\dress;

use app\admin\model\dress\Store;
use app\admin\model\dress\Yinliu;
use app\admin\model\dress\YinliuStore;
use app\common\constants\AdminConstant;
use app\admin\model\dress\Accessories;
use app\admin\model\dress\YinliuQuestion;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use think\App;
use think\facade\Db;
use app\common\logic\inventory\DressLogic;
use app\common\logic\inventory\InventoryLogic;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;


/**
 * 无需登录验证的页面
 * Class Inventory
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰问题统计")
 */
class Inventory extends AdminController
{

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Accessories();
        $this->logic = new InventoryLogic();
        $this->logic2 = new DressLogic();
    }

    /**
     * 展示配饰库存不足标准的数据
     * @NodeAnotation(title="配饰总览1.0")
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $Date = date('Y-m-d');
            list($page, $limit, $where) = $this->buildTableParames();
            // 获取其他筛选
            $other_where = $this->setWhere($where)[1];

            // 设置默认筛选结果
            $where = $this->setWhere($where)[0];
            $count = $this->model->where(['Date' => $Date])
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })->whereNotIn('店铺名称&省份&商品负责人','合计');

            // 增加其他筛选
            if(count($other_where) > 0){
                $count->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }

            // 设置门店筛选
            $count = $this->logic2->setStoreFilter($count,'accessories_store_list');
            $count = $count->count();

            if(empty($where)){
                $stock_warn = sysconfig('stock_warn');
            }else{
                $stock_warn = [];
                foreach ($where as $k => $v){
                    $stock_warn[$v[0]] = $v[2]??0;
                }
            }

            $list = $this->model->where(['Date' => $Date])
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })->whereNotIn(AdminConstant::NOT_FIELD,'合计');

            // 增加其他筛选
            if(count($other_where) > 0){
                $list->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }

            $list = $this->logic2->setStoreFilter($list,'accessories_store_list');
            $list = $list->order('省份,店铺名称,商品负责人')->page($page, $limit)
                ->select()->append(['config'])->withAttr('config',function ($data,&$value) use($stock_warn){
                    $value['_data'] = [];
                    foreach ($value as $k=>$v){
                        if(isset($stock_warn[$k]) && $stock_warn[$k] > 0 && $stock_warn[$k] > $v){
                            if($v < 0){
                                $v = 0;
                            }
                            $value['_data'][$k.'_'] = $stock_warn[$k] - $v;
                        }
                        $value['Deadline'] = date('Y-m-d',strtotime('-1day'));
                    }
                    return $stock_warn;
                });
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
                'config' => $stock_warn
            ];
            return json($data);
        }
        $get = $this->request->get();
        $this->assign([
            'where' => json_encode($get)
        ]);
        return $this->fetch();
    }

    /**
     * 设置默认筛选结果
     */
    public function setWhere($where)
    {
        $stock_warn = sysconfig('stock_warn');
        $other_where = $where;
        $_where = [];
        foreach ($stock_warn as $k => $v){
            $_where[] = [$k,'<',$v];
        }
        return [$_where,$other_where];
    }

    /**
     * 展示配饰不合格的店铺总计
     */
    public function question()
    {
        $model_question = new YinliuQuestion;
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $_where) = $this->buildTableParames();
            // 条件
            $parmas = $this->getParms();
            if(empty($parmas['Date'])){
               $where['Date'] = date('Y-m-d');
            }else{
                $where = $parmas;
            }
            $count = $model_question
                ->where($where)
                ->count();
            $list = $model_question
                ->where($where)
                ->page($page, $limit)
                ->select()->toArray();
            foreach ($list as $k => &$v){
                foreach ($v as $kk => $vv){
                    if(empty($vv)){
                        $v[$kk] = '';
                    }
                }
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign([
            'get' => json_encode($get)
        ]);
        return $this->fetch();
    }

    /**
     * 查看库存完成率
     */
    public function finish_rate()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            $default_date = getThisDayToStartDate();
            $start_date = $get['start_date']??$default_date[0];
            $end_date = $get['end_date']??$default_date[1];
             // 实例化逻辑类
            $logic = new DressLogic;
            // 获取完成率数据
            $list = $logic->contrastYinliuFinishRate($start_date,$end_date);
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($list),
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign([
            'get' => json_encode($get)
        ]);
        return $this->fetch();
    }

    /**
     * 统计单个人数据完成率
     */
    public function rate()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
             // 实例化逻辑类
            $logic = new DressLogic;
            // 获取完成率数据
            $list = $logic->getComparisonResult($get);
            if(empty($list)){
                $list = [];
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($list),
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign([
            'get' => json_encode($get)
        ]);
        return $this->fetch();
    }

    /**
     * 负责人问题总览
     * @NodeAnotation(title="负责人问题总览")
     */
    public function gather()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            $nameList = $get['name']??'';
            // 是否超级管理员
            if(empty($nameList)){
                if(checkAdmin()){
                    $nameList = AdminConstant::CHARGE_LIST;
                }else{
                    $nameList = [session('admin.name')];
                }
            }else{
                $nameList = [$nameList];
            }
            $data = [];
            $Date = date('Y-m-d');
            $sort_num = 1;
            // 统计引流款问题
            $item_2 = $this->logic->yinliuProblemTotal($nameList);
            foreach ($nameList as $key => $name){

                // 查询周一存在的问题
                $question_total = Store::where([
                    '商品负责人' => $name,
                    'Date' => getThisDayToStartDate()[0]
                ])->count();

                // 查询周一哪些问题未处理
                $question_not_total = Store::where([
                    '商品负责人' => $name,
                    'Date' => getThisDayToStartDate()[0],
                    'is_qualified' => 0
                ])->count();

                // 查询总共哪些问题未处理
                $question_this_num = Store::where([
                    '商品负责人' => $name,
                    'Date' => getThisDayToStartDate()[1],
                    'is_qualified' => 0
                ])->count();

                $item = [
                    'order_num' => $sort_num++,
                    '商品负责人' => $name,
                    'name' => '配饰库存不足1.0',
                    // 问题总数
                    'total' => $question_total,
                    'not_total' => $question_not_total,
                    'this_num' => $question_this_num,
                    'time' => $question_not_total>0?getIntervalDays():'',
                    'type' => 'accessories'
                ];

                // 引流款问题
                $item2 = [
                    'order_num' => $sort_num++,
                    '商品负责人' => $name,
                    'name' => '引流库存不足',
                    // 问题总数
                    'total' => $item_2['total'][$name]??0,
                    'not_total' => $item_2['not_total'][$name]??0,
                    'this_num' => $item_2['this_num'][$name]??0,
                    'time' => $item['time'],
                    'type' => 'yinliu'
                ];

                // 配饰2.0
                $item3 = [
                    'order_num' => $sort_num++,
                    '商品负责人' => $name,
                    'name' => '配饰库存不足2.0',
                    // 问题总数
                    'total' => '-',
                    'not_total' => '-',
                    'this_num' => '-',
                    'time' => '-',
                    'type' => 'accessories_2'
                ];

//                $data[] = $item;
                $data[] = $item2;
//                $data[] = $item3;
            }
            $list = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($data),
                'data'  => $data,
            ];
            return json($list);
        }
        $this->assign([
            'get' => json_encode($get)
        ]);
        return $this->fetch();
    }

    /**
     * 任务总览
     * @NodeAnotation(title="商品专员任务总览")
     */
    public function task_overview()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询商品负责人
            $charge = AdminConstant::CHARGE_LIST;
            // 获取周一日期
            $monday = getThisDayToStartDate()[0];
            // 今日
            $thisDay = date('Y-m-d');
            $data = [];

            // 统计周一总问题数
            $yinliu_data = Store::where([
                'Date' => $monday
            ])->group('商品负责人')->column('count(*) as num','商品负责人');

            // 统计周一问题未完成问题
            $store_data = Store::where([
                'Date' => $monday
            ])->where(['is_qualified' => 0])->group('商品负责人')->column('count(*) as num','商品负责人');

            // 配饰问题完成数据
            $store_finish_data = Store::where([
                'Date' => $monday
            ])->where(['is_qualified' => 1])->group('商品负责人')->column('count(*) as num','商品负责人');

            // 查询引流款的问题
            $dress_data = $this->logic->yinliuProblemTotal($charge);

            // 负责人循环
            foreach ($charge as $k => $v){
                $item = [
                    '商品负责人' => $v,
                    'num' => $k + 1,
                    // 问题总计
                    'total' => 0,
                    // 已完成统计
                    'ok_total' => 0,
                    // 未完成统计
                    'not_total' => 0,
                ];
                // 统计配饰表的问题数
                if(isset($yinliu_data[$v])){
                    $item['total'] += $yinliu_data[$v];
                }
                // 统计配饰未完成数
                if(isset($store_data[$v]) && $store_data[$v] > 0){
                    $item['not_total'] += $store_data[$v];
                }
                // 统计配饰未完成数
                if(isset($store_finish_data[$v]) && $store_finish_data[$v] > 0){
                    $item['ok_total'] += $store_finish_data[$v];
                }

                $item['total'] += $dress_data['total'][$v]??0;
                $item['not_total'] += $dress_data['not_total'][$v]??0;
                $item['ok_total'] += $dress_data['ok_total'][$v]??0;

                // 查询其他表的问题
                $data[] = $item;
            }

            $list = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($data),
                'data'  => $data,
            ];
            return json($list);
        }
        return $this->fetch();
    }

    /**
     * 配饰的可用库存与在途库存
     * @return \think\response\Json
     */
    public function stock()
    {
        $sql = " select 可用库存Quantity as available_stock,采购在途库存Quantity as transit_stock,二级分类 as cate from accessories_warehouse_stock ";
        $config = sysconfig('stock_warn');
        // 查询表数据
        $data = Db::connect("mysql2")->query($sql);
        $res = ['available_stock' => [],'transit_stock' => []];
        foreach ($res as $k => $v){
            foreach ($data as $kk => $vv){
                $temp_key = $vv['cate'];
                $res[$k][$temp_key] = $vv[$k];
                $res[$k]['Date'] = date('Y-m-d',strtotime('-1day'));
                $res[$k]['type'] = $k=='available_stock'?'可用库存':'在途库存';
                $res[$k]['text'] = '配饰';
            }
        }
        if($res){
            $first = array_merge($res['available_stock'],$config);
            $first['text'] = '配饰配置';
            $first['type'] = '配饰库存标准';
            // 增加配饰库存标准
            $res['config'] = $first;
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
        $list = Yinliu::field($field)->where($where);
        $list = $this->logic2->setStoreFilter($list,'accessories_store_list');
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
