<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Chaoliang
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="超量提醒")
 */
class Weathertips extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    /**
     * 
     */
    public function config() {
        $find_config = $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('config', [
            'config' => $find_config,
        ]);
    }

    public function getMapData() {
        // guojingli_shujuyuan_all
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();

            $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->strict(false)->update($params);     

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    // 上传excel
    public function upload() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $new_name = "超量标准修改_". session('admin.name')  . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '风格',
                    'B' => '店铺等级',
                    'C' => '一级分类',
                    'D' => '二级分类',
                    'E' => '单码量00/28/37/44/100/160/S',
                    'F' => '单码量29/38/46/105/165/M',
                    'G' => '单码量30/39/48/110/170/L',
                    'H' => '单码量31/40/50/115/175/XL',
                    'I' => '单码量32/41/52/120/180/2XL',
                    'J' => '单码量33/42/54/125/185/3XL',
                    'K' => '单码量34/43/56/190/4XL',
                    'L' => '单码量35/44/58/195/5XL',
                    'M' => '单码量36/6XL',
                    'N' => '单码量38/7XL',
                    'O' => '单码量_40',
                    'P' => '周转00/28/37/44/100/160/S',
                    'Q' => '周转29/38/46/105/165/M',
                    'R' => '周转30/39/48/110/170/L',
                    'S' => '周转31/40/50/115/175/XL',
                    'T' => '周转32/41/52/120/180/2XL',
                    'U' => '周转33/42/54/125/185/3XL',
                    'V' => '周转34/43/56/190/4XL',
                    'W' => '周转35/44/58/195/5XL',
                    'X' => '周转36/6XL',
                    'Y' => '周转38/7XL',
                    'Z' => '周转_40',

                ];
                // $read_column = [
                //     'A' => '原单编号',
                //     'B' => '调出店铺编号',
                //     'C' => '调入店铺编号',
                //     'D' => '货号',
                //     'E' => '尺码',
                //     'F' => '颜色编号',
                //     'G' => '数量',
                // ];

                // dump($select_customer);
                //读取数据
                $data = $this->readExcel($info, $read_column);

                // echo '<pre>';
                // print_r($data);
                foreach ($data as $key => $val) {
                    $this->db_easyA->table('cwl_chaoliang_biaozhun')->where([
                        ['风格', '=', $val['风格']],
                        ['店铺等级', '=', $val['店铺等级']],
                        ['一级分类', '=', $val['一级分类']],
                        ['二级分类', '=', $val['二级分类']],
                    ])->save([
                        '单码量00/28/37/44/100/160/S' => $val['单码量00/28/37/44/100/160/S'],
                        '单码量29/38/46/105/165/M' => $val['单码量29/38/46/105/165/M'],
                        '单码量30/39/48/110/170/L' => $val['单码量30/39/48/110/170/L'],
                        '单码量31/40/50/115/175/XL' => $val['单码量31/40/50/115/175/XL'],
                        '单码量32/41/52/120/180/2XL' => $val['单码量32/41/52/120/180/2XL'],
                        '单码量33/42/54/125/185/3XL' => $val['单码量33/42/54/125/185/3XL'],
                        '单码量34/43/56/190/4XL' => $val['单码量34/43/56/190/4XL'],
                        '单码量35/44/58/195/5XL' => $val['单码量35/44/58/195/5XL'],
                        '单码量36/6XL' => $val['单码量36/6XL'],
                        '单码量38/7XL' => $val['单码量38/7XL'],
                        '单码量_40' => $val['单码量_40'],
                        '周转00/28/37/44/100/160/S' => $val['周转00/28/37/44/100/160/S'],
                        '周转29/38/46/105/165/M' => $val['周转29/38/46/105/165/M'],
                        '周转30/39/48/110/170/L' => $val['周转30/39/48/110/170/L'],
                        '周转31/40/50/115/175/XL' => $val['周转31/40/50/115/175/XL'],
                        '周转32/41/52/120/180/2XL' => $val['周转32/41/52/120/180/2XL'],
                        '周转33/42/54/125/185/3XL' => $val['周转33/42/54/125/185/3XL'],
                        '周转34/43/56/190/4XL' => $val['周转34/43/56/190/4XL'],
                        '周转35/44/58/195/5XL' => $val['周转35/44/58/195/5XL'],
                        '周转36/6XL' => $val['周转36/6XL'],
                        '周转38/7XL' => $val['周转38/7XL'],
                        '周转_40' => $val['周转_40'],
                    ]);
                }


                return json(['code' => 0, 'msg' => '上传成功']);
            } 
        }
    }

    public function readExcel($file_path = '/', $read_column = array())
    {
        $reader = IOFactory::createReader('Xlsx');
    
        $reader->setReadDataOnly(TRUE);
    
        //载入excel表格
        $spreadsheet = $reader->load($file_path);
    
        // 读取第一個工作表
        $sheet = $spreadsheet->getSheet(0);
    
        // 取得总行数
        $highest_row = $sheet->getHighestRow();
    
        // 取得总列数
        $highest_column = $sheet->getHighestColumn();
    
        //读取内容
        $data_origin = array();
        $data = array();
        for ($row = 2; $row <= $highest_row; $row++) { //行号从2开始
            for ($column = 'A'; $column <= $highest_column; $column++) { //列数是以A列开始
                $str = $sheet->getCell($column . $row)->getValue();
                //保存该行的所有列
                $data_origin[$column] = $str;
                // if ($column == "B" || $column == "C") {
                //     if (is_numeric($data_origin[$column])) {
                //         $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                //         $data_origin[$column] = gmdate('Y/m/d',$t1);
                //     } else {
                //         $data_origin[$column] = $data_origin[$column];
                //     }
                // }
            }
            // 删除空行，好用的很
            if(!implode('', $data_origin)){
                //删除空行
                continue;
            }
            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = $data_origin[$key];
            }
        }
        return $data;
    }

    /**
     * @NodeAnotation(title="参数标准") 
     * 
     */
    public function biaozhun() {
        if (request()->isAjax()) {
            // 筛选条件
            $sql = "
                SELECT
                    *,

                    IFNULL(`单码量00/28/37/44/100/160/S`, 0) + 
                    IFNULL(`单码量29/38/46/105/165/M`, 0) + 
                    IFNULL(`单码量30/39/48/110/170/L`, 0) + 
                    IFNULL(`单码量31/40/50/115/175/XL`, 0) + 
                    IFNULL(`单码量32/41/52/120/180/2XL`, 0) +
                    IFNULL(`单码量33/42/54/125/185/3XL`, 0) + 
                    IFNULL(`单码量34/43/56/190/4XL`, 0) + 
                    IFNULL(`单码量35/44/58/195/5XL`, 0) + 
                    IFNULL(`单码量36/6XL`, 0) + 
                    IFNULL(`单码量38/7XL`, 0) + 
                    IFNULL(`单码量_40`, 0) AS 单码量合计,

                    IFNULL(`周转00/28/37/44/100/160/S`, 0) + 
                    IFNULL(`周转29/38/46/105/165/M`, 0) + 
                    IFNULL(`周转30/39/48/110/170/L`, 0) + 
                    IFNULL(`周转31/40/50/115/175/XL`, 0) + 
                    IFNULL(`周转32/41/52/120/180/2XL`, 0) +
                    IFNULL(`周转33/42/54/125/185/3XL`, 0) + 
                    IFNULL(`周转34/43/56/190/4XL`, 0) + 
                    IFNULL(`周转35/44/58/195/5XL`, 0) + 
                    IFNULL(`周转36/6XL`, 0) + 
                    IFNULL(`周转38/7XL`, 0) + 
                    IFNULL(`周转_40`, 0) AS 周转合计
                from cwl_chaoliang_biaozhun 
                where 1
            ";
            // die;

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                from cwl_chaoliang_biaozhun 
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('biaozhun', [

            ]);
        }
    }

    /**
     * @NodeAnotation(title="单店单款超量") 
     * 
     */
    public function weathertips() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            foreach ($input as $key => $val) {
                // echo $val;
                if ( $key != '在途库存') {
                    if (empty($val)) {
                        unset($input[$key]);
                    }
                }
            }

            // dump($input);die;
            if (checkAdmin()) {
                if (!empty($input['商品负责人'])) {
                    // echo $input['商品负责人'];
                    $map1Str = xmSelectInput($input['商品负责人']);
                    $map1 = " AND 商品负责人 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }
            } else {
                $admin = session('admin.name');
                $map1 = " AND 商品负责人 IN ('{$admin}')";
            }

            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['云仓']);
                $map2 = " AND 云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['省份']);
                $map3 = " AND 省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['经营模式']);
                $map4 = " AND 经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['店铺名称']);
                $map5 = " AND 店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['中类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['分类']);
                $map8 = " AND 分类 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['货号']);
                $map9 = " AND 货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['提醒备注'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['提醒备注']);
                $map11 = " AND 提醒备注 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['超量个数'])) {
                $map12 = " AND 超量个数 = {$input['超量个数']}";
            } else {
                $map12 = "";
            }

            $sql = "
                SELECT
                    left(c.云仓, 2) AS 云仓,
                    left(c.省份, 2) AS 省份,
                    c.商品负责人 AS 商品专员,
                    c.店铺名称,
                    c.店铺等级,
                    c.经营属性,
                    c.温区,
                    首单日期 as 开业日期,
                    c.day0_min, c.day1_min, c.day2_min, c.day3_min, c.day4_min, c.day5_min, c.day6_min, c.day7_min, c.day8_min, c.day9_min,
                    c.day10_min, c.day0_max, c.day1_max,  c.day2_max, c.day3_max, c.day4_max, c.day5_max, c.day6_max, c.day7_max, c.day8_max,
                    c.day9_max, c.day10_max,
                    c.day0_秋,c.day1_秋,c.day2_秋,c.day3_秋,c.day4_秋,c.day5_秋,c.day6_秋,c.day7_秋,c.day8_秋,c.day9_秋,c.day10_秋,
                    c.day0_冬,c.day1_冬,c.day2_冬,c.day3_冬,c.day4_冬,c.day5_冬,c.day6_冬,c.day7_冬,c.day8_冬,c.day9_冬,c.day10_冬,
                    c.秋季连续天数A,c.冬季连续天数A,c.秋季连续天数B,c.冬季连续天数B,c.秋季天数C,c.冬季天数C,
                    c.识别规则_秋,c.识别规则_冬,
                    c.提醒,
                    c.秋季历史最早,c.冬季历史最早,
                    c.秋季温区最早,c.冬季温区最早,
                    c.春季SKC基本_内搭,c.春季SKC基本_外套,c.秋季SKC基本_内搭,c.秋季SKC基本_外套,c.秋季SKC基本_下装,c.冬季SKC基本_内搭,c.冬季SKC基本_外套,c.冬季SKC基本_下装,
                    r_3day_nd.销售占比 as 大前天_春秋_内搭,
                    r_3day_wt.销售占比 as 大前天_春秋_外套,
                    r_3day_xz.销售占比 as 大前天_春秋_下装,
                    r_2day_nd.销售占比 as 前天_春秋_内搭,
                    r_2day_wt.销售占比 as 前天_春秋_外套,
                    r_2day_xz.销售占比 as 前天_春秋_下装,
                    r_1day_nd.销售占比 as 昨天_春秋_内搭,
                    r_1day_wt.销售占比 as 昨天_春秋_外套,
                    r_1day_xz.销售占比 as 昨天_春秋_下装,
                    r_3day_nd_dj.销售占比 as 大前天_冬_内搭,
                    r_3day_wt_dj.销售占比 as 大前天_冬_外套,
                    r_3day_xz_dj.销售占比 as 大前天_冬_下装,
                    r_2day_nd_dj.销售占比 as 前天_冬_内搭,
                    r_2day_wt_dj.销售占比 as 前天_冬_外套,
                    r_2day_xz_dj.销售占比 as 前天_冬_下装,
                    r_1day_nd_dj.销售占比 as 昨天_冬_内搭,
                    r_1day_wt_dj.销售占比 as 昨天_冬_外套,
                    r_1day_xz_dj.销售占比 as 昨天_冬_下装
                FROM
                    cwl_weathertips_customer AS c
                    left join cwl_weathertips_retail_2 as r_3day_nd ON c.省份 = r_3day_nd.省份 AND c.店铺名称 = r_3day_nd.店铺名称 AND r_3day_nd.季节修订 = '春秋季' AND r_3day_nd.日期识别='大前天' AND r_3day_nd.`一级分类`='内搭'
                    left join cwl_weathertips_retail_2 as r_3day_wt ON c.省份 = r_3day_wt.省份 AND c.店铺名称 = r_3day_wt.店铺名称 AND r_3day_wt.季节修订 = '春秋季' AND r_3day_wt.日期识别='大前天' AND r_3day_wt.`一级分类`='外套'
                    left join cwl_weathertips_retail_2 as r_3day_xz ON c.省份 = r_3day_xz.省份 AND c.店铺名称 = r_3day_xz.店铺名称 AND r_3day_xz.季节修订 = '春秋季' AND r_3day_xz.日期识别='大前天' AND r_3day_xz.`一级分类`='下装'
                    left join cwl_weathertips_retail_2 as r_2day_nd ON c.省份 = r_2day_nd.省份 AND c.店铺名称 = r_2day_nd.店铺名称 AND r_2day_nd.季节修订 = '春秋季' AND r_2day_nd.日期识别='前天' AND r_2day_nd.`一级分类`='内搭'
                    left join cwl_weathertips_retail_2 as r_2day_wt ON c.省份 = r_2day_wt.省份 AND c.店铺名称 = r_2day_wt.店铺名称 AND r_2day_wt.季节修订 = '春秋季' AND r_2day_wt.日期识别='前天' AND r_2day_wt.`一级分类`='外套'
                    left join cwl_weathertips_retail_2 as r_2day_xz ON c.省份 = r_2day_xz.省份 AND c.店铺名称 = r_2day_xz.店铺名称 AND r_2day_xz.季节修订 = '春秋季' AND r_2day_xz.日期识别='前天' AND r_2day_xz.`一级分类`='下装'
                    left join cwl_weathertips_retail_2 as r_1day_nd ON c.省份 = r_1day_nd.省份 AND c.店铺名称 = r_1day_nd.店铺名称 AND r_1day_nd.季节修订 = '春秋季' AND r_1day_nd.日期识别='昨天' AND r_1day_nd.`一级分类`='内搭'
                    left join cwl_weathertips_retail_2 as r_1day_wt ON c.省份 = r_1day_wt.省份 AND c.店铺名称 = r_1day_wt.店铺名称 AND r_1day_wt.季节修订 = '春秋季' AND r_1day_wt.日期识别='昨天' AND r_1day_wt.`一级分类`='外套'
                    left join cwl_weathertips_retail_2 as r_1day_xz ON c.省份 = r_1day_xz.省份 AND c.店铺名称 = r_1day_xz.店铺名称 AND r_1day_xz.季节修订 = '春秋季' AND r_1day_xz.日期识别='昨天' AND r_1day_xz.`一级分类`='下装'
                    
                    left join cwl_weathertips_retail_2 as r_3day_nd_dj ON c.省份 = r_3day_nd_dj.省份 AND c.店铺名称 = r_3day_nd_dj.店铺名称 AND r_3day_nd_dj.季节修订 = '冬季' AND r_3day_nd_dj.日期识别='大前天' AND r_3day_nd_dj.`一级分类`='内搭'
                    left join cwl_weathertips_retail_2 as r_3day_wt_dj ON c.省份 = r_3day_wt_dj.省份 AND c.店铺名称 = r_3day_wt_dj.店铺名称 AND r_3day_wt_dj.季节修订 = '冬季' AND r_3day_wt_dj.日期识别='大前天' AND r_3day_wt_dj.`一级分类`='外套'
                    left join cwl_weathertips_retail_2 as r_3day_xz_dj ON c.省份 = r_3day_xz_dj.省份 AND c.店铺名称 = r_3day_xz_dj.店铺名称 AND r_3day_xz_dj.季节修订 = '冬季' AND r_3day_xz_dj.日期识别='大前天' AND r_3day_xz_dj.`一级分类`='下装'
                    left join cwl_weathertips_retail_2 as r_2day_nd_dj ON c.省份 = r_2day_nd_dj.省份 AND c.店铺名称 = r_2day_nd_dj.店铺名称 AND r_2day_nd_dj.季节修订 = '冬季' AND r_2day_nd_dj.日期识别='前天' AND r_2day_nd_dj.`一级分类`='内搭'
                    left join cwl_weathertips_retail_2 as r_2day_wt_dj ON c.省份 = r_2day_wt_dj.省份 AND c.店铺名称 = r_2day_wt_dj.店铺名称 AND r_2day_wt_dj.季节修订 = '冬季' AND r_2day_wt_dj.日期识别='前天' AND r_2day_wt_dj.`一级分类`='外套'
                    left join cwl_weathertips_retail_2 as r_2day_xz_dj ON c.省份 = r_2day_xz_dj.省份 AND c.店铺名称 = r_2day_xz_dj.店铺名称 AND r_2day_xz_dj.季节修订 = '冬季' AND r_2day_xz_dj.日期识别='前天' AND r_2day_xz_dj.`一级分类`='下装'			
                    left join cwl_weathertips_retail_2 as r_1day_nd_dj ON c.省份 = r_1day_nd_dj.省份 AND c.店铺名称 = r_1day_nd_dj.店铺名称 AND r_1day_nd_dj.季节修订 = '冬季' AND r_1day_nd_dj.日期识别='昨天' AND r_1day_nd_dj.`一级分类`='内搭'
                  left join cwl_weathertips_retail_2 as r_1day_wt_dj ON c.省份 = r_1day_wt_dj.省份 AND c.店铺名称 = r_1day_wt_dj.店铺名称 AND r_1day_wt_dj.季节修订 = '冬季' AND r_1day_wt_dj.日期识别='昨天' AND r_1day_wt_dj.`一级分类`='外套'
                  left join cwl_weathertips_retail_2 as r_1day_xz_dj ON c.省份 = r_1day_xz_dj.省份 AND c.店铺名称 = r_1day_xz_dj.店铺名称 AND r_1day_xz_dj.季节修订 = '冬季' AND r_1day_xz_dj.日期识别='昨天' AND r_1day_xz_dj.`一级分类`='下装'		
                WHERE 1	

                LIMIT {$pageParams1}, {$pageParams2}  
            ";

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_weathertips_customer
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => $find_config['skauto_res_updatetime']]);
        } else {
            $sql_month = "
                SELECT
                    date_format(day0,'%m') AS day0,
                    date_format(day1,'%m') AS day1,
                    date_format(day2,'%m') AS day2,
                    date_format(day3,'%m') AS day3,
                    date_format(day4,'%m') AS day4,
                    date_format(day5,'%m') AS day5,
                    date_format(day6,'%m') AS day6,
                    date_format(day7,'%m') AS day7,
                    date_format(day8,'%m') AS day8,
                    date_format(day9,'%m') AS day9,
                    date_format(day10,'%m') AS day10
                FROM
                    cwl_weathertips_customer
                WHERE 1	
                LIMIT 1
            ";

            $sql2_day = "
                SELECT
                    date_format(day0,'%d') AS day0,
                    date_format(day1,'%d') AS day1,
                    date_format(day2,'%d') AS day2,
                    date_format(day3,'%d') AS day3,
                    date_format(day4,'%d') AS day4,
                    date_format(day5,'%d') AS day5,
                    date_format(day6,'%d') AS day6,
                    date_format(day7,'%d') AS day7,
                    date_format(day8,'%d') AS day8,
                    date_format(day9,'%d') AS day9,
                    date_format(day10,'%d') AS day10
                FROM
                    cwl_weathertips_customer
                WHERE 1	
                LIMIT 1
            ";

            $weather_date_month = $this->db_easyA->query($sql_month);
            $weather_date_day = $this->db_easyA->query($sql2_day);

            return View('weathertips', [
                'weather_date_month' => $weather_date_month[0], 
                'weather_date_day' => $weather_date_day[0] 
            ]);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_chaoliang_sk WHERE 商品负责人 IS NOT NULL GROUP BY 商品负责人
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_chaoliang_sk WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_chaoliang_sk GROUP BY 店铺名称
        ");
        $zhonglei = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_chaoliang_sk WHERE  二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $fenlei = $this->db_easyA->query("
            SELECT 分类 as name, 分类 as value FROM cwl_chaoliang_sk WHERE 分类 IS NOT NULL GROUP BY 分类
        ");
        $huohao = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_chaoliang_sk WHERE  货号 IS NOT NULL GROUP BY 货号
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer, 'zhonglei' => $zhonglei, 
        'fenlei' => $fenlei, 'huohao' => $huohao]]);
    }

    // 下载超量明细  sk
    public function excel_chaoliang() {
        if (request()->isAjax()) {
            $input = input();
            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (checkAdmin()) {
                if (!empty($input['商品负责人'])) {
                    // echo $input['商品负责人'];
                    $map1Str = xmSelectInput($input['商品负责人']);
                    $map1 = " AND 商品负责人 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }
            } else {
                $admin = session('admin.name');
                $map1 = " AND 商品负责人 IN ('{$admin}')";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['云仓']);
                $map2 = " AND 云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['省份']);
                $map3 = " AND 省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['经营模式']);
                $map4 = " AND 经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['店铺名称']);
                $map5 = " AND 店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['中类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['分类']);
                $map8 = " AND 分类 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['货号']);
                $map9 = " AND 货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['提醒备注'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['提醒备注']);
                $map11 = " AND 提醒备注 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['超量个数'])) {
                $map12 = " AND 超量个数 = {$input['超量个数']}";
            } else {
                $map12 = "";
            }


            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map9}{$map10}{$map11}{$map12}";
            $code = rand_code(6);
            cache($code, $map, 3600);

            $sql = "
                SELECT 
                    count(*) as total              
                FROM 
                    cwl_chaoliang_sk
                WHERE 1
                    {$map}            
            ";
            $count = $this->db_easyA->query($sql);
            // dump($count[0]['total']);
            // die;
            // $select = $this->db_easyA->query($sql);
            return json([
                'status' => 1,
                'code' => $code,
                'count' => $count[0]['total']
            ]);
        } else {
            $code = input('code');
            $map = cache($code);
            if (empty($map)) {
                $map = '';
            }
            $sql = "
                SELECT
                    *
                from cwl_chaoliang_sk 
                where 1
                    {$map}
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '单店单款超量提醒表_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }

    // 下载模板
    public function downloadDefault() {
        $sql = "
            SELECT
                *
            from cwl_chaoliang_biaozhun_default
            where 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '超量参数标准默认值_' . date('Ymd') . '_' . time() , 'xlsx');

    }

    // 下载模板
    public function downloadCurrent() {
        $sql = "
            SELECT
                *
            from cwl_chaoliang_biaozhun
            where 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '超量参数标准当前值_' . date('Ymd') . '_' . time() , 'xlsx');

        }
}
