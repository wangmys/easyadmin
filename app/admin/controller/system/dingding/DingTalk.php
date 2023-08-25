<?php
namespace app\admin\controller\system\dingding;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 钉钉APi 开发接口
 * 参考 https://developers.dingtalk.com/document/app/obtain-a-sub-department-id-list
 * Class Dingtalk
 * @package app\dingtalk
 */
class Dingtalk extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    
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

    public function clean() {
        cache('dd_access_token', null);
    }

    public function test() {
        echo $this->getAccessToken();
    }
    

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
    }

    /**
     * 获得query 参数access_token
     * @return bool|mixed|string
     */
    // public static function getAccessToken()
    // {

    //     if (empty(cache('dd_access_token'))) {
    //         $AppKey = 'dingepkj0zauvbccggha';
    //         $AppSecret = 'WDJFBx1neOcadWdg_uwjTIG2S2yw-aHtvhvVpGSjvpI9T2Etw9CiJMqNm5jFFWcD';
    //         $url = "https://oapi.dingtalk.com/gettoken?appkey=" . $AppKey . "&appsecret=" . $AppSecret;
    //         $re = file_get_contents($url);
    //         $obj = json_decode($re);
    //         $access_token = $obj->access_token;
    //         cache('dd_access_token', $access_token, 7200);
    //     } else {
    //         $access_token = cache('dd_access_token');
    //     }
    //     return $access_token;
    // }


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

    // 
    public function getDingId($phone = '') {
        $phone = $phone ? $phone : input('phone');
        // if ($phone) {
        //     $token = $this->getAccessToken();
        //     $url = "https://oapi.dingtalk.com/user/get_by_mobile?access_token=" . $token . '&mobile=' . $phone;
        //     $re = file_get_contents($url);
        //     $obj = json_decode($re);
        //     halt($obj);
    
        //     $data = [
        //         'access_token' => $token,
        //         'userid' => $obj->userid,
        //     ];
    
        //     $query = http_build_query($data);
        //     $url = "https://oapi.dingtalk.com/user/get?" . $query;
    
        //     $re = file_get_contents($url);
        //     $obj = json_decode($re);
        //     return $obj;
        // } else {
        //     return halt(false);
        // }

        $token = $this->getAccessToken();
            $url = "https://oapi.dingtalk.com/user/get_by_mobile?access_token=" . $token . '&mobile=' . $phone;
            $re = file_get_contents($url);
            $obj = json_decode($re, true);
            
            return $obj;
            // if($obj['errmsg'] == 'ok') {

            // }
    
            // $data = [
            //     'access_token' => $token,
            //     'userid' => $obj->userid,
            // ];
    
            // $query = http_build_query($data);
            // $url = "https://oapi.dingtalk.com/user/get?" . $query;
    
            // $re = file_get_contents($url);
            // $obj = json_decode($re);
            // // return halt($obj)['userid'];
            // return 11;

    }

    /**
     * 发送图片消息
     * @return bool|string
     */
    public function sendMarkdownImg($userid, $title, $path)
    {
        $time = time();
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken_cwl();
        $SendToUser_data = [
            'userid_list' => $userid,
            'agent_id' => 2476262581,
            "msg" => [
                "msgtype" => 'markdown',
                "markdown" => [
                    "title" => "{$title}",
                    "text" => "#### {$title} ![screenshot]({$path}?t={$time})\n>"
                ]

            ]
        ];
        // dump($SendToUser_data);die;
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }

    // public function sendMarkdownImg($userid, $path)
    // {
    //     $time = time();
    //     $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
    //     $SendToUser_data = [
    //         'userid_list' => $userid,
    //         'agent_id' => $this->AgentId,
    //         "msg" => [
    //             "msgtype" => 'markdown',
    //             "markdown" => [
    //                 "title" => "杭州天气1",
    //                 "text" => "#### 杭州天气 @150XXXXXXXX \n> 9度，西北风1级，空气良89，相对温度73%\n> ![screenshot](https://img.alicdn.com/tfs/TB1NwmBEL9TBuNjy1zbXXXpepXa-2400-1218.png?t={$time})\n>"
    //             ]

    //         ]
    //     ];
    //     $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
    //     return $result;
    // }

    public function testDep() {
        $res = $this->getDepartmentListIds();
        dump($res);
    }
    /**
     * 获取部门useid
     * @return mixed
     */
    public function getDepartmentListIds()
    {

        $getDepartment_config = 'https://oapi.dingtalk.com/department/list_ids?access_token=' . $this->getAccessToken() . '&id=1';
        $getDepartment = json_decode($this->GetCurlRequest($getDepartment_config), true);
        // dump($getDepartment);die;
        return $getDepartment;

    }




    /**
     * 获取部门用户信息
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    //  部门id  51388253
    public function getUserIdDetail($depId = '')
    {
        input('depId') ? $depId = input('depId') : '';
        $getDepartment = 'https://oapi.dingtalk.com/topapi/v2/user/list?access_token=' . $this->getAccessToken();

        $data = json_encode(['dept_id' => $depId, 'cursor' => 0, 'size' => 100]);
        $AllUserId[] = json_decode($this->PostCurlRequest($getDepartment, $data), JSON_OBJECT_AS_ARRAY);

        dump($AllUserId);die;

        // foreach ($AllUserId as &$all) {
        //     if (isset($all['result']['list'])) {
        //         foreach ($all['result']['list'] as &$lll) {
        //             $lll['dept_id_list'] = json_encode($lll['dept_id_list']);
        //             $lll['ruzhi_date'] = date('Y-m-d', substr($lll['hired_date'], 0, 10));
        //             if (!in_array($lll['name'], db('ding_users')->column('name'))) {
        //                 db("ding_users")->insertGetId($lll);
        //             }
        //         }
        //     }
        // }


//        $AllUserId = db("ding_users")->select();
//
//        if (!$AllUserId) {
//            return $AllUserId;
//        } else {
//            $getDepartment = 'https://oapi.dingtalk.com/topapi/v2/user/list?access_token=' . $this->getAccessToken();
//            foreach (db('ding_department')->column('bu_id') as $en) {
//                $data = json_encode(['dept_id' => $en, 'cursor' => 0, 'size' => 40]);
//                $AllUserId[] = json_decode($this->PostCurlRequest($getDepartment, $data), JSON_OBJECT_AS_ARRAY);
//            }
//
//            foreach ($AllUserId as &$all) {
//                if (isset($all['result']['list'])) {
//                    foreach ($all['result']['list'] as &$lll) {
//                        $lll['dept_id_list'] = json_encode($lll['dept_id_list']);
//                        $lll['ruzhi_date'] = date('Y-m-d', substr($lll['hired_date'], 0, 10));
//                        if (!in_array($lll['name'], db('ding_users')->column('name'))) {
//                            db("ding_users")->insertGetId($lll);
//                        }
//                    }
//                }
//
//            }
//        }

        return $AllUserId;
    }


    /**
     * 获取根部门的用户信息
     * @return mixed
     */
    public function getMainDepart()
    {
        $AllUserId = db("ding_users")->select();
        if ($AllUserId) {
            return $AllUserId;
        } else {
            $getDepartment = 'https://oapi.dingtalk.com/topapi/v2/user/list?access_token=' . $this->getAccessToken();
            $data = json_encode(['dept_id' => 1, 'cursor' => 0, 'size' => 30]);
            $AllUserId = json_decode($this->PostCurlRequest($getDepartment, $data), JSON_OBJECT_AS_ARRAY);
            foreach ($AllUserId['result']['list'] as $ma) {
                db("ding_users")->insertGetId($ma);
            }
        }
        return $AllUserId;
    }


    /***
     * 获取部门信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentListInfoMsg()
    {
        $dep_info = db('ding_department')->select();
        if ($dep_info) {
            return $dep_info;
        } else {

            $getDepartment_config = 'https://oapi.dingtalk.com/department/list?access_token=' . $this->getAccessToken() . '&id=1';
            $dep_info = json_decode($this->GetCurlRequest($getDepartment_config), true)['department'];

            foreach ($dep_info as &$ent) {
                $ent['bu_id'] = $ent['id'];
                unset($ent['id']);
                db('ding_department')->insertGetId($ent);
            }
        }


        return $dep_info;

    }

    // 获取部门信息
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
            // db('ding_department')->insertGetId($ent);
        }

        $this->db_easyA->execute('TRUNCATE dd_department_info;');
        $chunk_list = array_chunk($dep_info, 500);
        // // $this->db_easyA->startTrans();

        // // echo '<pre>';
        // // print_r($dep_info);
        // // die;
        // foreach($chunk_list as $key => $val) {
        //     // dump($val);die;
        //     $this->db_easyA->table('dd_department_info')->strict(false)->insertAll($val);
        // }

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


    /**
     * 获取所有Userid
     */
    public function getAllUserId()
    {
        // $data = $this->getDepartmentListIds()['sub_dept_id_list'];
        $data = $this->getDepartmentListIds();
        $main_dep = ['dept_id' => 51388253]; #根部门
        $getDepartment = 'https://oapi.dingtalk.com/topapi/user/listid?access_token=' . $this->getAccessToken();
        $AllUserId = json_decode($this->PostCurlRequest($getDepartment, json_encode($main_dep)), JSON_OBJECT_AS_ARRAY);

        dump($AllUserId); die;
        return $AllUserId;
    }


    /**
     * 获取链接消息的内容
     * @param $data
     * @return false|string
     */
    public function getContent($data)
    {
        $content = "你的订单" . $data['order_sn'] . " 的物流信息超过7天未更新，可能存在异常，单号：" . $data['tracking_num'];//文本内容
        $title = "订单物流异常提醒";//标题
        $picUrl = $data['url'];//图片链接
        $messageUrl = "http://www.321.design/user/#/order-management";//跳转链接
        $type = "link";
        $textString = json_encode([
            "agent_id" => $this->AgentId,
            "msg" => [
                "msgtype" => $type,
                "link" => [
                    "text" => $content,
                    "title" => $title,
                    "picUrl" => $picUrl,//图片链接
                    "messageUrl" => $messageUrl,//跳转链接
                ]
            ],
            "userid_list" => "191902624820360246",//接受用户ID
        ]);

        return $textString;
    }


    /**
     * 撤回消息通知
     * @return bool|string
     */
    public function recallMessage()
    {
        $webhook = 'https://oapi.dingtalk.com/topapi/message/corpconversation/recall?access_token=' . $this->getAccessToken();
        $SendToUser_data = json_encode(
            [
                'msg_task_id' => 351042088705,
                'agent_id' => $this->AgentId
            ]
        );
        $result = $this->PostCurlRequest($webhook, $SendToUser_data);
        return $result;

    }


    /**
     * 发送消息
     * @param string $type
     * @param array $data
     * @return bool|string
     */
    public function sendDingMessage($type = 'oa', $data = [])
    {
        switch ($type) {
            case 'text':
                $result = $this->sendTextMsg();
                break;
            case 'image':
                $result = $this->sendImageMsg();
                break;
            case 'link':
                $result = $this->sendLinkMsg($data);
                break;
            case 'voice':
                $result = $this->sendVoiceMsg();
                break;
            case 'action_card':
                $result = $this->sendActionCardMsg();
                break;
            case 'file':
                $result = $this->sendFileMsg();
                break;
            default:
                $result = $this->sendOaMsg($data);

        }
        return $result;
    }


    /**
     * 发送链接消息
     * @param $data
     * @return bool|string
     */
    public function sendLinkMsg($data)
    {
        $webhook = "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=" . $this->getAccessToken();
        $SendToUser_data = $this->getContent($data);
        $result = $this->PostCurlRequest($webhook, $SendToUser_data);
        return $result;

    }

    /**发送文本消息
     * @return bool|string
     */
    public function sendTextMsg($u_id = '', $text = '')
    {
        if (!$u_id) {
            return false;
        }
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        $SendToUser_data = [
            'userid_list' => $u_id,
            'agent_id' => $this->AgentId,
            "msg" => [
                "msgtype" => 'text',
                'text' => [
                    'content' => $text
                ]
            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }


    /**
     * 发送图片消息
     * @return bool|string
     */
    public function sendImageMsg($userid, $media_id)
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        $SendToUser_data = [
            'userid_list' => $userid,
            'agent_id' => $this->AgentId,
            "msg" => [
                "msgtype" => 'image',
                'image' => [
                    'media_id' => $media_id,
                ],

            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }

    /**
     * 发送语音
     * @return bool|string
     */
    public function sendVoiceMsg()
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        $SendToUser_data = [
            'userid_list' => '191902624820360246',
            'agent_id' => $this->AgentId,
            "msg" => [
                "msgtype" => 'voice',
                'voice' => [
                    'media_id' => '@lAjPDfYHyezlQyDOWmeJXM5HtYDJ',
                    'duration' => 20
                ]
            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }

    /**
     * 发送文件
     * @return bool|string
     */
    public function sendFileMsg()
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        $SendToUser_data = [
            'userid_list' => '350364576037719254',
            'agent_id' => $this->AgentId,
            "msg" => [
                "msgtype" => 'file',
                'file' => [
                    'media_id' => '@lAjPDgCwcKCcChTOPviv5c4jcn31',

                ]
            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }

    /**
     * 发送卡片消息
     * @return bool|string
     */
    public function sendActionCardMsg()
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        $SendToUser_data = [
            'userid_list' => '350364576037719254',
            'agent_id' => $this->AgentId,
            "msg" => [
                "msgtype" => 'action_card',
                'action_card' => [
                    'title' => '月度毛利表',
                    'markdown' => '月度毛利表',
                    'single_title' => '查看详情',
                    'single_url' => 'http://im.babiboy.com/img/20230817/S117.jpg',


                ]
            ]
        ];
        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
    }


    /**
     * 发送OA消息
     * @param $SendToUser_data
     * @return bool|string
     */
    public function sendOaMsg($userid)
    {
        $SendToUser_config = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $this->getAccessToken();
        // if (!$SendToUser_data) {
            $SendToUser_data = [
                'userid_list' => $userid,
                'agent_id' => $this->AgentId,
                "msg" => [
                    "msgtype" => 'oa',
                    'oa' => [
                        // 'message_url' => 'http://www.321.design/user/#/order-management',
                        'message_url' => '',
                        'head' => [
                            'bgcolor' => 'FFBBBBBB',
                            'text' => '头部标题'
                        ],

                        'body' => [
                            'title' => '史蒂芬孙地方',
                            'form' => [
                                [
                                    "key" => "商品：",
                                    "value" => "带帽毛毯40x50"
                                ],
                                [
                                    "key" => "件数：",
                                    "value" => "2"
                                ],
                                [
                                    "key" => "订单：",
                                    "value" => "112-1892467-4865808"
                                ],
                                [
                                    "key" => "运单：",
                                    "value" => "952677449652"
                                ],
                                [
                                    "key" => "所属：",
                                    "value" => "LB123 韩婷婷"
                                ],
                                [
                                    "key" => "物流公司：",
                                    "value" => "FedEx"
                                ],
                                [
                                    "key" => "订单处理时间：",
                                    "value" => "2021-3-5"
                                ],
                                [
                                    "key" => "最后更新时间：",
                                    "value" => "2021-3-11"
                                ], [
                                    "key" => "最后更新时间：",
                                    "value" => date('Y-m-d', time())
                                ],
                                [
                                    "key" => "备注：",
                                    "value" => "若完结，请忽略。"
                                ], [
                                    "key" => "最后更新时间：",
                                    "value" => date('Y-m-d', time())
                                ],
                                [
                                    "key" => "备注：",
                                    "value" => "若完结，请忽略。"
                                ], [
                                    "key" => "最后更新时间：",
                                    "value" => date('Y-m-d', time())
                                ],
                                [
                                    "key" => "备注：",
                                    "value" => "若完结，请忽略。"
                                ], [
                                    "key" => "最后更新时间：",
                                    "value" => date('Y-m-d', time())
                                ],
                                [
                                    "key" => "备注：",
                                    "value" => "若完结，请忽略。"
                                ],

                                [
                                    "key" => "备注：",
                                    "value" => "若完结，请忽略。"
                                ],
                                [
                                    "key" => "最后更新时间：",

                                ],


                            ],
                            'rich' => [
                                "num" => "15.6",
                                "unit" => "元"
                            ],
                            "content" => "你的订单112-1892467-4865808 的物流信息超过7天未更新，可能存在异常，单号：952677449652",
                            "image" => "@lAjPDgCwcHBV1z3OXFLSvs5Uauuv",
                            "file_count" => "0",
                            "author" => "321design系统消息"
                        ],
                    ]
                ]
            ];
        // }

        $result = $this->PostCurlRequest($SendToUser_config, json_encode($SendToUser_data));
        return $result;
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
     * 上传文件获得media_id
     * @param $filePath
     * @param $fileName
     * @return array
     */
    public function uploadDingFile($filePath, $fileName = '')
    {
        if (!$fileName) {
            $fileName = str_shuffle(md5(rand())) . '.jpg';
        }

        $url = "https://oapi.dingtalk.com/media/upload?access_token=" . $this->getAccessToken();

        //生成分隔符
        $delimiter = '-------------' . uniqid();
        //先将post的普通数据生成主体字符串
        $data = '';
        $data .= "--" . $delimiter . "\r\n";
        $data .= 'Content-Disposition: form-data; name="type"';
        $data .= "\r\n\r\n" . 'file' . "\r\n";
        $data .= "--" . $delimiter . "\r\n";
        $data .= 'Content-Disposition: form-data; name="' . 'media' . '"; filename="' . $fileName . "\" \r\n";
        $data .= 'Content-Type: ' . 'application/octet-stream' . "\r\n\r\n";
        $data .= file_get_contents($filePath) . "\r\n";
        //主体结束的分隔符
        $data .= "--" . $delimiter . "--";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            ]
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        unset($data);

        if (curl_errno($curl)) {
            return [false, '钉钉文件上传-curl失败'];
        } else {
            $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                return [false, '钉钉文件上传失败-非200'];
            }
        }
        $result = json_decode($response, true);
        if (!isset($result['errcode'])) {
            return [false, '钉钉文件上传失败-解码失败'];
        }
        if (isset($result['errcode']) && $result['errcode'] !== 0) {
            return [false, '钉钉文件上传失败-抛错信息-' . $response['errmsg']];
        }
        if (!isset($result['media_id'])) {
            return [false, '钉钉文件上传失败-media参数不存在'];
        }

        return $result['media_id'];
    }

}
