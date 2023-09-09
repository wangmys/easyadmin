<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\facade\Db;

class Spsk extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'mysql2';

    // 表名
    protected $name = 'sk';

    public function getList()
    {
        // 实例化
        $model = new self();
    }

    /**
     * 获取各下拉列表数据
     * @return void
     */
    public function get_spsk_select() {

        $model = Db::connect('mysql2');
        $arr = [];
        $store_sql = "select distinct 店铺名称 from sp_sk;";
        $jijie_sql = "select distinct 季节 from sp_sk;";
        $yijifenlei_sql = "select distinct 一级分类 from sp_sk;";
        $erjifenlei_sql = "select distinct 二级分类 from sp_sk;";
        $fenlei_sql = "select distinct 分类 from sp_sk;";
        $fengge_sql = "select distinct 风格 from sp_sk;";
        $goods_manager_sql = "select distinct 商品负责人 from sp_sk where 商品负责人 <> '';";
        $province_sql = "select distinct 省份 from sp_sk;";
        $mathod_sql = "select distinct 经营模式 from sp_sk;";
        $store = $model->query($store_sql);
        $jijie = $model->query($jijie_sql);
        $yijifenlei = $model->query($yijifenlei_sql);
        $erjifenlei = $model->query($erjifenlei_sql);
        $fenlei = $model->query($fenlei_sql);
        $fengge= $model->query($fengge_sql);
        $goods_manager= $model->query($goods_manager_sql);
        $province= $model->query($province_sql);
        $mathod= $model->query($mathod_sql);

        $store_temp = array_unique(array_column($store,'店铺名称'));
        $store = array_combine($store_temp,$store_temp);

        $jijie_temp = array_unique(array_column($jijie,'季节'));
        $jijie = array_combine($jijie_temp,$jijie_temp);

        $yijifenlei_temp = array_unique(array_column($yijifenlei,'一级分类'));
        $yijifenlei = array_combine($yijifenlei_temp,$yijifenlei_temp);

        $erjifenlei_temp = array_unique(array_column($erjifenlei,'二级分类'));
        $erjifenlei = array_combine($erjifenlei_temp,$erjifenlei_temp);

        $fenlei_temp = array_unique(array_column($fenlei,'分类'));
        $fenlei = array_combine($fenlei_temp,$fenlei_temp);

        $fengge_temp = array_unique(array_column($fengge,'风格'));
        $fengge = array_combine($fengge_temp,$fengge_temp);

        $goods_manager_temp = array_unique(array_column($goods_manager,'商品负责人'));
        $goods_manager = array_combine($goods_manager_temp,$goods_manager_temp);

        $province_temp = array_unique(array_column($province,'省份'));
        $province = array_combine($province_temp,$province_temp);

        $mathod_temp = array_unique(array_column($mathod,'经营模式'));
        $mathod = array_combine($mathod_temp,$mathod_temp);

        $arr['store'] = $store;
        $arr['jijie'] = $jijie;
        $arr['yijifenlei'] = $yijifenlei;
        $arr['erjifenlei'] = $erjifenlei;
        $arr['fenlei'] = $fenlei;
        $arr['fengge'] = $fengge;
        $arr['goods_manager'] = $goods_manager;
        $arr['province'] = $province;
        $arr['mathod'] = $mathod;
        return $arr;

    }



}