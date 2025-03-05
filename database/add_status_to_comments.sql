ALTER TABLE `comments`
ADD COLUMN `status` ENUM('active', 'pending', 'spam') NOT NULL DEFAULT 'pending';