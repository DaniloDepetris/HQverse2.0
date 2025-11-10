-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/11/2025 às 02:33
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

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
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_user_progress` (IN `p_user_id` INT)   BEGIN
  SELECT comic_id, progress_pct, last_read_at
  FROM user_progress
  WHERE user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_progress` (IN `p_user_id` INT, IN `p_comic_id` INT)   BEGIN
  SELECT comic_id, progress_pct, last_read_at
  FROM user_progress
  WHERE user_id = p_user_id AND comic_id = p_comic_id
  LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `increment_comic_views` (IN `comic_id` INT)   BEGIN
    UPDATE comics SET views = views + 1 WHERE id = comic_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `save_user_progress` (IN `p_user_id` INT, IN `p_comic_id` INT, IN `p_progress_pct` TINYINT)   BEGIN
  -- validações simples
  IF p_user_id IS NULL OR p_comic_id IS NULL OR p_progress_pct IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Parametros invalidos';
  END IF;

  INSERT INTO user_progress (user_id, comic_id, progress_pct)
  VALUES (p_user_id, p_comic_id, LEAST(GREATEST(p_progress_pct,0),100))
  ON DUPLICATE KEY UPDATE
    progress_pct = LEAST(GREATEST(p_progress_pct,0),100),
    last_read_at = CURRENT_TIMESTAMP;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `type` enum('user_report','system_alert','new_user','content_review') DEFAULT 'user_report',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `type`, `title`, `message`, `related_id`, `related_type`, `is_read`, `priority`, `created_at`) VALUES
(1, 'user_report', 'Novo usuário reportado', 'O usuário ID 6 foi reportado por ID 2', 6, 'user', 0, 'medium', '2025-11-10 00:58:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categories`
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
-- Estrutura para tabela `comics`
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
-- Estrutura para tabela `comic_categories`
--

CREATE TABLE `comic_categories` (
  `comic_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comic_collaborators`
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
-- Estrutura para tabela `comic_comments`
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
-- Estrutura para tabela `comic_drafts`
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
-- Estrutura para tabela `comic_pages`
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
-- Estrutura stand-in para view `comic_stats`
-- (Veja abaixo para a visão atual)
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
-- Estrutura para tabela `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `forums`
--

CREATE TABLE `forums` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `forums`
--

INSERT INTO `forums` (`id`, `name`, `description`, `category_id`, `created_at`) VALUES
(1, 'Geral', 'Discussões gerais sobre quadrinhos', NULL, '2025-10-20 23:04:36'),
(2, 'Novidades', 'Lançamentos e novidades do mundo dos quadrinhos', NULL, '2025-10-20 23:04:36'),
(3, 'Reviews', 'Análises e críticas de quadrinhos', NULL, '2025-10-20 23:04:36'),
(4, 'Dúvidas', 'Tire suas dúvidas sobre quadrinhos', NULL, '2025-10-20 23:04:36'),
(5, 'Criação', 'Discussões sobre criação de HQs', NULL, '2025-10-20 23:04:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `posts`
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
-- Estrutura para tabela `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `publishers`
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
-- Estrutura para tabela `reactions`
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
-- Estrutura para tabela `reading_progress`
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
-- Estrutura para tabela `reviews`
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
-- Estrutura para tabela `topics`
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
-- Estrutura para tabela `transactions`
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
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_file_name` varchar(255) DEFAULT NULL,
  `avatar_file_size` int(11) DEFAULT NULL,
  `avatar_mime_type` varchar(50) DEFAULT NULL,
  `avatar_updated_at` timestamp NULL DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','creator','moderator','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `avatar_file_name`, `avatar_file_size`, `avatar_mime_type`, `avatar_updated_at`, `bio`, `role`, `created_at`, `updated_at`) VALUES
(2, 'Juan Taborda', 'taborda.mjuan@gmail.com', '$2y$10$BzrSnR9AYcmK.bLQV3abV.AQ1gXidkWQLZ/rkEj6I/VfyeJS7CrFu', 'uploads/avatars/avatar_2_1762551937.png', 'avatar_4_1762435030.png', 10652, 'image/png', '2025-11-07 21:45:37', 'sou legal', 'user', '2025-10-20 23:21:30', '2025-11-07 21:45:37'),
(4, 'admin', 'admin@hqverso.com', '$2y$10$BzrSnR9AYcmK.bLQV3abV.AQ1gXidkWQLZ/rkEj6I/VfyeJS7CrFu', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2025-10-21 01:11:36', '2025-11-10 01:13:45'),
(5, 'reza+', 'ghyslainemoraes@gmail.com', '$2y$10$Bl1YDtuy/P54grfdqfTcw.vV/Gt/gj8tBR562cgU3UqlaBArIswju', 'uploads/avatars/avatar_5_1762544145.png', '3tene_20250930220016.png', 91516, 'image/png', '2025-11-07 19:35:45', NULL, 'user', '2025-11-07 19:35:20', '2025-11-07 19:35:45'),
(6, 'carro', 'a@a.com', '$2y$10$PbVOpLZio5Z1oPVbrHdZ7exgDYUYEamTRNVePFgSJOGNrYvysL8xO', NULL, NULL, NULL, NULL, NULL, NULL, 'user', '2025-11-07 23:10:41', '2025-11-07 23:10:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_follows`
--

INSERT INTO `user_follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(2, 2, 4, '2025-11-07 19:09:58'),
(7, 6, 2, '2025-11-07 23:10:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_library`
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
-- Estrutura para tabela `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `progress_pct` tinyint(4) NOT NULL DEFAULT 0,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_reports`
--

CREATE TABLE `user_reports` (
  `id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_reports`
--

INSERT INTO `user_reports` (`id`, `reported_user_id`, `reporter_user_id`, `reason`, `description`, `status`, `admin_notes`, `admin_id`, `created_at`, `updated_at`) VALUES
(1, 6, 2, 'assedio', 'me chamou de gostosa', 'pending', NULL, NULL, '2025-11-10 00:58:14', '2025-11-10 00:58:14');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `user_stats`
-- (Veja abaixo para a visão atual)
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
-- Estrutura para tabela `user_uploads`
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
-- Estrutura para view `comic_stats`
--
DROP TABLE IF EXISTS `comic_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `comic_stats`  AS SELECT `c`.`id` AS `id`, `c`.`title` AS `title`, `c`.`author_id` AS `author_id`, `u`.`username` AS `author_name`, count(distinct `r`.`id`) AS `review_count`, avg(`r`.`rating`) AS `avg_rating`, count(distinct `f`.`id`) AS `favorite_count`, count(distinct `t`.`id`) AS `library_count`, `c`.`views` AS `views` FROM ((((`comics` `c` left join `users` `u` on(`c`.`author_id` = `u`.`id`)) left join `reviews` `r` on(`c`.`id` = `r`.`comic_id`)) left join `favorites` `f` on(`c`.`id` = `f`.`comic_id`)) left join `user_library` `t` on(`c`.`id` = `t`.`comic_id`)) GROUP BY `c`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `user_stats`
--
DROP TABLE IF EXISTS `user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_stats`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`created_at` AS `created_at`, count(distinct `c`.`id`) AS `comics_created`, count(distinct `l`.`id`) AS `library_count`, count(distinct `f`.`id`) AS `favorites_count`, count(distinct `r`.`id`) AS `reviews_count` FROM ((((`users` `u` left join `comics` `c` on(`u`.`id` = `c`.`author_id`)) left join `user_library` `l` on(`u`.`id` = `l`.`user_id`)) left join `favorites` `f` on(`u`.`id` = `f`.`user_id`)) left join `reviews` `r` on(`u`.`id` = `r`.`user_id`)) GROUP BY `u`.`id` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_type` (`type`),
  ADD KEY `idx_notifications_read` (`is_read`),
  ADD KEY `idx_notifications_priority` (`priority`),
  ADD KEY `idx_notifications_created` (`created_at`);

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `comics`
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
-- Índices de tabela `comic_categories`
--
ALTER TABLE `comic_categories`
  ADD PRIMARY KEY (`comic_id`,`category_id`),
  ADD KEY `idx_comic_cat_comic` (`comic_id`),
  ADD KEY `idx_comic_cat_category` (`category_id`);

--
-- Índices de tabela `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comic_user_role` (`comic_id`,`user_id`,`role`),
  ADD KEY `idx_collaborators_comic` (`comic_id`),
  ADD KEY `idx_collaborators_user` (`user_id`);

--
-- Índices de tabela `comic_comments`
--
ALTER TABLE `comic_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_user` (`user_id`),
  ADD KEY `idx_comments_comic` (`comic_id`),
  ADD KEY `idx_comments_parent` (`parent_comment_id`);

--
-- Índices de tabela `comic_drafts`
--
ALTER TABLE `comic_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drafts_comic` (`comic_id`);

--
-- Índices de tabela `comic_pages`
--
ALTER TABLE `comic_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pages_comic` (`comic_id`),
  ADD KEY `idx_pages_number` (`page_number`);

--
-- Índices de tabela `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_fav` (`user_id`,`comic_id`),
  ADD KEY `idx_favorites_user` (`user_id`),
  ADD KEY `idx_favorites_comic` (`comic_id`);

--
-- Índices de tabela `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forums_category` (`category_id`);

--
-- Índices de tabela `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_posts_user` (`user_id`),
  ADD KEY `idx_posts_topic` (`topic_id`),
  ADD KEY `idx_posts_created` (`created_at`);

--
-- Índices de tabela `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_post_reaction` (`user_id`,`post_id`),
  ADD KEY `idx_reactions_user` (`user_id`),
  ADD KEY `idx_reactions_post` (`post_id`);

--
-- Índices de tabela `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic` (`user_id`,`comic_id`),
  ADD KEY `idx_reading_user` (`user_id`),
  ADD KEY `idx_reading_comic` (`comic_id`);

--
-- Índices de tabela `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_review` (`user_id`,`comic_id`),
  ADD KEY `idx_reviews_comic` (`comic_id`),
  ADD KEY `idx_reviews_rating` (`rating`),
  ADD KEY `idx_reviews_user` (`user_id`);

--
-- Índices de tabela `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_topics_user` (`user_id`),
  ADD KEY `idx_topics_forum` (`forum_id`),
  ADD KEY `idx_topics_comic` (`comic_id`),
  ADD KEY `idx_topics_created` (`created_at`);

--
-- Índices de tabela `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_transactions_user` (`user_id`),
  ADD KEY `idx_transactions_status` (`status`),
  ADD KEY `idx_transactions_comic` (`comic_id`),
  ADD KEY `idx_transactions_created` (`created_at`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`),
  ADD KEY `idx_users_created` (`created_at`);

--
-- Índices de tabela `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follower_following` (`follower_id`,`following_id`),
  ADD KEY `idx_follows_follower` (`follower_id`),
  ADD KEY `idx_follows_following` (`following_id`);

--
-- Índices de tabela `user_library`
--
ALTER TABLE `user_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic` (`user_id`,`comic_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_library_user` (`user_id`),
  ADD KEY `idx_library_comic` (`comic_id`);

--
-- Índices de tabela `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_user_comic` (`user_id`,`comic_id`),
  ADD KEY `ix_user` (`user_id`);

--
-- Índices de tabela `user_reports`
--
ALTER TABLE `user_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_reported_user` (`reported_user_id`),
  ADD KEY `idx_reports_reporter_user` (`reporter_user_id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_created` (`created_at`),
  ADD KEY `user_reports_ibfk_3` (`admin_id`);

--
-- Índices de tabela `user_uploads`
--
ALTER TABLE `user_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploads_user` (`user_id`),
  ADD KEY `idx_uploads_type` (`upload_type`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin_notifications`
--
ALTER TABLE `admin_notifications`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `user_library`
--
ALTER TABLE `user_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_reports`
--
ALTER TABLE `user_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `user_uploads`
--
ALTER TABLE `user_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comics`
--
ALTER TABLE `comics`
  ADD CONSTRAINT `comics_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comics_ibfk_2` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `comic_categories`
--
ALTER TABLE `comic_categories`
  ADD CONSTRAINT `comic_categories_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  ADD CONSTRAINT `comic_collaborators_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_collaborators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comic_comments`
--
ALTER TABLE `comic_comments`
  ADD CONSTRAINT `comic_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_comments_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `comic_comments` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comic_drafts`
--
ALTER TABLE `comic_drafts`
  ADD CONSTRAINT `comic_drafts_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comic_pages`
--
ALTER TABLE `comic_pages`
  ADD CONSTRAINT `comic_pages_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD CONSTRAINT `reading_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_progress_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_ibfk_2` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topics_ibfk_3` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_library`
--
ALTER TABLE `user_library`
  ADD CONSTRAINT `user_library_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_library_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_library_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_reports`
--
ALTER TABLE `user_reports`
  ADD CONSTRAINT `user_reports_ibfk_1` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reports_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `user_uploads`
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
