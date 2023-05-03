<?php
namespace app\admin\service\accessories;


use app\admin\model\accessories\AccessoriesStock;
use app\admin\model\accessories\AccessoriesSale;

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
        return $this;
    }

    /**
     * 获取实例对象
     * @return SystemLogService|object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 获取表头
     */
    public function getTableHead()
    {
        //
    }
}
