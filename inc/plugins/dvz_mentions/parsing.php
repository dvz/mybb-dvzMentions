<?php

namespace dvzMentions\Parsing;

function getMentionedUsernames(string $message): array
{
    return \dvzMentions\Parsing\getUniqueUsernamesFromMatches(
        \dvzMentions\Parsing\getMatches($message)
    );
}

function getMatches(string $message, bool $stripIndirectContent = false, int $limit = null): array
{
    $messageContent = $message;

    if ($stripIndirectContent) {
        $messageContent = \dvzMentions\Parsing\getMessageWithoutIndirectContent($message);
    }

    $lengthRange = \dvzMentions\getSettingValue('min_value_length') . ',' . \dvzMentions\getSettingValue('max_value_length');

    $regex = '~
        (?:^|[^\w])
        (?P<match>
            @
            (?:
                (?:
                    (?P<escapeCharacter>"|\'|`)
                    (?P<escapedUsername>[^\n<>,;&\\\]{' . $lengthRange . '}?)
                    (?P=escapeCharacter)
                )
                |
                (?P<simpleUsername>[^\n<>,;&\\\/"\'`\.:\-+=\~@\#$%^*!?()\[\]{}\s]{' . $lengthRange . '})
            )
            (?:\#(?P<userId>[1-9][0-9]{0,9}))?
        )
    ~ux';

    preg_match_all($regex, $messageContent, $regexMatchSets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $matches = [];

    if (!empty($regexMatchSets)) {
        if ($limit !== null & count($regexMatchSets) > $limit) {
            $matches = [];
        } else {
            $ignoredUsernames = \dvzMentions\getIgnoredUsernames();

            foreach ($regexMatchSets as $regexMatchSet) {
                if (!empty($regexMatchSet['escapedUsername'][0])) {
                    $username = $regexMatchSet['escapedUsername'][0];

                    if (in_array($username, $ignoredUsernames)) {
                        continue;
                    }
                } else {
                    $username = $regexMatchSet['simpleUsername'][0];
                }

                $matches[] = [
                    'offset' => $regexMatchSet['match'][1],
                    'full' => $regexMatchSet['match'][0],
                    'username' => $username,
                    'escapeCharacter' => $regexMatchSet['escapeCharacter'][0] ?? null,
                    'userId' => $regexMatchSet['userId'][0] ?? null,
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
