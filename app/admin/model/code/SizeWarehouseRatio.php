<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;
use think\facade\Db;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;

class SizeWarehouseRatio extends TimeModel
{
    // 表名
    protected $name = 'size_warehouse_ratio';

    /**
     * 保存单个货号云仓偏码数据
     * @param string $goodsno
     */
    public static function saveSizeRatio($goodsno)
    {
        // 云仓
        $warehouse = [
            '广州云仓',
            '南昌云仓',
            '长沙云仓',
            '武汉云仓',
            '贵阳云仓'
        ];

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
        $transit_stock_total = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->group('WarehouseName')->column($fieldStr,'WarehouseName');
        // 单款云仓可用库存
        $available_stock_total = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->group('WarehouseName')->column($fieldStr,'WarehouseName');
        // 单款店铺预计库存
        $shop_stock_total = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->group('CustomItem15')->column($fieldStr,'CustomItem15');
        // 单款周销
        $day7_total = Size7DaySale::where(['货号' => $goodsno])->group('云仓')->column($fieldStr,'云仓');
        // 单款累销
        $sale_total = SizeAccumulatedSale::where(['货号' => $goodsno])->group('云仓')->column($fieldStr,'云仓');
        // 单码上柜数
        $size_up_total = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->group('CustomItem15')->column($fieldStr2,'CustomItem15');

        // 所有云仓数据
        $all_warehouse_data = [];

        foreach ($warehouse as $k => $v){

            // 单云仓数据
            $data = [
                '单码售罄比' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '单码售罄比'
                ],
                '当前库存尺码比' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '当前库存尺码比'
                ],
                '累销尺码比' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '累销尺码比'
                ],
                '单码售罄' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '单码售罄'
                ],
                '周转' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '周转'
                ],
                '当前总库存量' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '当前总库存量'
                ],
                '单码上柜数' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '单码上柜数'
                ],
                '累销' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '累销'
                ],
                '周销' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '周销'
                ],
                '店铺库存' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '店铺库存'
                ],
                '云仓库存' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '云仓库存'
                ],
                '云仓在途' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '云仓在途'
                ],
                '当前单店均深' => [
                    'GoodsNo' => $goodsno,
                    'Date' => date('Y-m-d'),
                    '云仓' => $v,
                    '字段' => '当前单店均深'
                ]
            ];

            // 单款云仓在途库存
            $all_transit_stock = 0;
            if(!empty($transit_stock_total[$v])){
                $all_transit_stock = $transit_stock_total[$v]['Quantity']??0;
            }

            // 单款云仓库存
            $all_available_stock = 0;
            if(!empty($available_stock_total[$v])){
                $all_available_stock = $available_stock_total[$v]['Quantity']??0;
            }

            // 单款店铺预计库存
            $all_shop_stock = 0;
            if(!empty($shop_stock_total[$v])){
                $all_shop_stock = $shop_stock_total[$v]['Quantity']??0;
            }

            // 单款周销
            $all_day7_sale = 0;
            if(!empty($day7_total[$v])){
                $all_day7_sale = $day7_total[$v]['Quantity']??0;
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
            if(!empty($sale_total[$v])){
                $sale_total_sum = $sale_total[$v]['Quantity']??0;
            }

            // 单款售罄 = 单款累销 / (单款当前总库存量 + 单款累销)
            $all_size_sell_out = 0;
            if($sale_total_sum > 0 && ($all_total_stock + $sale_total_sum) > 0){
                $all_size_sell_out = bcadd($sale_total_sum / ($all_total_stock + $sale_total_sum) * 100,0,2);
            }

            // 单款上柜数
            $all_size_up_total = 0;
            if(!empty($size_up_total[$v])){
                $all_size_up_total = $size_up_total[$v]['Quantity']??0;
            }
            // 单款当前单店均深 = 当前总库存量 / 上柜家数
            $all_shop_mean = 0;
            if($all_total_stock > 0 && $all_size_up_total > 0){
                $all_shop_mean = bcadd($all_total_stock / $all_size_up_total,0,2);
            }

            // 总计
            $total_item = [
                '单码售罄比' => '',
                '当前库存尺码比' => ''
            ];
            foreach ($size as $kk => $vv){

                // 单码云仓在途
                $transit_stock = 0;
                if(!empty($transit_stock_total[$v])){
                    // 云仓在途某个云仓的某个尺码
                    $transit_stock = $transit_stock_total[$v][$vv]??0;
                }

                // 单码云仓库存
                $available_stock = 0;
                if(!empty($available_stock_total[$v])){
                    // 云仓库存某个云仓的某个尺码
                    $available_stock = $available_stock_total[$v][$vv]??0;
                }

                // 单码店铺预计库存
                $shop_stock = 0;
                if(!empty($shop_stock_total[$v])){
                    // 云仓库存某个云仓的某个尺码
                    $shop_stock = $shop_stock_total[$v][$vv]??0;
                }

                // 单码当前总库存量 = 单码云仓在途 + 单码云仓库存 + 单码店铺预计库存
                $this_total_stock = $transit_stock + $available_stock + $shop_stock;

                // 周销
                $day7_sale = 0;
                if(!empty($day7_total[$v])){
                    $day7_sale = $day7_total[$v][$vv]??0;
                }

                // 周转 = 当前总库存量 / 周销
                $turnover = 0;
                if($this_total_stock > 0 && $day7_sale > 0){
                   $turnover = bcadd($this_total_stock / $day7_sale,0,2) ;
                }

                // 单码累销
                $size_sale_total = 0;
                if(!empty($sale_total[$v])){
                    $size_sale_total = $sale_total[$v][$vv]??0;
                }

                // 单码售罄 = 单码累销 / (单码当前总库存量 + 单码累销)
                $size_sell_out = 0;
                if($size_sale_total > 0 && ($this_total_stock + $size_sale_total) > 0){
                    $size_sell_out = bcadd($size_sale_total / ($this_total_stock + $size_sale_total) * 100,0,2);
                }

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

                // 单码售罄比 = 单码售罄 - 单款售罄
                $size_sell_out_ratio = 0;
                if($size_sell_out > 0 && $all_size_sell_out > 0){
                    $size_sell_out_ratio = $size_sell_out - $all_size_sell_out;
                }

                // 单码缺量 = 如果单码售罄比大于设定商品的比例,则为单码缺量
                if($size_sell_out_ratio > $level_rate){
                    $total_item['单码售罄比'] = "单码缺量";
                }

                // 单码上柜数
                $size_up_num = 0;
                if(!empty($size_up_total[$v])){
                    $size_up_num = $size_up_total[$v][$vv]??0;
                }

                // 单码当前单店均深 = 单码当前总库存量 / 单码上柜家数
                $shop_mean = 0;
                if($this_total_stock > 0 && $size_up_num > 0){
                    $shop_mean = bcadd($this_total_stock / $size_up_num,0,2);
                }

                $data['单码售罄比'][$vv] = $size_sell_out_ratio;
                $data['当前库存尺码比'][$vv] = $total_stock_ratio;
                $data['累销尺码比'][$vv] = $sale_total_ratio;
                $data['单码售罄'][$vv] = $size_sell_out;
                $data['周转'][$vv] = $turnover;
                $data['当前总库存量'][$vv] = $this_total_stock;
                $data['单码上柜数'][$vv] = $size_up_num;
                $data['累销'][$vv] = $size_sale_total;
                $data['周销'][$vv] = $day7_sale;
                $data['店铺库存'][$vv] = $shop_stock;
                $data['云仓库存'][$vv] = $available_stock;
                $data['云仓在途'][$vv] = $transit_stock;
                $data['当前单店均深'][$vv] = $shop_mean;
            }

            // 1.对数据进行排序
            foreach (['当前库存尺码比','累销尺码比'] as $key => $val){
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

            $data['单码售罄比']['总计'] = $total_item['单码售罄比'];
            $data['当前库存尺码比']['总计'] = $total_item['当前库存尺码比'];
            $data['累销尺码比']['总计'] = '';
            $data['单码售罄']['总计'] = $all_size_sell_out;
            $data['周转']['总计'] = $all_turnover;
            $data['当前总库存量']['总计'] = $all_total_stock;
            $data['单码上柜数']['总计'] = '';
            $data['累销']['总计'] = $sale_total_sum;
            $data['周销']['总计'] = $all_day7_sale;
            $data['店铺库存']['总计'] = $all_shop_stock;
            $data['云仓库存']['总计'] = $all_available_stock;
            $data['云仓在途']['总计'] = $all_transit_stock;
            $data['当前单店均深']['总计'] = $all_shop_mean;
            $all_warehouse_data[$v] = $data;
        }

        if(!empty($all_warehouse_data)){
            // 批量插入云仓偏码数据
            Db::startTrans();
            try {
                foreach($all_warehouse_data as $w_key => $k_val){
                    (new self)->saveAll($k_val);
                }
                // 提交事务
                Db::commit();
                return true;
            }catch (\Exception $e){
                file_put_contents("./pull_warehouse_size_ratio.txt",var_export($e->getMessage(),true).'  '.date('Y/m/d H:i:s')."\r\n",FILE_APPEND);
                // 回滚
                Db::rollback();
                return false;
            }
        }
    }

    /**
     * 保存所有云仓偏码
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
        $list = SizeRanking::order('日均销','desc')->whereNotIn('货号',$goodsNo)->select();
        foreach ($list as $key => $value){
            // 计算并保存码比数据
            $res = self::saveSizeRatio($value['货号']);
            if($res === true){
                $result[] = $res;
            }else{
                $error[] = $res;
            }
            echo $res;
        }
        return ['success' => count($result),'error' => $error];
    }

    /**
     * 查询云仓偏码数据
     */
    public static function selectWarehouseRatio($goodsno)
    {
        // 查询时间
        $Date = date('Y-m-d');
        $sql = "
        SELECT 
        
        ra.id as rid,
        ra.`风格`,
        ra.`一级分类`,
        ra.`二级分类`,
        ra.`分类`,
        ra.`领型`,
        ra.`货品等级`,
        
        
        r1.id,
        r2.`云仓`,
        r2.`字段` as `广州_字段`,
        r2.`库存_00/28/37/44/100/160/S` as `广州_00/28/37/44/100/160/S`,
        r2.`库存_29/38/46/105/165/M` as `广州_29/38/46/105/165/M`,
        r2.`库存_30/39/48/110/170/L` as `广州_30/39/48/110/170/L`,
        r2.`库存_31/40/50/115/175/XL` as `广州_31/40/50/115/175/XL`,
        r2.`库存_32/41/52/120/180/2XL` as `广州_32/41/52/120/180/2XL`,
        r2.`库存_33/42/54/125/185/3XL` as `广州_33/42/54/125/185/3XL`,
        r2.`库存_34/43/56/190/4XL` as `广州_34/43/56/190/4XL`,
        r2.`库存_35/44/58/195/5XL` as `广州_35/44/58/195/5XL`,
        r2.`库存_36/6XL` as `广州_36/6XL`,
        r2.`库存_38/7XL` as `广州_38/7XL`,
        r2.`库存_40/8XL` as `广州_40/8XL`,
        r2.`总计` as `广州_总计`,
        
        
        r3.`云仓`,
        r3.`字段` as `南昌_字段`,
        r3.`库存_00/28/37/44/100/160/S` as `南昌_00/28/37/44/100/160/S`,
        r3.`库存_29/38/46/105/165/M` as `南昌_29/38/46/105/165/M`,
        r3.`库存_30/39/48/110/170/L` as `南昌_30/39/48/110/170/L`,
        r3.`库存_31/40/50/115/175/XL` as `南昌_31/40/50/115/175/XL`,
        r3.`库存_32/41/52/120/180/2XL` as `南昌_32/41/52/120/180/2XL`,
        r3.`库存_33/42/54/125/185/3XL` as `南昌_33/42/54/125/185/3XL`,
        r3.`库存_34/43/56/190/4XL` as `南昌_34/43/56/190/4XL`,
        r3.`库存_35/44/58/195/5XL` as `南昌_35/44/58/195/5XL`,
        r3.`库存_36/6XL` as `南昌_36/6XL`,
        r3.`库存_38/7XL` as `南昌_38/7XL`,
        r3.`库存_40/8XL` as `南昌_40/8XL`,
        r3.`总计` as `南昌_总计`,
        
        
        r4.`云仓`,
        r4.`字段` as `武汉_字段`,
        r4.`库存_00/28/37/44/100/160/S` as `武汉_00/28/37/44/100/160/S`,
        r4.`库存_29/38/46/105/165/M` as `武汉_29/38/46/105/165/M`,
        r4.`库存_30/39/48/110/170/L` as `武汉_30/39/48/110/170/L`,
        r4.`库存_31/40/50/115/175/XL` as `武汉_31/40/50/115/175/XL`,
        r4.`库存_32/41/52/120/180/2XL` as `武汉_32/41/52/120/180/2XL`,
        r4.`库存_33/42/54/125/185/3XL` as `武汉_33/42/54/125/185/3XL`,
        r4.`库存_34/43/56/190/4XL` as `武汉_34/43/56/190/4XL`,
        r4.`库存_35/44/58/195/5XL` as `武汉_35/44/58/195/5XL`,
        r4.`库存_36/6XL` as `武汉_36/6XL`,
        r4.`库存_38/7XL` as `武汉_38/7XL`,
        r4.`库存_40/8XL` as `武汉_40/8XL`,
        r4.`总计` as `武汉_总计`,
        
        
        r5.`云仓`,
        r5.`字段` as `长沙_字段`,
        r5.`库存_00/28/37/44/100/160/S` as `长沙_00/28/37/44/100/160/S`,
        r5.`库存_29/38/46/105/165/M` as `长沙_29/38/46/105/165/M`,
        r5.`库存_30/39/48/110/170/L` as `长沙_30/39/48/110/170/L`,
        r5.`库存_31/40/50/115/175/XL` as `长沙_31/40/50/115/175/XL`,
        r5.`库存_32/41/52/120/180/2XL` as `长沙_32/41/52/120/180/2XL`,
        r5.`库存_33/42/54/125/185/3XL` as `长沙_33/42/54/125/185/3XL`,
        r5.`库存_34/43/56/190/4XL` as `长沙_34/43/56/190/4XL`,
        r5.`库存_35/44/58/195/5XL` as `长沙_35/44/58/195/5XL`,
        r5.`库存_36/6XL` as `长沙_36/6XL`,
        r5.`库存_38/7XL` as `长沙_38/7XL`,
        r5.`库存_40/8XL` as `长沙_40/8XL`,
        r5.`总计` as `长沙_总计`,
        
        
        r6.`云仓`,
        r6.`字段` as `贵阳_字段`,
        r6.`库存_00/28/37/44/100/160/S` as `贵阳_00/28/37/44/100/160/S`,
        r6.`库存_29/38/46/105/165/M` as `贵阳_29/38/46/105/165/M`,
        r6.`库存_30/39/48/110/170/L` as `贵阳_30/39/48/110/170/L`,
        r6.`库存_31/40/50/115/175/XL` as `贵阳_31/40/50/115/175/XL`,
        r6.`库存_32/41/52/120/180/2XL` as `贵阳_32/41/52/120/180/2XL`,
        r6.`库存_33/42/54/125/185/3XL` as `贵阳_33/42/54/125/185/3XL`,
        r6.`库存_34/43/56/190/4XL` as `贵阳_34/43/56/190/4XL`,
        r6.`库存_35/44/58/195/5XL` as `贵阳_35/44/58/195/5XL`,
        r6.`库存_36/6XL` as `贵阳_36/6XL`,
        r6.`库存_38/7XL` as `贵阳_38/7XL`,
        r6.`库存_40/8XL` as `贵阳_40/8XL`,
        r6.`总计` as `贵阳_总计`
        
        from ea_size_ranking as ra
        left join 
        ea_size_warehouse_ratio as r1 on ra.货号 = r1.GoodsNo
        left join 
        ea_size_warehouse_ratio as r2 on r1.GoodsNo=r2.GoodsNo and r1.`字段`=r2.`字段` and r2.`云仓`='广州云仓' and r2.Date = '{$Date}'
        
        left join 
        ea_size_warehouse_ratio as r3 on r1.GoodsNo=r3.GoodsNo and r1.`字段`=r3.`字段` and r3.`云仓`='南昌云仓' and r3.Date = '{$Date}'
        
        left join 
        ea_size_warehouse_ratio as r4 on r1.GoodsNo=r4.GoodsNo and r1.`字段`=r4.`字段` and r4.`云仓`='武汉云仓' and r4.Date = '{$Date}'
         
        left join 
        ea_size_warehouse_ratio as r5 on r1.GoodsNo=r5.GoodsNo and r1.`字段`=r5.`字段` and r5.`云仓`='长沙云仓' and r5.Date = '{$Date}'
        
        left join 
        ea_size_warehouse_ratio as r6 on r1.GoodsNo=r6.GoodsNo and r1.`字段`=r6.`字段` and r6.`云仓`='贵阳云仓' and r6.Date = '{$Date}'
        
        where r1.Date = '{$Date}' and r1.GoodsNo = '{$goodsno}'  and r1.`云仓`='广州云仓'  GROUP BY r1.`字段`,r1.GoodsNo  ORDER BY r1.id asc ";
        // 执行查询
        $list = Db::query($sql);
        return $list;
    }
}