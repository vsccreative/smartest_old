ALTER TABLE  `ItemClasses` DROP INDEX  `itemclass_name`;
ALTER TABLE  `ItemClasses` ADD  `itemclass_shared` TINYINT( 1 ) NOT NULL AFTER  `itemclass_site_id`;
UPDATE  `ItemClasses` SET  `itemclass_shared` = 1;
ALTER TABLE  `Settings` CHANGE  `site_id`  `setting_site_id` INT( 11 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `Settings` ADD  `setting_user_id` INT( 9 ) NOT NULL AFTER  `setting_site_id`;
ALTER TABLE  `PageUrls` ADD  `pageurl_item_id` INT( 11 ) NOT NULL AFTER  `pageurl_page_id`;
ALTER TABLE  `Sites` DROP  `site_error_title`;
ALTER TABLE  `Sites` DROP  `site_error_tpl` ;
ALTER TABLE  `TagsObjectsLookup` ADD  `taglookup_order_index` INT( 9 ) NOT NULL;
ALTER TABLE  `Assets` ADD  `asset_search_field` TEXT NOT NULL;