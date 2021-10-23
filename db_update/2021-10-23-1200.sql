CREATE TABLE `order_list` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `order_group_id` int DEFAULT NULL,
  `order_store_id` int DEFAULT NULL,
  `order_customer_id` int DEFAULT NULL,
  `order_description` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `order_tax` int DEFAULT NULL,
  `order_shipping_fee` float DEFAULT NULL,
  `is_disabled` tinyint NOT NULL DEFAULT '0',
  `owner_id` int NOT NULL DEFAULT '0',
  `owner_ally_ids` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `modifiedby_idx` (`updated_by`),
  KEY `customerId_idx` (`order_customer_id`),
  KEY `storeId_idx` (`order_store_id`),
  CONSTRAINT `customerId` FOREIGN KEY (`order_customer_id`) REFERENCES `user_list` (`user_id`),
  CONSTRAINT `modifiedby` FOREIGN KEY (`updated_by`) REFERENCES `user_list` (`user_id`),
  CONSTRAINT `orderstoreId` FOREIGN KEY (`order_store_id`) REFERENCES `store_list` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
