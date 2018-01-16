<?php

$l['dvz_mentions_description'] = 'Parses <i>@username</i> mentions into profile links. Integrates with <i>MyAlerts</i>.';
$l['dvz_mentions_alerts'] = '<br><br><b>MyAlerts integrations:</b>';
$l['dvz_mentions_alerts_install'] = 'install';
$l['dvz_mentions_alerts_uninstall'] = 'uninstall';
$l['dvz_mentions_alerts_installed'] = 'MyAlerts integration has been installed.';
$l['dvz_mentions_alerts_uninstalled'] = 'MyAlerts integration has been uninstalled.';

$l['dvz_mentions_admin_pluginlibrary_missing'] = 'Add <a href="https://mods.mybb.com/view/pluginlibrary">PluginLibrary</a> in order to use the plugin.';

$l['setting_group_dvz_mentions'] = 'DVZ Mentions';
$l['setting_group_dvz_mentions_desc'] = 'Settings for DVZ Mentions.';

$l['setting_dvz_mentions_keep_prefix'] = 'Keep username call prefix';
$l['setting_dvz_mentions_keep_prefix_desc'] = 'Choose whether to display the "@" prefix in posts.';

$l['setting_dvz_mentions_apply_username_style'] = 'Apply username style';
$l['setting_dvz_mentions_apply_username_style_desc'] = 'Choose whether to apply group-specific Username Style.';

$l['setting_dvz_mentions_links_to_new_tabs'] = 'Open profile links in new tabs';
$l['setting_dvz_mentions_links_to_new_tabs_desc'] = 'Choose whether profile links should be opened in separate tabs.';

$l['setting_dvz_mentions_cs_collation'] = 'Case-sensitive username collation';
$l['setting_dvz_mentions_cs_collation_desc'] = 'Choose whether the plugin should perform additional character case transformations to remain compatible with case-sensitive collations of the <code>username</code> column.';

$l['setting_dvz_mentions_ignored_values'] = 'Ignored usernames';
$l['setting_dvz_mentions_ignored_values_desc'] = 'Enter values ignored by the mention parser in separate lines.';

$l['setting_dvz_mentions_min_value_length'] = 'Minimum username length';
$l['setting_dvz_mentions_min_value_length_desc'] = 'Choose the minimum length of values that can be parsed.';

$l['setting_dvz_mentions_max_value_length'] = 'Maximum value length';
$l['setting_dvz_mentions_max_value_length_desc'] = 'Choose the maximum length of values that can be parsed.';

$l['setting_dvz_mentions_match_limit'] = 'Match limit';
$l['setting_dvz_mentions_match_limit_desc'] = 'Choose the maximum number of mentions in a single message over which mentions will not be parsed.';

$l['setting_dvz_mentions_query_limit'] = 'Query limit';
$l['setting_dvz_mentions_query_limit_desc'] = 'Choose the maximum number of mentions in the database query (usually one per page) over which mentions will not be parsed.';
