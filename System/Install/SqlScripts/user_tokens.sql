INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (1, 'permission', 'create_remove_settings', 'Create, remove, and edit settings themselves.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (2, 'permission', 'modify_system_settings', 'Modify values of system settings.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (3, 'permission', 'add_new_pages', 'Add pages to the sitemap.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (4, 'permission', 'remove_pages', 'Remove pages from the sitemap.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (5, 'permission', 'create_remove_models', 'Create, remove, and edit item classes.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (6, 'permission', 'create_remove_properties', 'Create, remove and edit item class properties.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (7, 'permission', 'add_items', 'Add items to existing item classes.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (8, 'permission', 'delete_items', 'Delete items from existing item classes.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (9, 'permission', 'modify_page_properties', 'Modify the properties of pages, such as title, URLs, and meta information.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (10, 'permission', 'modify_draft_pages', 'Modify draft versions of pages in the content management system.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (11, 'permission', 'approve_page_changes', 'Approve pages marked as ready for approval.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (12, 'complexity', 'see_advanced_page_edit_ui', 'If present, the user will see an advanced "tree" page edit interface by default. If absent, the user sees a simplified one.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (18, 'permission', 'publish_all_pages', 'Publish changed pages even if they have not been approved.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (13, 'permission', 'modify_user_permissions', 'Edit the permissions of other users.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (14, 'permission', 'modify_items', 'Modify CMS Items that do not belong to the user.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (15, 'permission', 'approve_item_changes', 'Approve changes to items that do not belong to the user.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (16, 'permission', 'publish_approved_pages', 'Publish changes to pages that have been modified, and those modifications accepted.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (17, 'permission', 'publish_approved_items', 'Publish changes to items that have been modified, and those modifications accepted.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (19, 'permission', 'publish_all_items', 'Publish changed items even if they have not been approved.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (20, 'permission', 'modify_site_parameters', 'Edit site parameters.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (22, 'permission', 'modify_other_user_details', 'Edit the details of other users.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (23, 'permission', 'grant_site_access', 'Grant access to a site to another user.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (24, 'permission', 'grant_global_permissions', 'Permission to grant permissions that will persist across all sites.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (25, 'permission', 'create_sites', 'Allows the user to create new sites.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (26, 'permission', 'modify_assets', 'Allows the user to modify existing media assets.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (27, 'permission', 'delete_assets', 'Allows the user to delete media assets.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (28, 'permission', 'create_assets', 'Allows the user to add new media to the system.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (29, 'permission', 'clear_pages_cache', 'Allows the user to clear the pages cache.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (21, 'permission', 'site_access', 'To see the contents of a site: to open it in order to work on it.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (30, 'permission', 'approve_assets', 'Give approval for specific files (assets) to be used on live pages.');
INSERT INTO `UserTokens` (`token_id`, `token_type`, `token_code`, `token_description`) VALUES (31, 'permission', 'create_users', 'Allow the user to create other user accounts');