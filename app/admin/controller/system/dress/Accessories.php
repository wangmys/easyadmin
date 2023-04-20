<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories as AccessoriesM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DVar;
use think\App;
use think\facade\Db;
use function GuzzleHttp\Psr7\str;
use think\cache\driver\Redis;


/**
 * Class Accessories
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰库存")
 */
class Accessories extends AdminController
{

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new AccessoriesM();
    }

    /**
     * @NodeAnotation(title="配饰总览")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 获取其他筛选
            $other_where = $this->setWhere($where)[1];

            // 设置默认筛选结果
            $where = $this->setWhere($where)[0];

            $count = $this->model
                ->where(['Date' => date('Y-m-d')])
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
            $count = $this->setStoreFilter($count);
            // 查询数量
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
                ->where(['Date' => date('Y-m-d')])
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
            // 设置门店筛选
            $list = $this->setStoreFilter($list);
            $list = $list->order('省份,店铺名称,商品负责人')->page($page, $limit)
                ->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
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
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="配饰结果")
     */
    public function list()
    {
        // 条件
        $this->searchWhere();
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 获取其他筛选
            $other_where = $this->setWhere($where)[1];

             // 设置默认筛选结果
            $where = $this->setWhere($where)[0];
            if(empty($where)){
                // 设置默认筛选
                $where = $this->setWhere($where)[0];
            }

            // 计算条数
            $count = $this->model
                ->where(['Date' => date('Y-m-d')])
                ->whereNotIn(AdminConstant::NOT_FIELD,'合计')
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                });

            // 增加其他筛选
            if(count($other_where) > 0){
                $count->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }
             // 设置门店筛选
            $count = $this->setStoreFilter($count);
            // 计数
            $count = $count->count();

            // 配置
            $stock_warn = sysconfig('stock_warn');

            // 获取列表
            $list = $this->model->whereNotIn('店铺名称&省份&商品负责人','合计')
                ->where(['Date' => date('Y-m-d')])
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                });

            // 增加其他筛选
            if(count($other_where) > 0){
                $list->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }
            // 设置门店筛选
            $list = $this->setStoreFilter($list);
            $list = $list->order('省份,店铺名称,商品负责人')->page($page, $limit)->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
                    return $stock_warn;
                });

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list
            ];
            return json($data);
        }
        return $this->fetch();
    }


    /**
     * 设置默认筛选结果
     */
    public function setWhere($where)
    {
        $stock_warn = sysconfig('stock_warn');
        $other_where = [];
        $_where = [];
        if(empty($where)){
            foreach ($stock_warn as $k => $v){
                $_where[] = [$k,'<',$v];
            }
        }else{
            foreach ($where as $k => $v){
                if($v[1] == '<'){
                    $_where[] = [$v[0],$v[1],$v[2]];
                }else{
                    $other_where[] = [$v[0],$v[1],$v[2]];
                }
            }
        }
        return [$_where,$other_where];
    }

    /**
     * 设置门店筛选
     */
    public function setStoreFilter($model)
    {
        // 获取配饰门店排除列表
        $storeList = sysconfig('site','accessories_store_list');
        if(empty($storeList) || empty($model)) return $model;
        return $model->whereNotIn('店铺名称',$storeList);
    }

    /**
     * 初始化搜索条件
     */
    public function searchWhere()
    {
        $search_where = [];
        $fields = [
             // 设置省份列表
            'province_list' => '省份',
            // 设置省份列表
            'shop_list' => '店铺名称',
            // 设置省份列表
            'charge_list' => '商品负责人'
        ];
        foreach ($fields as $k => $v){
            $list = $this->model->column($v);
            $search_where[$k] = [
                'field' => $v,
                'data' => $list
            ];
        }
        // 设置搜索
        $this->setSearchWhere($search_where);
    }

}
