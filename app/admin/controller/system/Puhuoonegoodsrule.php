<?php


namespace app\admin\controller\system;

use app\admin\model\bi\SpLypPuhuoOnegoodsRuleModel;
use app\admin\model\bi\SpLypPuhuoRuleBModel;
use app\admin\model\CustomerModel;
use app\admin\model\SjpGoodsModel;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
// use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
// use voku\helper\HtmlDomParser;
// use think\cache\driver\Redis;
// use app\admin\model\weather\Region;

/**
 * Class Puhuorulea
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="单款-铺货规则")
 */
class Puhuoonegoodsrule extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];
    const ProductMemberAuth = 7;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SpLypPuhuoOnegoodsRuleModel;
    }

    /**
     * @NodeAnotation(title="单款-铺货规则列表")
     */
    public function index() {

        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $list = $this->model->alias('por')
            ->field('por.id, por.GoodsNo, prb.Yuncang, prb.State, prb.StyleCategoryName, prb.StyleCategoryName1, prb.CategoryName1, prb.CategoryName2, prb.CustomerGrade, prb.Stock_00, prb.Stock_29
            , prb.Stock_30, prb.Stock_31, prb.Stock_32, prb.Stock_33, prb.Stock_34, prb.Stock_35, prb.Stock_36, prb.Stock_38, prb.Stock_40, prb.Stock_42, prb.total
            ')
            ->join(['sp_lyp_puhuo_rule_b' => 'prb'], 'por.rule_id=prb.id', 'inner')
            ->where(function ($query) use ($where) {
                                if (!empty($where['GoodsNo'])) $query->whereIn('por.GoodsNo', $where['GoodsNo']);
                                // if (!empty($where['rule_id'])) $query->where('por.rule_id', $where['rule_id']);
                                if (!empty($where['Yuncang'])) $query->where('prb.Yuncang', $where['Yuncang']);
                                if (!empty($where['State'])) $query->where('prb.State', $where['State']);
                                if (!empty($where['StyleCategoryName'])) $query->where('prb.StyleCategoryName', $where['StyleCategoryName']);
                                if (!empty($where['CategoryName1'])) $query->where('prb.CategoryName1', 'in', $where['CategoryName1']);
                                if (!empty($where['CategoryName2'])) $query->where('prb.CategoryName2', 'in', $where['CategoryName2']);
                                if (!empty($where['CustomerGrade'])) $query->whereIn('prb.CustomerGrade', $where['CustomerGrade']);

                                // $query->where(1);
                            })
                            ->paginate([
                                'list_rows'=> $limit,
                                'page' => $page,
                            ]);
            $list = $list ? $list->toArray() : [];
            // print_r($list);die;

            $data = [
                'code'  => 0,
                'msg'   => '',
                'today_date'   => date('m-d'),
                'count' => $list ? $list['total'] : 0,
                'data'  => $list ? $list['data'] : 0,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="获取下拉字段列")
     */
    public function getWeatherField() {

        $info_list = SpLypPuhuoRuleBModel::column('Yuncang,State,StyleCategoryName,CategoryName1,CategoryName2');
        $goods_info_list = SpLypPuhuoOnegoodsRuleModel::column('GoodsNo');
        $GoodsNo_list = $Yuncang_list = $State_list = $StyleCategoryName_list = $CategoryName1_list = $CategoryName2_list = [];
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $State_list = array_unique(array_column($info_list,'State'));
            $State_list = array_combine($State_list,$State_list);

            $StyleCategoryName_list = array_unique(array_column($info_list,'StyleCategoryName'));
            $StyleCategoryName_list = array_combine($StyleCategoryName_list,$StyleCategoryName_list);

            $CategoryName1_list = array_unique(array_column($info_list,'CategoryName1'));
            $CategoryName1_list = array_combine($CategoryName1_list,$CategoryName1_list);

            $CategoryName2_list = array_unique(array_column($info_list,'CategoryName2'));
            $CategoryName2_list = array_combine($CategoryName2_list,$CategoryName2_list);

            $GoodsNo_list = array_unique($goods_info_list);
            $GoodsNo_list = array_combine($GoodsNo_list,$GoodsNo_list);
        }
        // print_r($GoodsNo_list);die;
        $data = [
            'code'  => 1,
            'msg'   => '',
            'Yuncang_list'  => $Yuncang_list,
            'State_list'  => $State_list,
            'StyleCategoryName_list'  => $StyleCategoryName_list,
            'CategoryName1_list'  => $CategoryName1_list,
            'CategoryName2_list'  => $CategoryName2_list,
            'GoodsNo_list'  => $GoodsNo_list,
        ];
        return json($data);

    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function update() {
        // 获取店铺ID
        $id = $this->request->get('id');
        
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $this->model->where([['id', '=', $id]])->update($post);
            $this->success('更新成功');

        }

        $rulea_info = $this->model->where([['id', '=', $id]])->find();
        $rulea_info = $rulea_info ? $rulea_info->toArray() : [];
        // print_r($rulea_info);die;

        $puhuo_rule_b_list = SpLypPuhuoRuleBModel::where([])->select();
        $puhuo_rule_b_list = $puhuo_rule_b_list ? $puhuo_rule_b_list->toArray() : [];


        $this->assign([
            'rulea_info' => $rulea_info,
            'puhuo_rule_b_list' => $puhuo_rule_b_list,
        ]);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add() {

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'GoodsNo' => 'require',
                'rule_id' => 'require',
            ];
            $message = [
                'GoodsNo.require' => '货号不能为空',
                'rule_id.require' => '请选择指定铺货规则',
            ];
            $this->validate($post, $rule, $message);
            // print_r($post);die;
            $rule_b_info = SpLypPuhuoRuleBModel::where([['id', '=', $post['rule_id']]])->find();
            if (!$rule_b_info) {
                $this->error('铺货规则不存在');
            }
            $ex_goodsno = explode(' ', $post['GoodsNo']);
            foreach ($ex_goodsno as &$v_ex_goodsno) {
                $v_ex_goodsno = trim($v_ex_goodsno);
            }
            $if_exist = $this->model::where([['Yuncang', '=', $rule_b_info['Yuncang']], ['GoodsNo', 'in', $ex_goodsno]])->select();
            $if_exist = $if_exist ? $if_exist->toArray() : [];
            $if_exist_goods = $if_exist ? array_column($if_exist, 'GoodsNo') : [];
            // print_r([$rule_b_info, $if_exist_goods]);die;
            if ($if_exist_goods) {
                $this->error('该云仓存在相同货号，请不要提交重复货号：'.implode(',', $if_exist_goods));
            }

            $add_data = [];
            foreach ($ex_goodsno as $v_goods) {
                $add_data[] = [
                    'Yuncang' => $rule_b_info['Yuncang'], 
                    'GoodsNo' => $v_goods, 
                    'rule_id' => $rule_b_info['id'], 
                ];
            }

            try {
                $save = $this->model->saveAll($add_data);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $puhuo_rule_b_list = SpLypPuhuoRuleBModel::where([])->select();
        $puhuo_rule_b_list = $puhuo_rule_b_list ? $puhuo_rule_b_list->toArray() : [];
        
        $this->assign([
            'puhuo_rule_b_list' => $puhuo_rule_b_list,
        ]);

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="删除")
     */
    public function delete() {

        $this->checkPostRequest();
        $post = $this->request->post();
        $row = $this->model->whereIn('id', $post ? $post['id'] : [])->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }


}
