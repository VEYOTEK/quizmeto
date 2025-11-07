-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2025 at 10:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quizmeto`
--

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `answer_text`, `is_correct`) VALUES
(1, 1, 'Ankara', 1),
(2, 1, 'İstanbul', 0),
(3, 1, 'İzmir', 0),
(4, 1, 'Bursa', 0),
(5, 2, 'Pasifik Okyanusu', 1),
(6, 2, 'Atlantik Okyanusu', 0),
(7, 2, 'Hint Okyanusu', 0),
(8, 2, 'Arktik Okyanusu', 0),
(9, 3, '206', 1),
(10, 3, '180', 0),
(11, 3, '300', 0),
(12, 3, '150', 0),
(17, 5, 'Karpaz', 0),
(18, 5, 'Lefkoşa', 1),
(19, 5, 'İskele', 0),
(20, 5, 'Güzelyurt', 0),
(21, 6, 'TMK', 0),
(22, 6, 'AML', 0),
(23, 6, 'SSEML', 1),
(24, 6, 'OÖML', 0),
(25, 7, 'Girne', 0),
(26, 7, 'Karpaz', 0),
(27, 7, 'İskele', 0),
(28, 7, 'Lefkoşa', 1),
(29, 8, 'Akdeniz', 1),
(30, 8, 'Ege Denizi', 0),
(31, 8, 'Marmara Denizi', 0),
(32, 8, 'Karadeniz', 0),
(33, 9, 'Molehiya', 0),
(34, 9, 'magarina bulli', 0),
(35, 9, 'Kapuska', 1),
(36, 9, 'Kolokas', 0),
(37, 10, 'Mustafa Akıncı', 0),
(38, 10, 'Ersin tatar', 1),
(39, 10, 'Rauf Raif Denktaş', 0),
(40, 10, 'Dr. Fazıl Küçük', 0),
(41, 11, 'İsmet inönü', 0),
(42, 11, 'Bülent ecevit', 0),
(43, 11, 'Turan Güneş', 1),
(44, 11, 'Recep tayyip erdoğan(opsiyonel)', 0),
(45, 12, 'yanlış', 0),
(46, 12, 'doğru', 1),
(47, 12, 'hayır', 0),
(48, 12, 'eh', 0),
(49, 13, 'Sadece oyun oynamaya yarayan sistemlerdir.', 0),
(50, 13, 'Sadece askeri alanlarda kullanılır.', 0),
(51, 13, 'Sadece bilgisayar tamirinde kullanılır.', 0),
(52, 13, 'Bilginin toplanması, işlenmesi ve iletilmesinde kullanılan teknolojilerdir.', 1),
(53, 14, 'Sadece fabrikalarda kullanılır, günlük yaşama etkisi yoktur.', 0),
(54, 14, 'İnsanları tamamen işsiz bırakır.', 0),
(55, 14, 'Asistanlar, öneri sistemleri, yüz tanıma gibi pek çok alanda işleri kolaylaştırır.', 1),
(56, 14, 'Sadece bilgisayarların daha hızlı çalışmasını sağlar.', 0),
(57, 15, 'Güçlü şifreler, güvenlik yazılımları ve bilinçli internet kullanımı verileri korur.', 1),
(58, 15, 'Şifre kullanmak gerekmez.', 0),
(59, 15, 'Antivirüs yazılımları sadece oyunları yavaşlatır.', 0),
(60, 15, 'Bilgisayara hiç bağlanmamak en iyi güvenliktir.', 0),
(61, 16, 'Bilgilerin sadece USB bellekte saklanmasıdır.', 0),
(62, 16, 'Verilere internet üzerinden her yerden erişim sağlayabilen bir teknolojidir.', 1),
(63, 16, 'Bilgisayarın donanımına zarar verir.', 0),
(64, 16, ' Bilgilerin internet olmadan paylaşılmasıdır.', 0),
(65, 17, 'Buharla çalışan makineler.', 0),
(66, 17, 'Daktilolar.', 0),
(67, 17, 'Analog telefonlar.', 0),
(68, 17, ' Yapay zeka, nesnelerin interneti ve artırılmış gerçeklik gibi yeni teknolojiler.', 1),
(69, 18, ' İnsanların sadece yalnız kalmasına neden olur.', 0),
(70, 18, 'Hem olumlu hem olumsuz etkiler yaratabilir; bilgiye erişimi kolaylaştırır ama bağımlılık da yaratabilir.', 1),
(71, 18, 'Toplumu birbirinden tamamen koparır.', 0),
(72, 18, 'Sosyal medya sadece eğlence içindir, başka etkisi yoktur.', 0),
(73, 19, 'Bilgisayar oyunlarında kullanılan bir yazılımdır.', 0),
(74, 19, 'Sadece şifre kırmak için kullanılır.', 0),
(75, 19, ' Büyük veri kümeleri içinde anlamlı bilgiler bulmak için kullanılan bir analiz yöntemidir.', 1),
(76, 19, ' Gerçek maden kazmak için kullanılan bir sistemdir.', 0),
(77, 20, 'Sadece oyun oynamak için kullanılır.', 0),
(78, 20, 'Eğitimde teknolojinin yeri yoktur. ', 0),
(79, 20, 'Mobil cihazlar dikkati dağıttığı için tamamen zararlıdır.', 0),
(80, 20, ' Uzaktan eğitim, dijital içerikler ve eğitim uygulamaları ile öğrenmeyi destekler.', 1),
(81, 21, ' Dağıtık ve güvenli yapısıyla veri takibini sağlar, finans ve sağlık gibi birçok alanda kullanılır.', 1),
(82, 21, 'Sadece Bitcoin için kullanılır, başka alanı yoktur.', 0),
(83, 21, 'Tek kişinin kontrol ettiği bir veritabanıdır.', 0),
(84, 21, 'Kolayca değiştirilebilen bir yapıya sahiptir.', 0),
(85, 22, 'Tüm meslekler yok olacak.', 0),
(86, 22, 'Hiçbir etkisi olmayacak.', 0),
(87, 22, 'Bazı meslekler otomasyona geçecek ama yeni meslekler de ortaya çıkacak.', 1),
(88, 22, 'Sadece mühendisleri etkiler.', 0),
(109, 27, 'y', 0),
(110, 27, 'd', 1),
(111, 27, 'y', 0),
(112, 27, 'y', 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`) VALUES
(1, 'Genel Kültür', 'Genel bilgi ve kültür soruları', 'globe'),
(2, 'Bilim', 'Bilim ve teknoloji hakkında sorular', 'flask');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `points` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `image_url`, `points`) VALUES
(1, 1, 'Türkiye\'nin başkenti hangi şehirdir?', NULL, 10),
(2, 1, 'Dünyanın en büyük okyanusu hangisidir?', NULL, 10),
(3, 1, 'İnsan vücudunda kaç kemik bulunur?', NULL, 10),
(5, 1, 'Kıbrısın Başkenti Neresidir?', '', 10),
(6, 1, 'KKTC de teknodfestde Elektirikli Araba kategorisinde biribcilik elde eden ilk ve tek okul hangisidir', '', 10),
(7, 1, 'Girne kapısı nerede bulunur', '', 10),
(8, 1, 'Kıbrıs Hangi Denizde yer almaktadır', '', 10),
(9, 1, 'aşşağılardan hangisi kıbrısa özgü bir yemek değildir', '', 10),
(10, 1, 'KKTC nin şuanki cumhur başkanı', '', 10),
(11, 1, 'Ayşeyi tatile çıkarın sözüyle kıbrıs harekatını başlatan kimdir', '', 10),
(12, 1, 'test', '', 10),
(13, 2, 'Bilişim teknolojileri nedir ve hangi alanlarda kullanılır?', '', 10),
(14, 2, 'Yapay zeka (AI) günlük yaşantımızı nasıl etkiliyor?', '', 10),
(15, 2, 'Siber güvenlik neden önemlidir ve kişisel verilerimizi nasıl koruyabiliriz?', '', 10),
(16, 2, 'Bulut bilişim (cloud computing) nedir ve avantajları nelerdir?', '', 10),
(17, 2, 'Gelecekte hangi teknolojiler hayatımızda daha fazla yer alacak?', '', 10),
(18, 2, 'Sosyal medya teknolojileri bireyler ve toplumlar üzerinde ne tür etkiler yaratıyor?', '', 10),
(19, 2, 'Veri madenciliği (data mining) nedir ve hangi amaçlarla kullanılır?', '', 10),
(20, 2, 'Mobil teknolojiler eğitim alanında nasıl kullanılıyor?', '', 10),
(21, 2, 'Blockchain teknolojisinin çalışma prensibi nedir ve hangi alanlarda kullanılabilir?', '', 10),
(22, 2, 'Yapay zekânın gelişmesiyle birlikte mesleklerin geleceği nasıl şekillenecek?', '', 10),
(27, 4, 'deneme2', '', 10);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `difficulty` enum('kolay','orta','zor') DEFAULT 'orta',
  `time_limit` int(11) DEFAULT 0,
  `question_count` int(11) DEFAULT 0,
  `participants` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `title`, `description`, `category_id`, `difficulty`, `time_limit`, `question_count`, `participants`, `created_by`, `created_at`) VALUES
(1, 'Genel Kültür Testi', 'Genel kültür seviyenizi test edin', 1, 'orta', 300, 10, 2, NULL, '2025-04-07 18:02:25'),
(2, 'Bilim ve Teknoloji', 'Bilim ve teknoloji alanında bilginizi ölçün', 2, 'zor', 300, 10, 1, NULL, '2025-04-07 18:02:25'),
(4, 'test', 'test', 1, 'kolay', 300, 1, 1, NULL, '2025-04-07 19:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'QuizMeto', '2025-04-07 20:04:46', '2025-04-07 20:14:01'),
(2, 'site_description', 'Online Test ve Quiz Platformu', '2025-04-07 20:04:46', '2025-04-07 20:04:46'),
(3, 'items_per_page', '10', '2025-04-07 20:04:46', '2025-04-07 20:06:04'),
(4, 'enable_registration', '1', '2025-04-07 20:04:46', '2025-04-07 20:04:46'),
(5, 'enable_leaderboard', '1', '2025-04-07 20:04:46', '2025-04-07 20:04:46'),
(6, 'footer_text', '', '2025-04-07 20:04:46', '2025-04-07 20:14:18'),
(7, 'primary_color', '#4f46e5', '2025-04-07 20:04:46', '2025-04-07 20:23:02'),
(8, 'enable_dark_mode', '1', '2025-04-07 20:04:46', '2025-04-07 20:11:00'),
(9, 'facebook_url', '', '2025-04-07 20:04:46', '2025-04-13 19:20:19'),
(10, 'twitter_url', '', '2025-04-07 20:04:46', '2025-04-13 19:20:19'),
(11, 'instagram_url', '', '2025-04-07 20:04:46', '2025-04-13 19:20:19'),
(12, 'youtube_url', '', '2025-04-07 20:04:46', '2025-04-13 19:20:19'),
(13, 'contact_email', '', '2025-04-07 20:04:46', '2025-04-07 20:04:46'),
(14, 'maintenance_mode', '0', '2025-04-07 20:04:46', '2025-04-13 19:20:19'),
(15, 'favicon', 'assets/images/favicon-1744056841.ico', '2025-04-07 20:05:21', '2025-04-07 20:14:01'),
(16, 'site_logo', 'assets/images/site-logo-1744056841.png', '2025-04-07 20:05:54', '2025-04-07 20:14:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_image`, `role`, `created_at`) VALUES
(4, 'metecan', 'metinguzey01@gmail.com', '$2y$10$vRV8fhesvKQVBrgMvLlMa.bIngdI.IMcYok8whgPbXWFAai7DCBLm', 'assets/uploads/profiles/profile_4_1744059375.jpg', 'admin', '2025-04-07 20:55:15'),
(5, 'deneme kullanıcı', 'dene@gmail.com', '$2y$10$TUPtM9fgU0AIAHyVwyr2LOLAOhK7HaoBPgDeFBTqPXFCYcasNxB06', 'assets/uploads/profiles/profile_5_1744573139.png', 'user', '2025-04-13 19:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_scores`
--

CREATE TABLE `user_scores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `completion_time` int(11) DEFAULT 0,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_scores`
--

INSERT INTO `user_scores` (`id`, `user_id`, `quiz_id`, `score`, `completion_time`, `completed_at`) VALUES
(4, 4, 1, 70, 50, '2025-04-07 21:13:09'),
(6, 5, 1, 80, 43, '2025-04-13 19:59:41'),
(7, 5, 2, 80, 30, '2025-04-13 20:00:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_scores`
--
ALTER TABLE `user_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_quiz` (`user_id`,`quiz_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_scores`
--
ALTER TABLE `user_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_scores`
--
ALTER TABLE `user_scores`
  ADD CONSTRAINT `user_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_scores_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
