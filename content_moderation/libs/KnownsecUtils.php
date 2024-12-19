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
use DB;
use Knownsec\KnownsecOptions as options;
require_once libfile('function/discuzcode');

defined('DISCUZX_KNOWNSEC_PLUGIN_NAME') || define('DISCUZX_KNOWNSEC_PLUGIN_NAME', 'content_moderation');

class KnownsecUtils
{
    /**
     * 解析post请求参数，如果为空或者没有设置返回默认值$default
     * @param string $key 解析参数
     * @param mixed $default 为空或者没有设置时的默认值
     * @return mixed
     */
    public static function parsePostParam($key, $default)
    {
        return isset($_POST[$key]) && !empty($_POST[$key]) ? dhtmlspecialchars($_POST[$key]) : $default;
    }

    /**
     * 解析包含富文本的post请求参数，如果为空或者没有设置返回默认值$default
     * @param string $key 解析参数
     * @param mixed $default 为空或者没有设置时的默认值
     * @return mixed
     */
    public static function parseTextPostParam($key, $default)
    {
        return isset($_POST[$key]) && !empty($_POST[$key]) ? htmlspecialchars(discuzcode($_POST[$key])) : $default;
        // return isset($_POST[$key]) && !empty($_POST[$key]) ? htmlspecialchars(base64_decode($_POST[$key])) : $default;
    }


    /**
     * 截取字符串自定义方法，fix mb_substr undefined
     * @param string $str
     * @param int $start
     * @param int $length
     * @return string 截取后字符串
     */
    public static function substr($str, $start, $length = null)
    {
        preg_match_all("/./u", $str, $matches);  // 按照 Unicode 字符匹配
        return implode('', array_slice($matches[0], $start, $length));  // 获取前3个字符
    }

    /**
     * 获取字符串长度，fix mb_strlen undefined
     * @param string $str
     * @return int 长度
     */
    public static function strlen($str)
    {
        preg_match_all("/./u", $str, $matches);  // 按 UTF-8 字符拆分
        return count($matches[0]);  // 计算字符数
    }



    function checkbbcodes($message, $bbcodeoff)
    {
        return !$bbcodeoff && (!strpos($message, '[/') && !strpos($message, '[hr]')) ? -1 : $bbcodeoff;
    }

    /**
     * 计算请求页数
     * @param int $totalItems 总条数
     * @param int  $pageSize 每页条数
     * @return float   总页数
     */
    public static function calculateTotalPages($totalItems, $pageSize)
    {
        // 计算总页数，使用向上取整
        return ceil($totalItems / $pageSize);
    }




    /**
     * get参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */

    public function parseGetParam($key, $default = '')
    {
        return isset($_GET[$key]) ? dhtmlspecialchars($_GET[$key]) : $default;
    }

    /**
     * 文本检测
     * @param mixed $text
     * @param mixed $title
     * @param mixed $type
     * @param array $postMeta
     * @return array|string
     */
    public function examineContent($text, $title = '', $type, array $postMeta = [], $bbText = '')
    {
        $result = [ // 返回的数据
            'examine_subject' => $title,
            'examine_text' => $text,
            'needChange' => false, // 是否需要修改标题和内容
            'code' => 200, // 参数，200是成功
            'msg' => '', // 违规后返回参数
        ];
        if (empty($text)) {
            return $result;
        }
        // if (!empty($bbText)) {
        //     $postMeta['bbText'] == true; // 使用bbtext
        // }
        $Options = options::getOptionsObject();
        $result = textModeration($text, $title);

        $parsedResult = parseModerationResult($result);

        if ($postMeta['pType'] == 2) { // 回帖
            $type = 2;
        }
        global $_G;
        $data = array(
            'uid' => $_G['uid'],
            'username' => $_G['username'],
            'keyword' => $parsedResult['matchedKeyword'],
            'evil_label' => $Options->getLabelEvil($parsedResult['machineTagL1']),
            'type' => $type,
            'examine_subject' => $title,
            'examine_text' => $text,
            // 'examine_text' => !empty($bbText) ? $bbText : $text, // 使用三元运算符
            'post_meta' => json_encode($postMeta, JSON_UNESCAPED_UNICODE),
            'response' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'release_date' => time(),
        );

        $its = $Options->getInterceptType();
        $evelLabel = $Options->getEvilLabel($data['evil_label']);
        if ($evelLabel !== '正常' && in_array($evelLabel, $its)) { // 内容违规后的处理方法，违规，而且开启了对应的拦截
            $handleMethod = $Options->getHandleMethod(); // 处理方法，1.可发布(需审核)，2.禁止发布 3.违规内容替换为*号；
            if ($handleMethod == 1) {
                insertRecord($data);
                $result['msg'] = '内容涉嫌违规，请等待管理员审核。';
            } else if ($handleMethod == 2) {
                $data['status'] = 4; // 无需人审
                insertRecord($data);
                $result['msg'] = '您的内容涉嫌违规，请修改后重新发布！';
            } else if ($handleMethod == 3) {
                // 将违规内容替换为*
                $data['status'] = 4; // 无需人审
                insertRecord($data);
                $result['needChange'] = true;
                $rpc = replaceTitleAndContent($result, $text, $title);
                $result['examine_subject'] = $rpc['newTitle'];
                $result['examine_text'] = $rpc['newData'];
                $result['keywords'] = $rpc['keywords'];
            }
        } else {
            // 正常情况，无需人审
            $data['status'] = 4;
            insertRecord($data);
        }
        return $result;
    }
}

/**
 * 插入记录
 * @param mixed $data
 * @throws \Exception
 * @return mixed
 */
function insertRecord($data)
{
    $id = DB::insert("knownsec_hjws_audit_content", $data, true);
    if (!is_numeric($id)) {
        throw new \Exception(lang('plugin/content-moderation', 'insert_fail'));
    }
    return $id;
}

/**
 * 文本审核
 * @param mixed $data
 * @param mixed $title
 * @return mixed
 */
function textModeration($data, $title = '')
{
    $options = KnownsecOptions::getOptionsObject();
    $appId = $options->getAppId();
    $secretKey = $options->getSecretKey();
    $businessId = $options->getTextBussinessId();
    $contentId = genUUid();

    $url = "https://newkmsapi.qixincha.com/kms-open/v3/text/sync";
    $textData = [
        ['contentId' => $contentId, 'data' => $data],
    ];
    if (!empty($title)) {
        $textData[] = ['contentId' => genUUid(), 'data' => $title];
    }

    // 调用示例
    try {
        $result = sendTextRequest($url, $appId, $secretKey, $businessId, $textData);
        return $result;
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
}

/**
 * 调用审核请求
 * @param string $url
 * @param string $appId
 * @param string $secretKey
 * @param string $businessId
 * @param array $textData
 * @param string $extra
 * @throws \Knownsec\Exception
 * @return mixed
 */
function sendTextRequest(
    string $url,
    string $appId,
    string $secretKey,
    string $businessId,
    array $textData,
    string $extra = ''
) {
    // 初始化 cURL
    $ch = curl_init();

    // 请求头
    $headers = [
        'Accept: */*',
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json',
    ];

    // 请求数据
    $data = [
        'appId' => $appId,
        'secretKey' => $secretKey,
        'businessId' => $businessId,
        'text' => $textData,
        'extra' => $extra,
    ];

    // 配置 cURL 请求
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // 发送请求并获取响应
    $response = curl_exec($ch);

    // 错误处理
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL error occurred: $error");
    }

    // 获取 HTTP 状态码
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        throw new \Exception('API request failed: ' . curl_error($ch));
    }

    $decodedResponse = json_decode($response, true);
    // 返回响应和状态码
    return $decodedResponse;
}

/**
 * 生成UUID
 * @return string
 */
function genUUid()
{
    // 生成16字节（128位）的随机数据
    $data = random_bytes(16);

    // 设置 UUID 的版本号（第7到8位的4表示版本4）
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

    // 设置 UUID 的变种号（第9到8位的8, 9, a, 或 b，表示变种1）
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // 格式化为UUID标准字符串
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * 解析文本返回参数
 * @param mixed $apiResponse
 * @return array
 */
function parseModerationResult($apiResponse)
{
    $result = [
        'machineTagL1' => '正常', // 默认正常
        'matchedKeyword' => null,
        'error' => null,
    ];

    try {
        if (!isset($apiResponse['code']) || $apiResponse['code'] !== 200) {
            // 可以打一点报错日志
            return $result;
        }
        $moderationResults = $apiResponse['moderationResult'] ?? [];
        if (empty($moderationResults)) {
            return $result; // 如果 moderationResult 为空，返回默认值
        }
        $firstAbnormal = null; // 第一个 "不正常" 的结果
        foreach ($moderationResults as $moderation) {
            if ($moderation['machineTagL1'] !== "正常") {
                // 如果找到第一个不正常的结果
                $firstAbnormal = $moderation;
                break;
            }
        }

        // 优先返回第一个不正常的结果，否则返回第一个结果
        $targetModeration = $firstAbnormal ?? $moderationResults[0];

        // 更新结果
        $result['machineTagL1'] = $targetModeration['machineTagL1'];
        if (!empty($targetModeration['matchedList'][0]['keyword'])) {
            $result['matchedKeyword'] = $targetModeration['matchedList'][0]['keyword'];
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

/**
 * 将$data、$title的内容使用**号替换，有多长就用几个*号
 * @param mixed $apiResponse
 * @param mixed $data
 * @param mixed $title
 * @return array
 */
function replaceTitleAndContent($apiResponse, $data, $title = '')
{
    $result = [
        'newTitle' => $title, // 默认值
        'newData' => $data,   // 默认值
        'keywords' => [], // 违规关键词
    ];

    // 判断 API 响应是否正常
    if (!isset($apiResponse['code']) || $apiResponse['code'] !== 200) {
        // 可以记录日志
        return $result;
    }

    // 获取 moderationResult
    $moderationResults = $apiResponse['moderationResult'] ?? [];
    if (empty($moderationResults)) {
        return $result;
    }

    // 遍历 moderationResult 并处理 matchedList
    foreach ($moderationResults as $moderationResult) {
        if ($moderationResult['machineSuggestion'] == 1) { // 正常，无需替换
            continue;
        }

        $matchedList = $moderationResult['matchedList'] ?? [];
        foreach ($matchedList as $match) {
            $keyword = $match['keyword'] ?? '';
            if (empty($keyword)) {
                continue;
            }
            if (!in_array($keyword, $result['keywords'])) {
                $result['keywords'][] = $keyword;
            }

            // 替换标题中的关键词
            if (!empty($title)) {
                $result['newTitle'] = str_replace($keyword, str_repeat('*', mb_strlen($keyword)), $result['newTitle']);
            }

            // 替换正文中的关键词
            $result['newData'] = str_replace($keyword, str_repeat('*', mb_strlen($keyword)), $result['newData']);
        }
    }

    return $result;
}


?>