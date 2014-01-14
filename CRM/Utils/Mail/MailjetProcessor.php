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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

class CRM_Utils_Mail_MailjetProcessor {

  /**
   * Process the mailjet bounce emails
   *
   * @return boolean always returns true (for the api). at a later stage we should
   *                 fix this to return true on success / false on failure etc
   */
  static function processBounces() {
    //Get mailing id of the mailing that already been processed
    $query = "SELECT DISTINCT civicrm_mailing.id
      FROM civicrm_mailing
      LEFT JOIN civicrm_mailing_job ON civicrm_mailing_job.mailing_id = civicrm_mailing.id
      WHERE 1
      AND civicrm_mailing_job.status = 'Complete'";

    $processedMailingIds = array();
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $processedMailingIds[] = $dao->id;
    }

    require_once('packages/mailjet-0.1/php-mailjet.class-mailjet-0.1.php');
      // Create a new Mailjet Object
    $mj = new Mailjet(MAILJET_API_KEY, MAILJET_SECRET_KEY);
    $mj->debug = 0;
    foreach ($processedMailingIds as $mailingId) {
      $mailJetParams = array(
        'custom_campaign' =>  $mailingId
      );
      $response = $mj->messageList($mailJetParams);
      if(!$response){
        continue;
      }
      $campaign = $response->result[0];
      $response = $mj->reportEmailBounce(array('campaign_id' => $campaign->id));
      $bounces = $response->bounces;
      foreach ($bounces as $bounce) {
        dpr($bounce);
        $params = array('email' => $bounce->email);
        $emailResult = civicrm_api3('Email', 'get', $params);
        if(CRM_Utils_Array::value('values', $emailResult)){
          $contactId = $emailResult['values'][$emailResult['id']]['contact_id'];
          $emailId = $emailResult['id'];
          $params = array(
            'mailing_id' => $mailingId,
          );
          $result = civicrm_api3('MailingJob', 'get', $params);
          $jobIds = array();
          foreach ($result['values'] as $id => $value) {
            $jobIds[] = $id;
          }
          $jobIds = implode(",", $jobIds);
          $params = array(
            1 => array( $contactId, 'Integer'),
            2 => array( $emailId, 'Integer')
          );
          $query = "SELECT eq.id
            FROM civicrm_mailing_event_bounce eb
            LEFT JOIN civicrm_mailing_event_queue eq ON eq.id = eb.event_queue_id
            WHERE 1
            AND eq.job_id IN ($jobIds)
            AND eq.email_id = $emailId
            AND eq.contact_id = $contactId";
          $dao = CRM_Core_DAO::executeQuery($query);
          $isBounceRecord = FALSE;
          while ($dao->fetch()) {
            $isBounceRecord = TRUE;
            break;
          }
          if(!$isBounceRecord){
            $bounceArray = array(
              'is_spam' => FALSE,
              'mailing_id' => $mailingId,
              'contact_id' => $contactId,
              'email_id' => $emailId,
              'blocked' => $bounce->blocked,
              'hard_bounce' => $bounce->hard_bounce,
              'date_ts' => $bounce->date_ts,
              'error_related_to' => $bounce->error_related_to,
              'error' => $bounce->error
            );
            CRM_Mailjet_BAO_Event::recordBounce($bounceArray);
          }
        }
      }

    }
    // always returns true, i.e. never fails :)
    return TRUE;
  }

}

