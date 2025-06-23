
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- order_pay_plug_data
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `order_pay_plug_data`;

CREATE TABLE `order_pay_plug_data`
(
    `id` INTEGER NOT NULL,
    `amount_refunded` DECIMAL(16,6),
    `need_capture` TINYINT DEFAULT 0,
    `capture_expire_at` DATETIME,
    `captured_at` DATETIME,
    PRIMARY KEY (`id`),
    CONSTRAINT `order_pay_plug_data_fk_19ea48`
        FOREIGN KEY (`id`)
        REFERENCES `order` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pay_plug_card
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `pay_plug_card`;

CREATE TABLE `pay_plug_card`
(
    `uuid` VARCHAR(150) NOT NULL,
    `customer_id` INTEGER,
    `brand` VARCHAR(255),
    `last_4` VARCHAR(255),
    `expire_month` INTEGER,
    `expire_year` INTEGER,
    PRIMARY KEY (`uuid`),
    INDEX `pay_plug_card_fi_7e8f3e` (`customer_id`),
    CONSTRAINT `pay_plug_card_fk_7e8f3e`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- order_pay_plug_multi_payment
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `order_pay_plug_multi_payment`;

CREATE TABLE `order_pay_plug_multi_payment`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `order_id` INTEGER NOT NULL,
    `amount` DECIMAL(16,6),
    `is_first_payment` TINYINT DEFAULT 0,
    `planned_at` DATETIME,
    `payment_method` VARCHAR(255),
    `payment_id` VARCHAR(255),
    `paid_at` DATETIME,
    `amount_refunded` DECIMAL(16,6) DEFAULT 0,
    PRIMARY KEY (`id`,`order_id`),
    INDEX `order_pay_plug_multi_payment_fi_75704f` (`order_id`),
    CONSTRAINT `order_pay_plug_multi_payment_fk_75704f`
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pay_plug_module_delivery_type
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `pay_plug_module_delivery_type`;

CREATE TABLE `pay_plug_module_delivery_type`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `module_id` INTEGER,
    `delivery_type` VARCHAR(255),
    PRIMARY KEY (`id`),
    INDEX `fi_pay_plug_module_delivery_type_module_id` (`module_id`),
    CONSTRAINT `fk_pay_plug_module_delivery_type_module_id`
        FOREIGN KEY (`module_id`)
        REFERENCES `module` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

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
