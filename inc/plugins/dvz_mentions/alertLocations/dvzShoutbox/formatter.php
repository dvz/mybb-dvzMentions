<?php

class MybbStuff_MyAlerts_Formatter_dvzMentionsDvzShoutboxFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert): string
    {
        return $this->lang->sprintf(
            $this->lang->myalerts_dvz_mentions_dvzShoutbox_alert,
            $outputAlert['from_user'],
            $alert->getObjectId()
        );
    }

    public function init()
    {
        $this->lang->load('dvz_mentions_alerts_dvzShoutbox');
    }

    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
    {
        $objectId = (int)$alert->getObjectId();

        $link = $this->mybb->settings['bburl'] . '/index.php?action=shoutbox_archive&sid=' . $objectId . '#sid' . $objectId;

        return $link;
    }
}
