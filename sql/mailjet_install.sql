SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_mailing_mailjet`;

SET FOREIGN_KEY_CHECKS=1;

-- /*******************************************************
-- *
-- * civicrm_mailing_mailjet
-- *
-- *******************************************************/
CREATE TABLE `civicrm_mailing_mailjet` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  ,
     `mailing_id` int unsigned NOT NULL   COMMENT 'FK to mailing ID',
     `campaign_id` int unsigned NOT NULL   COMMENT 'The mailjet campaing _id'
,
    PRIMARY KEY ( `id` )


,          CONSTRAINT FK_civicrm_mailing_mailjet_mailing_id FOREIGN KEY (`mailing_id`) REFERENCES `civicrm_mailing`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
