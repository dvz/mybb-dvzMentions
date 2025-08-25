<?php

class MybbStuff_MyAlerts_Formatter_dvzMentionsPostFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert): string
    {
        $alertDetails = $alert->getExtraDetails();

        require_once MYBB_ROOT . 'inc/class_parser.php';
        $parser = new postParser;

        return $this->lang->sprintf(
            $this->lang->myalerts_dvz_mentions_post_alert,
            $outputAlert['from_user'],
            htmlspecialchars_uni($parser->parse_badwords($alertDetails['subject']))
        );
    }

    public function init()
    {
        $this->lang->load('dvz_mentions_alerts_post');
    }

    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert): string
    {
        $objectId = $alert->getObjectId();
        $alertDetails = $alert->getExtraDetails();

        $postLink = $this->mybb->settings['bburl'] . '/' . get_post_link(
            $objectId,
            (int)$alertDetails['tid']
        ) . '#pid' . $objectId;

        return $postLink;
    }
}
