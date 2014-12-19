<?php

class CRM_Mailjet_BAO_Event extends CRM_Mailjet_DAO_Event {

  /*
   * Get a MJ campaign ID based on an job id.
   *
   * @param integer
   *  The job id for the required mailing.
   *
   * @return string
   *  The MJ custom campaign ID.
   */
  static function getMailjetCustomCampaignId($jobId) {
    if ($jobId !== NULL) {
      // Get the mailing job.
      $mailingJob = civicrm_api3('MailingJob', 'get', $params = array('id' => $jobId));
      if (isset($mailingJob['values'][$jobId]['job_type'])) {
        $jobType = $mailingJob['values'][$jobId]['job_type'];
        if ($jobType == 'child') {
          $timestamp = strtotime($mailingJob['values'][$jobId]['scheduled_date']);
          return $jobId . 'MJ' . $timestamp;
        }
      }
    }
    $timestamp = REQUEST_TIME;
    return 0 . 'MJ' . $timestamp;
  }

  /**
   * Record a bounce to the Database with the received data.
   *
   * @param $bounceData array
   *  The required bounce data for processing.
   *
   * @return bool
   */
  static function recordBounce($bounceData) {
    // Parse the bounce array, and retrieve all necessary data for processing.
    $isSpam = CRM_Utils_Array::value('is_spam', $bounceData);
    $contactId = CRM_Utils_Array::value('contact_id', $bounceData);
    $emailId = CRM_Utils_Array::value('email_id', $bounceData);
    $email = CRM_Utils_Array::value('email', $bounceData);
    $jobId = CRM_Utils_Array::value('job_id', $bounceData);
    // Create a new MJ event.
    $eqParams = array(
      'job_id' => $jobId,
      'contact_id' => $contactId,
      'email_id' => $emailId,
    );
    $eventQueue = CRM_Mailing_Event_BAO_Queue::create($eqParams);
    // Process the bounce data, and mark the user accordingly.
    $time = date('YmdHis', CRM_Utils_Array::value('date_ts', $bounceData));
    $bounceType = array();
    CRM_Core_PseudoConstant::populate($bounceType, 'CRM_Mailing_DAO_BounceType', TRUE, 'id', NULL, NULL, NULL, 'name');
    $bounce = new CRM_Mailing_Event_BAO_Bounce();
    $bounce->time_stamp = $time;
    $bounce->event_queue_id = $eventQueue->id;
    if ($isSpam) {
      $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SPAM];
      $bounce->bounce_reason = CRM_Utils_Array::value('source', $bounceData);
    }
    else {
      $hardBounce = CRM_Utils_Array::value('hard_bounce', $bounceData);
      $blocked = CRM_Utils_Array::value('blocked', $bounceData);
      if ($hardBounce && $blocked) {
        $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::BLOCKED];
      }
      else {
        if ($hardBounce && !$blocked) {
          $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::HARD_BOUNCE];
        }
        else {
          $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SOFT_BOUNCE];
        }
      }
      $bounce->bounce_reason = $bounceData['error_related_to'] . " - " . $bounceData['error'];
    }
    // Save the bounce.
    $bounce->save();

    // If the current bounce is marked as a soft bounce (that might be sent
    // eventually) we only mark the email as being on hold.
    if ($bounce->bounce_type_id == $bounceType[CRM_Mailjet_Upgrader::SOFT_BOUNCE]) {
      //put the email into on hold
      $contactParams = array(
        'id' => $emailId,
        'email' => $email,
        'on_hold' => 1,
        'hold_date' => $time,
      );
      $entity = 'Email';
    }
    // If the current bounce is marked as a hard bounce (that won't send due to
    // invalid email, domain, etc) then we mark the user as "don't mail".
    else {
      $contactParams = array(
        'id' => $contactId,
        'do_not_email' => 1,
      );
      $entity = 'Contact';
    }
    // Mark the user accordingly.
    $updateContact = civicrm_api3($entity, 'create', $contactParams);
    // If there was no error while updating the contact, then the bounce save
    // was successful.
    if (empty($updateContact['is_error'])) {
      return TRUE;
    }
    // If some problems were encountered while updating the contact, regardless
    // of the problem we mark the processing as unsuccessful.
    return FALSE;
  }
}