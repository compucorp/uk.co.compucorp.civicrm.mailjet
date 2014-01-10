
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_mailing_mailjet_event`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_mailing_mailjet_event
-- *
-- *******************************************************/
CREATE TABLE `civicrm_mailing_mailjet_event` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  ,
     `mailing_id` int unsigned    COMMENT 'FK to mailing ID and customcampiang on Mailjet',
     `email` varchar(255) NOT NULL   COMMENT 'Email address of recipient triggering the event',
     `event` varchar(255) NOT NULL   ,
     `mj_campaign_id` int unsigned    COMMENT 'The mailjet campaing _id',
     `mj_contact_id` int unsigned    COMMENT 'The mailjet campaing _id',
     `time` datetime NOT NULL   COMMENT 'Unix timestamp of event (free of timezone concerns)',
     `data` text    COMMENT 'Mailjet row data',
     `created_date` datetime NOT NULL
,
    PRIMARY KEY ( `id` )



)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;



-- alter civicrm_mailing_bounce_type to add Mailjet's bounce types to enum
ALTER TABLE `civicrm_mailing_bounce_type`
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown',
    'Mailjet Soft Bounces', 'Mailjet Hard Bounces', 'Mailjet Blocked', 'Mailjet Spam' )
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';
