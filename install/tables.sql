-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 22, 2012 at 02:19 PM
-- Server version: 5.1.52
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- --------------------------------------------------------

--
-- Table structure for table `Measurements`
--

CREATE TABLE IF NOT EXISTS `Measurements` (
  `Metric_Name` varchar(30) CHARACTER SET latin1 NOT NULL,
  `Value` double NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `Metric_Name` (`Metric_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `Metrics`
--

CREATE TABLE IF NOT EXISTS `Metrics` (
  `Name` varchar(30) CHARACTER SET latin1 NOT NULL,
  `Description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `Centerline` double NOT NULL DEFAULT '0',
  `UCL` double DEFAULT NULL,
  `LCL` double DEFAULT NULL,
  `DecimalPrecision` int(11) NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `Rules`
--

CREATE TABLE IF NOT EXISTS `Rules` (
  `Metric_Name` varchar(30) CHARACTER SET latin1 NOT NULL,
  `Type` varchar(30) CHARACTER SET latin1 NOT NULL,
  `Status` varchar(10) CHARACTER SET latin1 NOT NULL,
  `Violation_Status` varchar(10) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`Metric_Name`,`Type`),
  KEY `Metric_Name` (`Metric_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE IF NOT EXISTS `Users` (
  `username` varchar(25) CHARACTER SET latin1 NOT NULL,
  `password` varchar(150) CHARACTER SET latin1 NOT NULL,
  `acl_flags` int(11) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Measurements`
--
ALTER TABLE `Measurements`
  ADD CONSTRAINT `Measurements_ibfk_1` FOREIGN KEY (`Metric_Name`) REFERENCES `Metrics` (`Name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Rules`
--
ALTER TABLE `Rules`
  ADD CONSTRAINT `Rules_ibfk_1` FOREIGN KEY (`Metric_Name`) REFERENCES `Metrics` (`Name`) ON DELETE CASCADE ON UPDATE CASCADE;



--
-- Dumping data for table `Users`
--

INSERT INTO `Users` (`username`, `password`, `acl_flags`) VALUES
('admin', '', 31);

