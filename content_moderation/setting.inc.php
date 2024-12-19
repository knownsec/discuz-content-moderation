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


include "libs/KnownsecOptions.php";
include "libs/KnownsecUtils.php";

use Knownsec\KnownsecOptions;
use Knownsec\KnownsecUtils as utils;

try {
    //不是ajax请求直接返回html页面
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $options = KnownsecOptions::getOptionsObject();
        $appId = $options->getAppId();
        $secretKey = $options->getSecretKey();
        $auditType = $options->getAuditType();
        $interceptType = $options->getInterceptType();
        $textBussinessId = $options->getTextBussinessId();
        $handleMethod = $options->getHandleMethod();
        $allInterceptTypeList = KnownsecOptions::getInterceptTypeList();
        $allHandleMethod = KnownsecOptions::getHandleMethodList();

        $interceptTypeList = lang('plugin/content_moderation', 'evil_label_desc');
        include template('content_moderation:setting');
        exit;
    }

    // $at = utils::parsePostParam('auditType', '');
    // showmessage('set at === '.print_r($at, true));
    // 请求设置
    $options = KnownsecOptions::getOptionsObject();
    error_log('_POST========' . json_encode($_POST));
    $options->setAppId(utils::parsePostParam('appId', ''));
    $options->setSecretKey(utils::parsePostParam('secretKey', ''));
    $options->setAuditType(utils::parsePostParam('auditType', ''));
    $options->setInterceptType(utils::parsePostParam('interceptType', ''));
    $options->setTextBussinessId(utils::parsePostParam('textBussinessId', ''));
    $options->setHandleMethod(utils::parsePostParam('handleMethod', ''));
    error_log('options========' . json_encode($options->toArray()));



    C::t('common_setting')->update_batch(array("content_moderation" => $options->toArray()));
    updatecache('setting');


    $url = 'action=plugins&operation=config&do=' . $pluginid . '&identifier=content_moderation&pmod=setting';
    cpmsg('plugins_edit_succeed', $url, 'succeed');
} catch (\Exception $exception) {
    cpmsg($exception->getMessage(), '', 'error');
}

?>