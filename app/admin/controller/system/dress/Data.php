<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Yinliu;
use app\admin\model\dress\Accessories;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\YinliuStore;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use think\cache\driver\Redis;


/**
 * 提取配饰库存不合格的数据
 * Class Data
 * @package app\admin\controller\system\dress
 */
class Data extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Yinliu();
        $this->accessories = new Accessories();
        $this->yinliuStore = new YinliuStore();
    }

    /**
     * 提取库存不合格的店铺记录
     */
    public function selectSave()
    {
        // 获取判断标准
        $stock_warn = sysconfig('stock_warn');
        // 组合筛选条件
        $_where = [];
        foreach ($stock_warn as $k => $v){
            $_where[] = [$k,'<',$v];
        }
        $field = [
            '省份',
            '店铺名称',
            '商品负责人',
            '背包',
            '挎包',
            '领带',
            '帽子',
            '内裤',
            '皮带',
            '袜子',
            '手包',
            '胸包'
        ];
        // 查询今日数据数量
        $count = Db::connect("mysql2")->table('sp_customer_yinliu')->whereNotIn('店铺名称&省份&商品负责人','合计')->where(function ($q)use($_where){
            $q->whereOr($_where);
        })->count();
        $toDayCount = $this->model->where(['Date' => date('Y-m-d')])->count();
        // 如果远程引流表的数据与本地库存表数据量一致则今日已取数据,不再重复获取
        if($count == $toDayCount || $toDayCount > 1){
            return json([
                'success'=> 'ok',
                'msg' => '今日已执行'
            ]);
        }
        // 查询不合格数据
        $list = Db::connect("mysql2")->table('sp_customer_yinliu')->field($field)->where(function ($q)use($_where){
            $q->whereOr($_where);
        })->whereNotIn('店铺名称&省份&商品负责人','合计')->select()->chunk(1000,true);
        if($list){
          Db::startTrans();
          try {
            foreach ($list as $k => $v){
                $insertAll = [];
                foreach ($v as $kk => $vv){
                    // 循环组合要插入的数据
                    $vv['Date'] = date('Y-m-d');
                    $vv['create_time'] = time();
                    $insertAll[] = $vv;
                }
                // 执行插入操作
                $res = $this->model->insertAll($insertAll);
            }
            // 提交事务
            Db::commit();
          }catch (\Exception $e){
               // 回滚事务
                Db::rollback();
                return json([
                    'success'=> 'ok',
                    'msg' => $e->getMessage()
                ]);
            }
        }
        return json([
            'success'=> 'ok',
            'msg' => '成功'
        ]);
    }

    /**
     * 提取商品负责人配饰不合格的店铺总数
     */
    public function create()
    {
        // 货品列表
        $goods_list = AdminConstant::ACCESSORIES_LIST;
        // 获取配置
        $config = sysconfig('stock_warn');
        // 默认状态
        $code = 'no';
        // 获取当前日期
        $Date = date('Y-m-d');
        $yinliu_model = $this->model;
        // 查询商品负责人
        $charge = $yinliu_model->where([
            'Date' => $Date
        ])->group('商品负责人')->column('商品负责人');
        // 待插入数据
        $insert_d = [];
        // 待插入门店数据
        $insert_store_d = [];
        // 实例化
        $sheet = new YinliuQuestion;
        // 获取今日统计数据的条数()
        $toDaynum = $sheet->where(['Date' => $Date])->count();
        if($toDaynum > 0){
            return json([
                'success'=> $code,
                'msg' => '今日数据已处理'
            ]);
        }
        // 查询每个商品负责人,每个货品不合格的店铺数量
        foreach ($charge as $k=>$v){
            $d = $d2 = ['Date' => $Date,'create_time' => time(),'商品负责人' => $v];
            foreach ($goods_list as $kk => $vv){
                // 获取标准判断
                $standard = $config[$vv]??0;
                // 查询对应商品,库存低于标准的店铺数量
                $store_list = $yinliu_model->where([
                    '商品负责人' => $v,
                    'Date' => $Date
                ])->where(function ($q)use($vv,$standard){
                    if($standard > 0){
                        $q->whereNull($vv);
                    }
                    $q->whereOr($vv,'<',$standard);
                })->column('店铺名称');
                $d[$vv] = count($store_list);
                $d2[$vv] = implode(',',$store_list);
            }
            $insert_d[] = $d;
            $insert_store_d[] = $d2;
        }
        // 启动事务
        Db::startTrans();
        try {
            // 批量插入数据
            $res_count = $sheet->insertAll($insert_d);
            // 门店数据
            $store_count = $this->yinliuStore->insertAll($insert_store_d);
        }catch (\Exception $e){
            return json([
                'success'=> $code,
                'msg' => $e->getMessage()
            ]);
            // 回滚事务
            Db::rollback();
        }
        if($res_count > 0 && ($res_count == $store_count)){
            $code = 'ok';
            // 提交事务
            Db::commit();
        }
         return json([
            'success'=> $code,
            'msg' => $code=='ok'?'执行成功':'执行失败'
        ]);
    }
}
