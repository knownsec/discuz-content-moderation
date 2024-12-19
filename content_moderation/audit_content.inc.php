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

require_once 'libs/KnownsecUtils.php';
require_once 'libs/KnownsecOptions.php';
require_once 'knownsec_hjws_audit_content.php';
require_once libfile('function/discuzcode');
use Knownsec\KnownsecUtils as utils;
use Knownsec\KnownsecOptions as options;
use Knownsec\KnownsecHjwsAuditContent as KnownsecHjwsAuditContent;




date_default_timezone_set('Asia/Shanghai');  // 设置为上海时区






try {




    $opts = new options();




    $path = $_SERVER['REQUEST_URI'];
    $commonUrl = ADMINSCRIPT . '?action=' . $action . '&operation=config&do=12&identifier=content_moderation&pmod=audit_content';
    $tableModel = new KnownsecHjwsAuditContent();// 记录操作类
    $allAuditStatus = options::getAuditStatusList();
    $allAuditType = options::getAuditTypeList();
    $allAuditType = ['全部' => 0] + $allAuditType;



    $currentPage = intval(utils::parsePostParam('page', 1));
    $pageSize = utils::parsePostParam('pageSize', 10);
    $auditType = utils::parsePostParam('auditType', 0);
    $status = intval(utils::parsePostParam('status', options::STATUS_TODO));
    $content = utils::parsePostParam('content', '');
    $now = time();
    $todayEnd = strtotime('tomorrow') - 1;
    $startDate = utils::parsePostParam('startDate', date("Y-m-d H:i:s", $now - 24 * 3600 * 30));// 默认一个月前
    $endDate = utils::parsePostParam('endDate', date('Y-m-d H:i:s', $todayEnd));






    // 请求数量
    $query = array(
        // 'type' => $auditType,
        'status' => $status,
    );
    $like = array();
    if (!empty($auditType)) {
        $query['type'] = $auditType;
    }
    if ($startDate !== '' && $endDate !== '') {
        $query['date_range'] = [strtotime($startDate), strtotime($endDate)];
    }
    if (!empty($content)) {
        $like['examine_text'] = "'%" . $content . "%'";
    }


    // 查询数据
    $result = $tableModel->getAuditContents($query, $pageSize, $currentPage, $like);
    $currentPage = intval($result['current_page']);
    $count = intval($result['total_count']);
    $totalPages = $result['total_pages'];
    $contents = $result['data'];
    foreach ($contents as $key => $value) {
        $rType = $opts->getTypeLabel($value['type']);
        if ($value['type'] == $opts::AUDIT_TYPE_RELY) {
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
        $contents[$key]['evil_label'] = $opts->getEvilLabel($value['evil_label']);
        $contents[$key]['examine_text'] = discuzcode(utils::substr($value['examine_text'], 0, 500), 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0);
        $contents[$key]['examine_text_length'] = utils::strlen($value['examine_text']);
        $contents[$key]['examine_text_full'] = discuzcode($value['examine_text'], 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0);
    }
    // 边界处理
    if ($totalPages <= 1) {
        $currentPage = 1;
        $totalPages = 1;
    }


    $range = 2;// 当前页码前后展示的页码个数
    // 计算页码范围
    $startPage = max(1, $currentPage - $range);
    $endPage = min($totalPages, $currentPage + $range);
    $pageNumbers = range($startPage, $endPage);// 模板中不方便计算，赋值变量使用



    include template('content_moderation:audit_content');



} catch (\Exception $exception) {
    cpmsg($exception->getMessage(), '', 'error');
    return;
}


