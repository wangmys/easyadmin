<?php

namespace app\admin\service;
use app\admin\model\LoginCountModel;
use app\common\traits\Singleton;
use think\facade\Db;

class LoginService
{

    use Singleton;

    public function __construct() {
    }

    /**
     * 保存登录次数
     */
    public function save_login_count($data) {

        return LoginCountModel::create($data);

    }

    /**
     * 获取登录记录
     */
    public function get_login_count($where, $field='*') {

        return LoginCountModel::where($where)->field($field)->find();

    }

    /**
     * 登录计数
     */
    public function add_login_count($where, $update) {

        return LoginCountModel::where($where)->update($update);

    }

    /**
     * 获取登录记录列表
     */
    public function get_login_count_list($params, $field='username,name,GROUP_CONCAT(login_count) as login_count_str,GROUP_CONCAT(month) as month_str') {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $name = $params['name'] ?? '';
        $where = [];
        if ($name) {
            $where[] = ['name', 'in', $name];
        }

        $list = LoginCountModel::where($where)->field($field)->group('username')->order('create_time asc')->paginate([
            'list_rows'=> $pageLimit,
            'page' => $page,
        ]);
        $list = $list ? $list->toArray() : [];
        $all_month = [];
        if ($list && $list['data']) {
            $all_month = LoginCountModel::where($where)->field('month')->distinct(true)->select();
            $all_month = $all_month ? $all_month->toArray() : [];
            $list_data = $list['data'];
            foreach ($list_data as &$v_list_data) {
                foreach ($all_month as $v_month) $v_list_data[$v_month['month']] = '';
            }
            foreach ($list_data as &$v_data) {
                $each_login_count = $v_data['login_count_str'] ? explode(',', $v_data['login_count_str']) : [];
                $each_month = $v_data['month_str'] ? explode(',', $v_data['month_str']) : [];
                $new_arr = [];
                if ($each_login_count && $each_month) {
                    $new_arr = array_combine($each_month, $each_login_count);
                }
                if ($new_arr) {
                    foreach ($new_arr as $k_new_arr=>$v_new_arr) {
                        $v_data[$k_new_arr] = $v_new_arr;
                    }
                }
                unset($v_data['login_count_str']);
                unset($v_data['month_str']);
            }
            $list['data'] = $list_data;
        }
        $data = [
            'count' => $list ? $list['total'] : 0,
            'data'  => $list ? $list['data'] : [],
            'month_field'  => array_column($all_month, 'month'),
        ];
        return $data;

    }

    public function getXmMapSelect() {

        $name = LoginCountModel::where([])->field('name as name, name as value')->group('username')->select();
        $name = $name ? $name->toArray() : [];
        return ['name' => $name];

    }

}