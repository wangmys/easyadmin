<?php

namespace app\admin\controller\system\code;


use app\admin\model\code\SizePurchaseStock;
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
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;

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
     * @NodeAnotation(title="单货号码比展示")
     */
    public function index()
    {
        // 筛选
        $filters = $this->request->get();
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 指定货号
            $goodsno = $filters['货号']??'B42101021';
            $arr = [$goodsno];
            $list = [];
            foreach ($arr as $k => $v){

                // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods')
                        ->field('GoodsId,GoodsNo,GoodsName,UnitPrice,CategoryName,CategoryName1,CategoryName2,TimeCategoryName1,TimeCategoryName2,StyleCategoryName,StyleCategoryName2,LEFT(CategoryName,2) as Collar')
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

                // 单款累销
                $all_total = SizeAccumulatedSale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款7天销量
                $all_day7_total = Size7DaySale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款店铺预计库存
                $all_shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓可用库存
                $all_warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓在途库存
                $all_warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款当前总库存量 = 店铺预计库存 + 云仓可用库存 + 云仓在途库存
                $all_thisTotal = intval($all_shop_stock) + intval($all_warehouse_stock) + intval($all_warehouse_transit_stock);
                // 单款采购数量
                $all_purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款未入量 = 采购库存 - 累销 - 当前总库存
                $all_unearnedQuantity = intval($all_purchase_stock) - intval($all_total) - intval($all_thisTotal);

                // 货品上柜数
                $cabinets_num = SizeRanking::where(['货号' => $goodsno])->value("上柜家数");
                
                $total_item = [
                    '风格' => $info['StyleCategoryName'],
                    '一级分类' => $info['CategoryName1'],
                    '二级分类' => $info['CategoryName2'],
                    '领型' => $info['Collar'],
                    '近三天折率' => '100%',
                    '货品等级' => $info['StyleCategoryName2'],
                    '上柜数' => $cabinets_num,
                    '货号' => $v,
                    '尺码情况' => '合计',
                    '图片' => $thumb,
                    '周销' => $all_day7_total,
                    '累销' => $all_total,
                    '店铺库存' => $all_shop_stock,
                    '云仓库存' => $all_warehouse_stock??0,
                    '云仓在途库存' => $all_warehouse_transit_stock??0,
                    '当前总库存量' => $all_thisTotal,
                    '未入量' => $all_unearnedQuantity,
                    '周转' => '',
                    '单码售罄' => '',
                    '累销尺码比' => '',
                    '总库存' => '',
                    '当前库存' => '',
                    '单码售罄比' => '',
                    '当前单店均深' => bcadd($all_thisTotal / $cabinets_num,0,2)
                ];


                if(!empty($all_shop_stock) && !empty($all_total)){
                    $total_item['周转'] = bcadd($all_shop_stock / $all_total,0,2);
                }

                if(!empty($all_total) && !empty($all_total + $all_thisTotal)){
                    $total_item['单码售罄'] = bcadd($all_total / ($all_total + $all_thisTotal) * 100,0,2) . '%';
                }

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
                    // 云仓可用库存尺码字段
                    $warehouse_key = SizeWarehouseAvailableStock::getSizeKey($value);
                    // 云仓可用库存
                    $warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_key` )");

                    // 云仓在途库存尺码字段
                    $warehouse_transit_key = SizeWarehouseTransitStock::getSizeKey($value);
                    // 云仓在途库存
                    $warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_transit_key` )");

                    // 当前总库存量
                    $thisTotal = intval($shop_stock) + intval($warehouse_stock) + intval($warehouse_transit_stock);
                    // 采购库存尺码字段
                    $purchase_key = SizePurchaseStock::getSizeKey($value);
                    // 采购数量
                    $purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `$purchase_key` )");
                    // 未入量 = 采购库存 - 累销 - 当前总库存
                    $unearnedQuantity = intval($purchase_stock) - intval($total) - intval($thisTotal);
                    // 当前单店均深

                    $item = [
                        '风格' => $info['StyleCategoryName'],
                        '一级分类' => $info['CategoryName1'],
                        '二级分类' => $info['CategoryName2'],
                        '领型' => $info['Collar'],
                        '近三天折率' => '100%',
                        '货品等级' => $info['StyleCategoryName2'],
                        '上柜数' => $cabinets_num,
                        '货号' => $v,
                        '尺码情况' => $value,
                        '图片' => $thumb,
                        '周销' => $day7_total,
                        '累销' => $total,
                        '店铺库存' => $shop_stock,
                        '云仓库存' => $warehouse_stock??0,
                        '云仓在途库存' => $warehouse_transit_stock??0,
                        '当前总库存量' => $thisTotal,
                        '未入量' => $unearnedQuantity,
                        '周转' => '',
                        '单码售罄' => '',
                        '累销尺码比' => '',
                        '总库存' => '',
                        '当前库存' => '',
                        '单码售罄比' => '',
                        '当前单店均深' => bcadd($thisTotal / $cabinets_num,0,2),
                    ];
                    // 周转 = 店铺库存 / 累销
                    if(!empty($shop_stock) && !empty($total)){
                        $item['周转'] = bcadd($shop_stock / $total,0,2);
                    }
                    // 单码售罄 = 累销 / (累销 + 当前总库存量)
                    if(!empty($total) && !empty($total + $thisTotal)){
                        $item['单码售罄'] = bcadd($total / ($total + $thisTotal) * 100,0,2).'%';
                    }
                    // 累销尺码比 = 单尺码累销 / 单款累销
                    if(!empty($total) && !empty($all_total)){
                        $item['累销尺码比'] = bcadd($total / $all_total * 100,0,2).'%';
                    }
                    // 总库存比 = 单码总库存(未入量 + 当前总库存量) / 单款总库存(未入量 + 当前总库存量)
                    if(!empty($unearnedQuantity + $thisTotal) && !empty($all_unearnedQuantity + $all_thisTotal)){
                        $item['总库存'] = bcadd(($unearnedQuantity + $thisTotal) / ($all_unearnedQuantity + $all_thisTotal) * 100,0,2).'%';
                    }
                    // 当前库存比 = 单码当前库存 / 单款当前库存
                    if(!empty($thisTotal) && !empty($all_thisTotal)){
                        $item['当前库存'] = bcadd($thisTotal / $all_thisTotal * 100,0,2).'%';
                    }
                    $item['单码售罄比'] = (intval($item['单码售罄']) - intval($total_item['单码售罄'])).'%';

                    // 等级限制
                    $config = sysconfig('site');
                    $level_rate = 0;
                    if($info['StyleCategoryName2']=='B级'){
                        $level_rate = $config['level_b'];
                    }else{
                        $level_rate = $config['level_other'];
                    }
                    if(intval($item['单码售罄比']) > $level_rate){
                        $total_item['单码售罄比'] = "<span style='width: 100%;display: block; background:red;color:white;' >单码缺量</span>";
                    }
                    $item['单码售罄比'] = (intval($item['单码售罄']) - intval($total_item['单码售罄'])).'%';

                    $list[] = $item;
                }

                // 提取数据
                $ranking_data = [];
                foreach ($list as $lk => $lv){
                    $ranking_data['单码售罄比'][$lv['尺码情况']] = intval($lv['单码售罄比']);
                    $ranking_data['当前库存'][$lv['尺码情况']] = intval($lv['当前库存']);
                    $ranking_data['总库存'][$lv['尺码情况']] = intval($lv['总库存']);
                    $ranking_data['累销尺码比'][$lv['尺码情况']] = intval($lv['累销尺码比']);
                }

                foreach ($ranking_data as $kk => $vv){
                    asort($ranking_data[$kk]);
                }
                
                echo '<pre>';
                print_r($ranking_data);
                die;




                $arr = array_column($list,'当前库存');
                $ar = array_map(function ($v){
                    return intval($v);
                },$arr);
                rsort($ar);
                echo '<pre>';
                print_r($ranking_data);
                die;
                
                $list[] = $total_item;
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

        return $this->fetch('',[
            'where' => $where
        ]);
    }

    public function index2()
    {
        // 筛选
        $filters = $this->request->get();
        // 获取参数
        $where = $this->request->get();
        if (!$this->request->isAjax()) {
            // 指定货号
            $goodsno = $filters['货号']??'B42101021';
            $arr = [$goodsno];
            $list = [];
            foreach ($arr as $k => $v){

                // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods')
                        ->field('GoodsId,GoodsNo,GoodsName,UnitPrice,CategoryName,CategoryName1,CategoryName2,TimeCategoryName1,TimeCategoryName2,StyleCategoryName,StyleCategoryName2,LEFT(CategoryName,2) as Collar')
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

                // 单款累销
                $all_total = SizeAccumulatedSale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款7天销量
                $all_day7_total = Size7DaySale::where(['货号' => $goodsno])->value("sum( `Quantity` )");
                // 单款店铺预计库存
                $all_shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓可用库存
                $all_warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款云仓在途库存
                $all_warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款当前总库存量 = 店铺预计库存 + 云仓可用库存 + 云仓在途库存
                $all_thisTotal = intval($all_shop_stock) + intval($all_warehouse_stock) + intval($all_warehouse_transit_stock);
                // 单款采购数量
                $all_purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `Quantity` )");
                // 单款未入量 = 采购库存 - 累销 - 当前总库存
                $all_unearnedQuantity = intval($all_purchase_stock) - intval($all_total) - intval($all_thisTotal);

                // 货品上柜数
                $cabinets_num = SizeRanking::where(['货号' => $goodsno])->value("上柜家数");

                $total_item = [
                    '风格' => $info['StyleCategoryName'],
                    '一级分类' => $info['CategoryName1'],
                    '二级分类' => $info['CategoryName2'],
                    '领型' => $info['Collar'],
                    '近三天折率' => '100%',
                    '货品等级' => $info['StyleCategoryName2'],
                    '上柜数' => $cabinets_num,
                    '货号' => $v,
                    '尺码情况' => '合计',
                    '图片' => $thumb,
                    '周销' => $all_day7_total,
                    '累销' => $all_total,
                    '店铺库存' => $all_shop_stock,
                    '云仓库存' => $all_warehouse_stock??0,
                    '云仓在途库存' => $all_warehouse_transit_stock??0,
                    '当前总库存量' => $all_thisTotal,
                    '未入量' => $all_unearnedQuantity,
                    '周转' => '',
                    '单码售罄' => '',
                    '累销尺码比' => '',
                    '总库存' => '',
                    '当前库存' => '',
                    '单码售罄比' => '',
                    '当前单店均深' => bcadd($all_thisTotal / $cabinets_num,0,2)
                ];


                if(!empty($all_shop_stock) && !empty($all_total)){
                    $total_item['周转'] = bcadd($all_shop_stock / $all_total,0,2);
                }

                if(!empty($all_total) && !empty($all_total + $all_thisTotal)){
                    $total_item['单码售罄'] = bcadd($all_total / ($all_total + $all_thisTotal) * 100,0,2) . '%';
                }

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
                    // 云仓可用库存尺码字段
                    $warehouse_key = SizeWarehouseAvailableStock::getSizeKey($value);
                    // 云仓可用库存
                    $warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_key` )");

                    // 云仓在途库存尺码字段
                    $warehouse_transit_key = SizeWarehouseTransitStock::getSizeKey($value);
                    // 云仓在途库存
                    $warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->value("sum( `$warehouse_transit_key` )");

                    // 当前总库存量
                    $thisTotal = intval($shop_stock) + intval($warehouse_stock) + intval($warehouse_transit_stock);
                    // 采购库存尺码字段
                    $purchase_key = SizePurchaseStock::getSizeKey($value);
                    // 采购数量
                    $purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno])->value("sum( `$purchase_key` )");
                    // 未入量 = 采购库存 - 累销 - 当前总库存
                    $unearnedQuantity = intval($purchase_stock) - intval($total) - intval($thisTotal);
                    // 当前单店均深

                    $item = [
                        '风格' => $info['StyleCategoryName'],
                        '一级分类' => $info['CategoryName1'],
                        '二级分类' => $info['CategoryName2'],
                        '领型' => $info['Collar'],
                        '近三天折率' => '100%',
                        '货品等级' => $info['StyleCategoryName2'],
                        '上柜数' => $cabinets_num,
                        '货号' => $v,
                        '尺码情况' => $value,
                        '图片' => $thumb,
                        '周销' => $day7_total,
                        '累销' => $total,
                        '店铺库存' => $shop_stock,
                        '云仓库存' => $warehouse_stock??0,
                        '云仓在途库存' => $warehouse_transit_stock??0,
                        '当前总库存量' => $thisTotal,
                        '未入量' => $unearnedQuantity,
                        '周转' => '',
                        '单码售罄' => '',
                        '累销尺码比' => '',
                        '总库存' => '',
                        '当前库存' => '',
                        '单码售罄比' => '',
                        '当前单店均深' => bcadd($thisTotal / $cabinets_num,0,2),
                    ];
                    // 周转 = 店铺库存 / 累销
                    if(!empty($shop_stock) && !empty($total)){
                        $item['周转'] = bcadd($shop_stock / $total,0,2);
                    }
                    // 单码售罄 = 累销 / (累销 + 当前总库存量)
                    if(!empty($total) && !empty($total + $thisTotal)){
                        $item['单码售罄'] = bcadd($total / ($total + $thisTotal) * 100,0,2).'%';
                    }
                    // 累销尺码比 = 单尺码累销 / 单款累销
                    if(!empty($total) && !empty($all_total)){
                        $item['累销尺码比'] = bcadd($total / $all_total * 100,0,2).'%';
                    }
                    // 总库存比 = 单码总库存(未入量 + 当前总库存量) / 单款总库存(未入量 + 当前总库存量)
                    if(!empty($unearnedQuantity + $thisTotal) && !empty($all_unearnedQuantity + $all_thisTotal)){
                        $item['总库存'] = bcadd(($unearnedQuantity + $thisTotal) / ($all_unearnedQuantity + $all_thisTotal) * 100,0,2).'%';
                    }
                    // 当前库存比 = 单码当前库存 / 单款当前库存
                    if(!empty($thisTotal) && !empty($all_thisTotal)){
                        $item['当前库存'] = bcadd($thisTotal / $all_thisTotal * 100,0,2).'%';
                    }
                    $item['单码售罄比'] = (intval($item['单码售罄']) - intval($total_item['单码售罄'])).'%';
                    if(intval($item['单码售罄比']) > 0){
                        $total_item['单码售罄比'] = "<span style='width: 100%;display: block; background:red;color:white;' >单码缺量</span>";
                    }
                    $item['单码售罄比'] = (intval($item['单码售罄']) - intval($total_item['单码售罄'])).'%';

                    $list[] = $item;
                }

                $list[] = $total_item;
            }

            $field = [
                '尺码情况',
                '周销',
                '累销',
                '店铺库存',
                '云仓库存',
                '云仓在途库存',
                '当前总库存量',
                '未入量',
                '周转',
                '单码售罄',
                '累销尺码比',
                '当前单店均深',
                '合计'
            ];

            $res = [];
            foreach ($field as $kk => $vv){
                if($kk=='尺码情况'){
                    $item = ['字段'];
                    foreach ($size_list as $k_1 => $v_1){
                        $item[] = $v_1;
                    }
                    $item[] = '合计';
                }else{
                    $item = [$vv];
                    $list_data = array_column($list,$vv);
                    foreach ($list_data as $k_2 => $v_2){
                        $item[] = $v_2;
                    }
                }
                $res[] = $item;
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

        return $this->fetch('',[
            'where' => $where
        ]);
    }

    /**
     * @NodeAnotation(title="多货号码比展示")
     */
    public function list()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询货号列表排名
            $list = SizeRanking::order('日均销','desc')->select();

              foreach ($list as $key => &$value){
                    $value['近三天折率'] = '100%';
                    $value['全国排名'] = $key+1;
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
