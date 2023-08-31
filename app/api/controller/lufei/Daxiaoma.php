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
 * @ControllerAnnotation(title="大小码")
 */
class Daxiaoma extends BaseController
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

    public function seasionHandle($seasion = "夏季,秋季") {
        $seasionStr = "";
        $seasion = explode(',', $seasion);
        foreach ($seasion as $key => $val) {
            if ($key + 1 == count($seasion)) {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季'";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季'";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季'";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季'";
                }
            } else {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季',";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季',";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季',";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季',";
                }
            }
        }

        return $seasionStr;
    }

    // 货品资料
    public function hpzl_1()
    {
        $year = date('Y', time());

        $sql = "
            select * from sp_ww_hpzl where 年份 in ({$year})
        ";
		
        $select = $this->db_bi->query($sql);
        $count = count($select);

        // dump($select);die;

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_daxiao_hpzl;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_daxiao_hpzl')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_daxiao_hpzl 更新成功，数量：{$count}！"
            ]);
        }
    }

    // 货品资料
    public function hpzl_2()
    {
        $year = date('Y', time());

        $sql = "
            select 货号 from cwl_daxiao_hpzl where 1
        ";
        
        $select = $this->db_easyA->query($sql);

        $str_goodsno = '';
        $str_goodsno_pro = "";
        foreach ($select as $key => $val) {
            if ($key + 1 < count($select)) {
                $str_goodsno .= $val['货号'] . ',';
            } else {
                $str_goodsno .= $val['货号'];
            }
        }
        $str_goodsno_pro = xmSelectInput($str_goodsno); 
        
        $sql2 = "
            select GoodsNo as 货号,StyleCategoryName1 as 一级风格 from ErpGoods where GoodsNo in ({$str_goodsno_pro})
        ";
        $select_一级风格 = $this->db_sqlsrv->query($sql2);


        if ($select_一级风格) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_daxiao_hpzl_temp;');
            $chunk_list = array_chunk($select_一级风格, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_daxiao_hpzl_temp')->strict(false)->insertAll($val);

            }

            $sql3 = "
                update cwl_daxiao_hpzl as hpzl 
                left join cwl_daxiao_hpzl_temp as t on hpzl.货号 = t.货号
                set
                    hpzl.一级风格 = t.一级风格
                where hpzl.一级风格 is null
            ";
            $this->db_easyA->execute($sql3);

            $sql4 = "
                update sp_sk as sk 
                left join cwl_daxiao_hpzl_temp as t on sk.货号 = t.货号
                set
                    sk.一级风格 = t.一级风格
                where sk.一级风格 is null
            ";
            $this->db_easyA->execute($sql4);

            $sql5 = "
                delete from sp_sk where 一级风格='时尚系列(停用)'
            ";
            $this->db_easyA->execute($sql5);

            $sql6 = "
                update sp_sk 
                    set
                        季节归集 = 
                        case
                            when 季节 in ('初春', '正春', '春季') then '春季'
                            when 季节 in ('初夏', '盛夏', '夏季') then '夏季'
                            when 季节 in ('初秋', '深秋', '秋季') then '秋季'
                            when 季节 in ('初冬', '深冬', '冬季') then '冬季'
                            else '通季'
                        end
                where 季节归集 is null
            ";
            $this->db_easyA->execute($sql6);
        }
    }

    public function retail() {
        $sql_customer = "
            SELECT 
                店铺名称,一级分类,二级分类,风格,一级风格,二级分类,季节归集,
                round(`累销00/28/37/44/100/160/S` / `累销数量`, 3) as `占比00/28/37/44/100/160/S`,
                round(`累销29/38/46/105/165/M` / `累销数量`, 3) as `占比29/38/46/105/165/M`,
                round(`累销30/39/48/110/170/L` / `累销数量`, 3) as `占比30/39/48/110/170/L`,
                round(`累销31/40/50/115/175/XL` / `累销数量`, 3) as `占比31/40/50/115/175/XL`,
                round(`累销32/41/52/120/180/2XL` / `累销数量`, 3) as `占比32/41/52/120/180/2XL`,
                round(`累销33/42/54/125/185/3XL` / `累销数量`, 3) as `占比33/42/54/125/185/3XL`,
                round(`累销34/43/56/190/4XL` / `累销数量`, 3) as `占比34/43/56/190/4XL`,
                round(`累销35/44/58/195/5XL` / `累销数量`, 3) as `占比35/44/58/195/5XL`,
                round(`累销36/6XL` / `累销数量`, 3) as `占比36/6XL`,
                round(`累销38/7XL` / `累销数量`, 3) as `占比38/7XL`,
                round(`累销_40` / `累销数量`, 3) as `占比_40`
            FROM (
                SELECT
                    店铺名称,一级分类,二级分类,风格,一级风格,季节归集,
                    sum(`累销00/28/37/44/100/160/S`) as `累销00/28/37/44/100/160/S`, 
                    sum(`累销29/38/46/105/165/M`) as `累销29/38/46/105/165/M`, 
                    sum(`累销30/39/48/110/170/L`) as `累销30/39/48/110/170/L`, 
                    sum(`累销31/40/50/115/175/XL`) as `累销31/40/50/115/175/XL`, 
                    sum(`累销32/41/52/120/180/2XL`) as `累销32/41/52/120/180/2XL`, 
                    sum(`累销33/42/54/125/185/3XL`) as `累销33/42/54/125/185/3XL`, 
                    sum(`累销34/43/56/190/4XL`) as `累销34/43/56/190/4XL`, 
                    sum(`累销35/44/58/195/5XL`) as `累销35/44/58/195/5XL`, 
                    sum(`累销36/6XL`) as `累销36/6XL`, 
                    sum(`累销38/7XL`) as `累销38/7XL`, 
                    sum(`累销_40`) as `累销_40`, 
                    sum(`累销数量`) as `累销数量`
                FROM
                    `sp_sk`
                GROUP BY 店铺名称,一级分类,二级分类,风格,一级风格,季节归集
            ) AS t	
            ORDER BY 季节归集,店铺名称,风格,一级分类,二级分类,一级风格
        ";
        $sql_province = "
            SELECT 
                省份,一级分类,二级分类,风格,一级风格,季节归集,
                round(`累销00/28/37/44/100/160/S` / `累销数量`, 3) as `占比00/28/37/44/100/160/S`,
                round(`累销29/38/46/105/165/M` / `累销数量`, 3) as `占比29/38/46/105/165/M`,
                round(`累销30/39/48/110/170/L` / `累销数量`, 3) as `占比30/39/48/110/170/L`,
                round(`累销31/40/50/115/175/XL` / `累销数量`, 3) as `占比31/40/50/115/175/XL`,
                round(`累销32/41/52/120/180/2XL` / `累销数量`, 3) as `占比32/41/52/120/180/2XL`,
                round(`累销33/42/54/125/185/3XL` / `累销数量`, 3) as `占比33/42/54/125/185/3XL`,
                round(`累销34/43/56/190/4XL` / `累销数量`, 3) as `占比34/43/56/190/4XL`,
                round(`累销35/44/58/195/5XL` / `累销数量`, 3) as `占比35/44/58/195/5XL`,
                round(`累销36/6XL` / `累销数量`, 3) as `占比36/6XL`,
                round(`累销38/7XL` / `累销数量`, 3) as `占比38/7XL`,
                round(`累销_40` / `累销数量`, 3) as `占比_40`
            FROM (
                SELECT
                    省份,一级分类,二级分类,风格,一级风格,季节归集,
                    sum(`累销00/28/37/44/100/160/S`) as `累销00/28/37/44/100/160/S`, 
                    sum(`累销29/38/46/105/165/M`) as `累销29/38/46/105/165/M`, 
                    sum(`累销30/39/48/110/170/L`) as `累销30/39/48/110/170/L`, 
                    sum(`累销31/40/50/115/175/XL`) as `累销31/40/50/115/175/XL`, 
                    sum(`累销32/41/52/120/180/2XL`) as `累销32/41/52/120/180/2XL`, 
                    sum(`累销33/42/54/125/185/3XL`) as `累销33/42/54/125/185/3XL`, 
                    sum(`累销34/43/56/190/4XL`) as `累销34/43/56/190/4XL`, 
                    sum(`累销35/44/58/195/5XL`) as `累销35/44/58/195/5XL`, 
                    sum(`累销36/6XL`) as `累销36/6XL`, 
                    sum(`累销38/7XL`) as `累销38/7XL`, 
                    sum(`累销_40`) as `累销_40`, 
                    sum(`累销数量`) as `累销数量`
                FROM
                    `sp_sk`
                GROUP BY 省份,一级分类,二级分类,风格,一级风格,季节归集
            ) AS t	
            ORDER BY 季节归集,省份,风格, 一级分类,二级分类,一级风格
        ";
        $select_customer = $this->db_easyA->query($sql_customer);
        $select_province = $this->db_easyA->query($sql_province);
        if ($select_customer) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_daxiao_retail_customer;');
            $chunk_list = array_chunk($select_customer, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_daxiao_retail_customer')->strict(false)->insertAll($val);

            }
        }
        if ($select_province) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_daxiao_retail_province;');
            $chunk_list = array_chunk($select_province, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_daxiao_retail_province')->strict(false)->insertAll($val);

            }
        }
    }

    public function handle_1() {
        $sql = "
            SELECT
                省份,店铺名称,一级分类,二级分类,风格,一级风格,季节归集,
                sum(case when `预计00/28/37/44/100/160/S` then 1 else null end) as `预计SKC_00/28/37/44/100/160/S`,
                sum(case when `预计29/38/46/105/165/M` then 1 else null end) as `预计SKC_29/38/46/105/165/M`,
                sum(case when `预计30/39/48/110/170/L` then 1 else null end) as `预计SKC_30/39/48/110/170/L`,
                sum(case when `预计31/40/50/115/175/XL` then 1 else null end) as `预计SKC_31/40/50/115/175/XL`,
                sum(case when `预计32/41/52/120/180/2XL` then 1 else null end) as `预计SKC_32/41/52/120/180/2XL`,
                sum(case when `预计33/42/54/125/185/3XL` then 1 else null end) as `预计SKC_33/42/54/125/185/3XL`,
                sum(case when `预计34/43/56/190/4XL` then 1 else null end) as `预计SKC_34/43/56/190/4XL`,
                sum(case when `预计35/44/58/195/5XL` then 1 else null end) as `预计SKC_35/44/58/195/5XL`,
                sum(case when `预计36/6XL` then 1 else null end) as `预计SKC_36/6XL`,
                sum(case when `预计38/7XL` then 1 else null end) as `预计SKC_38/7XL`,
                sum(case when `预计_40` then 1 else null end) as `预计SKC_40`,
                sum(`预计00/28/37/44/100/160/S`) as `预计00/28/37/44/100/160/S`,
                sum(`预计29/38/46/105/165/M`) as `预计29/38/46/105/165/M`,
                sum(`预计30/39/48/110/170/L`) as `预计30/39/48/110/170/L`,
                sum(`预计31/40/50/115/175/XL`) as `预计31/40/50/115/175/XL`,
                sum(`预计32/41/52/120/180/2XL`) as `预计32/41/52/120/180/2XL`,
                sum(`预计33/42/54/125/185/3XL`) as `预计33/42/54/125/185/3XL`,
                sum(`预计34/43/56/190/4XL`) as `预计34/43/56/190/4XL`,
                sum(`预计35/44/58/195/5XL`) as `预计35/44/58/195/5XL`,
                sum(`预计36/6XL`) as `预计36/6XL`,
                sum(`预计38/7XL`) as `预计38/7XL`,
                sum(`预计_40`) as `预计_40`,
                sum(`预计库存数量`) as `预计库存数量`,
                sum(`总入量00/28/37/44/100/160/S`) as `总入量00/28/37/44/100/160/S`,
                sum(`总入量29/38/46/105/165/M`) as `总入量29/38/46/105/165/M`,
                sum(`总入量30/39/48/110/170/L`) as `总入量30/39/48/110/170/L`,
                sum(`总入量31/40/50/115/175/XL`) as `总入量31/40/50/115/175/XL`,
                sum(`总入量32/41/52/120/180/2XL`) as `总入量32/41/52/120/180/2XL`,
                sum(`总入量33/42/54/125/185/3XL`) as `总入量33/42/54/125/185/3XL`,
                sum(`总入量34/43/56/190/4XL`) as `总入量34/43/56/190/4XL`,
                sum(`总入量35/44/58/195/5XL`) as `总入量35/44/58/195/5XL`,
                sum(`总入量36/6XL`) as `总入量36/6XL`,
                sum(`总入量38/7XL`) as `总入量38/7XL`,
                sum(`总入量_40`) as `总入量_40`,
                sum(`总入量数量`) as `总入量数量`,
                sum(`累销00/28/37/44/100/160/S`) as `累销00/28/37/44/100/160/S`, 
                sum(`累销29/38/46/105/165/M`) as `累销29/38/46/105/165/M`, 
                sum(`累销30/39/48/110/170/L`) as `累销30/39/48/110/170/L`, 
                sum(`累销31/40/50/115/175/XL`) as `累销31/40/50/115/175/XL`, 
                sum(`累销32/41/52/120/180/2XL`) as `累销32/41/52/120/180/2XL`, 
                sum(`累销33/42/54/125/185/3XL`) as `累销33/42/54/125/185/3XL`, 
                sum(`累销34/43/56/190/4XL`) as `累销34/43/56/190/4XL`, 
                sum(`累销35/44/58/195/5XL`) as `累销35/44/58/195/5XL`, 
                sum(`累销36/6XL`) as `累销36/6XL`, 
                sum(`累销38/7XL`) as `累销38/7XL`, 
                sum(`累销_40`) as `累销_40`, 
                sum(`累销数量`) as `累销数量`
            FROM
                `sp_sk`
            WHERE 1 
                AND 季节归集 in ('秋季')
            GROUP BY 省份,店铺名称,风格,一级分类,二级分类,一级风格
            ORDER BY 省份,店铺名称, 风格, 一级分类,二级分类,一级风格

        ";
        $select = $this->db_easyA->query($sql);

        $this->db_easyA->execute('TRUNCATE cwl_daxiao_handle;');
        $chunk_list = array_chunk($select, 500);
        foreach($chunk_list as $key => $val) {
            // 基础结果 
            $this->db_easyA->table('cwl_daxiao_handle')->strict(false)->insertAll($val);
        }

        $sql_更新店省占比 = "
            UPDATE cwl_daxiao_handle as sk 
            LEFT JOIN cwl_daxiao_retail_customer as c ON sk.店铺名称 = c.店铺名称 AND  sk.一级分类 = c.一级分类 AND  sk.二级分类 = c.二级分类 AND sk.风格 = c.风格 AND sk.一级风格 = c.一级风格 AND sk.季节归集 = c.季节归集
            LEFT JOIN cwl_daxiao_retail_province as p ON sk.省份 = p.省份 AND  sk.一级分类 = p.一级分类 AND  sk.二级分类 = p.二级分类 AND sk.风格 = p.风格 AND sk.一级风格 = p.一级风格 AND sk.季节归集 = p.季节归集
            SET
                sk.`店销占比00/28/37/44/100/160/S` =  c.`占比00/28/37/44/100/160/S`,
                sk.`店销占比29/38/46/105/165/M` =  c.`占比29/38/46/105/165/M`,
                sk.`店销占比30/39/48/110/170/L` = c.`占比30/39/48/110/170/L`,
                sk.`店销占比31/40/50/115/175/XL` = c.`占比31/40/50/115/175/XL`,
                sk.`店销占比32/41/52/120/180/2XL` = c.`占比32/41/52/120/180/2XL`,
                sk.`店销占比33/42/54/125/185/3XL` = c.`占比33/42/54/125/185/3XL`,
                sk.`店销占比34/43/56/190/4XL` = c.`占比34/43/56/190/4XL`,
                sk.`店销占比35/44/58/195/5XL` = c.`占比35/44/58/195/5XL`,
                sk.`店销占比36/6XL` = c.`占比36/6XL`,
                sk.`店销占比38/7XL` = c.`占比38/7XL`,
                sk.`店销占比_40` = c.`占比_40`,

                sk.`省销占比00/28/37/44/100/160/S` =  p.`占比00/28/37/44/100/160/S`,
                sk.`省销占比29/38/46/105/165/M` =  p.`占比29/38/46/105/165/M`,
                sk.`省销占比30/39/48/110/170/L` = p.`占比30/39/48/110/170/L`,
                sk.`省销占比31/40/50/115/175/XL` = p.`占比31/40/50/115/175/XL`,
                sk.`省销占比32/41/52/120/180/2XL` = p.`占比32/41/52/120/180/2XL`,
                sk.`省销占比33/42/54/125/185/3XL` = p.`占比33/42/54/125/185/3XL`,
                sk.`省销占比34/43/56/190/4XL` = p.`占比34/43/56/190/4XL`,
                sk.`省销占比35/44/58/195/5XL` = p.`占比35/44/58/195/5XL`,
                sk.`省销占比36/6XL` = p.`占比36/6XL`,
                sk.`省销占比38/7XL` = p.`占比38/7XL`,
                sk.`省销占比_40` = p.`占比_40`	
            WHERE 1
        ";
        $this->db_easyA->execute($sql_更新店省占比);
    }

    // 14分钟！ 计算前三名
    public function handle_2() {
        $select_handle = $this->db_easyA->table('cwl_daxiao_handle')->field('店铺名称,一级分类,二级分类,风格,一级风格,季节归集,
            `店销占比00/28/37/44/100/160/S`,
            `店销占比29/38/46/105/165/M`,
            `店销占比30/39/48/110/170/L`,
            `店销占比31/40/50/115/175/XL`,
            `店销占比32/41/52/120/180/2XL`,
            `店销占比33/42/54/125/185/3XL`,
            `店销占比34/43/56/190/4XL`,
            `店销占比35/44/58/195/5XL`,
            `店销占比36/6XL`,
            `店销占比38/7XL`,
            `店销占比_40`
        ')->where( 1
            // [
            //     ['店铺名称', '=', '个旧一店']
            // ]
        )->select()->toArray();

        
        foreach ($select_handle as $key => $val) {
            $arr_data = [];
            $arr_data['店销占比00/28/37/44/100/160/S'] = $val['店销占比00/28/37/44/100/160/S'] > 0 ? $val['店销占比00/28/37/44/100/160/S'] : NULL;
            $arr_data['店销占比29/38/46/105/165/M'] = $val['店销占比29/38/46/105/165/M'] > 0 ? $val['店销占比29/38/46/105/165/M'] : NULL;
            $arr_data['店销占比30/39/48/110/170/L'] = $val['店销占比30/39/48/110/170/L'] > 0 ? $val['店销占比30/39/48/110/170/L'] : NULL;
            $arr_data['店销占比31/40/50/115/175/XL'] = $val['店销占比31/40/50/115/175/XL'] > 0 ? $val['店销占比31/40/50/115/175/XL'] : NULL;
            $arr_data['店销占比32/41/52/120/180/2XL'] = $val['店销占比32/41/52/120/180/2XL'] > 0 ? $val['店销占比32/41/52/120/180/2XL'] : NULL;
            $arr_data['店销占比33/42/54/125/185/3XL'] = $val['店销占比33/42/54/125/185/3XL'] > 0 ? $val['店销占比33/42/54/125/185/3XL'] : NULL;
            $arr_data['店销占比34/43/56/190/4XL'] = $val['店销占比34/43/56/190/4XL'] > 0 ? $val['店销占比34/43/56/190/4XL'] : NULL;
            $arr_data['店销占比35/44/58/195/5XL'] = $val['店销占比35/44/58/195/5XL'] > 0 ? $val['店销占比35/44/58/195/5XL'] : NULL;
            $arr_data['店销占比36/6XL'] = $val['店销占比36/6XL'] > 0 ? $val['店销占比36/6XL'] : NULL;
            $arr_data['店销占比38/7XL'] = $val['店销占比38/7XL'] > 0 ? $val['店销占比38/7XL'] : NULL;
            $arr_data['店销占比_40'] = $val['店销占比_40'] > 0 ? $val['店销占比_40'] : NULL;
            // 排序
            arsort($arr_data);

            // 删除重复数值
            $arr_data = array_unique($arr_data);
            // 重组下标
            $arr_data = array_values($arr_data);

            $this->db_easyA->table('cwl_daxiao_handle')->where([
                '店铺名称' => $val['店铺名称'],
                '一级分类' => $val['一级分类'],
                '二级分类' => $val['二级分类'],
                '风格' => $val['风格'],
                '一级风格' => $val['一级风格'],
                '季节归集' => $val['季节归集'],
            ])->update([
                '店销占比排名1' => @$arr_data[0],
                '店销占比排名2' => @$arr_data[1],
                '店销占比排名3' => @$arr_data[2],
                '店销占比排名4' => @$arr_data[3],
                '店销占比排名5' => @$arr_data[4],
                '店销占比排名6' => @$arr_data[5],
            ]);
        }
    }

    // 大小码明细提醒
    public function handle_3() {
        $_内搭外套 = "
            update cwl_daxiao_handle
            set
                大小码提醒 = 
                case
                    when `店销占比34/43/56/190/4XL` / `省销占比34/43/56/190/4XL` >= 1.2 OR `店销占比35/44/58/195/5XL` / `省销占比35/44/58/195/5XL` >= 2.5 then '建议偏大'
                    when `店销占比29/38/46/105/165/M` / `省销占比29/38/46/105/165/M` >= 2.5 OR `店销占比30/39/48/110/170/L` / `省销占比30/39/48/110/170/L` >= 1.2 then '建议偏小'
                    when (
                        `店销占比29/38/46/105/165/M` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            OR `店销占比30/39/48/110/170/L` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`) 
                        ) AND `店销占比31/40/50/115/175/XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                        AND `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND `店销占比30/39/48/110/170/L` - `店销占比33/42/54/125/185/3XL` >= 0.1 then '建议偏小'
                    WHEN `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND `店销占比33/42/54/125/185/3XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND (
                                `店销占比34/43/56/190/4XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`) 
                                OR `店销占比35/44/58/195/5XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            )
                            AND `店销占比33/42/54/125/185/3XL` - `店销占比30/39/48/110/170/L` >= 0.1 then '建议偏大'
                end
            WHERE 1
                AND 一级分类 in ('内搭', '外套')
                AND (
                    (`店销占比30/39/48/110/170/L` > 0 OR `店销占比34/43/56/190/4XL` > 0)
                    AND (`店销占比32/41/52/120/180/2XL` > 0 AND `店销占比33/42/54/125/185/3XL` > 0) 
                )
        ";
        $this->db_easyA->execute($_内搭外套);

        $sql_下装 = "
            update cwl_daxiao_handle
            set
                大小码提醒 = 
                case
                    when `店销占比00/28/37/44/100/160/S` / `省销占比00/28/37/44/100/160/S` >= 2 OR `店销占比29/38/46/105/165/M` / `省销占比29/38/46/105/165/M` >= 1.5 then '建议偏小'
                    when `店销占比38/7XL` / `省销占比38/7XL` >= 2 OR `店销占比36/6XL` / `省销占比36/6XL` >= 1.5 then '建议偏大'
                    when (
                        `店销占比00/28/37/44/100/160/S` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`)
                            OR `店销占比29/38/46/105/165/M` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                        ) AND `店销占比30/39/48/110/170/L` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                        AND `店销占比31/40/50/115/175/XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比29/38/46/105/165/M` - `店销占比36/6XL` >= 0.1 then '建议偏小'
                    WHEN (
                                `店销占比_40` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`)
                                OR `店销占比38/7XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`)
                            )
                            AND `店销占比36/6XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比35/44/58/195/5XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比34/43/56/190/4XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比33/42/54/125/185/3XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`, `店销占比排名5`, `店销占比排名6`) 
                            AND `店销占比36/6XL` - `店销占比29/38/46/105/165/M` >= 0.1 then '建议偏大'
                end
            WHERE 1
                AND 一级分类 in ('下装')
                AND 二级分类 NOT IN ('松紧长裤', '松紧短裤')
                AND (
                    (`店销占比30/39/48/110/170/L` > 0 OR `店销占比35/44/58/195/5XL` > 0)
                    AND (`店销占比31/40/50/115/175/XL` > 0 AND `店销占比32/41/52/120/180/2XL` > 0 AND `店销占比33/42/54/125/185/3XL` > 0 AND `店销占比34/43/56/190/4XL` > 0) 
                )        
        ";
        $this->db_easyA->execute($sql_下装);

        $sql_松紧 = "
            update cwl_daxiao_handle
            set
                大小码提醒 = 
                case
                    when `店销占比00/28/37/44/100/160/S` / `省销占比00/28/37/44/100/160/S` >= 2 OR `店销占比29/38/46/105/165/M` / `省销占比29/38/46/105/165/M` >= 1.5 then '建议偏小'
                    when `店销占比35/44/58/195/5XL` / `省销占比35/44/58/195/5XL` >= 2 OR `店销占比34/43/56/190/4XL` / `省销占比34/43/56/190/4XL` >= 1.5 then '建议偏大'
                    when (
                        `店销占比00/28/37/44/100/160/S` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            OR `店销占比29/38/46/105/165/M` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`) 
                        ) AND `店销占比30/39/48/110/170/L` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                        AND `店销占比31/40/50/115/175/XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            AND `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            AND `店销占比29/38/46/105/165/M` - `店销占比34/43/56/190/4XL` >= 0.1 then '建议偏小'
                    WHEN (
                                `店销占比34/43/56/190/4XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                                OR `店销占比35/44/58/195/5XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            )
                            AND `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            AND `店销占比33/42/54/125/185/3XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            AND `店销占比34/43/56/190/4XL` - `店销占比29/38/46/105/165/M` >= 0.1 then '建议偏大'
                end
            WHERE 1
                AND 一级分类 in ('下装')
                AND 二级分类 IN ('松紧长裤', '松紧短裤')
                AND (
                    (`店销占比30/39/48/110/170/L` > 0 OR `店销占比35/44/58/195/5XL` > 0)
                    AND (`店销占比31/40/50/115/175/XL` > 0 AND `店销占比32/41/52/120/180/2XL` > 0 AND `店销占比33/42/54/125/185/3XL` > 0 AND `店销占比34/43/56/190/4XL` > 0) 
                )
        ";
        $this->db_easyA->execute($sql_松紧);

        $sql_鞋履 = "
            update cwl_daxiao_handle
            set
                大小码提醒 = 
                case
                    when `店销占比29/38/46/105/165/M` / `省销占比29/38/46/105/165/M` >= 2 OR `店销占比30/39/48/110/170/L` / `省销占比30/39/48/110/170/L` >= 1.3 then '建议偏小'
                    when `店销占比34/43/56/190/4XL` / `省销占比34/43/56/190/4XL` >= 1.3 OR `店销占比35/44/58/195/5XL` / `省销占比35/44/58/195/5XL` >= 2 then '建议偏大'
                    when `店销占比29/38/46/105/165/M` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`, `店销占比排名4`)
                            AND `店销占比30/39/48/110/170/L` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND `店销占比31/40/50/115/175/XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`) then '建议偏小'
                    WHEN `店销占比32/41/52/120/180/2XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND `店销占比33/42/54/125/185/3XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`)
                            AND `店销占比34/43/56/190/4XL` in (`店销占比排名1`, `店销占比排名2`, `店销占比排名3`) then '建议偏大'
                end
            WHERE 1
                AND 一级分类 in ('鞋履')
                AND (
                    (`店销占比29/38/46/105/165/M` > 0 OR `店销占比33/42/54/125/185/3XL` > 0)
                    AND (`店销占比30/39/48/110/170/L` > 0 AND `店销占比31/40/50/115/175/XL` > 0 AND `店销占比32/41/52/120/180/2XL` > 0) 
                )
        ";
        $this->db_easyA->execute($sql_鞋履);
    }

    public function handle_4() {
        $sql_内搭外套1 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒35/44/58/195/5XL` = '缺'
            WHERE
                1 
                AND 一级分类 IN (
                '内搭', '外套')
                AND 大小码提醒 = '建议偏大'
                AND `预计35/44/58/195/5XL` <= 0   
        ";

        $sql_内搭外套2 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒29/38/46/105/165/M` = '缺'
            WHERE
                1 
                AND 一级分类 IN (
                '内搭', '外套')
                AND 大小码提醒 = '建议偏小'
                AND `预计29/38/46/105/165/M` <= 0   
        "; 
        $this->db_easyA->execute($sql_内搭外套1);
        $this->db_easyA->execute($sql_内搭外套2);

        $sql_下装1 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒38/7XL` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('下装')
                AND 二级分类 NOT IN ('松紧长裤', '松紧短裤')
                AND 大小码提醒 = '建议偏大'
                AND `预计38/7XL` <= 0  
        ";

        $sql_下装2 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒00/28/37/44/100/160/S` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('下装')
                AND 二级分类 NOT IN ('松紧长裤', '松紧短裤')
                AND 大小码提醒 = '建议偏小'
                AND `预计00/28/37/44/100/160/S` <= 0  
        "; 
        $this->db_easyA->execute($sql_下装1);
        $this->db_easyA->execute($sql_下装2);

        $sql_松紧1 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒38/7XL` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('下装')
                AND 二级分类 IN ('松紧长裤', '松紧短裤')
                AND 大小码提醒 = '建议偏大'
                AND `预计38/7XL` <= 0  
        ";

        $sql_松紧2 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒00/28/37/44/100/160/S` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('下装')
                AND 二级分类 IN ('松紧长裤', '松紧短裤')
                AND 大小码提醒 = '建议偏小'
                AND `预计00/28/37/44/100/160/S` <= 0  
        "; 
        $this->db_easyA->execute($sql_松紧1);
        $this->db_easyA->execute($sql_松紧2);

        $sql_鞋履1 = "
            UPDATE
                cwl_daxiao_handle
            SET 
                `未上柜提醒35/44/58/195/5XL` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('鞋履')
                AND 大小码提醒 = '建议偏大'
                AND `预计35/44/58/195/5XL` <= 0  
        ";

        $sql_鞋履2 = "
            UPDATE
                cwl_daxiao_handle
            SET
                `未上柜提醒29/38/46/105/165/M` = '缺'
            WHERE
                1 
                AND 一级分类 IN ('鞋履')
                AND 大小码提醒 = '建议偏小'
                AND `预计29/38/46/105/165/M` <= 0  
        "; 
        $this->db_easyA->execute($sql_鞋履1);
        $this->db_easyA->execute($sql_鞋履2);

    }
}
