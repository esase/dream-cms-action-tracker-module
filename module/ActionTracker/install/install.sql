SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

SET @moduleId = __module_id__;

-- application admin menu

SET @maxOrder = (SELECT `order` + 1 FROM `application_admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `application_admin_menu_category` (`name`, `module`, `icon`) VALUES
('ActionTracker', @moduleId, 'action_tracker_menu_item.png');

SET @menuCategoryId = (SELECT LAST_INSERT_ID());
SET @menuPartId = (SELECT `id` FROM `application_admin_menu_part` WHERE `name` = 'Modules');

INSERT INTO `application_admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
('List of actions', 'actions-tracker-administration', 'list', @moduleId, @maxOrder, @menuCategoryId, @menuPartId),
('Manage actions', 'actions-tracker-administration', 'manage', @moduleId, @maxOrder + 2, @menuCategoryId, @menuPartId),
('Settings', 'actions-tracker-administration', 'settings', @moduleId, @maxOrder + 3, @menuCategoryId, @menuPartId);

-- acl resources

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('actions_tracker_administration_list', 'ACL - Viewing actions in admin area', @moduleId),
('actions_tracker_administration_manage', 'ACL - Managing actions in admin area', @moduleId),
('actions_tracker_administration_activate', 'ACL - Activating actions in admin area', @moduleId),
('actions_tracker_administration_deactivate', 'ACL - Deactivating actions in admin area', @moduleId),
('actions_tracker_administration_settings', 'ACL - Editing actions tracker settings in admin area', @moduleId);

-- application events

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('action_tracker_activate', @moduleId, 'Event - Activating actions'),
('action_tracker_deactivate', @moduleId, 'Event - Deactivating actions');

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