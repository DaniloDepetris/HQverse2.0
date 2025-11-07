-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07-Nov-2025 às 14:32
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `hqsql`
--
CREATE DATABASE IF NOT EXISTS `hqsql` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hqsql`;

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `increment_comic_views` (IN `comic_id` INT)   BEGIN
    UPDATE comics SET views = views + 1 WHERE id = comic_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `banned_users`
--

CREATE TABLE `banned_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_by` int(11) NOT NULL,
  `banned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `is_permanent` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `banned_users`
--

INSERT INTO `banned_users` (`id`, `email`, `username`, `reason`, `banned_by`, `banned_at`, `ip_address`, `is_permanent`) VALUES
(1, 'souchato@gmail.com', 'souchato', 'é chato', 4, '2025-10-21 17:59:14', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Super-heróis', 'Quadrinhos de super-heróis da Marvel, DC e outras editoras', '2025-10-20 23:04:36'),
(2, 'Mangá', 'Quadrinhos japoneses', '2025-10-20 23:04:36'),
(3, 'Graphic Novels', 'Romances gráficos e histórias completas', '2025-10-20 23:04:36'),
(4, 'Clássicos', 'Quadrinhos clássicos e históricos', '2025-10-20 23:04:36'),
(5, 'Indie', 'Quadrinhos independentes', '2025-10-20 23:04:36'),
(6, 'Horror', 'Quadrinhos de terror e suspense', '2025-10-20 23:04:36'),
(7, 'Ficção Científica', 'Quadrinhos de ficção científica', '2025-10-20 23:04:36'),
(8, 'Fantasia', 'Quadrinhos de fantasia e medieval', '2025-10-20 23:04:36'),
(9, 'Humor', 'Quadrinhos cômicos e de humor', '2025-10-20 23:04:36'),
(10, 'Aventura', 'Quadrinhos de aventura e ação', '2025-10-20 23:04:36');

-- --------------------------------------------------------

--
-- Estrutura da tabela `comics`
--

CREATE TABLE `comics` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `is_published` tinyint(1) DEFAULT 0,
  `is_premium` tinyint(1) DEFAULT 0,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `release_date` date DEFAULT NULL,
  `page_count` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `comics`
--
DELIMITER $$
CREATE TRIGGER `before_comic_update` BEFORE UPDATE ON `comics` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comic_categories`
--

CREATE TABLE `comic_categories` (
  `comic_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comic_collaborators`
--

CREATE TABLE `comic_collaborators` (
  `id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('author','illustrator','colorist','letterer','editor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comic_comments`
--

CREATE TABLE `comic_comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `page_number` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comic_drafts`
--

CREATE TABLE `comic_drafts` (
  `id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `version` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `comic_pages`
--

CREATE TABLE `comic_pages` (
  `id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `page_number` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para vista `comic_stats`
-- (Veja abaixo para a view atual)
--
CREATE TABLE `comic_stats` (
`id` int(11)
,`title` varchar(255)
,`author_id` int(11)
,`author_name` varchar(50)
,`review_count` bigint(21)
,`avg_rating` decimal(7,4)
,`favorite_count` bigint(21)
,`library_count` bigint(21)
,`views` int(11)
);

-- --------------------------------------------------------

--
-- Estrutura da tabela `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `forums`
--

CREATE TABLE `forums` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `forums`
--

INSERT INTO `forums` (`id`, `name`, `description`, `category_id`, `created_at`) VALUES
(1, 'Geral', 'Discussões gerais sobre quadrinhos', NULL, '2025-10-20 23:04:36'),
(2, 'Novidades', 'Lançamentos e novidades do mundo dos quadrinhos', NULL, '2025-10-20 23:04:36'),
(3, 'Reviews', 'Análises e críticas de quadrinhos', NULL, '2025-10-20 23:04:36'),
(4, 'Dúvidas', 'Tire suas dúvidas sobre quadrinhos', NULL, '2025-10-20 23:04:36'),
(5, 'Criação', 'Discussões sobre criação de HQs', NULL, '2025-10-20 23:04:36');

-- --------------------------------------------------------

--
-- Estrutura da tabela `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `publishers`
--

INSERT INTO `publishers` (`id`, `name`, `logo`, `description`, `created_at`) VALUES
(1, 'DC Comics', NULL, 'Detective Comics - Publicadora de Batman, Superman, Mulher-Maravilha', '2025-10-20 23:04:36'),
(2, 'Marvel Comics', NULL, 'Publicadora de Homem-Aranha, X-Men, Vingadores', '2025-10-20 23:04:36'),
(3, 'Dark Horse', NULL, 'Editora independente de Hellboy, Sin City', '2025-10-20 23:04:36'),
(4, 'Image Comics', NULL, 'Editora de quadrinhos independentes', '2025-10-20 23:04:36'),
(5, 'Vertigo', NULL, 'Selos de quadrinhos maduros da DC', '2025-10-20 23:04:36'),
(6, 'Panini', NULL, 'Editora brasileira de quadrinhos', '2025-10-20 23:04:36'),
(7, 'Devir', NULL, 'Editora brasileira de quadrinhos', '2025-10-20 23:04:36'),
(8, 'Manga', NULL, 'Editoras de mangás variados', '2025-10-20 23:04:36'),
(9, 'Independente', NULL, 'Publicações independentes', '2025-10-20 23:04:36'),
(10, 'Outras', NULL, 'Outras editoras', '2025-10-20 23:04:36');

-- --------------------------------------------------------

--
-- Estrutura da tabela `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `type` enum('like','love','laugh','wow','sad','angry') DEFAULT 'like',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `reading_progress`
--

CREATE TABLE `reading_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `current_page` int(11) NOT NULL DEFAULT 1,
  `is_completed` tinyint(1) DEFAULT 0,
  `last_read` timestamp NOT NULL DEFAULT current_timestamp(),
  `reading_time` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `comic_id` int(11) DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','paypal','crypto') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','creator','moderator','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_file_name` varchar(255) DEFAULT NULL,
  `avatar_file_size` int(11) DEFAULT NULL,
  `avatar_mime_type` varchar(50) DEFAULT NULL,
  `avatar_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `bio`, `role`, `created_at`, `updated_at`, `avatar`, `avatar_file_name`, `avatar_file_size`, `avatar_mime_type`, `avatar_updated_at`) VALUES
(2, 'Juan Taborda', 'taborda.mjuan@gmail.com', '$2y$10$BzrSnR9AYcmK.bLQV3abV.AQ1gXidkWQLZ/rkEj6I/VfyeJS7CrFu', '\"Todos veem o que você parece ser, mas poucos sabem o que você realmente é.\"\r\n-Luva de pedreiro, receba', 'user', '2025-10-20 23:21:30', '2025-10-28 11:21:07', 'uploads/avatars/avatar_2_1761648349.png', 'images.png', 10652, 'image/png', '2025-10-28 10:45:49'),
(4, 'admin', 'admin@hqverso.com', '$2y$10$YRu2yXW6fY5EA969Ft5naeawk5M7FLcCLm4Br0NNnuSPJjGSRminW', 'sou admin do hq verso', 'admin', '2025-10-21 01:11:36', '2025-11-06 13:17:10', 'uploads/avatars/avatar_4_1762435030.png', 'images.png', 10652, 'image/png', '2025-11-06 13:17:10'),
(7, 'aa', 'a@a', '$2y$10$4O0Et2u4YtCkqoqbNHMiL.TJpPyDeWjEoCbtcRx2K/dTFALYSERGW', NULL, 'user', '2025-11-07 12:17:46', '2025-11-07 12:17:46', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `user_follows`
--

INSERT INTO `user_follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(1, 7, 2, '2025-11-07 13:24:45'),
(2, 7, 4, '2025-11-07 13:25:13');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_library`
--

CREATE TABLE `user_library` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para vista `user_stats`
-- (Veja abaixo para a view atual)
--
CREATE TABLE `user_stats` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`created_at` timestamp
,`comics_created` bigint(21)
,`library_count` bigint(21)
,`favorites_count` bigint(21)
,`reviews_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_uploads`
--

CREATE TABLE `user_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `upload_type` enum('avatar','comic_cover','comic_page','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para vista `comic_stats`
--
DROP TABLE IF EXISTS `comic_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `comic_stats`  AS SELECT `c`.`id` AS `id`, `c`.`title` AS `title`, `c`.`author_id` AS `author_id`, `u`.`username` AS `author_name`, count(distinct `r`.`id`) AS `review_count`, avg(`r`.`rating`) AS `avg_rating`, count(distinct `f`.`id`) AS `favorite_count`, count(distinct `t`.`id`) AS `library_count`, `c`.`views` AS `views` FROM ((((`comics` `c` left join `users` `u` on(`c`.`author_id` = `u`.`id`)) left join `reviews` `r` on(`c`.`id` = `r`.`comic_id`)) left join `favorites` `f` on(`c`.`id` = `f`.`comic_id`)) left join `user_library` `t` on(`c`.`id` = `t`.`comic_id`)) GROUP BY `c`.`id``id`  ;

-- --------------------------------------------------------

--
-- Estrutura para vista `user_stats`
--
DROP TABLE IF EXISTS `user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_stats`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`created_at` AS `created_at`, count(distinct `c`.`id`) AS `comics_created`, count(distinct `l`.`id`) AS `library_count`, count(distinct `f`.`id`) AS `favorites_count`, count(distinct `r`.`id`) AS `reviews_count` FROM ((((`users` `u` left join `comics` `c` on(`u`.`id` = `c`.`author_id`)) left join `user_library` `l` on(`u`.`id` = `l`.`user_id`)) left join `favorites` `f` on(`u`.`id` = `f`.`user_id`)) left join `reviews` `r` on(`u`.`id` = `r`.`user_id`)) GROUP BY `u`.`id``id`  ;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `banned_users`
--
ALTER TABLE `banned_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `banned_by` (`banned_by`),
  ADD KEY `idx_banned_email` (`email`),
  ADD KEY `idx_banned_username` (`username`);

--
-- Índices para tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices para tabela `comics`
--
ALTER TABLE `comics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `idx_comics_title` (`title`),
  ADD KEY `idx_comics_author` (`author_id`),
  ADD KEY `idx_comics_status` (`status`),
  ADD KEY `idx_comics_created` (`created_at`),
  ADD KEY `idx_comics_price` (`price`);

--
-- Índices para tabela `comic_categories`
--
ALTER TABLE `comic_categories`
  ADD PRIMARY KEY (`comic_id`,`category_id`),
  ADD KEY `idx_comic_cat_comic` (`comic_id`),
  ADD KEY `idx_comic_cat_category` (`category_id`);

--
-- Índices para tabela `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comic_user_role` (`comic_id`,`user_id`,`role`),
  ADD KEY `idx_collaborators_comic` (`comic_id`),
  ADD KEY `idx_collaborators_user` (`user_id`);

--
-- Índices para tabela `comic_comments`
--
ALTER TABLE `comic_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_user` (`user_id`),
  ADD KEY `idx_comments_comic` (`comic_id`),
  ADD KEY `idx_comments_parent` (`parent_comment_id`);

--
-- Índices para tabela `comic_drafts`
--
ALTER TABLE `comic_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drafts_comic` (`comic_id`);

--
-- Índices para tabela `comic_pages`
--
ALTER TABLE `comic_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pages_comic` (`comic_id`),
  ADD KEY `idx_pages_number` (`page_number`);

--
-- Índices para tabela `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_fav` (`user_id`,`comic_id`),
  ADD KEY `idx_favorites_user` (`user_id`),
  ADD KEY `idx_favorites_comic` (`comic_id`);

--
-- Índices para tabela `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forums_category` (`category_id`);

--
-- Índices para tabela `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_posts_user` (`user_id`),
  ADD KEY `idx_posts_topic` (`topic_id`),
  ADD KEY `idx_posts_created` (`created_at`);

--
-- Índices para tabela `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices para tabela `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_post_reaction` (`user_id`,`post_id`),
  ADD KEY `idx_reactions_user` (`user_id`),
  ADD KEY `idx_reactions_post` (`post_id`);

--
-- Índices para tabela `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic` (`user_id`,`comic_id`),
  ADD KEY `idx_reading_user` (`user_id`),
  ADD KEY `idx_reading_comic` (`comic_id`);

--
-- Índices para tabela `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_review` (`user_id`,`comic_id`),
  ADD KEY `idx_reviews_comic` (`comic_id`),
  ADD KEY `idx_reviews_rating` (`rating`),
  ADD KEY `idx_reviews_user` (`user_id`);

--
-- Índices para tabela `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_topics_user` (`user_id`),
  ADD KEY `idx_topics_forum` (`forum_id`),
  ADD KEY `idx_topics_comic` (`comic_id`),
  ADD KEY `idx_topics_created` (`created_at`);

--
-- Índices para tabela `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_transactions_user` (`user_id`),
  ADD KEY `idx_transactions_status` (`status`),
  ADD KEY `idx_transactions_comic` (`comic_id`),
  ADD KEY `idx_transactions_created` (`created_at`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`),
  ADD KEY `idx_users_created` (`created_at`);

--
-- Índices para tabela `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follower_following` (`follower_id`,`following_id`),
  ADD KEY `idx_follows_follower` (`follower_id`),
  ADD KEY `idx_follows_following` (`following_id`);

--
-- Índices para tabela `user_library`
--
ALTER TABLE `user_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic` (`user_id`,`comic_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_library_user` (`user_id`),
  ADD KEY `idx_library_comic` (`comic_id`);

--
-- Índices para tabela `user_uploads`
--
ALTER TABLE `user_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploads_user` (`user_id`),
  ADD KEY `idx_uploads_type` (`upload_type`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `comics`
--
ALTER TABLE `comics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comic_comments`
--
ALTER TABLE `comic_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comic_drafts`
--
ALTER TABLE `comic_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comic_pages`
--
ALTER TABLE `comic_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `forums`
--
ALTER TABLE `forums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `user_library`
--
ALTER TABLE `user_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_uploads`
--
ALTER TABLE `user_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `banned_users`
--
ALTER TABLE `banned_users`
  ADD CONSTRAINT `banned_users_ibfk_1` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `comics`
--
ALTER TABLE `comics`
  ADD CONSTRAINT `comics_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comics_ibfk_2` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `comic_categories`
--
ALTER TABLE `comic_categories`
  ADD CONSTRAINT `comic_categories_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  ADD CONSTRAINT `comic_collaborators_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_collaborators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `comic_comments`
--
ALTER TABLE `comic_comments`
  ADD CONSTRAINT `comic_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_comments_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `comic_comments` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `comic_drafts`
--
ALTER TABLE `comic_drafts`
  ADD CONSTRAINT `comic_drafts_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `comic_pages`
--
ALTER TABLE `comic_pages`
  ADD CONSTRAINT `comic_pages_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD CONSTRAINT `reading_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_progress_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_ibfk_2` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_ibfk_3` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `user_library`
--
ALTER TABLE `user_library`
  ADD CONSTRAINT `user_library_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_library_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_library_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `user_uploads`
--
ALTER TABLE `user_uploads`
  ADD CONSTRAINT `user_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `clean_old_sessions` ON SCHEDULE EVERY 1 DAY STARTS '2025-10-20 20:04:38' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Aqui você pode adicionar lógica para limpar sessões antigas se estiver usando tabela de sessões
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
