-- Migration script to fix emoji support in forum_post table
-- Changes charset from utf8 to utf8mb4 to support Unicode emojis

-- Alter the forum_post table to use utf8mb4 charset and collation
ALTER TABLE `forum_post`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Convert the title column to utf8mb4
ALTER TABLE `forum_post`
  MODIFY `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Convert the message column to utf8mb4
ALTER TABLE `forum_post`
  MODIFY `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;