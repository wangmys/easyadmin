<?php

namespace app\admin\controller\system\dress;

use app\admin\model\dress\Store;
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


/**
 * 无需登录验证的页面
 * Class Inventory
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="商品负责人库存")
 */
class Inventory extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Accessories();
    }

    /**
     * 展示配饰库存不足标准的数据
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
//            if(empty($where['商品负责人'])){
//                $_where = ['商品负责人','=',-99999];
//                foreach ($where as $k=>$v){
//                    if($v[0] == '商品负责人'){
//                        unset($_where);
//                        break;
//                    }
//                }
//                // 防止全部展示
//                if(isset($_where)) $where[] = $_where;
//            }
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
     * 商品专员问题集合
     */
    public function gather()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            $name = $get['name']??'';
            $Date = date('Y-m-d');
            // 查询周一存在的问题
            $question_total = Store::where([
                '商品负责人' => $name,
                'Date' => getThisDayToStartDate()[0]
            ])->group('cate')->column('cate');
            $total = count($question_total);

            // 查询周一哪些问题未处理
            $question_not_total = Store::where([
                '商品负责人' => $name,
                'Date' => getThisDayToStartDate()[0],
                'is_qualified' => 0
            ])->group('cate')->column('cate');
            $not_total = count($question_not_total);

            $data = [
                [
                    'order_num' => 1,
                    '商品负责人' => $name,
                    'name' => '配饰库存不足',
                    // 问题总数
                    'num' => $total,
                    'untreate' => $not_total,
                    'time' => $not_total>0?getIntervalDays():'',
                ]
            ];
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
     */
    public function task_overview()
    {
        $get = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询商品负责人
            $charge = AdminConstant::CHARGE_LIST;
            // 获取周一日期
            $monday = getThisDayToStartDate()[0];
            $thisDay = date('Y-m-d');
            $data = [];

            // 统计周一有哪些问题未完成
            $yinliu_data = YinliuQuestion::where([
                'Date' => $monday
            ])->where(function ($q){
                foreach (AdminConstant::ACCESSORIES_LIST as $k => $v){
                    $q->whereOr($v,'>',0);
                }
            })->group('商品负责人')->column('count(*) as num','商品负责人');

            // 配饰详情表
            $store_data = Store::where([
                'Date' => $monday
            ])->where(['is_qualified' => 0])->group('商品负责人')->column('count(*) as num','商品负责人');

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
                    'no_total' => 0,
                ];
                // 统计配饰表的问题数
                if(isset($yinliu_data[$v])){
                    $item['total'] += 1;
                }
                // 统计配饰未完成数
                if(isset($store_data[$v]) && $store_data[$v] > 0){
                    $item['no_total'] += 1;
                }
                // 查询不动销的问题

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
}
