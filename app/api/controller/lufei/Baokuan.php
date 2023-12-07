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
 * @ControllerAnnotation(title="爆款")
 */
class Baokuan extends BaseController
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

    // 数据源 周销
    public function first() {
        // 外套、下装两个大类按省份+温区为单位取各自近一周销售前6名推送
        $sql_周销 = "
                SELECT TOP
                1000000
                EC.State as 省份,
                EC.CustomItem36 as 温区,
                EG.GoodsNo  AS 货号,
                EG.GoodsName AS 货品名称,
                EG.TimeCategoryName1 AS 年份,
                CASE
                EG.TimeCategoryName2 
                WHEN '初春' THEN
                '春季' 
                WHEN '正春' THEN
                '春季' 
                WHEN '春季' THEN
                '春季' 
                WHEN '初秋' THEN
                '秋季' 
                WHEN '深秋' THEN
                '秋季' 
                WHEN '秋季' THEN
                '秋季' 
                WHEN '初夏' THEN
                '夏季' 
                WHEN '盛夏' THEN
                '夏季' 
                WHEN '夏季' THEN
                '夏季' 
                WHEN '冬季' THEN
                '冬季' 
                WHEN '初冬' THEN
                '冬季' 
                WHEN '深冬' THEN
                '冬季' 
            END AS 季节归集,
            EG.CategoryName1 AS 大类,
            EG.CategoryName2 AS 中类,
            EG.CategoryName AS 分类,
            EG.StyleCategoryName AS 风格,
            SUM(ERG.Quantity) AS 销量,
            SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
            concat(DATEADD(DAY, -7, CAST(GETDATE() AS DATE)), ' 至 ' ,DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) AS 销售日期,				
            CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EG.GoodsNo NOT IN ('B72212008', 'B72212007','B72212004','B72212006','B72212005','B72212003')
            --  AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
            --  AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
                AND EG.CategoryName1 IN ( '外套', '下装' ) 
                AND EG.StyleCategoryName in ( '基本款', '引流款')               
            GROUP BY
                EG.GoodsNo
                ,EC.State
                ,EG.GoodsName
                ,EC.CustomItem36
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.StyleCategoryName
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING SUM ( ERG.Quantity ) <> 0
            ORDER BY 
                EC.State,EC.CustomItem36,EG.CategoryName1
        ";

        $select_周销 = $this->db_sqlsrv->query($sql_周销);
        if ($select_周销) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();1306 6166636
            $this->db_easyA->execute('TRUNCATE cwl_baokuan_7day;');
            $chunk_list = array_chunk($select_周销, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_baokuan_7day')->strict(false)->insertAll($val);
            }
        }

        $sql_零售单价 = "
            update cwl_baokuan_7day as m
            left join sp_ww_hpzl as h on m.大类 = h.一级分类 and m.中类=h.二级分类 and m.分类=h.分类 and m.货号=h.货号
            set
                m.零售单价 = h.零售价
            where
                m.零售单价 is null
        ";
        $this->db_easyA->execute($sql_零售单价);

        $sql_折率 = "
            update cwl_baokuan_7day
            set
                折率 = 销售金额 / (零售单价*销量)
            where
                折率 is null
        ";
        $this->db_easyA->execute($sql_折率);
        
        $sql_删除折率低的 = "
            delete from cwl_baokuan_7day where 折率 <0.9
        ";
        $this->db_easyA->execute($sql_删除折率低的);


    }

    // 数据源 店铺库存
    public function second() {

        $sql_省份温区店铺库存 = "
            SELECT
            -- 		TOP 1000000
                    EC.State AS 省份,
                    EC.CustomItem36 as 温区,
                    EC.CustomerName As 店铺名称,
                    EG.CategoryName1 AS 一级分类,
                    EG.CategoryName2 AS 二级分类,
                    EG.CategoryName AS 分类,
                    EG.GoodsNo AS 货号,
                    SUM(ECSD.Quantity) AS 店铺库存,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM ErpCustomerStock ECS 
            left join ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE  EC.ShutOut=0
                AND EG.TimeCategoryName1 in (2023,2024)
                AND EC.MathodId IN (4,7)
                AND EG.CategoryName1 IN ( '外套', '下装' ) 
            GROUP BY 
                    EC.State,
                    EC.CustomItem36,
                    EC.CustomItem17,
                    EC.CustomerName,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsNo  
            HAVING SUM(ECSD.Quantity) > 0
        ";
        $select_省份温区店铺库存 = $this->db_sqlsrv->query($sql_省份温区店铺库存);
        if ($select_省份温区店铺库存) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_baokuan_stock;');
            $chunk_list = array_chunk($select_省份温区店铺库存, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_baokuan_stock')->strict(false)->insertAll($val);
            }
        }

        $sql_温区店铺数 = "
            update cwl_baokuan_stock as m
            left join (SELECT State, CustomItem36,count(*) as 店铺数 FROM `customer_pro` group by State, CustomItem36) as t on m.省份 = t.State and m.温区 = t.CustomItem36
            set
                m.温区店铺数 = t.店铺数
            where
                m.温区店铺数 is null
        ";
        $this->db_easyA->execute($sql_温区店铺数);

        $sql_有库存店铺数 = "
            update cwl_baokuan_stock as m
            left join (select 省份,温区,货号,count(店铺名称) AS 店铺数 from cwl_baokuan_stock group by 省份,温区,货号) as t on m.省份 = t.省份 and m.温区 = t.温区 and m.货号 = t.货号
            set
                m.有库存店铺数 = t.店铺数
            where
                m.有库存店铺数 is null
        ";
        $this->db_easyA->execute($sql_有库存店铺数);

        $sql_计算 = "
            update cwl_baokuan_stock a
            set
                计算 = 有库存店铺数 / 温区店铺数
            where
                计算 is null
        ";
        $this->db_easyA->execute($sql_计算);

        // 状况3剔除
        $sql_use1 = "
            update cwl_baokuan_7day as m
            left join (select 省份,温区,货号 from cwl_baokuan_stock where 计算>=0.85 group by 省份,温区,货号) as t on m.省份 = t.省份 and m.温区 = t.温区 and m.货号=t.货号
            set
                m.use1 = '是'
            where
                m.省份 = t.省份 and m.温区 = t.温区 and m.货号=t.货号
        ";
        $this->db_easyA->execute($sql_use1);
    }

    // 采购收货
    public function third() {
        // 1月1至 昨天的
        $sql_采购收货pro = "
            --仓库收采购单
            SELECT 
                t.GoodsNo as 货号,
                SUM(t.采购收货量) AS 采购收货量,
                SUM(t.采购退货量) AS 采购退货量,
                SUM(t.采购入库量) AS 采购入库量,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM 
            (
                SELECT
                    EG.GoodsNo,
                    SUM(ERG.Quantity) AS 采购收货量,
                    0 AS 采购退货量,
                    SUM(ERG.Quantity) AS 采购入库量
                FROM ErpReceipt ER 
                LEFT JOIN ErpWarehouse AS EW ON ER.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpReceiptGoods ERG ON ER.ReceiptId=ERG.ReceiptId
                LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
                WHERE EG.TimeCategoryName1 in (2023, 2024)
                    AND ER.ReceiptDate >= '2023-01-01'
                    -- AND ER.ReceiptDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) )
    --                         AND EG.TimeCategoryName2 IN ('初冬','深冬','冬季')
                    AND EG.CategoryName1 IN ('外套','下装')
                    AND ER.Type=1 
                    AND ER.CodingCodeText='已审结'
                    AND EG.StyleCategoryName in ('基本款', '引流款')
                    AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓','过账虚拟仓', '常熟正品仓库') 
                    AND ER.SupplyId !='K191000638'
                GROUP BY EG.GoodsNo

                UNION ALL
                --采购退货

                SELECT 
                    EG.GoodsNo,
                    0 AS 采购收货量,
                    SUM(EPRG.Quantity) AS 采购退货量,
                    -SUM(EPRG.Quantity)  AS 采购入库量
                FROM ErpPurchaseReturn EPR 
                LEFT JOIN ErpWarehouse AS EW ON EPR.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpPurchaseReturnGoods EPRG ON EPR.PurchaseReturnId=EPRG.PurchaseReturnId
                LEFT JOIN ErpGoods EG ON EPRG.GoodsId=EG.GoodsId
                WHERE EG.TimeCategoryName1 in (2023, 2024)
                    AND EPR.PurchaseReturnDate >= '2023-01-01'
                    -- AND EPR.PurchaseReturnDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) )
    --                         AND EG.TimeCategoryName2 IN ('初冬','深冬','冬季')
                    AND EG.CategoryName1 IN ('外套','下装')
                    AND EPR.CodingCodeText='已审结'
                    AND EG.StyleCategoryName in ('基本款', '引流款')
                    AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓','过账虚拟仓', '常熟正品仓库') 
                    AND EPR.SupplyId !='K191000638'
                GROUP BY EG.GoodsNo
            ) as t
            GROUP BY t.GoodsNo
        ";
        $select_采购收货pro = $this->db_sqlsrv->query($sql_采购收货pro);
        if ($select_采购收货pro) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_baokuan_caigou;');
            $chunk_list5 = array_chunk($select_采购收货pro, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list5 as $key5 => $val5) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_baokuan_caigou')->strict(false)->insertAll($val5);
            }
        }

        $sql_采购入库量 = "
            update cwl_baokuan_7day as m
            left join (select 货号 from cwl_baokuan_caigou where 采购入库量 >= 4500) as t on m.货号=t.货号
            set
                m.use2 = '是'
            where
                m.货号=t.货号
        ";
        $this->db_easyA->execute($sql_采购入库量);

    }

    // 分组排名
    public function fourth()
    {
        $sql_分组排名 = "
            update cwl_baokuan_7day as m1
            left join (
                SELECT
                    a.货号,
                    a.销量,
                    CASE
                        WHEN 
                                a.省份 = @省份 and 
                                a.温区 = @温区 and 
                                a.中类 = @中类
                        THEN
                                @rank := @rank + 1 ELSE @rank := 1
                    END AS 排名,
                    @省份 := a.省份 AS 省份,
                    @温区 := a.温区 AS 温区,
                    @中类 := a.中类 AS 中类
                FROM
                        cwl_baokuan_7day a,
                        ( SELECT @省份 := null,  @温区 := null, @中类 := null, @rank := 0 ) T
                WHERE
                        a.use1 = '是' 
                        and a.use2 = '是'
                ORDER BY
                        a.省份 ASC,a.温区 ASC, a.中类 ASC, a.销量 DESC
            ) as m2 on m1.省份=m2.省份 and m1.温区=m2.温区 and m1.货号=m2.货号
            set
                m1.排名 = m2.排名
            where
                m1.省份=m2.省份 
                and m1.温区=m2.温区
                and m1.货号=m2.货号
        ";
        $this->db_easyA->execute($sql_分组排名);
    }

    // 更新图片路径
    public function handle_2() {
        $sql_TOP = "
            SELECT
                GoodsId 
            FROM
                `cwl_cgzdt_caigoushouhuo` 
            WHERE
                TOP = 'Y'
        ";
        $select_TOP = $this->db_easyA->query($sql_TOP);
        $goodsId = '';
        foreach ($select_TOP as $key => $val) {
            if ($key + 1 < count($select_TOP)) {
                $goodsId .= $val['GoodsId'].',';
            } else {
                $goodsId .= $val['GoodsId'];
            }
        }

        $sql_图片 = "
            SELECT
                GoodsId,Img 
            FROM
                ErpGoodsImg 
            WHERE
                GoodsId IN ( {$goodsId} )
        ";
        $select_图片 = $this->db_sqlsrv->query($sql_图片);
        $select_data = $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->field('GoodsId')->where(['TOP' => 'Y'])->select();

        foreach ($select_data as $k1 => $v1) {
            foreach ($select_图片 as $k2 => $v2) {
                if ($v1['GoodsId'] == $v2['GoodsId']) {
                    $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->where(['GoodsId' => $v1['GoodsId']])->update([
                        '图片路径' => $v2['Img']
                    ]);
                    break;
                }
            }
        }
        
    }

    // 创建图片
    public function createImg() {
        $sql = "select * from cwl_cgzdt_config";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            foreach ($select as $key => $val) {
                // $path = "/data/web/cwl/img/cgzdt_{$val['值']}.jpg";

                $path = "/data/web/easyadmin2/easyadmin/public/img/".date('Ymd').'/'. "cgzdt_{$val['值']}.jpg";

                echo "wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}";
                echo '<br>';
                // wkhtmltoimage --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?中类=羽绒服 /data/web/cwl/cgzdt_test1.jpg

                // $res = system("wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}", $result);
                // print $result;//输出命令的结果状态码
                // print $res;//输出命令输出的最后一行
            }
        }
    }

}
