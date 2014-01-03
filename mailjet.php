<?php

require_once 'mailjet.civix.php';

/**
 * Implementation of hook_civicrm_postProcess
 */
function mailjet_civicrm_postProcess( $formName, &$form ) {
  if($formName == 'CRM_Mailing_Form_Schedule'){
    $mailingID = $form->_mailingID;
    $params = array(
      'id' => $mailingID,
    );
    $result = civicrm_api3('Mailing', 'get', $params);
    $mailing = $result['values'][$mailingID];

    $type = 'Include';
    $mailingGroup = new CRM_Mailing_DAO_MailingGroup();
    $group = CRM_Contact_DAO_Group::getTableName();
    $query = "SELECT entity_id
                      FROM   civicrm_mailing_group
                      WHERE  mailing_id = {$mailingID}
                      AND    group_type = '$type'
                      AND    entity_table = '$group'";

    $mailingGroup->query($query);
    $contactIds = array();
    while ($mailingGroup->fetch()) {
      $groupContacts = CRM_Contact_BAO_Group::getGroupContacts($mailingGroup->entity_id);
      foreach ($groupContacts as $contactId => $groupContact) {
        $contactIds[] = $contactId;
      }
    }
    $emails = array();
    foreach ($contactIds as $id) {
      $params = array(
        'contact_id' => $id
      );
      $contactResult = civicrm_api3('Email', 'get', $params);
      $emails[] = $contactResult['values'][$id]['email'];

    }
    $emails = implode(",", $emails);

    include_once('packages/mailjet-0.1/php-mailjet.class-mailjet-0.1.php');
    // Create a new Mailjet Object
    $mj = new Mailjet();
    $name = $mailing['name'];
    //The List name field may only contain alpha-numeric characters.
    //$listName = preg_replace("/[^A-Za-z0-9 ]/", '',$name);
    $date = new DateTime();
    $params = array(
        'method' => 'POST',
        'label' => $mailing['name'],
        'name' =>  $date->getTimestamp() //preg_repace is currently not working so use timestamp for now
    );
    $response = $mj->listsCreate($params);
    $listId = $response->list_id;

    $params = array(
      'method' => 'POST',
      'contacts' => $emails,
      'id' => $listId
    );
    $response = $mj->listsAddManyContacts($params);

    $params = array(
        'method' => 'POST',
        'subject' => $mailing['name'],
        'list_id' => $listId,
        'lang' => 'en',
        'from' => $mailing['from_email'],
        'from_name' => $mailing['from_name'],
        'reply_to' => $mailing['replyto_email'],
        'footer' => 'default'
    );
    $response = $mj->messageCreateCampaign($params);
    $campaignId = $response->campaign->id;

    $mailjetDAO = new CRM_Mailjet_DAO_Mailjet();
    $mailjetDAO->campaign_id = $campaignId;
    if(!$mailjetDAO->find(TRUE)){
      $mailjetDAO->mailing_id = $mailingID;
      $mailjetDAO->save();
    }
  }
}

/**
 * Implementation of hook_civicrm_alterMailParams( )
 * To add Mailjet headers in mail
 * Hacked overrided civicrm_alterMailParams hooks // TODO::contribute back to the core
 */
function mailjet_civicrm_alterMailParams(&$params, $context, $jobId) {
  if(isset($jobId)){
    $apiParams = array(
      'id' => $jobId
    );
    $mailJobResult = civicrm_api3('MailingJob', 'get', $apiParams);
    $mailingId = $mailJobResult['values'][$jobId]['mailing_id'];
    $mailjetParams  = array('mailing_id' => $mailingId);
    $mailjetObect = array();
    CRM_Mailjet_BAO_Mailjet::retrieve($params, $mailjetObect );
    $campaignId = CRM_Utils_Array::value('campaign_id', $mailjetObect);
    if($campaignId){
      include_once('packages/mailjet-0.1/php-mailjet.class-mailjet-0.1.php');
      // Create a new Mailjet Object
      $mj = new Mailjet();
      $mailJetParams = array(
        'id' => $campaignId
      );
      $response = $mj->messageCampaigns($mailJetParams);
      if($response->status == 'OK' && $response->cnt == 1){
        $campaign = $response->result[0];
        $params['headers']['X-Mailjet-Campaign'] = $campaign->subject;
      }else{
        //TODO:: log error
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
