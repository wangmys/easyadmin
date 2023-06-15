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

class SizeAllRatio extends TimeModel
{
    // 表名
    protected $name = 'size_all_ratio';

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

            // 周转 = 当前总库存/周销
            if(!empty($all_shop_stock) && !empty($all_total)){
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
                $item['单码售罄比'] = (floatval($item['单码售罄']) - floatval($total_item['单码售罄'])).'%';

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
}