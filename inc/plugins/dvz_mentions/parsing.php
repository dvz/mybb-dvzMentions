<?php

namespace dvzMentions\Parsing;

function getMentionedUsernames(string $message): array
{
    return \dvzMentions\Parsing\getUniqueUsernamesFromMatches(
        \dvzMentions\Parsing\getMatches($mesasge)
    );
}

function getMatches(string $message, bool $stripIndirectContent = false, int $limit = null): array
{
    $messageContent = $message;

    if ($stripIndirectContent) {
        $messageContent = \dvzMentions\Parsing\getMessageWithoutIndirectContent($message);
    }

    $lengthRange = \dvzMentions\getSettingValue('min_value_length') . ',' . \dvzMentions\getSettingValue('max_value_length');

    $regex = '/(?:^|[^\w/])@((?:("|\'|`)([^\n<>,;&\\\]{' . $lengthRange . '}?)\2)|([^\n<>,;&\\\"\'`\.:\-+=~@#$%^*!?()\[\]{}\s]{' . $lengthRange . '}))(?:#([1-9][0-9]{0,9}))?/u';

    preg_match_all($regex, $messageContent, $regexMatchSets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $matches = [];

    if (!empty($regexMatchSets)) {
        if ($limit !== null & count($regexMatchSets) > $limit) {
            $matches = [];
        } else {
            $ignoredUsernames = \dvzMentions\getIgnoredUsernames();

            foreach ($regexMatchSets as $regexMatchSet) {
                if (isset($regexMatchSet[4])) {
                    $username = $regexMatchSet[4][0];

                    if (in_array($username, $ignoredUsernames)) {
                        continue;
                    }
                } else {
                    $username = $regexMatchSet[3][0];
                }

                $trimmedMatch = substr($regexMatchSet[0][0], 1);

                if ($trimmedMatch[0] == '@') {
                    $fullMatch = $trimmedMatch;
                } else {
                    $fullMatch = $regexMatchSet[0][0];
                }

                $matches[] = [
                    'offset' => $regexMatchSet[1][1] - 1,
                    'full' => $fullMatch,
                    'username' => $username,
                    'escapeCharacter' => $regexMatchSet[2][0] ?? null,
                    'userId' => $regexMatchSet[5][0] ?? null,
                ];
            }
        }
    }

    return $matches;
}

function getUniqueUsernamesFromMatches(array $matches):  array
{
    return array_unique(
        array_map(
            'mb_strtolower',
            \array_column($matches, 'username')
        )
    );
}

function getUniqueUserIdsFromMatches(array $matches):  array
{
    return array_map(
        'intval',
        array_unique(
            array_filter(
                \array_column($matches, 'uid')
            )
        )
    );
}

function getUniqueUserSelectorsFromMatches(array $matches): array
{
    $selectors = [
        'userIds' => [],
        'usernames' => [],
    ];

    foreach ($matches as $match) {
        if ($match['userId']) {
            $value = (int)$match['userId'];

            if (!in_array($value, $selectors['userIds'])) {
                $selectors['userIds'][] = $value;
            }
        } elseif ($match['username']) {
            $value = mb_strtolower($match['username']);

            if (!in_array($value, $selectors['usernames'])) {
                $selectors['usernames'][] = $value;
            }
        }
    }

    return $selectors;
}

function getMessageWithoutIndirectContent(string $message)
{
    global $cache;

    // strip default tags
    $message = preg_replace('/\[(quote|code|php)(=[^\]]*)?\](.*?)\[\/\1\]/si', null, $message);

    // strip tags with DVZ Code Tags syntax
    $pluginsCache = $cache->read('plugins');

    if (!empty($pluginsCache['active']) && in_array('dvz_code_tags', $pluginsCache['active'])) {
        $_blackhole = [];

        if (\dvzCodeTags\getSettingValue('parse_block_fenced_code')) {
            $matches = \dvzCodeTags\Parsing\getFencedCodeMatches($message);
            $message = \dvzCodeTags\Formatting\getMessageWithPlaceholders($message, $matches, $_blackhole);
        }

        if (\dvzCodeTags\getSettingValue('parse_block_mycode_code')) {
            $matches = \dvzCodeTags\Parsing\getMycodeCodeMatches($message);
            $message = \dvzCodeTags\Formatting\getMessageWithPlaceholders($message, $matches, $_blackhole);
        }

        if (\dvzCodeTags\getSettingValue('parse_inline_backticks_code')) {
            $matches = \dvzCodeTags\Parsing\getInlineCodeMatches($message);
            $message = \dvzCodeTags\Formatting\getMessageWithPlaceholders($message, $matches, $_blackhole);
        }
    }

    return $message;
}
