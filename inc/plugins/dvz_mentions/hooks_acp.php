<?php

namespace dvzMentions\Hooks;

function admin_config_settings_change()
{
    global $lang;
    $lang->load('dvz_mentions');
}

function admin_config_settings_start()
{
    return \dvzMentions\Hooks\admin_config_settings_change();
}

function admin_config_plugins_begin()
{
    global $mybb, $lang;

    if (\dvzMentions\Alerts\myalertsIsIntegrable()) {
        $availableLocations = \dvzMentions\Alerts\getAvailableLocations();
        $installedLocations = \dvzMentions\Alerts\getInstalledLocations();

        if ($availableLocations) {
            $lang->load('dvz_mentions');

            // location installation handling
            $locationName = $mybb->get_input('dvz_mentions_alerts_install');

            if ($locationName && \verify_post_check($mybb->get_input('my_post_key'))) {
                if (in_array($locationName, $availableLocations)) {
                    if (!in_array($locationName, $installedLocations)) {
                        \dvzMentions\Alerts\installLocation($locationName);
                        \flash_message($lang->dvz_mentions_alerts_installed, 'success');
                        \admin_redirect('index.php?module=config-plugins');
                    }
                }
            }

            // location uninstallation handling
            $locationName = $mybb->get_input('dvz_mentions_alerts_uninstall');

            if ($locationName && \verify_post_check($mybb->get_input('my_post_key'))) {
                if (in_array($locationName, $availableLocations)) {
                    if (in_array($locationName, $installedLocations)) {
                        \dvzMentions\Alerts\uninstallLocation($locationName);
                        \flash_message($lang->dvz_mentions_alerts_uninstalled, 'success');
                        \admin_redirect('index.php?module=config-plugins');
                    }
                }
            }

            // location list
            $appendix = $lang->dvz_mentions_alerts;

            $locationList = [];

            foreach ($availableLocations as $locationName) {
                $installed = in_array($locationName, $installedLocations);

                $displayLocationName = $installed ? '<b>' . $locationName . '</b>' : $locationName;
                $actionUrl = 'index.php?module=config-plugins&amp;dvz_mentions_alerts_' . ($installed ? 'uninstall' : 'install') . '=' . \htmlspecialchars_uni($locationName) . '&amp;my_post_key=' . $mybb->post_code;
                $actionText = $installed ? $lang->dvz_mentions_alerts_uninstall : $lang->dvz_mentions_alerts_install;

                $locationList[] = $displayLocationName . ' <a href="' . $actionUrl . '">[' . $actionText . ']</a>';
            }

            $appendix .= ' ' . implode(' &middot; ', $locationList) . '<br>';

            $GLOBALS['dvzMentionsDescriptionAppendix'] .= $appendix;
        }
    }
}
