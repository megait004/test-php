ALTER TABLE `documents`
ADD COLUMN `category_id` int(11) DEFAULT NULL,
ADD CONSTRAINT `documents_category_fk`
FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
ON DELETE SET NULL;