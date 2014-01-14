<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CRM/Core/Page.php';

class CRM_Mailjet_Page_EndPoint extends CRM_Core_Page {

  function run() {
    $post = trim(file_get_contents('php://input'));
    if(empty($post)) {
      header('HTTP/1.1 421 No event');
       // => do action
      return;
    }

    watchdog('debug', $post);

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
    $emailResult = civicrm_api3('Email', 'get', $params);
    if(CRM_Utils_Array::value('values', $emailResult)){
      $contactId = $emailResult['values'][$emailResult['id']]['contact_id'];
      $emailId = $emailResult['id'];
      $params = array(
        'mailing_id' => $mailingId,
        'contact_id' => $contactId,
        'email_id' => $emailId,
        'date_ts' =>  $trigger['time'],
      );
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
          $params['hard_bounce'] =  CRM_Utils_Array::value('hard_bounce', $trigger);
          $params['blocked'] = CRM_Utils_Array::value('blocked', $trigger);
          $params['source'] = CRM_Utils_Array::value('source', $trigger);
          $params['error_related_to'] =  CRM_Utils_Array::value('error_related_to', $trigger);
          $params['error'] =   CRM_Utils_Array::value('error', $trigger);
          if(!empty($params['source'])){
            $params['is_spam'] = TRUE;
          }else{
            $params['is_spam'] = FALSE;
          }
          CRM_Mailjet_BAO_Event::recordBounce($params);
          //TODO: handle error
          break;
        # No handler
        default:k
          header('HTTP/1.1 423 No handler');
          // Log if there is no handler
          break;
      }
      header('HTTP/1.1 200 Ok');
    }
    CRM_Utils_System::civiExit();
  }


}
