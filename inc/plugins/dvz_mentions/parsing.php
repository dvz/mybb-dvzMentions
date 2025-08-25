<?php

namespace dvzMentions\Parsing;

function getMentionedUsernames(string $message): array
{
    return \dvzMentions\Parsing\getUniqueUsernamesFromMatches(
        \dvzMentions\Parsing\getMatches($message)
    );
}

function getMatches(string $message, bool $stripIndirectContent = false, ?int $limit = null): array
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
        if ($limit !== null && count($regexMatchSets) > $limit) {
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

    // strip non-nestable tags
    $message = preg_replace('/\[(code|php)(=[^\]]*)?\](.*?)\[\/\1\]/si', "\n", $message);

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

    // strip nestable tags
    $message = \dvzMentions\Parsing\getMessageWithoutQuotes($message);

    return $message;
}

function getMessageWithoutQuotes(string $message): string
{
    // collect start & end offsets
    /**
     * @var $bounds array<non-negative-int, bool>
     *
     * Types of found bounds by offset (inclusive). `true` if start bound, `false` if end bound.
     */
    $bounds = [];

    preg_match_all(
        '/(?<open>\[quote(?:=[^\]]*)?\])|(?<close>\[\/quote\])/i',
        $message,
        $sets,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );

    foreach ($sets as $set) {
        if ($set['open'][0] !== '') {
            [$string, $offset] = $set['open'];

            $bounds[$offset] = true;
        } elseif ($set['close'][0] !== '') {
            [$string, $offset] = $set['close'];

            $lastInclusiveOffset = $offset + strlen($string) - 1;

            $bounds[$lastInclusiveOffset] = false;
        }
    }


    // determine top-level ranges, assuming valid structure
    /**
     * @var $topLevelRanges array<non-negative-int, non-negative-int>
     *
     * Ranges found at the top level of nesting (inclusive).
     *
     */
    $topLevelRanges = [];

    $topLevelStartOffsets = [];
    $depth = 0;

    foreach ($bounds as $offset => $isStart) {
        if ($isStart) {
            if ($depth === 0) {
                $topLevelStartOffsets[] = $offset;
            }

            $depth++;
        } elseif ($depth >= 1) {
            $depth--;

            if ($depth === 0) {
                $topLevelRanges[] = [array_pop($topLevelStartOffsets), $offset];
            }
        }
    }


    // construct string without top-level tags
    $result = '';
    $readOffset = 0;

    foreach ($topLevelRanges as $range) {
        [$start, $end] = $range;

        $result .= substr($message, $readOffset, $start - $readOffset);

        $result .= "\n";

        $readOffset = $end + 1;
    }

    $result .= substr($message, $readOffset);


    return $result;
}
