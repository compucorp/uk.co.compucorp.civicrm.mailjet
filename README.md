CiviMailJet - MailJet integration for CiviCRM 
===============================


See the extension page at https://civicrm.org/extensions/civimailjet

Setup instructions for CiviMailjet extensions

1. Download and install the extension into the site extension directory.
2. Set up the CiviCRM Outbond email using SMTP  with  Mailjet's SMTP Credentials.
3. Config Event Tracking Endpoint Url in your Mailjet account using http://<sites>/civicrm/mailjet/event/endpoint
4. Add add the code below into the site civicrm settings file and put your mailjet api and secret key
define( 'MAILJET_API_KEY', 'YOUR MAILJET API KEY');
define( 'MAILJET_SECRET_KEY', 'YOUR MAILJET SECRET KEY');


Note: Currently CiviMailjet v1.0 overrides civicrm/CRM/Mailing/BAO/Mailing.php for alter mailing params for Mailjet to use when sending out the email.



