<?php

require_once 'mailjet.civix.php';

/**
 * Implementation of hook_civicrm_alterMailParams( )
 * To add Mailjet headers in mail
 */
function mailjet_civicrm_alterMailParams( &$params, $context ) {
  $jobId = CRM_Utils_Array::value('job_id', $params);
  if(isset($jobId)){
    $params['headers']['X-Mailjet-Campaign'] = CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($jobId);
  }
}


/**
 * Implementation of hook_civicrm_pageRun
 *
 * Handler for pageRun hook.
 */
function mailjet_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Mailing_Page_Report') {
    $mailingId = $page->_mailing_id;
    $mailingJobs = civicrm_api3('MailingJob', 'get', $params = array('mailing_id' => $mailingId));
    foreach ($mailingJobs['values'] as $jobId => $job) {
      if (isset($job['job_type']) && $job['job_type'] == 'child') {
        require_once('packages/mailjet-v3/php-mailjet-v3-simple.class.php');
        // Create a new Mailjet Object
        $mj = new Mailjet(MAILJET_API_KEY, MAILJET_SECRET_KEY);
        $mj->debug = 0;
        $campaignId = CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($jobId);
              $mailJetParams = array(
          "method" => "VIEW",
          "ID" => $campaignId
        );

        // Get campaign statistics.
        $campaingStatistics = $mj->campaignstatistics($mailJetParams);
        if ($campaingStatistics->Count && $campaingStatistics->Total == 1) {
          $campaignReport = array_pop($campaingStatistics->Data);
        }
        // Get general campaign info.
        $campaignInfo = $mj->campaign($mailJetParams);
        if ($campaignInfo->Count && $campaignInfo->Total == 1) {
          $campaignInfo = array_pop($campaignInfo->Data);
          $campaignReport->SpamassScore = $campaignInfo->SpamassScore;
        }

        // Get the message statistics for the current Campaign ID.
        $messageStatistics = $mj->messagestatistics(array(
          'method' => 'VIEW',
          'CampaignID' => $campaignInfo->ID,
        ));
        // If the retrievel of message statistics was successful, then we add
        // them to the report info.
        if ($messageStatistics) {
          $messageStatistics = $messageStatistics->Data[0];
        }
        // Add message statistics to the report info.
        $campaignReport->AverageClickDelay = $messageStatistics->AverageClickDelay;
        $campaignReport->AverageOpenDelay = $messageStatistics->AverageOpenDelay;

        $totalEmailsProcessed = $campaignReport->ProcessedCount;
        $campaignReport->DeliverRate = ($campaignReport->DeliveredCount * 100) / $totalEmailsProcessed;
        $campaignReport->QueuRate = ($campaignReport->QueuedCount * 100) / $totalEmailsProcessed;
        $campaignReport->OpenRate = ($campaignReport->OpenedCount * 100) / $totalEmailsProcessed;
        $campaignReport->ClickRate = ($campaignReport->ClickedCount * 100) / $totalEmailsProcessed;
        $campaignReport->BounceRate = ($campaignReport->BouncedCount * 100) / $totalEmailsProcessed;
        $campaignReport->BlockRate = ($campaignReport->BlockedCount * 100) / $totalEmailsProcessed;
        $campaignReport->SpamRate = ($campaignReport->SpamComplaintCount * 100) / $totalEmailsProcessed;
        $campaignReport->UnsubscribeRate = ($campaignReport->UnsubscribedCount * 100) / $totalEmailsProcessed;

        if (!empty($campaignReport)) {
          $page->assign('mailing_id', $mailingId);
          $page->assign('mailjet_stats', get_object_vars($campaignReport));

          CRM_Core_Region::instance('page-header')->add(array(
            'template' => 'CRM/Mailjet/Page/Report.tpl',
          ));
        }
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_config
 */
function mailjet_civicrm_config(&$config) {
  _mailjet_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function mailjet_civicrm_xmlMenu(&$files) {
  _mailjet_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function mailjet_civicrm_install() {
  return _mailjet_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function mailjet_civicrm_uninstall() {

  return _mailjet_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function mailjet_civicrm_enable() {
  return _mailjet_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function mailjet_civicrm_disable() {
  return _mailjet_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function mailjet_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mailjet_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function mailjet_civicrm_managed(&$entities) {
  return _mailjet_civix_civicrm_managed($entities);
}
