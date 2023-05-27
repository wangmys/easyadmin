<?php

namespace app\admin\controller\system\code;


use app\admin\model\Stocks as StocksM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\Size7DaySale;
use app\admin\model\code\SizeAccumulatedSale;

/**
 * Class Ratio
 * @package app\admin\controller\system\code
 * @ControllerAnnotation(title="码比")
 */
class Ratio extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * @NodeAnotation(title="调拨指令记录")
     */
    public function index()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 指定货号
            $goodsno = 'B32101019';
            $arr = [$goodsno];
            $list = [];
            foreach ($arr as $k => $v){

                // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods')
                    ->field('GoodsId,GoodsNo,GoodsName,UnitPrice,CategoryName,CategoryName1,CategoryName2,TimeCategoryName1,TimeCategoryName2,StyleCategoryName')
                    ->where([
                    'GoodsNo' => $v
                ])->find();

                // 查询尺码信息
                $size = Db::connect('sqlsrv')->table('ErpGoodsSize')->where([
                    'GoodsId' => $info['GoodsId'],
                    'IsEnable' => 1
                ])->select()->toArray();
                $size_list = array_column($size,'Size');
                // 图片信息
                $thumb = Db::connect('sqlsrv')->table('ErpGoodsImg')->where([
                    'GoodsId' => $info['GoodsId']
                ])->value('Img');


                foreach ($size_list as $key =>$value){

                    // 根据货号尺码获取周销尺码字段
                    $sum_key = Size7DaySale::getSizeKey($value);
                    // 周销
                    $day7_total = Size7DaySale::where(['货号' => $goodsno])->value("sum( `$sum_key` )");
                    // 根据尺码获取累销尺码字段
                    $total_key = SizeAccumulatedSale::getSizeKey($value);
                    // 累销
                    $total = SizeAccumulatedSale::where(['货号' => $goodsno])->value("sum( `$total_key` )");
                    // 店铺预计库存尺码字段
                    $stock_key = SizeShopEstimatedStock::getSizeKey($value);
                    // 店铺预计库存
                    $shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->value("sum( `$stock_key` )");

                    $item = [
                        '货号' => $v,
                        '尺码情况' => $value,
                        '图片' => $thumb,
                        '周销' => $day7_total,
                        '累销' => $total,
                        '店铺库存' => $shop_stock
                    ];
                    $list[] = $item;
                }

            }

            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list),
                    'data'  => $list
                ];
            return json($data);
        }

        return $this->fetch();
    }
}
