<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\Size7DaySale;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;
use think\facade\Db;

/**
 * 码比-全体偏码情况表
 * Class SizeAllRatio
 * @package app\admin\model\code
 */
class SizeAllRatio extends TimeModel
{
    // 表名
    protected $name = 'size_all_ratio';


    /**
     * 新判断
     * @param $goodsno
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function newSaveSizeRatio($goodsno)
    {
        // 尺码
        $size = [
            '库存_00/28/37/44/100/160/S',
            '库存_29/38/46/105/165/M',
            '库存_30/39/48/110/170/L',
            '库存_31/40/50/115/175/XL',
            '库存_32/41/52/120/180/2XL',
            '库存_33/42/54/125/185/3XL',
            '库存_34/43/56/190/4XL',
            '库存_35/44/58/195/5XL',
            '库存_36/6XL',
            '库存_38/7XL',
            '库存_40/8XL'
        ];

        // 查询库存
        $fieldStr = "sum(`Quantity`) Quantity,
            sum(`库存_00/28/37/44/100/160/S`) `库存_00/28/37/44/100/160/S`,
            sum(`库存_29/38/46/105/165/M`) `库存_29/38/46/105/165/M`,
            sum(`库存_30/39/48/110/170/L`) `库存_30/39/48/110/170/L`,
            sum(`库存_31/40/50/115/175/XL`) `库存_31/40/50/115/175/XL`,
            sum(`库存_32/41/52/120/180/2XL`) `库存_32/41/52/120/180/2XL`,
            sum(`库存_33/42/54/125/185/3XL`) `库存_33/42/54/125/185/3XL`,
            sum(`库存_34/43/56/190/4XL`) `库存_34/43/56/190/4XL`,
            sum(`库存_35/44/58/195/5XL`) `库存_35/44/58/195/5XL`,
            sum(`库存_36/6XL`) `库存_36/6XL`,
            sum(`库存_38/7XL`) `库存_38/7XL`,
            sum(`库存_40/8XL`) `库存_40/8XL`
        ";

        // 查询上柜数
        $fieldStr2 = "
            sum(CASE WHEN `Quantity` > 0 THEN 1 ELSE 0 END ) `Quantity`,
            sum(CASE WHEN `库存_00/28/37/44/100/160/S` > 0 THEN 1 ELSE 0 END ) `库存_00/28/37/44/100/160/S`,
            sum(CASE WHEN `库存_29/38/46/105/165/M` > 0 THEN 1 ELSE 0 END ) `库存_29/38/46/105/165/M`,
            sum(CASE WHEN `库存_30/39/48/110/170/L` > 0 THEN 1 ELSE 0 END ) `库存_30/39/48/110/170/L`,
            sum(CASE WHEN `库存_31/40/50/115/175/XL` > 0 THEN 1 ELSE 0 END ) `库存_31/40/50/115/175/XL`,
            sum(CASE WHEN `库存_32/41/52/120/180/2XL` > 0 THEN 1 ELSE 0 END ) `库存_32/41/52/120/180/2XL`,
            sum(CASE WHEN `库存_33/42/54/125/185/3XL` > 0 THEN 1 ELSE 0 END ) `库存_33/42/54/125/185/3XL`,
            sum(CASE WHEN `库存_34/43/56/190/4XL` > 0 THEN 1 ELSE 0 END ) `库存_34/43/56/190/4XL`,
            sum(CASE WHEN `库存_35/44/58/195/5XL` > 0 THEN 1 ELSE 0 END ) `库存_35/44/58/195/5XL`,
            sum(CASE WHEN `库存_36/6XL` > 0 THEN 1 ELSE 0 END ) `库存_36/6XL`,
            sum(CASE WHEN `库存_38/7XL` > 0 THEN 1 ELSE 0 END ) `库存_38/7XL`,
            sum(CASE WHEN `库存_40/8XL` > 0 THEN 1 ELSE 0 END ) `库存_40/8XL`
        ";

        // 商品信息
        $info = SizeRanking::where([
            'Date' => date('Y-m-d'),
            '货号' => $goodsno
        ])->find();

        // 货品等级
        $config = sysconfig('site');
        // 单码缺量判断数值
        $level_rate = 0;
        if($info['货品等级']=='B级'){
            $level_rate = $config['level_b'];
        }else{
            $level_rate = $config['level_other'];
        }

        // 单款云仓在途库存
        $transit_stock_total = SizeWarehouseTransitStock::field($fieldStr)->where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 单款云仓可用库存
        $available_stock_total = SizeWarehouseAvailableStock::field($fieldStr)->where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 单款店铺预计库存
        $shop_stock_total = SizeShopEstimatedStock::field($fieldStr)->where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 单款周销
        $day7_total = Size7DaySale::field($fieldStr)->where(['货号' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 单款累销
        $sale_total = SizeAccumulatedSale::field($fieldStr)->where(['货号' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 采购库存
        $size_purchase_stock = SizePurchaseStock::field($fieldStr)->where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();
        // 单码上柜数
        $size_up_total = SizeShopEstimatedStock::field($fieldStr2)->where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->find()->toArray();

        // 单货号数据
        $data = [
            '单码售罄比' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '单码售罄比'
            ],
            '当前库存尺码比' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '当前库存尺码比'
            ],
            '总库存尺码比' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '总库存尺码比'
            ],
            '累销尺码比' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '累销尺码比'
            ],
            '单码售罄' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '单码售罄'
            ],
            '周转' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '周转'
            ],
            '当前总库存量' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '当前总库存量'
            ],
            '未入量' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '未入量'
            ],
            '累销' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '累销'
            ],
            '周销' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '周销'
            ],
            '店铺库存' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '店铺库存'
            ],
            '云仓库存' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '云仓库存'
            ],
            '云仓在途' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '云仓在途'
            ],
            '当前单店均深' => [
                'GoodsNo' => $goodsno,
                '上柜家数' => $size_up_total['Quantity']??0,
                'Date' => date('Y-m-d'),
                '字段' => '当前单店均深'
            ]
        ];

        // 单款云仓在途库存
        $all_transit_stock = 0;
        if(!empty($transit_stock_total['Quantity'])){
            $all_transit_stock = $transit_stock_total['Quantity']??0;
        }

        // 单款云仓库存
        $all_available_stock = 0;
        if(!empty($available_stock_total['Quantity'])){
            $all_available_stock = $available_stock_total['Quantity']??0;
        }

        // 单款店铺预计库存
        $all_shop_stock = 0;
        if(!empty($shop_stock_total['Quantity'])){
            $all_shop_stock = $shop_stock_total['Quantity']??0;
        }

        // 单款周销
        $all_day7_sale = 0;
        if(!empty($day7_total['Quantity'])){
            $all_day7_sale = $day7_total['Quantity']??0;
        }

        // 单款当前总库存量 = 单款云仓在途 + 单款云仓库存 + 单款店铺预计库存
        $all_total_stock = $all_transit_stock + $all_available_stock + $all_shop_stock;

        // 周转 = 单款当前总库存量 / 周销
        $all_turnover = 0;
        if($all_total_stock > 0 && $all_day7_sale > 0){
            $all_turnover = bcadd($all_total_stock / $all_day7_sale,0,2);
        }

        // 单款累销
        $sale_total_sum = 0;
        if(!empty($sale_total['Quantity'])){
            $sale_total_sum = $sale_total['Quantity']??0;
        }

        // 采购库存
        $all_purchase_stock = 0;
        if(!empty($size_purchase_stock['Quantity'])){
            $all_purchase_stock = $size_purchase_stock['Quantity']??0;
        }
        // 单款未入量 = 采购库存 - 累销 - 当前总库存
        $not_total_stock = $all_purchase_stock - $sale_total_sum - $all_total_stock;

        // 单款售罄 = 单款累销 / (单款当前总库存量 + 单款累销)
        $all_size_sell_out = 0;
        if($sale_total_sum > 0 && ($all_total_stock + $sale_total_sum) > 0){
            $all_size_sell_out = bcadd($sale_total_sum / ($all_total_stock + $sale_total_sum) * 100,0,2);
        }

        // 单款上柜数
        $all_size_up_total = 0;
        if(!empty($size_up_total['Quantity'])){
            $all_size_up_total = $size_up_total['Quantity']??0;
        }
        // 单款当前单店均深 = 当前总库存量 / 上柜家数
        $all_shop_mean = 0;
        if($all_total_stock > 0 && $all_size_up_total > 0){
            $all_shop_mean = bcadd($all_total_stock / $all_size_up_total,0,2);
        }

        // 总计
        $total_item = [
            '单码售罄比' => '',
            '当前库存尺码比' => '',
            '总库存尺码比' => ''
        ];
        foreach ($size as $kk => $vv){

            // 单码云仓在途
            $transit_stock = 0;
            if(!empty($transit_stock_total[$vv])){
                // 云仓在途某个云仓的某个尺码
                $transit_stock = $transit_stock_total[$vv]??0;
            }

            // 单码云仓库存
            $available_stock = 0;
            if(!empty($available_stock_total[$vv])){
                // 云仓库存某个云仓的某个尺码
                $available_stock = $available_stock_total[$vv]??0;
            }

            // 单码店铺预计库存
            $shop_stock = 0;
            if(!empty($shop_stock_total[$vv])){
                // 云仓库存某个云仓的某个尺码
                $shop_stock = $shop_stock_total[$vv]??0;
            }

            // 单码当前总库存量 = 单码云仓在途 + 单码云仓库存 + 单码店铺预计库存
            $this_total_stock = $transit_stock + $available_stock + $shop_stock;

            // 周销
            $day7_sale = 0;
            if(!empty($day7_total[$vv])){
                $day7_sale = $day7_total[$vv]??0;
            }

            // 周转 = 当前总库存量 / 周销
            $turnover = 0;
            if($this_total_stock > 0 && $day7_sale > 0){
               $turnover = bcadd($this_total_stock / $day7_sale,0,2) ;
            }

            // 单码累销
            $size_sale_total = 0;
            if(!empty($sale_total[$vv])){
                $size_sale_total = $sale_total[$vv]??0;
            }

            // 单码售罄 = 单码累销 / (单码当前总库存量 + 单码累销)
            $size_sell_out = 0;
            if($size_sale_total > 0 && ($this_total_stock + $size_sale_total) > 0){
                $size_sell_out = bcadd($size_sale_total / ($this_total_stock + $size_sale_total) * 100,0,2);
            }

             // 单码采购库存
            $purchase_stock = 0;
            if(!empty($size_purchase_stock[$vv])){
                $purchase_stock = $size_purchase_stock[$vv]??0;
            }
            // 单码未入量 = 单码采购库存 - 单码累销 - 单码当前总库存量
            $not_stock = $purchase_stock - $size_sale_total - $this_total_stock;

            // 累销尺码比 = (单码累销 / 单款累销)
            $sale_total_ratio = 0;
            if($size_sale_total > 0 && $sale_total_sum > 0){
                $sale_total_ratio = bcadd($size_sale_total / $sale_total_sum * 100,0,2);
            }

            // 当前库存尺码比 = 单码当前总库存量 / 单款当前总库存量
            $total_stock_ratio = 0;
            if($this_total_stock > 0 && $all_total_stock > 0){
                $total_stock_ratio = bcadd($this_total_stock / $all_total_stock * 100,0,2);
            }

            // 总库存尺码比 = 单码总库存(未入量 + 当前总库存量) / 单款总库存(未入量 + 当前总库存量)
            $all_stock_ratio = 0;
            if(($not_stock + $this_total_stock) > 0 && ($not_total_stock + $all_total_stock) > 0){
                $all_stock_ratio = bcadd(($not_stock + $this_total_stock) / ($not_total_stock + $all_total_stock) * 100,0,2);
            }

            // 单码售罄比 = 单码售罄 - 单款售罄
            $size_sell_out_ratio = 0;
            if($size_sell_out > 0 && $all_size_sell_out > 0){
                $size_sell_out_ratio = bcadd($size_sell_out - $all_size_sell_out,0,2);
            }

            // 单码缺量 = 如果单码售罄比大于设定商品的比例,则为单码缺量
            if($size_sell_out_ratio > $level_rate){
                $total_item['单码售罄比'] = "单码缺量";
            }

            // 单码上柜数
            $size_up_num = 0;
            if(!empty($size_up_total[$vv])){
                $size_up_num = $size_up_total[$vv]??0;
            }

            // 单码当前单店均深 = 单码当前总库存量 / 单码上柜家数
            $shop_mean = 0;
            if($this_total_stock > 0 && $size_up_num > 0){
                $shop_mean = bcadd($this_total_stock / $size_up_num,0,2);
            }

            $data['单码售罄比'][$vv] = $size_sell_out_ratio;
            $data['总库存尺码比'][$vv] = $all_stock_ratio;
            $data['当前库存尺码比'][$vv] = $total_stock_ratio;
            $data['累销尺码比'][$vv] = $sale_total_ratio;
            $data['单码售罄'][$vv] = $size_sell_out;
            $data['周转'][$vv] = $turnover;
            $data['当前总库存量'][$vv] = $this_total_stock;
            $data['未入量'][$vv] = $not_stock;
            $data['累销'][$vv] = $size_sale_total;
            $data['周销'][$vv] = $day7_sale;
            $data['店铺库存'][$vv] = $shop_stock;
            $data['云仓库存'][$vv] = $available_stock;
            $data['云仓在途'][$vv] = $transit_stock;
            $data['当前单店均深'][$vv] = $shop_mean;
        }

        // 1.对数据进行排序
        foreach (['当前库存尺码比','总库存尺码比','累销尺码比'] as $key => $val){
            asort($data[$val]);
        }

        // 2.获取前多少名尺码个数,如果大于等于配置数,则使用配置数,如果小于配置数,则使用尺码数
        $_count = 3;
        if($n = count($data[$val]) < $_count){
            $_count = $n;
        }

        // 3.对取好的库存尺码比排序,并截取排名前三的数值
        // 取出每个尺码指定前几名的当前库存尺码比数据
        $this_stock_size_ratio = array_slice($data['当前库存尺码比'],-$_count,null,true);
        // 取出每个尺码指定前几名的累销尺码比数据
        $accumulated_sale_ratio = array_slice($data['累销尺码比'],-$_count,null,true);
        // 4.使用当前库存尺码比与累销尺码比对比,两组尺码数据是否一致,不一致则判断单码售罄比是否为单码缺量,是的情况下,则标识偏码
        $current_inventory_1 = array_diff_key($this_stock_size_ratio,$accumulated_sale_ratio);
        $current_inventory_2 = array_diff_key($accumulated_sale_ratio,$this_stock_size_ratio);
        $current_inventory = $current_inventory_1 + $current_inventory_2;
        // 5.判断当前库存比是否偏码
        foreach ($current_inventory as $current_key => $current_val){
            // 当前库存比是否高于设定偏码参数
            if(isset($data['单码售罄比'][$current_key]) && $data['单码售罄比'][$current_key] > $level_rate){
                // 高于则提示当前库存偏码
                $total_item['当前库存尺码比'] =  "偏码";
            }
        }

        // 3.1对取好的库存尺码比排序,并截取排名前三的数值
        // 取出每个尺码指定前几名的当前库存尺码比数据
        $total_stock_size_ratio = array_slice($data['总库存尺码比'],-$_count,null,true);
        // 取出每个尺码指定前几名的累销尺码比数据
        $total_sale_ratio = array_slice($data['累销尺码比'],-$_count,null,true);
        // 4.1使用总库存尺码比与累销尺码比对比,两组尺码数据是否一致,不一致则判断单码售罄比是否为单码缺量,是的情况下,则标识偏码
        $total_inventory_1 = array_diff_key($total_stock_size_ratio,$total_sale_ratio);
        $total_inventory_2 = array_diff_key($total_sale_ratio,$total_stock_size_ratio);
        $total_inventory = $total_inventory_1 + $total_inventory_2;
        // 5.1判断总库存尺码比是否偏码
        foreach ($total_inventory as $total_key => $total_val){
            // 总库存尺码比是否高于设定偏码参数
            if(isset($data['单码售罄比'][$total_key]) && $data['单码售罄比'][$total_key] > $level_rate){
                // 高于则提示当前库存偏码
                $total_item['总库存尺码比'] =  "偏码";
            }
        }

        $data['单码售罄比']['合计'] = $total_item['单码售罄比'];
        $data['当前库存尺码比']['合计'] = $total_item['当前库存尺码比'];
        $data['总库存尺码比']['合计'] = $total_item['总库存尺码比'];
        $data['累销尺码比']['合计'] = '';
        $data['单码售罄']['合计'] = $all_size_sell_out;
        $data['周转']['合计'] = $all_turnover;
        $data['当前总库存量']['合计'] = $all_total_stock;
        $data['未入量']['合计'] = $not_total_stock;
        $data['累销']['合计'] = $sale_total_sum;
        $data['周销']['合计'] = $all_day7_sale;
        $data['店铺库存']['合计'] = $all_shop_stock;
        $data['云仓库存']['合计'] = $all_available_stock;
        $data['云仓在途']['合计'] = $all_transit_stock;
        $data['当前单店均深']['合计'] = $all_shop_mean;

        if(!empty($data)){
            // 批量插入云仓偏码数据
            Db::startTrans();
            try {
                (new self)->saveAll($data);
                // 提交事务
                Db::commit();
                return true;
            }catch (\Exception $e){
                file_put_contents("./pull_size_ratio_log.txt",var_export($e->getMessage(),true).'  '.date('Y/m/d H:i:s')."\r\n",FILE_APPEND);
                // 回滚
                Db::rollback();
                return false;
            }
        }
        return false;
    }

    /**
     * 统计单个货号码比数据(偏码判断)
     * @param $goodsno
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function saveSizeRatio($goodsno)
    {
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
            // 分离尺码列
            $size_list = array_column($size,'Size');
            // 图片信息
            $thumb = Db::connect('sqlsrv')->table('ErpGoodsImg')->where([
                'GoodsId' => $info['GoodsId']
            ])->value('Img');

            // 单款累销
            $all_total = SizeAccumulatedSale::where(['货号' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款7天销量
            $all_day7_total = Size7DaySale::where(['货号' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款店铺预计库存
            $all_shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款云仓可用库存
            $all_warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款云仓在途库存
            $all_warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款当前总库存量 = 店铺预计库存 + 云仓可用库存 + 云仓在途库存
            $all_thisTotal = intval($all_shop_stock) + intval($all_warehouse_stock) + intval($all_warehouse_transit_stock);
            // 单款采购数量
            $all_purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `Quantity` )");
            // 单款未入量 = 采购库存 - 累销 - 当前总库存
            $all_unearnedQuantity = intval($all_purchase_stock) - intval($all_total) - intval($all_thisTotal);

            // 货品上柜数
            $cabinets_num = SizeRanking::where(['货号' => $goodsno,'Date' => date('Y-m-d')])->value("上柜家数");

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

            // 周转 = 当前总库存/周销
            if(!empty($all_thisTotal) && !empty($all_day7_total)){
                $total_item['周转'] = bcadd($all_thisTotal / $all_day7_total,0,2);
            }

            if(!empty($all_total) && !empty($all_total + $all_thisTotal)){
                $total_item['单码售罄'] = bcadd($all_total / ($all_total + $all_thisTotal) * 100,0,2) . '%';
            }

            // 货品等级
            $config = sysconfig('site');
            // 单码缺量判断数值
            $level_rate = 0;
            if($info['StyleCategoryName2']=='B级'){
                $level_rate = $config['level_b'];
            }else{
                $level_rate = $config['level_other'];
            }

            // 总尺码
            $size_list = [
                '库存_00/28/37/44/100/160/S',
                '库存_29/38/46/105/165/M',
                '库存_30/39/48/110/170/L',
                '库存_31/40/50/115/175/XL',
                '库存_32/41/52/120/180/2XL',
                '库存_33/42/54/125/185/3XL',
                '库存_34/43/56/190/4XL',
                '库存_35/44/58/195/5XL',
                '库存_36/6XL',
                '库存_38/7XL',
                '库存_40/8XL'
            ];

            foreach ($size_list as $key =>$value){

                // 根据货号尺码获取周销尺码字段
                $sum_key = Size7DaySale::getSizeKey($value);
                // 周销
                $day7_total = Size7DaySale::where(['货号' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$sum_key` )");
                // 根据尺码获取累销尺码字段
                $total_key = SizeAccumulatedSale::getSizeKey($value);
                // 累销
                $total = SizeAccumulatedSale::where(['货号' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$total_key` )");
                // 店铺预计库存尺码字段
                $stock_key = SizeShopEstimatedStock::getSizeKey($value);
                // 店铺预计库存
                $shop_stock = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$stock_key` )");
                // 云仓可用库存尺码字段
                $warehouse_key = SizeWarehouseAvailableStock::getSizeKey($value);
                // 云仓可用库存
                $warehouse_stock = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$warehouse_key` )");

                // 云仓在途库存尺码字段
                $warehouse_transit_key = SizeWarehouseTransitStock::getSizeKey($value);
                // 云仓在途库存
                $warehouse_transit_stock = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$warehouse_transit_key` )");

                // 当前总库存量
                $thisTotal = intval($shop_stock) + intval($warehouse_stock) + intval($warehouse_transit_stock);
                // 采购库存尺码字段
                $purchase_key = SizePurchaseStock::getSizeKey($value);
                // 采购数量
                $purchase_stock = SizePurchaseStock::where(['GoodsNo' => $goodsno,'Date' => date('Y-m-d')])->value("sum( `$purchase_key` )");
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
                // 周转 = 当前总库存 / 周销
                if(!empty($thisTotal) && !empty($day7_total)){
                    $item['周转'] = bcadd($thisTotal / $day7_total,0,2);
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
                $item['单码售罄比'] = bcadd((floatval($item['单码售罄']) - floatval($total_item['单码售罄'])),0,2).'%';

                if(intval($item['单码售罄比']) > $level_rate){
                    $total_item['单码售罄比'] = "单码缺量";
                }

                $list[] = $item;
            }

            // 提取偏码判断数据
            $ranking_data = [];
            // 单码售罄比
            $sell_out_ratio = [];
            foreach ($list as $lk => $lv){
                $size_k = Size7DaySale::getSizeKey($lv['尺码情况']);
                $sell_out_ratio[$size_k] = floatval($lv['单码售罄比']);
                $ranking_data['当前库存'][$size_k] = floatval($lv['当前库存']);
                $ranking_data['总库存'][$size_k] = floatval($lv['总库存']);
                $ranking_data['累销尺码比'][$size_k] = floatval($lv['累销尺码比']);
            }
            // 对数据进行排序
            foreach ($ranking_data as $kk => $vv){
                asort($ranking_data[$kk]);
            }
            // 判断尺码个数,如果大于等于配置数,则使用配置数,如果小于配置数,则使用尺码数
            $_count = 3;
            if($n = count($ranking_data['累销尺码比']) < $_count){
                $_count = $n;
            }
            // 获取指定前几名的尺码数据
            $ranking_arr = [];
            foreach ($ranking_data as $rk => $rv){
                $item = array_slice($rv,-$_count,null,true);
                $ranking_arr[$rk] = $item;
            }
            // 总库存偏码对比
            $total_inventory_1 = array_diff_key($ranking_arr['总库存'],$ranking_arr['累销尺码比']);
            $total_inventory_2 = array_diff_key($ranking_arr['累销尺码比'],$ranking_arr['总库存']);
            $total_inventory = $total_inventory_1 + $total_inventory_2;
            // 当前库存偏码对比
            $current_inventory_1 = array_diff_key($ranking_arr['当前库存'],$ranking_arr['累销尺码比']);
            $current_inventory_2 = array_diff_key($ranking_arr['累销尺码比'],$ranking_arr['当前库存']);
            $current_inventory = $current_inventory_1 + $current_inventory_2;

            // 判断总库存是否偏码
            foreach ($total_inventory as $total_key => $total_val){
                // 单码售罄比是否高于设定偏码参数
                if(isset($sell_out_ratio[$total_key]) && $sell_out_ratio[$total_key] > $level_rate){
                    // 高于则提示总库存偏码
                    $total_item['总库存'] =  "偏码";
                }
            }

            // 判断当前库存是否偏码
            foreach ($current_inventory as $current_key => $current_val){
                // 单码售罄比是否高于设定偏码参数
                if(isset($sell_out_ratio[$current_key]) && $sell_out_ratio[$current_key] > $level_rate){
                    // 高于则提示当前库存偏码
                    $total_item['当前库存'] =  "偏码";
                }
            }
            $list[] = $total_item;

            $field = [
                '尺码情况',
                '单码售罄比',
                '当前库存',
                '总库存',
                '累销尺码比',
                '单码售罄',
                '周转',
                '当前总库存量',
                '未入量',
                '累销',
                '周销',
                '店铺库存',
                '云仓库存',
                '云仓在途库存',
                '当前单店均深'
            ];
            // 表数据
            $res = [];
            // 表头
            $head = [];
            // 公众字段
            $common = ['GoodsNo' => $v];
            foreach ($field as $kk => $vv){
                if($kk=='尺码情况'){
                    $head[] = ['field' => '字段','width' => 115,'title' => '字段','search' => false];
                    foreach ($size_list as $k_1 => $v_1){
                        $head[] = [
                            'field' => $v_1,
                            'width' => 115,
                            'title' => $v_1,
                            'search' => false,
                        ];
                    }
                    $head[] = ['field' => '合计','width' => 115,'title' => '合计','search' => false];
                }else{
                    $item = ['字段' => $vv,'Date' => date('Y-m-d')];
                    $list_data = array_column($list,$vv);
                    foreach ($list_data as $k_2 => $v_2){
                        $size_key = $size_list[$k_2]??'合计';
                        $item[$size_key] = $v_2;
                    }
                    $res[] = $common + $item;
                }
            }
        }
        if(!empty($res)){
            Db::startTrans();
            try{
                (new self)->saveAll($res);
                // 提交事务
                Db::commit();
                return true;
            }catch(\Exception $e){
                file_put_contents("./pull_size_ratio_log.txt",var_export($e->getMessage(),true).'  '.date('Y/m/d H:i:s')."\r\n",FILE_APPEND);
                // 回滚
                Db::rollback();
                return false;
            }
        }
        return false;
    }

    /**
     * 查询日均销排名货号,并计算每个货号的码比
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function saveData()
    {
        // 成功结果
        $result = [];
        // 失败结果
        $error = [];
        $goodsNo = self::group("GoodsNo")->where(['Date' => date('Y-m-d')])->column('GoodsNo');
        // 查询货号列表排名
        $list = SizeRanking::where(['Date' => date('Y-m-d')])->order('日均销','desc')->whereNotIn('货号',$goodsNo)->select();
        foreach ($list as $key => $value){
            // 计算并保存码比数据
            $res = self::newSaveSizeRatio($value['货号']);
            if($res === true){
                $result[] = $res;
            }else{
                $error[] = $res;
            }
            echo $res;
        }
        return ['success' => count($result),'error' => $error];
    }
}