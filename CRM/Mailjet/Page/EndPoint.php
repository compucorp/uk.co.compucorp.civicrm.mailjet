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
  /**
   * Callback Endpoint for MailJet. This end point is called after a mailing
   * campaign was successfully sent to MailJet. Once MailjJet finished processing
   * the mailing, it will call our end point with campaign statistics (bounces,
   * blocked emails, etc). We process this statistics and save relevant data in
   * our own DB. By doing this we can built our mailing reports.
   */
  function run() {
    // MJ responds with a json on POST.
    $post = trim(file_get_contents('php://input'));
    // As of the MJ ducumentation, if we send any other header than 200, then
    // the call will be interrupted.
    if(empty($post)) {
      header('HTTP/1.1 421 No event');
      // Log that MJ didn't send anything on POST.
      watchdog('mailjet error', 'MailJet POST is empty.', NULL, WATCHDOG_WARNING);
      return;
    }

    // Decode Trigger Information.
    $trigger = json_decode($post, true);

    // No Information sent with the Event.
    if(!is_array($trigger) || !isset($trigger['event'])) {
      header('HTTP/1.1 422 Not ok');
      // Log a faulty call from MJ.
      watchdog('mailjet error', 'No information was sent to the End point with the MailJet event.', NULL, WATCHDOG_WARNING);
      return;
    }
    // Get the Event data.
    $event = $trigger['event'];
    $email = $trigger['email'];
    $time = date('YmdHis', $trigger['time']);
    // Get the mailing ID.
    $mailingId = CRM_Utils_Array::value('customcampaign', $trigger); //CiviCRM mailling ID
    if ($mailingId) {
      // Get the MJ campaign and contact ID.
      $mailjetCampaignId = CRM_Utils_Array::value('mj_campaign_id', $trigger);
      $mailjetContactId = CRM_Utils_Array::value('mj_contact_id' , $trigger);

      // Get the internal job ID based on the MJ campaign ID.
      $jobID = explode('MJ', $mailingId);
      $jobID = $jobID[0];

      // Instantiate a new MJ event and populate the object with required data.
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

      // Process events of type typofix.
      if($event == 'typofix'){
        // We do not handle typofix.
        watchdog('mailjet notice', 'Event of type "typofix" was sent by MailJet.', NULL, WATCHDOG_NOTICE);
        return;
      }

      // Get the internal user based on the MJ supplied e-mail.
      $emailResult = civicrm_api3('Email', 'get', array('email' => $email));
      if(isset($emailResult['values']) && !empty($emailResult['values'])) {
        $userInfo = array_pop($emailResult['values']);
        $contactId = $userInfo['contact_id'];
        $emailId = $userInfo['id'];
        $params = array(
          'mailing_id' => $mailingId,
          'contact_id' => $contactId,
          'email_id' => $emailId,
          'date_ts' =>  $trigger['time'],
        );

        // Event handler
        // More info:  https://www.mailjet.com/docs/event_tracking
         switch($event) {
          case 'open':
          case 'click':
          case 'unsub':
          case 'typofix':
            break;
          // Handle bounce, span and blocked emails as bounce mailing in CiviCRM.
          case 'bounce':
          case 'spam':
          case 'blocked':
            $params['hard_bounce'] =  CRM_Utils_Array::value('hard_bounce', $trigger);
            $params['blocked'] = CRM_Utils_Array::value('blocked', $trigger);
            $params['source'] = CRM_Utils_Array::value('source', $trigger);
            $params['error_related_to'] =  CRM_Utils_Array::value('error_related_to', $trigger);
            $params['error'] =   CRM_Utils_Array::value('error', $trigger);
            $params['is_spam'] = !empty($params['source']) ? TRUE : FALSE;
            $params['job_id'] = $jobID;
            // Record the bounce to the DB.
            if (!CRM_Mailjet_BAO_Event::recordBounce($params)) {
              watchdog('mailjet error', 'A bounce for the following job id: %job_id wasn\'t successfully saved to the database.', array('%job_id' => $jobID), WATCHDOG_NOTICE);
            }
            //TODO: handle error
            break;
          # No handler
          default:
            header('HTTP/1.1 423 No handler');
            // Log if there is no handler
            break;
        }
        // Respond to MJ with status code 200, marking a successful processing
        // of data.
        header('HTTP/1.1 200 Ok');
      }
    }
    else { //assumed if there is not mailing_id, this should be a transaction email
      //TODO::process a transaction email
    }
    // Block page render by using civiExit().
    CRM_Utils_System::civiExit();
  }
}