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
if ($toversion === '1.0.0') {
    try {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `pre_knownsec_hjws_audit_content` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(255) NOT NULL DEFAULT '' ,
  `keyword` varchar(255) NOT NULL DEFAULT '' ,
  `evil_label` varchar(16)  NOT NULL DEFAULT '' ,
  `type` tinyint(2) unsigned NOT NULL  DEFAULT 1 ,
  `examine_subject` text NOT NULL ,
  `examine_text` text NOT NULL ,
  `response` text NOT NULL ,
  `status` tinyint(2) unsigned  NOT NULL DEFAULT 1 ,
  `release_date` bigint unsigned NOT NULL DEFAULT 0 , 
  `audit_date` bigint unsigned NOT NULL DEFAULT 0 , 
  `audit_uid` int(10) unsigned NOT NULL DEFAULT 0,
  `audit_username` varchar(255) NOT NULL DEFAULT '' ,
  `post_meta` text NOT NULL,
  `fail_reason` varchar(255) NOT NULL DEFAULT '' ,
  PRIMARY KEY (`id`),
  KEY username_idx(`username`),
  KEY status_idx(`status`),
  KEY type_idx(`type`),
  KEY date_label_idx(`audit_date`,`evil_label`)
) ENGINE=InnoDB;
SQL;
        runquery($sql);
    } catch (\Exception $exception) {
        $finish = true;
    }
}
$finish = true;