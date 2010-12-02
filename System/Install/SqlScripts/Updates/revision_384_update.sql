ALTER TABLE  `ItemClasses` ADD  `itemclass_primary_property_id` INT( 11 ) NOT NULL AFTER  `itemclass_default_description_property_id`;
ALTER TABLE  `DropDowns` ADD  `dropdown_datatype` VARCHAR( 64 ) NOT NULL AFTER  `dropdown_label`;
ALTER TABLE  `DropDowns` ADD  `dropdown_is_system` TINYINT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `ItemProperties` CHANGE  `itemproperty_required`  `itemproperty_required` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'FALSE';
ALTER TABLE  `ItemProperties` ADD  `itemproperty_order_index` INT( 11 ) NOT NULL AFTER  `itemproperty_option_set_id`;
ALTER TABLE  `ItemClasses` ADD  `itemclass_class_file_checksum` VARCHAR( 32 ) NOT NULL AFTER  `itemclass_varname`;
ALTER TABLE  `ManyToManyLookups` ADD  `mtmlookup_status_flag` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'SM_MTMLOOKUPSTATUS_LIVE' AFTER  `mtmlookup_order_index`;
ALTER TABLE  `AssetClasses` ADD  `assetclass_is_system` TINYINT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `Assets` ADD  `asset_is_system` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `asset_is_archived`;
ALTER TABLE  `Tags` ADD  `tag_language` VARCHAR( 8 ) NOT NULL DEFAULT  'eng';
UPDATE `Settings` SET `setting_value` = '384' WHERE  `Settings`.`setting_name` = 'database_minimum_revision';
UPDATE `Settings` SET `setting_value` = '16' WHERE  `Settings`.`setting_name` = 'database_version';