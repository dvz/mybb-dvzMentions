<?php

namespace dvzMentions\Alerts\Post;

// common
function alertPossible(array $locationData, int $userId): bool
{
    global $db, $cache;

    static $usersCache;

    if (!isset($usersCache[$userId])) {
        $row = $db->fetch_array(
            $db->simple_select('users', 'usergroup,additionalgroups,ignorelist', 'uid=' . (int)$userId)
        );

        if ($row) {
            $usersCache[$userId] = [
                'ignored_users' => explode(',', $row['ignorelist']),
                'groups' => array_merge([$row['usergroup']], explode(',', $row['additionalgroups'])),
            ];
        } else {
            return false;
        }
    }

    $userData = $usersCache[$userId];

    // ignore list
    if (in_array($locationData['author'], $userData['ignored_users'])) {
        return false;
    }

    $forumData = \get_forum($locationData['fid'], true);

    // forum password
    if ($forumData['password']) {
        return false;
    }

    $threadData = \get_thread($locationData['tid']);
    $postData = \get_post($locationData['pid']);

    // forum permissions
    $forumPermissions = $cache->read('forumpermissions')[ $locationData['fid'] ];

    $permissions = [
        'canview'               => false,
        'canviewthreads'        => false,
        'canonlyviewownthreads' => null, // any "false" supersedes "true"
    ];

    foreach ($userData['groups'] as $gid) {
        if (isset($forumPermissions[$gid])) {
            // get forum-specific group permissions
            $groupPermissions = $forumPermissions[$gid];
        } else {
            // default to global group permissions
            $groupPermissions = $cache->read('usergroups')[$gid];
        }

        if ($groupPermissions['canview']) {
            $permissions['canview'] = true;
        }

        if ($groupPermissions['canviewthreads']) {
            $permissions['canviewthreads'] = true;
        }

        if ($groupPermissions['canonlyviewownthreads']) {
            if ($permissions['canonlyviewownthreads'] == null) {
                $permissions['canonlyviewownthreads'] = true;
            }
        } else {
            $permissions['canonlyviewownthreads'] = false;
        }
    }

    if (!$permissions['canview'] || !$permissions['canviewthreads']) {
        return false;
    }

    if ($permissions['canonlyviewownthreads']) {
        if ($threadData['uid'] != $userId) {
            return false;
        }
    }

    // thread permissions
    if ($threadData['visible'] != 1) {
        if (
            $threadData['visible'] == 0 && !\is_moderator($locationData['fid'], 'canviewunapprove', $userId) ||
            $threadData['visible'] == -1 && !\is_moderator($locationData['fid'], 'canviewdeleted', $userId)
        ) {
            return false;
        }
    }

    // post permissions
    if ($postData['visible'] != 1) {
        if (
            $postData['visible'] == 0 && !\is_moderator($locationData['fid'], 'canviewunapprove', $userId) ||
            $postData['visible'] == -1 && !\is_moderator($locationData['fid'], 'canviewdeleted', $userId)
        ) {
            return false;
        }
    }

    return true;
}
