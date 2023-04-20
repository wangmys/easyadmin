<?php
namespace app\admin\model\budongxiao;
use think\db\Raw;
use app\common\model\TimeModel;

/**
 * @mixin \think\Model
 */
class SpWwBudongxiaoDetail extends TimeModel
{

    protected $connection = 'mysql2';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $table = 'sp_ww_budongxiao_detail';
    protected static $fields = 'd.商品负责人,d.省份,d.季节归集,d.上市时间,d.经营模式,d.店铺名称,d.店铺库存数量,d.云仓,d.货号,d.大类,d.中类,d.小类,d.累销量,d.月销量,d.五天销量,d.十天销量,d.十五天销量,d.二十天销量
    ,d.三十天销量,d.上市天数,d.上架店数,d.总店数,d.上柜率,d.省份售罄,d.品类排名,y.可用库存Quantity,y.齐码情况';

    
    public function details()
    {
        // 参数1：关联模型名称 参数2：关联模型的外键 参数3：当前模型的主键
        return $this->hasOne('SpXwBudongxiaoYuncangkeyong', '仓库名称', '云仓');
    }

    // 获取店铺名称
    public static function getStore($map) {
        // dump($map);
        $where = [
            // ['商品负责人', 'exp', new Raw('IS NOT NULL')],
        ];
        $where[] = ['商品负责人', 'exp', new Raw('IS NOT NULL')];
        // $where['商品负责人'] = ['exp', new Raw('IS NOT NULL')];
        $limit = 10000;
        if (!empty($map['省份'])) {
            // $where['省份'] = $map['省份'];
            $mapArr = arrToStr(explode(',', $map['省份']));
            $where[] = ['省份', 'exp', new Raw("IN ({$mapArr})")]; 
        } 
        if (!empty($map['商品负责人'])) {
            $where['商品负责人'] = $map['商品负责人'];
        } 
        if (!empty($map['店铺名称'])) {
            $where['店铺名称'] = $map['店铺名称'];
        } 
        if (!empty($map['经营模式'])) {
            $where['经营模式'] = $map['经营模式'];
        } 
        if (!empty($map['limit'])) {
            $limit = intval($map['limit']);
        }
        if (!empty($map['不考核门店'])) {
            $mapArr = arrToStr(explode(',', $map['不考核门店']));
            $where[] = ['店铺名称', 'exp', new Raw("NOT IN ({$mapArr})")]; 
        }
        // echo '<pre>';    
        // print_r($where);die;
        $res = self::where($where)
        ->field('商品负责人,店铺名称')
        ->group('商品负责人,店铺名称')  
        ->limit($limit)  
        ->select()
        ->toArray();
        // echo self::getLastSql();
        // die;
        return $res;
    } 

    // 获取接口所需所有门店 
    public static function getMapStore() {
        $res = self::where(1)
        ->field('店铺名称 as name, 店铺名称 as value')
        ->group('店铺名称')  
        ->select()
        ->toArray();
        return $res;
    }

    // 获取码数，没写好的
    public static function getTypeQiMa($map = []) {
        $res = self::where($map)
        ->field('大类,中类,季节归集')
        ->group('季节归集,大类,中类')
        ->select()
        ->toArray();
        // echo self::getLastSql();
        return $res;
    } 

    // 获取商品负责人
    public static function getPeople($map = []) {
        $res = self::where($map)
        ->field('商品负责人')
        ->group('商品负责人')  
        ->select()
        ->toArray();
        return $res;
    } 

    // 获取城市
    public static function getProvince() {
        $res = self::field('省份')
        ->group('省份')  
        ->select()
        ->toArray();
        return $res;
    }

    // 获取城市
    public static function getMapProvince() {
        $res = self::field('省份 as name, 省份 as value')
        ->group('省份')  
        ->select()
        ->toArray();
        return $res;
    }

    // 连接云仓
    public static function joinYuncang_all($map)
    {
        $res = self::alias('d')
        ->leftJoin(['sp_ww_budongxiao_yuncangkeyong' => 'y'], 'd.云仓 = y.仓库名称 AND d.货号 = y.货号')
        ->field(self::$fields)
        ->where($map)
        // ->whereNotNull('商品负责人')
        ->select()
        ->toArray();
        // echo self::getLastSql();
        // die;
        return $res;
    }
}
