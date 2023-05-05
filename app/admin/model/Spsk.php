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

        $arr = [];
        $store_sql = "select distinct 店铺名称 from sp_sk;";
        $jijie_sql = "select distinct 季节 from sp_sk;";
        $yijifenlei_sql = "select distinct 一级分类 from sp_sk;";
        $erjifenlei_sql = "select distinct 二级分类 from sp_sk;";
        $fenlei_sql = "select distinct 分类 from sp_sk;";
        $fengge_sql = "select distinct 风格 from sp_sk;";
        $store = Db::connect('mysql2')->query($store_sql);
        $jijie = Db::connect('mysql2')->query($jijie_sql);
        $yijifenlei = Db::connect('mysql2')->query($yijifenlei_sql);
        $erjifenlei = Db::connect('mysql2')->query($erjifenlei_sql);
        $fenlei = Db::connect('mysql2')->query($fenlei_sql);
        $fengge= Db::connect('mysql2')->query($fengge_sql);

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

        $arr['store'] = $store;
        $arr['jijie'] = $jijie;
        $arr['yijifenlei'] = $yijifenlei;
        $arr['erjifenlei'] = $erjifenlei;
        $arr['fenlei'] = $fenlei;
        $arr['fengge'] = $fengge;
        return $arr;

    }



}