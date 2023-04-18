<?php
namespace app\admin\model\budongxiao;

use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class CwlBudongxiaoStatistics extends TimeModel
{

    protected $connection = 'mysql';
    // protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;
    protected $table = 'cwl_budongxiao_statistics';


    // 添加负责人
    public static function addStatic($data = []) {
        // dump($data);die;
        $res = (new self)
        ->allowField(['商品负责人', '省份', '云仓简称', '店铺简称', '经营性质', '季节归集', '预计SKC数' ,'考核结果','5-10天','10-15天','15-20天','20-30天',
        '30天以上', '【考核标准】键', '【考核标准】值', '需要调整SKC数', 'rand_code', 'create_time']) 
        ->save($data);
        return $res;
    } 

    // 查找负责人
    public static function getPeople($map = []) {
        // dump($data);die;
        // $res = self::where($map)
        // ->field('商品负责人, count(*) as 总家数')
        // ->group('商品负责人')
        // ->select()
        // ->toArray();
        // return $res;

        $res = self::alias('a')
        ->leftJoin(['cwl_budongxiao_statistics' => 'b'], 'a.商品负责人 = b.商品负责人')
        
        ->where([
            ['b.考核结果', '=', '合格']
        ])
        ->field('a.商品负责人, count(*) as 总家数')
        ->group('商品负责人')
        ->select()
        ->toArray();
        echo self::getLastSql();
        return $res;
    } 

}
