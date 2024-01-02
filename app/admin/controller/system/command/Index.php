<?php


namespace app\admin\controller\system\command;

use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use jianyan\excel\Excel;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\admin\model\command\CommandErrorTotal;
use app\admin\model\command\CommandLog;
use app\admin\model\command\CommandErrorLog;


/**
 * Class Index
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="调拨指令记录")
 */
class Index extends AdminController
{

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    protected $db_easyA = null;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->totalModel = new CommandErrorTotal;
        $this->logModel = new CommandLog;
        $this->errorLogModel = new CommandErrorLog;
        $this->db_easyA = Db::connect('mysql');
    }

    /**
     * @NodeAnotation(title="调拨指令记录")
     * ea_command_error_log
     */
    public function index()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
         // 商品负责人列表
        $manager = $this->errorLogModel->group('商品负责人')->order('id','asc')->column('商品负责人','商品负责人');
        // 获取参数
        $where = $this->request->get();

        if ($this->request->isAjax()) {

            if(empty($where['时间'])){
                $where['时间']=date('Y-m');
            }
            $time=[date('Y-m-01 00:00:00',strtotime($where['时间'])),date('Y-m-t 23:59:59',strtotime($where['时间']))];
            // 查询指令记录
            $list = $this->errorLogModel->where(function ($q)use($where){
                if(!empty($where['商品负责人'])){
                     $q->whereIn('商品负责人',$where['商品负责人']);
                }
            })->whereBetweenTime('变动时间',$time[0],$time[1])->select();
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list),
                    'data'  => $list
                ];
            return json($data);
        }
        $newManager=[];

        foreach ($manager as  $item){
            $newManager[]=['name'=>$item,'value'=>$item];
        };

        return $this->fetch('',[
            'manager' => $newManager,
            'dateY' =>date('Y-m')
        ]);
    }

    /**
     * @NodeAnotation(title="错误调拨统计")
     * 
     * 表：ea_command_error_total
     */
    public function total()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
         // 商品负责人列表
        $manager = $this->totalModel->group('商品负责人')->where([
            'year' => date('Y')
        ])->order('id','asc')->column('商品负责人','商品负责人');
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询指令记录
            $list = $this->totalModel->where(function ($q)use($filters,$manager){
                if(!empty($filters['商品负责人'])){
                     $q->whereIn('商品负责人',$filters['商品负责人']);
                }
            })->order('商品负责人,year desc,month asc')->select();

            // echo $list = $this->totalModel->where(function ($q)use($filters,$manager){
            //     if(!empty($filters['商品负责人'])){
            //          $q->whereIn('商品负责人',$filters['商品负责人']);
            //     }
            // })->order('商品负责人,year desc,month asc')->fetchSql(true)->select();
            // die;
            // 数据集重组
            $new = [];
            foreach ($list as $k => $v){
                $new[$v['商品负责人']]['商品负责人'] = $v['商品负责人'];
                $new[$v['商品负责人']][$v['date_str']] = $v['num'];
            }
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($new),
                    'data'  => $new
                ];
            return json($data);
        }
        $field = $this->totalModel->group('date_str')->column('date_str');
        return $this->fetch('',[
            'manager' => $manager,
            'field' => $field
        ]);
    }

    // 错误指令统计 结果修复 1
    public function totalResultHandle_1() {
        echo '<pre>';
        $year = date('Y');
        $month = date('m');
        if (date('Y-m-d') == date('Y-01-01')) {
            $year = date('Y', strtotime('-1 year'));
            $month = date('m', strtotime('-1 month'));
        } elseif (date('Y-m-d') == date('Y-m-01')) {
            $month = date('m', strtotime('-1 month'));
        }

        $select_商品专员 = $this->db_easyA->table('ea_command_error_log')->field('商品负责人')->where([
            'month' => $month,
            'year' => $year,
            // '商品负责人' => '曹太阳',
        ])->group('商品负责人')->select()->toArray();
        // dump($select_商品专员);

        foreach ($select_商品专员 as $key => $val) {
            $select_商品负责人_店铺名称_货号 = $this->db_easyA->table('ea_command_error_log')->field('商品负责人,店铺名称,货号,month')->where([
                'month' => $month,
                'year' => $year,
                '商品负责人' => $val['商品负责人'],
                // '店铺名称' => '平凉一店'
            ])->group('商品负责人,店铺名称,货号')->select()->toArray();

            // print_r($select_商品负责人_店铺名称_货号);

            foreach ($select_商品负责人_店铺名称_货号 as $key2 => $val2) {
                // print_r($val2);
                $sql_记录对比明细 = "
                    SELECT
                        `id`, `商品负责人`, `店铺名称`, `货号`, `变动时间`, `清空操作`, `type`,
                        right(创建人, 3) as 创建人
                    FROM
                        `ea_command_error_log` 
                    WHERE
                        `month` = '{$month}'
                        AND `year` = '{$year}'  
                        AND `商品负责人` = '{$val2['商品负责人']}' 
                        AND `店铺名称` = '{$val2['店铺名称']}' 
                        AND `货号` = '{$val2['货号']}' 
                    GROUP BY
                        `type` 
                    ORDER BY
                        `变动时间` ASC
                ";
                $select_记录对比明细 = $this->db_easyA->query($sql_记录对比明细);

                // dump($select_记录对比明细);
                $this->db_easyA->table('ea_command_error_log')->where([
                    'month' => $month,
                    'year' => $year,
                    '店铺名称' => $val2['店铺名称'],
                    '商品负责人' => $val2['商品负责人'],
                    '货号' => $val2['货号'],
                    'type' => 0
                ])->update([
                    '骚操作判定' => $select_记录对比明细[1]['创建人']
                ]);
            }
        }
    }

    // 错误指令统计 结果修复 2
    public function totalResultHandle_2() {
        $year = date('Y');
        $month = date('m');
        if (date('Y-m-d') == date('Y-01-01')) {
            $year = date('Y', strtotime('-1 year'));
            $month = date('m', strtotime('-1 month'));
        } elseif (date('Y-m-d') == date('Y-m-01')) {
            $month = date('m', strtotime('-1 month'));
        }

        $sql_统计数补丁 = "
            update `ea_command_error_total` as total
            left join (
                SELECT
                    骚操作判定,
                    year,month,
                    count(骚操作判定) as result_num
                FROM
                    ea_command_error_log 
                WHERE
                    month = '{$month}' 
                    AND year = '{$year}'
                    AND 骚操作判定 is not null
                    AND 骚操作判定 not in ('中心1', '中心2', '工作号')
                group by
                    骚操作判定
            ) as t on total.商品负责人 = t.骚操作判定
            set
                total.result_num = case
                    when total.商品负责人 = '刘琳娜' 
                    then (
                            SELECT
                                count(骚操作判定) as result_num
                            FROM
                                ea_command_error_log 
                            WHERE
                                month = '{$month}' 
                                AND year = '{$year}'
                                AND 骚操作判定 = '廖翠芳'
                    )	
                    else 
                        t.result_num
                end
            where 1
                AND total.month = '{$month}'
                AND total.year = '{$year}'
        ";
        $this->db_easyA->execute($sql_统计数补丁);
    }

    public function test() {
        $data = strtotime('2023-11-01 00:00:00');
        // 2023-11月份之前的记录
        $sql_旧月份 = "
            update `ea_command_error_total`
                set `result_num` = `num`
            where `year` = '2023'
                    and `month` < 11
        ";
        $this->db_easyA->execute($sql_旧月份);
    }

}
