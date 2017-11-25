<?php

namespace dvzMentions\Formatting;

function getFormattedMessageFromPlaceholdersAndUsers(string $content, array $placeholders, array $users): string
{
    if ($placeholders) {
        foreach ($placeholders as $index => $fingerprint) {
            $user = $users['byUserId'][ (int)$fingerprint['userId'] ] ?? $users['byUsername'][ mb_strtolower($fingerprint['username']) ] ?? null;

            if ($user) {
                $replacement = \dvzMentions\Formatting\getFormattedTag($user);
            } else {
                $replacement = $fingerprint['full'];
            }

            $content = str_replace('<DVZ_ME#' . $index . '>', $replacement, $content);
        }
    }

    return $content;
}

function getMessageWithPlaceholders(string $message, array $matches, array &$placeholders = []): string
{
    foreach ($matches as &$match) {
        $fingerprint = [
            'full' => $match['full'],
            'username' => $match['username'],
            'escapeCharacter' => $match['escapeCharacter'],
            'userId' => $match['userId'],
        ];

        $placeholderId = array_search($fingerprint, $placeholders);

        if ($placeholderId === false) {
            $placeholderId = count($placeholders);
            $placeholders[] = $fingerprint;
        }

        $match['replacement'] = '<DVZ_ME#' . $placeholderId . '>';
    }

    $message = \dvzMentions\Formatting\replaceMatchesInMessage($message, $matches);

    return $message;
}

function replaceMatchesInMessage(string $message, array $matches): string
{
    $correction = 0;

    foreach ($matches as $match) {
        // offset, call character, correction
        $start = $match['offset'] + $correction;

        $length = strlen($match['full']);

        $message = substr_replace($message, $match['replacement'], $start, $length);

        $correction += strlen($match['replacement']) - $length;
    }

    return $message;
}

function getFormattedTag(array $user): string
{
    global $mybb;

    if (\dvzMentions\getSettingValue('keep_prefix')) {
        $prefix = '@';
    } else {
        $prefix =  null;
    }

    $usernameEscaped = \htmlspecialchars_uni($user['username']);

    if (\dvzMentions\getSettingValue('apply_username_style') && isset($user['usergroup'], $user['displaygroup'])) {
        $username = \format_name($usernameEscaped, $user['usergroup'], $user['displaygroup']);
    } else {
        $username = $usernameEscaped;
    }

    $attributes = [
        'href="' . $mybb->settings['bburl'] . '/' . \get_profile_link($user['uid']) . '"',
        'class="mycode_mention"',
    ];

    if (\dvzMentions\getSettingValue('links_to_new_tabs')) {
        $attributes[] = 'target="_blank"';
    }

    $attributesHtml = implode(' ', $attributes);

    $html = $prefix . '<a ' . $attributesHtml . '>' . $username . '</a>';

    return $html;
}

function getUserFieldList(): array
{
    $fields = [
        'uid',
        'username',
    ];

    if (\dvzMentions\getSettingValue('apply_username_style')) {
        $fields = array_merge($fields, [
            'usergroup',
            'displaygroup',
        ]);
    }

    return $fields;
}
