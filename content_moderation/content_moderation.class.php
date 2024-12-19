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

defined('DISCUZX_KNOWNSEC_PLUGIN_NAME') || define('DISCUZX_KNOWNSEC_PLUGIN_NAME', 'content_moderation');





class plugin_content_moderation
{
    public static $pluginOptions;
    public function __construct()
    {
        global $_G;
        self::$pluginOptions = unserialize($_G['setting'][DISCUZX_KNOWNSEC_PLUGIN_NAME]);
    }

    public function common()
    {
        $fid = $_GET['fid'];
        $tid = $_GET['tid'];
        // include template('content_moderation:ajax_examine_js');
    }
}


class plugin_content_moderation_forum extends plugin_content_moderation
{

    public function post_btn_extra() // 正常发帖
    {
        global $_G;
        $formhash = $_G['formhash'];
        $fid = $_GET['fid'];
        $ftype = '正常发帖';
        include template('content_moderation:ajax_examine_js');
        return $ajax_examine_js;
    }
    public function forumdisplay_fastpost_btn_extra() // 快速发帖
    {
        global $_G;
        $formhash = $_G['formhash'];
        $fid = $_GET['fid'];
        $ftype = '快速发帖';
        include template('content_moderation:ajax_examine_js');
        return $ajax_examine_js;
    }
    public function viewthread_fastpost_btn_extra()
    {
        global $_G;
        $formhash = $_G['formhash'];
        $fid = $_GET['fid'];
        $tid = $_GET['tid'];
        $ftype = '快速回帖';
        include template('content_moderation:ajax_examine_js');
        return $ajax_examine_js;
    }
    public function post_infloat_btn_extra()
    {
        global $_G;
        $formhash = $_G['formhash'];
        $key = generateRandomString(16);
        $fid = $_GET['fid'];
        $tid = $_GET['tid'];
        $ftype = '浮窗回帖';
        include template('content_moderation:ajax_examine_js_float');
        return $ajax_examine_js;
    }

    public function forumdisplay()
    {
        $fid = $_GET['fid'];
        $tid = $_GET['tid'];
        $ftype = '不知道';
        include template('content_moderation:ajax_examine_js');
        return $ajax_examine_js;
    }
}

/**
 * 生成随机字符串
 *
 * @param integer $length
 * @return string
 */
function generateRandomString($length = 10)
{
    return bin2hex(random_bytes($length / 2));
}

?>