<?php

namespace app\admin\controller\system\dress;

use app\common\constants\AdminConstant;
use app\admin\model\dress\Accessories;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;


/**
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
}
