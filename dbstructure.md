## Structure Database noobz_cinema ##

# noobz_cinema.admin_action_logs definition

CREATE TABLE `admin_action_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_user_id` bigint unsigned DEFAULT NULL,
  `target_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('success','failed','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_action_logs_admin_id_created_at_index` (`admin_id`,`created_at`),
  KEY `admin_action_logs_action_created_at_index` (`action`,`created_at`),
  KEY `admin_action_logs_target_user_id_created_at_index` (`target_user_id`,`created_at`),
  KEY `admin_action_logs_severity_created_at_index` (`severity`,`created_at`),
  KEY `admin_action_logs_is_sensitive_created_at_index` (`is_sensitive`,`created_at`),
  KEY `admin_action_logs_action_index` (`action`),
  KEY `admin_action_logs_action_type_index` (`action_type`),
  KEY `admin_action_logs_ip_address_index` (`ip_address`),
  KEY `admin_action_logs_severity_index` (`severity`),
  KEY `admin_action_logs_is_sensitive_index` (`is_sensitive`),
  KEY `admin_action_logs_status_index` (`status`),
  CONSTRAINT `admin_action_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_action_logs_target_user_id_foreign` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.audit_logs definition

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_action_created_at_index` (`action`,`created_at`),
  KEY `audit_logs_ip_address_index` (`ip_address`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.broken_link_reports definition

CREATE TABLE `broken_link_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `movie_id` bigint unsigned DEFAULT NULL,
  `movie_source_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `issue_type` enum('not_loading','wrong_movie','wrong_episode','poor_quality','no_audio','no_subtitle','buffering','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','reviewing','fixed','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `series_id` bigint unsigned DEFAULT NULL,
  `episode_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `broken_link_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `broken_link_reports_movie_id_status_index` (`movie_id`,`status`),
  KEY `broken_link_reports_movie_source_id_status_index` (`movie_source_id`,`status`),
  KEY `broken_link_reports_user_id_index` (`user_id`),
  KEY `broken_link_reports_status_index` (`status`),
  KEY `idx_reports_status_created` (`status`,`created_at`),
  KEY `broken_link_reports_series_id_foreign` (`series_id`),
  CONSTRAINT `broken_link_reports_movie_id_foreign` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `broken_link_reports_movie_source_id_foreign` FOREIGN KEY (`movie_source_id`) REFERENCES `movie_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `broken_link_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `broken_link_reports_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `broken_link_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.cache definition

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.cache_locks definition

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.failed_jobs definition

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.genres definition

CREATE TABLE `genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tmdb_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `genres_slug_unique` (`slug`),
  UNIQUE KEY `genres_tmdb_id_unique` (`tmdb_id`),
  KEY `genres_slug_index` (`slug`),
  KEY `idx_genres_name` (`name`),
  KEY `idx_genres_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.invite_codes definition

CREATE TABLE `invite_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `used_count` int NOT NULL DEFAULT '0',
  `max_uses` int DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_codes_code_unique` (`code`),
  KEY `invite_codes_created_by_foreign` (`created_by`),
  KEY `invite_codes_code_index` (`code`),
  KEY `invite_codes_status_index` (`status`),
  KEY `idx_invite_codes_status_created` (`status`,`created_at`),
  CONSTRAINT `invite_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.job_batches definition

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.jobs definition

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.movie_genres definition

CREATE TABLE `movie_genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `movie_id` bigint unsigned NOT NULL,
  `genre_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movie_genres_movie_id_genre_id_unique` (`movie_id`,`genre_id`),
  KEY `idx_movie_genres_genre_id` (`genre_id`),
  KEY `idx_movie_genres_movie_id` (`movie_id`),
  CONSTRAINT `movie_genres_genre_id_foreign` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movie_genres_movie_id_foreign` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.movie_sources definition

CREATE TABLE `movie_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `movie_id` bigint unsigned NOT NULL,
  `source_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `embed_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quality` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('embed','direct','torrent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'embed',
  `language` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'English',
  `has_subtitle` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0',
  `report_count` int NOT NULL DEFAULT '0',
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `added_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_sources_movie_id_is_active_index` (`movie_id`,`is_active`),
  KEY `movie_sources_movie_id_is_active_quality_index` (`movie_id`,`is_active`,`quality`),
  KEY `movie_sources_report_count_index` (`report_count`),
  KEY `idx_movie_sources_quality` (`quality`),
  KEY `idx_movie_sources_movie_quality` (`movie_id`,`quality`),
  CONSTRAINT `movie_sources_movie_id_foreign` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.movie_views definition

CREATE TABLE `movie_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `movie_id` bigint unsigned NOT NULL,
  `watched_at` timestamp NOT NULL,
  `watch_duration` int DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_views_user_id_movie_id_index` (`user_id`,`movie_id`),
  KEY `movie_views_watched_at_index` (`watched_at`),
  KEY `idx_movie_views_trending` (`movie_id`,`created_at`),
  KEY `idx_movie_views_date_movie` (`created_at`,`movie_id`),
  KEY `idx_movie_views_movie_date` (`movie_id`,`created_at`),
  KEY `idx_movie_views_created` (`created_at`),
  CONSTRAINT `movie_views_movie_id_foreign` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movie_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.movies definition

CREATE TABLE `movies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tmdb_id` int DEFAULT NULL,
  `imdb_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `overview` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `embed_url` text COLLATE utf8mb4_unicode_ci,
  `poster_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backdrop_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trailer_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `runtime` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `poster_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backdrop_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` year DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `vote_count` int NOT NULL DEFAULT '0',
  `quality` enum('CAM','HD','FHD','4K') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HD',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `view_count` int NOT NULL DEFAULT '0',
  `added_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_subtitle` tinyint(1) NOT NULL DEFAULT '0',
  `is_dubbed` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cast` text COLLATE utf8mb4_unicode_ci,
  `director` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `popularity` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `movies_slug_unique` (`slug`),
  UNIQUE KEY `movies_tmdb_id_unique` (`tmdb_id`),
  UNIQUE KEY `movies_imdb_id_unique` (`imdb_id`),
  KEY `movies_added_by_foreign` (`added_by`),
  KEY `movies_slug_index` (`slug`),
  KEY `movies_tmdb_id_index` (`tmdb_id`),
  KEY `movies_status_index` (`status`),
  KEY `movies_status_created_at_index` (`status`,`created_at`),
  KEY `movies_language_index` (`language`),
  KEY `movies_has_subtitle_index` (`has_subtitle`),
  KEY `movies_is_dubbed_index` (`is_dubbed`),
  KEY `movies_popularity_index` (`popularity`),
  KEY `idx_movies_rating` (`rating`),
  KEY `idx_movies_year` (`year`),
  KEY `idx_movies_view_count` (`view_count`),
  KEY `idx_movies_is_active` (`is_active`),
  KEY `idx_movies_is_featured` (`is_featured`),
  KEY `idx_movies_release_date` (`release_date`),
  KEY `idx_movies_active_rating` (`is_active`,`rating`),
  KEY `idx_movies_active_year` (`is_active`,`year`),
  KEY `idx_movies_active_created` (`is_active`,`created_at`),
  KEY `idx_movies_featured_active` (`is_featured`,`is_active`),
  KEY `idx_movies_trending` (`is_active`,`view_count`,`created_at`),
  KEY `idx_movies_status_created` (`status`,`created_at`),
  KEY `idx_movies_updated` (`updated_at`),
  FULLTEXT KEY `movies_title_description_fulltext` (`title`,`description`),
  CONSTRAINT `movies_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.password_reset_tokens definition

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.permission_role definition

CREATE TABLE `permission_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.permissions definition

CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.personal_access_tokens definition

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.roles definition

CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hierarchy` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.search_histories definition

CREATE TABLE `search_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `search_term` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `results_count` int NOT NULL DEFAULT '0',
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_histories_user_id_index` (`user_id`),
  KEY `search_histories_search_term_index` (`search_term`),
  KEY `search_histories_created_at_index` (`created_at`),
  KEY `idx_search_histories_term_date` (`search_term`,`created_at`),
  CONSTRAINT `search_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series definition

CREATE TABLE `series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tmdb_id` int DEFAULT NULL,
  `original_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poster_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backdrop_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `status` enum('published','draft') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `view_count` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `overview` text COLLATE utf8mb4_unicode_ci,
  `poster_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backdrop_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trailer_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `runtime` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `vote_count` int DEFAULT NULL,
  `popularity` decimal(10,2) DEFAULT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_subtitle` tinyint(1) NOT NULL DEFAULT '0',
  `is_dubbed` tinyint(1) NOT NULL DEFAULT '0',
  `cast` text COLLATE utf8mb4_unicode_ci,
  `director` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `first_air_date` date DEFAULT NULL,
  `last_air_date` date DEFAULT NULL,
  `number_of_seasons` int DEFAULT NULL,
  `number_of_episodes` int DEFAULT NULL,
  `in_production` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `series_tmdb_id_unique` (`tmdb_id`),
  KEY `series_created_by_foreign` (`created_by`),
  KEY `series_updated_by_foreign` (`updated_by`),
  KEY `idx_series_is_active` (`is_active`),
  KEY `idx_series_rating` (`rating`),
  KEY `idx_series_year` (`year`),
  KEY `idx_series_active_created` (`is_active`,`created_at`),
  KEY `idx_series_status_created` (`status`,`created_at`),
  KEY `idx_series_view_count` (`view_count`),
  CONSTRAINT `series_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `series_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series_episode_views definition

CREATE TABLE `series_episode_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `episode_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `series_episode_views_episode_id_viewed_at_index` (`episode_id`,`viewed_at`),
  KEY `series_episode_views_user_id_viewed_at_index` (`user_id`,`viewed_at`),
  CONSTRAINT `series_episode_views_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `series_episodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `series_episode_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series_episodes definition

CREATE TABLE `series_episodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `tmdb_id` int DEFAULT NULL,
  `episode_number` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview` text COLLATE utf8mb4_unicode_ci,
  `still_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `air_date` date DEFAULT NULL,
  `runtime` int DEFAULT NULL,
  `vote_average` decimal(3,1) DEFAULT NULL,
  `vote_count` int DEFAULT NULL,
  `embed_url` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `series_episodes_season_id_foreign` (`season_id`),
  KEY `series_episodes_series_id_season_id_episode_number_index` (`series_id`,`season_id`,`episode_number`),
  CONSTRAINT `series_episodes_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `series_seasons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `series_episodes_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series_genres definition

CREATE TABLE `series_genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint unsigned NOT NULL,
  `genre_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `series_genres_series_id_genre_id_unique` (`series_id`,`genre_id`),
  KEY `idx_series_genres_genre_id` (`genre_id`),
  KEY `idx_series_genres_series_id` (`series_id`),
  CONSTRAINT `series_genres_genre_id_foreign` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `series_genres_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series_seasons definition

CREATE TABLE `series_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint unsigned NOT NULL,
  `tmdb_id` int DEFAULT NULL,
  `season_number` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview` text COLLATE utf8mb4_unicode_ci,
  `poster_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `air_date` date DEFAULT NULL,
  `episode_count` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `series_seasons_series_id_season_number_index` (`series_id`,`season_number`),
  CONSTRAINT `series_seasons_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.series_views definition

CREATE TABLE `series_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `series_views_series_id_viewed_at_index` (`series_id`,`viewed_at`),
  KEY `series_views_user_id_viewed_at_index` (`user_id`,`viewed_at`),
  CONSTRAINT `series_views_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `series_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.sessions definition

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.user_action_logs definition

CREATE TABLE `user_action_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_action_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `user_action_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.user_activities definition

CREATE TABLE `user_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `activity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activities_user_id_activity_at_index` (`user_id`,`activity_at`),
  KEY `user_activities_activity_type_activity_at_index` (`activity_type`,`activity_at`),
  KEY `user_activities_activity_at_index` (`activity_at`),
  CONSTRAINT `user_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.user_registrations definition

CREATE TABLE `user_registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `invite_code_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_registrations_user_id_invite_code_id_index` (`user_id`,`invite_code_id`),
  KEY `user_registrations_invite_code_id_foreign` (`invite_code_id`),
  CONSTRAINT `user_registrations_invite_code_id_foreign` FOREIGN KEY (`invite_code_id`) REFERENCES `invite_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.users definition

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('member','admin','super_admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `role_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','suspended','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `idx_users_status_login` (`status`,`last_login_at`),
  KEY `idx_users_last_login` (`last_login_at`),
  KEY `idx_users_role_created` (`role`,`created_at`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

# noobz_cinema.watchlist definition

CREATE TABLE `watchlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `movie_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `watchlist_user_id_movie_id_unique` (`user_id`,`movie_id`),
  KEY `watchlist_movie_id_foreign` (`movie_id`),
  KEY `watchlist_user_id_index` (`user_id`),
  CONSTRAINT `watchlist_movie_id_foreign` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;