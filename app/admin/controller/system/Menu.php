<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller\system;

use app\admin\model\SystemMenu;
use app\admin\model\SystemNode;
use app\admin\service\TriggerService;
use app\common\constants\MenuConstant;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\App;
use jianyan\excel\Excel;

/**
 * Class Menu
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="菜单管理",auth=true)
 */
class Menu extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'asc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemMenu();
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
            $count = $this->model->count();
            $list = $this->model->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add($id = null)
    {
        $homeId = $this->model
            ->where([
                'pid' => MenuConstant::HOME_PID,
            ])
            ->value('id');
        if ($id == $homeId) {
            $this->error('首页不能添加子菜单');
        }
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'pid|上级菜单'   => 'require',
                'title|菜单名称' => 'require',
                'icon|菜单图标'  => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                TriggerService::updateMenu();
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $pidMenuList = $this->model->getPidMenuList();
        $this->assign('id', $id);
        $this->assign('pidMenuList', $pidMenuList);
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
            $rule = [
                'pid|上级菜单'   => 'require',
                'title|菜单名称' => 'require',
                'icon|菜单图标'  => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                TriggerService::updateMenu();
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $pidMenuList = $this->model->getPidMenuList();
        $this->assign([
            'id'          => $id,
            'pidMenuList' => $pidMenuList,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        empty($row) && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        if ($save) {
            TriggerService::updateMenu();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
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
        $row = $this->model->find($post['id']);
        if (!$row) {
            $this->error('数据不存在');
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        $homeId = $this->model
            ->where([
                'pid' => MenuConstant::HOME_PID,
            ])
            ->value('id');
        if ($post['id'] == $homeId && $post['field'] == 'status') {
            $this->error('首页状态不允许关闭');
        }
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        TriggerService::updateMenu();
        $this->success('保存成功');
    }

    /**
     * @NodeAnotation(title="添加菜单提示")
     */
    public function getMenuTips()
    {
        $node = input('get.keywords');
        $list = SystemNode::whereLike('node', "%{$node}%")
            ->field('node,title')
            ->limit(10)
            ->select();
        return json([
            'code'    => 0,
            'content' => $list,
            'type'    => 'success',
        ]);
    }

    /**
     * 展示业绩对比
     */
    public function list()
    {
        // 本期时间
        $current_time_1 = date('Y-m-d',strtotime(date('Y-m-d').'-4day'));
        $current_time_2 = date('Y-m-d 23:59:59',strtotime(date('Y-m-d').'-4day'));
        // 查询本期数据
        $current_data = $this->model->getPerformanceData($current_time_1,$current_time_2);

        // 对比时间
        $contrast_time_1 = date('Y-m-d',strtotime(date('Y-m-d').'-3day'));
        $contrast_time_2 = date('Y-m-d 23:59:59',strtotime(date('Y-m-d').'-2day'));

        // 查询对比数据
        $contrast_data = $this->model->getPerformanceData($contrast_time_1,$contrast_time_2);

        // 对比值数据
        $contrast_value_data = [];
        // 对比数据
        foreach ($contrast_data as $k => $v){

            // 本期数据
            $current_item = $current_data[$k]??[];
            if($current_item){

                // 公共数据
                $contrast_value_data[$k]['State'] = $v['State'];
                $contrast_value_data[$k]['CustomerName'] = $v['CustomerName'];
                $contrast_value_data[$k]['CustomerCode'] = $v['CustomerCode'];
                $contrast_value_data[$k]['CustomItem19'] = $v['CustomItem19'];
                $contrast_value_data[$k]['CustomItem18'] = $v['CustomItem18'];
                $contrast_value_data[$k]['StoreArea'] = $v['StoreArea'];

                // 本期数据
                $contrast_value_data[$k]['current_ranking'] = $current_item['ranking'];
                $contrast_value_data[$k]['current_num'] = $current_item['有效件量'];
                $contrast_value_data[$k]['current_singular'] = $current_item['有效单数'];
                $contrast_value_data[$k]['current_performance'] = $current_item['有效业绩'];
                $contrast_value_data[$k]['current_performance_total'] = $current_item['总业绩'];
                $contrast_value_data[$k]['current_unitprice'] = round($current_item['件单价'],2);
                $contrast_value_data[$k]['current_customerprice'] = round($current_item['客单价'],2);
                $contrast_value_data[$k]['current_joint_rate'] = round($current_item['连带率'],2);
                $contrast_value_data[$k]['current_efficiency'] = round( $current_item['人效'] * 0.01,2) * 100;;

                // 对比数据
                $contrast_value_data[$k]['contrast_ranking'] = $v['ranking'];
                $contrast_value_data[$k]['contrast_num'] = $v['有效件量'];
                $contrast_value_data[$k]['contrast_singular'] = $v['有效单数'];
                $contrast_value_data[$k]['contrast_performance'] = $v['有效业绩'];
                $contrast_value_data[$k]['contrast_performance_total'] = $v['总业绩'];
                $contrast_value_data[$k]['contrast_unitprice'] = round($v['件单价'],2);
                $contrast_value_data[$k]['contrast_customerprice'] = round($v['客单价'],2);
                $contrast_value_data[$k]['contrast_joint_rate'] = round($v['连带率'],2);
                $contrast_value_data[$k]['contrast_efficiency'] = round( $v['人效'] * 0.01,2) * 100;

                // 对比值
                $contrast_value_data[$k]['ranking'] = $current_item['ranking'] - $v['ranking'];
                $contrast_value_data[$k]['num'] = $current_item['有效件量'] - $v['有效件量'];
                $contrast_value_data[$k]['singular'] =  $current_item['有效单数'] - $v['有效单数'];
                $contrast_value_data[$k]['performance'] = $current_item['有效业绩'] - $v['有效业绩'];
                $contrast_value_data[$k]['performance_total'] = round(($current_item['总业绩'] - $v['总业绩']) / $v['总业绩'] * 100,2).'%';
                $contrast_value_data[$k]['unitprice'] = round($contrast_value_data[$k]['current_unitprice'] - $contrast_value_data[$k]['contrast_unitprice'],2);
                $contrast_value_data[$k]['customerprice'] = round($contrast_value_data[$k]['current_customerprice'] - $contrast_value_data[$k]['contrast_customerprice'],2);
                $contrast_value_data[$k]['joint_rate'] = round($contrast_value_data[$k]['current_joint_rate'] - $contrast_value_data[$k]['contrast_joint_rate'],2);
                $contrast_value_data[$k]['efficiency'] = round(($contrast_value_data[$k]['current_efficiency'] - $contrast_value_data[$k]['contrast_efficiency']) / $contrast_value_data[$k]['contrast_efficiency'] * 100 ,2).'%';
            }
//            $contrast_value_data['sum']['State'] = '';
//            $contrast_value_data['sum']['CustomerName'] = '';
//            $contrast_value_data['sum']['CustomerCode'] = '';
//            $contrast_value_data['sum']['CustomItem19'] = '';
//            $contrast_value_data['sum']['CustomItem18'] = '';
//            $contrast_value_data['sum']['StoreArea'] = round(array_sum(array_column($contrast_value_data,'StoreArea')) / count($contrast_value_data),2);
//            $contrast_value_data['sum']['current_ranking'] = '-';
//            $contrast_value_data['sum']['current_num'] = '-';
//            $contrast_value_data['sum']['current_singular'] = array_sum(array_column($contrast_value_data,'current_singular'));
//            $contrast_value_data['sum']['current_performance'] = array_sum(array_column($contrast_value_data,'current_performance'));
//            $contrast_value_data['sum']['current_performance_total'] = array_sum(array_column($contrast_value_data,'current_performance_total'));
//            $contrast_value_data['sum']['current_unitprice'] = array_sum(array_column($contrast_value_data,'current_unitprice'));
//            $contrast_value_data['sum']['current_customerprice'] = array_sum(array_column($contrast_value_data,'current_customerprice'));
//            $contrast_value_data['sum']['current_joint_rate'] = array_sum(array_column($contrast_value_data,'current_joint_rate'));
//            $contrast_value_data['sum']['current_efficiency'] = array_sum(array_column($contrast_value_data,'current_efficiency'));
//
//            $contrast_value_data['sum']['contrast_ranking'] = '-';
//            $contrast_value_data['sum']['contrast_num'] = array_sum(array_column($contrast_value_data,'contrast_num'));
//            $contrast_value_data['sum']['contrast_singular'] = array_sum(array_column($contrast_value_data,'contrast_singular'));
//            $contrast_value_data['sum']['contrast_performance'] = array_sum(array_column($contrast_value_data,'contrast_performance'));
//            $contrast_value_data['sum']['contrast_performance_total'] = array_sum(array_column($contrast_value_data,'contrast_performance_total'));
//            $contrast_value_data['sum']['contrast_unitprice'] = array_sum(array_column($contrast_value_data,'contrast_unitprice'));
//            // 平均件单价
//            $contrast_value_data['sum']['contrast_customerprice'] = array_sum(array_column($contrast_value_data,'contrast_customerprice')) / count($contrast_value_data);
//            // 平均连带率
//            $contrast_value_data['sum']['contrast_joint_rate'] = array_sum(array_column($contrast_value_data,'contrast_joint_rate'));
//            // 总人效
//            $contrast_value_data['sum']['contrast_efficiency'] = array_sum(array_column($contrast_value_data,'contrast_efficiency'));
//
//            // 对比值
//            // 排名
//            $contrast_value_data['sum']['ranking'] = '-';
//            // 有效件单量
//            $contrast_value_data['sum']['num'] = array_sum(array_column($contrast_value_data,'num'));
//            // 有效单数
//            $contrast_value_data['sum']['singular'] =  array_sum(array_column($contrast_value_data,'singular'));
//            // 有效业绩
//            $contrast_value_data['sum']['performance'] = array_sum(array_column($contrast_value_data,'performance')) / count($contrast_value_data);
//            // 平均总流水
//            $contrast_value_data['sum']['performance_total'] = array_sum(array_column($contrast_value_data,'performance_total')) / count($contrast_value_data);
//            // 平均件单价
//            $contrast_value_data['sum']['unitprice'] = array_sum(array_column($contrast_value_data,'unitprice')) / count($contrast_value_data);
//            // 平均客单价
//            $contrast_value_data['sum']['customerprice'] = array_sum(array_column($contrast_value_data,'customerprice')) / count($contrast_value_data);
//            // 平均连带率
//            $contrast_value_data['sum']['joint_rate'] = array_sum(array_column($contrast_value_data,'joint_rate')) / count($contrast_value_data);
//            // 平均人效
//            $contrast_value_data['sum']['efficiency'] = array_sum(array_column($contrast_value_data,'efficiency')) / count($contrast_value_data);
        }

         // 设置标题头
         $header = [];
         if($contrast_value_data){
              $header = [
                  ['省份' , 'State'],
                  ['店铺名称' , 'CustomerName'],
                  ['店铺代码' , 'CustomerCode'],
                  ['店铺负责人' , 'CustomItem19'],
                  ['督导负责人' , 'CustomItem18'],
                  ['面积' , 'StoreArea'],
                  ['业绩排名' , 'current_ranking'],
                  ['业绩' , 'current_performance_total'],
                  ['单数' , 'current_singular'],
                  ['连带率' , 'current_joint_rate'],
                  ['客单价' , 'current_customerprice'],
                  ['件单价' , 'current_unitprice'],
                  ['人效' , 'current_efficiency'],
                  ['业绩排名' , 'contrast_ranking'],
                  ['业绩' , 'contrast_performance_total'],
                  ['单数' , 'contrast_singular'],
                  ['连带率' , 'contrast_joint_rate'],
                  ['客单价' , 'contrast_customerprice'],
                  ['件单价' , 'contrast_unitprice'],
                  ['人效' , 'contrast_efficiency'],
                  ['排名' , 'ranking'],
                  ['流水' , 'performance_total'],
                  ['单数' , 'singular'],
                  ['连带率' , 'joint_rate'],
                  ['客单价' , 'customerprice'],
                  ['件单价' , 'unitprice'],
                  ['人效' , 'efficiency']
              ];
         }
         $fileName = '店铺业绩对比';
         return Excel::exportData($contrast_value_data, $header, $fileName, 'xlsx');
    }
}