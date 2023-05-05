<?php

namespace app\common\logic\accessories;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Cache;
use think\facade\Db;
use app\admin\model\accessories\AccessoriesHead;
use app\admin\model\accessories\AccessoriesWarStock;

/**
 * 逻辑层
 * Class AccessoriesLogic
 * @package app\common\logic
 */
class AccessoriesLogic
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
        $this->accessoriesHead = new AccessoriesHead;
        $this->warStock = new AccessoriesWarStock;
    }

    /**
     * 获取配置项表头字段
     * @return array
     */
    public function getSysHead()
    {
        $head = $this->accessoriesHead::where(['state' => 1])->column('name,field','id');
        // 查询所有分类名
        $column = $this->getTableRow();
        $head_list = [];
        foreach ($head as $k => $v){
            $v['field'] = array_map(function($val)use($v){
                $item = explode(',',$v['field']);
                if(in_array($val['name'],$item)) $val['selected'] = true;
                return $val;
            }, $column);
            $head_list[] = $v;
        }
        return $head_list;
    }



    /**
     * 获取所有店铺等级
     */
    public function getLevel()
    {
        $levelName = Db::connect('sqlsrv')->table('ErpCustomer')
            ->where([
                ['CustomItem16', '<>', '']
            ])->group('CustomItem16')
            ->order('CustomItem16','desc')
            ->column('CustomItem16');
        // 库存维度
        $levelList = [];
        // 销量维度
        $_levelList = [];
        foreach ($levelName as $k => $v){
            $levelList[] = [
                'name' => $v.'_库存',
                'value' => $v.'_库存'
            ];
            $_levelList[] = [
                'name' => $v.'_周转',
                'value' => $v.'_周转'
            ];
        }
        $allList = array_merge($levelList,$_levelList);
        return $allList;
    }

    /**
     * 查询所有分类名
     */
    public function getTableRow()
    {
        $levelName = Cache::get('AccessoriesField');
        if(empty($levelName)){
            $sql = "SELECT CategoryName,CategoryId from ErpBaseGoodsCategory where  ParentId in (
    SELECT CategoryId from ErpBaseGoodsCategory where  ParentId =
    (SELECT CategoryId FROM ErpBaseGoodsCategory where CategoryName = '配饰'))";
            $levelName = Db::connect('mysql2')->query($sql);
//            $sql = "";
//            $levelName = "selet * from sp_customer_yinliu_sale limit 1";
            Cache::set('AccessoriesField',$levelName);
        }
        $new_field = [];
        foreach ($levelName as $k=>$v){
            $item = [
                'name' => $v['CategoryName'],
                'value' => $v['CategoryName']
            ];
            $new_field[] = $item;
        }
        return $new_field;
    }

    /**
     * 设置默认店铺等级选中
     */
    public function setLevel($level_str,$level_list)
    {
        $list = explode(',',$level_str);
        foreach ($level_list as $key => &$v){
            if(in_array($v['name'],$list)){
                $v['selected'] = true;
            }
        }
        return $level_list;
    }


    /**
     * 获取预警库存配置
     */
    public function warStockItem()
    {
        // 配置列表
        $config_list = $this->warStock->column('level,content');
        $level_config_list = [];
        foreach ($config_list as $k => $v){
            $level_key = $v['level'];
            $config_item = json_decode($v['content'],true);
            if(isset($config_item['店铺等级'])) unset($config_item['店铺等级']);
            $level_config_list[$level_key] = [
                '店铺等级' => $level_key,
                '_data' => $config_item
            ];
        }
        return $level_config_list;
    }

    /**
     * 保存预警库存配置
     */
    public function saveWarStock($data)
    {
        // 启动事务
        Db::startTrans();
        // 保存数据前,删除所有数据
        try {
            // 清空所有数据
            $this->warStock->where('id','>',0)->delete(true);
            $this->warStock->saveAll($data);
            // 提交事务
            Db::commit();
            return true;
        }catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            return false;
        }
        return false;
    }


    /**
     * 保存表头数
     * @param $data
     * @return bool
     */
    public function saveHead($data)
    {
        $this->accessoriesHead->save($data);
        return $this->accessoriesHead->id;
    }

}