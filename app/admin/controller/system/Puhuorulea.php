<?php


namespace app\admin\controller\system;

use app\admin\model\bi\SpLypPuhuoRuleAModel;
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
 * @ControllerAnnotation(title="铺货规则参数A")
 */
class Puhuorulea extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];
    const ProductMemberAuth = 7;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SpLypPuhuoRuleAModel;
    }

    /**
     * @NodeAnotation(title="铺货规则参数A列表")
     */
    public function index() {

        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $list = $this->model->where(function ($query) use ($where) {
                                if (!empty($where['Yuncang'])) $query->where('Yuncang', $where['Yuncang']);
                                if (!empty($where['State'])) $query->where('State', $where['State']);
                                if (!empty($where['StyleCategoryName'])) $query->where('StyleCategoryName', $where['StyleCategoryName']);
                                if (!empty($where['CategoryName1'])) $query->where('CategoryName1', 'in', $where['CategoryName1']);
                                if (!empty($where['CategoryName2'])) $query->where('CategoryName2', 'in', $where['CategoryName2']);
                                if (!empty($where['CustomerGrade'])) $query->whereIn('CustomerGrade', $where['CustomerGrade']);
                                $query->where(1);
                            })
                            ->paginate([
                                'list_rows'=> $limit,
                                'page' => $page,
                            ]);
            $list = $list ? $list->toArray() : [];
            // print_r($list);die;

            if ($list && $list['data']) {
                foreach ($list['data'] as &$v_data) {
                    $v_data['Stock_00'] = $v_data['Stock_00'] ?: '';
                    $v_data['Stock_29'] = $v_data['Stock_29'] ?: '';
                    $v_data['Stock_30'] = $v_data['Stock_30'] ?: '';
                    $v_data['Stock_31'] = $v_data['Stock_31'] ?: '';
                    $v_data['Stock_32'] = $v_data['Stock_32'] ?: '';
                    $v_data['Stock_33'] = $v_data['Stock_33'] ?: '';
                    $v_data['Stock_34'] = $v_data['Stock_34'] ?: '';
                    $v_data['Stock_35'] = $v_data['Stock_35'] ?: '';
                    $v_data['Stock_36'] = $v_data['Stock_36'] ?: '';
                    $v_data['Stock_38'] = $v_data['Stock_38'] ?: '';
                    $v_data['Stock_40'] = $v_data['Stock_40'] ?: '';
                    $v_data['Stock_42'] = $v_data['Stock_42'] ?: '';
                    $v_data['total'] = $v_data['total'] ?: '';
                }
            }

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

        $cus_info_list = CustomerModel::where([['ShutOut', '=', 0]])->column('CustomItem15 as Yuncang,State,CustomerGrade');
        $info_list = SpLypPuhuoWaitGoodsModel::column('StyleCategoryName,CategoryName1,CategoryName2,CategoryName');
        $Yuncang_list = $State_list = $StyleCategoryName_list = $CategoryName1_list = $CategoryName2_list = $CategoryName_list = [];
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($cus_info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $State_list = array_unique(array_column($cus_info_list,'State'));
            $State_list = array_combine($State_list,$State_list);

            $StyleCategoryName_list = array_unique(array_column($info_list,'StyleCategoryName'));
            $StyleCategoryName_list = array_combine($StyleCategoryName_list,$StyleCategoryName_list);

            $CategoryName1_list = array_unique(array_column($info_list,'CategoryName1'));
            $CategoryName1_list = array_combine($CategoryName1_list,$CategoryName1_list);

            $CategoryName2_list = array_unique(array_column($info_list,'CategoryName2'));
            $CategoryName2_list = array_combine($CategoryName2_list,$CategoryName2_list);

            $CategoryName_list = array_unique(array_column($info_list,'CategoryName'));
            $CategoryName_list = array_combine($CategoryName_list,$CategoryName_list);
        }
        $data = [
            'code'  => 1,
            'msg'   => '',
            'Yuncang_list'  => $Yuncang_list,
            'State_list'  => $State_list,
            'StyleCategoryName_list'  => $StyleCategoryName_list,
            'CategoryName1_list'  => $CategoryName1_list,
            'CategoryName2_list'  => $CategoryName2_list,
            'CategoryName_list'  => $CategoryName_list,
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
            $rule = [
                'Yuncang' => 'require',
                'Stock_00' => 'require',
                'Stock_29' => 'require',
                'Stock_30' => 'require',
                'Stock_31' => 'require',
                'Stock_32' => 'require',
                'Stock_33' => 'require',
                'Stock_34' => 'require',
                'Stock_35' => 'require',
                'Stock_36' => 'require',
                'Stock_38' => 'require',
                'Stock_40' => 'require',
                'Stock_42' => 'require',
                // 'total' => 'require',
            ];
            $message = [
                'Yuncang.require' => '云仓不能为空',
                'Stock_00.require' => '28尺码铺货数不能为空',
                'Stock_29.require' => '29尺码铺货数不能为空',
                'Stock_30.require' => '30尺码铺货数不能为空',
                'Stock_31.require' => '31尺码铺货数不能为空',
                'Stock_32.require' => '32尺码铺货数不能为空',
                'Stock_33.require' => '33尺码铺货数不能为空',
                'Stock_34.require' => '34尺码铺货数不能为空',
                'Stock_35.require' => '35尺码铺货数不能为空',
                'Stock_36.require' => '36尺码铺货数不能为空',
                'Stock_38.require' => '38尺码铺货数不能为空',
                'Stock_40.require' => '40尺码铺货数不能为空',
                'Stock_42.require' => '42尺码铺货数不能为空',
                // 'total.require' => '合计尺码铺货数不能为空',
            ];
            $this->validate($post, $rule, $message);


            $post = $this->request->post();
            $post['total'] = $post['Stock_00']+$post['Stock_29']+$post['Stock_30']+$post['Stock_31']+$post['Stock_32']+$post['Stock_33']+$post['Stock_34']+$post['Stock_35']+
            $post['Stock_36']+$post['Stock_38']+$post['Stock_40']+$post['Stock_42'];
            $this->model->where([['id', '=', $id]])->update($post);
            $this->success('更新成功');

        }

        $info_list = CustomerModel::where([['ShutOut', '=', 0]])->column('CustomItem15 as Yuncang,State,CustomerGrade');
        $goods_info_list = SpLypPuhuoWaitGoodsModel::where([])->column('StyleCategoryName,StyleCategoryName1,CategoryName1,CategoryName2,CategoryName');
        $Yuncang_list = $State_list = $StyleCategoryName_list = $StyleCategoryName1_list = $CategoryName1_list = $CategoryName2_list = $CategoryName_list = $CustomerGrade_list = [];
        
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $State_list = array_unique(array_column($info_list,'State'));
            $State_list = array_combine($State_list,$State_list);

            $StyleCategoryName_list = array_unique(array_column($goods_info_list,'StyleCategoryName'));
            $StyleCategoryName_list = array_combine($StyleCategoryName_list,$StyleCategoryName_list);

            $StyleCategoryName1_list = array_unique(array_column($goods_info_list,'StyleCategoryName1'));
            $StyleCategoryName1_list = array_combine($StyleCategoryName1_list,$StyleCategoryName1_list);

            $CategoryName1_list = array_unique(array_column($goods_info_list,'CategoryName1'));
            $CategoryName1_list = array_combine($CategoryName1_list,$CategoryName1_list);

            $CategoryName2_list = array_unique(array_column($goods_info_list,'CategoryName2'));
            $CategoryName2_list = array_combine($CategoryName2_list,$CategoryName2_list);

            $CategoryName_list = array_unique(array_column($goods_info_list,'CategoryName'));
            $CategoryName_list = array_combine($CategoryName_list,$CategoryName_list);
            $CategoryName_list['其它'] = '其它' ;

            $CustomerGrade_list = array_unique(array_column($info_list,'CustomerGrade'));
            $CustomerGrade_list = array_combine($CustomerGrade_list,$CustomerGrade_list);
        }
        $rulea_info = $this->model->where([['id', '=', $id]])->find();
        $rulea_info = $rulea_info ? $rulea_info->toArray() : [];
        // print_r($Yuncang_list);die;

        $this->assign([
            'Yuncang_list' => $Yuncang_list,
            'State_list' => $State_list,
            'StyleCategoryName_list' => $StyleCategoryName_list,
            'StyleCategoryName1_list' => $StyleCategoryName1_list,
            'CategoryName1_list' => $CategoryName1_list,
            'CategoryName2_list' => $CategoryName2_list,
            'CategoryName_list' => $CategoryName_list,
            'CustomerGrade_list' => $CustomerGrade_list,
            'rulea_info' => $rulea_info
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
                'Yuncang' => 'require',
                'Stock_00' => 'require',
                'Stock_29' => 'require',
                'Stock_30' => 'require',
                'Stock_31' => 'require',
                'Stock_32' => 'require',
                'Stock_33' => 'require',
                'Stock_34' => 'require',
                'Stock_35' => 'require',
                'Stock_36' => 'require',
                'Stock_38' => 'require',
                'Stock_40' => 'require',
                'Stock_42' => 'require',
                // 'total' => 'require',
            ];
            $message = [
                'Yuncang.require' => '云仓不能为空',
                'Stock_00.require' => '28尺码铺货数不能为空',
                'Stock_29.require' => '29尺码铺货数不能为空',
                'Stock_30.require' => '30尺码铺货数不能为空',
                'Stock_31.require' => '31尺码铺货数不能为空',
                'Stock_32.require' => '32尺码铺货数不能为空',
                'Stock_33.require' => '33尺码铺货数不能为空',
                'Stock_34.require' => '34尺码铺货数不能为空',
                'Stock_35.require' => '35尺码铺货数不能为空',
                'Stock_36.require' => '36尺码铺货数不能为空',
                'Stock_38.require' => '38尺码铺货数不能为空',
                'Stock_40.require' => '40尺码铺货数不能为空',
                'Stock_42.require' => '42尺码铺货数不能为空',
                // 'total.require' => '合计尺码铺货数不能为空',
            ];
            $this->validate($post, $rule, $message);
            $post['total'] = $post['Stock_00']+$post['Stock_29']+$post['Stock_30']+$post['Stock_31']+$post['Stock_32']+$post['Stock_33']+$post['Stock_34']+$post['Stock_35']+
            $post['Stock_36']+$post['Stock_38']+$post['Stock_40']+$post['Stock_42'];

            // echo json_encode([$post['Yuncang']]);die; 
            if ($post['State'] == '全部') {

                $cus_info_list = CustomerModel::where([['ShutOut', '=', 0], ['CustomItem15', '=', $post['Yuncang']]])->column('CustomItem15 as Yuncang,State,CustomerGrade');
                $State_list = array_unique(array_column($cus_info_list,'State'));
                if ($State_list) {

                    $add_data = [];
                    $post_new = $post;
                    unset($post_new['State']);
                    foreach ($State_list as $v_state) {
                        $post_new['State'] = $v_state;
                        $add_data[] = $post_new;
                    }
                    $save = $this->model->saveAll($add_data);
                    $save ? $this->success('保存成功') : $this->error('保存失败');

                } else {
                    $this->error('没找到省份');
                }

            } else {

                try {
                    $save = $this->model->save($post);
                } catch (\Exception $e) {
                    $this->error('保存失败');
                }
                $save ? $this->success('保存成功') : $this->error('保存失败');

            }
        }

        $info_list = CustomerModel::where([['ShutOut', '=', 0]])->column('CustomItem15 as Yuncang,State,CustomerGrade');
        $goods_info_list = SpLypPuhuoWaitGoodsModel::where([])->column('StyleCategoryName,StyleCategoryName1,CategoryName1,CategoryName2,CategoryName');
        $Yuncang_list = $State_list = $StyleCategoryName_list = $StyleCategoryName1_list = $CategoryName1_list = $CategoryName2_list = $CategoryName_list = $CustomerGrade_list = [];
        
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $State_list = array_unique(array_column($info_list,'State'));
            $State_list = array_combine($State_list,$State_list);

            $StyleCategoryName_list = array_unique(array_column($goods_info_list,'StyleCategoryName'));
            $StyleCategoryName_list = array_combine($StyleCategoryName_list,$StyleCategoryName_list);

            $StyleCategoryName1_list = array_unique(array_column($goods_info_list,'StyleCategoryName1'));
            $StyleCategoryName1_list = array_combine($StyleCategoryName1_list,$StyleCategoryName1_list);

            $CategoryName1_list = array_unique(array_column($goods_info_list,'CategoryName1'));
            $CategoryName1_list = array_combine($CategoryName1_list,$CategoryName1_list);

            $CategoryName2_list = array_unique(array_column($goods_info_list,'CategoryName2'));
            $CategoryName2_list = array_combine($CategoryName2_list,$CategoryName2_list);

            $CategoryName_list = array_unique(array_column($goods_info_list,'CategoryName'));
            $CategoryName_list = array_combine($CategoryName_list,$CategoryName_list);
            $CategoryName_list['其它'] = '其它' ;

            $CustomerGrade_list = array_unique(array_column($info_list,'CustomerGrade'));
            $CustomerGrade_list = array_combine($CustomerGrade_list,$CustomerGrade_list);
        }
        // print_r($Yuncang_list);die;
        
        $this->assign([
            'Yuncang_list' => $Yuncang_list,
            'State_list' => $State_list,
            'StyleCategoryName_list' => $StyleCategoryName_list,
            'StyleCategoryName1_list' => $StyleCategoryName1_list,
            'CategoryName1_list' => $CategoryName1_list,
            'CategoryName2_list' => $CategoryName2_list,
            'CategoryName_list' => $CategoryName_list,
            'CustomerGrade_list' => $CustomerGrade_list,
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

    /**
     * 获取云仓对应省份
     */
    public function get_province_by_yuncang() {

        $post = $this->request->post();
        $cus_info_list = CustomerModel::where([['ShutOut', '=', 0], ['CustomItem15', '=', $post['Yuncang']]])->column('CustomItem15 as Yuncang,State,CustomerGrade');
        $State_list = array_unique(array_column($cus_info_list,'State'));
        $State_list = array_combine($State_list,$State_list);
        return json(['data' => $State_list]);

    }


}
