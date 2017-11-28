<?php

namespace dvzMentions\Hooks;

function global_start()
{
    global $mybb, $lang;

    if (\dvzMentions\Alerts\myalertsIsIntegrable()) {
        if ($mybb->user['uid'] != 0) {
            \dvzMentions\Alerts\registerMyalertsFormatters();
        }
    }
}

function parse_message_me_mycode(string $message): string
{
    $matches = \dvzMentions\Parsing\getMatches($message, false, \dvzMentions\getSettingValue('match_limit'));

    $message = \dvzMentions\Formatting\getMessageWithPlaceholders(
        $message,
        $matches,
        $GLOBALS['dvzMentionsPlaceholders']
    );

    return $message;
}

function parse_message_end(string $message): string
{
    if (!\dvzMentions\isStaticRender()) {
        $message = \dvzMentions\getFormattedMessageFromPlaceholders(
            $message,
            $GLOBALS['dvzMentionsPlaceholders'],
            \dvzMentions\getSettingValue('query_limit')
        );
    }

    return $message;
}

function pre_output_page(string $content): string
{
    return \dvzMentions\getFormattedMessageFromPlaceholders(
        $content,
        $GLOBALS['dvzMentionsPlaceholders'],
        \dvzMentions\getSettingValue('query_limit')
    );
}
