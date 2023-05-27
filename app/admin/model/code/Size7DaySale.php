<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;

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
            '店铺库存00/28/37/44/100/160/S',
            '店铺库存29/38/46/105/165/M',
            '店铺库存30/39/48/110/170/L',
            '店铺库存31/40/50/115/175/XL',
            '店铺库存32/41/52/120/180/2XL',
            '店铺库存33/42/54/125/185/3XL',
            '店铺库存34/43/56/190/4XL',
            '店铺库存35/44/58/195/5XL',
            '店铺库存36/6XL',
            '店铺库存38/7XL',
            '店铺库存40/8XL'
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