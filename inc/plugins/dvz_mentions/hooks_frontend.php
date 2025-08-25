<?php

namespace dvzMentions\Hooks;

function global_start()
{
    global $mybb;

    if (\dvzMentions\Alerts\myalertsIsIntegrable()) {
        if ($mybb->user['uid'] != 0) {
            \dvzMentions\Alerts\registerMyalertsFormatters();
        }
    }
}

function parse_message_me_mycode(string $message): string
{
    global $mybb;

    // temporarily replace content where tags shouldn't be parsed
    $preMatchReplacementsPatterns = [];

    $preMatchReplacementsPatterns[] = "#\[quote=([\"']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"']|&quot;)?\]#si";

    if ($mybb->settings['allowlinkmycode'] == 1) {
        $preMatchReplacementsPatterns[] = '#\[url(?:=.*?)?\].*?\[/url\]#si';
    }

    if ($mybb->settings['allowemailmycode'] == 1) {
        $preMatchReplacementsPatterns[] = '#\[email(?:=.*?)?\].*?\[/email\]#si';
    }

    if($mybb->settings['allowautourl'] == 1) {
        // RegEx used in postParser::mycode_auto_url()
        $preMatchReplacementsPatterns[] = "~
            <a\\s[^>]*>.*?</a>|								# match and return existing links
            (?<=^|[\s\(\)\[\>])								# character preceding the link
            (?P<prefix>
                (?:http|https|ftp|news|irc|ircs|irc6)://|	# scheme, or
                (?:www|ftp)\.								# common subdomain
            )
            (?P<link>
                (?:[^\/\"\s\<\[\.]+\.)*[\w]+				# host
                (?::[0-9]+)?								# port
                (?:/(?:[^\"\s<\[&]|\[\]|&(?:amp|lt|gt);)*)?	# path, query, fragment; exclude unencoded characters
                [\w\/\)]
            )
            (?![^<>]*?>)									# not followed by unopened > (within HTML tags)
        ~iusx";
    }

    $preMatchReplacedStrings = [];
    $preMatchReplacedStringIndex = 0;

    $message = preg_replace_callback(
        $preMatchReplacementsPatterns,
        function (array $matches) use (&$preMatchReplacedStringIndex, &$preMatchReplacedStrings): string {
            $preMatchReplacedStrings[$preMatchReplacedStringIndex] = $matches[0];

            $replacement = '<dvz_me_prematch_placeholder id="' . $preMatchReplacedStringIndex . '" />';

            $preMatchReplacedStringIndex++;

            return $replacement;
        },
        $message
    );

    // find matches and insert placeholders
    $matches = \dvzMentions\Parsing\getMatches($message, false, \dvzMentions\getSettingValue('match_limit'));

    $message = \dvzMentions\Formatting\getMessageWithPlaceholders(
        $message,
        $matches,
        $GLOBALS['dvzMentionsPlaceholders']
    );

    // restore temporary replacements
    foreach ($preMatchReplacedStrings as $index => $replacedString) {
        $message = str_replace(
            '<dvz_me_prematch_placeholder id="' . $index . '" />',
            $replacedString,
            $message
        );
    }

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
