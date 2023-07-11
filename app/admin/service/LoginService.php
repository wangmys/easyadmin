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
    public function get_login_count_list($where, $field='*') {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页

        $list = LoginCountModel::where($where)->field($field)->paginate([
            'list_rows'=> $pageLimit,
            'page' => $page,
        ]);
        $list = $list ? $list->toArray() : [];
        $data = [
            'count' => $list ? $list['total'] : 0,
            'data'  => $list ? $list['data'] : 0,
        ];
        return $data;

    }

}