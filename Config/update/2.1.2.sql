# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- pay_plug_notification_history
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `pay_plug_notification_history`;

CREATE TABLE `pay_plug_notification_history`
(
    `uuid` VARCHAR(150) NOT NULL,
    `order_id` INTEGER NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`uuid`,`order_id`),
    INDEX `pay_plug_notification_history_fi_75704f` (`order_id`),
    CONSTRAINT `pay_plug_notification_history_fk_75704f`
        FOREIGN KEY (`order_id`)
            REFERENCES `order` (`id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
