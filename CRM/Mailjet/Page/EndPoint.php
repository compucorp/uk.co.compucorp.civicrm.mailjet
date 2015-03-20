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
      watchdog('mailjet', 'Empty campaign STATs', NULL, WATCHDOG_ERROR);
      return;
    }

    //Decode Trigger Informations
    $trigger = json_decode($post, true);
    watchdog('mailjet', '<pre>' . print_r( $trigger, true) . '</pre>', NULL, WATCHDOG_DEBUG);

    //No Informations sent with the Event
    if(!is_array($trigger) || !isset($trigger['event'])) {
      header('HTTP/1.1 422 Not ok');
      watchdog('mailjet', 'No event info found in STATs', NULL, WATCHDOG_ERROR);
      return;
    }

    $event = $trigger['event'];
    $email = $trigger['email'];
    $time = date('YmdHis', $trigger['time']);
    if(is_null($trigger['customcampaign'])){
      watchdog('mailjet', 'customcampaign is empty', NULL, WATCHDOG_DEBUG);
      return;
    }
    $campaignJobId = strstr($trigger['customcampaign'], 'MJ', true);
    if($campaignJobId) {
      $mailJobResult = civicrm_api3('MailingJob', 'getvalue', array('id' => $campaignJobId, 'return' => 'mailing_id'));
    }
    $mailingId = $mailJobResult; //CiviCRM mailling ID
    if($mailingId){ //we only process if mailing_id exist - marketing email
      $mailjetCampaignId = CRM_Utils_Array::value('mj_campaign_id', $trigger);
      $mailjetContactId = CRM_Utils_Array::value('mj_contact_id' , $trigger);

      $mailjetEvent = new CRM_Mailjet_DAO_Event();
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

      $emailResult = civicrm_api3('Email', 'get', array('email' => $email, 'sequential' => 1));
      if(isset($emailResult['values']) && !empty($emailResult['values'])){
        //we always get the first result
        $contactId = $emailResult['values'][0]['contact_id'];
        $emailId = $emailResult['values'][0]['id'];
        $params = array(
          'mailing_id' => $mailingId,
          'job_id' => $campaignJobId,
          'email' => $email,
          'contact_id' => $contactId,
          'email_id' => $emailId,
          'date_ts' =>  $trigger['time'],
        );

        $query = "SELECT eq.id
          FROM civicrm_mailing_event_bounce eb
          LEFT JOIN civicrm_mailing_event_queue eq ON eq.id = eb.event_queue_id
          WHERE 1
          AND eq.job_id = $campaignJobId
          AND eq.email_id = $emailId
          AND eq.contact_id = $contactId";
        $dao = CRM_Core_DAO::executeQuery($query);

        while ($dao->fetch()) {
          break;
        }

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
            }watchdog('mailjet', '<pre>' . print_r( $params, true) . '</pre>', NULL, WATCHDOG_ERROR);
            CRM_Mailjet_BAO_Event::recordBounce($params);
            //TODO: handle error
            break;
          # No handler
          default:
            header('HTTP/1.1 423 No handler');
            // Log if there is no handler
            break;
        }
        header('HTTP/1.1 200 Ok');
      }
    }else{ //assumed if there is not mailing_id, this should be a transaction email
      //TODO::process a transaction email
    }
    CRM_Utils_System::civiExit();
  }


}
