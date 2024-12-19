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




namespace Knownsec;

use C;

defined('DISCUZX_KNOWNSEC_PLUGIN_NAME') || define('DISCUZX_KNOWNSEC_PLUGIN_NAME', 'content_moderation');



class KnownsecOptions
{


    const ALL_AUDIT_TYPE = array(
        '发帖' => self::AUDIT_TYPE_POST,
        '回帖' => self::AUDIT_TYPE_RELY,
    );

    const ALL_AUDIT_STATUS = array(
        '无需审核' => self::STATUS_NO_NEED,
        '待审核' => self::STATUS_TODO,
        '审核成功' => self::STATUS_SUCCESS,
        '审核失败' => self::STATUS_FAILLURE,
        '审核删除' => self::STATUS_DELETED,
    );


    // 通用状态码
    const CODE_SUCCESS = 200;
    const CODE_EXCEPTION = 10000;
    const CODE_PARAM_ERROR = 400;
    const CODE_UNKOWNN_ERROR = 500;

    // 审核状态
    const STATUS_TODO = 1; // 待审核
    const STATUS_SUCCESS = 2; // 审核成功
    const STATUS_FAILLURE = 3; // 审核失败
    const STATUS_NO_NEED = 4; // 无需审核
    const STATUS_DELETED = 5;// 审核删除


    const DEFAULT_INTERCEPT_TYPE = array(
        // '正常',
        // '涉政',
        '色情/性感',
        '违法违规/暴恐',
        '广告',
        '自定义'
    );

    const ALL_HANDLE_METHODS = array(
        '可发布（需后台再审核）' => self::HANDLE_METHOD_PASS,
        '禁止发布' => self::HANDLE_METHOD_FAIL,
        // '违规内容替换为*号' => self::HANDLE_METHOD_REPLACE
    );




    // 审核类型
    const AUDIT_TYPE_POST = 1; // 发帖
    const AUDIT_TYPE_RELY = 2; // 回帖

    const DEFAULT_AUDITY_TYPE = array(self::AUDIT_TYPE_POST, self::AUDIT_TYPE_RELY);

    // 处理措施
    const HANDLE_METHOD_PASS = 1;// 发布
    const HANDLE_METHOD_FAIL = 2;// 禁止发布
    const HANDLE_METHOD_REPLACE = 3;// 替换

    const DEFAULT_METHOD_HANDLE = self::HANDLE_METHOD_PASS;


    private $appId;
    private $secretKey;
    private $textBussinessId;// 文本业务id

    private $auditType;// 审核类型

    private $interceptType;// 拦截类型

    private $handleMethod;// 处理措施



    public function __construct($appId = '', $secretKey = '', $textBussinessId = '', $auditType = self::DEFAULT_AUDITY_TYPE, $interceptType = self::DEFAULT_INTERCEPT_TYPE, $handleMethod = self::DEFAULT_METHOD_HANDLE)
    {
        $this->appId = $appId;
        $this->secretKey = $secretKey;
        $this->textBussinessId = $textBussinessId;
        $this->auditType = $auditType;
        $this->interceptType = $interceptType;
        $this->handleMethod = $handleMethod;
    }



    public static function getInterceptTypeList(): array
    {
        return self::DEFAULT_INTERCEPT_TYPE;
    }

    public static function getHandleMethodList(): array
    {
        return self::ALL_HANDLE_METHODS;
    }

    public static function getAuditStatusList(): array
    {
        return self::ALL_AUDIT_STATUS;
    }

    public static function getAuditTypeList(): array
    {
        return self::ALL_AUDIT_TYPE;
    }

    // getter and setter
    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId)
    {
        $this->appId = $appId;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function getTextBussinessId(): string
    {
        return $this->textBussinessId;
    }
    public function setTextBussinessId(string $textBussinessId)
    {
        $this->textBussinessId = $textBussinessId;
    }

    public function getAuditType(): array
    {
        return $this->auditType;
    }

    public function setAuditType(array $auditType)
    {
        $this->auditType = $auditType;
    }

    public function getInterceptType(): array
    {
        return $this->interceptType;
    }

    public function setInterceptType(array $interceptType)
    {
        $this->interceptType = $interceptType;
    }

    public function getHandleMethod(): int
    {
        return $this->handleMethod;
    }

    public function setHandleMethod(int $handleMethod)
    {
        $this->handleMethod = $handleMethod;
    }

    /**
     * 获取配置对象
     * @return KnownsecOptions
     */
    public static function getOptionsObject(): KnownsecOptions
    {
        global $_G;
        $knownsecOptions = new KnownsecOptions();
        $options = $_G['setting'][DISCUZX_KNOWNSEC_PLUGIN_NAME];
        if (empty($options)) {
            $options = C::t('common_setting')->fetch(DISCUZX_KNOWNSEC_PLUGIN_NAME);
        }
        if (empty($options)) {
            return $knownsecOptions;
        }
        $options = unserialize($options);
        $knownsecOptions->setAppid($options['appId']);
        $knownsecOptions->setSecretKey($options['secretKey']);
        $knownsecOptions->setAuditType($options['auditType']);
        $knownsecOptions->setInterceptType($options['interceptType']);
        $knownsecOptions->setTextBussinessId($options['textBussinessId']);
        $knownsecOptions->setHandleMethod($options['handleMethod']);

        return $knownsecOptions;
    }
    public function toArray()
    {
        return array(
            'secretKey' => $this->secretKey,
            'textBussinessId' => $this->textBussinessId,
            'auditType' => $this->auditType,
            'interceptType' => $this->interceptType,
            'handleMethod' => $this->handleMethod,
            'appId' => $this->appId
        );
    }

    /**
     * 标签映射
     * @param string $label
     * @return string
     */
    function getEvilLabel(string $label): string
    {
        $labelMap = [
            'Normal' => '正常',
            // 'Polity' => '涉政',
            'Porn' => '色情/性感',
            'Illegal' => '违法违规/暴恐',
            'Ad' => '广告',
            'Custom' => '自定义',
        ];

        return $labelMap[$label] ?? '正常'; // 返回默认值:正常
    }
    /**
     * 标签映射
     * @param string $label
     * @return string
     */
    function getTypeLabel(int $label): string
    {
        $labelMap = [
            '1' => '发帖',
            '2' => '回帖',
        ];

        return $labelMap[$label] ?? '发帖';
    }

    /**
     * 标签映射
     * @param string $label
     * @return string
     */
    function getLabelEvil(string $label): string
    {
        $labelMap = [
            '正常' => 'Normal',
            // '涉政' => 'Polity',
            '色情' => 'Porn',
            '性感' => 'Porn',
            '违法' => 'Illegal',
            '违法违规' => 'Illegal',
            '暴恐' => 'Illegal',
            '广告' => 'Ad',
            '自定义关键词' => 'Custom',
            '自定义' => 'Custom',
        ];

        return $labelMap[$label] ?? 'Normal'; // 返回默认值:Normal
    }
}



?>