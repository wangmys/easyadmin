<?php


namespace app\api\service\bi\yinliu;

use app\admin\model\dress\Yinliu;
use app\admin\model\dress\Accessories;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\YinliuStore;
use app\admin\model\dress\Store;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\App;
use think\facade\Db;
use think\cache\driver\Redis;

/**
 * 引流配饰数据拉取服务
 * Class AuthService
 * @package app\common\service
 */
class YinliuDataService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    protected $code = 0;
    protected $msg = '';

    public function __construct()
    {
        $this->model = new Yinliu();
        $this->accessories = new Accessories();
        $this->yinliuStore = new YinliuStore();
        $this->store = new Store();
    }

    /**
     * 保存库存不合格的店铺记录
     */
    public function save($Date = '')
    {
        // 生成筛选条件
        $_where = crateStockWhere();
        $Date = $Date?:date('Y-m-d');
        // 查询今日数据数量
        $count = Db::connect("mysql2")->table('sp_customer_yinliu')
            ->where(['Date' => $Date])
            ->whereNotIn(AdminConstant::NOT_FIELD,'合计')->where(function ($q)use($_where){
            $q->whereOr($_where);
        })->count();
        // 查询本地今日数据量
        $toDayCount = $this->model->where(['Date' => $Date])->count();
        // 如果远程引流表的数据与本地库存表数据量一致则今日已取数据,不再重复获取
        if($count == $toDayCount || $toDayCount > 1){
            return ApiConstant::ERROR_CODE_1;
        }
        // 查询不合格数据
        $list = Db::connect("mysql2")->table('sp_customer_yinliu')
            ->field(ApiConstant::YINLIU_FIELD)
            ->where(['Date' => $Date])
            ->where(function ($q)use($_where){
            $q->whereOr($_where);
        })->whereNotIn(AdminConstant::NOT_FIELD,'合计')->select()->chunk(1000,true);
        if($list){
          Db::startTrans();
          try {
            foreach ($list as $k => $v){
                $insertAll = [];
                foreach ($v as $kk => $vv){
                    // 循环组合要插入的数据
                    $vv['Date'] = $Date;
                    $vv['Deadline'] = date('Y-m-d',strtotime($Date.'-1day'));
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
                $this->msg = $e->getMessage();
                return ApiConstant::ERROR_CODE;
          }
          return ApiConstant::SUCCESS_CODE;
        }
        return ApiConstant::ERROR_CODE_3;
    }

    /**
     * 提取商品负责人配饰不合格的店铺总数
     */
    public function create($Date = '')
    {
        // 货品列表
        $goods_list = AdminConstant::ACCESSORIES_LIST;
        // 获取配置
        $config = sysconfig('stock_warn');
        // 获取当前日期
        $Date = $Date?:date('Y-m-d');
        // 本地问题数据表
        $yinliu_model = $this->model;
        // 查询商品负责人
        $charge = $yinliu_model->where([
            'Date' => $Date
        ])->group('商品负责人')->column('商品负责人');
        // 待插入数据
        $insert_d = [];
        // 待插入门店数据
        $insert_store_d = [];
        // 配饰详情数据
        $insert_item = [];
        // 实例化
        $sheet = new YinliuQuestion;
        // 获取今日统计数据的条数()
        $toDaynum = $sheet->where(['Date' => $Date])->count();
        if($toDaynum > 0){
            return ApiConstant::ERROR_CODE_1;
        }
        if(empty($charge)){
            return ApiConstant::ERROR_CODE_3;
        }
        // 查询每个商品负责人,每个货品不合格的店铺数量
        foreach ($charge as $k=>$v){
            $d = $d2 = $item = ['Date' => $Date,'create_time' => time(),'商品负责人' => $v];
            foreach ($goods_list as $kk => $vv){
                // 获取标准判断
                $standard = $config[$vv]??0;
                // 查询对应商品,库存低于标准的店铺列表
                $store_list = $yinliu_model->where([
                    '商品负责人' => $v,
                    'Date' => $Date
                ])->where(function ($q)use($vv,$standard){
                    if($standard > 0){
                        $q->whereNull($vv);
                    }
                    $q->whereOr($vv,'<',$standard);
                })->column('店铺名称,'.$vv);
                // 统计不合格店铺数量
                $d[$vv] = count($store_list);
                if($store_list){
                    // 细分每个配饰不合格店铺、库存
                    foreach ($store_list as $key=>$value){
                        $item['cate'] = $vv;
                        $item['商品负责人'] = $v;
                        $item['店铺名称'] = $value['店铺名称'];
                        $item['stocks'] = $value[$vv];
                        $item['qualified_num'] = $standard;
                        $item['is_qualified'] = (intval($value[$vv]) > intval($standard))?1:0;
                        $insert_item[] = $item;
                    }
                    // 获得具体的不合格店铺
                    $d2[$vv] = implode(',',array_column($store_list,'店铺名称'));
                }else{
                    $d2[$vv] = '';
                }
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
            // 每个配饰不合格的店铺、库存、标准等数据
            $this->store->insertAll($insert_item);
        }catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            $this->msg = $e->getMessage();
            return ApiConstant::ERROR_CODE;
        }
        if($res_count > 0 && ($res_count == $store_count)){
            // 提交事务
            Db::commit();
            return ApiConstant::SUCCESS_CODE;
        }
        return ApiConstant::ERROR_CODE;
    }

    /**
     * 检测周一完成度
     */
    public function checkMondayComplete($Date = '')
    {
        // 一周日期
        $monday = getThisDayToStartDate()[0];
        // 当前日期
        $thisDay = $Date?:date('Y-m-d');
        // 查询当前周周一的问题数据
        if(date('w',strtotime($Date)) != 1){// 一周不检测
            // 启动事务
            Db::startTrans();
            try {
                // 问题数据列表
                $list = Store::where([
                    'Date' => $monday,
                    'is_qualified' => 0
                ])->select();
                // 定义修改数据的集合
                $save_data = [];
                foreach ($list as $k => $v){
                    $item = [
                        'id' => $v['id'],
                        'is_qualified' => 1,
                        '商品负责人' => $v['商品负责人'],
                        '店铺名称' => $v['店铺名称'],
                        'cate' => $v['cate']
                    ];
                    // 检测此问题在今日是否已处理
                    $is_ext = Store::where([
                        'Date' => $thisDay,
                        '商品负责人' => $v['商品负责人'],
                        '店铺名称' => $v['店铺名称'],
                        'cate' => $v['cate']
                    ])->count();
                    // 如果问题在今日不存在,则修改当前问题状态为已处理
                    if($is_ext < 1){
                        $save_data[] = $item;
                    }
                }
                // 批量更新问题状态
                if($save_data) {
                    $res = (new Store())->saveAll($save_data);
                    // 提交事务
                    Db::commit();
                    return ApiConstant::SUCCESS_CODE;
                }
                return ApiConstant::ERROR_CODE;
            }catch (\Exception $e){
                // 回滚事务
                Db::rollback();
                $this->msg = $e->getMessage();
                return ApiConstant::ERROR_CODE;
            }
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 获取错误提示
     */
    public function getError($code = 0)
    {
        return $this->msg?:ApiConstant::ERROR_CODE_LIST[$code];
    }
}