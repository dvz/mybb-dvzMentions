<?php
/**
 * Copyright (c) 2014-2018, Tomasz 'Devilshakerz' Mlynski [devilshakerz.com]
 *
 * Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby
 * granted, provided that the above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN
 * AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 */

// common modules
require MYBB_ROOT . 'inc/plugins/dvz_mentions/core.php';
require MYBB_ROOT . 'inc/plugins/dvz_mentions/data.php';
require MYBB_ROOT . 'inc/plugins/dvz_mentions/formatting.php';
require MYBB_ROOT . 'inc/plugins/dvz_mentions/parsing.php';
require MYBB_ROOT . 'inc/plugins/dvz_mentions/alerts.php';

// hook files
require MYBB_ROOT . 'inc/plugins/dvz_mentions/hooks_frontend.php';
require MYBB_ROOT . 'inc/plugins/dvz_mentions/hooks_acp.php';

// hooks
\dvzMentions\addHooksNamespace('dvzMentions\Hooks');

// init
if (\dvzMentions\Alerts\myalertsIsIntegrable()) {
    \dvzMentions\Alerts\initMyalerts();
    \dvzMentions\Alerts\initLocations();
}

$GLOBALS['dvzMentionsPlaceholders'] = [];

// MyBB plugin system
function dvz_mentions_info()
{
    global $lang;

    $lang->load('dvz_mentions');

    return [
        'name'          => 'DVZ Mentions',
        'description'   => $lang->dvz_mentions_description . $GLOBALS['dvzMentionsDescriptionAppendix'],
        'website'       => 'https://devilshakerz.com/',
        'author'        => 'Tomasz \'Devilshakerz\' Mlynski',
        'authorsite'    => 'https://devilshakerz.com/',
        'version'       => '1.0.3',
        'codename'      => 'dvz_mentions',
        'compatibility' => '18*',
    ];
}

function dvz_mentions_install()
{
    global $db, $PL, $cache;

    dvz_mentions_admin_load_pluginlibrary();

    // settings
    $PL->settings(
        'dvz_mentions',
        'DVZ Mentions',
        'Settings for DVZ Mentions.',
        [
            'keep_prefix' => [
                'title'       => 'Keep username call prefix',
                'description' => 'Choose whether to display the "@" prefix in posts.',
                'optionscode' => 'yesno',
                'value'       => '1',
            ],
            'apply_username_style' => [
                'title'       => 'Apply username style',
                'description' => 'Choose whether to apply group-specific Username Style.',
                'optionscode' => 'yesno',
                'value'       => '1',
            ],
            'links_to_new_tabs' => [
                'title'       => 'Open profile links in new tabs',
                'description' => 'Choose whether profile links should be opened in separate tabs.',
                'optionscode' => 'yesno',
                'value'       => '0',
            ],
            'cs_collation' => [
                'title'       => 'Case-sensitive username collation',
                'description' => 'Choose whether the plugin should perform additional character case transformations to remain compatible with case-sensitive collations of the <code>username</code> column.',
                'optionscode' => 'yesno',
                'value'       => '0',
            ],
            'ignored_values' => [
                'title'       => 'Ignored usernames',
                'description' => 'Enter values ignored by the mention parser in separate lines.',
                'optionscode' => 'textarea',
                'value'       => '',
            ],
            'min_value_length' => [
                'title'       => 'Minimum username length',
                'description' => 'Choose the minimum length of values that can be parsed.',
                'optionscode' => 'numeric',
                'value'       => '3',
            ],
            'max_value_length' => [
                'title'       => 'Maximum value length',
                'description' => 'Choose the maximum length of values that can be parsed.',
                'optionscode' => 'numeric',
                'value'       => '30',
            ],
            'match_limit' => [
                'title'       => 'Match limit',
                'description' => 'Choose the maximum number of mentions in a single message over which mentions will not be parsed.',
                'optionscode' => 'numeric',
                'value'       => '10000',
            ],
            'query_limit' => [
                'title'       => 'Query limit',
                'description' => 'Choose the maximum number of mentions in the database query (usually one per page) over which mentions will not be parsed.',
                'optionscode' => 'numeric',
                'value'       => '10000',
            ],
        ]
    );

    // datacache
    $alertLocationsInstalled = array_filter(
        \dvzMentions\Alerts\getAvailableLocations(),
        '\\dvzMentions\\Alerts\\isLocationAlertTypePresent'
    );

    $cache->update('dvz_mentions', [
        'version' => dvz_mentions_info()['version'],
        'alertLocationsInstalled' => $alertLocationsInstalled,
    ]);
}

function dvz_mentions_uninstall()
{
    global $db, $PL, $cache;

    dvz_mentions_admin_load_pluginlibrary();

    // settings
    $PL->settings_delete('dvz_mentions', true);

    // datacache
    $cache->delete('dvz_mentions');
}

function dvz_mentions_is_installed()
{
    global $db;

    // manual check to avoid caching issues
    $query = $db->simple_select('settinggroups', 'gid', "name='dvz_mentions'");

    return (bool)$db->num_rows($query);
}

function dvz_mentions_activate()
{
    global $cache;

    $pluginCache = $cache->read('dvz_mentions');

    if (isset($pluginCache['version']) && version_compare($pluginCache['version'], dvz_mentions_info()['version']) == -1) {
        $pluginCache['version'] = dvz_mentions_info()['version'];

        $cache->update('dvz_mentions', $pluginCache);
    }
}

// helpers
function dvz_mentions_admin_load_pluginlibrary()
{
    global $lang;

    if (!defined('PLUGINLIBRARY')) {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (!file_exists(PLUGINLIBRARY)) {
        $lang->load('dvz_mentions');

        flash_message($lang->dvz_mentions_admin_pluginlibrary_missing, 'error');

        admin_redirect('index.php?module=config-plugins');
    } elseif (!$PL) {
        require_once PLUGINLIBRARY;
    }
}
