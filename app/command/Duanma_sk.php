<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;


// 断码sk cwl_duanmalv_sk
class Duanma_sk extends Command
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';

    protected function configure() {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        // 指令配置
        $this->setName('Duanma_sk')
            ->setDescription('the Duanma_sk command');
    }

    protected function execute(Input $input, Output $output)
    {
        $sql = "
            SELECT 
                sk.云仓,
                sk.店铺名称,
                c.CustomerGrade as 店铺等级,
                sk.商品负责人,
                sk.省份,
                sk.经营模式,
                sk.年份,
                sk.季节, 
                sk.一级分类,
                sk.二级分类,
                sk.分类,
                sk.风格,
                SUBSTRING(sk.分类, 1, 2) as 领型,
                sk.货号,
                sk.`总入量00/28/37/44/100/160/S`,
                sk.`总入量29/38/46/105/165/M`,
                sk.`总入量30/39/48/110/170/L`,
                sk.`总入量31/40/50/115/175/XL`,
                sk.`总入量32/41/52/120/180/2XL`,
                sk.`总入量33/42/54/125/185/3XL`,
                sk.`总入量34/43/56/190/4XL`,
                sk.`总入量35/44/58/195/5XL`,
                sk.`总入量36/6XL`,
                sk.`总入量38/7XL`,
                sk.`总入量_40`,
                sk.`总入量数量`,
                sk.`累销00/28/37/44/100/160/S`,
                sk.`累销29/38/46/105/165/M`,
                sk.`累销30/39/48/110/170/L`,
                sk.`累销31/40/50/115/175/XL`,
                sk.`累销32/41/52/120/180/2XL`,
                sk.`累销33/42/54/125/185/3XL`,
                sk.`累销34/43/56/190/4XL`,
                sk.`累销35/44/58/195/5XL`,
                sk.`累销36/6XL`,
                sk.`累销38/7XL`,
                sk.`累销_40`,
                sk.`累销数量`,
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
                sk.`预计库存数量`,
                CASE
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAAAA%' THEN 11 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAAA%' THEN 10 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAA%' THEN 9 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAA%' THEN 8 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAA%' THEN 7 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAA%' THEN 6	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAA%' THEN 5	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAA%' THEN 4	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAA%' THEN 3	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AA%' THEN 2		
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%A%' THEN 1		
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%BBBBBBBBBBB%' THEN 0
                END AS 预计库存连码个数,
                CASE
                    WHEN sk.`预计库存数量` > 1
                    THEN 1
                    ELSE 0	
                END AS 店铺SKC计数

                FROM `sp_sk` as sk
                LEFT JOIN customer as c ON sk.店铺名称=c.CustomerName

                WHERE
                    sk.季节 IN ('初夏', '盛夏', '夏季') 
                -- 	AND sk.年份 = 2023
                -- 	AND sk.省份='广东省'
                -- 	AND sk.店铺名称='东莞三店'
                -- 	AND sk.货号='B32101027'
                GROUP BY 
                    sk.店铺名称, 
                    sk.季节, 
                    sk.货号
                -- limit 100    
        ";
		
        $select_sk = $this->db_bi->query($sql);
        $count = count($select_sk);

        if ($select_sk) {
            // 删除历史数据
            $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $chunk_list = array_chunk($select_sk, 3000);
            $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_sk')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_duanmalv_sk first 更新成功，数量：{$count}！"
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_sk first 更新失败！'
                ]);
            }
        }
    }

}
