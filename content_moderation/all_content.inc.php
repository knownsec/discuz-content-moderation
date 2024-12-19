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

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}
defined('KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE') || define('KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE', 'knownsec_hjws_audit_content');

require_once 'libs/KnownsecUtils.php';
require_once 'libs/KnownsecOptions.php';
require_once 'knownsec_hjws_audit_content.php';
require_once libfile('function/discuzcode');
use Knownsec\KnownsecUtils;
use Knownsec\KnownsecOptions;
use Knownsec\KnownsecHjwsAuditContent as KnownsecHjwsAuditContent;














try {
    //后台的展示时间不正确，取消下方代码注释
//    date_default_timezone_set('Asia/Shanghai');
    global $_G;
    $knownsec = new KnownsecUtils();
    $knownsecOption = new KnownsecOptions();
    $tableModel = new KnownsecHjwsAuditContent();// 记录操作类


    $type = intval($knownsec->parsePostParam('auditType', 0)); // 检测模块：发帖，回帖
    $status = intval($knownsec->parsePostParam('status', 0)); // 审核状态：待审核、审核失败、审核成功、无需人审
    $currentPage = $knownsec->parsePostParam('page', 1); // 页
    $pageSize = $knownsec->parsePostParam('pageSize', 10);
    $dateStart = $knownsec->parsePostParam('dateStart', date('Y-m-d H:i:s', time() - 86400)); // 开始时间
    $dateEnd = $knownsec->parsePostParam('dateEnd', date('Y-m-d H:i:s')); // 结束时间
    $content = $knownsec->parsePostParam('content', '');
    $username = $knownsec->parsePostParam('username', '');


    $allAuditStatus = $knownsecOption->getAuditStatusList();
    $allAuditType = $knownsecOption->getAuditTypeList();
    $allAuditType = ['全部' => 0] + $allAuditType;
    $allAuditStatus = ['全部' => 0] + $allAuditStatus;

    $commonUrl = ADMINSCRIPT . '?action=' . $action . '&operation=config&do=12&identifier=content_moderation&pmod=all_content';

    if ($currentPage < 1 || $currentPage > 99999 || !is_numeric($currentPage)) {
        $currentPage = 1;
    }
    //页大小选项数组
    $pageSizeValues = array(10, 20, 50, 100);
    if (!in_array($pageSize, $pageSizeValues)) {
        $currentPage = $pageSizeValues[0];
    }


    // 请求数量
    $query = array();
    $like = array();
    if ($status != 0) {
        $query['status'] = $status;
    }
    if (!empty($type)) {
        $query['type'] = $type;
    }
    if ($dateStart !== '' && $dateEnd !== '') {
        $query['date_range'] = [strtotime($dateStart), strtotime($dateEnd)];
    }
    if (!empty($content)) {
        $like['examine_text'] = "'%" . $content . "%'";
    }
    if (!empty($username)) {
        $like['username'] = "'%" . $username . "%'";
    }

    // 查询数据
    $result = $tableModel->getAuditContents($query, $pageSize, $currentPage, $like);
    $currentPage = intval($result['current_page']);
    $count = intval($result['total_count']);
    $totalPages = $result['total_pages'];
    $contents = $result['data'];
    foreach ($contents as $key => $value) {
        $rType = $knownsecOption->getTypeLabel($value['type']);
        if ($value['type'] == $knownsecOption::AUDIT_TYPE_RELY) {
            // 初始化 $tid 的默认值
            $tid = null;
            // 如果 post_meta 是字符串，先将其转为数组
            if (isset($value['post_meta']) && is_string($value['post_meta'])) {
                $postMetaArray = json_decode($value['post_meta'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($postMetaArray['tid'])) {
                    $tid = $postMetaArray['tid']; // 获取 tid
                    $rType = '<a href="/forum.php?mod=viewthread&tid=' . $tid . '#lastpost">' . $rType . '</a>';
                }
            }

        }

        $contents[$key]['type'] = $rType;
        $contents[$key]['release_date'] = date("Y-m-d H:i:s", $value['release_date']);// 转换时间戳为时间
        $contents[$key]['audit_date'] = $value['audit_date'] !== 0 && !empty($value['audit_date']) ? date("Y-m-d H:i:s", $value['audit_date']) : '';// 转换时间戳为时间
        $contents[$key]['evil_label'] = $knownsecOption->getEvilLabel($value['evil_label']);
        $contents[$key]['examine_text'] = discuzcode($knownsec->substr($value['examine_text'], 0, 500), 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0);
        $contents[$key]['examine_text_length'] = $knownsec->strlen($value['examine_text']);
        $contents[$key]['examine_text_full'] = discuzcode($value['examine_text'], 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0);
    }

    // 边界处理
    if ($totalPages <= 1) {
        $currentPage = 1;
        $totalPages = 1;
    }

    // 当前页码前后展示的页码个数
    $range = 2;
    // 计算页码范围
    $startPage = max(1, $currentPage - $range);
    $endPage = min($totalPages, $currentPage + $range);
    $pageNumbers = range($startPage, $endPage);// 模板中不方便计算，赋值变量使用




    include template('content_moderation:all_content');

} catch (\Exception $exception) {
    cpmsg($exception->getMessage(), '', 'error');
    return;
}
