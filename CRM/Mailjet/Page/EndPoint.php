<?php

require_once 'CRM/Core/Page.php';

class CRM_Mailjet_Page_EndPoint extends CRM_Core_Page {

  function run() {
    $post = trim(file_get_contents('php://input'));
    if(empty($post)) {
      header('HTTP/1.1 421 No event');
       // => do action
      return;
    }

    //Decode Trigger Informations
    $trigger = json_decode($post, true);

    //No Informations sent with the Event
    if(!is_array($trigger) || !isset($trigger['event'])) {
      header('HTTP/1.1 422 Not ok');
      // TODO:: notifiy admin
      return;
    }

    $event = $trigger['event'];
    $email = $trigger['email'];
    $time = date('YmdHis', $trigger['time']);
    $mailingId = CRM_Utils_Array::value('customcampaign', $trigger); //CiviCRM mailling ID
    $mailjetCampaignId = CRM_Utils_Array::value('mj_campaign_id', $trigger);
    $mailjetContactId = CRM_Utils_Array::value('mj_contact_id' , $trigger);

    $mailjetEvent   = new CRM_Mailjet_DAO_Event();
    $mailjetEvent->mailing_id = $mailingId;
    $mailjetEvent->email = $email;
    $mailjetEvent->event = $event;
    $mailjetEvent->mj_campaign_id = $mailjetCampaignId;
    $mailjetEvent->mj_contact_id = $mailjetContactId;
    $mailjetEvent->time = $time;
    $mailjetEvent->data = serialize($trigger);
    $mailjetEvent->created_date = date('YmdHis');
    $mailjetEvent->save(); //log event


    if($event == 'typofix'){
      //we do not handle typofix
      // TODO:: notifiy admin
      return;
    }

    $params = array(
      'email' => $email,
    );
    $bounceType = array();
    CRM_Core_PseudoConstant::populate($bounceType, 'CRM_Mailing_DAO_BounceType', TRUE, 'id', NULL, NULL, NULL, 'name');
    $emailResult = civicrm_api3('Email', 'get', $params);
    if(CRM_Utils_Array::value('values', $emailResult)){
      $contactId = $emailResult['values'][$emailResult['id']]['contact_id'];
      $emailId = $emailResult['id'];
      $jobId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_MailingJob', $mailingId, 'id', 'mailing_id');
      $params = array(
        'job_id' => $jobId,
        'contact_id' => $contactId,
        'email_id' => $emailId,
      );
      $eventQueue = CRM_Mailing_Event_BAO_Queue::create($params);
      /*
      *  Event handler
      *  - please check https://www.mailjet.com/docs/event_tracking for further informations.
      */
      switch($trigger['event']) {
        case 'open':
        case 'click':
        case 'unsub':
        case 'typofix':
          break;
        //we treat bounce, span and blocked as bounce mailing in CiviCRM
        case 'bounce':
        case 'spam':
        case 'blocked':
          $bounce             = new CRM_Mailing_Event_BAO_Bounce();
          $bounce->time_stamp =  date('YmdHis', $time);
          $bounce->event_queue_id = $eventQueue->id;
          $bounceReason = NULL;
          if(isset($trigger['hard_bounce'])){
            $hardBounce = CRM_Utils_Array::value('hard_bounce', $trigger); //for bouncing , true if error was permanent
            $blocked = CRM_Utils_Array::value('blocked', $trigger); //  blocked : true if this bounce leads to recipient being blocked
            if($hardBounce){
              $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::HARD_BOUNCE];
            }else{
              $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SOFT_BOUNCE];
            }
          }else if(CRM_Utils_Array::value('source', $trigger)){ //for spaming
            $bounceReason = CRM_Utils_Array::value('source', $trigger); //bounce reason when spam occured
            $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::SPAM];
          }else{ //bounce = blocked
            $bounce->bounce_type_id = $bounceType[CRM_Mailjet_Upgrader::BLOCKED];
          }
          if(!$bounceReason){
            $bounce->bounce_reason  =  $trigger['error_related_to'] . " - " . $trigger['error'];
          }
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
          //TODO: handle error
          break;
        # No handler
        default:
          header('HTTP/1.1 423 No handler');
          // Log if there is no handler
          break;
      }
      header('HTTP/1.1 200 Ok');
    }else{
      // If an error occurs, tell Mailjet to retry later: header('HTTP/1.1 400 Error'); , Log/nofify if email not found
      header('HTTP/1.1 400 Error');
    }

    CRM_Utils_System::civiExit();
  }


}
