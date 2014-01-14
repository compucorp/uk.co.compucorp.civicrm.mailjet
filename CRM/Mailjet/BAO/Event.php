<?php

class CRM_Mailjet_BAO_Event extends CRM_Mailjet_DAO_Event {

  static function recordBounce($params, isSpam ) {

    $mailingId = CRM_Utils_Array::value('customcampaign', $params); //CiviCRM mailling ID

    $contactId = CRM_Utils_Array::value('contact_id' , $params);
    $emailId =  CRM_Utils_Array::value('email_id' , $params);
    $jobId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_MailingJob', $mailingId, 'id', 'mailing_id');
    $params = array(
      'job_id' => $jobId,
      'contact_id' => $contactId,
      'email_id' => $emailId,
    );
    $bounceType = array();
    CRM_Core_PseudoConstant::populate($bounceType, 'CRM_Mailing_DAO_BounceType', TRUE, 'id', NULL, NULL, NULL, 'name');
    $bounce  = new CRM_Mailing_Event_BAO_Bounce();
    $bounce->time_stamp =  date('YmdHis', CRM_Utils_Array::value('date_ts', $params));
    $bounce->event_queue_id = $eventQueue->id;
    $bounceReason = NULL;
    if($isSpam){
      $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SPAM];
      $bounceReason = CRM_Utils_Array::value('source', $trigger); //bounce reason when spam occured
    }else{
     $hardBounce = CRM_Utils_Array::value('hard_bounce', $params);
     $blocked = CRM_Utils_Array::value('blocked', $params); //  blocked : true if this bounce leads to recipient being blocked
      if($hardBounce && $blocked){
        $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::BLOCKED];
      }else if($hardBounce && !$blocked){
        $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::HARD_BOUNCE];
      }else{
        $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SOFT_BOUNCE];
      }
      $bounce->bounce_reason  =  $trigger['error_related_to'] . " - " . $trigger['error'];
      $bounce->save();
      if($bounce->bounce_type_id == $bounceType[CRM_Mailjet_Upgrader::SOFT_BOUNCE]){
        //put the email into on hold
        $params = array(
          'id' => $emailId,
          'email' => $email,
          'on_hold' => 1,
          'hold_date' =>  $time,
        );
        civicrm_api3('Email', 'create', $params);
      }else {
        $params = array(
          'id' => $contactId,
          'do_not_email' => 1,
        );
        civicrm_api3('Contact', 'create', $params);
      }
    return TRUE;
  }



}
