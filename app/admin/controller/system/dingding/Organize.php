<?php
namespace app\admin\controller\system\dingding;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 公司人员组织
 * @package app\Organize
 */
class Organize extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
   
    /**
     * @var string
     */
    protected $AgentId;
    protected $AgentId_cwl;


    /**
     * @var string
     */
    protected $AppKey;
    protected $AppKey_cwl;


    /**
     * @var string
     */
    protected $AppSecret;
    protected $AppSecret_cwl;

    /**
     * @var string
     */
    protected $CorpId;


    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->AgentId_cwl = '2476262581';
        $this->AppKey_cwl = 'dingepkj0zauvbccggha';
        $this->AppSecret_cwl = 'WDJFBx1neOcadWdg_uwjTIG2S2yw-aHtvhvVpGSjvpI9T2Etw9CiJMqNm5jFFWcD';

        $this->AgentId = '2476262581';
        // cc
        $this->AppKey = 'dinga7devai5kbxij8zr';
        $this->AppSecret = 'JnQ_2VRvr5BFKlBiTnGf3mnyiNCb3pafnkg5FmB_SkNzyNBPtXCE1vLdHjpNwc1A';
        $this->CorpId = 'ding113b83e00f1ca31435c2f4657eb6378f';

        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    /**
     * 获得query 参数access_token
     * @return bool|mixed|string
     */
    protected function getAccessToken()
    {
        $gettoken_config = 'https://oapi.dingtalk.com/gettoken' . '?corpid=' . $this->AppKey . '&corpsecret=' . $this->AppSecret;
        $access_token = $this->GetCurlRequest($gettoken_config);
        $access_token = json_decode($access_token, true);
        $access_token = $access_token['access_token'];
        // dump($access_token);
        return $access_token;
    }

    protected function getAccessToken_cwl()
    {
        $gettoken_config = 'https://oapi.dingtalk.com/gettoken' . '?corpid=' . $this->AppKey_cwl . '&corpsecret=' . $this->AppSecret_cwl;
        $access_token = $this->GetCurlRequest($gettoken_config);
        $access_token = json_decode($access_token, true);
        $access_token = $access_token['access_token'];
        // dump($access_token);
        return $access_token;
    }


    // 获取部门信息  有用
    // http://www.easyadmin1.com/admin/system.dingding.Organize/getDepartmentInfo
    public function getDepartmentInfo()
    {
        $getDepartment_config = 'https://oapi.dingtalk.com/department/list?access_token=' . $this->getAccessToken() . '&id=1';
        $dep_info = json_decode($this->GetCurlRequest($getDepartment_config), true)['department'];

        foreach ($dep_info as &$val) {
            $val['depId'] = $val['id'];
            $val['parentId'] = $val['parentid'];
            unset($val['ext']);
            unset($val['id']);
            unset($val['parentid']);
        }

        $this->db_easyA->execute('TRUNCATE dd_department_info;');
        $chunk_list = array_chunk($dep_info, 500);

        $this->db_easyA->table('dd_department_info')->strict(false)->insertAll($dep_info);

        $sql_是否店铺 = "
            UPDATE dd_department_info AS a
            LEFT JOIN (
                SELECT
                    dep.* 
                FROM
                    dd_department_info AS dep
                    LEFT JOIN customer_pro AS c ON dep.name = c.customerName 
                WHERE
                    dep.name = c.customerName 
                ) AS t ON a.name = t.name 
                SET a.isCustomer = '是' 
            WHERE
                a.name = t.name        
        ";
        $this->db_easyA->execute($sql_是否店铺);

        return json([
            'status' => 0,
            'msg' => 'error',
            'content' => "dd_department_info  更新成功！"
        ]);
    }

    // 获取dd架构所有用户
    // http://www.easyadmin1.com/admin/system.dingding.Organize/updateUser
    public function updateUser() {
        $this->db_easyA->execute('TRUNCATE dd_user;');
        $select_deparent = $this->db_easyA->table('dd_department_info')->where(
            1
            // [
            // ['use', '=', 1],
            // ['isCustomer', '=', 1],
            // ]
            // [
            //     'name' => '崇左一店'
            // ]
        )->select();

        if ($select_deparent ) {
            $this->db_easyA->execute('TRUNCATE dd_user;');
            // dump($select_deparent); die;
            foreach($select_deparent as $key => $val) {
                // echo $val['depId'];
                // echo '<br>';
                $this->ddUser($val['depId'], $val['name']);
            }
        }

        $sql_更新user是否店铺 = "
            update
                dd_user AS u
            LEFT JOIN 
                `dd_department_info` AS d ON d.depId = u.depId
            set 
                u.isCustomer = '是'
            WHERE 
                d.isCustomer = '是'
                AND u.店铺名称 != u.name
        ";
        $this->db_easyA->execute($sql_更新user是否店铺);

    }

    public function ddUser($depId = '', $店铺名称 = '')
    {
        input('depId') ? $depId = input('depId') : '';

        $find_deparent = $this->db_easyA->table('dd_department_info')->where([
            ['depId', '=', $depId]
        ])->find();
        $getDepartment = 'https://oapi.dingtalk.com/topapi/v2/user/list?access_token=' . $this->getAccessToken();

        $data = json_encode(['dept_id' => $depId, 'cursor' => 0, 'size' => 100]);
        $AllUserId[] = json_decode($this->PostCurlRequest($getDepartment, $data), JSON_OBJECT_AS_ARRAY);

        // dump($AllUserId[0]['result']['list']);die;

        $new_data = [];

        try {
            foreach ($AllUserId[0]['result']['list'] as $key => $val) {
                $new_data[$key]['店铺名称'] = $店铺名称;
                $new_data[$key]['depId'] = $depId;
                $new_data[$key]['name'] = @$val['name'];
                $new_data[$key]['title'] = @$val['title'];
                $new_data[$key]['mobile'] = @$val['mobile'];
                $new_data[$key]['userid'] = @$val['userid'];
            }
            // dump($new_data);
            $this->db_easyA->table('dd_user')->strict(false)->insertAll($new_data);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    // 更新店铺推送信息
    public function updateCustomerPush() {
        $sql = "
            select 
                *,
                date_format(now(),'%Y-%m-%d') as 更新日期 
            from dd_user where isCustomer='是' and title in ('店长', '负责人', '实习店长', '优秀店长')
        ";
        $select = $this->db_easyA->query($sql);

        foreach ($select as $key => $val) {
            $pattern = "/{$val['店铺名称']}/i";
            if (preg_match($pattern, $val['name'])) {
                unset($select[$key]);
                // dump($val);
            }
        }
        
        if ($select) {
            $this->db_easyA->execute('TRUNCATE dd_customer_push;');
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('dd_customer_push')->strict(false)->insertAll($val);
            }

            $补丁 = $this->db_easyA->table('dd_customer_push_buding')->select()->toArray();
            // dump($补丁);die;
            $this->db_easyA->table('dd_customer_push')->strict(false)->insertAll($补丁);
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "店铺推送表：dd_customer_push  更新成功！"
            ]);
        }
    }

    // 
    // public function addcwl() {
    //     $this->db_easyA->table('dd_customer_push')->strict(false)->insertAll([
    //         [
    //             '店铺名称' => '汉川一店',
    //             'depId' => '380875357',
    //             'name' => '陈威良',
    //             'title' => '店长',
    //             'mobile' => '13066166636',
    //             'userid' => '350364576037719254',
    //             'isCustomer' => '是',
    //         ],
    //         [
    //             '店铺名称' => '宜春二店',
    //             'depId' => '380875357',
    //             'name' => '王威',
    //             'title' => '店长',
    //             'mobile' => '15880012590',
    //             'userid' => '0812473564939990',
    //             'isCustomer' => '是',
    //         ],
    //     ]);
    // }

    public function test() {
        $str = '田2珊';
        $str2 = ' 11田珊珊的工作号';
        $pattern = "/{$str}/i";
        echo preg_match($pattern, $str2);
    }

    /**
     * post 请求
     * @param $remote_server
     * @param $post_string
     * @return bool|string
     */
    protected function PostCurlRequest($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    } 

    /**
     * get 请求
     * @param $url
     * @return bool|string
     */
    protected function GetCurlRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//关闭ssl验证    
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}
