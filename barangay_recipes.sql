-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 09:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barangay_recipes`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(6, 'Desserts'),
(4, 'Kakanin'),
(5, 'Merienda'),
(3, 'Pancit & Noodles'),
(2, 'Sabaw & Soup'),
(8, 'Seafood'),
(1, 'Ulam'),
(7, 'Vegetable Dishes');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `recipe_id`, `user_id`, `body`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 'hm po taho big cup?', '2026-10-09 06:46:18', NULL),
(2, 23, 1, 'what if sinigang na rabbit?', '2026-10-09 06:58:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `recipe_id`, `created_at`) VALUES
(1, 16, '2026-10-09 06:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `amount` varchar(10) NOT NULL DEFAULT '',
  `unit` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `recipe_id`, `amount`, `unit`, `name`) VALUES
(43, 16, '2', 'cups', 'graham crackers, crushed'),
(44, 16, '1/2', 'cup', 'melted butter'),
(45, 16, '2', 'cups', 'all-purpose cream'),
(46, 16, '1/2', 'can', 'condensed milk'),
(47, 16, '3', 'pcs', 'ripe carabao mangoes, sliced'),
(48, 17, '1', 'pack', 'ramen noodles'),
(49, 17, '4', 'cups', 'pork bone broth'),
(50, 17, '4', 'slices', 'leche flan'),
(51, 17, '1/2', 'cup', 'ube halaya, cubed'),
(52, 17, '1/3', 'cup', 'sweet red beans (munggo)'),
(53, 17, '1/4', 'cup', 'evaporated milk'),
(54, 18, '2', 'cups', 'heavy cream'),
(55, 18, '1', 'can', 'condensed milk'),
(56, 18, '1/4', 'cup', 'calamansi juice'),
(57, 18, '1', 'tsp', 'salt'),
(58, 18, '1', 'cup', 'crispy pork belly bits'),
(59, 18, '3', 'tbsp', 'sugar'),
(60, 19, '1.5', 'cups', 'graham crackers, crushed'),
(61, 19, '1/2', 'cup', 'melted butter'),
(62, 19, '2', 'tbsp', 'minced garlic'),
(63, 19, '500', 'g', 'cream cheese'),
(64, 19, '3/4', 'cup', 'sugar'),
(65, 19, '3', 'pcs', 'eggs'),
(66, 19, '2', 'tbsp', 'soy sauce'),
(67, 19, '1/4', 'cup', 'vinegar'),
(68, 20, '1', 'cup', 'sago (tapioca pearls)'),
(69, 20, '1', 'block', 'silken tofu'),
(70, 20, '1/2', 'cup', 'brown sugar'),
(71, 20, '1', 'cup', 'water'),
(72, 20, '2', 'cups', 'black tea, brewed'),
(73, 20, '1/2', 'cup', 'milk'),
(74, 21, '1', 'cup', 'tamarind pulp'),
(75, 21, '1/2', 'cup', 'water'),
(76, 21, '1', 'cup', 'sugar'),
(77, 21, '2', 'tbsp', 'gelatin powder'),
(78, 21, '1/4', 'cup', 'dried kangkong leaves, powdered'),
(79, 22, '1', 'kg', 'oxtail, cut into pieces'),
(80, 22, '1', 'cup', 'peanuts, toasted'),
(81, 22, '3', 'tbsp', 'rice, toasted and ground'),
(82, 22, '2', 'tbsp', 'annatto seeds'),
(83, 22, '2', 'pcs', 'eggplant, sliced'),
(84, 22, '1', 'bundle', 'string beans'),
(85, 22, '1', 'cup', 'banana blossoms'),
(86, 22, '1/2', 'cup', 'bagoong (shrimp paste)'),
(87, 23, '1', 'kg', 'pork ribs'),
(88, 23, '1', 'pc', 'onion, quartered'),
(89, 23, '2', 'pcs', 'tomatoes, quartered'),
(90, 23, '1', 'pack', 'sinigang mix (tamarind)'),
(91, 23, '1', 'pc', 'radish, sliced'),
(92, 23, '2', 'pcs', 'eggplant, sliced'),
(93, 23, '1', 'bundle', 'string beans'),
(94, 23, '1', 'bundle', 'kangkong (water spinach)'),
(95, 23, '2', 'pcs', 'long green chilies'),
(96, 23, '2', 'tbsp', 'fish sauce');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) NOT NULL,
  `steps` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `category_id`, `title`, `description`, `steps`, `created_at`, `updated_at`) VALUES
(16, 13, 6, 'Mango Float Pizza', 'Graham-cracker crust topped with whipped cream and ripe carabao mangoes — a barangay fiesta crowd-pleaser.', '1. Crush graham crackers and mix with melted butter to form the crust; press into a round pan and chill.\n2. Whip all-purpose cream with condensed milk until thick and fluffy.\n3. Arrange sliced ripe mangoes over the chilled crust.\n4. Spread the cream mixture evenly on top.\n5. Add another layer of mangoes and a final layer of cream.\n6. Freeze for at least 4 hours, then slice and serve cold.', '2026-10-09 06:43:32', NULL),
(17, 14, 3, 'Halo-Halo Ramen', 'Hot noodle broth loaded with leche flan, ube, and sweet beans — the ultimate Filipino dessert-meets-comfort-food mashup.', '1. Cook ramen noodles in pork bone broth until tender.\n2. Slice leche flan and ube halaya into bite-sized cubes.\n3. Ladle hot broth and noodles into a bowl.\n4. Top with leche flan, ube, and sweet red beans.\n5. Drizzle with evaporated milk and a scoop of shaved ice for the halo-halo effect.\n6. Serve immediately while the broth is hot.', '2026-10-09 06:43:32', NULL),
(18, 15, 6, 'Sisig Ice Cream', 'Crispy pork bits folded into a salted calamansi ice cream base — a savory-sweet frozen treat for the brave.', '1. Boil and grill pork belly, then chop into tiny crispy bits.\n2. Mix calamansi juice, a pinch of salt, and sugar into a cream base.\n3. Churn the mixture in an ice cream maker until semi-firm.\n4. Fold in the crispy pork bits.\n5. Transfer to a container and freeze for 4 hours.\n6. Scoop and serve with a calamansi wedge.', '2026-10-09 06:43:32', NULL),
(19, 16, 6, 'Adobo Cheesecake', 'Soy-vinegar glaze over a garlicky graham crust cheesecake — a tangy, savory twist on a classic dessert.', '1. Mix crushed graham crackers, melted butter, and minced garlic for the crust; press into a springform pan.\n2. Beat cream cheese with sugar, eggs, and a splash of soy sauce until smooth.\n3. Pour filling over the crust and bake in a water bath for 45 minutes at 160C.\n4. Cool, then refrigerate for 4 hours.\n5. Simmer soy sauce, vinegar, garlic, and sugar into a glossy glaze.\n6. Drizzle the glaze over the chilled cheesecake and serve.', '2026-10-09 06:43:32', NULL),
(20, 17, 5, 'Taho Boba', 'Silken tofu, arnibal syrup, and sago served in a milk tea cup — a street-food classic turned boba-shop favorite.', '1. Cook sago (tapioca pearls) in boiling water until translucent; drain and rinse.\n2. Prepare silken tofu by gently warming in a pot over low heat.\n3. Simmer brown sugar and water to make arnibal (sugar syrup).\n4. Brew strong black tea and mix with milk for the milk tea base.\n5. In a tall cup, add sago, then ladle in warm silken tofu.\n6. Pour in milk tea, drizzle arnibal on top, and serve with a boba straw.', '2026-10-09 06:43:33', NULL),
(21, 18, 6, 'Sinigang Gummies', 'Tamarind sour candy gummies with a crunchy kangkong-flavored coating — a playful snack inspired by the national soup.', '1. Blend tamarind pulp, water, and sugar; strain to get a smooth tamarind puree.\n2. Mix the puree with gelatin and heat until dissolved.\n3. Pour the mixture into gummy molds and chill until set.\n4. Powder dried kangkong leaves and mix with sugar for the coating.\n5. Toss the set gummies in the kangkong-sugar coating.\n6. Store in an airtight container and serve as a sour snack.', '2026-10-09 06:43:33', NULL),
(22, 19, 1, 'Kare-Kare', 'Oxtail and vegetables simmered in a rich peanut sauce, served with a side of bagoong — a Filipino feast centerpiece.', '1. Boil oxtail until tender, about 2 hours; reserve the broth.\n2. Toast rice and peanuts, then grind into a powder for the thickener.\n3. Sauté annatto seeds in oil for color, then discard the seeds.\n4. Simmer oxtail in broth with the peanut-rice mixture until thick.\n5. Add eggplant, string beans, and banana blossoms; cook until tender.\n6. Serve hot with bagoong on the side.', '2026-10-09 06:43:33', NULL),
(23, 19, 2, 'Sinigang na Baboy', 'Pork ribs in a sour tamarind broth loaded with vegetables — the comforting Filipino soup that defines home cooking.', '1. Boil pork ribs in water; skim off scum as it rises.\n2. Add onions, tomatoes, and tamarind soup base (sinigang mix).\n3. Simmer until pork is tender, about 45 minutes.\n4. Add radish, eggplant, and string beans; cook for 5 minutes.\n5. Stir in kangkong (water spinach) and long green chilies.\n6. Season with fish sauce to taste and serve hot with rice.', '2026-10-09 06:43:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'jdndiaz', 'example@example.com', '$2y$10$KcdoJdKgC9IZ8oWu.6lcce18cdQqf8/Js.nIgpDFbk5aRTKOOEete', '2026-10-09 06:05:43'),
(13, 'mangomayor', 'mayor@example.com', '$2y$10$/5SSCHKe7ZlEr1EAaO5dUOE.EIkLsjDPrPeDNAGzUVkfZtjr2IYhy', '2026-10-09 06:43:32'),
(14, 'ramenrizal', 'rizal@example.com', '$2y$10$z92X0SdFWl6oGsPgmfJmp.ZaVUGwFqqOtXDpI3O64FmUbfT7ytYaK', '2026-10-09 06:43:32'),
(15, 'sisigpablo', 'pablo@example.com', '$2y$10$dDTLtLmLU4mbca7lU82GG.wUro3H9Ms1wys9hru./W36N9mlAVbRe', '2026-10-09 06:43:32'),
(16, 'adobolola', 'lola@example.com', '$2y$10$3DUVH8jDYp3hBKaTrQkgt.WEpGj/ADgf7RDK607cagTtz4H/OtVPi', '2026-10-09 06:43:32'),
(17, 'tahotito', 'tito@example.com', '$2y$10$DOeKraDyhg1T/VzwLktfZujpLVD.m.bhbExkup6vzH3lWAemI98G6', '2026-10-09 06:43:33'),
(18, 'gummyging', 'ging@example.com', '$2y$10$xlzGJQUQ0hHl0rnlBpIzGOH15EGEG2v/dEJhnuH3hlbyknjuoZuNe', '2026-10-09 06:43:33'),
(19, 'karekaren', 'karen@example.com', '$2y$10$b/gdntWkQYNRl/J8MIQlq.dkE64qicXqrWItTTdYwEVw3UtaAwB6m', '2026-10-09 06:43:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`recipe_id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_recipes_created` (`created_at`);
ALTER TABLE `recipes` ADD FULLTEXT KEY `ft_recipes_search` (`title`,`description`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
