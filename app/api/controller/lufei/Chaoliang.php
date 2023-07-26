<?php
namespace app\api\controller\lufei;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * @ControllerAnnotation(title="单店单款超量提醒")
 */
class Chaoliang extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    public function seasionHandle($seasion = "春季") {
        $seasionStr = "";
        if ($seasion == '春季') {
            $seasionStr = "'初春','正春','春季'";
        } elseif ($seasion == '夏季') {
            $seasionStr = "'初夏','盛夏','夏季'";
        } elseif ($seasion == '秋季') {
            $seasionStr = "'初秋','深秋','秋季'";
        } elseif ($seasion == '冬季') {
            $seasionStr = "'初冬','深冬','冬季'";
        }
        return $seasionStr;
    }

    public function sk_first()
    {
        // $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        // $seasion = $this->seasionHandle($select_config['季节归集']); 
        // // 不考核门店
        // $noCustomer = xmSelectInput($select_config['不考核门店']);
        $sql = "
            SELECT
                t.*,
                round(t.`预计00/28/37/44/100/160/S` / t.`平均每日销量00/28/37/44/100/160/S`, 1) as `周转00/28/37/44/100/160/S`,
                round(t.`预计29/38/46/105/165/M` / t.`平均每日销量29/38/46/105/165/M`, 1) as `周转29/38/46/105/165/M`,
                round(t.`预计30/39/48/110/170/L` / t.`平均每日销量30/39/48/110/170/L`, 1) as `周转30/39/48/110/170/L`,
                round(t.`预计31/40/50/115/175/XL` / t.`平均每日销量31/40/50/115/175/XL`, 1) as `周转31/40/50/115/175/XL`,
                round(t.`预计32/41/52/120/180/2XL` / t.`平均每日销量32/41/52/120/180/2XL`, 1) as `周转32/41/52/120/180/2XL`,
                round(t.`预计33/42/54/125/185/3XL` / t.`平均每日销量33/42/54/125/185/3XL`, 1) as `周转33/42/54/125/185/3XL`,
                round(t.`预计34/43/56/190/4XL` / t.`平均每日销量34/43/56/190/4XL`, 1) as `周转34/43/56/190/4XL`,
                round(t.`预计35/44/58/195/5XL` / t.`平均每日销量35/44/58/195/5XL`, 1) as `周转35/44/58/195/5XL`,
                round(t.`预计36/6XL` / t.`平均每日销量36/6XL`, 1) as `周转36/6XL`,
                round(t.`预计38/7XL` / t.`平均每日销量38/7XL`, 1) as `周转38/7XL`,
                round(t.`预计_40` / t.`平均每日销量_40`, 1) as `周转_40`
            FROM
                (
                SELECT
                    sk.云仓,
                    sk.店铺名称,
                    c.CustomerGrade AS 店铺等级,
                    sk.商品负责人,
                    sk.省份,
                    sk.经营模式,
                    sk.年份,
                    sk.季节,
                    sk.一级分类,
                    sk.二级分类,
                    sk.分类,
                    sk.风格,
                    SUBSTRING( sk.分类, 1, 2 ) AS 领型,
                    sk.货号,
                    lz.上市天数,
                    sk.`预计00/28/37/44/100/160/S`,
                    sk.`预计29/38/46/105/165/M`,
                    sk.`预计30/39/48/110/170/L`,
                    sk.`预计31/40/50/115/175/XL`,
                    sk.`预计32/41/52/120/180/2XL`,
                    sk.`预计33/42/54/125/185/3XL`,
                    sk.`预计34/43/56/190/4XL`,
                    sk.`预计35/44/58/195/5XL`,
                    sk.`预计36/6XL`,
                    sk.`预计38/7XL`,
                    sk.`预计_40`,
                    sk.`预计库存数量` AS 预计库存合计,
                    lz.`00/28/37/44/100/160/S` AS `两周销00/28/37/44/100/160/S`,
                    lz.`29/38/46/105/165/M` AS `两周销29/38/46/105/165/M`,
                    lz.`30/39/48/110/170/L` AS `两周销30/39/48/110/170/L`,
                    lz.`31/40/50/115/175/XL` AS `两周销31/40/50/115/175/XL`,
                    lz.`32/41/52/120/180/2XL` AS `两周销32/41/52/120/180/2XL`,
                    lz.`33/42/54/125/185/3XL` AS `两周销33/42/54/125/185/3XL`,
                    lz.`34/43/56/190/4XL` AS `两周销34/43/56/190/4XL`,
                    lz.`35/44/58/195/5XL` AS `两周销35/44/58/195/5XL`,
                    lz.`36/6XL` AS `两周销36/6XL`,
                    lz.`38/7XL` AS `两周销38/7XL`,
                    lz.`_40` AS `两周销_40`,
                    lz.`合计` AS 两周销合计,
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`00/28/37/44/100/160/S` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`00/28/37/44/100/160/S` / lz.上市天数 * 7 
                    END AS `平均每日销量00/28/37/44/100/160/S`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`29/38/46/105/165/M` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`29/38/46/105/165/M` / lz.上市天数 * 7 
                    END AS `平均每日销量29/38/46/105/165/M`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`30/39/48/110/170/L` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`30/39/48/110/170/L` / lz.上市天数 * 7 
                    END AS `平均每日销量30/39/48/110/170/L`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`31/40/50/115/175/XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`31/40/50/115/175/XL` / lz.上市天数 * 7 
                    END AS `平均每日销量31/40/50/115/175/XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`32/41/52/120/180/2XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`32/41/52/120/180/2XL` / lz.上市天数 * 7 
                    END AS `平均每日销量32/41/52/120/180/2XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`33/42/54/125/185/3XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`33/42/54/125/185/3XL` / lz.上市天数 * 7 
                    END AS `平均每日销量33/42/54/125/185/3XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`34/43/56/190/4XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`34/43/56/190/4XL` / lz.上市天数 * 7 
                    END AS `平均每日销量34/43/56/190/4XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`35/44/58/195/5XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`35/44/58/195/5XL` / lz.上市天数 * 7 
                    END AS `平均每日销量35/44/58/195/5XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`36/6XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`36/6XL` / lz.上市天数 * 7 
                    END AS `平均每日销量36/6XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`38/7XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`38/7XL` / lz.上市天数 * 7 
                    END AS `平均每日销量38/7XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`_40` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`_40` / lz.上市天数 * 7 
                    END AS `平均每日销量_40`															
                FROM
                    `sp_sk` AS sk
                    RIGHT JOIN customer AS c ON sk.店铺名称 = c.CustomerName
                    LEFT JOIN sjp_liangzhou AS lz ON sk.云仓 = lz.云仓 
                    AND sk.店铺名称 = lz.店铺名称 
                    AND sk.货号 = lz.货号 
                WHERE
                    sk.季节 IN ( '初夏', '盛夏', '夏季' ) 
                    AND c.Region <> '闭店区' 
                    AND sk.店铺名称 NOT IN ( '' ) --    AND sk.店铺名称 IN ('三江一店', '安化二店', '南宁二店')
                    
                    AND sk.年份 = 2023 
                    AND sk.一级分类 IN ('内搭', '外套', '下装', '鞋履')
                GROUP BY
                    sk.店铺名称,
                    sk.季节,
                    sk.货号 
             	LIMIT 1000 
                ) AS t
        ";
		
        $select_sk = $this->db_easyA->query($sql);
        $count = count($select_sk);

        if ($select_sk) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_chaoliang_sk;');
            $chunk_list = array_chunk($select_sk, 500);
            // $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_chaoliang_sk')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            // 更新零售当前零售折率
            $sql = "
                UPDATE cwl_chaoliang_sk AS sk
                LEFT JOIN sp_ww_chunxia_stock AS st ON sk.省份 = st.省份 
                    AND sk.店铺名称 = st.店铺名称 
                    AND sk.一级分类 = st.一级分类 
                    AND sk.二级分类 = st.二级分类 
                    AND sk.分类 = st.分类 
                    AND sk.货号 = st.货号
                SET sk.当前零售价 = st.当前零售价,
                        sk.零售价 = st.零售价,
                        sk.折率 = round(st.当前零售价 / st.零售价, 2)
                WHERE 1        
            ";

            // 周转合计
            $sql0 = "
                UPDATE 
                    cwl_chaoliang_sk
                SET 
                    周转合计 = ROUND( 预计库存合计 / (两周销合计 / 2), 0)
                WHERE
                    周转合计 IS NULL
            ";

            // $sql0 = "
            //     UPDATE 
            //         cwl_chaoliang_sk
            //     SET 
            //         周转合计 = 
            //             预计库存合计 / (两周销合计 / 2)
            //             IFNULL(`周转00/28/37/44/100/160/S`, 0) + 
            //             IFNULL(`周转29/38/46/105/165/M`, 0) + 
            //             IFNULL(`周转30/39/48/110/170/L`, 0) + 
            //             IFNULL(`周转31/40/50/115/175/XL`, 0) + 
            //             IFNULL(`周转32/41/52/120/180/2XL`, 0) +
            //             IFNULL(`周转33/42/54/125/185/3XL`, 0) + 
            //             IFNULL(`周转34/43/56/190/4XL`, 0) + 
            //             IFNULL(`周转35/44/58/195/5XL`, 0) + 
            //             IFNULL(`周转36/6XL`, 0) + 
            //             IFNULL(`周转38/7XL`, 0) + 
            //             IFNULL(`周转_40`, 0)
            //     WHERE
            //         周转合计 IS NULL
            // ";

            // 折率 当前零售 零售
            $status2 = $this->db_easyA->execute($sql);  
            // 周转累计
            $status3 = $this->db_easyA->execute($sql0);  

            if ($status || $status2 || $status3) {
                // $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_chaoliang_sk first 更新成功，数量：{$count}！"
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_chaoliang_sk first 更新失败！'
                ]);
            }
        }
    }

    // 修正风格
    public function handle_1() {
        $find_config = $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->find();
        $sql1 = "
            UPDATE 
                cwl_chaoliang_sk 
            SET 
                风格修订 = '引流款'
            WHERE 1
                AND 风格 = '基本款'
                AND (
                    折率 < {$find_config['折率']}
                    AND (
                        (二级分类 = '短T' AND 当前零售价 <= {$find_config['短T']}) OR
                        (二级分类 = '休闲短衬' AND 当前零售价 <= {$find_config['休闲短衬']}) OR
                        (二级分类 = '休闲短裤' AND 当前零售价 <= {$find_config['休闲短裤']}) OR
                        (二级分类 = '松紧短裤' AND 当前零售价 <= {$find_config['松紧短裤']}) OR
                        (二级分类 = '牛仔短裤' AND 当前零售价 <= {$find_config['牛仔短裤']}) OR
                        (二级分类 = '休闲长裤' AND 当前零售价 <= {$find_config['休闲长裤']}) OR
                        (二级分类 = '牛仔长裤' AND 当前零售价 <= {$find_config['牛仔长裤']}) OR
                        (二级分类 = '松紧长裤' AND 当前零售价 <= {$find_config['松紧长裤']})
                    )
                ) OR (
                    折率 < {$find_config['折率']} 
                    AND 二级分类 NOT IN('短T', '休闲短衬', '休闲短裤', '松紧短裤', '牛仔短裤', '休闲长裤', '牛仔长裤', '松紧长裤')
                )
        ";
        $this->db_easyA->execute($sql1);

        $sql2 = "
            UPDATE 
                cwl_chaoliang_sk 
            SET 
                风格修订 = `风格`
            WHERE 
                风格修订 IS NULL    
        "; 
        $this->db_easyA->execute($sql2);  
        
        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "cwl_chaoliang_sk 风格修订 更新成功！"
        ]);
    }

    // 更新超量提醒1 
    public function handle_2() {
        // 更新超量提醒1  内搭 外套 鞋履
        $sql1 = "
            UPDATE cwl_chaoliang_sk as sk 
                LEFT JOIN cwl_chaoliang_biaozhun as bz ON sk.风格修订 = bz.风格 AND sk.店铺等级 = bz.店铺等级 AND sk.一级分类 = bz.一级分类 AND bz.二级分类 IS NULL
                SET
                    `提醒00/28/37/44/100/160/S` =  
                    CASE
                        WHEN (sk.`预计00/28/37/44/100/160/S` > bz.`单码量00/28/37/44/100/160/S`) AND (sk.`周转00/28/37/44/100/160/S` > bz.`周转00/28/37/44/100/160/S` || sk.`周转00/28/37/44/100/160/S` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒29/38/46/105/165/M` =  
                    CASE
                        WHEN (sk.`预计29/38/46/105/165/M` > bz.`单码量29/38/46/105/165/M`) AND (sk.`周转29/38/46/105/165/M` > bz.`周转29/38/46/105/165/M` || sk.`周转29/38/46/105/165/M` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒30/39/48/110/170/L` =  
                    CASE
                        WHEN (sk.`预计30/39/48/110/170/L` > bz.`单码量30/39/48/110/170/L`) AND (sk.`周转30/39/48/110/170/L` > bz.`周转30/39/48/110/170/L` || sk.`周转30/39/48/110/170/L` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒31/40/50/115/175/XL` =  
                    CASE
                        WHEN (sk.`预计31/40/50/115/175/XL` > bz.`单码量31/40/50/115/175/XL`) AND (sk.`周转31/40/50/115/175/XL` > bz.`周转31/40/50/115/175/XL` || sk.`周转31/40/50/115/175/XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒32/41/52/120/180/2XL` =  
                    CASE
                        WHEN (sk.`预计32/41/52/120/180/2XL` > bz.`单码量32/41/52/120/180/2XL`) AND (sk.`周转32/41/52/120/180/2XL` > bz.`周转32/41/52/120/180/2XL` || sk.`周转32/41/52/120/180/2XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒33/42/54/125/185/3XL` =  
                    CASE
                        WHEN (sk.`预计33/42/54/125/185/3XL` > bz.`单码量33/42/54/125/185/3XL`) AND (sk.`周转33/42/54/125/185/3XL` > bz.`周转33/42/54/125/185/3XL` || sk.`周转33/42/54/125/185/3XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒34/43/56/190/4XL` =  
                    CASE
                        WHEN (sk.`预计34/43/56/190/4XL` > bz.`单码量34/43/56/190/4XL`) AND (sk.`周转34/43/56/190/4XL` > bz.`周转34/43/56/190/4XL` || sk.`周转34/43/56/190/4XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒35/44/58/195/5XL` =  
                    CASE
                        WHEN (sk.`预计35/44/58/195/5XL` > bz.`单码量35/44/58/195/5XL`) AND (sk.`周转35/44/58/195/5XL` > bz.`周转35/44/58/195/5XL` || sk.`周转35/44/58/195/5XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒36/6XL` =  
                    CASE
                        WHEN (sk.`预计36/6XL` > bz.`单码量36/6XL`) AND (sk.`周转36/6XL` > bz.`周转36/6XL` || sk.`周转36/6XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒38/7XL` =  
                    CASE
                        WHEN (sk.`预计38/7XL` > bz.`单码量38/7XL`) AND (sk.`周转38/7XL` > bz.`周转38/7XL` || sk.`周转38/7XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒_40` =  
                    CASE
                        WHEN (sk.`预计_40` > bz.`单码量_40`) AND (sk.`周转_40` > bz.`周转_40` || sk.`周转_40` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END
                WHERE 1
            AND sk.一级分类 IN ('内搭','外套','鞋履')
        ";

        // 一级下装 二级不包括松紧长短
        $sql2 = "
            UPDATE cwl_chaoliang_sk as sk 
                LEFT JOIN cwl_chaoliang_biaozhun as bz ON	sk.风格 = bz.风格 AND sk.店铺等级 = bz.店铺等级 AND sk.一级分类 = bz.一级分类 AND bz.二级分类 IS NULL
                SET
                    `提醒00/28/37/44/100/160/S` =  
                    CASE
                        WHEN (sk.`预计00/28/37/44/100/160/S` > bz.`单码量00/28/37/44/100/160/S`) AND (sk.`周转00/28/37/44/100/160/S` > bz.`周转00/28/37/44/100/160/S` || sk.`周转00/28/37/44/100/160/S` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒29/38/46/105/165/M` =  
                    CASE
                        WHEN (sk.`预计29/38/46/105/165/M` > bz.`单码量29/38/46/105/165/M`) AND (sk.`周转29/38/46/105/165/M` > bz.`周转29/38/46/105/165/M` || sk.`周转29/38/46/105/165/M` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒30/39/48/110/170/L` =  
                    CASE
                        WHEN (sk.`预计30/39/48/110/170/L` > bz.`单码量30/39/48/110/170/L`) AND (sk.`周转30/39/48/110/170/L` > bz.`周转30/39/48/110/170/L` || sk.`周转30/39/48/110/170/L` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒31/40/50/115/175/XL` =  
                    CASE
                        WHEN (sk.`预计31/40/50/115/175/XL` > bz.`单码量31/40/50/115/175/XL`) AND (sk.`周转31/40/50/115/175/XL` > bz.`周转31/40/50/115/175/XL` || sk.`周转31/40/50/115/175/XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒32/41/52/120/180/2XL` =  
                    CASE
                        WHEN (sk.`预计32/41/52/120/180/2XL` > bz.`单码量32/41/52/120/180/2XL`) AND (sk.`周转32/41/52/120/180/2XL` > bz.`周转32/41/52/120/180/2XL` || sk.`周转32/41/52/120/180/2XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒33/42/54/125/185/3XL` =  
                    CASE
                        WHEN (sk.`预计33/42/54/125/185/3XL` > bz.`单码量33/42/54/125/185/3XL`) AND (sk.`周转33/42/54/125/185/3XL` > bz.`周转33/42/54/125/185/3XL` || sk.`周转33/42/54/125/185/3XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒34/43/56/190/4XL` =  
                    CASE
                        WHEN (sk.`预计34/43/56/190/4XL` > bz.`单码量34/43/56/190/4XL`) AND (sk.`周转34/43/56/190/4XL` > bz.`周转34/43/56/190/4XL` || sk.`周转34/43/56/190/4XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒35/44/58/195/5XL` =  
                    CASE
                        WHEN (sk.`预计35/44/58/195/5XL` > bz.`单码量35/44/58/195/5XL`) AND (sk.`周转35/44/58/195/5XL` > bz.`周转35/44/58/195/5XL` || sk.`周转35/44/58/195/5XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒36/6XL` =  
                    CASE
                        WHEN (sk.`预计36/6XL` > bz.`单码量36/6XL`) AND (sk.`周转36/6XL` > bz.`周转36/6XL` || sk.`周转36/6XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒38/7XL` =  
                    CASE
                        WHEN (sk.`预计38/7XL` > bz.`单码量38/7XL`) AND (sk.`周转38/7XL` > bz.`周转38/7XL` || sk.`周转38/7XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒_40` =  
                    CASE
                        WHEN (sk.`预计_40` > bz.`单码量_40`) AND (sk.`周转_40` > bz.`周转_40` || sk.`周转_40` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END
                WHERE 1
                    AND sk.一级分类 IN ('下装')
                    AND sk.二级分类 NOT IN ('松紧长裤', '松紧短裤')
        ";

        // 一级下装二级松紧长短
        $sql3 = "
            UPDATE cwl_chaoliang_sk as sk 
                LEFT JOIN cwl_chaoliang_biaozhun as bz ON	sk.风格 = bz.风格 AND sk.店铺等级 = bz.店铺等级 AND sk.一级分类 = bz.一级分类 AND bz.二级分类 = sk.二级分类
                SET
                    `提醒00/28/37/44/100/160/S` =  
                    CASE
                        WHEN (sk.`预计00/28/37/44/100/160/S` > bz.`单码量00/28/37/44/100/160/S`) AND (sk.`周转00/28/37/44/100/160/S` > bz.`周转00/28/37/44/100/160/S` || sk.`周转00/28/37/44/100/160/S` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒29/38/46/105/165/M` =  
                    CASE
                        WHEN (sk.`预计29/38/46/105/165/M` > bz.`单码量29/38/46/105/165/M`) AND (sk.`周转29/38/46/105/165/M` > bz.`周转29/38/46/105/165/M` || sk.`周转29/38/46/105/165/M` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒30/39/48/110/170/L` =  
                    CASE
                        WHEN (sk.`预计30/39/48/110/170/L` > bz.`单码量30/39/48/110/170/L`) AND (sk.`周转30/39/48/110/170/L` > bz.`周转30/39/48/110/170/L` || sk.`周转30/39/48/110/170/L` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒31/40/50/115/175/XL` =  
                    CASE
                        WHEN (sk.`预计31/40/50/115/175/XL` > bz.`单码量31/40/50/115/175/XL`) AND (sk.`周转31/40/50/115/175/XL` > bz.`周转31/40/50/115/175/XL` || sk.`周转31/40/50/115/175/XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒32/41/52/120/180/2XL` =  
                    CASE
                        WHEN (sk.`预计32/41/52/120/180/2XL` > bz.`单码量32/41/52/120/180/2XL`) AND (sk.`周转32/41/52/120/180/2XL` > bz.`周转32/41/52/120/180/2XL` || sk.`周转32/41/52/120/180/2XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒33/42/54/125/185/3XL` =  
                    CASE
                        WHEN (sk.`预计33/42/54/125/185/3XL` > bz.`单码量33/42/54/125/185/3XL`) AND (sk.`周转33/42/54/125/185/3XL` > bz.`周转33/42/54/125/185/3XL` || sk.`周转33/42/54/125/185/3XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒34/43/56/190/4XL` =  
                    CASE
                        WHEN (sk.`预计34/43/56/190/4XL` > bz.`单码量34/43/56/190/4XL`) AND (sk.`周转34/43/56/190/4XL` > bz.`周转34/43/56/190/4XL` || sk.`周转34/43/56/190/4XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒35/44/58/195/5XL` =  
                    CASE
                        WHEN (sk.`预计35/44/58/195/5XL` > bz.`单码量35/44/58/195/5XL`) AND (sk.`周转35/44/58/195/5XL` > bz.`周转35/44/58/195/5XL` || sk.`周转35/44/58/195/5XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒36/6XL` =  
                    CASE
                        WHEN (sk.`预计36/6XL` > bz.`单码量36/6XL`) AND (sk.`周转36/6XL` > bz.`周转36/6XL` || sk.`周转36/6XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒38/7XL` =  
                    CASE
                        WHEN (sk.`预计38/7XL` > bz.`单码量38/7XL`) AND (sk.`周转38/7XL` > bz.`周转38/7XL` || sk.`周转38/7XL` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END,
                    `提醒_40` =  
                    CASE
                        WHEN (sk.`预计_40` > bz.`单码量_40`) AND (sk.`周转_40` > bz.`周转_40` || sk.`周转_40` IS NULL)
                        THEN '超'
                        ELSE NULL
                    END
                WHERE 1
                    AND sk.一级分类 IN ('下装')
                    AND sk.二级分类 IN ('松紧长裤', '松紧短裤')
        ";

        // 超量提醒 
        $sql4 = "
            UPDATE 
                cwl_chaoliang_sk 
            SET
                提醒备注 = '请注意'
            WHERE 1
                AND 一级分类 IN ('内搭','外套','下装', '鞋履')
                AND (`提醒00/28/37/44/100/160/S` = '超' OR `提醒29/38/46/105/165/M` = '超' OR `提醒30/39/48/110/170/L` = '超' OR `提醒31/40/50/115/175/XL` = '超' OR `提醒32/41/52/120/180/2XL` = '超'
                OR `提醒33/42/54/125/185/3XL` = '超' OR `提醒34/43/56/190/4XL` = '超' OR `提醒35/44/58/195/5XL` = '超' OR `提醒36/6XL` = '超' OR `提醒38/7XL` = '超' OR `提醒_40`)         
        ";

        // 超量个数
        $sql5 = "
            UPDATE cwl_chaoliang_sk
            SET 超量个数 = CHAR_LENGTH(
                CONCAT(
                        ifnull( `提醒00/28/37/44/100/160/S`, '' ),
                        ifnull( `提醒29/38/46/105/165/M`, '' ),
                        ifnull( `提醒30/39/48/110/170/L`, '' ),
                        ifnull( `提醒31/40/50/115/175/XL`, '' ),
                        ifnull( `提醒32/41/52/120/180/2XL`, '' ),
                        ifnull( `提醒33/42/54/125/185/3XL`, '' ),
                        ifnull( `提醒34/43/56/190/4XL`, '' ),
                        ifnull( `提醒35/44/58/195/5XL`, '' ),
                        ifnull( `提醒36/6XL`, '' ),
                        ifnull( `提醒38/7XL`, '' ),
                        ifnull( `提醒_40`, '' )) 
            ) 
            WHERE 超量个数 is null        
        ";
        
        // 1 内搭 外套 鞋履
        $status1 = $this->db_easyA->execute($sql1);
        // 2 下装
        $status2 = $this->db_easyA->execute($sql2);
        // 3下装 松紧长短
        $status3 = $this->db_easyA->execute($sql3);
        // 4 超量提醒
        $status4 = $this->db_easyA->execute($sql4);
        // 5 超量提醒
        $status5 = $this->db_easyA->execute($sql5);
        $total = $status1 + $status2 + $status3;
        if (($status1 || $status2 || $status3) && $status4 && $status5) {
            // $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_chaoliang_sk 超量更新1 更新成功，数量：{$status4}！"
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_chaoliang_sk 超量更新1  更新失败！'
            ]);
        }
    }

}
