# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;


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

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
