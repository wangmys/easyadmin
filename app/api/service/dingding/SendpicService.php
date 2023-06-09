<?php

namespace app\api\service\dingding;
use app\common\traits\Singleton;
use app\common\constants\AdminConstant;
use think\facade\Db;

class SendpicService
{

    use Singleton;

    /**
     * 获取店铺导购
     */
    public function get_daogou_users($dept_id) {

        $daogou_users_sql = "select u.id, u.erp_uid, u.real_name, u.checkin_sys_uid  from user u 
        left join user_dept_relation udr on u.id=udr.user_id 
        left join user_role_relations urr on u.id=urr.user_id 
        left join role r on r.id=urr.role_id 
        where dept_id='{$dept_id}' and u.erp_uid<>'' and u.state=0 and u.is_virtual=0 and u.checkin_sys_uid<>'';"; // and r.name like '%导购%'
        return Db::connect("cip")->Query($daogou_users_sql);

    }

    /**
     * 获取店长信息
     */
    public function get_dianzhang_info($name) {

        $sql = "select real_name, checkin_sys_uid  from user where real_name='{$name}';";
        return Db::connect("cip")->Query($sql);

    }

    /**
     * 返回店铺目标
     * @param $start_time
     * @param $end_time
     * @param $dept_id
     * @return int|mixed|string
     */
    public function get_dept_aim($start_time, $end_time, $dept_id) {

        $sql = "SELECT
        d.id AS deptId,
        d.erp_shop_id AS erpShopId,
        d.erp_shop_alias AS erpShopAlia,
        d.`name` AS deptName,
        sum(sdot.amount) AS amount,
        sdot.date AS forDate 
    FROM
        dept d
        LEFT JOIN sales_day_object_target sdot ON sdot.type = 0 
        AND sdot.for_type = 0  
        AND sdot.for_id = d.id  
    WHERE
        d.sale_quality = 'ZY'   
        AND d.type = 1   
        AND (sdot.date between '{$start_time}' and '{$end_time}') 
        AND d.is_virtual = 0   
        AND d.del_flag = 0  
        and d.id='{$dept_id}'";
        
        $res = Db::connect("cip")->Query($sql);
        return $res ? $res[0]['amount'] : 0;

    }

    public function return_daogou_sql($store_id, $start_time, $end_time, $if_whole_country = 0, $order_by = 'sum') {

        $if_whole_country_str = '';
        if ($if_whole_country) {
            $if_whole_country_str = "";
        } else {
            $if_whole_country_str = "AND EC.CustomerId='{$store_id}'";
        }
        return "SELECT 
    T.CustomerId,
	T.CustomerCode,
	T.CustomerName,
	T.SalesmanName as Name,
	T.SalesmanID,
	T.CustomItem19,
	SUM(T.[销售数量]) AS quantity,
	SUM(T.[单数]) AS count,
	SUM(T.[销售业绩]) 五大类业绩,
	SUM(T.[总销售业绩]) AS sum,
	CASE WHEN SUM(T.[销售数量])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[销售数量])) END  AS jd,
	CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[单数])) END  AS kd,
	CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售数量])/SUM(T.[单数])) END AS ld
FROM 
(
SELECT  
    EC.CustomerId,
	EC.CustomerCode,
	EC.CustomerName,
	ERG.SalesmanName,
	ERG.SalesmanID,
	ER.RetailID,
	EC.CustomItem19,
	SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS 销售数量,
	SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS 销售业绩,
	SUM(ERG.Quantity*ERG.DiscountPrice) AS 总销售业绩,
	CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS 单数
FROM ErpRetail ER
LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
WHERE EC.ShutOut=0
	AND ER.CodingCodeText='已审结'
	{$if_whole_country_str}
	AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}'
	AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退'  	AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}' GROUP BY ER.RetailID )
	AND ERG.Status!='赠'
GROUP BY 
    EC.CustomerId,
	ERG.SalesmanName,
	ERG.SalesmanID,
	EC.CustomerCode,
	EC.CustomerName,
EC.CustomItem19,
	ER.RetailID
) T
GROUP BY 
    T.CustomerId,
	T.CustomerCode,
	T.SalesmanName,
	T.SalesmanID,
	T.CustomerName,
	T.CustomItem19
ORDER BY 
	{$order_by} desc;";

    }

    /**
     * 获取业绩
     */
    public function get_achievement($store_id, $start_time, $end_time, $if_whole_country = 0, $order_by = 'sum') {

        return Db::connect("sqlsrv")->Query($this->return_daogou_sql($store_id, $start_time, $end_time, $if_whole_country, $order_by));

    }

    /**
     * 获取所有店铺业绩
     */
    public function return_customer_retail($start_time, $end_time) {

        $sql = "SELECT 
            T.CustomerId,
            T.CustomerCode,
            T.CustomerName,
            T.CustomItem19,
            SUM(T.[销售数量]) AS quantity,
            SUM(T.[单数]) AS count,
            SUM(T.[销售业绩]) 五大类业绩,
            SUM(T.[总销售业绩]) AS sum,
            CASE WHEN SUM(T.[销售数量])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[销售数量])) END  AS jd,
            CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[单数])) END  AS kd,
            CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售数量])/SUM(T.[单数])) END AS ld
        FROM 
        (
        SELECT  
            EC.CustomerId,
            EC.CustomerCode,
            EC.CustomerName,
            EC.CustomItem19,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS 销售数量,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS 销售业绩,
            SUM(ERG.Quantity*ERG.DiscountPrice) AS 总销售业绩,
            CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS 单数
        FROM ErpRetail ER
        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
        LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
        WHERE EC.ShutOut=0
            AND ER.CodingCodeText='已审结' 
            AND EC.MathodId = 4 
            --AND EC.CustomerName='福泉一店' 
            AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}' 
            AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退'  	AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}' GROUP BY ER.RetailID )
            AND ERG.Status!='赠'
        GROUP BY 
            EC.CustomerId,
            EC.CustomerCode,
            EC.CustomerName,
          EC.CustomItem19
            --ER.RetailID
        ) T
        GROUP BY 
            T.CustomerId,
            T.CustomerCode,
            T.CustomerName,
            T.CustomItem19
        ORDER BY sum desc;";
        return Db::connect("sqlsrv")->Query($sql);

    }

    /**
     * 返回导购本月目标
     * @param $erp_uid
     * @param $start_time
     * @param $end_time
     * @return string
     */
    public function return_month_aim_sql($erp_uid, $start_time, $end_time) {

        $sql = "SELECT
 userId,
 userName,
 userErpId,
 deptId,
 deptName,
 deptErpId,
 postId,
 postName,
 storeRelationId,
 SUM( originalTarget ) monthOriginalTarget,
 SUM( practicalTarget ) monthPracticalTarget 
FROM
 (
 SELECT
 u.id AS userId,
 u.real_name AS userName,
 u.erp_uid AS userErpId,
 d.id AS deptId,
 d.`name` AS deptName,
 d.erp_shop_id AS deptErpId,
 uspr.post_id AS postId,
 sp.post AS postName,
 sp.type AS storeRelationId,
 CASE
 sp.type 
 WHEN 3 THEN
 '店长' 
 WHEN 2 THEN
 '店助' ELSE '导购' 
 END AS storeRelationName,
 IFNULL( sdot.old_amount, 0 ) AS originalTarget,
 IFNULL( sdot.amount, 0 ) AS practicalTarget 
 FROM
 `user` u
 LEFT JOIN user_salary_post_relation uspr ON u.id = uspr.user_id
 LEFT JOIN salary_post sp ON uspr.post_id = sp.id
 LEFT JOIN user_dept_relation udr ON u.id = udr.user_id
 LEFT JOIN dept d ON d.id = udr.dept_id
 LEFT JOIN sales_day_object_target sdot ON sdot.for_id = u.id 
 AND sdot.type = 0 
 AND sdot.for_type = 1 
 WHERE
 u.state = 0 
 AND u.is_virtual = 0 
 AND uspr.post_id IS NOT NULL 
 AND d.sale_quality = 'ZY' 
 AND d.del_flag = 0 
 AND d.is_virtual = 0 
 AND d.type = 1 
 AND u.erp_uid = '{$erp_uid}' 
 AND sdot.date between '{$start_time}' and '{$end_time}'
 ) z 
GROUP BY
 userId,
 userName,
 userErpId,
 deptId,
 deptName,
 deptErpId,
 postId,
 postName,
 storeRelationId
";
        $res = Db::connect("cip")->Query($sql);
        return $res ? $res[0]['monthPracticalTarget'] : 0;

    }

    /**
     * 获取店铺信息
     */
    public function get_customer_info($customer_id, $field='*') {

        return Db::connect("sqlsrv")->Query("select $field from ErpCustomer where CustomerId='{$customer_id}'");

    }




}

