SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

SET @moduleId = __module_id__;

-- application admin menu

SET @maxOrder = (SELECT `order` + 1 FROM `application_admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `application_admin_menu_category` (`name`, `module`, `icon`) VALUES
('Actions tracker', @moduleId, 'action_tracker_menu_item.png');

SET @menuCategoryId = (SELECT LAST_INSERT_ID());
SET @menuPartId = (SELECT `id` FROM `application_admin_menu_part` WHERE `name` = 'Modules');

INSERT INTO `application_admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
('List of actions', 'actions-tracker-administration', 'list', @moduleId, @maxOrder, @menuCategoryId, @menuPartId),
('Manage actions', 'actions-tracker-administration', 'manage', @moduleId, @maxOrder + 2, @menuCategoryId, @menuPartId),
('Settings', 'actions-tracker-administration', 'settings', @moduleId, @maxOrder + 3, @menuCategoryId, @menuPartId);

-- acl resources

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('actions_tracker_administration_list', 'ACL - Viewing actions log in admin area', @moduleId),
('actions_tracker_administration_delete', 'ACL - Deleting actions log in admin area', @moduleId),
('actions_tracker_administration_manage', 'ACL - Managing actions in admin area', @moduleId),
('actions_tracker_administration_activate', 'ACL - Activating actions in admin area', @moduleId),
('actions_tracker_administration_deactivate', 'ACL - Deactivating actions in admin area', @moduleId),
('actions_tracker_administration_settings', 'ACL - Editing actions tracker settings in admin area', @moduleId);

-- application events

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('action_tracker_delete', @moduleId, 'Event - Deleting actions log'),
('action_tracker_activate', @moduleId, 'Event - Activating actions'),
('action_tracker_deactivate', @moduleId, 'Event - Deactivating actions');

-- application settings

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Email notifications', @moduleId);
SET @settingsCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('action_tracker_send_actions', 'Send notifications about new actions', NULL, 'checkbox', NULL, 1, @settingsCategoryId, @moduleId, NULL, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('action_tracker_title', 'Action title', 'An action notification', 'notification_title', 1, 2, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, 'There has been a new action', NULL),
(@settingId, 'Произошло новое действие', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('action_tracker_message', 'Action message', NULL, 'notification_message', 1, 3, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '<p><b>The site was a new action:</b></p><p>__Action__</p><p>__Date__</p>', NULL),
(@settingId, '<p><b>На сайте произошло новое действие:</b></p><p>__Action__</p><p>__Date__</p>', 'ru');

-- module tables

CREATE TABLE `action_tracker_connection` (
    `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
    `action_id` SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`action_id`) REFERENCES `application_event`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `action_tracker_log` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `action_id` SMALLINT(5) UNSIGNED NOT NULL,
    `description` VARCHAR(100) NOT NULL,
    `description_params` TEXT NOT NULL,
    `registered` INT(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`action_id`) REFERENCES `application_event`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;