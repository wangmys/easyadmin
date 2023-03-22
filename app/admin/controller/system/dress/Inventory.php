<?php

namespace app\admin\controller\system\dress;

use app\common\constants\AdminConstant;
use app\admin\model\dress\Accessories;
use app\admin\model\dress\YinliuQuestion;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
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
            if (input('selectFields')) {
                return $this->selectList();
            }
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
            $count = $this->model
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

            $list = $this->model
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
            $default_date = date('Y-m-d',time() - (24 * 60 * 60 * 2));
            $start_date = $get['start_date']??$default_date;
            $end_date = $get['end_date']??date('Y-m-d',strtotime($start_date.'+1day'));
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

}
