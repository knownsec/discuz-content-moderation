<?php
/*
 * Copyright 2024 Knownsec ScanA.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
//不是ajax请求直接退出
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    exit('Access Denied');
}

require_once 'libs/KnownsecUtils.php';
require_once 'libs/KnownsecOptions.php';
use Knownsec\KnownsecUtils;
use Knownsec\KnownsecOptions;
try {

    $knownsec = new KnownsecUtils();
    $knownsecOption = new KnownsecOptions();
    $options = KnownsecOptions::getOptionsObject();
    $at = $options->getAuditType(); // 配置审核类型 1.发帖  2回帖
    if (empty($at)) { // 未开启审核配置
        return;
    }




    $fid = $_GET['fid'];
    $tid = $_GET['tid'];
    $pType = $_GET['pType']; // pType: 1发帖 2回帖
    $quote = $_GET['quote']; // 引用的值

    //快速发帖时检测帖子的标贴和内容
    global $_G;
    $postMeta = [ // 添加需要的配置信息
        'fid' => $fid,
        'tid' => $tid,
        'pType' => $pType,
        'quote' => $quote,
        'useip' => $_G['clientip'],
        'port' => $_G['remoteport'],
    ];

    // 发帖
    $result = [];
    if (
        in_array('1', $at) && $pType == 1
    ) {
        $result = $knownsec->examineContent(
            $knownsec->parsePostParam('message', ''),
            $knownsec->parsePostParam('subject', ''),
                $knownsecOption::AUDIT_TYPE_POST,
            $postMeta
        );
    }
    // 回帖
    if (
        in_array('2', $at) && $pType == 2
    ) {
        $result = $knownsec->examineContent(
            $knownsec->parsePostParam('message', ''),
            '',
                $knownsecOption::AUDIT_TYPE_RELY,
            $postMeta
        );
    }

    // 错误提示
    if (is_array($result) && $result['msg'] != '' && $result['msg'] != 'ok') {
        echo json_encode(array('code' => $options::CODE_EXCEPTION, 'msg' => $result['msg'], 'handleMethod' => $options->getHandleMethod()));
        exit();
    }
    if (is_array($result) && $result['needChange']) { // 需要替换
        echo json_encode(array(
            'code' => $options::CODE_SUCCESS,
            'msg' => $result['msg'],
            'examine_subject' => $result['examine_subject'],
            'examine_text' => $result['examine_text'],
            'keywords' => $result['keywords'],
            'handleMethod' => $options->getHandleMethod()
        ));
        exit();
    }

    echo json_encode(array('code' => $options::CODE_SUCCESS, 'msg' => '成功'));
    exit();
} catch (\Exception $exception) {
    echo json_encode(array('code' => $options::CODE_EXCEPTION, 'msg' => $exception->getMessage()));
    exit();
}
