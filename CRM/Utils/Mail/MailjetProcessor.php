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
  static function processBounces($mailingId = NULL) {
    require_once('packages/mailjet-0.1/php-mailjet.class-mailjet-0.1.php');
      // Create a new Mailjet Object @php-mailjet.class-mailjet-0.1.php
    $mj = new Mailjet(MAILJET_API_KEY, MAILJET_SECRET_KEY);
	
	//G: TODO
    $mj->debug = 0;
    
    if($mailingId){
      $apiParams = array(
      'mailing_id' => $mailingId
      );
	  $campaignJobId = 0;
      $mailJobResult = civicrm_api3('MailingJob', 'get', $apiParams);
	  foreach ($mailJobResult['values'] as $jobId => $currentJob) {
		if(isset($currentJob['job_type'])){
	      $jobType = $currentJob['job_type'];
		  //if job is not test
	      if($jobType == 'child'){
	    	$campaignJobId = $jobId;
	      }
	    }
	  }
	      	
      $mailjetParams = array('custom_campaign' =>  CRM_Mailjet_BAO_Event::getMailjetCustomCampaignId($campaignJobId));
		
	  //G: https://uk.mailjet.com/docs/api/message/list
	  //List all your messages (both transactional and campaign) with numerous filters.
      $response = $mj->messageList($mailjetParams);
      if(!$response){
         return TRUE; //always return true - we don't process bounces if there is no reponse.
      }
      $campaign = $response->result[0];
	  //G: https://uk.mailjet.com/docs/api/report/emailbounce
	  //Lists emails declared as bounce.
	  //Call
      $response = $mj->reportEmailBounce(array('campaign_id' => $campaign->id));
    }else{
      $response = $mj->reportEmailBounce();
    }
	//Result
    $bounces = $response->bounces;
    foreach ($bounces as $bounce) {
      $params = array('email' => $bounce->email,'sequential' => 1);
      $emailResult = civicrm_api3('Email', 'get', $params);
      if(!empty($emailResult['values'])){
        //we always get the first result
        //TODO: might related to bounce record issue
        $contactId = $emailResult['values'][0]['contact_id'];
        $emailId = $emailResult['values'][0]['id'];
		    $emailAddress = $emailResult['values'][0]['email'];
        if(!$bounce->customcampaign){
          //do not process bounce if we dont have custom campaign
          continue;
        }
        $campaingArray = explode("MJ", $bounce->customcampaign);
		//TODO: related to bounce record issue
        $jobId = $campaingArray[0];
		$mailingJobResult = civicrm_api3('MailingJob', 'get', array('id'=>$jobId));
		$mailingResult = civicrm_api3('Mailing', 'get', array('id'=>$mailingJobResult['values'][$jobId]['mailing_id']));
		
		$currentMailingId = 0;
		foreach ($mailingResult['values'] as $mailingId => $mailing) {
			$currentMailingId = $mailingId;
		}
		
        /*$params = array(
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
        );*/
        $query = "SELECT eq.id
          FROM civicrm_mailing_event_bounce eb
          LEFT JOIN civicrm_mailing_event_queue eq ON eq.id = eb.event_queue_id
          WHERE 1
          AND eq.job_id = $jobId
          AND eq.email_id = $emailId
          AND eq.contact_id = $contactId";
        $dao = CRM_Core_DAO::executeQuery($query);
        $isBounceRecord = FALSE;
        while ($dao->fetch()) {
          $isBounceRecord = TRUE;
          break;
        }
        //if bounce record doesn't exsit so we record it
        if(!$isBounceRecord){
          $bounceArray = array(
            'is_spam' => FALSE,
            'mailing_id' => $currentMailingId,
            'job_id' => $jobId,
            'contact_id' => $contactId,
            'email_id' => $emailId,
            'email' => $emailAddress,
            'blocked' => 0, //if it's manual refresh, we fource it as a normal bounce not blocked
            'hard_bounce' => $bounce->hard_bounce,
            'date_ts' => $bounce->date_ts,
            'error_related_to' => $bounce->error_related_to,
            'error' => $bounce->error
          );
          CRM_Mailjet_BAO_Event::recordBounce($bounceArray);
        }
      }
    }
    // always returns true, i.e. never fails :)
    return TRUE;
  }

}

