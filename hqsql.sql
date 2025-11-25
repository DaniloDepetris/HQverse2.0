-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/11/2025 às 20:57
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
(1, 'user_report', 'Novo usuário reportado', 'O usuário ID 6 foi reportado por ID 2', 6, 'user', 0, 'medium', '2025-11-10 00:58:14'),
(2, '', 'Nova solicitação de conta criador', 'O usuário ID 2 solicitou uma conta de criador', 2, 'user', 0, 'medium', '2025-11-13 16:40:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `banned_users`
--

CREATE TABLE `banned_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_by` int(11) NOT NULL,
  `banned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_permanent` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Despejando dados para a tabela `comics`
--

INSERT INTO `comics` (`id`, `title`, `author_id`, `publisher_id`, `cover`, `description`, `price`, `is_published`, `is_premium`, `status`, `release_date`, `page_count`, `views`, `created_at`, `updated_at`) VALUES
(1, 'Homem-Aranha: A Última Caçada', 2, NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS3IF2BDobibbGFLs-E4f1dz2cGgEjVEhZVmA&s', 'A clássica história onde o Homem-Aranha enfrenta seu maior desafio.', 0.00, 1, 0, 'published', NULL, 120, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(2, 'Watchmen', 2, NULL, 'https://upload.wikimedia.org/wikipedia/pt/d/d0/Watchmen.jpg', 'A revolucionária graphic novel que questiona a natureza dos super-heróis.', 0.00, 1, 0, 'published', NULL, 180, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(3, 'Sandman: Prelúdios e Noturnos', 2, NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcReNzUJRFsrqKb-Y4UIxg-jUSPYg-4ermAT3w&s', 'A primeira coleção da aclamada série de Neil Gaiman.', 0.00, 1, 0, 'published', NULL, 160, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(4, 'V de Vingança', 2, NULL, 'https://m.media-amazon.com/images/I/711dLCQ6kuL._UF1000,1000_QL80_.jpg', 'A distópica graphic novel sobre anarquia e liberdade.', 0.00, 1, 0, 'published', NULL, 140, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(5, 'Maus', 2, NULL, 'https://m.media-amazon.com/images/I/916IgqQ-54L.jpg', 'A premiada graphic novel sobre o Holocausto.', 0.00, 1, 0, 'published', NULL, 160, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(6, 'Persépolis', 2, NULL, 'https://m.media-amazon.com/images/I/814zhAWOKBL._UF1000,1000_QL80_.jpg', 'A autobiografia em quadrinhos sobre o Irã revolucionário.', 0.00, 1, 0, 'published', NULL, 150, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(7, 'Hellboy: Caçada Selvagem', 2, NULL, 'https://images.tcdn.com.br/img/img_prod/1119494/hellboy_omnibus_vol_3_1709745_1_a6e86cfa8f53f219f4d0d0b0c4f79558.jpg', 'As primeiras aventuras do demônio herói.', 0.00, 1, 0, 'published', NULL, 130, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(8, 'Saga', 2, NULL, 'https://m.media-amazon.com/images/I/81s49EEptML.jpg', 'A épica space opera de ficção científica.', 0.00, 1, 0, 'published', NULL, 170, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(9, 'Superman: Terra Um', 2, NULL, 'https://m.media-amazon.com/images/I/91wpPruCKrL._UF1000,1000_QL80_.jpg', 'Uma reinterpretação moderna do Homem de Aço.', 0.00, 1, 0, 'published', NULL, 110, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(10, 'Liga da Justiça: A Torre de Babel', 2, NULL, 'https://super.abril.com.br/wp-content/uploads/2018/07/torredebabel.jpg', 'Quando Batman se torna a maior ameaça da Liga.', 0.00, 1, 0, 'published', NULL, 140, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(11, 'Akira', 2, NULL, 'https://m.media-amazon.com/images/I/91F6lcEw++L._AC_UF1000,1000_QL80_.jpg', 'A épica cyberpunk que revolucionou os mangás.', 0.00, 1, 0, 'published', NULL, 220, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(12, 'Death Note', 2, NULL, 'https://www.jbchost.com.br/editorajbc/wp-content/uploads/2013/06/dn-black-edition-01.jpg', 'Um estudante genius encontra um caderno que pode matar pessoas.', 0.00, 1, 0, 'published', NULL, 180, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(13, 'Attack on Titan', 2, NULL, 'https://m.media-amazon.com/images/I/71WVN5IXeJL._AC_UF1000,1000_QL80_.jpg', 'Humanidade luta pela sobrevivência contra titãs gigantes.', 0.00, 1, 0, 'published', NULL, 190, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(14, 'One-Punch Man', 2, NULL, 'https://m.media-amazon.com/images/I/81VAgJoB3BL.jpg', 'Um herói tão forte que derrota qualquer inimigo com um só soco.', 0.00, 1, 0, 'published', NULL, 160, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(15, 'Scott Pilgrim', 2, NULL, 'https://m.media-amazon.com/images/I/81kwlJ1pcpL._AC_UF1000,1000_QL80_.jpg', 'Um baixista deve derrotar os 7 ex-namorados malvados de sua amada.', 0.00, 1, 0, 'published', NULL, 120, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(16, 'Batman: Ano Um', 2, NULL, 'https://m.media-amazon.com/images/I/61-2G84LF-L._AC_UF1000,1000_QL80_.jpg', 'A origem definitiva do Cavaleiro das Trevas.', 0.00, 1, 0, 'published', NULL, 100, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(17, 'X-Men: Fênix Negra', 2, NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmC8oS-dR6n1EW5YxEnvL0mPaz13taAKsbvQ&s', 'A épica saga onde Jean Grey se torna a Fênix Negra.', 0.00, 1, 0, 'published', NULL, 150, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(18, 'Monstress', 2, NULL, 'https://m.media-amazon.com/images/I/81bGs636lzL.jpg', 'Fantasia sombria em um mundo de guerra e monstros.', 0.00, 1, 0, 'published', NULL, 160, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(19, 'Homem de Ferro: Extremis', 2, NULL, 'https://m.media-amazon.com/images/I/71RiKAW7WTL._AC_UF1000,1000_QL80_.jpg', 'A história que redefiniu o Homem de Ferro moderno.', 0.00, 1, 0, 'published', NULL, 110, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(20, 'Capitão América: O Soldado Invernal', 2, NULL, 'https://m.media-amazon.com/images/I/611wcUISMmL._UF1000,1000_QL80_.jpg', 'Um thriller político que coloca o Capitão América contra inimigos internos.', 0.00, 1, 0, 'published', NULL, 130, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(21, 'Thor: Deus do Trovão', 2, NULL, 'https://m.media-amazon.com/images/I/91JTRo6EFcL._UF1000,1000_QL80_.jpg', 'Uma saga épica do Deus do Trovão através dos tempos.', 0.00, 1, 0, 'published', NULL, 140, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(22, 'Doutor Estranho: O Juramento', 2, NULL, 'https://m.media-amazon.com/images/I/91DcEu1b-rL.jpg', 'Uma história íntima e sombria do Mago Supremo.', 0.00, 1, 0, 'published', NULL, 120, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(23, 'Pantera Negra: Rei do Wakanda', 2, NULL, 'https://d14d9vp3wdof84.cloudfront.net/image/589816272436/image_v8bl17fqv95mf8v1jd9k8lrp5r/-S897-FWEBP', 'A jornada do rei e herói de Wakanda.', 0.00, 1, 0, 'published', NULL, 130, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(24, 'Batman: Silêncio', 2, NULL, 'https://m.media-amazon.com/images/I/71VZGfpEH6L._AC_UF1000,1000_QL80_.jpg', 'Uma intensa história de Batman escrita por Jeph Loeb.', 0.00, 1, 0, 'published', NULL, 150, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(25, 'Mulher-Maravilha: Deuses e Mortais', 2, NULL, 'https://cdn.awsli.com.br/600x450/1668/1668242/produto/162790896905299a6a0.jpg', 'A reinvenção da origem da Mulher-Maravilha por George Pérez.', 0.00, 1, 0, 'published', NULL, 140, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(26, 'Flashpoint', 2, NULL, 'https://m.media-amazon.com/images/I/91dXNvO2fML.jpg', 'Uma linha temporal alternativa que altera o universo DC.', 0.00, 1, 0, 'published', NULL, 160, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(27, 'Arqueiro Verde: Ano Um', 2, NULL, 'https://rika.vtexassets.com/arquivos/ids/219835/-herois_panini-arqueiro-verde-ano-um.jpg?v=635316153891630000', 'A origem moderna do Arqueiro Verde.', 0.00, 1, 0, 'published', NULL, 110, 0, '2025-11-13 05:13:28', '2025-11-13 05:13:28'),
(28, 'Scott Pilgrim - contra o munso', 2, NULL, 'https://m.media-amazon.com/images/I/81KTDbue42S._AC_UF1000,1000_QL80_.jpg', 'um canadense luta contra os 7 ex-namorados do mal da sua namorada.', 0.00, 1, 0, 'published', NULL, 1, 0, '2025-11-13 17:29:55', '2025-11-13 17:29:55');

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

--
-- Despejando dados para a tabela `comic_categories`
--

INSERT INTO `comic_categories` (`comic_id`, `category_id`) VALUES
(1, 1),
(1, 4),
(2, 1),
(2, 3),
(2, 4),
(3, 3),
(3, 4),
(4, 3),
(4, 4),
(5, 3),
(5, 4),
(6, 3),
(6, 4),
(7, 1),
(7, 5),
(8, 3),
(8, 5),
(9, 1),
(9, 4),
(10, 1),
(10, 4),
(11, 2),
(11, 4),
(12, 2),
(13, 2),
(14, 1),
(14, 2),
(15, 3),
(15, 5),
(16, 1),
(16, 4),
(17, 1),
(17, 4),
(18, 3),
(18, 5),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(24, 4),
(25, 1),
(25, 4),
(26, 1),
(26, 4),
(27, 1),
(27, 4),
(28, 1),
(28, 5),
(28, 9);

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
  `comment` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `contains_spoilers` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `comic_comments`
--

INSERT INTO `comic_comments` (`id`, `user_id`, `comic_id`, `comment`, `parent_comment_id`, `contains_spoilers`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 2, 7, 'salve', NULL, 0, 1, '2025-11-13 06:10:30', '2025-11-13 06:10:30'),
(2, 2, 7, 'salve', 1, 0, 1, '2025-11-13 06:17:43', '2025-11-13 06:17:43'),
(3, 4, 3, 'morreu no final', NULL, 1, 1, '2025-11-13 07:43:24', '2025-11-13 07:43:24');

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

--
-- Despejando dados para a tabela `comic_pages`
--

INSERT INTO `comic_pages` (`id`, `comic_id`, `page_number`, `image_url`, `title`, `created_at`) VALUES
(1, 1, 1, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTg9az9jtGfQoj20u_4hUEeTQXvXvX9i0W2KPKw&s', 'Capa', '2025-11-13 05:34:12'),
(2, 1, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Homem-Aranha+Página+1', 'Peter Parker em ação', '2025-11-13 05:34:12'),
(3, 1, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Homem-Aranha+Página+2', 'O vilão aparece', '2025-11-13 05:34:12'),
(4, 1, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Homem-Aranha+Página+3', 'Confronto épico', '2025-11-13 05:34:12'),
(5, 1, 5, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Homem-Aranha+Página+4', 'Reviravolta final', '2025-11-13 05:34:12'),
(6, 2, 1, 'https://upload.wikimedia.org/wikipedia/pt/d/d0/Watchmen.jpg', 'Capa', '2025-11-13 05:34:12'),
(7, 2, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Watchmen+Página+1', 'Rorschach investiga', '2025-11-13 05:34:12'),
(8, 2, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Watchmen+Página+2', 'Dr. Manhattan', '2025-11-13 05:34:12'),
(9, 2, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Watchmen+Página+3', 'Ozymandias planeja', '2025-11-13 05:34:12'),
(10, 2, 5, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Watchmen+Página+4', 'Confronto final', '2025-11-13 05:34:12'),
(11, 3, 1, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcReNzUJRFsrqKb-Y4UIxg-jUSPYg-4ermAT3w&s', 'Capa', '2025-11-13 05:34:12'),
(12, 3, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Sandman+Página+1', 'Morpheus aprisionado', '2025-11-13 05:34:12'),
(13, 3, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Sandman+Página+2', 'A fuga', '2025-11-13 05:34:12'),
(14, 3, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Sandman+Página+3', 'Busca pelos artefatos', '2025-11-13 05:34:12'),
(15, 4, 1, 'https://m.media-amazon.com/images/I/711dLCQ6kuL._UF1000,1000_QL80_.jpg', 'Capa', '2025-11-13 05:34:12'),
(16, 4, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=V+Vingança+Página+1', 'V aparece', '2025-11-13 05:34:12'),
(17, 4, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=V+Vingança+Página+2', 'Evey capturada', '2025-11-13 05:34:12'),
(18, 4, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=V+Vingança+Página+3', 'Revelações', '2025-11-13 05:34:12'),
(19, 5, 1, 'https://m.media-amazon.com/images/I/916IgqQ-54L.jpg', 'Capa', '2025-11-13 05:34:12'),
(20, 5, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Maus+Página+1', 'História do pai', '2025-11-13 05:34:12'),
(21, 5, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Maus+Página+2', 'Guerra começa', '2025-11-13 05:34:12'),
(22, 5, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Maus+Página+3', 'Sobrevivência', '2025-11-13 05:34:12'),
(23, 6, 1, 'https://m.media-amazon.com/images/I/814zhAWOKBL._UF1000,1000_QL80_.jpg', 'Capa', '2025-11-13 05:34:12'),
(24, 6, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Persépolis+Página+1', 'Infância no Irã', '2025-11-13 05:34:12'),
(25, 6, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Persépolis+Página+2', 'Revolução', '2025-11-13 05:34:12'),
(26, 6, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Persépolis+Página+3', 'Mudança para Europa', '2025-11-13 05:34:12'),
(27, 7, 1, 'https://images.tcdn.com.br/img/img_prod/1119494/hellboy_omnibus_vol_3_1709745_1_a6e86cfa8f53f219f4d0d0b0c4f79558.jpg', 'Capa', '2025-11-13 05:34:12'),
(28, 7, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Hellboy+Página+1', 'Hellboy em ação', '2025-11-13 05:34:12'),
(29, 7, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Hellboy+Página+2', 'Criaturas sobrenaturais', '2025-11-13 05:34:12'),
(30, 7, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Hellboy+Página+3', 'Confronto épico', '2025-11-13 05:34:12'),
(31, 8, 1, 'https://m.media-amazon.com/images/I/81s49EEptML.jpg', 'Capa', '2025-11-13 05:34:12'),
(32, 8, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Saga+Página+1', 'Nascimento de Hazel', '2025-11-13 05:34:12'),
(33, 8, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Saga+Página+2', 'Fuga pelos planetas', '2025-11-13 05:34:12'),
(34, 8, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Saga+Página+3', 'Encontro com aliados', '2025-11-13 05:34:12'),
(35, 9, 1, 'https://m.media-amazon.com/images/I/91wpPruCKrL._UF1000,1000_QL80_.jpg', 'Capa', '2025-11-13 05:34:12'),
(36, 9, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Superman+Página+1', 'Clark em Smallville', '2025-11-13 05:34:12'),
(37, 9, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Superman+Página+2', 'Chegada em Metropolis', '2025-11-13 05:34:12'),
(38, 9, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Superman+Página+3', 'Primeiro voo', '2025-11-13 05:34:12'),
(39, 10, 1, 'https://super.abril.com.br/wp-content/uploads/2018/07/torredebabel.jpg', 'Capa', '2025-11-13 05:34:12'),
(40, 10, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Torre+Babel+Página+1', 'Batman vigiando', '2025-11-13 05:34:12'),
(41, 10, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Torre+Babel+Página+2', 'Planos roubados', '2025-11-13 05:34:12'),
(42, 10, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Torre+Babel+Página+3', 'Liga atacada', '2025-11-13 05:34:12'),
(43, 11, 1, 'https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg', 'Capa', '2025-11-13 05:34:12'),
(44, 11, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Akira+Página+1', 'Neo-Tóquio', '2025-11-13 05:34:12'),
(45, 11, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Akira+Página+2', 'Kaneda e a gangue', '2025-11-13 05:34:12'),
(46, 11, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Akira+Página+3', 'Poderes psíquicos', '2025-11-13 05:34:12'),
(47, 12, 1, 'https://m.media-amazon.com/images/I/81MZ6eFQsfL.jpg', 'Capa', '2025-11-13 05:34:12'),
(48, 12, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Death+Note+Página+1', 'Light encontra Death Note', '2025-11-13 05:34:12'),
(49, 12, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Death+Note+Página+2', 'Ryuk aparece', '2025-11-13 05:34:12'),
(50, 12, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Death+Note+Página+3', 'L inicia investigação', '2025-11-13 05:34:12'),
(51, 13, 1, 'https://m.media-amazon.com/images/I/81d6e+kN5+L.jpg', 'Capa', '2025-11-13 05:34:12'),
(52, 13, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Attack+Titan+Página+1', 'Muralha é quebrada', '2025-11-13 05:34:12'),
(53, 13, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Attack+Titan+Página+2', 'Eren transforma', '2025-11-13 05:34:12'),
(54, 13, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Attack+Titan+Página+3', 'Batalha contra titãs', '2025-11-13 05:34:12'),
(55, 14, 1, 'https://m.media-amazon.com/images/I/81I1+-+0R0L.jpg', 'Capa', '2025-11-13 05:34:12'),
(56, 14, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=One-Punch+Página+1', 'Saitama entediado', '2025-11-13 05:34:12'),
(57, 14, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=One-Punch+Página+2', 'Monstro aparece', '2025-11-13 05:34:12'),
(58, 14, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=One-Punch+Página+3', 'Um soco só', '2025-11-13 05:34:12'),
(59, 15, 1, 'https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg', 'Capa', '2025-11-13 05:34:12'),
(60, 15, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Scott+Pilgrim+Página+1', 'Scott conhece Ramona', '2025-11-13 05:34:12'),
(61, 15, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Scott+Pilgrim+Página+2', 'Primeiro ex-namorado', '2025-11-13 05:34:12'),
(62, 15, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Scott+Pilgrim+Página+3', 'Batalha musical', '2025-11-13 05:34:12'),
(63, 16, 1, 'https://m.media-amazon.com/images/I/81zK5OjR5aL.jpg', 'Capa', '2025-11-13 05:34:12'),
(64, 16, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Batman+Ano+Um+Página+1', 'Bruce volta para Gotham', '2025-11-13 05:34:12'),
(65, 16, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Batman+Ano+Um+Página+2', 'Primeira aparição', '2025-11-13 05:34:12'),
(66, 16, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Batman+Ano+Um+Página+3', 'Jim Gordon chega', '2025-11-13 05:34:12'),
(67, 17, 1, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmC8oS-dR6n1EW5YxEnvL0mPaz13taAKsbvQ&s', 'Capa', '2025-11-13 05:34:12'),
(68, 17, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Fênix+Negra+Página+1', 'Jean Grey como Fênix', '2025-11-13 05:34:12'),
(69, 17, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Fênix+Negra+Página+2', 'Poderes descontrolados', '2025-11-13 05:34:12'),
(70, 17, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Fênix+Negra+Página+3', 'X-Men confrontam Jean', '2025-11-13 05:34:12'),
(71, 18, 1, 'https://m.media-amazon.com/images/I/81bGs636lzL.jpg', 'Capa', '2025-11-13 05:34:12'),
(72, 18, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Monstress+Página+1', 'Maika Halfwolf', '2025-11-13 05:34:12'),
(73, 18, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Monstress+Página+2', 'Mundo de guerra', '2025-11-13 05:34:12'),
(74, 18, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Monstress+Página+3', 'Monstro interior', '2025-11-13 05:34:12'),
(75, 19, 1, 'https://m.media-amazon.com/images/I/81bGs636lzL.jpg', 'Capa', '2025-11-13 05:34:12'),
(76, 19, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Extremis+Página+1', 'Tony Stark ferido', '2025-11-13 05:34:12'),
(77, 19, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Extremis+Página+2', 'Tecnologia Extremis', '2025-11-13 05:34:12'),
(78, 19, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Extremis+Página+3', 'Nova armadura', '2025-11-13 05:34:12'),
(79, 20, 1, 'https://m.media-amazon.com/images/I/611wcUISMmL._UF1000,1000_QL80_.jpg', 'Capa', '2025-11-13 05:34:12'),
(80, 20, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Soldado+Invernal+Página+1', 'Steve Rogers em ação', '2025-11-13 05:34:12'),
(81, 20, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Soldado+Invernal+Página+2', 'Soldado Invernal aparece', '2025-11-13 05:34:12'),
(82, 20, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Soldado+Invernal+Página+3', 'Revelação chocante', '2025-11-13 05:34:12'),
(83, 21, 1, 'https://m.media-amazon.com/images/I/91JTRo6EFcL._UF1000,1000_QL80_.jpg', 'Capa', '2025-11-13 05:34:12'),
(84, 21, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Thor+Página+1', 'Thor através dos tempos', '2025-11-13 05:34:12'),
(85, 21, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Thor+Página+2', 'Gorr o Carniceiro', '2025-11-13 05:34:12'),
(86, 21, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Thor+Página+3', 'Batalha épica', '2025-11-13 05:34:12'),
(87, 22, 1, 'https://m.media-amazon.com/images/I/91DcEu1b-rL.jpg', 'Capa', '2025-11-13 05:34:12'),
(88, 22, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Doutor+Estranho+Página+1', 'Stephen Strange doente', '2025-11-13 05:34:12'),
(89, 22, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Doutor+Estranho+Página+2', 'Busca pela cura', '2025-11-13 05:34:12'),
(90, 22, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Doutor+Estranho+Página+3', 'Magia e medicina', '2025-11-13 05:34:12'),
(91, 23, 1, 'https://d14d9vp3wdof84.cloudfront.net/image/589816272436/image_v8bl17fqv95mf8v1jd9k8lrp5r/-S897-FWEBP', 'Capa', '2025-11-13 05:34:12'),
(92, 23, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Pantera+Negra+Página+1', 'TChalla como rei', '2025-11-13 05:34:12'),
(93, 23, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Pantera+Negra+Página+2', 'Wakanda revelada', '2025-11-13 05:34:12'),
(94, 23, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Pantera+Negra+Página+3', 'Desafio ao trono', '2025-11-13 05:34:12'),
(95, 24, 1, 'https://lh3.googleusercontent.com/proxy/9y2rp6F2x4dSCvFkZoz847oXtBE8IP0mscS0W0SkYpRtdub4qCQRCzj-Qwfgd4BWQq6EtqSmr7edCB_rNckNCs8pGT8jFx0HdMknRmb_1EmPWIb5zuujbw', 'Capa', '2025-11-13 05:34:12'),
(96, 24, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Silêncio+Página+1', 'Batman investiga', '2025-11-13 05:34:12'),
(97, 24, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Silêncio+Página+2', 'Hush aparece', '2025-11-13 05:34:12'),
(98, 24, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Silêncio+Página+3', 'Identidade revelada', '2025-11-13 05:34:12'),
(99, 25, 1, 'https://cdn.awsli.com.br/600x450/1668/1668242/produto/162790896905299a6a0.jpg', 'Capa', '2025-11-13 05:34:12'),
(100, 25, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Mulher+Maravilha+Página+1', 'Themyscira', '2025-11-13 05:34:12'),
(101, 25, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Mulher+Maravilha+Página+2', 'Diana chega ao mundo', '2025-11-13 05:34:12'),
(102, 25, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Mulher+Maravilha+Página+3', 'Primeira missão', '2025-11-13 05:34:12'),
(103, 26, 1, 'https://m.media-amazon.com/images/I/91dXNvO2fML.jpg', 'Capa', '2025-11-13 05:34:12'),
(104, 26, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Flashpoint+Página+1', 'Barry acorda em novo mundo', '2025-11-13 05:34:12'),
(105, 26, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Flashpoint+Página+2', 'Realidade alternativa', '2025-11-13 05:34:12'),
(106, 26, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Flashpoint+Página+3', 'Guerra Atlante-Amazona', '2025-11-13 05:34:12'),
(107, 27, 1, 'https://rika.vtexassets.com/arquivos/ids/219835/-herois_panini-arqueiro-verde-ano-um.jpg?v=635316153891630000', 'Capa', '2025-11-13 05:34:12'),
(108, 27, 2, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Arqueiro+Verde+Página+1', 'Oliver Queen naufraga', '2025-11-13 05:34:12'),
(109, 27, 3, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Arqueiro+Verde+Página+2', 'Ilha deserta', '2025-11-13 05:34:12'),
(110, 27, 4, 'https://via.placeholder.com/700x1000/1a1a2e/e94560?text=Arqueiro+Verde+Página+3', 'Retorno a Star City', '2025-11-13 05:34:12'),
(111, 28, 1, 'uploads/pages/page_28_1_1763054995.jpg', 'Página 1', '2025-11-13 17:29:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comic_ratings`
--

CREATE TABLE `comic_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `comic_ratings`
--

INSERT INTO `comic_ratings` (`id`, `user_id`, `comic_id`, `rating`, `created_at`, `updated_at`) VALUES
(1, 2, 7, 5, '2025-11-13 06:05:00', '2025-11-13 06:05:00'),
(2, 4, 3, 5, '2025-11-13 18:15:11', '2025-11-13 18:15:11');

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
-- Estrutura para tabela `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `comment_likes`
--

INSERT INTO `comment_likes` (`id`, `user_id`, `comment_id`, `created_at`) VALUES
(1, 2, 1, '2025-11-13 06:26:00'),
(2, 2, 2, '2025-11-13 06:26:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `last_message_at`, `created_at`) VALUES
(1, 2, 7, '2025-11-13 02:39:55', '2025-11-10 04:01:12'),
(2, 4, 5, '2025-11-13 00:48:32', '2025-11-13 00:47:45'),
(3, 2, 5, '2025-11-13 02:40:11', '2025-11-13 01:00:07'),
(4, 5, 6, '2025-11-13 17:14:11', '2025-11-13 17:14:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `creator_requests`
--

CREATE TABLE `creator_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `address` text NOT NULL,
  `age` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `creator_requests`
--

INSERT INTO `creator_requests` (`id`, `user_id`, `cpf`, `address`, `age`, `status`, `requested_at`, `processed_at`, `admin_id`, `admin_notes`) VALUES
(1, 2, '13971969984', 'feijão com farinha', 18, 'approved', '2025-11-13 16:40:12', '2025-11-13 16:40:54', 4, '');

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
-- Estrutura para tabela `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `is_read`, `created_at`) VALUES
(1, 2, 4, 'e ai macho', 1, '2025-11-13 00:47:55'),
(2, 2, 5, 'salve doido', 0, '2025-11-13 00:48:32'),
(3, 3, 5, 'carro', 1, '2025-11-13 01:00:15'),
(4, 3, 5, 'odeio esse cara', 1, '2025-11-13 01:02:30'),
(5, 1, 2, 'oxe baitola oq que tu quer?', 0, '2025-11-13 02:39:55'),
(6, 3, 2, 'eu tbm me chamou de gostosa', 1, '2025-11-13 02:40:11'),
(7, 4, 5, 'e ai?', 0, '2025-11-13 17:14:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `page_comments`
--

CREATE TABLE `page_comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `page_number` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `page_comments`
--
DELIMITER $$
CREATE TRIGGER `before_page_comment_update` BEFORE UPDATE ON `page_comments` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `page_ratings`
--

CREATE TABLE `page_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comic_id` int(11) NOT NULL,
  `page_number` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contains_spoilers` tinyint(1) DEFAULT 0
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `favorite_hero` varchar(20) DEFAULT 'batman',
  `nationality` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `pronouns` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `avatar_file_name`, `avatar_file_size`, `avatar_mime_type`, `avatar_updated_at`, `bio`, `role`, `created_at`, `updated_at`, `favorite_hero`, `nationality`, `age`, `pronouns`) VALUES
(2, 'Juan Taborda', 'taborda.mjuan@gmail.com', '$2y$10$BzrSnR9AYcmK.bLQV3abV.AQ1gXidkWQLZ/rkEj6I/VfyeJS7CrFu', 'uploads/avatars/avatar_2_1762812275.png', '3tene_20250930220016.png', 91516, 'image/png', '2025-11-10 22:04:35', 'sou legal', 'creator', '2025-10-20 23:21:30', '2025-11-13 16:40:54', 'batman', NULL, NULL, NULL),
(4, 'admin', 'admin@hqverso.com', '$2y$10$BzrSnR9AYcmK.bLQV3abV.AQ1gXidkWQLZ/rkEj6I/VfyeJS7CrFu', 'uploads/avatars/avatar_4_1762988992.png', 'avatar_4_1762435030.png', 10652, 'image/png', '2025-11-12 23:09:52', 'sou bilola', 'admin', '2025-10-21 01:11:36', '2025-11-12 23:09:52', 'batman', NULL, NULL, NULL),
(5, 'reza+', 'ghyslainemoraes@gmail.com', '$2y$10$Bl1YDtuy/P54grfdqfTcw.vV/Gt/gj8tBR562cgU3UqlaBArIswju', 'uploads/avatars/avatar_5_1763054020.gif', 'sr20a1666d409aws3.gif', 48568, 'image/gif', '2025-11-13 17:13:40', NULL, 'user', '2025-11-07 19:35:20', '2025-11-13 17:13:40', 'batman', NULL, NULL, NULL),
(6, 'carro', 'a@a.com', '$2y$10$PbVOpLZio5Z1oPVbrHdZ7exgDYUYEamTRNVePFgSJOGNrYvysL8xO', NULL, NULL, NULL, NULL, NULL, NULL, 'user', '2025-11-07 23:10:41', '2025-11-07 23:10:41', 'batman', NULL, NULL, NULL),
(7, 'souchato', 'souchato@gmail.com', '$2y$10$y14kdrgtyszdqo5ifsaoBOiQCCMPwIU5tk6Ud6.IBfBq3D/QgMmae', NULL, NULL, NULL, NULL, NULL, NULL, 'user', '2025-11-10 03:45:40', '2025-11-10 03:45:40', 'batman', NULL, NULL, NULL);

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
(7, 6, 2, '2025-11-07 23:10:41'),
(9, 7, 2, '2025-11-10 03:45:40'),
(10, 4, 2, '2025-11-12 01:21:08');

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

--
-- Despejando dados para a tabela `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `comic_id`, `progress_pct`, `last_read_at`) VALUES
(1, 2, 3, 50, '2025-11-13 06:39:44'),
(3, 2, 12, 75, '2025-11-13 05:39:46'),
(5, 2, 2, 100, '2025-11-13 05:40:32'),
(7, 2, 10, 25, '2025-11-13 04:35:51'),
(13, 2, 9, 25, '2025-11-11 01:23:56'),
(17, 4, 2, 25, '2025-11-13 07:56:54'),
(19, 4, 3, 25, '2025-11-13 18:15:21'),
(21, 4, 9, 100, '2025-11-12 23:29:49'),
(25, 5, 2, 50, '2025-11-13 00:49:08'),
(34, 2, 11, 25, '2025-11-13 05:07:36'),
(38, 2, 7, 75, '2025-11-13 05:19:46'),
(41, 2, 5, 100, '2025-11-13 05:31:39'),
(84, 2, 13, 75, '2025-11-13 05:39:25'),
(135, 2, 28, 25, '2025-11-13 17:30:28'),
(141, 4, 13, 100, '2025-11-13 18:14:58');

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
(1, 6, 2, 'assedio', 'me chamou de gostosa', 'resolved', NULL, 4, '2025-11-10 00:58:14', '2025-11-12 22:46:12');

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
-- Índices de tabela `banned_users`
--
ALTER TABLE `banned_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_banned_by` (`banned_by`),
  ADD KEY `idx_banned_at` (`banned_at`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comic_id` (`comic_id`),
  ADD KEY `idx_comic_comments_parent_id` (`parent_comment_id`);

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
-- Índices de tabela `comic_ratings`
--
ALTER TABLE `comic_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_rating` (`user_id`,`comic_id`),
  ADD KEY `idx_comic_ratings_comic_id` (`comic_id`);

--
-- Índices de tabela `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comment_like` (`user_id`,`comment_id`),
  ADD KEY `idx_comment_likes_comment_id` (`comment_id`);

--
-- Índices de tabela `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`),
  ADD KEY `idx_conversation_user1` (`user1_id`),
  ADD KEY `idx_conversation_user2` (`user2_id`);

--
-- Índices de tabela `creator_requests`
--
ALTER TABLE `creator_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pending_request` (`user_id`,`status`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

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
-- Índices de tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_conversation` (`conversation_id`),
  ADD KEY `idx_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Índices de tabela `page_comments`
--
ALTER TABLE `page_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_page_comments_user` (`user_id`),
  ADD KEY `idx_page_comments_comic` (`comic_id`),
  ADD KEY `idx_page_comments_page` (`page_number`),
  ADD KEY `idx_page_comments_parent` (`parent_comment_id`);

--
-- Índices de tabela `page_ratings`
--
ALTER TABLE `page_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_comic_page_rating` (`user_id`,`comic_id`,`page_number`),
  ADD KEY `idx_page_ratings_comic` (`comic_id`),
  ADD KEY `idx_page_ratings_user` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `comics`
--
ALTER TABLE `comics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `comic_collaborators`
--
ALTER TABLE `comic_collaborators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comic_comments`
--
ALTER TABLE `comic_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `comic_drafts`
--
ALTER TABLE `comic_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comic_pages`
--
ALTER TABLE `comic_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT de tabela `comic_ratings`
--
ALTER TABLE `comic_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `creator_requests`
--
ALTER TABLE `creator_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `page_comments`
--
ALTER TABLE `page_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `page_ratings`
--
ALTER TABLE `page_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `user_library`
--
ALTER TABLE `user_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

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
-- Restrições para tabelas `banned_users`
--
ALTER TABLE `banned_users`
  ADD CONSTRAINT `banned_users_ibfk_1` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Restrições para tabelas `comic_ratings`
--
ALTER TABLE `comic_ratings`
  ADD CONSTRAINT `comic_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_ratings_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`comment_id`) REFERENCES `comic_comments` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `creator_requests`
--
ALTER TABLE `creator_requests`
  ADD CONSTRAINT `creator_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `creator_requests_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Restrições para tabelas `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
