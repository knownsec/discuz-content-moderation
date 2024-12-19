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

require_once 'libs/KnownsecOptions.php';
require_once 'libs/KnownsecUtils.php';
require_once "knownsec_hjws_audit_content.php";
use Knownsec\KnownsecOptions as options;
use Knownsec\KnownsecUtils as utils;
use Knownsec\KnownsecHjwsAuditContent as KnownsecHjwsAuditContent;




const PASS = 'pass';
const DELETE = 'delete';

try {




    $action = utils::parsePostParam('action', 'pass');
    $postIds = utils::parsePostParam('ids', []);

    if (empty($postIds)) {
        echo json_encode(array('code' => options::CODE_PARAM_ERROR, 'msg' => '请选择需要审核的帖子'));
        exit;
    }


    $tableModel = new KnownsecHjwsAuditContent();


    // 审核通过
    // 在原始文章中添加一条数据
    if ($action === PASS) {
        $result = $tableModel->getAuditContentByIds($postIds);
        $tableModel->batchInsertForumFromAuditContent($result);
    }


    // 删除
    if ($action === DELETE) {
        global $_G;

        $auditUid = $_G['uid'];
        $auditUser = $_G['username'];
        $now = time();
        $tableModel->updateAuditContentByIds(
            $postIds,
            array(
                'status' => options::STATUS_DELETED,
                'audit_uid' => $auditUid,
                'audit_username' => $auditUser,
                'audit_date' => $now,
            )
        );
    }




    echo json_encode(array('code' => options::CODE_SUCCESS, 'msg' => ''));
    exit();
} catch (\Exception $e) {
    echo json_encode(array('code' => options::CODE_UNKOWNN_ERROR, 'msg' => $e->getMessage()));
    return;
}