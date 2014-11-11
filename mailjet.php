<?php

require_once 'mailjet.civix.php';

/**
 * Implementation of hook_civicrm_alterMailParams( )
 * To add Mailjet headers in mail
 */
function mailjet_civicrm_alterMailParams(&$params, $context) {
  $jobId = CRM_Utils_Array::value('job_id', $params); //CiviCRM job ID
  if(isset($jobId)){
    $apiParams = array(
      'id' => $jobId
    );
    $mailJobResult = civicrm_api3('MailingJob', 'get', $apiParams);
    $mailingId = $mailJobResult['values'][$jobId]['mailing_id'];
    $params['headers']['X-Mailjet-Campaign'] = CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($jobId);
  }
}


/**
 * Implementation of hook_civicrm_pageRun
 *
 * Handler for pageRun hook.
 */
function mailjet_civicrm_pageRun(&$page) {
  if(get_class($page) == 'CRM_Mailing_Page_Report'){
    $mailingId = $page->_mailing_id;
    $mailingJobs = civicrm_api3('MailingJob', 'get', $params = array('mailing_id' => $mailingId));

        $jobId = 0;
        foreach($mailingJobs['values'] as $key => $job){
                if($job['job_type'] == 'child'){
                        $jobId = $key;

    require_once('packages/mailjet-v3/php-mailjet-v3-simple.class.php');
    // Create a new Mailjet Object
    $mj = new Mailjet(MAILJET_API_KEY, MAILJET_SECRET_KEY);
    $mj->debug = 1;
    $campaignId = CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($jobId);
    $mailJetParams = array(
       "method" => "VIEW",
       "ID" => $campaignId
    );
    $response = $mj->campaign($mailJetParams);
    if(!empty($response)){
      if($response->Count && $response->Total == 1){
        $campaign = $response->Data[0];
        $statsResponse = $mj->campaignstatistics($mailJetParams);
        if($statsResponse->Count && $statsResponse->Total == 1){
          $stats = $statsResponse->Data[0];
          $page->assign('mailing_id', $mailingId);
          $page->assign('mailjet_stats', get_object_vars($stats));
        }
      }
    }
        }
        }
    CRM_Core_Region::instance('page-header')->add(array(
      'template' => 'CRM/Mailjet/Page/Report.tpl',
    ));
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
