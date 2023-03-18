<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Dingtalk\Vcontact_1_0\Models;

use AlibabaCloud\Tea\Model;

class SortUserResponseBody extends Model
{
    /**
     * @description userIdList
     *
     * @var string[]
     */
    public $userIdList;
    protected $_name = [
        'userIdList' => 'userIdList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->userIdList) {
            $res['userIdList'] = $this->userIdList;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SortUserResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['userIdList'])) {
            if (!empty($map['userIdList'])) {
                $model->userIdList = $map['userIdList'];
            }
        }

        return $model;
    }
}
