<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Dingtalk\Vedu_1_0\Models\GetRemoteClassCourseResponseBody;

use AlibabaCloud\SDK\Dingtalk\Vedu_1_0\Models\GetRemoteClassCourseResponseBody\result\attendParticipants;
use AlibabaCloud\SDK\Dingtalk\Vedu_1_0\Models\GetRemoteClassCourseResponseBody\result\recordInfos;
use AlibabaCloud\SDK\Dingtalk\Vedu_1_0\Models\GetRemoteClassCourseResponseBody\result\teachingParticipant;
use AlibabaCloud\Tea\Model;

class result extends Model
{
    /**
     * @description 听课设备列表
     *
     * @var attendParticipants[]
     */
    public $attendParticipants;

    /**
     * @description 课程是否可以编辑或删除
     *
     * @var bool
     */
    public $canEdit;

    /**
     * @description 课程code
     *
     * @var string
     */
    public $courseCode;

    /**
     * @description 课程名称
     *
     * @var string
     */
    public $courseName;

    /**
     * @description 结束时间
     *
     * @var int
     */
    public $endTime;

    /**
     * @description 直播观看URL（如果有）
     *
     * @var string
     */
    public $liveUrl;

    /**
     * @description 录制信息列表（如果有）。根据录制端的不同，有不同时长的延迟
     *
     * @var recordInfos[]
     */
    public $recordInfos;

    /**
     * @description 课堂当前状态：0: 未进行；1: 进行中
     *
     * @var int
     */
    public $roomStatus;

    /**
     * @description 开始时间
     *
     * @var int
     */
    public $startTime;

    /**
     * @description 课程状态：0: 未开始；1: 已开始；2: 已结束
     *
     * @var int
     */
    public $status;

    /**
     * @description 授课设备
     *
     * @var teachingParticipant
     */
    public $teachingParticipant;
    protected $_name = [
        'attendParticipants'  => 'attendParticipants',
        'canEdit'             => 'canEdit',
        'courseCode'          => 'courseCode',
        'courseName'          => 'courseName',
        'endTime'             => 'endTime',
        'liveUrl'             => 'liveUrl',
        'recordInfos'         => 'recordInfos',
        'roomStatus'          => 'roomStatus',
        'startTime'           => 'startTime',
        'status'              => 'status',
        'teachingParticipant' => 'teachingParticipant',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->attendParticipants) {
            $res['attendParticipants'] = [];
            if (null !== $this->attendParticipants && \is_array($this->attendParticipants)) {
                $n = 0;
                foreach ($this->attendParticipants as $item) {
                    $res['attendParticipants'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->canEdit) {
            $res['canEdit'] = $this->canEdit;
        }
        if (null !== $this->courseCode) {
            $res['courseCode'] = $this->courseCode;
        }
        if (null !== $this->courseName) {
            $res['courseName'] = $this->courseName;
        }
        if (null !== $this->endTime) {
            $res['endTime'] = $this->endTime;
        }
        if (null !== $this->liveUrl) {
            $res['liveUrl'] = $this->liveUrl;
        }
        if (null !== $this->recordInfos) {
            $res['recordInfos'] = [];
            if (null !== $this->recordInfos && \is_array($this->recordInfos)) {
                $n = 0;
                foreach ($this->recordInfos as $item) {
                    $res['recordInfos'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->roomStatus) {
            $res['roomStatus'] = $this->roomStatus;
        }
        if (null !== $this->startTime) {
            $res['startTime'] = $this->startTime;
        }
        if (null !== $this->status) {
            $res['status'] = $this->status;
        }
        if (null !== $this->teachingParticipant) {
            $res['teachingParticipant'] = null !== $this->teachingParticipant ? $this->teachingParticipant->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return result
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['attendParticipants'])) {
            if (!empty($map['attendParticipants'])) {
                $model->attendParticipants = [];
                $n                         = 0;
                foreach ($map['attendParticipants'] as $item) {
                    $model->attendParticipants[$n++] = null !== $item ? attendParticipants::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['canEdit'])) {
            $model->canEdit = $map['canEdit'];
        }
        if (isset($map['courseCode'])) {
            $model->courseCode = $map['courseCode'];
        }
        if (isset($map['courseName'])) {
            $model->courseName = $map['courseName'];
        }
        if (isset($map['endTime'])) {
            $model->endTime = $map['endTime'];
        }
        if (isset($map['liveUrl'])) {
            $model->liveUrl = $map['liveUrl'];
        }
        if (isset($map['recordInfos'])) {
            if (!empty($map['recordInfos'])) {
                $model->recordInfos = [];
                $n                  = 0;
                foreach ($map['recordInfos'] as $item) {
                    $model->recordInfos[$n++] = null !== $item ? recordInfos::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['roomStatus'])) {
            $model->roomStatus = $map['roomStatus'];
        }
        if (isset($map['startTime'])) {
            $model->startTime = $map['startTime'];
        }
        if (isset($map['status'])) {
            $model->status = $map['status'];
        }
        if (isset($map['teachingParticipant'])) {
            $model->teachingParticipant = teachingParticipant::fromMap($map['teachingParticipant']);
        }

        return $model;
    }
}
