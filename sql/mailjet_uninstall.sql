DROP TABLE IF EXISTS `civicrm_mailing_mailjet_event`;

DELETE FROM `civicrm_mailing_bounce_type` WHERE `name` IN ( 'Mailjet Soft Bounces', 'Mailjet Hard Bounces', 'Mailjet Blocked', 'Mailjet Spam');

-- remove mailjet bounce types from enum
ALTER TABLE `civicrm_mailing_bounce_type`
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown' )
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';
