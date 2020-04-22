# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `order_pay_plug_multi_payment` ADD COLUMN `amount_refunded` DECIMAL(16,6) DEFAULT 0;
ALTER TABLE `order_pay_plug_multi_payment` DROP COLUMN `refunded_at`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;