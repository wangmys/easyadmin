<?php
namespace app\admin\service\accessories;


use app\admin\model\accessories\AccessoriesStock;
use app\admin\model\accessories\AccessoriesSale;
use app\admin\model\accessories\AccessoriesHead;
use app\admin\model\accessories\AccessoriesWarStock;
use app\common\constants\AdminConstant;
use app\common\logic\accessories\AccessoriesLogic;

class AccessoriesService
{
    /**
     * 当前实例
     * @var object
     */
    protected static $instance;

    // 引流库存实例
    public $stock = null;
    // 引流销售实例
    public $sale = null;

    /**
     * 构造方法
     * SystemLogService constructor.
     */
    protected function __construct()
    {
        $this->stock = new AccessoriesStock;
        $this->sale = new AccessoriesSale;
        $this->accessoriesHead = new AccessoriesHead;
        $this->warStock = new AccessoriesWarStock;
        $this->logic = new AccessoriesLogic;
        return $this;
    }

    /**
     * 获取实例对象
     * @return AccessoriesService|object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 获取静态查询
     */
    public function getTableField()
    {
        $head = $this->accessoriesHead::where(['state' => 1])->column('name,field','id');
        return $head;
    }

    /**
     * 获取查询动态字段
     * @param string $alias
     * @return string
     */
    public function getTrendsField($alias = '')
    {
         $head = $this->accessoriesHead::where(['state' => 1])->column('name,field','id');
         $field = '';
         foreach ($head as $k=>$v){
            // 计算字段合并,多字段相加
            $field_arr = explode(',',$v['field']);
            $field_str = '';
            foreach ($field_arr as $fk =>$fv){
                if($alias) $fv = $alias.".".$fv;
                $field_str .= " IFNULL($fv,0) +";
            }
            // 清空多余字符串
            $field_str = trim($field_str,'+');
            // 拼接查询字段
            $field .= ",( $field_str ) as {$v['name']}";
        }
        // 清空多余字符串
        $field = trim($field,',');
        return $field;
    }

    /**
     * 获取固定字段
     * @param string $alias
     * @param int $type
     * @return string 返回格式 0字符串 1数组
     */
    public function getFixField($alias = '',$type = 0)
    {
        $fix_field = [
            '店铺ID' => 'CustomerId',
            '商品负责人' =>'CustomItem17',
            '店铺名称' =>'CustomerName',
            '店铺等级' => 'CustomerGrade',
            '省份' =>'State',
            '运营模式' =>'Mathod'
        ];
        $fix_str = '';
        foreach ($fix_field as $k => &$v){
            switch ($type){
                case 0:
                    if(empty($alias)){
                        $fix_str .= "{$v},";
                    }else{
                        $fix_str .= "$alias.{$v},";
                    }
                    break;
                default:
                    if(!empty($alias)){
                        $v = "$alias.{$v}";
                    }
                    break;
            }
        }
        return $type==0?$fix_str:$fix_field;
    }

    /**
     * 查询配饰库存
     * @param string $Date     筛选日期
     * @param int $is_result   是否展示结果
     * @return array
     */
    public function getTableBody($Date = '',$is_result = 0)
    {
        $Date = $Date?$Date:date('Y-m-d');
        // 获取动态组合字段
        $trends_field = $this->getTrendsField('s');
        // 固定字段
        $fix_field = $this->getFixField('c');
        // 组合完整字段
        $field = str_replace('c.State','left(c.State,2) as State',$fix_field).$trends_field;



        // 查询库存数据
        $model = $this->stock->alias('s')->leftjoin(['customer'=>'c'],'s.CustomerId = c.CustomerId')->field($field)->where([
            'ShutOut' => 0,
            'Date' => $Date
        ]);

        // 获取当前登录信息
        $user = session('admin');
        // 超级管理员ID
        $admin_id = AdminConstant::SUPER_ADMIN_ID;
        // 如果用户不为超管,则进行商品负责人筛选
        if($user['id'] != $admin_id){
            $model->where([
                'CustomItem17' => $user['name']
            ]);
        }
        // 筛选掉配饰排除的店铺
        $model = $this->setStoreFilter($model);
        // 查询库存数据
        $stock_list = $model->order('CustomItem17 desc,State')->where('Region','<>','闭店区')->select()->toArray();
        // 获取库存预警配置
        $sysconfig = $this->logic->warStockItem();
        // 查询销量数据
        $sale_list = $this->sale->where([
            'Date' => $Date
        ])->column($this->getTrendsField(),'CustomerId');
        $new_list = [];
        // 循环计算库存数据的周转数
        foreach ($stock_list as $k => &$v){
            // 根据店铺ID,获取销量数据
            $saleItem = $sale_list[$v['CustomerId']]??[];
            if(!empty($saleItem)){
                // 销毁店铺ID
                unset($saleItem['CustomerId']);

                // 循环销售数据的每一项,计算库存数据
                foreach ($saleItem as $kk => $vv){
                    // 库存
                    $stockValue = $v[$kk];
                    // 库存为0,周转则为0
                    if(empty($stockValue)){
                        $v['_'.$kk] = 0;
                    }else if(empty($vv)){
                        // 销量为0,则周转为库存
                        $v['_'.$kk]  = 0;
                    }else{
                        // 计算周转( 库存 / 一周销量 )
                        $v['_'.$kk] = bcadd($stockValue / $vv,0,1);
                    }
               }
            }else{
                $saleItem_data = reset($sysconfig);
                $saleItem = $saleItem_data['_data'];
                foreach ($saleItem as $kk => $vv){
                    $v['_'.$kk] = 0;
                }
            }
            // 根据筛选条件,设置颜色是否标红
            $this->setStyle($v,$sysconfig,$new_list);
        }
        // 是否只需要标红(结果)数据
        if($is_result){
            return  $new_list;
        }
        return $stock_list;
    }

    /**
     * 根据引流配置,判断库存是否标红
     * @param $list
     * @param $config
     * @param $new_list  返回有标红的数据
     * @return mixed
     */
    public function setStyle(&$list,$config,&$new_list)
    {
        if(empty($list) || empty($list['CustomerGrade'])) return $list;
        $isWarning = 0;
        foreach ($list as $k => $v){
            if((strpos($k,'_') === false)){
                // 等级配置
                $item = $config["{$list['CustomerGrade']}_库存"];
                // 具体配置
                $_data = $item['_data'];
                if(isset($_data[$k]) && !empty($_data[$k])){
                    $vv = intval($v);
                    if($vv < $_data[$k]){
                        $list[$k] = "<span style='width: 100%;display: block;background: rgba(255,0,0,.2)'>{$v}</span>";
                        $isWarning = 1;
                    }
                }
            }else{
                // 等级配置
                $item = $config["{$list['CustomerGrade']}_周转"];
                $key = str_replace('_','',$k);
                // 具体配置
                $_data = $item['_data'];
                if(isset($_data[$key]) && !empty($_data[$key]) && !empty($v)){
                    $vv = intval($v);
                    if($vv < $_data[$key]){
                        $list[$k] = "<span style='width: 100%;display: block;background: rgba(255,0,0,.2)'>{$v}</span>";;
                    }
                }
            }
        }
        if($isWarning) $new_list[] = $list;
        return $list;
    }

    /**
     * 设置引流款门店筛选
     */
    public function setStoreFilter($model,$name = 'accessories_store_list')
    {
        // 获取配饰门店排除列表
        $storeList = sysconfig('site',$name);
        if(empty($storeList) || empty($model)) return $model;
        return $model->whereNotIn('CustomerName',$storeList);
    }
}