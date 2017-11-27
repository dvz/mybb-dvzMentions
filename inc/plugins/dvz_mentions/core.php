<?php

namespace dvzMentions;

function getMentionedUserIds(string $message): array
{
    $selectors = \dvzMentions\Parsing\getUniqueUserSelectorsFromMatches(
        \dvzMentions\Parsing\getMatches($message)
    );

    $userIds = $selectors['userIds'];

    $users = \dvzMentions\Data\getUsersBySelectors([
        'usernames' => $selectors['usernames'],
    ]);

    $userIds = array_unique(
        array_merge($userIds, array_column($users['byUsername'], 'uid'))
    );

    return $userIds;
}

function getFormattedMessageFromPlaceholders(string $message, array $placeholders, int $limit = null): string
{
    $selectors = \dvzMentions\Parsing\getUniqueUserSelectorsFromMatches($placeholders);

    if ($limit !== null && (count($selectors['userIds']) + count($selectors['usernames'])) > $limit) {
        $users = [];
    } else {
        $users = \dvzMentions\Data\getUsersBySelectors($selectors, \dvzMentions\Formatting\GetUserFieldList());
    }

    return \dvzMentions\Formatting\getFormattedMessageFromPlaceholdersAndUsers($message, $placeholders, $users);
}

function getIgnoredUsernames()
{
    static $ignoredUsernames;

    if (!$ignoredUsernames) {
        $ignoredUsernames = array_map('trim', \dvzMentions\getDelimitedSettingValues('ignored_values'));
    }

    return $ignoredUsernames;
}

// common
function isStaticRender(): bool
{
    static $status;

    if (!$status) {
        $status = !defined('THIS_SCRIPT') || !in_array(THIS_SCRIPT, [
            'xmlhttp.php',
            'newreply.php',
        ]);
    }

    return $status;
}

function addHooks(array $hooks, string $namespace = null)
{
    global $plugins;

    if ($namespace) {
        $prefix = $namespace . '\\';
    } else {
        $prefix = null;
    }

    foreach ($hooks as $hook) {
        $plugins->add_hook($hook, $prefix . $hook);
    }
}

function addHooksNamespace(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;
        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $plugins->add_hook($hookName, $namespace . '\\' . $hookName);
        }
    }
}

function getSettingValue(string $name): string
{
    global $mybb;
    return $mybb->settings['dvz_mentions_' . $name];
}

function getCsvSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(explode(',', getSettingValue($name)));
    }

    return $values[$name];
}

function getDelimitedSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(preg_split("/\\r\\n|\\r|\\n/", getSettingValue($name)));
    }

    return $values[$name];
}
