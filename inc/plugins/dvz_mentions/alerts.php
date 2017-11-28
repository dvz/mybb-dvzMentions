<?php

namespace dvzMentions\Alerts;

function getAvailableLocations(): array
{
    $directory = MYBB_ROOT . 'inc/plugins/dvz_mentions/alertLocations/';

    return array_map(
        'basename',
        glob($directory . '*', \GLOB_ONLYDIR)
    );
}

function getInstalledLocations(): array
{
    global $cache;

    return $cache->read('dvz_mentions')['alertLocationsInstalled'] ?? [];
}

function isLocationAlertTypePresent(string $locationName): bool
{
    if (\dvzMentions\Alerts\myalertsIsIntegrable()) {
        $alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        return $alertTypeManager->getByCode('dvz_mentions_' . $locationName) !== null;
    } else {
        return false;
    }
}

function installLocation(string $name)
{
    global $db, $cache;

    // add datacache value if not present
    $cacheEntry = $cache->read('dvz_mentions');

    if (!in_array($name, $cacheEntry['alertLocationsInstalled'])) {
        $cacheEntry['alertLocationsInstalled'][] = $name;
        $cache->update('dvz_mentions', $cacheEntry);
    }

    if (!\dvzMentions\Alerts\isLocationAlertTypePresent($name)) {
        $alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();
        $alertType = new \MybbStuff_MyAlerts_Entity_AlertType();

        $alertType->setCode('dvz_mentions_' . $name);

        $alertTypeManager->add($alertType);
    }
}

function uninstallLocation(string $name)
{
    global $db, $cache;

    // remove MyAlerts type
    $alertTypeManager = \MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    $alertTypeManager->deleteByCode('dvz_mentions_' . $name);

    // remove datacache value
    $cacheEntry = $cache->read('dvz_mentions');
    $key = array_search($name, $cacheEntry['alertLocationsInstalled']);

    if ($key !== false) {
        unset($cacheEntry['alertLocationsInstalled'][$key]);
        $cache->update('dvz_mentions', $cacheEntry);
    }
}

function initMyalerts()
{
    defined('MYBBSTUFF_CORE_PATH') or define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');
    defined('MYALERTS_PLUGIN_PATH') or define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');
    defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

    require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

    $classLoader = new \MybbStuff_Core_ClassLoader();
    $classLoader->registerNamespace('MybbStuff_MyAlerts', [MYALERTS_PLUGIN_PATH . '/src']);
    $classLoader->register();
}

function initLocations()
{
    global $plugins;

    foreach (\dvzMentions\Alerts\getInstalledLocations() as $locationName) {
        require_once MYBB_ROOT . 'inc/plugins/dvz_mentions/alertLocations/' . $locationName . '/init.php';
    }
}

function registerMyalertsFormatters()
{
    global $mybb, $lang;

    $formatterManager = \MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

    foreach (\dvzMentions\Alerts\getInstalledLocations() as $locationName) {
        $class = 'MybbStuff_MyAlerts_Formatter_dvzMentions' . ucfirst($locationName) . 'Formatter';

        $formatter = new $class($mybb, $lang, 'dvz_mentions_' . $locationName);

        $formatterManager->registerFormatter($formatter);
    }
}

function myalertsIsIntegrable(): bool
{
    global $cache;

    static $status;

    if (!$status) {
        $status = false;

        $pluginsCache = $cache->read('plugins');

        if (!empty($pluginsCache['active']) && in_array('myalerts', $pluginsCache['active'])) {
            if ($euantor_plugins = $cache->read('euantor_plugins')) {
                if (isset($euantor_plugins['myalerts']['version'])) {
                    $version = explode('.', $euantor_plugins['myalerts']['version']);
                    if ($version[0] == '2' && $version[1] == '0') {
                        $status = true;
                    }
                }
            }
        }
    }

    return $status;
}

function queueAlerts(string $locationName, array $alertDetails, array $locationData, array $mentionedUserIds, int $authorUserId = null)
{
    if ($authorUserId !== null) {
        $key = array_search($authorUserId, $mentionedUserIds);

        if ($key !== false) {
            unset($mentionedUserIds[$index]);
        }
    }

    $alertType = \MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('dvz_mentions_' . $locationName);

    $alerts = [];

    foreach ($mentionedUserIds as $userId) {
        if (call_user_func_array('dvzMentions\\Alerts\\' . ucfirst($locationName) . '\\alertPossible', [$locationData, $userId])) {
            if ($alertType && $alertType->getEnabled()) {
                $alert = new \MybbStuff_MyAlerts_Entity_Alert();

                $alert->setType($alertType)
                    ->setUserId($userId)
                    ->setExtraDetails($alertDetails);

                if (!empty($locationData['object_id'])) {
                    $alert->setObjectId($locationData['object_id']);
                }

                if (!empty($locationData['author'])) {
                    $alert->setFromUserId($locationData['author']);
                }

                $alerts[] = $alert;
            }
        }
    }

    if ($alerts) {
        \MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
    }
}

function deleteAlerts(array $alerts): bool
{
    return \MybbStuff_MyAlerts_AlertManager::getInstance()->deleteAlerts($alerts);
}

function deleteAlertsByLocationAndObjectId(string $locationName, int $objectId): bool
{
    global $db;

    $alertTypeId = \MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('dvz_mentions_' . $locationName)->getId();

    return $db->delete_query('alerts', 'alert_type_id=' . (int)$alertTypeId . ' AND object_id=' . (int)$objectId);
}
