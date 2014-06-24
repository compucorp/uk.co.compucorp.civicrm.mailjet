<?php

/**
 * Mailjet.ProcessBounces API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_mailjet_processbounces_spec(&$spec) {}

/**
 * Mailjet.ProcessBounces API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_mailjet_processbounces($params) {
  $lock = new CRM_Core_Lock('civimail.job.MailjetProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another MailjetProcessor process is running');
  }
  $mailingId = CRM_Utils_Array::value('mailing_id', $params);
  //G: this is called when click on "Manually refresh Mailjet's stats" button
  if (!CRM_Utils_Mail_MailjetProcessor::processBounces($mailingId)) {
    $lock->release();
    return civicrm_api3_create_error('Process Bounces failed');
  }
  $lock->release();

  // FIXME: processBounces doesn't return true/false on success/failure
  $values = array();
  return civicrm_api3_create_success($values, $params, 'mailjet', 'bounces');
}

