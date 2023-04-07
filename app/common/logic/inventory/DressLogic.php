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
    public function contrastYinliuFinishRate($start_date = '' , $end_date = '')
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
                    $f_count = count($not_list) - $store_count;
                    $info[$vv] = count($not_list);
                    $info["{$vv}_1"] = $f_count;
                    $info["{$vv}_2"] = $store_count;
                }else{
                    $info[$vv] = '';
                    $info["{$vv}_1"] = '';
                    $info["{$vv}_2"] = '';
                }
            }
             $charge_finish_info[] = $info;
        }
        return $charge_finish_info;
    }


    /**
     * 获取对比结果
     * @param $get
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getComparisonResult($get)
    {
        // 指定商品负责人
        $charge = $get['商品负责人']??'曹太阳';
        // 获取开始至结束日期
        $date_all = getThisDayToStartDate();
        // 开始日期
        $start_date = $date_all[0];
        // 结束日期
        $end_date = $date_all[1];
        // 查询负责人待完成店铺
        $model = new YinliuStore;
        // 查询开始数据
        $info = $model->where([
            '商品负责人' => $charge,
            'Date' => $start_date
        ])->find();
        $info_1 = $model->where([
            '商品负责人' => $charge,
            'Date' => $end_date
        ])->find();
        $merge_data = [];
        $list = AdminConstant::ACCESSORIES_LIST;
         // 获取配置
        $config = sysconfig('stock_warn');
        $arr1 = [];
        if($info && $info_1){
            $info = $info->toArray();
            foreach ($info as $k => $v){
                if(in_array($k,$list)){
                    // 获取标准判断
                    $standard = $config[$k];
                    $arr = ['商品负责人' => $info['商品负责人']];
                    $arr['配饰'] = $k;
                    if(empty($v)){
                        $store_arr = [];
                    }else{
                        $store_arr = explode(',',$v);
                    }
                    $arr['问题店铺'] = count($store_arr);
                    // 剩余店铺
                    $store_list = Yinliu::where([
                        '商品负责人' => $info['商品负责人'],
                        'Date' => $end_date,
                        '店铺名称' => $store_arr
                    ])->where(function ($q)use($k,$standard){
                        if($standard > 0){
                            $q->whereNull($k);
                        }
                        $q->whereOr($k,'<',$standard);
                    })->column('店铺名称');
                    $arr['剩余店铺'] = count($store_list);
                    $arr['已处理'] = $arr['问题店铺'] - $arr['剩余店铺'];
                    $arr1[] = $arr;
                }
            }
            return $arr1;
        }
    }
}