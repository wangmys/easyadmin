<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\common\logic\inventory;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use app\admin\model\dress\Yinliu;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\YinliuStore;

/**
 * 逻辑层
 * Class AuthService
 * @package app\common\logic
 */
class DressLogic
{


    /***
     * 构造方法
     * DressLogic constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        $this->yinliu = new Yinliu;
        $this->question = new YinliuQuestion;
        $this->yinliuStore = new YinliuStore;
    }

    /**
     * 对比配饰库存完成率
     * @param string $start_date
     * @param string $end_date
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function contrastYinliuFinishRate($start_date = '2023-03-20' , $end_date = '2023-03-21')
    {
        if(empty($start_date) || empty($end_date)) return [];
        if(strtotime($end_date) > time()){
            return [];
        }
        // 查询所有商品负责人
        $charge_list = $this->yinliuStore->where([
            'Date' => $start_date
        ])->select();
        // 获取配置
        $config = sysconfig('stock_warn');
        // 配饰列表
        $goods_list = AdminConstant::ACCESSORIES_LIST;
        // 负责人店铺完成信息
        $charge_finish_info = [];
        foreach ($charge_list as $key => $value){
            $info = [
                '商品负责人' => $value['商品负责人']
            ];
            // 查询结束时间节点时开始时间配饰库存不达标的店铺数
            foreach ($goods_list as $kk => $vv){
                if($value[$vv]){
                    // 获取标准判断
                    $standard = $config[$vv];
                    // 开始时间配饰不合格店铺
                    $not_list = explode(',',$value[$vv]);
                    // 查询结束时间时对应商品库存低于标准的店铺数量
                    $store_list = $this->yinliu->where([
                        '商品负责人' => $value['商品负责人'],
                        'Date' => $end_date,
                        '店铺名称' => $not_list
                    ])->where(function ($q)use($vv,$standard){
                        if($standard > 0){
                            $q->whereNull($vv);
                        }
                        $q->whereOr($vv,'<',$standard);
                    })->column('店铺名称');
                    // 计算当前配饰店铺未完成率 = 结束店铺数量 / 开始店铺数量
                    $store_count = count($store_list);
                    $info[$vv] = bcadd($store_count / count($not_list),0,2);
                    if($info[$vv] > 0){
                        $info[$vv] = ($info[$vv] * 100).'%';
                    }else{
                        $info[$vv] = '已完成';
                    }
                }else{
                    $info[$vv] = '';
                }
            }
             $charge_finish_info[] = $info;
        }
        return $charge_finish_info;
    }

}