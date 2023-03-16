<?php

namespace app\admin\controller\system\sales;


use app\admin\model\Stocks as StocksM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;

/**
 * Class Admin
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="库存销售")
 */
class Stocks extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new StocksM;
    }

    /**
     * 列表
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            $sql = "SELECT
            T.WenDai,
            T.WenQu,
            IFNULL(T.State, '合计') AS State,
            IFNULL(T.CategoryName1, '合计') AS CategoryName1,
            IFNULL(T.CategoryName2, '合计') AS CategoryName2,
            IFNULL(T.CategoryName, '合计') AS CategoryName,
            CONCAT(
                ROUND(
                    SUM(T.StockCost) / (
                        SELECT
                            SUM(StockCost)
                        FROM
                            sp_customer_stock_sale_year
                        WHERE
                            Date between  '2022-07-01' and '2022-11-30'
                        AND WenDai = '中'
                        AND TimeCategoryName1 = 2022
                        AND TimeCategoryName2 = '初秋'
                    ) * 100,
                    2
                ),
                '%'
            ) AS sale_rate,
            CONCAT(
                ROUND(
                    SUM(T.SaleQuantity) / (
                        SELECT
                            SUM(SaleQuantity)
                        FROM
                            sp_customer_stock_sale_year
                        WHERE
                            Date between  '2022-07-01' and '2022-11-30'
                        AND WenDai = '中'
                        AND TimeCategoryName1 = 2022
                        AND TimeCategoryName2 = '初秋'
                    ) * 100,
                    2
                ),
                '%'
            ) AS stock_rate,
            CONCAT(
                ROUND(
                    (
                        SUM(T.StockCost) / (
                            SELECT
                                SUM(StockCost)
                            FROM
                                sp_customer_stock_sale_year
                            WHERE
                                Date between  '2022-07-01' and '2022-11-30'
                            AND WenDai = '中'
                            AND TimeCategoryName1 = 2022
                            AND TimeCategoryName2 = '初秋'
                        ) * 100
                    ) / (
                        SUM(T.SaleQuantity) / (
                            SELECT
                                SUM(SaleQuantity)
                            FROM
                                sp_customer_stock_sale_year
                            WHERE
                                Date between  '2022-07-01' and '2022-11-30'
                            AND WenDai = '中'
                            AND TimeCategoryName1 = 2022
                            AND TimeCategoryName2 = '初秋'
                        ) * 100
                    ) * 100,
                    2
                ),
                '%'
            ) AS xl_rate,
            CONCAT(
                ROUND(
                    SUM(T.SalesVolume) / SUM(T.RetailAmount) * 100,
                    2
                ),
                '%'
            ) AS discount,
            CONCAT(
                ROUND(
                    (
                        SUM(T.SalesVolume) - SUM(T.CostAmount)
                    ) / SUM(T.SalesVolume) * 100,
                    2
                ),
                '%'
            ) AS profit_rate
        FROM
            `sp_customer_stock_sale_year` T
        WHERE
            T.Date between  '2022-07-01' and '2022-11-30'
        AND T.WenDai = '中'
        AND T.TimeCategoryName1 = 2022
        AND T.TimeCategoryName2 = '初秋'
        GROUP BY
            T.WenDai,
            T.WenQu,
            T.State,
            T.CategoryName1,
            T.CategoryName2,
            CategoryName WITH ROLLUP";

            $list = Db::connect("mysql2")->query($sql);
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => 2500,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }

            $sql = "SELECT
	T.WenDai,
	T.WenQu,
	IFNULL(T.State, '合计') AS State,
	IFNULL(T.CategoryName1, '合计') AS CategoryName1,
	IFNULL(T.CategoryName2, '合计') AS CategoryName2,
	IFNULL(T.CategoryName, '合计') AS CategoryName,
	CONCAT(
		ROUND(
			SUM(T.StockCost) / (
				SELECT
					SUM(StockCost)
				FROM
					sp_customer_stock_sale_year
				WHERE
					Date between  '2022-07-01' and '2022-11-30'
				AND WenDai = '中'
				AND TimeCategoryName1 = 2022
				AND TimeCategoryName2 = '初秋'
			) * 100,
			2
		),
		'%'
	) AS 销售占比,
	CONCAT(
		ROUND(
			SUM(T.SaleQuantity) / (
				SELECT
					SUM(SaleQuantity)
				FROM
					sp_customer_stock_sale_year
				WHERE
					Date between  '2022-07-01' and '2022-11-30'
				AND WenDai = '中'
				AND TimeCategoryName1 = 2022
				AND TimeCategoryName2 = '初秋'
			) * 100,
			2
		),
		'%'
	) AS 库存占比,
	CONCAT(
		ROUND(
			(
				SUM(T.StockCost) / (
					SELECT
						SUM(StockCost)
					FROM
						sp_customer_stock_sale_year
					WHERE
						Date between  '2022-07-01' and '2022-11-30'
					AND WenDai = '中'
					AND TimeCategoryName1 = 2022
					AND TimeCategoryName2 = '初秋'
				) * 100
			) / (
				SUM(T.SaleQuantity) / (
					SELECT
						SUM(SaleQuantity)
					FROM
						sp_customer_stock_sale_year
					WHERE
						Date between  '2022-07-01' and '2022-11-30'
					AND WenDai = '中'
					AND TimeCategoryName1 = 2022
					AND TimeCategoryName2 = '初秋'
				) * 100
			) * 100,
			2
		),
		'%'
	) AS 效率,
	CONCAT(
		ROUND(
			SUM(T.SalesVolume) / SUM(T.RetailAmount) * 100,
			2
		),
		'%'
	) AS 折扣,
	CONCAT(
		ROUND(
			(
				SUM(T.SalesVolume) - SUM(T.CostAmount)
			) / SUM(T.SalesVolume) * 100,
			2
		),
		'%'
	) AS 毛利
FROM
	`sp_customer_stock_sale_year` T
WHERE
	T.Date between  '2022-07-01' and '2022-11-30'
AND T.WenDai = '中'
AND T.TimeCategoryName1 = 2022
AND T.TimeCategoryName2 = '初秋'
GROUP BY
	T.WenDai,
	T.WenQu,
	T.State,
	T.CategoryName1,
	T.CategoryName2,
	CategoryName WITH ROLLUP";

            $list = Db::connect("mysql2")->query($sql);

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => 0,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 获取一级分类
     */
    public function getCate1()
    {
        // 获取温区分组
        $sql = "SELECT 
            IFNULL(T.WenDai,'合计') AS WenDai,
            SUM(T.StockCost) as StockCostSum,
            SUM(T.SalesVolume) as SalesVolumeSum,
            SUM(T.CostAmount) as CostAmountSum,
            SUM(T.RetailAmount) as RetailAmountSum
        FROM `sp_customer_stock_sale_year` T
        WHERE T.Date between '2022-01-01' and '2022-01-02'
        GROUP BY
            T.WenDai
            ";

        $list = Db::connect("mysql2")->query($sql);
        if($list){
            // 总计销售额
            $SalesVolumeTotal = array_sum(array_column($list,'SalesVolumeSum'));
            // 总计库存成本金额
            $StockCostTotal = array_sum(array_column($list,'StockCostSum'));

            foreach ($list as $key => &$val){
                // 销售占比 = 单项销售金额 / 总计销售金额
                $sale_rate = bcadd($val['SalesVolumeSum'] / $SalesVolumeTotal * 100,0,3);
                $val['sale_rate'] = $sale_rate . '%';
                // 库存占比 = 库存成本金额 / 总计库存成本金额
                $sotck_rate = bcadd($val['StockCostSum'] / $StockCostTotal * 100,0,3);
                $val['stock_rate'] = $sotck_rate . '%';
                // 效率 = 销售占比 / 库存占比
//                $val['xl_rate'] = round(). '%';
                $val['xl_rate'] = round(bcadd($sale_rate / $sotck_rate * 100,0,3),3,3). '%';
                // 折扣 = 累计销售金额 / 累计销售零售金额
                $val['discount'] = round(bcadd($val['SalesVolumeSum'] / $val['RetailAmountSum'] * 10,0,3),3);
                // 毛利 = (累计销售金额 - 累计销售成本) / 累计销售金额
                $val['profit_rate'] = round(bcadd(($val['SalesVolumeSum'] - $val['CostAmountSum']) / $val['SalesVolumeSum'] * 100,0,3),3) . '%';
            }
        }

        $data = [
            'code'  => 0,
            'msg'   => '',
            'count' => 0,
            'data'  => $list,
        ];
        return json($data);
    }

    /**
     * 获取一级分类
     */
    public function getCate2()
    {
        // 获取温区分组
        $sql = "SELECT 
                T.WenDai,
                IFNULL(T.CategoryName1,'合计') AS CategoryName1,
                SUM(T.StockCost) as StockCostSum,
                SUM(T.SalesVolume) as SalesVolumeSum,
                SUM(T.CostAmount) as CostAmountSum,
                SUM(T.RetailAmount) as RetailAmountSum
            FROM `sp_customer_stock_sale_year` T
            WHERE T.Date between '2022-01-01' and '2022-01-02' and T.WenDai = '中'
            GROUP BY
                T.CategoryName1
            ";

        $list = Db::connect("mysql2")->query($sql);
        if($list){
            // 总计销售额
            $SalesVolumeTotal = array_sum(array_column($list,'SalesVolumeSum'));
            // 总计库存成本金额
            $StockCostTotal = array_sum(array_column($list,'StockCostSum'));

            foreach ($list as $key => &$val){
                // 销售占比 = 单项销售金额 / 总计销售金额
                $sale_rate = bcadd($val['SalesVolumeSum'] / $SalesVolumeTotal * 100,0,3);
                $val['sale_rate'] = $sale_rate . '%';
                // 库存占比 = 库存成本金额 / 总计库存成本金额
                $sotck_rate = bcadd($val['StockCostSum'] / $StockCostTotal * 100,0,3);
                $val['stock_rate'] = $sotck_rate . '%';
                // 效率 = 销售占比 / 库存占比
                $val['xl_rate'] = bcadd($sale_rate / $sotck_rate * 100,0,3). '%';
                // 折扣 = 累计销售金额 / 累计销售零售金额
                $val['discount'] = bcadd($val['SalesVolumeSum'] / $val['RetailAmountSum'] * 10,0,3);
                // 毛利 = (累计销售金额 - 累计销售成本) / 累计销售金额
                $val['profit_rate'] = bcadd(($val['SalesVolumeSum'] - $val['CostAmountSum']) / $val['SalesVolumeSum'] * 100,0,3) . '%';
            }
        }
        $data = [
            'code'  => 0,
            'msg'   => '',
            'count' => 0,
            'data'  => $list,
        ];
        return json($data);
    }

    /**
     * 获取一级分类
     */
    public function getCate3()
    {
        // 获取温区分组
        $sql = "SELECT 
                IFNULL(T.WenDai,'合计') AS WenDai,
                IFNULL(T.CategoryName1,'合计') AS CategoryName1,
                IFNULL(T.CategoryName2,'合计') AS CategoryName2,
                SUM(T.StockCost) as StockCostSum,
                SUM(T.SalesVolume) as SalesVolumeSum,
                SUM(T.CostAmount) as CostAmountSum,
                SUM(T.RetailAmount) as RetailAmountSum
            FROM `sp_customer_stock_sale_year` T
            WHERE T.Date between '2022-01-01' and '2022-01-02' and T.WenDai = '中' and T.CategoryName1 = '下装'
            GROUP BY
                T.CategoryName2
            ";

        $list = Db::connect("mysql2")->query($sql);

        if($list){
            // 总计销售额
            $SalesVolumeTotal = array_sum(array_column($list,'SalesVolumeSum'));
            // 总计库存成本金额
            $StockCostTotal = array_sum(array_column($list,'StockCostSum'));

            foreach ($list as $key => &$val){
                if($val['SalesVolumeSum'] > 0 && $SalesVolumeTotal > 0){
                    // 销售占比 = 单项销售金额 / 总计销售金额
                    $sale_rate = bcadd($val['SalesVolumeSum'] / $SalesVolumeTotal * 100,0,3);
                }else{
                    $sale_rate = 0;
                }
                $val['sale_rate'] = $sale_rate . '%';
                if($val['StockCostSum'] > 0 && $StockCostTotal > 0){
                    // 库存占比 = 库存成本金额 / 总计库存成本金额
                    $sotck_rate = bcadd($val['StockCostSum'] / $StockCostTotal * 100,0,3);
                }else{
                    $sotck_rate = 0;
                }

                $val['stock_rate'] = $sotck_rate . '%';
                if($sale_rate <= 0 || $sotck_rate <= 0){
                    $val['xl_rate'] = '0%';
                }else{
                    // 效率 = 销售占比 / 库存占比
                    $val['xl_rate'] = bcadd($sale_rate / $sotck_rate * 100,0,3). '%';
                }
                if($val['SalesVolumeSum'] > 0 && $val['RetailAmountSum'] > 0){
                    // 折扣 = 累计销售金额 / 累计销售零售金额
                    $val['discount'] = bcadd($val['SalesVolumeSum'] / $val['RetailAmountSum'] * 10,0,3);
                }else{
                    $val['discount'] = 0;
                }
                if($val['SalesVolumeSum'] > 0 && $val['CostAmountSum'] > 0){
                    // 毛利 = (累计销售金额 - 累计销售成本) / 累计销售金额
                    $val['profit_rate'] = bcadd(($val['SalesVolumeSum'] - $val['CostAmountSum']) / $val['SalesVolumeSum'] * 100,0,3) . '%';
                }else{
                    $val['profit_rate'] = 0;
                }
            }
        }

        $data = [
            'code'  => 0,
            'msg'   => '',
            'count' => 0,
            'data'  => $list,
        ];
        return json($data);
    }

    /**
     * 获取一级分类
     */
    public function getCate4()
    {
        // 获取温区分组
        $sql = "SELECT 
                IFNULL(T.WenDai,'合计') AS WenDai,
                IFNULL(T.CategoryName1,'合计') AS CategoryName1,
                IFNULL(T.CategoryName2,'合计') AS CategoryName2,
                IFNULL(T.CategoryName,'合计') AS CategoryName,
                SUM(T.StockCost) as StockCostSum,
                SUM(T.SalesVolume) as SalesVolumeSum,
                SUM(T.CostAmount) as CostAmountSum,
                SUM(T.RetailAmount) as RetailAmountSum
            FROM `sp_customer_stock_sale_year` T
            WHERE T.Date between '2022-01-01' and '2022-01-02' and T.WenDai = '中' and T.CategoryName1 = '下装' and T.CategoryName2 = '休闲短裤'
            GROUP BY
                T.CategoryName
                WITH ROLLUP
            ";

        $list = Db::connect("mysql2")->query($sql);

        $data = [
            'code'  => 0,
            'msg'   => '',
            'count' => 0,
            'data'  => $list,
        ];
        return json($data);
    }
    
    public function getTestField()
    {

        $data = [
            'code'  => 0,
            'msg'   => '',
            'count' => 10,
            'data'  => [
                [
                    'title' => '醉赤壁',
                    'dynasty' => '现代',
                    'author' => '林俊杰',
                    'content' => '流行音乐',
                    'type' => '歌曲',
                    'heat' => '666+',
                    'createTime' => '2008年',
                ],
                [
                    'title' => '罪己昭',
                    'dynasty' => '汉朝',
                    'author' => '汉武帝',
                    'content' => '内容1',
                    'type' => '诏书',
                    'heat' => '9999+',
                    'createTime' => '公元689',
                ]
            ],
        ];
        return json($data);
    }
}
