--
-- Database: `sqlite.db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tree_data`
--

CREATE TABLE IF NOT EXISTS `tree_data` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nm` varchar(255) NOT NULL
);

--
-- Dumping data for table `tree_data`
--

INSERT INTO `tree_data` (`id`, `nm`) VALUES
(1, 'root'),
(1063, 'Node 12'),
(1064, 'Node 2'),
(1065, 'Node 3'),
(1066, 'Node 4'),
(1067, 'Node 5'),
(1068, 'Node 6'),
(1069, 'Node 7'),
(1070, 'Node 8'),
(1071, 'Node 9'),
(1072, 'Node 9'),
(1073, 'Node 9'),
(1074, 'Node 9'),
(1075, 'Node 7'),
(1076, 'Node 8'),
(1077, 'Node 9'),
(1078, 'Node 9'),
(1079, 'Node 9');

-- --------------------------------------------------------

--
-- Table structure for table `tree_struct`
--

CREATE TABLE IF NOT EXISTS `tree_struct` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `lft` int(10) NOT NULL,
  `rgt` int(10) NOT NULL,
  `lvl` int(10) NOT NULL,
  `pid` int(10) NOT NULL,
  `pos` int(10) NOT NULL
);

--
-- Dumping data for table `tree_struct`
--

INSERT INTO `tree_struct` (`id`, `lft`, `rgt`, `lvl`, `pid`, `pos`) VALUES
(1, 1, 36, 0, 0, 0),
(1063, 2, 31, 1, 1, 0),
(1064, 3, 30, 2, 1063, 0),
(1065, 4, 29, 3, 1064, 0),
(1066, 5, 28, 4, 1065, 0),
(1067, 6, 19, 5, 1066, 0),
(1068, 7, 18, 6, 1067, 0),
(1069, 8, 17, 7, 1068, 0),
(1070, 9, 16, 8, 1069, 0),
(1071, 12, 13, 9, 1070, 1),
(1072, 14, 15, 9, 1070, 2),
(1073, 10, 11, 9, 1070, 0),
(1074, 32, 35, 1, 1, 1),
(1075, 20, 27, 5, 1066, 1),
(1076, 21, 26, 6, 1075, 0),
(1077, 24, 25, 7, 1076, 1),
(1078, 33, 34, 2, 1074, 0),
(1079, 22, 23, 7, 1076, 0);