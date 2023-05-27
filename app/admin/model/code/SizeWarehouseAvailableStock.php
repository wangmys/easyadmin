<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;

class SizeWarehouseAvailableStock extends TimeModel
{
    // 表名
    protected $name = 'size_warehouse_available_stock';

    /**
     * 获取尺码字段
     */
    public static function getSizeKey($key)
    {
        // 总尺码
        $key_arr = [
            '可用库存_00/28/37/44/100/160/S',
            '可用库存_29/38/46/105/165/M',
            '可用库存_30/39/48/110/170/L',
            '可用库存_31/40/50/115/175/XL',
            '可用库存_32/41/52/120/180/2XL',
            '可用库存_33/42/54/125/185/3XL',
            '可用库存_34/43/56/190/4XL',
            '可用库存_35/44/58/195/5XL',
            '可用库存_36/6XL',
            '可用库存_38/7XL',
            '可用库存_40/8XL'
        ];
        // 匹配尺码
        foreach ($key_arr as $k => $v){
            if(strpos($v,$key) !== false){
                return $v;
            }
        }
        return $key;
    }
}