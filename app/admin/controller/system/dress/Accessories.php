<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories as AccessoriesM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DVar;
use think\App;
use think\facade\Db;
use function GuzzleHttp\Psr7\str;
use think\cache\driver\Redis;


/**
 * Class Accessories
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰库存")
 */
class Accessories extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new AccessoriesM();
    }

    /**
     * @NodeAnotation(title="配饰总览")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 获取其他筛选
            $other_where = $this->setWhere($where)[1];

            // 设置默认筛选结果
            $where = $this->setWhere($where)[0];

            $count = $this->model
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })->whereNotIn('店铺名称&省份&商品负责人','合计');

            // 增加其他筛选
            if(count($other_where) > 0){
                $count->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }

            $count = $count->count();

            if(empty($where)){
                $stock_warn = sysconfig('stock_warn');;
            }else{
                $stock_warn = [];
                foreach ($where as $k => $v){
                    $stock_warn[$v[0]] = $v[2]??0;
                }
            }

            $list = $this->model
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })->whereNotIn(AdminConstant::NOT_FIELD,'合计');

            // 增加其他筛选
            if(count($other_where) > 0){
                $list->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }

            $list = $list->order('省份,店铺名称,商品负责人')->page($page, $limit)
                ->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
                    return $stock_warn;
                });
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
                'config' => $stock_warn
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="配饰结果")
     */
    public function list()
    {
        // 条件
        $this->searchWhere();
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 获取其他筛选
            $other_where = $this->setWhere($where)[1];

             // 设置默认筛选结果
            $where = $this->setWhere($where)[0];
            if(empty($where)){
                // 设置默认筛选
                $where = $this->setWhere($where)[0];
            }

            // 计算条数
            $count = $this->model
                ->whereNotIn(AdminConstant::NOT_FIELD,'合计')
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                });

            // 增加其他筛选
            if(count($other_where) > 0){
                $count->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }
            // 计数
            $count = $count->count();

            // 配置
            $stock_warn = sysconfig('stock_warn');

            // 获取列表
            $list = $this->model->whereNotIn('店铺名称&省份&商品负责人','合计')
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                });

            // 增加其他筛选
            if(count($other_where) > 0){
                $list->where(function ($q)use($other_where){
                    foreach ($other_where as $k => $v){
                        $q->where($v[0], $v[1], $v[2]);
                    }
                });
            }

            $list = $list->order('省份,店铺名称,商品负责人')->page($page, $limit)->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
                    return $stock_warn;
                });

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list
            ];
            return json($data);
        }
        return $this->fetch();
    }


    /**
     * 设置默认筛选结果
     */
    public function setWhere($where)
    {
        $stock_warn = sysconfig('stock_warn');
        $other_where = [];
        $_where = [];
        if(empty($where)){
            foreach ($stock_warn as $k => $v){
                $_where[] = [$k,'<',$v];
            }
        }else{
            foreach ($where as $k => $v){
                if($v[1] == '<'){
                    $_where[] = [$v[0],$v[1],$v[2]];
                }else{
                    $other_where[] = [$v[0],$v[1],$v[2]];
                }
            }
        }
        return [$_where,$other_where];
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
            $this->validate($post, $rule);
            if (isset($row['password'])) {
                unset($row['password']);
            }
            try {
                $save = $row->save($post);
                TriggerService::updateMenu($id);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        $id == AdminConstant::SUPER_ADMIN_ID && $this->error('超级管理员不允许修改');
        if (is_array($id)){
            if (in_array(AdminConstant::SUPER_ADMIN_ID, $id)){
                $this->error('超级管理员不允许修改');
            }
        }
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        if ($post['id'] == AdminConstant::SUPER_ADMIN_ID && $post['field'] == 'status') {
            $this->error('超级管理员状态不允许修改');
        }
        $row = $this->model->find($post['id']);
        empty($row) && $this->error('数据不存在');
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * 初始化搜索条件
     */
    public function searchWhere()
    {
        $search_where = [];
        $fields = [
             // 设置省份列表
            'province_list' => '省份',
            // 设置省份列表
            'shop_list' => '店铺名称',
            // 设置省份列表
            'charge_list' => '商品负责人'
        ];
        foreach ($fields as $k => $v){
            $list = $this->model->column($v);
            $search_where[$k] = [
                'field' => $v,
                'data' => $list
            ];
        }
        // 设置搜索
        $this->setSearchWhere($search_where);
    }

    // 设置任务队列
    public function setRedisQueue()
    {

    }

    public function sqlsrvTest(){

        $day = (new Redis())->lindex('task_queue',0);
        // 初始时间
        $datetime = '2022-01-03';
        // 插入结果
        $result = [];


        $model = (new \app\http\logic\AddHistoryData);
        echo '<pre>';
//        print_r(date('Y-m-d',strtotime('2022-01-01'."+313day")));
//        $model->redis->rpush('finish_task',313);
//        $model->redis->lpop('task_queue');
        print_r($model->showTaskInfo());
        die;
        echo '<pre>';
        die;
        // 启动事务
        Db::startTrans();
    
        
//        // 计算一年有多少天
//        $days = $this->daysbetweendates('2022-01-01','2022-12-31');
//
//        echo '<pre>';
//        print_r(date("z",strtotime('2022-12-31'))+1);
//        die;

        $redis = new Redis;


        for ($day = 0;$day<=10;$day++){
            try {

                $datetime = date('Y-m-d',strtotime($datetime.'+1day'));
                $sql = $this->setSql("'$datetime'");
                // 查询数据
                $data = Db::connect("sqlsrv")->Query($sql);
                // 实例化
                $table = Db::connect("mysql2")->table('sp_customer_stock_sale_year');
                $res = $table->insertAll($data);
                $result[] = [count($data),$res];

            }catch (\Exception $e){

                // 回滚事务
                Db::rollback();
                $this->setLog($e->getMessage());
                echo '<pre>';
                print_r($e->getMessage());
                die;
            }
        }

        // 提交事务
        Db::commit();
        echo '<pre>';
        print_r($result);
        die;
    }

    public function daysbetweendates($date1, $date2){
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        $days = ceil(($date2 - $date1)/86400);
        return $days;
    }

    // 写入数据
    public function setData($fielname,$data)
    {
        file_put_contents("data/{$fielname}.txt",$data);
    }

    // 写入日志
    public function setLog($msg)
    {
        file_put_contents('error/log.txt',var_dump($msg . date('Y-m-d H:i:s')),FILE_APPEND | LOCK_EX);
    }
    
    
    public function setSql($datetime)
    {
        $sql = "SELECT 
	{$datetime} AS Date,
	EC.State AS State,
	EC.CustomItem36 AS WenQu,
	EG.TimeCategoryName1 AS TimeCategoryName1,
	CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
			 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
			 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
			 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
			 ELSE EG.TimeCategoryName2 
  END AS Season,
	EG.TimeCategoryName2 AS TimeCategoryName2,
	EG.CategoryName1 AS CategoryName1,
	EG.CategoryName2 AS CategoryName2,
	EG.CategoryName AS CategoryName,
	EG.StyleCategoryName AS StyleCategoryName,
	EG.StyleCategoryName1 AS StyleCategoryName1,
	SUM(ECSD.Quantity) AS StockQuantity,
	SUM(CASE WHEN EBGS.ViewOrder=1 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity00/28/37/44/100/160/S],
	SUM(CASE WHEN EBGS.ViewOrder=2 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity29/38/46/105/165/M],
	SUM(CASE WHEN EBGS.ViewOrder=3 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity30/39/48/110/170/L],
	SUM(CASE WHEN EBGS.ViewOrder=4 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity31/40/50/115/175/XL],
	SUM(CASE WHEN EBGS.ViewOrder=5 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity32/41/52/120/180/2XL],
	SUM(CASE WHEN EBGS.ViewOrder=6 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity33/42/54/125/185/3XL],
	SUM(CASE WHEN EBGS.ViewOrder=7 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity34/43/56/190/4XL],
	SUM(CASE WHEN EBGS.ViewOrder=8 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity35/44/58/195/5XL],
	SUM(CASE WHEN EBGS.ViewOrder=9 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity36/6XL],
	SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity38/7XL],
	SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity40/8XL],
	SUM(ECSD.Quantity*EGPT.[成本价]) AS StockCost,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=1	 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales00/28/37/44/100/160/S],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=2  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales29/38/46/105/165/M],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=3  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales30/39/48/110/170/L],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=4  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales31/40/50/115/175/XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=5  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales32/41/52/120/180/2XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=6  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales33/42/54/125/185/3XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=7  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales34/43/56/190/4XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=8  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales35/44/58/195/5XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=9  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales36/6XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales38/7XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales40/8XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
FROM ErpCustomer EC 
LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
LEFT JOIN ErpGoods EG ON ECS.GoodsId = EG.GoodsId
LEFT JOIN (SELECT 
							EGPT.GoodsId, 
							SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS 零售价,
							SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS 成本价
						FROM ErpGoodsPriceType EGPT
						GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId
LEFT JOIN (SELECT ERG.RetailID,ERG.GoodsId,ERG.DiscountPrice FROM ErpRetail ER LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)={$datetime}) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId
WHERE EC.MathodId IN (4,7)
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= {$datetime}
GROUP BY 
	EC.State,
	EC.CustomItem36,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1
ORDER BY 
	EC.State,
	EG.TimeCategoryName2,
	EG.StyleCategoryName,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName";
        return $sql;
    }

}
