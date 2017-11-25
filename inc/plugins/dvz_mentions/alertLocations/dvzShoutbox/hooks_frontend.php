<?php

namespace dvzMentions\Alerts\dvzShoutbox\Hooks;

function myalerts_load_lang()
{
    global $lang;
    $lang->load('dvz_mentions_alerts_dvzShoutbox');
}

function dvz_shoutbox_shout_commit(array $data)
{
    $alertDetails = [];

    $locationData = [
        'object_id' => (int)$data['shout_id'],
        'author'   => (int)$data['uid'],
    ];

    $mentionedUserIds = \dvzMentions\getMentionedUserIds($data['text']);

    if ($mentionedUserIds) {
        \dvzMentions\Alerts\queueAlerts('dvzShoutbox', $alertDetails, $locationData, $mentionedUserIds, $data['uid']);
    }
}

function dvz_shoutbox_update(array $data)
{
    $GLOBALS['dvzMentionsAlertsDvzShoutboxOldMessage'] = $data['text'];
}

function dvz_shoutbox_update_commit(array $data)
{
    if (isset($GLOBALS['dvzMentionsAlertsDvzShoutboxOldMessage'])) {
        $alertDetails = [];

        $locationData = [
            'object_id' => (int)$data['shout_id'],
            'author'   => (int)$data['uid'],
        ];

        $mentionedUserIdsOld = \dvzMentions\getMentionedUserIds($GLOBALS['dvzMentionsAlertsDvzShoutboxOldMessage']);
        $mentionedUserIds = \dvzMentions\getMentionedUserIds($data['text']);

        $mentionedUserIds = array_diff($mentionedUserIds, $mentionedUserIdsOld);

        if ($mentionedUserIds) {
            \dvzMentions\Alerts\queueAlerts('dvzShoutbox', $alertDetails, $locationData, $mentionedUserIds, $data['uid']);
        }
    }
}

function dvz_shoutbox_delete_commit()
{
    global $mybb;

    $shoutId = $mybb->get_input('id', \MyBB::INPUT_INT);

    \dvzMentions\Alerts\deleteAlertsByLocationAndObjectId('dvzShoutbox', $shoutId);
}
