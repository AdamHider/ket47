CREATE TABLE `image_list` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `image_holder_id` int DEFAULT NULL,
  `image_holder` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `image_title` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `owner_id` int NOT NULL DEFAULT '0',
  `owner_ally_id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `is_disabled` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`image_id`),
  KEY `hldr` (`image_holder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



ALTER TABLE `store_list` 
ADD COLUMN `store_name_new` VARCHAR(45) NULL AFTER `store_name`,
ADD COLUMN `store_description_new` VARCHAR(1000) NULL AFTER `store_description`,
CHANGE COLUMN `store_description` `store_description` VARCHAR(1000) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL DEFAULT NULL ;
