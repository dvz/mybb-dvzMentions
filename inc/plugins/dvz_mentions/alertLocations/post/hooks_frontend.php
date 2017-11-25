<?php

namespace dvzMentions\Alerts\Post\Hooks;

function myalerts_load_lang()
{
    global $lang;
    $lang->load('dvz_mentions_alerts_post');
}

function newthread_do_newthread_end()
{
    global $mybb, $new_thread, $visible, $uid, $thread_info;

    if ($visible == 1) {
        $message = $new_thread['message'];

        $alertDetails = [
            'tid'     => (int)$thread_info['tid'],
            'pid'     => (int)$thread_info['pid'],
            'subject' => $new_thread['subject'],
        ];

        $locationData = [
            'fid'       => (int)$new_thread['fid'],
            'tid'       => (int)$thread_info['tid'],
            'pid'       => (int)$thread_info['pid'],
            'object_id' => (int)$thread_info['pid'],
            'author'    => $uid,
            'subject'   => $new_thread['subject'],
        ];

        $mentionedUserIds = \dvzMentions\getMentionedUserIds($message);

        if ($mentionedUserIds) {
            \dvzMentions\Alerts\queueAlerts('post', $alertDetails, $locationData, $mentionedUserIds, $mybb->user['uid']);
        }
    }
}

function newreply_do_newreply_end()
{
    global $post, $pid, $thread, $visible, $thread_subject;

    if ($visible == 1) {
        $message = $post['message'];

        $alertDetails = [
            'tid'     => (int)$thread['tid'],
            'pid'     => $pid,
            'subject' => $thread_subject,
        ];

        $locationData = [
            'fid'       => (int)$thread['fid'],
            'tid'       => (int)$thread['tid'],
            'pid'       => (int)$pid,
            'object_id' => (int)$pid,
            'author'    => $post['uid'],
            'subject'   => $thread_subject,
        ];

        $mentionedUserIds = \dvzMentions\getMentionedUserIds($message);

        if ($mentionedUserIds) {
            \dvzMentions\Alerts\queueAlerts('post', $alertDetails, $locationData, $mentionedUserIds, $post['uid']);
        }
    }
}

function editpost_do_editpost_start()
{
    global $post;

    $GLOBALS['dvzMentionsEditOldMessage'] = $post['message'];
}

function xmlhttp_edit_post_start()
{
    return editpost_do_editpost_start();
}

function datahandler_post_update(\PostDataHandler $PostDataHandler)
{
    global $post, $thread;

    if (isset($GLOBALS['dvzMentionsEditOldMessage']) && isset($PostDataHandler->data['message'])) {
        $postData = $post;

        $message = $PostDataHandler->data['message'];

        $alertDetails = [
            'tid'     => (int)$thread['tid'],
            'pid'     => (int)$postData['pid'],
            'subject' => $thread['subject'],
        ];

        $locationData = [
            'fid'       => (int)$thread['fid'],
            'tid'       => (int)$thread['tid'],
            'pid'       => (int)$postData['pid'],
            'object_id' => (int)$postData['pid'],
            'author'    => (int)$postData['uid'],
        ];

        $mentionedUserIdsOld = \dvzMentions\getMentionedUserIds($GLOBALS['dvzMentionsEditOldMessage']);
        $mentionedUserIds = \dvzMentions\getMentionedUserIds($message);

        $mentionedUserIds = array_diff($mentionedUserIds, $mentionedUserIdsOld);

        if ($mentionedUserIds) {
            \dvzMentions\Alerts\queueAlerts('post', $alertDetails, $locationData, $mentionedUserIds, (int)$postData['uid']);
        }
    }
}

function class_moderation_delete_post($pid)
{
    global $post, $thread;

    \dvzMentions\Alerts\deleteAlertsByLocationAndObjectId('post', $pid);
}
