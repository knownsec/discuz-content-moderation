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

/*
数据库操作的curd
*/
namespace Knownsec;

use DB;


if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

defined('KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE') || define('KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE', 'knownsec_hjws_audit_content');

require_once('libs/KnownsecOptions.php');

use Knownsec\KnownsecOptions;

class KnownsecHjwsAuditContent
{

    /**
     *  新插入一条内容
     * @param mixed $keyword
     * @param mixed $evil_label 标签枚举
     * @param string $examine_text 审核内容
     * @param string $examine_subject 审核标题
     * @param int $type 审核类型默认发帖
     * @param int $status 审核状态
     * @param int $release_date 发布时间戳
     * @return void
     */
    function addAuditContent($keyword = '', $evil_label = '', $examine_text = '', $examine_subject = '', $type = KnownsecOptions::AUDIT_TYPE_POST, $status = self::AUDIT_STATUS_NO, $relase_date)
    {
        global $_G;
        $data = [
            'uid' => $_G['uid'],
            'username' => $_G['username'],
            'keyword' => $keyword,
            'evil_label' => $evil_label,
            'type' => $type,
            'examine_text' => $examine_text,
            'status' => intval($status),
            'audit_date' => intval($relase_date),
        ];

        DB::insert(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE, $data);
    }



    /**
     * 根据主键id查询数据
     * @param array $ids 主键id数组
     * @return array
     */
    function getAuditContentByIds($ids)
    {

        if (count($ids) == 1) {
            return DB::fetch_all("SELECT * FROM " . DB::table(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE) . " WHERE id=$ids[0]");
        }

        $ids = implode(",", $ids);
        return DB::fetch_all("SELECT * FROM " . DB::table(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE) . " WHERE id IN ($ids)");

    }


    /**
     * 查询多条数据
     * @param array $conditions 查询条件map  {uid=>1}
     * @param int $limit 分页页大小
     * @param int $page  页码
     * @param array $likes 模糊查询条件map  {text=>1} like %text%
     * @return array
     */
    function getAuditContents($conditions = [], $limit = 10, $page = 1, $likes = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1"; // 默认条件为 true

        // 添加条件
        if (isset($conditions['uid'])) {
            $where .= " AND uid=" . intval($conditions['uid']);
        }
        if (isset($conditions['username'])) {
            $where .= " AND username='" . daddslashes($conditions['username']) . "'";
        }
        if (isset($conditions['evil_label'])) {
            $where .= " AND evil_label='" . daddslashes($conditions['evil_label']) . "'";
        }
        if (isset($conditions['type'])) {
            $where .= " AND type=" . intval($conditions['type']);
        }
        if (isset($conditions['status'])) {
            $where .= " AND status=" . intval($conditions['status']);
        }
        if (isset($conditions['audit_date'])) {
            $where .= " AND audit_date = " . intval($conditions['audit_date']);
        }

        if (isset($conditions['date_range'])) { // 时间范围
            if (count($conditions['date_range']) == 2) {
                $where .= " AND release_date BETWEEN " . intval($conditions['date_range'][0]) . " AND " . intval($conditions['date_range'][1]);
            }
        }

        foreach ($likes as $like => $value) {
            $where .= " AND " . $like . " LIKE " . $value;
        }



        // 查询总数
        $total_count = DB::result_first("SELECT COUNT(*) FROM " . DB::table(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE) . " WHERE $where");

        // 获取当前页数据
        $sql = "SELECT * FROM " . DB::table(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE) . " WHERE $where ORDER BY release_date DESC LIMIT $offset, $limit";
        $data = DB::fetch_all($sql);


        return [
            'data' => $data,
            'total_count' => $total_count,
            'current_page' => $page,
            'total_pages' => ceil($total_count / $limit)
        ];
    }


    /**
     * 根据id更新数据
     * @param int $id
     * @param array $data
     * @return void
     */
    function updateAuditContent($id, $data)
    {
        $id = intval($id);

        $update_data = [];
        if (isset($data['uid']))
            $update_data['uid'] = intval($data['uid']);
        if (isset($data['username']))
            $update_data['username'] = daddslashes($data['username']);
        if (isset($data['keyword']))
            $update_data['keyword'] = daddslashes($data['keyword']);
        if (isset($data['evil_label']))
            $update_data['evil_label'] = daddslashes($data['evil_label']);
        if (isset($data['type']))
            $update_data['type'] = intval($data['type']);
        if (isset($data['examine_text']))
            $update_data['examine_text'] = daddslashes($data['examine_text']);
        if (isset($data['status']))
            $update_data['status'] = intval($data['status']);
        if (isset($data['audit_date']))
            $update_data['audit_date'] = intval($data['audit_date']);
        if (isset($data['fail_reason']))
            $update_data['fail_reason'] = daddslashes($data['fail_reason']);
        if (isset($data['audit_uid']))
            $update_data['audit_uid'] = intval($data['audit_uid']);
        if (isset($data['audit_username']))
            $update_data['audit_username'] = daddslashes($data['audit_username']);

        DB::update(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE, $update_data, "id=$id");
    }


    /**
     * 根据主键id删除数据
     * @param int $id
     * @return void
     */
    function deleteAuditContentById($id)
    {
        $id = intval($id);
        DB::delete(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE, "id=$id");
    }


    /**
     * 根据id批量删除数据
     * @param array $ids  id数组
     * @return void
     */
    function deleteAuditContentByIds($ids)
    {
        $ids = implode(",", $ids);
        DB::delete(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE, "id IN ($ids)");
    }

    /**
     * 根据id列表批量更新数据
     * @param array $ids
     * @param array $data
     * @return void
     */
    function updateAuditContentByIds($ids, $data)
    {
        $ids = implode(",", $ids);
        $update_data = [];
        if (isset($data['uid']))
            $update_data['uid'] = intval($data['uid']);
        if (isset($data['username']))
            $update_data['username'] = daddslashes($data['username']);
        if (isset($data['keyword']))
            $update_data['keyword'] = daddslashes($data['keyword']);
        if (isset($data['evil_label']))
            $update_data['evil_label'] = daddslashes($data['evil_label']);
        if (isset($data['type']))
            $update_data['type'] = intval($data['type']);
        if (isset($data['examine_text']))
            $update_data['examine_text'] = daddslashes($data['examine_text']);
        if (isset($data['status']))
            $update_data['status'] = intval($data['status']);
        if (isset($data['audit_date']))
            $update_data['audit_date'] = intval($data['audit_date']);
        if (isset($data['fail_reason']))
            $update_data['fail_reason'] = daddslashes($data['fail_reason']);
        if (isset($data['audit_uid']))
            $update_data['audit_uid'] = intval($data['audit_uid']);
        if (isset($data['audit_username']))
            $update_data['audit_username'] = daddslashes($data['audit_username']);

        DB::update(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE, $update_data, "id IN ($ids)");
    }

    /**
     * @param array $conditions 查询条件map
     * @param array $likes 模糊查询条件map
     * **/
    function countAuditContent($conditions, $likes)
    {
        $cond = [];
        foreach ($conditions as $condition => $value) {
            if (is_string($value)) {
                array_push($cond, $condition . " = '" . daddslashes($value) . "'");
            } else {
                array_push($cond, $condition . " = " . daddslashes($value) . "");
            }
        }


        foreach ($likes as $condition => $value) {
            if (is_string($value)) {
                array_push($cond, $condition . " like '" . daddslashes($value) . "'");
            } else {
                array_push($cond, $condition . " like " . daddslashes($value) . "");
            }
        }

        // 查询条件
        $condition = implode(" AND ", $cond);

        // 查询数量
        $count = DB::result_first("SELECT COUNT(*) FROM " . DB::table(KNOWNSEC_HJWS_AUDIT_CONTENT_TABLE) . " WHERE " . $condition);

        return $count;

    }


    /**
     * 将审核内容批量插入discuz帖子
     * @param mixed $results knownsec 帖子内容信息列表
     * @throws \Exception
     * @return void
     */
    public function batchInsertForumFromAuditContent($results)
    {
        global $_G;
        $auditUid = $_G['uid'];
        $auditUser = $_G['username'];
        $now = time();
        loadcache(array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes', 'domainwhitelist', 'albumcategory'));


        foreach ($results as $result) {
            $postmeta = json_decode($result['post_meta'], true);

            try {
                $type = $result['type'];
                $releaseDate = $result['release_date'];
                $uid = $result['uid'];
                $subject = $result['examine_subject'];
                $content = $result['examine_text'];
                $username = $result['username'];

                // 发帖
                if ($type == KnownsecOptions::AUDIT_TYPE_POST) {
                    $fid = intval($postmeta['fid']);
                    if (empty($fid)) {
                        self::updateAuditContent($result['id'], array(
                            'status' => KnownsecOptions::STATUS_FAILLURE,
                            'fail_reason' => 'post_meta fid为空',
                            'audit_uid' => $auditUid,
                            'audit_username' => $auditUser,
                            'audit_date' => $now,
                        ));
                        // 只有这一条消息的时候
                        if (count($results) == 1) {
                            throw new \Exception('post_meta fid为空');
                        }
                        continue;
                    }

                    $thread_model = new \model_forum_thread($fid);
                    $thread_model->member('username', $username);
                    $thread_model->member('uid', $uid);
                    $thread_model->newthread(array(
                        'subject' => $subject,
                        'message' => $content,
                        'lastposter' => $username,
                        'publishdate' => $releaseDate,
                        'clientip' => $postmeta['useip'],
                        'remoteport' => $postmeta['port'],
                        'allownoticeauthor' => '1',
                        'special' => 0,// thread
                    ));





                    // // 插入 forum_thread 表的数据，用于记录帖子的基本信息
                    // $thread_data = array(
                    //     'fid' => $fid,  // 版块 ID，指定帖子所属的版块
                    //     'posttableid' => 0,
                    //     'readperm' => 0,
                    //     'price' => 0,
                    //     'author' => $username,
                    //     'authorid' => $uid,
                    //     'subject' => $subject,
                    //     'dateline' => $releaseDate,
                    //     'lastpost' => $releaseDate,
                    //     'lastposter' => $username,
                    //     'views' => 0,
                    //     'replies' => 0,
                    //     'displayorder' => 0,// 置顶1置顶
                    //     'highlight' => 0,
                    //     'digest' => 0,
                    //     'rate' => 0,
                    //     'special' => 0,
                    //     'attachment' => 0,
                    //     'moderated' => 0,
                    //     'closed' => 0,
                    // );

                    // // 使用 C::t('forum_thread') 插入数据到 forum_thread 表，并返回帖子的 tid（帖子 ID）
                    // $tid = DB::insert('forum_thread', $thread_data, true); // 插入主题并获取 TID


                    // $new_thread = array(
                    //     'tid' => $tid,
                    //     'fid' => $fid,
                    //     'dateline' => $releaseDate,
                    // );
                    // DB::insert('forum_newthread', $new_thread, true);



                    // // 获取pid
                    // $pid = DB::insert('forum_post_tableid', array('pid' => null), true);


                    // // 插入 forum_post 表的数据，用于记录帖子的回复（帖子的第一个回复）
                    // $post_data = array(
                    //     'pid' => $pid,
                    //     'fid' => $fid,
                    //     'tid' => $tid,
                    //     'first' => 1, // 标识为主帖
                    //     'author' => $username,
                    //     'authorid' => $uid,
                    //     'subject' => $subject,
                    //     'dateline' => $releaseDate,
                    //     'message' => $content,
                    //     'useip' => $postmeta['useip'],
                    //     'port' => $postmeta['port'],
                    //     'invisible' => 0,
                    //     'anonymous' => 0,
                    //     'htmlon' => 0,

                    //     // 'bbcodeoff' => -1,
                    //     // 'smileyoff' => -1,
                    //     'parseurloff' => 0,
                    //     'attachment' => 0,
                    //     'tags' => '',
                    //     'replycredit' => 0,
                    //     'status' => 0,
                    //     'position' => 1, // 标识为第一个回复
                    // );




                    // // 插入回复数据到 forum_post 表
                    // DB::insert('forum_post', $post_data);

                    // // 更新版块统计数据
                    // DB::query("UPDATE " . DB::table('forum_forum') . " SET threads=threads+1, posts=posts+1, lastpost='$tid\t$subject\t$releaseDate\t$username' WHERE fid='$fid'");

                    // // 更新用户统计数据
                    // DB::query("UPDATE " . DB::table('common_member_status') . " SET lastpost='$releaseDate' WHERE uid='$uid'");


                }
                // 回帖
                if ($type == KnownsecOptions::AUDIT_TYPE_RELY) {
                    $tid = intval($postmeta['tid']);
                    $quote = empty($postmeta['quote']) ? "" : $postmeta['quote'];
                    if (empty($tid)) {
                        self::updateAuditContent($result['id'], array(
                            'status' => KnownsecOptions::STATUS_FAILLURE,
                            'fail_reason' => 'post_meta tid|quote为空',
                            'audit_uid' => $auditUid,
                            'audit_username' => $auditUser,
                            'audit_date' => $now,
                        ));
                        // 只有这一条消息的时候
                        if (count($results) == 1) {
                            throw new \Exception('post_meta tid为空');
                        }
                        continue;
                    }

                    $post_model = new \model_forum_post($tid);
                    $post_model->member('username', $username);
                    $post_model->member('uid', $uid);
                    $post_model->newreply(array(
                        'timestamp' => $releaseDate,
                        'subject' => $subject,
                        'message' => $quote . $content,
                        'clientip' => $postmeta['useip'],
                        'remoteport' => $postmeta['port'],
                        'special' => 0,
                    ));


                    // DB::begin_transaction();
                    // try {

                    //     // 获取pid
                    //     $pid = DB::insert('forum_post_tableid', array('pid' => null), true);
                    //     if ($pid % 1024 == 0) {
                    //         C::t('forum_post_tableid')->delete_by_lesspid($pid);
                    //     }
                    //     savecache('max_post_id', $pid);

                    //     // 查一下主贴，这里加写锁。需要获取此时的maxposition
                    //     $thread = DB::fetch_first("SELECT * FROM " . DB::table('forum_thread') . " WHERE tid = " . $tid . " FOR UPDATE");


                    //     // 插入 forum_post 表的数据
                    //     // 主键是tid+position
                    //     $post_data = array(
                    //         'pid' => $pid,
                    //         'fid' => $thread['fid'],
                    //         'tid' => $tid,
                    //         'first' => 0,
                    //         'author' => $username,
                    //         'authorid' => $uid,
                    //         // 'subject' => $subject,
                    //         'dateline' => $releaseDate,
                    //         'message' => $quote . $content,
                    //         'useip' => $postmeta['useip'],
                    //         'port' => $postmeta['port'],
                    //         'invisible' => 0,
                    //         'status' => 0,
                    //         'position' => $thread['maxposition'] + 1,
                    //     );

                    //     // 插入回复数据到 forum_post 表
                    //     DB::insert('forum_post', $post_data);

                    //     // 更新 `forum_thread` 表的回复数
                    //     DB::query("UPDATE " . DB::table('forum_thread') . " SET replies=replies+1,maxposition=maxposition+1 WHERE tid='$tid'");



                    //     // // 更新版块统计数据
                    //     DB::query("UPDATE " . DB::table('forum_forum') . " SET posts=posts+1 WHERE fid='$fid'");

                    //     // 提交事务
                    //     DB::commit();

                    //     // // 更新用户统计数据
                    //     // DB::query("UPDATE " . DB::table('common_member_status') . " SET lastpost='$releaseDate' WHERE uid='$uid'");
                    // } catch (\Exception $e) {
                    //     DB::rollback();
                    //     error_log('update reply fail:' . $e->getMessage());
                    //     throw $e;
                    // }

                }


                // 更新为审核成功
                self::updateAuditContent($result['id'], array(
                    'status' => KnownsecOptions::STATUS_SUCCESS,
                    'audit_uid' => $auditUid,
                    'audit_username' => $auditUser,
                    'audit_date' => $now,
                ));





            } catch (\Exception $e) {
                // 更新为审核失败
                self::updateAuditContent($result['id'], array(
                    'status' => KnownsecOptions::STATUS_FAILLURE,
                    'fail_reason' => $e->getMessage(),
                    'audit_uid' => $auditUid,
                    'audit_username' => $auditUser,
                    'audit_date' => $now,
                ));
                throw $e;// 又一个审核失败了就抛出去，其他的都不执行
            }
        }
    }


}