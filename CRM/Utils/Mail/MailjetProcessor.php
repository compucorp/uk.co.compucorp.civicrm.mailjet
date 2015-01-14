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
   * Process the mailjet bounce emails.
   *
   * @params $mailingId string
   *  The mailing ID.
   *
   * @return boolean always returns true (for the api). at a later stage we
   *  should fix this to return true on success / false on failure etc.
   */
  static function processBounces($mailingId = NULL) {
    require_once('packages/mailjet-v3/php-mailjet-v3-simple.class.php');
    // Instantiate a new MJ object.
    $mj = new Mailjet(MAILJET_API_KEY, MAILJET_SECRET_KEY);
    $mj->debug = 0;

    // Proceed only if we have a mailing ID.
    if ($mailingId) {
      $apiParams = array('mailing_id' => $mailingId);
      $mailJobResult = civicrm_api3('MailingJob', 'get', $apiParams);
      foreach ($mailJobResult['values'] as $jobId => $currentJob) {
        if (isset($currentJob['job_type'])) {
          $jobType = $currentJob['job_type'];
          $campaignJobId = $jobType == 'child' ? $jobId : FALSE;
        }
      }

      // Get the current campaign ID.
      $campaignId = CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($campaignJobId);

      $mailJetParams = array(
        "method" => "VIEW",
        "ID" => $campaignId
      );
      $campaignInfo = $mj->campaign($mailJetParams);
      $campaignInfo= $campaignInfo->Data[0];

      // Get the bounce statistics for the current Campaign ID>
      $mjBounces = $mj->messagesentstatistics(array(
        'CampaignID' => $campaignInfo->ID,
        'Allmessages' => 1,
        'messagestatus' => 'bounce'
      ));

    }
    else {
      // If we don't have a campaign ID then we process all bounces for our MJ
      // account.
      $mjBounces = $mj->messagesentstatistics(array(
        'Allmessages' => 1,
        'messagestatus' => 'bounce'
      ));
    }

    $mjBounces = $mjBounces->Data;

    // Format the result if we have found any bounces.
    if (!empty($mjBounces)) {
      foreach ($mjBounces as $bounce) {
        // Get the current contact details, as we need the users email, email id
        // and contact id for further processing.
        $contactDetails = $mj->contact(array('method' => 'VIEW', 'ID' => $bounce->ContactID));
        $contactDetails = array_pop($contactDetails->Data);
        $params = array('email' => $contactDetails->Email, 'sequential' => 1);
        $emailResult = civicrm_api3('Email', 'get', $params);
        if (!empty($emailResult['values'])) {
          $emailResult = array_pop($emailResult['values']);
          $contactId = $emailResult['contact_id'];
          $emailId = $emailResult['id'];
          $emailAddress = $emailResult['email'];

          $mailingJobResult = civicrm_api3('MailingJob', 'get', array('id' => $jobId));
          $mailingResult = civicrm_api3('Mailing', 'get', array('id' => $mailingJobResult['values'][$jobId]['mailing_id']));

          $currentMailingId = 0;
          foreach ($mailingResult['values'] as $mailingId => $mailing) {
            $currentMailingId = $mailingId;
          }
          // Check if the bounce was already recorder in the DB.
          $query = "SELECT eq.id
          FROM civicrm_mailing_event_bounce eb
          LEFT JOIN civicrm_mailing_event_queue eq ON eq.id = eb.event_queue_id
          WHERE 1
          AND eq.job_id = $jobId
          AND eq.email_id = $emailId
          AND eq.contact_id = $contactId";
          $dao = CRM_Core_DAO::executeQuery($query);
          // We presume that the bounce was already added.
          $bounceRecordInexistent = TRUE;
          while ($dao->fetch()) {
            // If the bounce wasn't already recorded, then we inserted in the DB.
            $bounceRecordInexistent = FALSE;
            break;
          }
          // Create the bounce record info, and preping it for save.
          if ($bounceRecordInexistent) {
            $bounceDate = new DateTime($bounce->BouncedAt);
            $bounceDate = strtotime($bounceDate->date);
            $bounceArray = array(
              'is_spam' => FALSE,
              'mailing_id' => $currentMailingId,
              'job_id' => $jobId,
              'contact_id' => $contactId,
              'email_id' => $emailId,
              'email' => $emailAddress,
              'blocked' => 0,
              // If it's manual refresh, we force it as a normal bounce and
              // not blocked.
              'hard_bounce' => $bounce->hard_bounce,
              'date_ts' => $bounceDate,
              'error_related_to' => $bounce->error_related_to,
              'error' => $bounce->error
            );
            // Record the bounce to the database, by supplying all the necessary
            // info. If the save was successful then the bounce processing can
            // be marked as successful.
            if (CRM_Mailjet_BAO_Event::recordBounce($bounceArray)) {
              return TRUE;
            };
          }
        }
      }
    }

    // If nou bounces were found for the current campaign.
    return FALSE;
  }
}