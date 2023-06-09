<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;
use think\facade\Db;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;

class Size7DaySale extends TimeModel
{
    // 表名
    protected $name = 'size_7day_sale';

    /**
     * 获取尺码字段
     */
    public static function getSizeKey($key)
    {
        // 总尺码
        $key_arr = [
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
        // 匹配尺码
        foreach ($key_arr as $k => $v){
            if(strpos($v,$key) !== false){
                return $v;
            }
        }
        return $key;
    }

    /**
     * 保存数据
     */
    public static function saveData($goodsno = 'B31502006')
    {
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
        // 查询云仓在途库存
        // 单款累销
        $all_total = SizeAccumulatedSale::where(['货号' => $goodsno])->column($fieldStr);
        // 货号
        $arr = [$goodsno];
        foreach ($arr as $key => $val){
             // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods eg')->leftJoin('ErpGoodsImg egi','eg.GoodsId = egi.GoodsId')
                        ->field('eg.GoodsId,eg.GoodsNo,
                        eg.GoodsName,
                        eg.UnitPrice,
                        eg.CategoryName,
                        eg.CategoryName1,
                        eg.CategoryName2,
                        eg.TimeCategoryName1,
                        eg.TimeCategoryName2,
                        eg.StyleCategoryName,
                        eg.StyleCategoryName2,
                        LEFT(eg.CategoryName,2) as Collar,
                        egi.Img')
                    ->where([
                    'GoodsNo' => $val
                ])->find();

                echo '<pre>';
                print_r($info);
                die;
        }
    }
}