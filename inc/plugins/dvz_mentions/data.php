<?php

namespace dvzMentions\Data;

function getUsersBySelectors(array $selectors, array $fields = ['uid']): array
{
    global $db;

    $usersBySelectors = [
        'byUserId' => [],
        'byUsername' => [],
    ];

    $fields[] = 'username';
    $fields = array_unique($fields);

    $querySelectors = [];

    if (!empty($selectors['usernames'])) {
        $usernamesEscaped = array_map(
            function ($username) use ($db) {
                return "'" . $db->escape_string($username) . "'";
            },
            array_unique($selectors['usernames'])
        );

        if (\dvzMentions\getSettingValue('cs_collation')) {
            $usernameColumn = 'LOWER(username)';
        } else {
            $usernameColumn = 'username';
        }

        $usernamesCsv = implode(',', $usernamesEscaped);

        $querySelectors[] = $usernameColumn . ' IN (' . $usernamesCsv . ')';
    }

    if (!empty($selectors['userIds'])) {
        $userIdsEscaped = array_map(
            'intval',
            array_unique($selectors['userIds'])
        );

        $userIdsCsv = implode(',', $userIdsEscaped);

        $querySelectors[] = 'uid IN (' . $userIdsCsv . ')';
    }

    if (!empty($querySelectors)) {
        $where = implode(' OR ', $querySelectors);

        $data = $db->simple_select('users', implode(',', $fields), $where);

        while ($row = $db->fetch_array($data)) {
            $usersBySelectors['byUsername'][ mb_strtolower($row['username']) ] = $row;
            $usersBySelectors['byUserId'][ (int)$row['uid'] ] = $row;
        }
    }

    return $usersBySelectors;
}
