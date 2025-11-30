
--
-- Database: `codonoexchange`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `ConvertMyISAMToInnoDB`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ConvertMyISAMToInnoDB` ()  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE tableName VARCHAR(256);
    DECLARE cur CURSOR FOR SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'codonoexchange' AND ENGINE = 'MyISAM';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO tableName;
        IF done THEN
            LEAVE read_loop;
        END IF;
        SET @s = CONCAT('ALTER TABLE ', tableName, ' ENGINE=InnoDB;');
        PREPARE stmt FROM @s;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
END$$

DROP PROCEDURE IF EXISTS `CreateCoinTables`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateCoinTables` (IN `coin` VARCHAR(255))  BEGIN
    DECLARE coin_table VARCHAR(255);
    DECLARE coin_d_table VARCHAR(255);
    DECLARE coin_b_table VARCHAR(255);

    SET coin_table = CONCAT(coin, '_table');
    SET coin_d_table = CONCAT(coin, 'd_table');
    SET coin_b_table = CONCAT(coin, 'b_table');

    -- Create the main coin table
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', coin_table, '` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `', coin, '` DECIMAL(20,8) DEFAULT 0.00000000,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Create the coin_d table
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', coin_d_table, '` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `', coin, 'd` DECIMAL(20,8) DEFAULT 0.00000000,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Create the coin_b table
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', coin_b_table, '` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `', coin, 'b` VARCHAR(42) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

DROP PROCEDURE IF EXISTS `DeleteZeroTradeLogs`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteZeroTradeLogs` ()  BEGIN
  DELETE FROM codono_trade_log WHERE userid = 0 AND peerid = 0;
END$$

DROP PROCEDURE IF EXISTS `FinanceProcedure`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FinanceProcedure` (IN `before_balance_coin` DECIMAL(20,8), IN `before_balance_coind` DECIMAL(20,8), IN `aid` INT, IN `uid` INT, IN `coin` VARCHAR(255), IN `market` VARCHAR(255), IN `name` VARCHAR(255), IN `fee` DECIMAL(20,8), IN `type` INT)  BEGIN
    DECLARE coin_lowercase VARCHAR(255);
    DECLARE coind VARCHAR(255);
    DECLARE rand_value VARCHAR(255);
    DECLARE finance_hash VARCHAR(255);
    DECLARE remark VARCHAR(255);
    DECLARE before_balance_total DECIMAL(20, 8);
    DECLARE after_balance_coin DECIMAL(20, 8);
    DECLARE after_balance_coind DECIMAL(20, 8);
    DECLARE after_balance_total DECIMAL(20, 8);
    
    SET coin_lowercase = LOWER(coin);
    SET coind = CONCAT(coin, 'd');
    SET rand_value = CONCAT(FLOOR(RAND() * 9000000) + 1000000, UNIX_TIMESTAMP());
    SET finance_hash = MD5(CONCAT(aid, 'finance', uid, rand_value));

    IF type = 1 THEN
        SET remark = CONCAT('Buy Order Commission:', market);
    ELSE
        SET remark = CONCAT('Sell Order Commission:', market);
    END IF;

    SET before_balance_total = before_balance_coin + before_balance_coind;
    SET after_balance_coin = before_balance_coin;
    SET after_balance_coind = before_balance_coind;
    SET after_balance_total = after_balance_coin + after_balance_coind;

    INSERT INTO codono_finance (userid, coinname, num_a, num_b, num, fee, type, name, nameid, remark, mum_a, mum_b, mum, move, addtime, status)
    VALUES (uid, coin_lowercase, before_balance_coin, before_balance_coind, before_balance_total, fee, type, name, aid, remark, after_balance_coin, after_balance_coind, after_balance_total, finance_hash, UNIX_TIMESTAMP(), 1);
END$$

DROP PROCEDURE IF EXISTS `InsertUserCoin`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertUserCoin` ()  BEGIN
    INSERT INTO codono_user_coin (addtime, userid)
    SELECT UNIX_TIMESTAMP(NOW()), id
    FROM codono_user
    WHERE id NOT IN (
        SELECT DISTINCT userid 
        FROM codono_user_coin
    );
END$$

DROP PROCEDURE IF EXISTS `RenameAndCopyTables`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RenameAndCopyTables` ()  BEGIN
    -- Check if old_trade table exists
    IF NOT EXISTS (SELECT * FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'old_trade') THEN
        -- Rename codono_trade to old_trade
        RENAME TABLE codono_trade TO old_trade;
        
        -- Create a new table with the same schema as old_trade
        CREATE TABLE codono_trade (LIKE old_trade);
        
        -- Copy rows from old_trade where userid != 0 to codono_trade
        INSERT INTO codono_trade
        SELECT * FROM old_trade WHERE userid != 0;
    ELSE
        -- Print a message indicating that old_trade table already exists
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'old_trade table already exists.Please drop old_trade table';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `codono_action_log`
--

DROP TABLE IF EXISTS `codono_action_log`;
CREATE TABLE IF NOT EXISTS `codono_action_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `action_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'action id',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'userid',
  `action_ip` varchar(40) DEFAULT NULL COMMENT 'Executor ip',
  `model` varchar(50) NOT NULL DEFAULT '' COMMENT 'The table that triggers the behavior',
  `record_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Data id that triggered the behavior',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'Log notes',
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT 'state',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Time to perform the act',
  PRIMARY KEY (`id`),
  KEY `action_ip_ix` (`action_ip`),
  KEY `action_id_ix` (`action_id`),
  KEY `user_id_ix` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_activity`
--

DROP TABLE IF EXISTS `codono_activity`;
CREATE TABLE IF NOT EXISTS `codono_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `adminid` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '1=income,2=expense',
  `account` tinyint(1) DEFAULT 0 COMMENT 'spot:0 p2p:1 nft:2 margin:3 staking:4 stock:5',
  `coin` varchar(20) NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `memo` varchar(200) DEFAULT NULL,
  `txid` varchar(100) DEFAULT NULL,
  `addtime` int(11) NOT NULL,
  `internal_note` varchar(200) DEFAULT NULL,
  `internal_hash` varchar(100) DEFAULT NULL COMMENT '0=not changed 1 =done',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_activity`
--


--
-- Table structure for table `codono_admin`
--

DROP TABLE IF EXISTS `codono_admin`;
CREATE TABLE IF NOT EXISTS `codono_admin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(200) DEFAULT NULL,
  `username` char(16) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `cellphone` varchar(50) DEFAULT NULL,
  `password` char(32) DEFAULT NULL,
  `ga` varchar(30) DEFAULT NULL COMMENT 'google 2fa',
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `last_login_time` int(11) UNSIGNED DEFAULT NULL,
  `last_login_ip` varchar(50) DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Administrators table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_admin`
--

INSERT INTO `codono_admin` (`id`, `email`, `username`, `nickname`, `cellphone`, `password`, `ga`, `sort`, `addtime`, `last_login_time`, `last_login_ip`, `endtime`, `status`) VALUES
(1, 'support@codono.com', 'admin', 'Codono', '13502182299', '21232f297a57a5a743894a0e4a801fc3', NULL, 0, 0, 1711434494, '::1', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_admin_urlaccess`
--

DROP TABLE IF EXISTS `codono_admin_urlaccess`;
CREATE TABLE IF NOT EXISTS `codono_admin_urlaccess` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(60) CHARACTER SET latin1 DEFAULT NULL,
  `access_time` datetime NOT NULL,
  `passed_adminkey` tinyint(1) NOT NULL DEFAULT 0,
  `passed_login` tinyint(1) NOT NULL DEFAULT 0,
  `passed_2fa` tinyint(1) NOT NULL DEFAULT 0,
  `browser_agent` text CHARACTER SET latin1 NOT NULL,
  `country_code` varchar(3) CHARACTER SET latin1 DEFAULT NULL,
  `region_name` varchar(30) CHARACTER SET latin1 DEFAULT NULL,
  `city_name` varchar(30) CHARACTER SET latin1 DEFAULT NULL,
  `is_proxy_or_vpn` tinyint(1) DEFAULT 0,
  `is_cloudflare` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_admin_urlaccess`
--

--
-- Table structure for table `codono_adver`
--

DROP TABLE IF EXISTS `codono_adver`;
CREATE TABLE IF NOT EXISTS `codono_adver` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `title` varchar(90) DEFAULT NULL,
  `embed_link` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `img` varchar(250) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COMMENT='Ads pictures table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_adver`
--

INSERT INTO `codono_adver` (`id`, `name`, `title`, `embed_link`, `url`, `img`, `type`, `sort`, `addtime`, `endtime`, `status`) VALUES
(11, 'Find Your Next ,Moonshot', 'Never miss a crypto gem anymore. Sign up today !!', '', '#', '6659a9af1a8ac5.06718880.png', NULL, 0, 1717152178, 1717152178, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_appads`
--

DROP TABLE IF EXISTS `codono_appads`;
CREATE TABLE IF NOT EXISTS `codono_appads` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `content` varchar(256) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `block_id` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Ads pictures table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_appads`
--

INSERT INTO `codono_appads` (`id`, `name`, `content`, `url`, `img`, `block_id`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'Some test', 'Some content', 'https://goog.le', '/Upload/app/654e383078ab6.png', NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_appadsblock`
--

DROP TABLE IF EXISTS `codono_appadsblock`;
CREATE TABLE IF NOT EXISTS `codono_appadsblock` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `content` varchar(256) DEFAULT NULL,
  `rank` varchar(256) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `remain` varchar(255) DEFAULT '3',
  `type` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='Ads pictures table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_appadsblock`
--

INSERT INTO `codono_appadsblock` (`id`, `name`, `content`, `rank`, `img`, `remain`, `type`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'App Banner 1', 'WAP Ads', '1', '/Upload/app/672d016addeab2.82266798.png', '', '', 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_appc`
--

DROP TABLE IF EXISTS `codono_appc`;
CREATE TABLE IF NOT EXISTS `codono_appc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `web_name` varchar(64) DEFAULT NULL,
  `web_title` varchar(64) DEFAULT NULL,
  `web_icp` varchar(64) DEFAULT NULL,
  `index_img` varchar(256) DEFAULT NULL,
  `pay` varchar(256) DEFAULT NULL,
  `withdraw_notice` varchar(256) DEFAULT NULL,
  `charge_notice` varchar(256) DEFAULT NULL,
  `show_coin` varchar(255) DEFAULT NULL,
  `show_market` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `codono_app_log`
--

DROP TABLE IF EXISTS `codono_app_log`;
CREATE TABLE IF NOT EXISTS `codono_app_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `addtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_app_log`
--

INSERT INTO `codono_app_log` (`id`, `uid`, `type`, `content`, `addtime`) VALUES
(1, NULL, 'vip', 'Initialization ratingvip0', 1694160290),
(2, NULL, 'vip', 'upgrade to', 1694160290);

-- --------------------------------------------------------

--
-- Table structure for table `codono_app_vip`
--

DROP TABLE IF EXISTS `codono_app_vip`;
CREATE TABLE IF NOT EXISTS `codono_app_vip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `rule` text DEFAULT NULL,
  `times` int(11) DEFAULT NULL,
  `price_num` varchar(255) DEFAULT NULL,
  `price_coin` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `addtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_app_vip`
--

INSERT INTO `codono_app_vip` (`id`, `tag`, `name`, `rule`, `times`, `price_num`, `price_coin`, `status`, `addtime`) VALUES
(1, '1', 'VIP Membership', '[{\"id\":\"1\",\"num\":1000},{\"id\":\"38\",\"num\":1}]', 100, '100', '1', 1, 1476004810);

-- --------------------------------------------------------

--
-- Table structure for table `codono_app_vipuser`
--

DROP TABLE IF EXISTS `codono_app_vipuser`;
CREATE TABLE IF NOT EXISTS `codono_app_vipuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `vip_id` int(11) DEFAULT NULL,
  `addtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_app_vipuser`
--

INSERT INTO `codono_app_vipuser` (`id`, `uid`, `vip_id`, `addtime`) VALUES
(1, NULL, NULL, 1694160290);

-- --------------------------------------------------------

--
-- Table structure for table `codono_article`
--

DROP TABLE IF EXISTS `codono_article`;
CREATE TABLE IF NOT EXISTS `codono_article` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb3 DEFAULT NULL,
  `content` text CHARACTER SET utf8mb3 DEFAULT NULL,
  `adminid` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 DEFAULT NULL,
  `hits` int(11) UNSIGNED DEFAULT NULL,
  `footer` int(11) UNSIGNED DEFAULT NULL,
  `index` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `img` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `adminid` (`adminid`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_article`
--

INSERT INTO `codono_article` (`id`, `title`, `content`, `adminid`, `type`, `hits`, `footer`, `index`, `sort`, `addtime`, `endtime`, `status`, `img`) VALUES
(40, 'What BTC Is Really Worth May No Longer Be Such A Mystery..', '<p>\n	It took two economists one three-course meal and two bottles of wine to calculate the fair value of one Bitcoin: $200.<br />\n<br />\nIt took an extra day for them to realize they were one decimal place out: $20, they decided, was the right price for a virtual currency that was worth $1,200 a year ago, flirted with $20,000 in December, and is still around $8,000. Setting aside the fortunes lost on it this year, Bitcoin, by their calculation, is still overvalued, to the tune of about 40,000 percent. The pair named this the Côtes du Rhône theory, after the wine they were drinking.<br />\n<br />\n“It’s how we get our best ideas. It’s the lubricant,” says Savvas Savouri, a partner at a London hedge fund who shared drinking and thinking duties that night with Richard Jackman, professor emeritus at the London School of Economics. Their quest is one shared by the legions of traders, techies, online scribblers, and gamblers and grifters mesmerized by Bitcoin. What’s the value of a cryptocurrency made of code with no country enforcing it, no central bank controlling it, and few places to spend it? Is it $2, $20,000, or $2 million? Can one try to grasp at rational analysis, or is this just the madness of crowds?<br />\n<br />\nAnswering this question isn’t easy: Buying Bitcoin won’t net you any cash flows, or any ownership of the blockchain technology underpinning it, or really anything much at all beyond the ability to spend or save it. Maybe that’s why Warren Buffett once said the idea that Bitcoin had “huge intrinsic value” was a “joke”—there’s no earnings potential that can be used to estimate its value. But with $2 billion pumped into cryptocurrency hedge funds last year, there’s a lot of money betting the punchline is something other than zero. If Bitcoin is a currency, and currencies have value, surely some kind of stab—even in the dark—should be made at gauging its worth.<br />\n<br />\nWriting on a tablecloth, Jackman and Savouri turned to the quantity theory of money. Formalized by Irving Fisher in 1911, with origins that go back to Copernicus’s work on the effects of debasing coinage, the theory holds that the price of money is linked to its supply and how often it’s used.<br />\n<br />\nHere’s how it works. By knowing a money’s total supply, its velocity—the rate at which people use each coin—and the amount of goods and services on which it’s spent, you should be able to calculate price. Estimating Bitcoin’s supply at about 15 million coins (it’s currently a bit more), and assuming each one is used an average of about four times a year, led Jackman and Savouri to calculate that 60 million Bitcoin payments were supporting their assumed $1.2 billion worth of total U.S. dollar-denominated purchases. Using the theory popularized by Fisher and his followers, you can—simplifying things somewhat—divide the $1.2 billion by the 60 million Bitcoin payments to get the price of Bitcoin in dollars. That’s $20.\n</p>\n<p>\n	<br />\n</p>', 1, 'aaa', 0, 1, 1, 0, 1715263419, 1715263421, 1, '59426a65d0dac.png'),
(41, 'Bigger Blocks and Smarter Contracts: What\'s In Bitcoin Cash\'s Next Fork?', 'Bitcoin cash\'s next software upgrade may be even more ambitious than its first - and that\'s no small feat given last time it broke off from bitcoin in acrimonious fashion.<br />\r\n <br />\r\n In fact, the update, announced in November and slated for May 15, packages together a number of features that all seem about helping the network process more transactions than the original bitcoin (while adding more variety to features). Perhaps most notably, the change will quadruple bitcoin cash\'s block size parameter from 8 MB to 32 MB, allowing for vastly more transactions per block.<br />\r\n <br />\r\n But while that might sound aggressive given bitcoin\'s more limited approach, those who have been following the cryptocurrency might be surprised that such an aggressive shift wasn\'t pursued sooner.<br />\r\n <br />\r\n After all, last fall, bitcoin cash\'s developers chose to ignore the protests of bitcoin\'s more seasoned developers, who had long argued that increasing the block size and moving the cryptocurrency forward too fast could jeopardize the more than $157 billion network.<br />\r\n <br />\r\n But that contrarian mentality has proved, at least partially, attractive - one bitcoin cash is going for a little less than $1,500 a coin, making it\'s market cap more than $24 billion.<br />\r\n <br />\r\n Indeed, Joshua Yabut, who contributes to the bitcoin cash protocol\'s main software implementation, BitcoinABC, said he doesn\'t expect any protest at all when users are finally given the choice to upgrade software.<br />', 1, 'aaa', 0, 1, 1, 0, 1497525005, 1497456000, 1, '59426b2338444.png'),
(42, 'Crypto Market In Green Following Correction, Bitcoin Above $9,000, EOS Gains Significantly', 'Friday, April 27: after a mid-week correction that has seen Bitcoin go below $9,000, the crypto market is back on track with all top 10 cryptocurrencies listed on Coin360 in the green.<br />\r\n <br />\r\n COIN360<br />\r\n <br />\r\n Bitcoin (BTC) is above $9,000 again, trading at about $9,263 with a value gain of about 5 percent over 24 hours to press time.<br />\r\n <br />\r\n BTC Vatue &amp; Volume<br />\r\n <br />\r\n Ethereum (ETH) is steadily climbing towards the $700 mark, trading at around $678 at press time, up almost 8 percent from yesterday.<br />\r\n <br />\r\n ETH Value &amp; Volume<br />\r\n <br />\r\n Total market cap is again over $400 bln, currently at $417 bln, after having dropped to as low as $380 bln Thursday.<br />\r\n <br />\r\n Total Market Capitalization<br />\r\n <br />\r\n EOS continues to grow significantly, seeing 19 percent gains over 24 hours and trading around $17.40 at press time. With a growth of almost 200 percent over the last 30 days, the cryptocurrency’s price is approaching the levels of January, according to Coinmarketcap data.<br />\r\n <br />\r\n EOS Charts<br />\r\n <br />\r\n Stellar (XLM) has increased by almost 10 percent over 24 hours - the altcoin is currently trading at $0.41.<br />\r\n <br />\r\n Excepting this week’s temporary decline, the crypto market has been moving upwards since the day Bitcoin’s price jumped $1,000 in 30 minutes on April 12. During this period, the markets have been propelled by a number of positive news, particularly Goldman Sachs executives resigning for positions at crypto projects, such as the crypto wallet Blockchain.com and the crypto merchant bank Galaxy Digital.<br />\r\n <br />\r\n Earlier today, Gil Beyda, the managing director of the venture capital arm of Comcast, expressed a bullish view on Bitcoin and real world applications of Blockchain technology.<br />\r\n <br />\r\n On Monday, Goldman Sachs hired cryptocurrency trader Justing Schmidt as vice president of digital asset markets of its securities division, “in response to client interest in various digital products.”<br />\r\n <div>\r\n 	<br />\r\n </div>', 1, 'aaa', 0, 0, 1, 3, 1524898085, 1524898092, 1, '59426b5715ef3.png'),
(43, 'Is the Tokyo bitcoin whale set to strike again?', '<p>\r\n 	Speculation is growing that the trustees for the now-defunct Mt. Gox cryptocurrency exchange are getting set for another significant bitcoin sale.<br />\r\n <br />\r\n On April 26, a website that tracks the Mt. Gox cryptocurrency wallets indicated that the trust had shifted a sizable portion of bitcoins and Bitcoin Cash from its wallet, which led many to speculate that Nobuaki Kobayashi, the head attorney for the Mt. Gox trust who’s been dubbed the “Tokyo whale,” is set to unload another lot of the recovered coins.<br />\r\n <br />\r\n “Some in the [cryptocurrency] community have been a bit worried about the recent movement of bitcoin out of the Mt. Gox settlement wallets,” wrote Mati Greenspan, senior market analyst at etoro.<br />\r\n <br />\r\n “Indeed, it appears that Kobayashi has moved about 16,000 BTC out of cold storage, which may indicate that he’s preparing to sell them on the open market,” Greenspan said.<br />\r\n <br />\r\n <br />\r\n Mt. Gox trust cryptocurrency balance<br />\r\n If the trustees were to proceed with a sale, it would be the first sizable transaction since the trust dumped nearly $400 million worth of bitcoin and Bitcoin Cash in March.<br />\r\n <br />\r\n At current market value, the 16,000 bitcoins BTCUSD, +3.94%&nbsp; and 16,000 Bitcoin Cash are worth a combined $170 million and should the sale happen, onlookers will be scouting for potential price fluctuations.<br />\r\n <br />\r\n “If they are smart about it, it won’t have a material impact,” said Martin Garcia, managing director and co-head of sales and trading, at Genesis. “However, it all depends on the market at the time. If it’s going up the market will absorb it easily.”<br />\r\n <br />\r\n Garcia added that if done over the counter, it would be possible for the transaction to be executed in a single block.<br />\r\n 	<div>\r\n 		<br />\r\n 	</div>\r\n </p>\r\n <p>\r\n 	<br />\r\n </p>', 1, 'aaa', 0, 0, 1, 4, 1524898182, 1524898189, 1, '59426c26a6e49.png'),
(44, 'Banking Giant ING Is Quietly Becoming a Serious Blockchain Innovator', '<p>\r\n 	ING is out to prove that startups aren\'t the only ones that can advance blockchain cryptography.<br />\r\n <br />\r\n Rather than waiting on the sidelines for innovation to arrive, the Netherlands-based bank is diving headlong into a problem that it turns out worries financial institutions as much as average cryptocurrency users. In fact, the bank first made a splash in November of last year by modifying an area of cryptography known as zero-knowledge proofs.<br />\r\n <br />\r\n Simply put, the code allows someone to prove that they have knowledge of a secret without revealing the secret itself.<br />\r\n <br />\r\n On their own, zero-knowledge proofs were a promising tool for financial institutions that were intrigued by the benefits of shared ledgers but wary of revealing too much data to their competitors. The technique, previously applied in the cryptocurrency world by zcash, offered banks a way to transfer assets on these networks without tipping their hands or compromising client confidentiality.<br />\r\n <br />\r\n But ING has came up with a modified version called \"zero-knowledge range proofs,\" which can prove that a number is within a certain range without revealing exactly what that number is. This was an improvement in part because it uses less computational power and therefore runs faster on a blockchain.<br />\r\n <br />\r\n For example, zero-knowledge range proofs (which the bank open-sourced last year) can be used to prove that someone has a salary within the range needed to attain a mortgage without revealing the actual figure, said Mariana Gomez de la Villa, global head of ING\'s blockchain program.<br />\r\n <br />\r\n \"It can be used to protect the denomination of a transaction, but still allowing validation that there\'s enough money in the participant account to settle the transaction,\" she said.<br />\r\n <br />\r\n Now, building on its past work, ING is adding yet another wrinkle to enterprise blockchain privacy, leveraging a type of proof known as \"zero-knowledge set membership.\"<br />\r\n <br />\r\n Revealed exclusively to CoinDesk, ING plans to take the zero-knowledge concept beyond numbers to include other types of data.&nbsp;&nbsp;<br />\r\n <br />\r\n Set membership allows the prover to demonstrate that a secret belongs to a generic set, which can be composed of any kind of information, like names, addresses and locations.<br />\r\n <br />\r\n The potential applications of set membership are wide-ranging, Gomez de la Villa said. Not restricted to numbers belonging to an interval, it can be used to validate that any sort of data is correctly formed.<br />\r\n <br />\r\n \"Set membership is more powerful than range proofs,\" Gomez de la Villa told CoinDesk, adding:<br />\r\n <br />\r\n \"For example, imagine that you could validate that someone lives in a country that belongs to the European Union, without revealing which one.\"<br />\r\n <br />\r\n Benefits of openness<br />\r\n But you don\'t have to just take ING\'s word for it. Since being open-sourced, the body of cryptographic work that ING is building on has been subjected to academic to peer review at the highest levels.<br />\r\n <br />\r\n MIT math whiz and one of the co-founders of zcash, Madars Virza, revealed a vulnerability in last year\'s zero-knowledge range proofs paper. Virza showed that, in theory, it was possible to reduce the range interval and so glean knowledge about a hidden number.<br />\r\n <br />\r\n ING said it has since fixed this vulnerability, and Gomez de la Villa pointed out that this is the type of contribution expected from the ecosystem where the very purpose of open-sourcing is allowing users to fix bugs and improve functions.<br />\r\n <br />\r\n \"By making the source code available, improving our zero-knowledge range proof solution has become a collaborative effort,\" she said.&nbsp;&nbsp;<br />\r\n <br />\r\n She also framed the incident as an example of a mutually beneficial relationship between academic cryptographers and enterprises like ING.<br />\r\n <br />\r\n \"They are working on the theory; we are working on the practice,\" Gomez de la Villa said, adding:<br />\r\n <br />\r\n \"They can keep thinking about their crazy stuff and then we can say, \'OK, how can we use it in order to make it available to the rest so it can actually work?\'\"<br />\r\n <br />\r\n Jack Gavigan, chief operating officer at Zerocoin Electric Coin Company, the company that develops the zcash network, said this type of open-source collaboration is contributing to a body of knowledge that all can draw upon, thus driving progress in the zero-knolwedge proof space at a rapid click. And those benefits will be returned in full.<br />\r\n <br />\r\n \"When a disruptive technology like blockchain comes along, it can shake things up, and companies that are best-positioned to embrace and exploit that technology are likely to end up at the top of the pile when things have settled down,\" said Gavigan.<br />\r\n <br />\r\n He continued:<br />\r\n <br />\r\n \"I think that\'s why you see companies like ING delving into this space, getting hands-on with the technology, and joining the broader community - because when this technology matures and is ready for prime time, they\'ll be ready and able to hit the ground running.\"<br />\r\n <br />\r\n Picking up from JPM<br />\r\n In other ways, the blockchain-savvy move is already paying off.<br />\r\n <br />\r\n ING has been invited to the table with the world\'s top cryptographers and will participate in an invite-only workshop in Boston seeking to standardize zero knowledge proofs, alongside the likes of MIT\'s Shafi Goldwasser.&nbsp;&nbsp;<br />\r\n <br />\r\n In this way, ING is now part of a wide community of experts extending the scope of zero-knowledge proofs.<br />\r\n <br />\r\n At the start of this year, University College of London\'s Jonathan Bootle and Stanford\'s Benedikt Bunz released \"Bulletproofs,\" which dramatically improves proof performance and allows proving a much wider class of statements than just range proofs. Many startups have jumped on this and it\'s being taken into the enterprise space by the likes of Silicon Valley startup Chain.<br />\r\n <br />\r\n Among banks, though, the best known implementation of zero-knowledge proofs is in JPMorgan Chase\'s Quorum, which was showcased to a rapturous reception on the blockchain circuit last year.<br />\r\n <br />\r\n Taking the Quorum model a step further, ING designed its range proofs to be computationally less onerous than previous zero knowledge deployments and so faster to run on distributed ledgers.<br />\r\n <br />\r\n \"Zk-SNARKs, used in JPM Quorum, are known to be less efficient than the construction of zero knowledge proofs for a specific purpose, as is the case of zero-knowledge range proofs. Indeed, range proofs are at least an order of magnitude faster,\" said Gomez de la Villa.<br />\r\n <br />\r\n At JPMorgan, the Quorum team was led by Amber Baldet, who has since left to join a yet-to-be named startup. Now the word on the street is that JPMorgan is considering spinning out Quorum so it\'s not longer under the direct purview of the Wall Street giant, in a possible bid to gain more of a network effect from other banks.<br />\r\n </p>', 1, 'aaa', 0, 0, 1, 2, 1525065769, 1525065771, 1, '59426e46ae85b.png'),
(46, 'Trading platform  (www.CODONO.com) Formally launched', '<p class=\"MsoNormal\">\r\n 	<span></span>\r\n 	<div style=\"margin:0px 14.3906px 0px 28.7969px;padding:0px;font-family:&quot;font-size:14px;background-color:#FFFFFF;\">\r\n 		<h2 style=\"font-weight:400;font-family:DauphinPlain;font-size:24px;\">\r\n 			What is Lorem Ipsum?\r\n 		</h2>\r\n 		<p style=\"text-align:justify;\">\r\n 			<strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\r\n 		</p>\r\n 	</div>\r\n 	<div style=\"margin:0px 28.7969px 0px 14.3906px;padding:0px;font-family:&quot;font-size:14px;background-color:#FFFFFF;\">\r\n 		<h2 style=\"font-weight:400;font-family:DauphinPlain;font-size:24px;\">\r\n 			Why do we use it?\r\n 		</h2>\r\n 		<p style=\"text-align:justify;\">\r\n 			It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).\r\n 		</p>\r\n 	</div>\r\n </p>\r\n <p>\r\n 	<br />\r\n </p>', 1, 'aaa', 0, 0, 1, 5, 1525066038, 1525066040, 1, '5ae6cc0845178.png'),
(50, 'Investors from 28 Countries Own Land in Norway’s “Private City” Liberstad', 'With slogans like “taxation is theft”, Liberstad is attracting more and more libertarians from around the world, local media reports. According to its website, 112 people have already bought land plots in the “anarcho-capitalist city” established on farmland, not far from Kristiansand in Southern Norway. The buyers come from 28 countries, including Norway, neighboring Sweden, distant Brazil and the United Kingdom. Another 500 potential investors have signed up on a waiting list.<br />\r\n <br />\r\n The plots are sold for as little as 75,000 Norwegian Kronor, or $9,400 dollars for 1,000 m2, and as much as 375,000 NOK ($47,100 USD) for 5,000 m2. Payments in 27 different cryptocurrencies are currently accepted, including bitcoin cash (BCH) and bitcoin core (BTC). The team behind Liberstad plans to start handing over the purchased plots by 2020, when the first residents will be able to move in.<br />\r\n <br />\r\n Last summer, John Holmesland and Sondre Bjellås, bought the Tjelland farm in the municipality of Marnardal, where the city is located. Since then they have promoted the project and informed about its progress through social media and the Liberstad’s blog. In December, they announced that local authorities had granted concession and permission for ownership of the agricultural property where the city is being developed.<br />\r\n <br />\r\n <br />\r\n <br />\r\n Private Police and Other “Public” Services Planned<br />\r\n John Holmesland told the Norwegian outlet Local that Liberstad was inspired by Atlantic Station, a similar project within the city of Atlanta in the US state of Georgia. He and his partner want to eventually set up a private police force, a fire department, and a water utility for the city’s residents. Companies will be invited to provide these and other private (public) services.<br />\r\n <br />\r\n Investors from 28 Countries Own Land in Norway’s “Private City” Liberstad“The only thing we demand is that you respect the principle of non-aggression and private property rights,” its founders state. According to local media, they may run into some issues in their attempt to achieve all that. Their plans have been dismissed by some Norwegian officials.<br />\r\n <br />\r\n “It may be that someone comes and settles there, but establishing a state within the state is not realistic,” Labor Party deputy Kari Henriksen told NRK, the Norwegian state-owned broadcaster. Henriksen, who is representing the local Vest-Agder constituency in the Norwegian parliament, believes that the residents of the “private city” will be dependent on the rest of the society in many ways.<br />\r\n <br />\r\n A similar project is the Free Republic of Liberland established on a disputed territory between Croatia and Serbia. It was proclaimed by the Czech libertarian Vít Jedlička in 2015. Another example worth mentioning is the Seasteading Institute’s plan to develop a floating city in the Pacific Ocean as a “permanent and politically autonomous settlement”.<br />\r\n <br />', 1, 'bbb', 0, 0, 1, 0, 1497669354, 1497628800, 1, ''),
(51, 'Bitcoin Was the Ninth Most Popular Wikipedia Article Last Year', 'Last year lots of people were inquiring about the cryptocurrency bitcoin and the word itself was one of the topmost trending words searched in 2017 according to Google Trends data. Another area where bitcoin was searched frequently was the website Wikipedia. The website hosts a free encyclopedia that is openly editable, while educational resources are also provided in 299 different languages. Wikipedia recently published its “Annual Top 50 Report” which includes a curated list of the top fifty most popular articles on Wikipedia throughout 2017.<br />\r\n <br />\r\n Bitcoin Was the Ninth Most Popular Wikipedia Article Last Year<br />\r\n The Wiki Bitcoin article was the ninth most popular last year on Wikipedia.<br />\r\n According to Wiki’s data, the ‘Bitcoin’ article was the ninth most popular encyclopedia post last year just below the ‘United States’ articles and just above the Netflix drama series ‘13 Reasons Why.’ Bitcoin stands among other top ten editorials documenting Donald Trump, Game of Thrones, and Queen Elizabeth II. The introduction in the Wiki Bitcoin article states:   <br />\r\n <br />\r\n Bitcoin is a cryptocurrency and worldwide payment system. It is the first decentralized digital currency, as the system works without a central bank or single administrator. The network is peer-to-peer and transactions take place between users directly, without an intermediary.<br />\r\n <br />\r\n Bitcoin Was the Ninth Most Popular Wikipedia Article Last Year<br />\r\n The Wiki Bitcoin article is just below the ‘United States’ article, and above the Netflix original show ’13 Reasons Why.’<br />\r\n Wiki Senior Editor: ‘Bitcoin the Much-Hyped “Future of Money”’<br />\r\n Last year the ‘Bitcoin’ article accumulated over 15 million views and the page peaked in traffic on December 8, 2017. In the annual report Wiki Senior Editor JFG gives the article a bit of an odd introduction.<br />\r\n <br />\r\n “For our dear readers who can’t make heads or tails of this novelty: Bitcoin is as good as gold, shinier than lead, bubblier than tulips, held deep in the mines, and driving people nuts,” explains the Wiki editor.  <br />\r\n <br />\r\n Gold has enriched adventurers and bitcoin has held fools to ransom. You may dive in a pool of gold, but lose it all at war. Strangely, while you can still buy gold today and forget about it until your great-grandchildren cash it out, the much-hyped “future of money” has turned into the most speculative intangible asset of all time, while proving totally unsuitable as a means of payment.<br />\r\n <br />\r\n Within the archives of 5,000 most popular articles from last week according to the Wiki page ‘User:west.andrew.g/popular pages,’ Bitcoin ranks at number 354. The page is aggregated from raw data which displays articles with at least 1,000 hits in a seven day period and only the most popular are published through the feed. Ethereum just makes the cut at 3710, Cryptocurrency 1273, and Blockchain slides ahead at 312. All of the data showing how popular digital currencies are on Wiki is derived from the company’s content consumption metrics which shows datasets of raw dump files and page views.<br />\r\n <br />\r\n What do you think about the Bitcoin article on Wikipedia placing 9th most popular in 2017? How do you think it will fare in 2018? Let us know in the comments below.<br />', 1, 'bbb', 0, 0, 1, 0, 1524897549, 1524897552, 1, ''),
(52, 'The Ukrainian Central Bank Is Expanding Its Blockchain Team', 'The National Bank of Ukraine is expanding the team working to move the country\'s national currency, the hyrvnia, to a blockchain.<br />\r\n <br />\r\n Revealed in an email to CoinDesk, the number of people added and who they are is being kept private for now, but the expansion shows a level of intent that hasn\'t been seen at many other central banks.<br />\r\n <br />\r\n \"Today, we\'ve reinforced our team with world-class professionals and are optimistic that the project will get a boost in upcoming months,\" wrote Yakiv Smolii, acting governor of the central bank.<br />\r\n <br />\r\n While the details of the team were not made public, CoinDesk last week reported that Ukraine-based Distributed Lab is helping with the build.<br />\r\n <br />\r\n Distributed Lab\'s founder, Pavel Kravchenko, confirmed that the startup is at least partly \"responsible for [the] architecture, blockchain research and development and security analysis\" of the institution\'s initiative.<br />\r\n <br />\r\n Central banks across the globe have been discussing and exploring blockchain technology for its ability to more efficiently track funds and reduce the expenses of commercial banks. For instance, the People\'s Bank of China has deemed the creation of a fiat-based cryptocurrency a \"crucial\" financial development.<br />\r\n <br />\r\n But still, the National Bank of Ukraine gave a more detailed vision of its undertaking to create a \"national digital currency,\" that makes its work less theoretical than others.<br />\r\n <br />\r\n According to Smolii:<br />\r\n <br />\r\n \"National bank of Ukraine is looking forward to implementation of e-hryvnia based on blockchain technology. We consider blockchain as the next step in evolution of transactions technologies, which will become more popular and widespread during the next decades.\"<br />\r\n <br />\r\n \'Convenient\' money<br />\r\n The central bank formally began working on a blockchain-based system in November 2016 to develop a \"cashless economy.\"<br />\r\n <br />\r\n While the results of the earlier research were not disclosed, Smolli told CoinDesk, the project is now \"focused on studying the ability of the central bank to establish [an] operational e-hryvnia solution which would be available 24/7 and easy-to-use for all stakeholders.\"<br />\r\n <br />\r\n In this way, the central bank is now working to build a more \"convenient instrument\" for Ukrainian citizens and businesses to conduct any number of transactions.<br />\r\n <br />\r\n Displaying how determined the central bank is to move forward with the project, the National Bank of Ukraine has begun surveying commercial banks in the country, in an effort to understand their \"readiness\" to \"support the circulation\" of a national fiat currency riding on a blockchain.<br />\r\n <br />\r\n The central bank is also looking at best practices employed by other blockchain users around the world.<br />', 1, 'bbb', 0, 0, 1, 0, 1525066282, 1525066285, 1, ''),
(53, 'Food industry companies block chain Alliance Block chain applications breakthrough again', '<p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	2017year6month23Day, ushered in Jinan, China Food Industry Association event - the Third China Food Safety and the circulation of cold chain logistics Development Summit at Sheraton Jinan Hotel grand opening, the Ziyun stake Shuanghui logistics, missing food, Chinese food group, Chia Tai(China)Investment Co., Ltd., Zhengzhou one thousand flavor central kitchen and other 20 companies initiated the China Food Industry Alliance was formally established chain block, which is the industrys one small step, one giant leap for food safety, which marks the block chain applications reproduction industry breakthrough, Chinese food safety upgrade!\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	from2017year3month28May the State Administration of Food and Drug Administration issued Provisions on food production and operation of the establishment of this food traceability system, clearly provides food manufacturers, food enterprises, a variety of data catering enterprises established food traceability system must be traceable, especially food clearly defined transportation information will continue retrospective, at the same time explicitly require food traceability information must ensure that information need not modify technically, specification. The new policy of strict food from farm to table each pass, the Chinese food safety into the four most serious of the era.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n \r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	As bears the main responsibility for food safety of Chinese food industry enterprises, how to deal with tighter government regulation under the new policy requirements, the industry has been a sore point. China Food Industry Association fully aware of business needs, invited industry experts on food industry enterprises, food cold chain logistics enterprises in-depth interpretation of the meeting, corporate crack confused.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p><p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	As vice president of logistics and food Branch of China Food Industry Association, Luo Jianhui, chairman of Ziyun shares based on the new regulatory requirements of the State Food and Drug Administration launched the first cloud services platform based on food traceability chain block, without increasing the cost of doing business as much as possible premise, in the cloud model, combined with the shares of Ziyun car-free carrier services, using block chain technology for the food industry enterprises to provide a new generation of food safety traceability service, first in the industry to achieve food during storage and transportation information can be traced back , the companys various food safety traceability information in real-time write block chain, technically perfect solution to the problem of traceability data can not be modified, so that the food traceability system for food industry enterprises are able to meet the regulatory requirements of government regulatory authorities.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	Retrospective, for regulators and can not be traced. Luo Jianhui further stressed that the public service must be retroactive, so that consumers have a convenient way to trace food purchased, food traceability to make the transition from the original post-supervision as consumers daily consumption of prevention in advance, so you can avoid not meet the quality requirements of food to be consumed, so as to avoid more incidents of food safety, the protection of public food safety to the maximum extent.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	Ziyun shares based on the introduction of small micro-channel program block chain so that consumers can simply focus on the retrospective, is an indispensable assistant Consumer food safety and security.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	The new policy ground, we must consider the interests of business. Luo Jianhui put forward new ideas on how food production enterprises to implement the national food Administration new drug regulatory policies, A lot of the domestic food industry enterprises mention of retrospective thinking about to increase investment, to increase the cost, without a proper understanding of the food traceability system. to know that serious chaos in food production, a large number of substandard food in the market yield expelled through low-cost, construction traceability system is actually a highlight corporate product quality and food safety an important means of main responsibility is an important measure to better protect their own markets. \r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	In the practice of food traceability, Luo Jianhui team led by innovative Ziyun shares of closely integrated food traceability and marketing business to help companies achieve service delivery through the food product traceability system, so that consumers can simultaneously get to the traceability of products About eating or cooking of the food so that consumers better enjoy high quality food, increase consumer stickiness of their products, increase consumer re-purchase rate of the product. In addition Ziyun shares of food security treasure dating back service can help companies get a lot of personal consumption data, so that enterprises can be precision marketing, improve economic efficiency. In view of the participating companies, the shares of food traceability service Ziyun perfect combination of product and corporate marketing services together, to help enterprises solve food safety problems at the same time, but also solve the problem of large data precision marketing Internet era, such a platform only vitality, can the regulatory policy of the country landing.\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	After the State Food and drug Administration issued a new policy, food traceability service this market has become just need to market, market size reach 100 billion. Luo Jianhui said he was optimistic. The face of billions of market cake, Ziyun shares chose to share with the industry, Ziyun share scheme in the country each province to find a business scale in5000About million for third-party cold chain logistics companies to jointly develop food traceability service market, for consumers to strict farm to table every pass, so that consumers enjoy food safety!\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	<br />\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	Source: Netease News\r\n </p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	</p>\r\n <p style=\"color:#333333;font-family:sans-serif;font-size:16px;background-color:#F9F9F9;\">\r\n 	Disclaimer: This article is reprinted, has nothing to do with the sea through a network, only with the spread of news, does not mean<span style=\"color:#333333;font-family:sans-serif;font-size:16px;line-height:24px;background-color:#F9F9F9;\">Trading platform </span>Point of view, does not constitute<span style=\"color:#333333;font-family:sans-serif;font-size:16px;line-height:24px;background-color:#F9F9F9;\">Trading platform </span>Investment advice, fried currency risk, the investment need to be cautious! !\r\n </p>\r\n <br />\r\n <br />\r\n <div style=\"margin:0px;padding:0px;border:0px;font-family:sans-serif;color:#990000;font-size:13px;background-color:#F9F9F9;\">\r\n 	Disclaimer: Some Article from the Internet, such as the reprint does not meet your wishes or infringe on your copyright, please contact us, we will remove as soon as possible.\r\n </div>', 1, 'bbb', 0, 1, 1, 0, 1498188169, 1498147200, 1, ''),
(54, '7 Facts You Might Not Know About Ripple', '<br />\r\n Ripple was one of the top-performing cryptocurrencies of 2017, up by a staggering 35,500% for the year. And as a relatively new player in the cryptocurrency space, Ripple isn\'t well understood by as many people as, say, bitcoin and Ethereum are.<br />\r\n <br />\r\n With that in mind, here are seven facts that will help familiarize you with Ripple, how the cryptocurrency works, and its key advantages over other leading digital tokens.<br />\r\n <br />\r\n Ripples on surface of water.<br />\r\n 1. Ripple isn\'t the cryptocurrency\'s official name<br />\r\n The cryptocurrency commonly referred to as Ripple is officially called the XRP. In other words, there\'s technically no such thing as buying \"100 Ripple.\"<br />\r\n <br />\r\n Ripple Labs is the name of the company that created the XRP token, and to be fair, Ripple is much easier to remember and a much catchier name, so that\'s why many people use the terms interchangeably.<br />\r\n <br />\r\n On that topic, if you were ever wondering why bitcoin isn\'t generally capitalized, but cryptocurrencies like Ripple, Stellar, and others are, it\'s because bitcoin isn\'t the name of a company, while many other cryptocurrencies are.<br />\r\n <br />\r\n 2. Ripple offers three different products, and only one uses the XRP token<br />\r\n One interesting fact that even many Ripple owners are surprised to learn is that the company\'s flagship product, xCurrent, doesn\'t really use the XRP cryptocurrency at all.<br />\r\n <br />\r\n xCurrent is designed to allow banks to transact with one another, and to provide compatibility between any currencies, not just cryptocurrencies. In fact, this is the product used in the well-known partnership with American Express and Santander.<br />\r\n <br />\r\n The product that trades XRP through the xCurrent system is known as xRapid. This supposedly has several key advantages, such as making transactions even faster and opening up new markets, but it isn\'t being widely used yet.<br />\r\n <br />\r\n <br />\r\n 3. You can\'t mine Ripple<br />\r\n Bitcoin\'s circulating supply gradually increases due to a process called mining, in which users pool their computing power to process transactions in exchange for newly minted \"blocks\" of tokens. Many other major cryptocurrencies also grow their supply through mining.<br />\r\n <br />\r\n However, Ripple is different. All 100 billion XRP that will ever be created already exist, although not all are in circulation yet, which we\'ll get to in the next section.<br />\r\n <br />\r\n 4. Only about 40% of XPR tokens are in circulation<br />\r\n Although there are 100 billion XRP tokens in existence, the majority of them aren\'t in circulation yet. Ripple Labs owns about 60 billion XRP as of this writing, 6.25 billion of which are directly owned and 55 billion of which are in escrow accounts for future distribution. Over the next few years, 1 billion XRP will become available per month to be distributed. So, the circulating supply could increase dramatically in the coming years.<br />\r\n <br />\r\n Based on the current XPR price of just under $0.80, the value of all XRP in circulation is $31.52 billion, making it the third-largest cryptocurrency by market cap. However, the combined value of all XRP in existence is almost $80 billion.<br />', 1, 'bbb', 0, 0, 1, 0, 1498361419, 1498320000, 1, ''),
(55, 'The World Economic Forum published a detailed white paper, Understanding the potential of the block chain', 'YouTuber ‘Genius Trader’ has recently uploaded a video in which he claims Ripple could fly past $11.00 by the 25th of May, due to a potentially huge announcement.<br />\r\n <br />\r\n Before I start, you can see the video for yourself, here- (Skip to 1:47) https://www.youtube.com/watch?v=nFoeSqh8ORU<br />\r\n <br />\r\n So, what’s going on and how real is this?<br />\r\n <br />\r\n Well of course, it’s a Ripple video so the focus here is on Ripple potentially making a huge partnership. Rightly so, Genius Trader admits that in order for his predictions to ring true, Ripple needs to see a huge gain in market cap, a gain that is quite unlikely given the present gaps in the market. Regardless of this, the show must go on.<br />\r\n <br />\r\n To cut to the chase, Genius Trader believes that Amazon will start accepting Ripple.<br />\r\n <br />\r\n Remember what happened to Verge when that rumour spread regarding XVG? Just imagine how huge an Amazon partnership would be for Ripple. Genius Trader now makes an interesting point that sort of reinforces his theory.<br />\r\n <br />\r\n Basically, in order to grow to $11.00, Ripple needs to really bolster its market capitalisation right? Well Amazon have a NET worth that could possibly contribute to that, after reports this week came out stating that overall, Amazons NET worth reached $105.1Billion, according to Genius Traders video.<br />\r\n <br />\r\n Moreover, Ripple can offer something incredible in return to Amazon, lost cost, high speed transactions that would allow Amazon to improve the efficiency at which it facilitates cross border payments.<br />\r\n <br />\r\n Right okay, so all of the above is sort of fair enough, it’s heavily speculative but I guess he has a point, the question I am asking now however- where does the 25th of May fit into this, is this just a complete stab in the dark?<br />\r\n <br />\r\n How realistic is this?<br />\r\n <br />\r\n It’s probably not going to happen soon and really, I doubt we will see Amazon accepting XRP as a payment option  before the 25th of May. I just think Amazon are way to big for this. If Amazon wanted to dip into cryptocurrency, I’m pretty sure they would have done it by now. They have the facilities and the finances to enter the blockchain themselves and obviously, an Amazon native currency would be most beneficial to them.<br />', 1, 'bbb', 0, 1, 1, 0, 1524897686, 1524897689, 1, ''),
(56, 'FLIP – token for gamers from gaming experts', 'Dear traders,<br />\r\n <br />\r\n We are happy to present a new token joining our exchange – the FLP token from Gameflip.<br />\r\n <br />\r\n This year alone, there are nearly 2 billion gamers and a growing USD $94.4 billion in revenue from ‘direct-from-publisher’ in-game digital items. Experts predict that it would only grow by 2020.<br />\r\n <br />\r\n <br />\r\n Gamers spend a significant amount of their lives earning and accumulating digital items within the game worlds they are a part of. However, if the player leaves a game, those in-game items, bought and earned, go to waste. Gameflip has developed a solution aiming to help gamers liquidate those goods.<br />\r\n <br />\r\n  <br />\r\n <br />\r\n Gameflip has extensive experience operating a digital marketplace for the buying and selling of in-game digital items and has already accumulated a notable audience of over 2M gamers. Now the company is focusing its efforts on providing gamers liquidity via a secure, transparent ecosystem based on blockchain.<br />\r\n <br />\r\n  <br />\r\n <br />\r\n The Gameflip team is made up of gaming industry veterans and has an advantage of having strong, established relationships with the gatekeepers of the industry – the game publishers that own and control the games and therefore, digital goods generated within. These ties developed over the past decade may significantly contribute to spreading the ecosystem and the FLP token.<br />', 1, 'aaa', 0, 0, 1, 0, 1525073427, 1525073429, 1, ''),
(120, 'What is joining fees?', 'Exchange is absolutely free to join!', 1, 'General Questions', NULL, 1, 1, 0, 1588172871, 1588201495, 1, ''),
(121, 'What cryptocurrencies can I use to purchase? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'General Questions', NULL, 1, 1, 0, 1588172950, 1588201495, 1, ''),
(122, 'How can I participate in the ICO Token sale? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'General Questions', NULL, 1, 1, 0, 1588172966, 1588201495, 1, ''),
(123, 'How do I benefit from the ICO Token? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'General Questions', NULL, 1, 1, 0, 1588172981, 1588201495, 1, ''),
(124, 'How do I benefit from the ICO Token? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'ICO Questions', NULL, 1, 1, 0, 1588173005, 1588201495, 1, ''),
(125, 'How do I benefit from the ICO Token? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'Token Listing', NULL, 1, 1, 0, 1588173016, 1588201495, 1, ''),
(126, 'How do I benefit from the ICO Token? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'Voting', NULL, 1, 1, 0, 1588173026, 1588201495, 1, ''),
(127, 'How do I benefit from the ICO Token? ', 'Once ICO period is launched, You can purchased Token with Etherum, \r\n Bitcoin or Litecoin. You can also tempor incididunt ut labore et dolore \r\n magna aliqua sed do eiusmod eaque ipsa.', 1, 'Voting', NULL, 1, 1, 0, 1588173030, 1588201495, 1, ''),
(128, 'Which Is The Best Cryptocurrency For Long-Term Investment? Find Out', '<span style=\"color:#2E2E2E;font-family:Roboto, sans-serif;font-size:42px;font-weight:900;background-color:#FFFFFF;\">Which Is The Best Cryptocurrency For Long-Term Investment? Find Out</span>', 1, 'news', NULL, 1, 1, 0, 1631000853, 1631000853, 1, ''),
(129, 'Bitcoin Faces Biggest Test As El Salvador Makes It Official Currency', '<span style=\"color:#2E2E2E;font-family:Roboto, sans-serif;font-size:42px;font-weight:900;background-color:#FFFFFF;\">Bitcoin Faces Biggest Test As El Salvador Makes It Official Currency</span>', 1, 'news', NULL, 0, 1, 0, 1631002142, 1631002142, 1, ''),
(130, 'Litecoin Prices Reach A 3-Month High As Fundamentals Continue To Improve', '<span style=\"color:#333333;font-family:Georgia, Cambria, &quot;font-size:18px;background-color:#FCFCFC;\">Litecoin prices have rallied lately, climbing to their highest since mid-May as the digital currency’s network continues to benefit from growing activity.</span>', 1, 'guide', NULL, 1, 1, 0, 1631012279, 1631012279, 1, '613745673bc54.jpg'),
(131, 'Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up', '<span style=\"color:#2E2E2E;font-family:Roboto, sans-serif;font-size:42px;font-weight:900;background-color:#FFFFFF;\">Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up</span>', 1, 'blog', NULL, 1, 1, 0, 1631012367, 1631012367, 1, '613745eb45e56.jpg'),
(132, 'Litecoin Prices Reach A 3-Month High As Fundamentals Continue To Improve', '<span style=\"color:#333333;font-family:Georgia, Cambria, &quot;font-size:18px;background-color:#FCFCFC;\">Litecoin prices have rallied lately, climbing to their highest since mid-May as the digital currency’s network continues to benefit from growing activity.</span>', 1, 'guide', NULL, 1, 1, 0, 1631012279, 1631012279, 1, '613745673bc54.jpg');
INSERT INTO `codono_article` (`id`, `title`, `content`, `adminid`, `type`, `hits`, `footer`, `index`, `sort`, `addtime`, `endtime`, `status`, `img`) VALUES
(133, 'Litecoin Prices Reach A 3-Month High As Fundamentals Continue To Improve', '<span style=\"color:#333333;font-family:Georgia, Cambria, &quot;font-size:18px;background-color:#FCFCFC;\">Litecoin prices have rallied lately, climbing to their highest since mid-May as the digital currency’s network continues to benefit from growing activity.</span>', 1, 'guide', NULL, 1, 1, 0, 1631012279, 1631012279, 1, '613745673bc54.jpg'),
(134, 'Litecoin Prices Reach A 3-Month High As Fundamentals Continue To Improve', '<span style=\"color:#333333;font-family:Georgia, Cambria, &quot;font-size:18px;background-color:#FCFCFC;\">Litecoin prices have rallied lately, climbing to their highest since mid-May as the digital currency’s network continues to benefit from growing activity.</span>', 1, 'guide', NULL, 1, 1, 0, 1631012279, 1631012279, 1, '613745673bc54.jpg'),
(135, 'Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up', '<span style=\"color:#2E2E2E;font-family:Roboto, sans-serif;font-size:42px;font-weight:900;background-color:#FFFFFF;\">Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up</span>', 1, 'blog', NULL, 1, 1, 0, 1631012367, 1631012367, 1, '613745eb45e56.jpg'),
(136, 'Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up', '<span style=\"color:#2E2E2E;font-family:Roboto, sans-serif;font-size:42px;font-weight:900;background-color:#FFFFFF;\">Meet \"The Jedi Master Of Crypto\": He Has Also Invested In Indian Start-Up</span>', 1, 'blog', NULL, 1, 1, 0, 1631012367, 1631012367, 1, '613745eb45e56.jpg'),
(137, 'What is Chat GPT , How can It help with Crypto', 'Main Takeaways\nIn basic terms, ChatGPT is an AI chatbot. Users can type in any text they like — a question or prompt, for example — and ChatGPT will generate text in response.\n\nChatGPT is easy to use and can generate human-like responses quickly on a wide range of topics. As of February 2023, ChatGPT has been entirely free.\n\nIt’s important to recognize that ChatGPT lacks any real understanding of the text it is processing. Any response it gives should therefore be treated with caution.\n\nWhen it comes to crypto adoption, ChatGPT offers great promise in terms of information and education. It can help educate users on blockchain and crypto concepts and make the technology more accessible to a wider audience.', 1, 'blog', NULL, 1, 1, 0, 1676037549, 1676037551, 1, '63e600833e49f.jpg'),
(139, 'kk', 'Friday, April 27: after a mid-week correction that has seen Bitcoin go below $9,000, the crypto market is back on track with all top 10 cryptocurrencies listed on Coin360 in the green.<br />\n<br />\nCOIN360<br />\n<br />\nBitcoin (BTC) is above $9,000 again, trading at about $9,263 with a value gain of about 5 percent over 24 hours to press time.<br />\n<br />\nBTC Vatue &amp; Volume<br />\n<br />\nEthereum (ETH) is steadily climbing towards the $700 mark, trading at around $678 at press time, up almost 8 percent from yesterday.<br />\n<br />\nETH Value &amp; Volume<br />\n<br />\nTotal market cap is again over $400 bln, currently at $417 bln, after having\ndropped to as low as $380 bln Thursday.<br />\n<br />\nTotal Market Capitalization<br />\n<br />\nEOS continues to grow significantly, seeing 19 percent gains over 24 hours and\ntrading around $17.40 at press time. With a growth of almost 200 percent over\nthe last 30 days, the cryptocurrency’s price is approaching the levels of\nJanuary, according to Coinmarketcap data.<br />\n<br />\nEOS Charts<br />\n<br />\nStellar (XLM) has increased by almost 10 percent over 24 hours - the altcoin is\ncurrently trading at $0.41.<br />\n<br />\nExcepting this week’s temporary decline, the crypto market has been moving\nupwards since the day Bitcoin’s price jumped $1,000 in 30 minutes on April 12.\nDuring this period, the markets have been propelled by a number of positive\nnews, particularly Goldman Sachs executives resigning for positions at crypto\nprojects, such as the crypto wallet Blockchain.com and the crypto merchant bank\nGalaxy Digital.<br />\n<br />\nEarlier today, Gil Beyda, the managing director of the venture capital arm of\nComcast, expressed a bullish view on Bitcoin and real world applications of\nBlockchain technology.<br />\n<br />\nOn Monday, Goldman Sachs hired cryptocurrency trader Justing Schmidt as vice\npresident of digital asset markets of its securities division, “in response to\nclient interest in various digital products.”<br />\n<div>\n	<br />\n</div>\nVersion 2', 1, 'Company Profile', NULL, 1, 1, 0, 1654063154, 1654063154, 1, ''),
(140, '50% Discount on Trading on Halloween', 'Get Upto 50% Discount on Trading on Halloween', 1, 'appbanner', NULL, 0, 0, 0, 1731047760, 1731047760, 1, '672db1bc1b3cd7.63699354.png'),
(141, 'New Pairs Added', 'New Pairs added to Exchange', 1, 'appbanner', NULL, 0, 0, 0, 1731049894, 1731049894, 1, '672db996637851.65200037.png');

-- --------------------------------------------------------

--
-- Table structure for table `codono_article_type`
--

DROP TABLE IF EXISTS `codono_article_type`;
CREATE TABLE IF NOT EXISTS `codono_article_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb3 DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb3 DEFAULT NULL,
  `remark` varchar(50) CHARACTER SET utf8mb3 DEFAULT NULL,
  `index` varchar(50) CHARACTER SET utf8mb3 DEFAULT NULL,
  `footer` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `shang` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_article_type`
--

INSERT INTO `codono_article_type` (`id`, `name`, `title`, `remark`, `index`, `footer`, `shang`, `content`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'Company Profile', 'Company Profile', 'Industry News', '0', '1', 'aboutus', '<p class=\"MsoNormal\">\r\n 	<span></span>Trading platform <span>(</span><span>www.CODONO.com</span><span>) Formally launched</span> \r\n </p>\r\n <p class=\"MsoNormal\">\r\n 	Trading platform digitalgoodsCurrency trading platform professional encrypted digital currency online trading platform.\r\n </p>\r\n <p class=\"MsoNormal\">\r\n 	<br />\r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<span>We hope that through the integration of resources accumulated over the years of globalization and digital currency in line with the trend of globalization, technology-based, to help build a global financial center Internet as the ultimate goal, so that the platform a</span><span>International</span><span>Digital assets and digital currency trading platform Industry Standard organization</span><span>.</span> \r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<b><span>Platform advantages:</span></b><b></b> \r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n 1,The most cutting-edge technology block chain system. We have a complete transaction system and digital encryption,<span></span>Trading platform <span>Trading platform block chain does not rely on third-party system, for storing network data distributed through its own node, verification and transmission technology, having a block chain to the central storage technology, information is highly transparent, non-tampering security features, and can achieve online and offline financial transactions docking full coverage, block chain technology will subvert the entire Internet infrastructure, and thus have a profound impact on the industry, known as the block chain</span>Fourth industrial revolution.\r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n 2,<span></span>Trading platform <span>Trading platform has developed into an encrypted digital currency as the core business of a diversified investment platform, comprehensive digital asset trading platform, serving the worlds leading brand of encrypted digital currency investment transactions.</span> \r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n 3,<span></span>Trading platform <span>Bitcoin trading platform support,</span><span>Ethernet Square</span><span>And other transaction encrypted digital currency.</span><span></span>Trading platform <span>Trading platform</span><span>Even block applications</span>As the core, and build membership system<span></span>Trading platform <span>Trading Platform wallet</span>Brick and mortar businesses and integrate the flow of the whole industry chain finance investment mode.<span></span>Trading platform Supports two-waytransaction,<span>low</span><span>Fees,Global arbitraryAccountReal-time arrival.</span><span></span>Trading platform <span>Trading Platform</span>With a strong block chain system provides transparent transaction, safe, reliable, efficient service revenue doubled.\r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n <span></span>Trading platform <span>It is encryptedDigital CurrencyConsumer businesses, online payment gradually integrate circulation, it is changing the storage, use and payment of funds to build a more secure and efficient encryptionDigital CurrencyThe internet,future,</span><span></span>Trading platform <span>We will provide more high-value digital assets services to global investors.</span> \r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<span><br />\r\n </span> \r\n </p>\r\n <p class=\"p\" style=\"text-indent:0pt;background:#FFFFFF;\">\r\n 	<br />\r\n </p>', 1, 1521717888, 1521717890, 1),
(3, 'help', 'Help', 'Help', '0', '1', '', '', 2, 0, 0, 1),
(4, 'aboutus', 'About', 'about us', '0', '1', '', '', 1, 1498792179, 1498792179, 1),
(5, 'Contact us', 'Contact us', '', '0', '1', 'aboutus', '\r\n 	Contact Us&nbsp;\r\n \r\n \r\n 	\r\n \r\n \r\n \r\n 	\r\n \r\n \r\n 	Info here \r\n ', 2, 1677526635, 1677526637, 1),
(6, 'JoinUs', 'JoinUs', '', '0', '1', 'aboutus', '<span><span style=\"font-size:14px;line-height:21px;\">\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		Job Description\r\n 	</h6>\r\n 	<p>\r\n 		Named among Fortune’s 2018 World’s Most Admired Companies, Flex offers a world of innovation, learning opportunities, and a strong reputation as environmentally responsible citizens. We are a leading sketch-to-scale™ company that designs and builds intelligent products for a connected world. With more than 200,000 professionals across 30 countries, and a promise to help the world Live smarter™, Flex provides innovative design, engineering, manufacturing, real-time supply chain insight and logistics services to companies of all sizes in various industries and end-markets.\r\n 	</p>\r\n 	<p>\r\n 		With more than 100,000 team members globally, we promote an environment that is rooted in the entrepreneurial spirit in which the company was founded. Dell ’ s team members are committed to serving our communities, regularly volunteering for over 1,500 non-profit organizations. The company has also received many accolades from employer of choice to energy conservation. Our team members follow an open approach to technology innovation and believe that technology is essential for human success.\r\n 	</p>\r\n 	<p>\r\n 		We are looking for a <span style=\"font-weight:700;\">Interaction UX/UI Industrial Designer</span> for our <span style=\"font-weight:700;\">Product Development</span> team!\r\n 	</p>\r\n </div>\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		Responsibilities:\r\n 	</h6>\r\n 	<p class=\"content-group-sm\">\r\n 		You will work closely with our product owners and the analytics team to help drive and ensure a best-in-class user experience on web, tablet and mobile platforms. With your knowledge and passion for keeping up-to-date with the latest advances in user interface design and web related technologies. You will be creating high quality designs with the goal of ensuring continual improvement of our sites. To realise this you will guide and set the standards and design principles for all of our brands to follow and work towards to enhance their online success.\r\n 	</p>\r\n 	<ul class=\"list\">\r\n 		<li>\r\n 			Gather, analyze, record and report on current market information with regard to the latest transportation methods.\r\n 		</li>\r\n 		<li>\r\n 			Work with the team to determine company and customer needs and make recommendations on cost effective transportation methods and assist in price negotiations if appropriate.\r\n 		</li>\r\n 		<li>\r\n 			Ensures lowest cost transportation by analyzing company and customer needs, researching transportation methods and auditing carrier costs and performances.\r\n 		</li>\r\n 		<li>\r\n 			Ensure laws, rules and regulations regarding shipping/transportation methods are adhered to and prepares applications for appropriate certifications and licenses.\r\n 		</li>\r\n 		<li>\r\n 			Prepare application for import / export control certifications and licenses (control documents).\r\n 		</li>\r\n 		<li>\r\n 			Maintain logs and compile information on routes, rates and services on various vendors.\r\n 		</li>\r\n 		<li>\r\n 			Arranges shipping details such as packing, shipping, and routing of product.\r\n 		</li>\r\n 		<li>\r\n 			Analyzes and recommends transportation and freight costs as well as appropriate routing and carriers to be used.\r\n 		</li>\r\n 		<li>\r\n 			Plans, schedules, and routes inbound and outbound domestic and international shipments of freight, using knowledge of applicable laws, tariffs, and Flextronics policies.\r\n 		</li>\r\n 		<li>\r\n 			Be familiar with compliance required for corporate, and facility policies and procedures and assist the team in ensuring the highest standards are adhered to in the process.\r\n 		</li>\r\n 		<li>\r\n 			Ensure Traffic Metrics are maintained and updated on a daily/weekly/monthly basis.\r\n 		</li>\r\n 		<li>\r\n 			Establish and maintain good relationships with agents / suppliers in order to achieve quality of service and consistent cost reduction.\r\n 		</li>\r\n 		<li>\r\n 			May schedule company vehicles for service and normal maintenance checks and is responsible for ensuring that all are registered and have the proper insurance.\r\n 		</li>\r\n 		<li>\r\n 			Support the team in terms of knowledge and experience in dealing with daily operational and transportation issues.\r\n 		</li>\r\n 	</ul>\r\n </div>\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		Requirements:\r\n 	</h6>\r\n 	<ul class=\"list\">\r\n 		<li>\r\n 			Undergraduate Industrial Design/Graphic Design degree and 6-8 years relevant experience or Graduate degree in a related field, plus 4-6 years relevant experience\r\n 		</li>\r\n 		<li>\r\n 			Strong skillset in digital design with an emphasis on Windows, mobile (iOS/Android), and web User Interfaces\r\n 		</li>\r\n 		<li>\r\n 			Ability to distill complex problems into intuitive, clean, user friendly designs\r\n 		</li>\r\n 		<li>\r\n 			Expert in User Experience concepts, Information Architecture, and software brand strategy\r\n 		</li>\r\n 		<li>\r\n 			Experience with creating detailed workflow specifications and behaviors for development teams\r\n 		</li>\r\n 		<li>\r\n 			Can process and integrate research studies, reports, trends, data, and information into plans, deliverables, and recommendations\r\n 		</li>\r\n 	</ul>\r\n </div>\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		Desired Skills and Experience:\r\n 	</h6>\r\n 	<ul class=\"list\">\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Strategic Thinking.</span>You will not only solve design issues but will proactively offer ideas and insights to improve the customer\'s experience and visual challenges.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Creative Suite.</span>Primarily Photoshop and Illustrator with some InDesign. Experience with Adobe Muse is also helpful.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Typography.</span>We need a designer who knows typography visual hierarchy and styles and how to use them properly. Your work will be for the whole EMEA region and be translated into dozens of languages. Any experience with Asian and Middle Eastern languages and fonts will be useful.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Experimentation.</span>Experience optimizing designs based on A/B testing is a plus.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Communication.</span>We need someone who can own their time but also knows how to ask the right questions to ensure the right message is communicated in the right way.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Marketing.</span>You are familiar with and have previously worked on a marketing team.\r\n 		</li>\r\n 		<li>\r\n 			<span class=\"display-block\" style=\"font-weight:700;\">Asset Management.</span>Assist with development and maintenance of a digital asset management system.\r\n 		</li>\r\n 	</ul>\r\n </div>\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		What we offer:\r\n 	</h6>\r\n 	<ul class=\"list\">\r\n 		<li>\r\n 			A learning prone environment where employee development and satisfaction lies at the heart of the organisation\r\n 		</li>\r\n 		<li>\r\n 			You choose and change your workplace besides our open office in our café area, or your home\r\n 		</li>\r\n 		<li>\r\n 			Life at Dell means collaborating with dedicated professionals with a passion for technology\r\n 		</li>\r\n 		<li>\r\n 			When we see something that could be improved, we get to work inventing the solution\r\n 		</li>\r\n 		<li>\r\n 			Our people demonstrate our winning culture through positive and meaningful relationship\r\n 		</li>\r\n 		<li>\r\n 			We invest in our people and offer a series of programs that enables them to pursue a career that fulfills their potential\r\n 		</li>\r\n 		<li>\r\n 			Our team members ’ health and wellness is our priority as well as rewarding them for their hard work\r\n 		</li>\r\n 	</ul>\r\n </div>\r\n <div class=\"content-group-lg\" style=\"color:#333333;font-family:Roboto, \"font-size:13px;background-color:#FFFFFF;\">\r\n 	<h6 class=\"text-semibold\" style=\"font-family:inherit;font-weight:500;color:inherit;font-size:15px;\">\r\n 		Interested?\r\n 	</h6>\r\n 	<p>\r\n 		We look forward to hearing from you! Please apply directly using the apply button below or via our website. In case you have any further questions about the role, you are welcome to contact Scott Ot, Recruitment Specialist on phone +01234567890.\r\n 	</p>\r\n </div>\r\n </span></span>', 4, 1523761584, 1523761590, 1),
(7, 'Legal Notices', 'Legal Notices', '', '0', '1', 'aboutus', '<div align=\"center\" class=\"MsoNormal\" style=\"text-align:left;\">\r\n 	<strong><span style=\"line-height:150%;font-size:26px;\">DISCLAIMER</span></strong> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<br />\r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<br />\r\n </div>\r\n <div class=\"MsoNormal\">\r\n 	<strong><span style=\"line-height:115%;font-size:19px;\">WEBSITE DISCLAIMER</span></strong> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<span style=\"color:#595959;\">&nbsp;</span> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<span style=\"color:#595959;font-size:15px;\">The information provided by Codono.com(“we,” “us” or “our”) on&nbsp;<span>https://codono.com&nbsp;</span>(the “Site”) and our mobile application&nbsp;is for general informational purposes only. All information on the Siteand our mobile application&nbsp;is provided in good faith, however we make no\r\n representation or warranty of any kind, express or implied, regarding the\r\n accuracy, adequacy, validity, reliability, availability or completeness of any\r\n information on the Site&nbsp;or our mobile application. UNDER NO CIRCUMSTANCE SHALL WE HAVE ANY LIABILITY TO YOU FOR ANY LOSS OR DAMAGE OF ANY KIND INCURRED AS A RESULT OF THE USE OF THE SITEOR OUR MOBILE APPLICATION&nbsp;OR RELIANCE ON ANY\r\n INFORMATION PROVIDED ON THE SITE&nbsp;AND OUR MOBILE APPLICATION. YOUR USE OF THE SITEAND OUR MOBILE APPLICATION&nbsp;AND YOUR RELIANCE ON ANY INFORMATION ON THE SITEAND OUR MOBILE APPLICATION&nbsp;IS SOLELY AT YOUR OWN RISK.</span> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<span style=\"color:#595959;\">&nbsp;</span> \r\n </div>\r\n <div class=\"MsoNormal\">\r\n 	<br />\r\n </div>\r\n <div class=\"MsoNormal\">\r\n 	<strong><span style=\"line-height:115%;font-size:19px;\">EXTERNAL LINKS\r\n DISCLAIMER</span></strong> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<span style=\"color:#595959;\">&nbsp;</span> \r\n </div>\r\n <div class=\"MsoNormal\" style=\"text-align:justify;\">\r\n 	<span style=\"color:#595959;font-size:15px;\">The&nbsp;</span><span style=\"font-size:15px;\"><span style=\"color:#595959;\">Siteand our mobile applicationmay contain (or you may be sent through the Siteor our mobile application) links</span></span><span style=\"color:#595959;font-size:15px;\">&nbsp;to other\r\n websites or content belonging to or originating from third parties or links to\r\n websites and features in banners or other advertising. Such external links are\r\n not investigated, monitored, or checked for accuracy, adequacy, validity, reliability,\r\n availability or completeness by us. WE DO NOT WARRANT, ENDORSE, GUARANTEE, OR\r\n ASSUME RESPONSIBILITY FOR THE ACCURACY OR RELIABILITY OF ANY INFORMATION\r\n OFFERED BY THIRD-PARTY WEBSITES LINKED THROUGH THE SITE OR ANY WEBSITE OR\r\n FEATURE LINKED IN ANY BANNER OR OTHER ADVERTISING. WE WILL NOT BE A PARTY TO OR\r\n IN ANY WAY BE RESPONSIBLE FOR MONITORING ANY TRANSACTION BETWEEN YOU AND THIRD-PARTY PROVIDERS OF PRODUCTS OR SERVICES.</span> \r\n </div>', 4, 1525667451, 1525667454, 1),
(8, 'Disclaimer', 'Disclaimer', '', '0', '1', 'Company Profile', '<p class=\"MsoNormal\">\r\n 	The relevant provisions of the relevant ministries of the Peoples Bank noted in, bitcoin digital currency system and other special virtual goods as a commodity trading behavior on the Internet, ordinary people have the freedom to participate in the premise own risk. Currently there is a lot of uncertainty digital currency industry, uncontrollable risk factors (such as pre-dig, spike, making manipulation, the team disbanded, technical defects, etc.), resulting in trading is very risky.<span></span>Trading platform  only digital currency and other virtual goods enthusiasts to provide a free online exchange platform for the<span></span>Source Haitong digital network platform for the exchange of virtual currency and other commodities, the value of the site operator does not undertake any review, warranty, liability for compensation.\r\n </p>', 5, 1497495947, 1497495955, 1),
(9, 'Registration Agreement', 'Registration Agreement', '', '0', '1', 'aboutus', 'This agreement is made by and between you and operator of CODONO and has the legal effect as a legal contract.<br />\r\n <br />\r\n The operator of CODONO means the legal entity that, recognized by law, operates the networking platform. Please refer to the company and license information at the bottom of the website of CODONO for the information regarding the operator of CODONO. The operator of CODONO may be referred to, individually or collectively, as “CODONO Limited” in this agreement. “CODONO” means the networking platform operated by CODONO, including but not limited to the CODONO website, with the domain name of CODONO.com, https://www.CODONO.com, which is encrypted.<br />\r\n <br />\r\n 1. Agreement and Execution<br />\r\n <br />\r\n The content of this agreement includes main body of this agreement and various rules that have been posted or may be posted from time to time by CODONO. All of the rules shall be an integral part of this agreement, and shall have the same legal effect as the main body of this agreement. Unless otherwise expressly provided, any service provided by CODONO and its affiliates (hereinafter referred as “CODONO Service”) shall be bound by this agreement. You shall carefully read through this agreement before using any CODONO Service, and pay close attention to the content written in bold font. You may consult CODONO if you have any question with regard to this agreement. However, regardless whether you have carefully read through this agreement before using CODONO Service, you shall be bound by this agreement as long as you use CODONO Service. You shall not claim to void or rescind this agreement on the ground that you did not read this agreement or you did not receive any respond from CODONO to your consultation. You hereby promise to accept and observe this agreement. If you do not agree to this agreement, you shall immediately stop registration/activation or stop using CODONO Service. CODONO may make or amend this agreement and various rules from time to time as needed, and announce the same on the website, without any individual notice to you. The amended agreement and rules shall come into effect immediately and automatically upon being announced on the website. If you do not agree to the relevant amendment, you shall immediately stop using CODONO Service. If you continue using CODONO Service, you shall be deemed as having accepted the amended agreement and rules.<br />\r\n <br />\r\n 2. Registration and Account<br />\r\n <br />\r\n Eligibility of Registrants<br />\r\n <br />\r\n You hereby confirm that you are an individual, legal person or other organization with full capacity for civil rights and civil conducts when you complete the registration or actually use CODONO Service in any other way allowed by CODONO. If you do not have the said capacity, you and your guardian shall undertake all the consequences resulted therefrom, and CODONO shall have the right to cancel or permanently freeze your account, and claims against you and your guardian for compensation.<br />\r\n <br />\r\n Registration and Account<br />\r\n <br />\r\n You shall be bound by this agreement once you have filled in information, read and agreed to this agreement and completed the registration process following the instructions on the registration page or you have filled information, read and agreed to this agreement and completed the activation process following the instructions on the activation page, or upon your actual use of CODONO Service in a way permitted by CODONO. You may log in CODONO by your email address or mobile number that you have provided or confirmed or any other means permitted by CODONO. You must provide your real name, ID type, ID number and other information required by the laws and regulations. If any information you have provided during the registration is inaccurate, CODONO will not take any responsibility and any loss, direct or indirect, and adverse consequence resulted therefrom will be borne by you. CODONO accounts can only be used by the person whose name they are registered under. CODONO reserves the right to suspend, freeze, or cancel accounts that are used by persons other than the persons whose names the accounts are registered under. CODONO will also not take legal responsibility for these accounts.<br />\r\n <br />\r\n User’s Information<br />\r\n <br />\r\n During the registration or activation, you shall accurately provide and timely update your information by following the instructions on the relevant page according to the laws and regulations in order to make it truthful, timely, complete and accurate. If there is any reasonable doubt that any information provided by you is wrong, untruthful, outdated or incomplete, CODONO shall have the right to send you a notice to make enquiry and demand corrections, remove relevant information directly and, as the case may be, terminate all or part of CODONO Service to you. CODONO will not take any responsibility and any loss, direct or indirect, and adverse consequence resulted therefrom will be borne by you. You shall accurately fill in and timely update your email address, telephone number, contact address, postal code and other contact information so that CODONO or any other user will be able to effectively contact you. You shall be solely and fully responsible for any loss or extra expenses incurred during the use of CODONO Service by you if you cannot be contacted through these contact information. You hereby acknowledge and agree that you have the obligation to keep your contact information effective and to take actions as required by CODONO if there is any change or update.<br />\r\n <br />\r\n Account Security<br />\r\n <br />\r\n You shall be solely responsible for the safekeeping of your CODONO account and password on your own, and you shall be responsible for all activities under your log-in email, CODONO account and password (including but not limited to information disclosure, information posting, consent to or submission of various rules and agreements by clicking on the website, online renewal of agreement or online purchase of services, etc.). You hereby agree that: a) you will notify CODONO immediately if you are aware of any unauthorized use of your CODONO account and password by any person or any other violations to the security rules; b) you will strictly observe the security, authentication, dealing, charging, withdrawal mechanism or procedures of the website/service; and c) you will log out the website by taking proper steps at the end of every visit. CODONO shall not and will not be responsible for any loss caused by your failure to comply with this provision. You understand that CODONO needs reasonable time to take actions upon your request, and CODONO will not undertake any responsibility for the consequences (including but not limited to any of your loss) that have occurred prior to such actions.<br />\r\n <br />\r\n 3. CODONO Service<br />\r\n <br />\r\n Through CODONO Service and other services provided by CODONO and its affiliates, members may post deal information, access to the pricing and dealing information of a deal and carry out the deal, participate in activities organized by CODONO and enjoy other information services and technical services. If you have any dispute with other members arising from any transaction on CODONO, once such dispute is submitted by one or both of you and the other member to CODONO for dispute resolution, CODONO shall have the right to make decision at its sole discretion. You hereby acknowledge and accept the discretion and decision of CODONO. You acknowledge and agree that, CODONO may, on requests from governmental authorities (including judicial and administrative departments), provide user information provided by you to CODONO, transaction records and any other necessary information. If you allegedly infringe upon any other’s intellectual rights or other legitimate interests, CODONO may provide the necessary ID information of you to the interest holder if CODONO preliminarily decides that the infringement exists. All the applicable taxes and all the expenses in relation to hardware, software, service and etc. arising during your use of the CODONO Service shall be solely borne by you. By using this service you accept that all trade executions are final and irreversible. By using this service you accept that CODONO reserves the right to liquidate any trades at any time regardless of the profit or loss position.<br />\r\n <br />\r\n 4. User’s Guide of CODONO Service<br />\r\n <br />\r\n You hereby promise to observe the following covenants during your use of CODONO Service on CODONO: All the activities that you carry out during the use of CODONO Service will be in compliance with the requirements of laws, regulations, regulatory documents and various rules of CODONO, will not be in violation of public interests, public ethnics or other’s legitimate interests, will not constitute evasion of payable taxes or fees and will not violate this agreement or relevant rules. If you violate the foregoing promises and thereby cause any legal consequence, you shall independently undertake all of the legal liabilities in your own name and hold CODONO harmless from any loss resulted from such violation. During any transaction with other members, you will be in good faith, will not take any acts of unfair competition, will not disturb the normal order of online transactions, and will not engage in any acts unrelated to online transactions. You will not use any data on CODONO for commercial purposes, including but not limited to using any data displayed on CODONO through copy, dissemination or any other means without prior written consent of CODONO. You will not use any device, software or subroutine to intervene or attempt to intervene the normal operation of CODONO or any ongoing transaction or activities on CODONO. You will not adopt any action that will induce unreasonable size of data loading on the network equipments of CODONO. You acknowledge and agree: CODONO shall have the right to unilaterally determine whether you have violated any of the covenants above and, according to such unilateral determination, apply relevant rules and take actions thereunder or terminate services to you, without your consent or prior notice to you. As required to maintain the order and security of transactions on CODONO, CODONO shall have the right to close relevant orders and take other actions in case of any malicious sale or purchase or any other events disturbing the normal order of transaction of the market. If your violation or infringement has been held by any effective legal documents issued by judicial or administrative authorities, or CODONO determines at its sole discretion that it is likely that you have violated the terms of this agreement or the rules or the laws and regulations, CODONO shall have the right to publish on CODONO such alleged violations and the actions that having been taken against you by CODONO. As to any information you may have published on CODONO that allegedly violates or infringes upon the law, other’s legitimate interests or this agreement or the rules, CODONO shall have the right to delete such information without any notice to you and impose punishments according to the rules. As to any act you may have carried out on CODONO, including those you have not carried out on CODONO but have had impacts on CODONO and its users, CODONO shall have the right to unilaterally determine its nature and whether it constitutes violation of this agreement or any rules, and impose punishments accordingly. You shall keep all the evidence related to your acts on your own and shall undertake all the adverse consequences resulted from your failure to discharge your burden of proof. If your alleged violation to your promises causes any losses to any third party, you shall solely undertake all the legal liabilities in your own name and hold CODONO harmless from any loss or extra expenses. If, due to any alleged violation by you to the laws or this agreement, CODONO incurs any losses, is claimed by any third party for compensation or suffers any punishment imposed by any administrative authorities, you shall indemnify CODONO against any losses and expense caused thereby, including reasonable attorney’s fee.<br />\r\n <br />\r\n 5. Scope and Limitation of Liability<br />\r\n <br />\r\n CODONO will provide CODONO Service at an “as is” and “commercially available” condition. CODONO disclaims any express or implied warranty with regards to CODONO Service, however, including but not limited to applicability, free from error or omission, continuity, accuracy, reliability or fitness for a particular purpose. Meanwhile, CODONO disclaims any promise or warranty with regards to the effectiveness, accuracy, correctness, reliability, quality, stability, completeness and timeliness of the technology and information involved by CODONO Service. You are fully aware that the information on CODONO is published by users on their own and may contain risks and defects. CODONO serves merely as a venue of transactions. CODONO serves merely as a venue where you acquire coin related information, search for counterparties of transactions and negotiate and conduct transactions, but CODONO cannot control the quality, security or legality of the coin involved in any transaction, truthfulness or accuracy of the transaction information, or capacity of the parties to any transaction to perform its obligations under the transaction documents. You shall cautiously make judgment on your own on the truthfulness, legality and effectiveness of the coin and information in question, and undertake any liabilities and losses that may be caused thereby. Unless expressly required by laws and regulations or any of the following circumstances occurs, CODONO shall not have any duty to conduct preliminary review on information data, transaction activity and any other transaction related issues of all users: CODONO has reasonable cause to suspect that a particular member and a particular transaction may materially violate the law or agreement. CODONO has reasonable cause to suspect that the activities conducted on CODONO by a member may be illegal or improper. You acknowledge and agree, CODONO shall not be liable for any of your losses caused by any of the following events, including but not limited to losses of profits, goodwill, usage or data or any other intangible losses (regardless whether CODONO has been advised of the possibility of such losses): use or failure to use CODONO Service. unauthorized use of your account or unauthorized alternation of your data by any third parties. expenses and losses incurred from purchase or acquisition of any data or information or engagement in transaction through CODONO Service, or any alternatives of the same. your misunderstanding on CODONO Service. any other losses related to CODONO Service which are not attributable to CODONO. In no event shall CODONO be liable for any failure or delay of service resulted from regular equipment maintenance of the information network, connection error of information network, error of computers, communication or other systems, power failure, strike, labor disputes, riots, revolutions, chaos, insufficiency of production or materials, fire, flood, tornado, blast, war, governmental acts or judicial orders. You agree to indemnify and hold harmless CODONO, its contractors, and its licensors, and their respective directors, officers, employees and agents from and against any and all claims and expenses, including attorneys’ fees, arising out of your use of the Website, including but not limited to out of your violation this Agreement.<br />\r\n <br />\r\n 6. Termination of Agreement<br />\r\n <br />\r\n You hereby agree that, CODONO shall have the right to terminate all or part of CODONO Service to you, temporarily freeze or permanently freeze (cancel) the authorizations of your account on CODONO at CODONO’s sole discretion, without any prior notice, for whatsoever reason, and CODONO shall not be liable to you; however, CODONO shall have the right to keep and use the transaction data, records and other information that is related to such account. In case of any of the following events, CODONO shall have the right to directly terminate this agreement by cancelling your account, and shall have the right to permanently freeze (cancel) the authorizations of your account on CODONO and withdraw the corresponding CODONO account thereof: after CODONO terminates services to you, you allegedly register or register in any other person’s name as CODONO user again, directly or indirectly; the main content of user’s information that you have provided is untruthful, inaccurate, outdated or incomplete; when this agreement (including the rules) is amended, you expressly state and notify CODONO of your unwillingness to accept the amended service agreement; any other circumstances where CODONO deems it should terminate the services. After the account service is terminated or the authorizations of your account on CODONO is permanently froze (cancelled), CODONO shall not have any duty to keep or disclose to you any information in your account or forward any information you have not read or sent to you or any third party. You agree that, after the termination of agreement between you and CODONO, CODONO shall still have the rights to: keep your user’s information and all the transaction information during your use of CODONO Service. Claim against you according to this agreement if you have violated any laws, this agreement or the rules during your use of CODONO Service. After CODONO suspends or terminates CODONO Service to you, your transaction activities prior to such suspension or termination will be dealt with according to the following principles and you shall will take care of on your own efforts and fully undertake any disputes, losses or extra expenses caused thereby and keep CODONO harmless from any losses or expenses: CODONO shall have the right to delete, at the same time of suspension or termination of services, information related to any un-traded coin tokens that you have uploaded to CODONO prior to the suspension or termination. If you have reached any purchase agreement with any other member prior to the suspension or termination but such agreement has not been actually performed, CODONO shall have the right to delete information related to such purchase agreement and the coins in question. If you have reached any purchase agreement with any other member prior to the suspension or termination and such agreement has been partially performed, CODONO may elect not to delete the transaction; provided, however, CODONO shall have the right to notify your counterparty of the situation at the same time of the suspension or termination.<br />\r\n <br />\r\n 7. Privacy Policy<br />\r\n <br />\r\n CODONO may announce and amend its privacy policy on the platform of CODONO from time to time and the privacy policy shall be an integral part of this agreement.<br />\r\n <br />', 6, 1523761518, 1523761522, 1),
(10, 'Registration Guide', 'Registration Guide', '', '0', '1', 'help', '<img src=\"/Upload/article/583a700024ba4.png\" alt=\"\" />', 1, 1497495861, 1497495865, 1),
(11, 'Trading Guide', 'Trading Guide', '', '0', '1', 'help', '', 2, 1497495802, 1497495805, 1),
(12, 'Recharge Guide', 'Recharge Guide', '', '0', '1', 'help', '', 3, 1497495770, 1497495773, 1),
(13, 'Recharge limit', 'Recharge limit', '', '0', '1', 'help', 'Minimum recharge $100 Maximum recharge $5000', 4, 1497495698, 1497495701, 1),
(14, 'Withdraw Guide', 'Withdraw Guide', '', '1', '1', 'help', 'Incase of issues contact customer support', 5, 1497495645, 1497495649, 1),
(19, 'aaa', 'Announcement', '', '1', '0', '', '<img src=\"/Upload/article/5955b7dbec138.png\" alt=\"\" />', 2, 1497456000, 1497456000, 0),
(20, 'bbb', 'Industry News', '', '1', '0', '', '', 3, 1497456000, 1497456000, 0),
(21, 'Mining', 'Mining', '', '1', '1', '', '', 4, 1497493937, 1497493942, 1),
(22, 'faq', 'faq', NULL, '0', '0', '', '', 0, 1588201442, 1588201451, 1),
(23, 'General Questions', 'General Questions', NULL, '0', '0', 'faq', '', 0, 1588201489, 1588201495, 1),
(24, 'ICO Questions', 'ICO Questions', NULL, '0', '0', 'faq', '', 0, 1588201489, 1588201495, 1),
(25, 'Token Listing', 'Token Listing', NULL, '1', '0', '', '', 0, 1588201489, 1588201495, 1),
(26, 'Voting', 'Voting', NULL, '0', '0', 'faq', NULL, 0, 1588201489, 1588201495, 1),
(27, 'news', 'news', NULL, '0', '0', '', '', 0, 1631000758, 0, 1),
(28, 'blog', 'Blog', NULL, '0', '0', '', '', 0, 1712002632, 0, 1),
(29, 'guide', 'Guide', NULL, '0', '0', '', '', 0, 1631011342, 0, 1),
(30, 'P2P-FAQ', 'FAQ', NULL, '0', '0', 'faq', '', 0, 1636445874, 0, 1),
(31, 'appbanner', 'Mobile Banners', NULL, '0', '0', '', '', 0, 1731047696, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_auth_extend`
--

DROP TABLE IF EXISTS `codono_auth_extend`;
CREATE TABLE IF NOT EXISTS `codono_auth_extend` (
  `group_id` mediumint(10) UNSIGNED NOT NULL COMMENT 'userid',
  `extend_id` mediumint(8) UNSIGNED NOT NULL COMMENT 'Extension data tableid',
  `type` tinyint(1) UNSIGNED NOT NULL COMMENT 'Extended type identifier 1:Column classification authority;2:Permissions model',
  UNIQUE KEY `group_extend_type` (`group_id`,`extend_id`,`type`),
  KEY `uid` (`group_id`),
  KEY `group_id` (`extend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_auth_extend`
--

INSERT INTO `codono_auth_extend` (`group_id`, `extend_id`, `type`) VALUES
(1, 1, 1),
(1, 1, 2),
(1, 2, 1),
(1, 2, 2),
(1, 3, 1),
(1, 3, 2),
(1, 4, 1),
(1, 37, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_auth_group`
--

DROP TABLE IF EXISTS `codono_auth_group`;
CREATE TABLE IF NOT EXISTS `codono_auth_group` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'user groupid,Auto-increment primary keys',
  `module` varchar(20) NOT NULL COMMENT 'User group module',
  `type` tinyint(4) NOT NULL COMMENT 'Group Type',
  `title` char(20) NOT NULL DEFAULT '' COMMENT 'User Group Chinese name',
  `description` varchar(80) NOT NULL DEFAULT '' COMMENT 'Description',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'User group status: is1Normal for the0Disable,-1For deletion',
  `rules` text NOT NULL DEFAULT '' COMMENT 'User groups have rulesid, More rules , Apart',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_auth_group`
--

INSERT INTO `codono_auth_group` (`id`, `module`, `type`, `title`, `description`, `status`, `rules`) VALUES
(2, 'admin', 1, 'Financial Management', 'Funds and transactions', 1, '2217'),
(3, 'admin', 1, 'Super Admin', 'All permissions', 1, '2163,2164,2165,2166,2167,2168,2169,2170,2171,2172,2173,2174,2175,2176,2177,2178,2179,2180,2181,2182,2183,2184,2185,2187,2188,2189,2190,2191,2192,2193,2194,2195,2196,2197,2198,2199,2200,2201,2202,2203,2204,2205,2206,2207,2208,2209,2210,2211,2212,2215,2216,2217,2218,2219,2220,2221,2222,2223,2224,2225,2226,2227,2228,2229,2230,2231,2232,2233,2234,2235,2236,2237,2238,2239,2240,2241,2242,2243,2244,2245,2246,2247,2248,2249,2250,2251,2253,2254,2255,2256,2257,2258,2259,2502,2503,2504,2505,2506,2507,2508,2509'),
(15, 'admin', 1, 'kyc only', '', 1, '1889,1890,2173,2175');

-- --------------------------------------------------------

--
-- Table structure for table `codono_auth_group_access`
--

DROP TABLE IF EXISTS `codono_auth_group_access`;
CREATE TABLE IF NOT EXISTS `codono_auth_group_access` (
  `uid` int(10) UNSIGNED NOT NULL COMMENT 'userid',
  `group_id` mediumint(8) UNSIGNED NOT NULL COMMENT 'user groupid',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_auth_group_access`
--

INSERT INTO `codono_auth_group_access` (`uid`, `group_id`) VALUES
(1, 11),
(2, 13),
(2, 15),
(3, 14);

-- --------------------------------------------------------

--
-- Table structure for table `codono_auth_rule`
--

DROP TABLE IF EXISTS `codono_auth_rule`;
CREATE TABLE IF NOT EXISTS `codono_auth_rule` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ruleid,Auto-increment primary keys',
  `module` varchar(20) NOT NULL COMMENT 'Rule belongsmodule',
  `type` tinyint(2) NOT NULL DEFAULT 1 COMMENT '1-url;2-main menu',
  `name` char(80) NOT NULL DEFAULT '' COMMENT 'The only rules of English identity',
  `title` char(30) NOT NULL DEFAULT '' COMMENT 'Rule description',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'is it effective(0:invalid,1:effective)',
  `condition` varchar(300) NOT NULL DEFAULT '' COMMENT 'Rules additional conditions',
  PRIMARY KEY (`id`),
  KEY `module` (`module`,`status`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=2510 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_auth_rule`
--

INSERT INTO `codono_auth_rule` (`id`, `module`, `type`, `name`, `title`, `status`, `condition`) VALUES
(425, 'admin', 1, 'Admin/article/add', 'New', -1, ''),
(427, 'admin', 1, 'Admin/article/setStatus', 'Change state', -1, ''),
(428, 'admin', 1, 'Admin/article/update', 'Storage', -1, ''),
(429, 'admin', 1, 'Admin/article/autoSave', 'save draft', -1, ''),
(430, 'admin', 1, 'Admin/article/move', 'mobile', -1, ''),
(437, 'admin', 1, 'Admin/Trade/config', 'Trading Config', -1, ''),
(449, 'admin', 1, 'Admin/Index/operate', 'Market Statistics', -1, ''),
(455, 'admin', 1, 'Admin/Issue/config', 'ICO Config', -1, ''),
(457, 'admin', 1, 'Admin/Index/database/type/export', 'data backup', -1, ''),
(461, 'admin', 1, 'Admin/Article/chat', 'Chat list', -1, ''),
(464, 'admin', 1, 'Admin/Index/database/type/import', 'Data Restore', -1, ''),
(471, 'admin', 1, 'Admin/Mytx/config', 'Withdraw Config', -1, ''),
(472, 'admin', 2, 'Admin/Mytx/index', 'withdraw', -1, ''),
(473, 'admin', 1, 'Admin/Config/market', 'Market allocation', -1, ''),
(477, 'admin', 1, 'Admin/User/myzr', 'crypto Deposits', -1, ''),
(479, 'admin', 1, 'Admin/User/myzc', 'crypto withdrawls', -1, ''),
(482, 'admin', 2, 'Admin/ExtA/index', 'Spread', -1, ''),
(488, 'admin', 1, 'Admin/Auth_manager/createGroup', 'New User Group', -1, ''),
(499, 'admin', 1, 'Admin/ExtA/index', 'Extension Manager', -1, ''),
(509, 'admin', 1, 'Admin/Article/adver_edit', 'edit', -1, ''),
(510, 'admin', 1, 'Admin/Article/adver_status', 'modify', -1, ''),
(513, 'admin', 1, 'Admin/Issue/index_edit', 'Edit ICO', -1, ''),
(514, 'admin', 1, 'Admin/Issue/index_status', 'Modify ICO', -1, ''),
(515, 'admin', 1, 'Admin/Article/chat_edit', 'edit', -1, ''),
(516, 'admin', 1, 'Admin/Article/chat_status', 'modify', -1, ''),
(517, 'admin', 1, 'Admin/User/coin_edit', 'coinmodify', -1, ''),
(519, 'admin', 1, 'Admin/Mycz/type_status', 'Modify status', -1, ''),
(520, 'admin', 1, 'Admin/Issue/log_status', 'ICO status', -1, ''),
(521, 'admin', 1, 'Admin/Issue/log_jiedong', 'ICO thaw', -1, ''),
(522, 'admin', 1, 'Admin/Tools/database/type/export', 'data backup', -1, ''),
(525, 'admin', 1, 'Admin/Config/coin_edit', 'edit', -1, ''),
(526, 'admin', 1, 'Admin/Config/coin_add', 'Edit currency', -1, ''),
(527, 'admin', 1, 'Admin/Config/coin_status', 'Modify status', -1, ''),
(528, 'admin', 1, 'Admin/Config/market_edit', 'edit', -1, ''),
(530, 'admin', 1, 'Admin/Tools/database/type/import', 'Data Restore', -1, ''),
(541, 'admin', 2, 'Admin/Trade/config', 'transaction', -1, ''),
(570, 'admin', 1, 'Admin/Tradelog/index', 'Transaction Record', -1, ''),
(585, 'admin', 1, 'Admin/Config/mycz', 'Recharge Config', -1, ''),
(590, 'admin', 1, 'Admin/Mycztype/index', 'Recharge type', -1, ''),
(600, 'admin', 1, 'Admin/Usergoods/index', 'User Address', -1, ''),
(1846, 'admin', 1, 'Admin/AuthManager/createGroup', 'New User Group', -1, ''),
(1847, 'admin', 1, 'Admin/AuthManager/editgroup', 'Edit User Groups', -1, ''),
(1848, 'admin', 1, 'Admin/AuthManager/writeGroup', 'Update User Group', -1, ''),
(1849, 'admin', 1, 'Admin/AuthManager/changeStatus', 'Change state', -1, ''),
(1850, 'admin', 1, 'Admin/AuthManager/access', 'Access authorization', -1, ''),
(1851, 'admin', 1, 'Admin/AuthManager/category', 'Classification Autho', -1, ''),
(1852, 'admin', 1, 'Admin/AuthManager/user', 'Members of the autho', -1, ''),
(1853, 'admin', 1, 'Admin/AuthManager/tree', 'Members of the list', -1, ''),
(1854, 'admin', 1, 'Admin/AuthManager/group', 'user group', -1, ''),
(1855, 'admin', 1, 'Admin/AuthManager/addToGroup', 'Added to the user gr', -1, ''),
(1856, 'admin', 1, 'Admin/AuthManager/removeFromGroup', 'User group removed', -1, ''),
(1857, 'admin', 1, 'Admin/AuthManager/addToCategory', 'Classified added to', -1, ''),
(1858, 'admin', 1, 'Admin/AuthManager/addToModel', 'Model added to the u', -1, ''),
(1859, 'admin', 1, 'Admin/Trade/status', 'Modify status', -1, ''),
(1860, 'admin', 1, 'Admin/Trade/reject', 'Revoked pending', -1, ''),
(1862, 'admin', 1, 'Admin/Login/index', 'User login', -1, ''),
(1863, 'admin', 1, 'Admin/Login/loginout', 'User exits', -1, ''),
(1864, 'admin', 1, 'Admin/User/setpwd', 'Change the administr', -1, ''),
(1877, 'admin', 1, 'Admin/Article/edit', 'Edit Add', -1, ''),
(1878, 'admin', 1, 'Admin/Text/index', 'Text Tips', -1, ''),
(1879, 'admin', 1, 'Admin/Text/edit', 'edit', -1, ''),
(1880, 'admin', 1, 'Admin/Text/status', 'modify', -1, ''),
(1882, 'admin', 1, 'Admin/User/config', 'User Config', -1, ''),
(1884, 'admin', 1, 'Admin/Finance/myczTypeEdit', 'Edit Add', -1, ''),
(1885, 'admin', 1, 'Admin/Finance/config', 'Config', -1, ''),
(1887, 'admin', 1, 'Admin/Finance/type', 'Types of', -1, ''),
(1888, 'admin', 1, 'Admin/Finance/type_status', 'Modify status', -1, ''),
(1889, 'admin', 1, 'Admin/User/edit', 'Edit Add', 1, ''),
(1890, 'admin', 1, 'Admin/User/status', 'Modify status', 1, ''),
(1891, 'admin', 1, 'Admin/User/adminEdit', 'Edit Add', -1, ''),
(1892, 'admin', 1, 'Admin/User/adminStatus', 'Modify status', -1, ''),
(1893, 'admin', 1, 'Admin/User/authEdit', 'Edit Add', -1, ''),
(1894, 'admin', 1, 'Admin/User/authStatus', 'Modify status', -1, ''),
(1895, 'admin', 1, 'Admin/User/authStart', 'Permission to re-ini', -1, ''),
(1896, 'admin', 1, 'Admin/User/logEdit', 'Edit Add', -1, ''),
(1897, 'admin', 1, 'Admin/User/logStatus', 'Modify status', -1, ''),
(1898, 'admin', 1, 'Admin/User/walletEdit', 'Edit Add', -1, ''),
(1900, 'admin', 1, 'Admin/User/walletStatus', 'Modify status', -1, ''),
(1901, 'admin', 1, 'Admin/User/bankEdit', 'Edit Add', -1, ''),
(1902, 'admin', 1, 'Admin/User/bankStatus', 'Modify status', -1, ''),
(1903, 'admin', 1, 'Admin/User/coinEdit', 'Edit Add', -1, ''),
(1904, 'admin', 1, 'Admin/User/coinLog', 'Property statistics', -1, ''),
(1905, 'admin', 1, 'Admin/User/goodsEdit', 'Edit Add', -1, ''),
(1906, 'admin', 1, 'Admin/User/goodsStatus', 'Modify status', -1, ''),
(1907, 'admin', 1, 'Admin/Article/typeEdit', 'Edit Add', -1, ''),
(1908, 'admin', 1, 'Admin/Article/linkEdit', 'Edit Add', -1, ''),
(1910, 'admin', 1, 'Admin/Article/adverEdit', 'Edit Add', -1, ''),
(1911, 'admin', 1, 'Admin/User/authAccess', 'Access authorization', -1, ''),
(1912, 'admin', 1, 'Admin/User/authAccessUp', 'Access unauthorized', -1, ''),
(1913, 'admin', 1, 'Admin/User/authUser', 'Members of the autho', -1, ''),
(1914, 'admin', 1, 'Admin/User/authUserAdd', 'Members of the autho', -1, ''),
(1915, 'admin', 1, 'Admin/User/authUserRemove', 'Members of the autho', -1, ''),
(1918, 'admin', 1, 'AdminUser/detail', 'User Details backgro', -1, ''),
(1919, 'admin', 1, 'AdminUser/status', 'Background user stat', -1, ''),
(1920, 'admin', 1, 'AdminUser/add', 'New user background', -1, ''),
(1921, 'admin', 1, 'AdminUser/edit', 'Users to edit the ba', -1, ''),
(1922, 'admin', 1, 'Admin/Articletype/edit', 'edit', -1, ''),
(1924, 'admin', 1, 'Admin/Topup/index', 'Recharge record', -1, ''),
(1925, 'admin', 1, 'Admin/Topup/config', 'Recharge Config', -1, ''),
(1928, 'admin', 1, 'Admin/Money/index', 'Money Management', -1, ''),
(1931, 'admin', 1, 'Admin/Article/images', 'upload image', -1, ''),
(1932, 'admin', 1, 'Admin/Adver/edit', 'edit', -1, ''),
(1933, 'admin', 1, 'Admin/Adver/status', 'modify', -1, ''),
(1935, 'admin', 1, 'Admin/User/index_edit', 'edit', -1, ''),
(1936, 'admin', 1, 'Admin/User/index_status', 'modify', -1, ''),
(1938, 'admin', 1, 'Admin/Finance/myczTypeStatus', 'Modify status', -1, ''),
(1939, 'admin', 1, 'Admin/Finance/myczTypeImage', 'upload image', -1, ''),
(1940, 'admin', 1, 'Admin/Finance/mytxStatus', 'Modify status', -1, ''),
(1945, 'admin', 1, 'Admin/Issue/edit', 'Edit ICO', -1, ''),
(1946, 'admin', 1, 'Admin/Issue/status', 'Modify ICO', -1, ''),
(1950, 'admin', 1, 'Admin/Link/edit', 'edit', -1, ''),
(1951, 'admin', 1, 'Admin/Link/status', 'modify', -1, ''),
(1954, 'admin', 1, 'Admin/Money/log', 'Money Log', -1, ''),
(1956, 'admin', 1, 'Admin/Chat/edit', 'edit', -1, ''),
(1957, 'admin', 1, 'Admin/Chat/status', 'modify', -1, ''),
(1961, 'admin', 1, 'Admin/Usercoin/edit', 'Modify property', -1, ''),
(1962, 'admin', 1, 'Admin/Finance/mytxExcel', 'Export selected', -1, ''),
(1964, 'admin', 1, 'Admin/Mycz/status', 'modify', -1, ''),
(1965, 'admin', 1, 'Admin/Mycztype/status', 'Modify status', -1, ''),
(1967, 'admin', 1, 'Admin/App/adsblock_list', 'APPAdvertising secto', -1, ''),
(1969, 'admin', 1, 'Admin/Tools/wallet', 'Check the wallet', -1, ''),
(1972, 'admin', 1, 'Admin/Topup/type', 'Recharge amount', -1, ''),
(1973, 'admin', 1, 'Admin/Money/fee', 'Financial details', -1, ''),
(1977, 'admin', 1, 'Admin/Finance/mytxChuli', 'Processing', -1, ''),
(1979, 'admin', 1, 'Admin/Config/bank_edit', 'edit', -1, ''),
(1981, 'admin', 1, 'Admin/Coin/status', 'Modify status', -1, ''),
(1983, 'admin', 1, 'Admin/Config/market_add', 'Modify status', -1, ''),
(1984, 'admin', 1, 'Admin/Tools/invoke', 'Other module calls', -1, ''),
(1985, 'admin', 1, 'Admin/Tools/optimize', 'Table Optimization', -1, ''),
(1986, 'admin', 1, 'Admin/Tools/repair', 'Repair Tables', -1, ''),
(1987, 'admin', 1, 'Admin/Tools/del', 'Removing Backup File', -1, ''),
(1988, 'admin', 1, 'Admin/Tools/export', 'backup database', -1, ''),
(1989, 'admin', 1, 'Admin/Tools/import', 'Restore Database', -1, ''),
(1990, 'admin', 1, 'Admin/Tools/excel', 'Export Database', -1, ''),
(1991, 'admin', 1, 'Admin/Tools/exportExcel', 'ExportExcel', -1, ''),
(1992, 'admin', 1, 'Admin/Tools/importExecl', 'ImportingExcel', -1, ''),
(1994, 'admin', 1, 'Admin/User/detail', 'User Details', -1, ''),
(1998, 'admin', 1, 'Admin/Topup/coin', 'payment method', -1, ''),
(2003, 'admin', 1, 'Admin/Finance/mytxReject', 'Undo withdrawals', -1, ''),
(2004, 'admin', 1, 'Admin/Mytx/status', 'Modify status', -1, ''),
(2005, 'admin', 1, 'Admin/Mytx/excel', 'cancel', -1, ''),
(2006, 'admin', 1, 'Admin/Mytx/exportExcel', 'Importingexcel', -1, ''),
(2016, 'admin', 1, 'Admin/Menu/importFile', 'Import File', -1, ''),
(2017, 'admin', 1, 'Admin/Menu/import', 'Importing', -1, ''),
(2024, 'admin', 1, 'Admin/Finance/mytxConfirm', 'Confirm Withdraw', -1, ''),
(2025, 'admin', 1, 'Admin/Finance/myzcConfirm', 'Confirm turn out', -1, ''),
(2030, 'admin', 1, 'Admin/Verify/code', 'Captcha', -1, ''),
(2031, 'admin', 1, 'Admin/Verify/mobile', 'Phone code', -1, ''),
(2035, 'admin', 1, 'Admin/User/myzc_qr', 'Confirm turn out', -1, ''),
(2036, 'admin', 1, 'Admin/Article/status', 'Modify status', -1, ''),
(2037, 'admin', 1, 'Admin/Finance/myczStatus', 'Modify status', -1, ''),
(2038, 'admin', 1, 'Admin/Finance/myczConfirm', 'Confirm arrival', -1, ''),
(2039, 'admin', 1, 'Admin/Article/typeStatus', 'Modify status', -1, ''),
(2040, 'admin', 1, 'Admin/Article/linkStatus', 'Modify status', -1, ''),
(2041, 'admin', 1, 'Admin/Article/adverStatus', 'Modify status', -1, ''),
(2042, 'admin', 1, 'Admin/Article/adverImage', 'upload image', -1, ''),
(2051, 'admin', 2, 'Admin/Game/index', 'ICO', -1, ''),
(2053, 'admin', 2, 'Admin/Operate/index', 'System', -1, ''),
(2079, 'admin', 1, 'Admin/Tools/queue', 'Server queue', -1, ''),
(2107, 'admin', 1, 'Admin/Finance/mytxConfig', 'Fiat Config', -1, ''),
(2127, 'admin', 1, 'Admin/Initial/index', 'IEO', -1, ''),
(2128, 'admin', 1, 'Admin/Initial/log', 'Records', -1, ''),
(2140, 'admin', 2, 'Admin/Hybrid/index', 'Dex Deposit', -1, ''),
(2159, 'admin', 1, 'Admin/Shop/images', 'image', 1, ''),
(2160, 'admin', 1, 'Admin/Invest/Index', 'Investbox', -1, ''),
(2161, 'admin', 1, 'Admin/Invest/investlist', 'Investments', -1, ''),
(2162, 'admin', 1, 'Admin/Invest/dicerolls', 'DiceRolls', -1, ''),
(2163, 'admin', 1, 'Admin/Pool/index', 'Mining Machines', 1, ''),
(2164, 'admin', 1, 'Admin/Pool/userMachines', 'User Machines', 1, ''),
(2165, 'admin', 1, 'Admin/Pool/userRewards', 'Mining Rewards', 1, ''),
(2166, 'admin', 2, 'Admin/Index/index', 'Dashboard', 1, ''),
(2167, 'admin', 2, 'Admin/Article/index', 'Content', 1, ''),
(2168, 'admin', 2, 'Admin/User/index', 'User', 1, ''),
(2169, 'admin', 2, 'Admin/Finance/index', 'Finance', 1, ''),
(2170, 'admin', 2, 'Admin/Trade/index', 'Trade', 1, ''),
(2171, 'admin', 2, 'Admin/Vote/index', 'Community', 1, ''),
(2172, 'admin', 1, 'Admin/Operate/index', 'Bonus Logs', 1, ''),
(2173, 'admin', 1, 'Admin/Index/index', 'Dashboard', 1, ''),
(2174, 'admin', 1, 'Admin/Article/index', 'Articles', 1, ''),
(2175, 'admin', 1, 'Admin/User/index', 'Users', 1, ''),
(2176, 'admin', 1, 'Admin/Finance/index', 'Financial details', 1, ''),
(2177, 'admin', 1, 'Admin/Tools/index', 'Clear cache', 1, ''),
(2178, 'admin', 1, 'Admin/Trade/index', 'Trade', 1, ''),
(2179, 'admin', 1, 'Admin/Config/index', 'Basic', 1, ''),
(2180, 'admin', 1, 'Admin/App/config', 'APP Config', 1, ''),
(2181, 'admin', 1, 'Admin/Shop/index', 'Products', 1, ''),
(2182, 'admin', 1, 'Admin/Vote/index', 'Voting Record', 1, ''),
(2183, 'admin', 1, 'Admin/Vote/type', 'Voting type', 1, ''),
(2184, 'admin', 1, 'Admin/Issue/index', 'ICO', 1, ''),
(2185, 'admin', 1, 'Admin/Issue/log', 'Records', 1, ''),
(2186, 'admin', 1, 'Admin/User/award', 'Award', -1, ''),
(2187, 'admin', 1, 'Admin/Faucet/index', 'Faucet', 1, ''),
(2188, 'admin', 1, 'Admin/Faucet/log', 'Logs', 1, ''),
(2189, 'admin', 1, 'Admin/Competition/index', 'Competition Logs', 1, ''),
(2190, 'admin', 1, 'Admin/Competition/type', 'Competition', 1, ''),
(2191, 'admin', 1, 'Admin/Article/type', 'Categories', 1, ''),
(2192, 'admin', 1, 'Admin/Finance/mycz', 'Fiat Deposit', 1, ''),
(2193, 'admin', 1, 'Admin/User/admin', 'Admins', 1, ''),
(2194, 'admin', 1, 'Admin/Trade/log', 'Logs', 1, ''),
(2195, 'admin', 1, 'Admin/Config/cellphone', 'SMS', 1, ''),
(2196, 'admin', 1, 'Admin/Invit/config', 'Promotion', 1, ''),
(2197, 'admin', 1, 'Admin/App/vip_config_list', 'APP VIP', 1, ''),
(2198, 'admin', 1, 'Admin/Index/coin', 'Coin stats', 1, ''),
(2199, 'admin', 1, 'Admin/Shop/config', 'Config', 1, ''),
(2200, 'admin', 1, 'Admin/Index/market', 'Market', 1, ''),
(2201, 'admin', 1, 'Admin/Article/adver', 'Big Slider', 1, ''),
(2202, 'admin', 1, 'Admin/Trade/chat', 'Chat', 1, ''),
(2203, 'admin', 1, 'Admin/Finance/myczType', 'Payment Gateways', 1, ''),
(2204, 'admin', 1, 'Admin/User/auth', 'Permissions', 1, ''),
(2205, 'admin', 1, 'Admin/Config/contact', 'Support', 1, ''),
(2206, 'admin', 1, 'Admin/App/ads_list/block_id/1', 'WAP Banners', 1, ''),
(2207, 'admin', 1, 'Admin/Shop/type', 'Categories', 1, ''),
(2208, 'admin', 1, 'Admin/Dividend/index', 'Airdrop', 1, ''),
(2209, 'admin', 1, 'Admin/Article/link', 'Small Slider', 1, ''),
(2210, 'admin', 1, 'Admin/User/signup_log', 'Signup Attempts', 1, ''),
(2211, 'admin', 1, 'Admin/User/log', 'Signin Log', 1, ''),
(2212, 'admin', 1, 'Admin/Finance/mytx', 'Fiat Withdrawal', 1, ''),
(2213, 'admin', 1, 'Admin/Coin/edit', 'edit', 1, ''),
(2214, 'admin', 1, 'Admin/Market/edit', 'Editing Market', 1, ''),
(2215, 'admin', 1, 'Admin/Config/coin', 'Coins', 1, ''),
(2216, 'admin', 1, 'Admin/App/ads_user', 'APP Ads', 1, ''),
(2217, 'admin', 1, 'Admin/Shop/coin', 'Payment method', 1, ''),
(2218, 'admin', 1, 'Admin/Trade/comment', 'Coin Reviews', 1, ''),
(2219, 'admin', 1, 'Admin/User/wallet', 'Users wallet', 1, ''),
(2220, 'admin', 1, 'Admin/Trade/market', 'Market', 1, ''),
(2221, 'admin', 1, 'Admin/Config/text', 'Tips', 1, ''),
(2222, 'admin', 1, 'Admin/Shop/log', 'Orders', 1, ''),
(2223, 'admin', 1, 'Admin/Dividend/log', 'Records', 1, ''),
(2224, 'admin', 1, 'Admin/Tools/safety', 'Safety Screen', 1, ''),
(2225, 'admin', 1, 'Admin/User/bank', 'Withdrawal Address', 1, ''),
(2226, 'admin', 1, 'Admin/Trade/invit', 'Invite', 1, ''),
(2227, 'admin', 1, 'Admin/Finance/myzr', 'Crypto Deposit', 1, ''),
(2228, 'admin', 1, 'Admin/Config/misc', 'Misc Config', 1, ''),
(2229, 'admin', 1, 'Admin/Shop/goods', 'Shipping address', 1, ''),
(2230, 'admin', 1, 'Admin/Tools/debug', 'Debug', 1, ''),
(2231, 'admin', 1, 'Admin/Tools/register', 'Signup Activity', 1, ''),
(2232, 'admin', 1, 'Admin/User/coin', 'User Spot Balances', 1, ''),
(2233, 'admin', 1, 'Admin/Finance/myzc', 'Crypto Withdraw', 1, ''),
(2234, 'admin', 1, 'Admin/Verify/email', 'Mail Code', 1, ''),
(2235, 'admin', 1, 'Admin/Options/index', 'Other Config', 1, ''),
(2236, 'admin', 1, 'Admin/Config/navigation', 'Top Menu', 1, ''),
(2237, 'admin', 1, 'Admin/Config/footer', 'Footer Menu', 1, ''),
(2238, 'admin', 1, 'Admin/User/goods', 'Address', 1, ''),
(2239, 'admin', 1, 'Admin/Otc/index', 'OTC plans', 1, ''),
(2240, 'admin', 1, 'Admin/Fx/index', 'FX plans', 1, ''),
(2241, 'admin', 1, 'Admin/Bank/Index', 'Supported Banks', 1, ''),
(2242, 'admin', 1, 'Admin/Activity/index', 'Do Deposit/Withdraw', 1, ''),

(2244, 'admin', 1, 'Admin/Otc/log', 'OTC logs', 1, ''),

(2246, 'admin', 1, 'Admin/Fx/log', 'FX logs', 1, ''),

(2252, 'admin', 2, 'Admin/Invest/index', 'Earn', -1, ''),
(2253, 'admin', 2, 'Admin/Config/index', 'Config', 1, ''),
(2254, 'admin', 2, 'Admin/Tools/index', 'Tools', 1, ''),
(2255, 'admin', 1, 'Admin/Hybrid/config', 'Dex Config', 1, ''),
(2256, 'admin', 1, 'Admin/Hybrid/quotes', 'Dex Quotes', 1, ''),
(2257, 'admin', 1, 'Admin/Hybrid/coins', 'Dex Coins', 1, ''),
(2258, 'admin', 1, 'Admin/Hybrid/index', 'Dex Deposit', 1, ''),
(2259, 'admin', 1, 'Admin/Email/index', 'Email', 1, ''),
(2502, 'admin', 1, 'Admin/Staking/Index', 'Staking', 1, ''),
(2503, 'admin', 1, 'Admin/Staking/stakingLog', 'Staking Logs', 1, ''),
(2504, 'admin', 1, 'Admin/Staking/dicerolls', 'DiceRolls', 1, ''),
(2505, 'admin', 1, 'Admin/Pool/fees', 'Mining Fees', 1, ''),
(2506, 'admin', 1, 'Admin/Fees/transfers', 'Transfers', 1, ''),
(2507, 'admin', 1, 'Admin/Fees/index', 'Wallet Transfer Fees', 1, ''),
(2508, 'admin', 1, 'Admin/Fees/income', 'Transfer/Staking Income', 1, ''),
(2509, 'admin', 2, 'Admin/Staking/index', 'Earn', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `codono_award`
--

DROP TABLE IF EXISTS `codono_award`;
CREATE TABLE IF NOT EXISTS `codono_award` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `awardname` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `addtime` bigint(20) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `codono_awards`
--

DROP TABLE IF EXISTS `codono_awards`;
CREATE TABLE IF NOT EXISTS `codono_awards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `probability` decimal(5,2) DEFAULT NULL COMMENT 'Chance of winning this award (0-100%)',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_awards`
--

INSERT INTO `codono_awards` (`id`, `name`, `description`, `image`, `probability`, `status`, `created_at`, `updated_at`, `quantity`) VALUES
(1, 'Apple Laptop', 'Win an Apple Laptop', 'apple_laptop.png', '10.00', 1, '2024-11-04 07:50:54', '2024-11-04 08:24:19', 1),
(2, 'Smartphone', 'Win a Smartphone', 'smartphone.png', '20.00', 1, '2024-11-04 07:50:54', '2024-11-04 08:24:21', 2),
(3, 'Gift Card', 'Win a $100 Gift Card', 'gift_card.png', '50.00', 1, '2024-11-04 07:50:54', '2024-11-04 08:24:23', 19),
(4, 'No Prize', 'No prize awarded', 'no_prize.png', '99.00', 1, '2024-11-04 07:50:54', '2024-11-04 08:24:36', 100000);

-- --------------------------------------------------------

--
-- Table structure for table `codono_award_attempts`
--

DROP TABLE IF EXISTS `codono_award_attempts`;
CREATE TABLE IF NOT EXISTS `codono_award_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `attempt_date` date NOT NULL COMMENT 'Date of the award attempt',
  `attempts` int(4) DEFAULT 1 COMMENT 'Number of attempts on this date',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid_date` (`userid`,`attempt_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_award_attempts`
--

INSERT INTO `codono_award_attempts` (`id`, `userid`, `attempt_date`, `attempts`) VALUES
(1, 30, '2024-11-04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_binance`
--

DROP TABLE IF EXISTS `codono_binance`;
CREATE TABLE IF NOT EXISTS `codono_binance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symbol` varchar(20) NOT NULL,
  `priceChange` decimal(20,8) DEFAULT 0.00000000,
  `priceChangePercent` decimal(5,2) DEFAULT 0.00,
  `weightedAvgPrice` decimal(20,8) DEFAULT NULL,
  `prevClosePrice` decimal(20,8) DEFAULT NULL,
  `lastPrice` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `lastQty` decimal(20,8) DEFAULT NULL,
  `bidPrice` decimal(20,8) DEFAULT NULL,
  `bidQty` decimal(20,8) DEFAULT NULL,
  `askPrice` decimal(20,8) DEFAULT NULL,
  `askQty` decimal(20,8) DEFAULT NULL,
  `openPrice` decimal(20,8) DEFAULT NULL,
  `highPrice` decimal(20,8) DEFAULT NULL,
  `lowPrice` decimal(20,8) DEFAULT NULL,
  `volume` decimal(50,12) DEFAULT NULL,
  `quoteVolume` decimal(30,12) DEFAULT NULL,
  `openTime` int(13) NOT NULL,
  `closeTime` int(13) NOT NULL,
  `firstId` bigint(20) DEFAULT NULL,
  `lastId` bigint(20) DEFAULT NULL,
  `count` int(13) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `symbol` (`symbol`),
  KEY `id` (`id`),
  KEY `idx_lastPrice` (`lastPrice`),
  KEY `idx_openTime` (`openTime`),
  KEY `idx_closeTime` (`closeTime`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_binance`
--

INSERT INTO `codono_binance` (`id`, `symbol`, `priceChange`, `priceChangePercent`, `weightedAvgPrice`, `prevClosePrice`, `lastPrice`, `lastQty`, `bidPrice`, `bidQty`, `askPrice`, `askQty`, `openPrice`, `highPrice`, `lowPrice`, `volume`, `quoteVolume`, `openTime`, `closeTime`, `firstId`, `lastId`, `count`) VALUES
(88, 'MNDEUSDT', '-0.00264000', '-0.90', '0.29744182', '0.28903000', '0.28903000', '0.00000000', '0.28868000', '1.95350000', '0.28907000', '100.60310000', NULL, '0.30701000', '0.28630000', '167109.431200000000', '49705.053384522000', 0, 0, -1, 1710260661000, NULL),
(89, 'ETHBTC', '-0.00027000', '-0.52', '0.05208128', '0.05216000', '0.05190000', '0.02000000', '0.05190000', '42.50640000', '0.05191000', '6.67310000', '0.05217000', '0.05253000', '0.05168000', '30937.582000000000', '1611.268942890000', 0, 0, 449668524, 449800146, NULL),
(90, 'LTCBTC', '0.00000700', '0.61', '0.00114309', '0.00114900', '0.00115600', '0.16800000', '0.00115600', '312.53300000', '0.00115700', '105.57700000', '0.00114900', '0.00116200', '0.00112600', '60544.219000000000', '69.207462860000', 0, 0, 97638322, 97652950, NULL),
(91, 'DOGEBTC', '0.00000006', '2.91', '0.00000211', '0.00000206', '0.00000212', '49864.00000000', '0.00000211', '1574180.00000000', '0.00000212', '506824.00000000', '0.00000206', '0.00000217', '0.00000205', '40194879.000000000000', '84.991545130000', 0, 0, 66577586, 66586635, NULL),
(92, 'BTCUSDT', '261.20001221', '0.39', '68586.23503353', '67262.78000000', '67523.98000000', '0.00029000', '67523.98000000', '1.68532000', '67523.99000000', '10.98013000', '67262.78000000', '69999.00000000', '66969.98000000', '39561.424400000000', '2713369152.159507400000', 0, 0, 3632677866, 3634472672, NULL),
(93, 'ETHUSDT', '-3.54999995', '-0.10', '3574.93554409', '3508.73000000', '3505.20000000', '2.26650000', '3505.19000000', '58.74400000', '3505.20000000', '3.19060000', '3508.75000000', '3659.01000000', '3476.21000000', '411678.711200000000', '1471724857.413452000000', 0, 0, 1445989161, 1446882505, NULL),
(94, 'ADAUSDT', '0.00740000', '1.74', '0.43569107', '0.42410000', '0.43160000', '6017.10000000', '0.43160000', '57277.80000000', '0.43170000', '11581.10000000', '0.42420000', '0.44450000', '0.42360000', '133300470.200000000000', '58077823.863260000000', 0, 0, 492311070, 492429302, NULL),
(95, 'ETHTRY', '-467.00000000', '-0.41', '115883.35265935', '114029.00000000', '113606.00000000', '0.02320000', '113592.00000000', '0.01160000', '113608.00000000', '0.60100000', '114073.00000000', '118867.00000000', '112859.00000000', '955.551300000000', '110732488.282000000000', 0, 0, 23874036, 23887429, NULL),
(96, 'ETCTRY', '-2.09999990', '-0.25', '850.19810599', '835.10000000', '837.80000000', '4.84600000', '831.40000000', '2.43600000', '834.60000000', '0.85400000', '839.90000000', '867.00000000', '827.30000000', '1185.525000000000', '1007931.109600000000', 0, 0, 678704, 679140, NULL),
(97, 'LTCTRY', '26.00000000', '1.04', '2535.54589023', '2509.00000000', '2535.00000000', '0.61800000', '2530.00000000', '3.53100000', '2532.00000000', '16.35200000', '2509.00000000', '2571.00000000', '2494.00000000', '2572.541000000000', '6522795.760000000000', 0, 0, 1160328, 1163140, NULL),
(98, 'USDTRUB', '0.00000000', '0.00', '0.00000000', '91.10000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.000000000000', '0.000000000000', 0, 0, -1, -1, NULL),
(99, 'BNBUSDT', '-1.00000000', '-0.16', '619.40721822', '609.80000000', '608.70000000', '0.33300000', '608.60000000', '56.12400000', '608.70000000', '54.16500000', '609.70000000', '635.40000000', '602.80000000', '454754.301000000000', '281678096.557200000000', 0, 0, 763530324, 763961376, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_binance_trade`
--

DROP TABLE IF EXISTS `codono_binance_trade`;
CREATE TABLE IF NOT EXISTS `codono_binance_trade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symbol` varchar(15) DEFAULT NULL,
  `orderId` int(11) NOT NULL,
  `orderListId` int(11) DEFAULT NULL,
  `clientOrderId` varchar(30) NOT NULL,
  `transactTime` varchar(20) NOT NULL,
  `price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `origQty` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `executedQty` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `cummulativeQuoteQty` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `status` varchar(30) NOT NULL,
  `timeInForce` varchar(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `side` varchar(10) NOT NULL,
  `fills` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='executed orders from binance liquidity';

-- --------------------------------------------------------

--
-- Table structure for table `codono_bonus`
--

DROP TABLE IF EXISTS `codono_bonus`;
CREATE TABLE IF NOT EXISTS `codono_bonus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(10) DEFAULT NULL,
  `uidstart` int(11) NOT NULL DEFAULT 0,
  `uidend` int(11) NOT NULL DEFAULT 0,
  `coin` varchar(11) DEFAULT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `total` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `title` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_bonus`
--

INSERT INTO `codono_bonus` (`id`, `type`, `uidstart`, `uidend`, `coin`, `amount`, `total`, `title`, `description`, `addtime`, `endtime`, `status`) VALUES
(1, 'kyc', 1, 250000, 'xrp', '0.00000000', '0.00000000', 'KYC Pack2', 'First 250k Users get 1 xrp', 1602186256, 1604173497, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_category`
--

DROP TABLE IF EXISTS `codono_category`;
CREATE TABLE IF NOT EXISTS `codono_category` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'classificationID',
  `name` varchar(30) NOT NULL COMMENT 'Mark',
  `title` varchar(50) NOT NULL COMMENT 'title',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sub-headingsID',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sort (effectively the same level)',
  `list_row` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'List the number of lines per page',
  `meta_title` varchar(50) NOT NULL DEFAULT '' COMMENT 'SEOThe page title',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'Keyword',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT 'description',
  `template_index` varchar(100) NOT NULL COMMENT 'Channel page template',
  `template_lists` varchar(100) NOT NULL COMMENT 'List Template',
  `template_detail` varchar(100) NOT NULL COMMENT 'Details page template',
  `template_edit` varchar(100) NOT NULL COMMENT 'Edit page template',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT 'Relational Model',
  `type` varchar(100) NOT NULL DEFAULT '' COMMENT 'Allow the type of content published',
  `link_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Outside the chain',
  `allow_publish` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Yes No Allowed to publish content',
  `display` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Visibility',
  `reply` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Yes No Reply allowed',
  `check` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Whether to publish the article needs to be reviewed',
  `reply_model` varchar(100) NOT NULL DEFAULT '',
  `extend` text NOT NULL COMMENT 'Extended Setup',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Created',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Updated',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Data status',
  `icon` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Category Icon',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Category Table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_category`
--

INSERT INTO `codono_category` (`id`, `name`, `title`, `pid`, `sort`, `list_row`, `meta_title`, `keywords`, `description`, `template_index`, `template_lists`, `template_detail`, `template_edit`, `model`, `type`, `link_id`, `allow_publish`, `display`, `reply`, `check`, `reply_model`, `extend`, `create_time`, `update_time`, `status`, `icon`) VALUES
(1, 'blog', 'Blog', 0, 0, 10, '', '', '', '', '', '', '', '2', '2,1', 0, 0, 1, 0, 0, '1', '', 1379474947, 1382701539, 1, 0),
(2, 'default_blog', 'default category', 1, 1, 10, '', '', '', '', '', '', '', '2', '2,1,3', 0, 1, 1, 0, 1, '1', '', 1379475028, 1386839751, 1, 31);

-- --------------------------------------------------------

--
-- Table structure for table `codono_chat`
--

DROP TABLE IF EXISTS `codono_chat`;
CREATE TABLE IF NOT EXISTS `codono_chat` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `market` varchar(20) NOT NULL DEFAULT 'btc_usdt',
  `userid` varchar(20) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb3 COMMENT='Text chat table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_chat`
--

INSERT INTO `codono_chat` (`id`, `market`, `userid`, `username`, `content`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'btc_usdt', '19', 'mancore', 'trollme', 0, 1520639118, 1520621118, 1),
(2, 'btc_usdt', '19', 'mancore', '[/#66]', 0, 1520639229, 1520621229, 1),
(3, 'btc_usdt', '19', 'mancore', 'Ola\n', 0, 1520639744, 1520621744, 1),
(4, 'btc_usdt', '19', 'mancore', 'This is quick test\n', 0, 1520639755, 1520621755, 1),
(5, 'btc_usdt', '19', 'mancore', '@mancore :hyy\n', 0, 1520639858, 1520621858, 1),
(6, 'btc_usdt', '19', 'mancore', '@mancore :yeah\n', 0, 1520639870, 1520621870, 1),
(7, 'btc_usdt', '19', 'mancore', 'Just signedup', 0, 1520639881, 1520621881, 1),
(8, 'btc_usdt', '22', 'demo123', 'Me too!!', 0, 1520640077, 1520622077, 1),
(9, 'btc_usdt', '19', 'mancore', 'That was fast!', 0, 1520640086, 1520622086, 1),
(10, 'btc_usdt', '19', 'mancore', 'Lets buy some EOS', 0, 1520641369, 1520623369, 1),
(11, 'btc_usdt', '19', 'mancore', 'yeah', 0, 1520641380, 1520623380, 1),
(12, 'btc_usdt', '22', 'demo123', 'see u later\n', 0, 1520641389, 1520623389, 1),
(13, 'btc_usdt', '19', 'mancore', 'OKKKK\n', 0, 1520641395, 1520623395, 1),
(14, 'btc_usdt', '22', 'demo123', 'Sell your LTC !!', 0, 1520641400, 1520623400, 1),
(15, 'btc_usdt', '19', 'mancore', 'fine\n', 0, 1520641413, 1520623413, 1),
(16, 'btc_usdt', '22', 'demo123', 'BCH to the moon!!!', 0, 1520641420, 1520623420, 1),
(17, 'btc_usdt', '19', 'mancore', 'fine\n', 0, 1520641413, 1520623413, 1),
(18, 'btc_usdt', '22', 'demo123', 'Buy XRP!!', 0, 1520641420, 1520623420, 1),
(19, 'btc_usdt', '19', 'mancore', 'fine\n', 0, 1520641413, 1520623413, 1),
(59, 'btc_usdt', '19', 'mancore', 'fine\n', 0, 1520641413, 1520623413, 1),
(139, 'btc_usdt', '19', 'mancore', '@demo123 :stop spamm', 0, 1520641637, 1520623637, 1),
(140, 'btc_usdt', '22', 'demo123', 'ok\n', 0, 1520642838, 1520624838, 1),
(141, 'btc_usdt', '22', 'demo123', 'yeah\n', 0, 1520642855, 1520624855, 1),
(142, 'btc_usdt', '22', 'demo123', 'HMm\n', 0, 1520642865, 1520624865, 1),
(143, 'btc_usdt', '23', 'demouser', 'Bravo', 0, 1521074844, 1521056844, 1),
(144, 'btc_usdt', '23', 'demouser', 'kk\n', 0, 1521555878, 1521537878, 1),
(147, 'btc_usdt', '30', '123456789', '@demouser :True\n', 0, 1524273885, 1524255885, 1),
(149, 'btc_usdt', '1', 'technicator', 'Ltc to usd?\n', 0, 1524675146, 1524657146, 1),
(150, 'btc_usdt', '1', 'technicator', 'Just got my 2fa', 0, 1524675276, 1524657276, 1),
(151, 'btc_usdt', '1', 'technicator', 'Amazing', 0, 1525351803, 1525333803, 1),
(152, 'btc_usdt', '31', '3216549870', 'Looks nice\n', 0, 1525452500, 1525434500, 1),
(153, 'btc_usdt', '38', 'amber', '@technicator :hi', 0, 1620662312, 1620644312, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_coin`
--

DROP TABLE IF EXISTS `codono_coin`;
CREATE TABLE IF NOT EXISTS `codono_coin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `symbol` varchar(30) DEFAULT NULL COMMENT 'master symbol for any coin/token',
  `type` varchar(50) DEFAULT NULL COMMENT 'rmb=fiat, qbb=bitcoin, eth=eth based,rgb=ico',
  `rpc_type` varchar(30) DEFAULT NULL COMMENT 'light or full , if light then url public rpc',
  `public_rpc` varchar(255) DEFAULT NULL COMMENT 'infura, bnb seed url or others',
  `tokenof` varchar(10) DEFAULT NULL COMMENT 'like waves, eth, bnb , etc',
  `network` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=testnet , 1=mainnet',
  `title` varchar(50) DEFAULT NULL,
  `img` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `fee_bili` varchar(50) DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL COMMENT '',
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) UNSIGNED DEFAULT NULL,
  `block` bigint(20) DEFAULT 0 COMMENT 'current block being read',
  `fee_meitian` varchar(200) DEFAULT NULL COMMENT 'Daily limit',
  `dj_zj` varchar(200) DEFAULT NULL,
  `dj_dk` varchar(200) DEFAULT NULL,
  `dj_yh` varchar(200) DEFAULT NULL,
  `dj_mm` varchar(200) DEFAULT NULL,
  `contract` varchar(64) DEFAULT NULL,
  `zr_zs` varchar(50) DEFAULT NULL,
  `zr_jz` tinyint(1) DEFAULT 1,
  `zr_dz` tinyint(2) DEFAULT 1 COMMENT 'network confirmations',
  `zr_sm` varchar(50) DEFAULT NULL,
  `zc_sm` varchar(50) DEFAULT NULL,
  `zc_coin` varchar(30) DEFAULT NULL,
  `zc_fee` varchar(50) DEFAULT NULL,
  `zr_fee` decimal(20,5) NOT NULL DEFAULT 0.00000,
  `zc_flat_fee` decimal(20,8) DEFAULT 0.00000000 COMMENT 'flat withdrawal fees',
  `zc_user` varchar(50) DEFAULT NULL,
  `zc_min` decimal(20,8) DEFAULT 0.00000001,
  `zc_max` decimal(20,8) DEFAULT 99999999999.00000000,
  `zc_jz` tinyint(1) DEFAULT NULL,
  `zc_zd` decimal(20,8) DEFAULT NULL,
  `js_yw` varchar(50) DEFAULT NULL,
  `js_sm` text DEFAULT NULL,
  `js_qb` varchar(60) DEFAULT NULL COMMENT 'domain name',
  `js_ym` varchar(50) DEFAULT NULL,
  `js_gw` varchar(50) DEFAULT NULL,
  `js_lt` varchar(50) DEFAULT NULL,
  `js_wk` varchar(50) DEFAULT NULL,
  `cs_yf` varchar(50) DEFAULT NULL,
  `cs_sf` varchar(50) DEFAULT NULL,
  `cs_fb` varchar(50) DEFAULT NULL,
  `cs_qk` varchar(50) DEFAULT NULL,
  `cs_zl` varchar(50) DEFAULT NULL,
  `cs_cl` varchar(50) DEFAULT NULL,
  `cs_zm` varchar(50) DEFAULT NULL,
  `cs_nd` varchar(50) DEFAULT NULL,
  `cs_jl` varchar(50) DEFAULT NULL,
  `cs_ts` varchar(50) DEFAULT NULL,
  `cs_bz` varchar(50) DEFAULT NULL,
  `tp_zs` varchar(50) DEFAULT NULL,
  `tp_js` varchar(50) DEFAULT NULL,
  `tp_yy` varchar(50) DEFAULT NULL,
  `tp_qj` varchar(50) DEFAULT NULL,
  `codono_coinaddress` varchar(100) DEFAULT NULL,
  `dest_tag` varchar(50) DEFAULT NULL COMMENT 'dest_tag for xmr,xrp,xlm',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb3 COMMENT='The currency allocation table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_coin`
--

INSERT INTO `codono_coin` (`id`, `name`, `symbol`, `type`, `rpc_type`, `public_rpc`, `tokenof`, `network`, `title`, `img`, `sort`, `fee_bili`, `endtime`, `addtime`, `status`, `block`, `fee_meitian`, `dj_zj`, `dj_dk`, `dj_yh`, `dj_mm`, `contract`, `zr_zs`, `zr_jz`, `zr_dz`, `zr_sm`, `zc_sm`, `zc_coin`, `zc_fee`, `zr_fee`, `zc_flat_fee`, `zc_user`, `zc_min`, `zc_max`, `zc_jz`, `zc_zd`, `js_yw`, `js_sm`, `js_qb`, `js_ym`, `js_gw`, `js_lt`, `js_wk`, `cs_yf`, `cs_sf`, `cs_fb`, `cs_qk`, `cs_zl`, `cs_cl`, `cs_zm`, `cs_nd`, `cs_jl`, `cs_ts`, `cs_bz`, `tp_zs`, `tp_js`, `tp_yy`, `tp_qj`, `codono_coinaddress`, `dest_tag`) VALUES
(1, 'usd', '', 'rmb', 'Select Type', '', '0', 1, 'USD', 'usd.png', 0, '0', 1651482007, 0, 1, NULL, '0', 'x.x.x.x', '0', '0', '0', NULL, '0', 1, 1, '0', '0', NULL, '0.5', '0.00000', '2.00000000', '', '0.01000000', '10000.00000000', 1, '10.00000000', 'USD', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(38, 'btc', '', 'rgb', 'Select Type', '', '0', 0, 'Bitcoin', 'BTC.png', 1, '100', 1717061858, NULL, 1, NULL, '0', '', '', '1CVCX8zSGmi3NDD5LjmZusQUcCUMWTEQtk', 'BP3OqUly7dadwePKhPZGNvBs9rLN51NtiawGNa0Z', '', '0', 1, 1, NULL, NULL, '0', '0.5', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bitcoin', '', '', '', '', '', '', '', '', '', '8', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(39, 'eth', '', 'esmart', 'light', 'https://eth-ropsten.alchemyapi.io/v2/V87sc02UwOOY4m2_AGys_oQ1gdLsco9B', '0', 0, 'Ethereum', 'ETH.png', 1027, '100', 1675767328, NULL, 1, 15083388, '100', '62833c373402c90007468fd4', '9150', '', 'NHRxZ2d1RXZvQUx4VmJlRkR3WEdia1Rja1pkNGg2dUpNTzV5bXVoektoMFpEdkJwU2pxL1B1Wkg1V0RSTVpCbQ==', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.00100000', '10000.00000000', 1, '1.00000000', '', 'This is description', 'http://etherscan.io/', '', 'http://etherscan.io/', 'http://etherscan.io/', 'https://etherscan.io/tx/', 'Vitalik', 'Ehash', '2015', '8', '1000000', '90000', 'PoS', '', '2', 'POS, POW', 'Mining', NULL, '5', '5', '5', '0xe21dc596378ca932ecbac6586a8ea2abc23db60e', NULL),
(40, 'xrp', NULL, 'xrp', NULL, NULL, '0', 1, 'Ripple', 'XRP.png', 52, '0', 1618812268, NULL, 1, NULL, '0', '127.0.0.1', '150001', '', 'x', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'xrp', '', '', '', '', '', 'https://xrpcharts.ripple.com/#/transactions/', '', '', '', '6', '', '', '', '', '', 'ok', '', NULL, NULL, NULL, NULL, 'r4r28F2rXSsHvGF3Yjouq2XBWGUhrDtpX2', NULL),
(41, 'bch', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Bitcoin Cash', 'BCH.png', 1831, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bch', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'usdt', '', 'esmart', 'Select Type', '', 'eth', 1, 'Tether USDT', 'USDT.png', 825, '0', 1663157820, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'erc20', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(43, 'eos', NULL, 'coinpay', NULL, NULL, NULL, 1, 'EOS', 'EOS.png', 1765, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'eos', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'ltc', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Litecoin', 'LTC.png', 2, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'ltc', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'bsv', NULL, 'coinpay', NULL, NULL, NULL, 1, 'BitcoinSV', 'BSV.png', 3602, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bsv', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'bnb', '', 'blockgum', 'light', 'https://bsc-dataseed.binance.org/', '0', 1, 'BinanceCoin', 'BNB.png', 1839, '0', 1708931980, NULL, 1, 19712106, '100', '', '', '', 'x', '', '0', 1, 1, NULL, NULL, 'btc', '0.5', '0.00000', '1.00000000', '0', '0.00010000', '10000.00000000', 1, '0.00100000', 'bep20', '', '', '', '', '', 'https://bscscan.com/tx/', '', '', '', '8', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '0x', NULL),
(47, 'xlm', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Stellar', 'XLM.png', 512, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'xlm', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'link', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Chainlink', 'LINK.png', 1975, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'link', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'ada', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Cardano', 'ADA.png', 2010, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'ada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 'xmr', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Monero', 'XMR.png', 328, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'xmr', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 'trx', '', 'tron', 'Select Type', '', '0', 1, 'Tron', 'TRX.png', 1958, '0', 1724396652, NULL, 1, 21227551, '100', '', '', '', 'R1AxWVF1MTRGSCtxcHZSNzdnS1RRa29aK2tuNU9Qb0d4NUxKRXl1YlpGbXlqeXpWdGlFN2laSVRiNnU1TEpFQldqVXMxTDVqczRYOEtSSzVybFE2Ylg1cXR6bHRWUmVqVXo0c092dDJlM2M9', '', '0', 1, 1, '', '', '0', '1', '0.00000', '2.00000000', '0', '0.01000000', '10000.00000000', 1, '100.00000000', 'trc20', '', '', '', '', '', 'https://tronscan.org/#/transaction/', '', '', '', '6', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', NULL),
(52, 'dash', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Dash', 'DASH.png', 131, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'dash', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 'etc', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Ethereum Classic', 'ETC.png', 1321, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'etc', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 'neo', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Neo', 'NEO.png', 1376, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'neo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(55, 'atom', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Cosmos', 'ATOM.png', 3794, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'atom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(56, 'zec', NULL, 'coinpay', NULL, NULL, NULL, 1, 'zCash', 'ZEC.png', 1437, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'zec', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, 'doge', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Dogecoin', 'DOGE.png', 74, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'doge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, 'bat', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Basic Attention Token', 'BAT.png', 1697, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bat', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, 'rvn', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Raven', 'RVN.png', 2577, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'rvn', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(61, 'waves', NULL, 'waves', NULL, NULL, NULL, 1, 'Waves', 'Waves.png', 1274, '0', 1616444367, NULL, 1, NULL, '100', '127.0.0.1', '00', '', 'xx', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'waves', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'addr', NULL),
(66, 'etn', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Electronium', 'ETN.png', 2137, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'etn', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 'grin', NULL, 'coinpay', NULL, NULL, NULL, 1, 'Grin', 'GRIN.png', 3709, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'grin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 'beam', NULL, 'coinpay', NULL, NULL, NULL, 1, 'beam', 'BEAM.png', 3702, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'beam', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, 'dft', NULL, 'coinpay', NULL, NULL, NULL, 1, 'DigiFinex Token', 'DFT.png', 1120, '0', 1589735467, NULL, 1, NULL, '100', 'merchantid', 'IPNSecret', 'PubKey', 'PrivKey', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'dft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, 'eur', NULL, 'rmb', NULL, NULL, NULL, 1, 'EURO', 'EUR.png', NULL, '0', 1591681521, NULL, 1, NULL, '', '', '', '', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'eur', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(82, 'dwe', NULL, 'qbb', NULL, NULL, NULL, 1, 'DWE', '5f28f54989e00.png', NULL, '0', 1602228732, NULL, 0, NULL, '', '0.0.0.1', '123', 'xxx', 'vvv', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '100.00000000', 'dwe', '', '', '', '', '', '', '', '', '', '8', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(83, 'zar', NULL, 'rmb', NULL, NULL, NULL, 1, 'ZAR', 'ZAR.png', NULL, '100', 1597564436, NULL, 1, NULL, '100', '', '', '', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'zar', '', '', '', '', '', '', '', '', '', '2', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(84, 'krb', NULL, 'cryptonote', NULL, NULL, '0', 1, 'KRB', 'KRB.png', 1340, '0', 1622800691, NULL, 1, 526676, '', '127.0.0.1', '8070', 'first.wallet', 'xx', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'krb', '', '', '', '', '', 'https://explorer.karbo.io/?hash=', '', '', '', '8', '', '', '', 'ww', '', '', '', NULL, NULL, NULL, NULL, 'x', NULL),
(85, 'tsf', '', 'cryptoapis', 'Select Type', '', '0', 1, 'Teslafunds', '', 12746, '100', 1653029858, NULL, 1, 1298170, '100', '127.0.0.1', '4949', '', 'gam123', NULL, '0', 1, 1, NULL, NULL, NULL, '0.5', '0.00000', '0.00040000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'TSF', '', '', '', '', '', 'https://tsfexplorer.xyz/tx/', '', '', '', '8', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '0x', NULL),
(86, 'ugx', NULL, 'rmb', NULL, NULL, NULL, 1, 'Ugandan Shilling ', '5fcdd9245e235.png', NULL, '0', 1607325992, NULL, 1, NULL, '', '', '', '', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'ugx', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(88, 'wbnb', NULL, 'esmart', NULL, NULL, 'bnb', 1, 'Wrapped BNB', '', NULL, '0', 1617446847, NULL, 0, NULL, '', 'x:x@x', '4052', 'x', 'x==', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', 'https://bscscan.com/tx/', '', '', '', '18', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'x', NULL),
(89, 'bbt', NULL, 'esmart', NULL, NULL, 'bnb', 1, 'BibiToken', '', 10201, '0', 1629908045, NULL, 1, NULL, '', 'abc', '4052', '0x', 'x', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', 'https://bscscan.com/tx/', '', '', '', '18', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '0xeb324ef91e6f2aa1cd2e09a6232e3cf0b7080882', NULL),
(91, 'try', NULL, 'rmb', NULL, NULL, '0', 1, 'Turkish Lira', '6155c4f8890d4.png', 9097, '0', 1633010941, NULL, 1, NULL, '', '', '', '', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'fiat', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(92, 'wbtc', 'btc', 'offline', 'Select Type', '', '0', 1, 'Wrapped Bitcoin ', '', 3717, '0', 1660406620, NULL, 1, NULL, '', '', '', '', NULL, NULL, '0', 1, 12, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '0xeb324ef91e6f2aa1cd2e09a6232e3cf0b7080882', NULL),
(94, 'btt', 'btt', 'tron', 'Select Type', '', 'trx', 1, 'BitTorrent', '61c1bc0ddb996.png', 3718, '0', 1640086688, NULL, 1, NULL, '', '0', '0', 'TAFjULxiVgT4qWk6UZwjqwZXTSaGaqnVp4', 'x', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'trc20', '', '', '', '', '', 'https://tronscan.org/#/transaction/', '', '', '', '18', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', NULL),
(95, 'bttold', '', 'tron', 'Select Type', '', 'trx', 1, 'BTTOLD [TRC10]', '', NULL, '0', 1640093452, NULL, 1, NULL, '', '', '', '1002000', 'x', NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', NULL, '', '', '', '', '', '', '', '', '', '6', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', NULL),
(96, 'tht', '', 'tron', 'Select Type', '', 'trx', 1, 'Test Token', '', NULL, '0', 1724398794, NULL, 1, NULL, '', '', '', '1000666', 'x', '', '0', 1, 1, '', '', '0', '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'trc20', '', '', '', '', '', '', '', '', '', '6', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', NULL),
(97, 'tusdt', 'usdt', 'tron', 'Select Type', '', 'trx', 1, 'Test USDT', '', 825, '0', 1641361857, NULL, 1, NULL, '', '', '', 'TNfdSwYEBtEJ5sXteWvRC8Dtc3Zo2Kgru1', 'x', NULL, '0', 1, 1, NULL, NULL, NULL, '1', '0.00000', '0.50000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'trc20', '', '', '', '', '', 'https://shasta.tronscan.org/#/transaction/', '', '', '', '6', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', NULL),
(98, 'busdt', 'usdt', 'esmart', 'light', 'https://bsc-dataseed.binance.org/', 'bnb', 1, 'USDT [Bep20]', '', NULL, '100', 1731166651, NULL, 1, NULL, NULL, '0.0.0.0', '9150', '0x55d398326f99059ff775485246999027b3197955', 'U3dDU05jd3BJVUp0Yll3bDJyZ3A4QT09', '', '0', 1, 1, '', '', '0', '1', '0.00000', '0.00200000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', 'https://bscscan.com/tx/', '', '', '', '18', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(99, 'ron', '', 'esmart', 'full', 'abc', '0', 1, 'ronin', '', NULL, '0', 1653723336, NULL, NULL, NULL, NULL, '', '', '', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(100, 'arbiusdt', 'usdt', 'blockgum', 'Select Type', '', 'bnb', 1, 'USDT [GOR]', '', NULL, '0', 1676815073, NULL, 1, 0, NULL, '', '', '0x6b8f83530993ff33b51e7cef9999c911e15bd919', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', '', '', '', '', '18', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(101, 'dot', '', 'substrate', 'Select Type', '', '0', 1, 'Westend Polkadot', '632d8e0649e37.png', NULL, '100', 1701937980, NULL, 1, 0, NULL, '127.0.0.1', '22547', '', 'xx', '', '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.01000000', '0', '0.01000000', '10000.00000000', 0, '10.00000000', 'substrate', '', '', '', '', '', 'https://westend.subscan.io/extrinsic/', '', '', '', '12', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(102, 'xud', '', 'blockgum', 'Select Type', '', 'bnb', 1, 'XUD', '', NULL, '0', 1676983894, NULL, 1, 0, NULL, '', '', '0x20880f0d6fce640805f50a45cc00568b7b7a499b', NULL, NULL, '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', 'bep20', '', '', '', '', '', '', '', '', '', '6', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(103, 'rub', '', 'rmb', 'Select Type', '', '0', 1, 'RUB', '', NULL, '0', 1700035368, NULL, 1, 0, NULL, '', '', '', NULL, '', '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2', NULL, NULL, NULL, NULL, '', NULL),
(104, 'gnss', '', 'offline', 'Select Type', '', '0', 1, 'GNSS', '655b44c5df30f.png', NULL, '0', 1706516510, NULL, 1, 0, NULL, '', '', '', NULL, '', '0', 1, 1, NULL, NULL, NULL, '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '101.00000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 's', NULL, NULL, NULL, NULL, '', NULL),
(105, 'rota', '', 'rgb', 'Select Type', '', '0', 1, 'rota', '', NULL, '0', 1708935025, NULL, 1, 0, NULL, '', '', '', NULL, '', '0', 1, 1, NULL, NULL, '0', '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(106, 'tlnt', '', 'rgb', 'Select Type', '', '0', 1, 'tlnt', '', NULL, '0', 1708935054, NULL, 1, 0, NULL, '', '', '', NULL, '', '0', 1, 1, NULL, NULL, '0', '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '10.00000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(108, 'zrt', '', 'rgb', 'Select Type', '', '0', 1, 'zrt', '', NULL, '0', 1718605219, NULL, NULL, 0, NULL, '', '', '', NULL, '', '0', 1, 1, '', '', '0', '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '0.00000010', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL),
(109, 'zb', '', 'rgb', 'Select Type', '', '0', 1, 'zb', '', NULL, '0', 1722528679, NULL, 1, 0, NULL, '', '', '', NULL, '', '0', 1, 1, '', '', '0', '0', '0.00000', '0.00000000', '0', '0.01000000', '10000.00000000', 1, '0.00000010', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_coinmarketcap`
--

DROP TABLE IF EXISTS `codono_coinmarketcap`;
CREATE TABLE IF NOT EXISTS `codono_coinmarketcap` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(50) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `symbol` varchar(50) DEFAULT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `price_usd` decimal(20,8) DEFAULT NULL,
  `price_btc` decimal(25,8) DEFAULT NULL,
  `24h_volume_usd` decimal(25,8) DEFAULT NULL,
  `market_cap_usd` decimal(30,8) DEFAULT NULL,
  `available_supply` bigint(20) DEFAULT NULL,
  `total_supply` bigint(20) DEFAULT NULL,
  `max_supply` bigint(20) DEFAULT NULL,
  `percent_change_1h` decimal(5,2) DEFAULT NULL,
  `percent_change_24h` decimal(5,2) DEFAULT NULL,
  `percent_change_7d` decimal(5,2) DEFAULT NULL,
  `last_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`seq`),
  KEY `idx_symbol` (`symbol`)
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_coinmarketcap`
--

INSERT INTO `codono_coinmarketcap` (`seq`, `id`, `name`, `symbol`, `rank`, `price_usd`, `price_btc`, `24h_volume_usd`, `market_cap_usd`, `available_supply`, `total_supply`, `max_supply`, `percent_change_1h`, `percent_change_24h`, `percent_change_7d`, `last_updated`) VALUES
(1, 'bitcoin', 'Bitcoin', 'BTC', '1', '42346.14818768', '1.00000000', '9126075157.54252815', '830106247325.00000000', 21000000, 19602475, 21000000, NULL, '-0.20', NULL, 1706530607260),
(2, 'ethereum', 'Ethereum', 'ETH', '2', '2270.67750292', '18.64912482', '2671989260.86132812', '272907629133.00000000', 0, 120177065, 0, NULL, '-0.52', NULL, 1706530607260),
(3, 'tether', 'Tether', 'USDT', '3', '1.00100886', '42303.46989189', '8164021998.69309998', '95153925112.00000000', 0, 95060023618, 0, NULL, '-0.07', NULL, 1706530607260),
(4, 'binance-coin', 'BNB', 'BNB', '4', '308.93848237', '137.06983948', '202903055.50440487', '51532799261.00000000', 166801148, 166801148, 166801148, NULL, '0.78', NULL, 1706530607260),
(5, 'solana', 'Solana', 'SOL', '5', '97.72779434', '433.31595124', '634275475.68319237', '42295704295.00000000', 0, 432790943, 0, NULL, '-0.11', NULL, 1706530607260),
(6, 'usd-coin', 'USDC', 'USDC', '6', '1.00145661', '42285.41863911', '630231859.14437246', '25691527889.00000000', 0, 25654159684, 0, NULL, '-0.05', NULL, 1706530607260),
(7, 'xrp', 'XRP', 'XRP', '7', '0.52727346', '80313.18741201', '304074991.75984377', '23940339254.00000000', 100000000000, 45404028640, 100000000000, NULL, '-0.63', NULL, 1706530607260),
(8, 'cardano', 'Cardano', 'ADA', '8', '0.49046579', '86340.39943691', '116442692.84725145', '17362775677.00000000', 45000000000, 35400584612, 45000000000, NULL, '-1.27', NULL, 1706530607260),
(9, 'avalanche', 'Avalanche', 'AVAX', '9', '35.07253767', '1207.41226562', '224334934.70162562', '12863332792.00000000', 720000000, 366763675, 720000000, NULL, '-2.81', NULL, 1706530607260),
(10, 'dogecoin', 'Dogecoin', 'DOGE', '10', '0.08099459', '522826.90111582', '144538246.02795124', '11554260377.00000000', 0, 142715686384, 0, NULL, '1.15', NULL, 1706530607260),
(11, 'tron', 'TRON', 'TRX', '11', '0.11328123', '373821.97598205', '77133002.11357693', '9992022800.00000000', 0, 88205460457, 0, NULL, '0.85', NULL, 1706530607260),
(12, 'polkadot', 'Polkadot', 'DOT', '12', '6.85361468', '6178.78508276', '111095812.67999603', '9075583525.00000000', 0, 1324203934, 0, NULL, '1.99', NULL, 1706530607260),
(13, 'chainlink', 'Chainlink', 'LINK', '13', '14.60850721', '2898.79120126', '199596802.83110410', '8299092515.00000000', 1000000000, 568099970, 1000000000, NULL, '0.60', NULL, 1706530607260),
(14, 'polygon', 'Polygon', 'MATIC', '14', '0.78870284', '53691.97342340', '107677593.93904860', '7587538605.00000000', 10000000000, 9620275440, 10000000000, NULL, '-0.96', NULL, 1706530607260),
(15, 'wrapped-bitcoin', 'Wrapped Bitcoin', 'WBTC', '15', '42265.46538871', '1.00192940', '41459234.79430101', '6691415884.00000000', 0, 158319, 0, NULL, '-0.22', NULL, 1706530607260),
(16, 'internet-computer', 'Internet Computer', 'ICP', '16', '12.38309190', '3419.74464218', '53919196.98750869', '5644157830.00000000', 0, 455795522, 0, NULL, '-1.75', NULL, 1706530607260),
(17, 'multi-collateral-dai', 'Multi Collateral DAI', 'DAI', '17', '0.99913612', '42382.76185743', '75019966.09870639', '5343346831.00000000', 0, 5347888596, 0, NULL, '-0.10', NULL, 1706530607260),
(18, 'shiba-inu', 'Shiba Inu', 'SHIB', '18', '0.00000905', '4679549133.14092827', '26942729.15723484', '5332712828.00000000', 0, 589290493330736, 0, NULL, '-1.07', NULL, 1706530607260),
(19, 'litecoin', 'Litecoin', 'LTC', '19', '67.77217827', '624.83085638', '111373905.57386629', '5022544209.00000000', 84000000, 74109469, 84000000, NULL, '-0.26', NULL, 1706530607260),
(20, 'bitcoin-cash', 'Bitcoin Cash', 'BCH', '20', '236.98865733', '178.68428246', '56543768.71604116', '4650682958.00000000', 21000000, 19618044, 21000000, NULL, '-2.31', NULL, 1706530607260),
(21, 'unus-sed-leo', 'UNUS SED LEO', 'LEO', '21', '4.02183951', '10529.26453513', '623699.62081008', '3731869249.00000000', 0, 927901085, 0, NULL, '-0.49', NULL, 1706530607260),
(22, 'uniswap', 'Uniswap', 'UNI', '22', '5.95334731', '7113.14323500', '27513936.65227924', '3561215063.00000000', 1000000000, 598187016, 1000000000, NULL, '-0.94', NULL, 1706530607260),
(23, 'ethereum-classic', 'Ethereum Classic', 'ETC', '23', '23.59394026', '1794.82577750', '63729647.19223472', '3423754147.00000000', 210700000, 145111588, 210700000, NULL, '-1.37', NULL, 1706530607260),
(24, 'stellar', 'Stellar', 'XLM', '24', '0.11518425', '367645.85741642', '48800858.29548427', '3267016102.00000000', 50001806812, 28363392705, 50001806812, NULL, '-0.05', NULL, 1706530607260),
(25, 'okb', 'OKB', 'OKB', '25', '52.04879431', '813.60217332', '101829.97351185', '3122927658.00000000', 0, 60000000, 0, NULL, '-0.76', NULL, 1706530607260),
(26, 'monero', 'Monero', 'XMR', '26', '164.24557820', '257.82741082', '36976003.14295150', '3019877449.00000000', 0, 18386355, 0, NULL, '2.43', NULL, 1706530607260),
(27, 'near-protocol', 'NEAR Protocol', 'NEAR', '27', '2.96212076', '14296.18020167', '53589972.67913418', '2975943411.00000000', 0, 1004666471, 0, NULL, '-0.86', NULL, 1706530607260),
(28, 'lido-dao', 'Lido DAO', 'LDO', '28', '3.09960885', '13662.05033501', '3999489.09419725', '2759240980.00000000', 1000000000, 890190057, 1000000000, NULL, '-1.20', NULL, 1706530607260),
(29, 'injective-protocol', 'Injective', 'INJ', '29', '37.16882146', '1139.31543977', '75295566.00819778', '2713530414.00000000', 100000000, 73005554, 100000000, NULL, '-1.78', NULL, 1706530607260),
(30, 'filecoin', 'Filecoin', 'FIL', '30', '5.24063787', '8080.50721998', '67758398.17222552', '2605613392.00000000', 0, 497193940, 0, NULL, '-0.18', NULL, 1706530607260),
(31, 'cosmos', 'Cosmos', 'ATOM', '31', '9.58344983', '4418.76494608', '44960235.14368226', '2381038787.00000000', 0, 248453201, 0, NULL, '0.25', NULL, 1706530607260),
(32, 'bitcoin-bep2', 'Bitcoin BEP2', 'BTCB', '32', '42795.04944299', '0.98953063', '2385839.84061520', '2308369344.00000000', 0, 53940, 0, NULL, '0.00', NULL, 1706530607260),
(33, 'stacks', 'Stacks', 'STX', '33', '1.47304542', '28747.93371196', '24015757.99923833', '2114611797.00000000', 1818000000, 1435537401, 1818000000, NULL, '-3.65', NULL, 1706530607260),
(34, 'crypto-com-coin', 'Crypto.com Coin', 'CRO', '34', '0.08199002', '516479.30237813', '2212052.81882346', '2071402356.00000000', 30263013692, 25263013692, 30263013692, NULL, '0.70', NULL, 1706530607260),
(35, 'vechain', 'VeChain', 'VET', '35', '0.02831600', '1495515.22611106', '22331900.84570318', '2058984406.00000000', 0, 72714516834, 0, NULL, '-1.19', NULL, 1706530607260),
(36, 'trueusd', 'TrueUSD', 'TUSD', '36', '0.99038184', '42758.26789863', '28575614.42779684', '1891119799.00000000', 0, 1909485530, 0, NULL, '-0.01', NULL, 1706530607260),
(37, 'maker', 'Maker', 'MKR', '37', '1946.89914974', '21.75100450', '26543833.02005091', '1795308396.00000000', 1005577, 922137, 1005577, NULL, '-2.74', NULL, 1706530607260),
(38, 'render-token', 'Render Token', 'RNDR', '38', '4.16151221', '10175.87118893', '7530211.61307922', '1538153207.00000000', 536870912, 369614008, 536870912, NULL, '-0.15', NULL, 1706530607260),
(39, 'the-graph', 'The Graph', 'GRT', '39', '0.15941647', '265637.63134179', '12077879.48960970', '1496829410.00000000', 0, 9389427933, 0, NULL, '-0.15', NULL, 1706530607260),
(40, 'thorchain', 'THORChain', 'RUNE', '40', '4.29660011', '9855.73410222', '30154978.83580200', '1460191482.00000000', 0, 339354805, 0, NULL, '-1.72', NULL, 1706530607260),
(41, 'bitcoin-sv', 'Bitcoin SV', 'BSV', '41', '71.93273234', '588.70295607', '28164369.19465901', '1410673713.00000000', 0, 19611013, 0, NULL, '-1.35', NULL, 1706530607260),
(42, 'aave', 'Aave', 'AAVE', '42', '91.86426740', '460.97371009', '21164979.64237725', '1351220802.00000000', 16000000, 14708883, 16000000, NULL, '-0.63', NULL, 1706530607260),
(43, 'algorand', 'Algorand', 'ALGO', '43', '0.16623446', '254742.68768528', '26714010.39084995', '1337066423.00000000', 10000000000, 8043256817, 10000000000, NULL, '0.07', NULL, 1706530607260),
(44, 'elrond-egld', 'MultiversX', 'EGLD', '44', '54.43139775', '777.98869620', '18978518.55723361', '1316399361.00000000', 31415926, 24184559, 31415926, NULL, '1.68', NULL, 1706530607260),
(45, 'helium', 'Helium', 'HNT', '45', '8.01149974', '5285.77838621', '1335541.34636151', '1283377481.00000000', 223000000, 160191914, 223000000, NULL, '1.45', NULL, 1706530607260),
(46, 'quant', 'Quant', 'QNT', '46', '106.20122428', '398.74316376', '7236695.30178712', '1282139556.00000000', 14881364, 12072738, 14881364, NULL, '-1.95', NULL, 1706530607260),
(47, 'mina', 'Mina', 'MINA', '47', '1.10629221', '38278.32434234', '10091942.94518932', '1147022544.00000000', 0, 1036816973, 0, NULL, '0.84', NULL, 1706530607260),
(48, 'flow', 'Flow', 'FLOW', '48', '0.75760405', '55895.96825876', '18808918.65452603', '1124039449.00000000', 0, 1483676655, 0, NULL, '0.19', NULL, 1706530607260),
(49, 'hedera-hashgraph', 'Hedera Hashgraph', 'HBAR', '49', '0.07338220', '577074.75412403', '11436352.70659968', '1088460196.00000000', 50000000000, 14832756028, 50000000000, NULL, '-1.73', NULL, 1706530607260),
(50, 'fantom', 'Fantom', 'FTM', '50', '0.38460809', '110104.32058015', '42307879.91042398', '1078300632.00000000', 3175000000, 2803634836, 3175000000, NULL, '-0.12', NULL, 1706530607260),
(51, 'theta', 'THETA', 'THETA', '51', '1.01687716', '41644.17673886', '17826004.60729096', '1016877160.00000000', 1000000000, 1000000000, 1000000000, NULL, '1.16', NULL, 1706530607260),
(52, 'axie-infinity', 'Axie Infinity', 'AXS', '52', '7.33595306', '5772.53041316', '32224414.85498961', '995547210.00000000', 270000000, 135707958, 270000000, NULL, '-0.70', NULL, 1706530607260),
(53, 'kucoin-token', 'KuCoin Token', 'KCS', '53', '10.05029180', '4213.51071486', '1081070.43233675', '971270695.00000000', 170118638, 96641044, 170118638, NULL, '0.58', NULL, 1706530607260),
(54, 'the-sandbox', 'The Sandbox', 'SAND', '54', '0.45552496', '92963.09949190', '17330690.98938692', '965135276.00000000', 3000000000, 2118731926, 3000000000, NULL, '-0.96', NULL, 1706530607260),
(55, 'chiliz', 'Chiliz', 'CHZ', '55', '0.10826929', '391126.71010477', '46207987.05275850', '962329131.00000000', 8888888888, 8888292417, 8888888888, NULL, '1.38', NULL, 1706530607260),
(56, 'tezos', 'Tezos', 'XTZ', '56', '0.99361905', '42618.96158309', '23021267.98564052', '961543093.00000000', 0, 967718052, 0, NULL, '0.36', NULL, 1706530607260),
(57, 'ftx-token', 'FTX Token', 'FTT', '57', '2.73921232', '15459.55813017', '9203237.43075209', '900913522.00000000', 352170015, 328895104, 352170015, NULL, '-1.72', NULL, 1706530607260),
(58, 'conflux-network', 'Conflux', 'CFX', '58', '0.24594826', '172178.54030358', '84655481.47675544', '897692733.00000000', 0, 3649925143, 0, NULL, '10.12', NULL, 1706530607260),
(59, 'wemix', 'WEMIX', 'WEMIX', '59', '2.42008854', '17498.12512467', '5566383.58256236', '857565848.00000000', 0, 354353088, 0, NULL, '-0.25', NULL, 1706530607260),
(60, 'decentraland', 'Decentraland', 'MANA', '60', '0.44807363', '94509.04856445', '10824339.91108532', '848246109.00000000', 0, 1893095371, 0, NULL, '-1.06', NULL, 1706530607260),
(61, 'iota', 'IOTA', 'IOTA', '61', '0.26111005', '162180.70866250', '11885791.35591395', '808276926.00000000', 0, 3095541289, 0, NULL, '3.33', NULL, 1706530607260),
(62, 'eos', 'EOS', 'EOS', '62', '0.71061948', '59590.46873150', '55297677.12855295', '792207161.00000000', 0, 1114700914, 0, NULL, '-1.49', NULL, 1706530607260),
(63, 'kava', 'Kava', 'KAVA', '63', '0.72984519', '58021.91024801', '14432448.11808510', '790321462.00000000', 0, 1082861779, 0, NULL, '-0.81', NULL, 1706530607260),
(64, 'neo', 'Neo', 'NEO', '64', '11.06129326', '3828.39611711', '9709780.00487334', '780250696.00000000', 100000000, 70538831, 100000000, NULL, '0.26', NULL, 1706530607260),
(65, 'synthetix-network-token', 'Synthetix', 'SNX', '65', '3.22032053', '13149.93701852', '10996521.69521587', '752642795.00000000', 212424133, 233716733, 212424133, NULL, '-0.65', NULL, 1706530607260),
(66, 'frax-share', 'Frax Share', 'FXS', '66', '9.79869467', '4321.69932981', '699112.94103096', '747098187.00000000', 0, 76244664, 0, NULL, '-2.67', NULL, 1706530607260),
(67, 'oasis-network', 'Oasis Network', 'ROSE', '67', '0.10695667', '395926.79741630', '7608430.51820948', '718064292.00000000', 10000000000, 6713599876, 10000000000, NULL, '-1.01', NULL, 1706530607260),
(68, 'klaytn', 'Klaytn', 'KLAY', '68', '0.20577562', '205792.17874775', '3069045.31890071', '716753844.00000000', 0, 3483181640, 0, NULL, '0.51', NULL, 1706530607260),
(69, 'wootrade', 'WOO', 'WOO', '69', '0.35648634', '118789.99920410', '15870078.53185094', '645886607.00000000', 0, 1811813055, 0, NULL, '0.34', NULL, 1706530607260),
(70, 'akash-network', 'Akash Network', 'AKT', '70', '2.84786541', '14869.73787279', '570501.66046582', '642379022.00000000', 388539008, 225565092, 388539008, NULL, '-5.35', NULL, 1706530607260),
(71, 'pancakeswap', 'PancakeSwap', 'CAKE', '71', '2.59645046', '16309.57836423', '8757513.50729555', '639038430.00000000', 450000000, 246120017, 450000000, NULL, '-0.05', NULL, 1706530607260),
(72, 'gala', 'Gala', 'GALA', '72', '0.02297097', '1843501.35023548', '19406899.52602346', '636867009.00000000', 50000000000, 27724864874, 50000000000, NULL, '-1.32', NULL, 1706530607260),
(73, 'ecash', 'eCash', 'XEC', '73', '0.00003089', '1371030534.60990000', '1936815.21007978', '605731937.00000000', 21000000000000, 19611229673092, 21000000000000, NULL, '-1.78', NULL, 1706530607260),
(74, 'arweave', 'Arweave', 'AR', '74', '8.83226093', '4794.58345947', '12758096.24311436', '578108441.00000000', 66000000, 65454185, 66000000, NULL, '-0.39', NULL, 1706530607260),
(75, 'rocket-pool', 'Rocket Pool', 'RPL', '75', '28.67248264', '1476.92171266', '319428.90745229', '575353541.00000000', 0, 20066401, 0, NULL, '-2.07', NULL, 1706530607260),
(76, 'xinfin-network', 'XinFin Network', 'XDC', '76', '0.04540429', '932665.36079862', '3657878.02785071', '558168312.00000000', 0, 12293293519, 0, NULL, '0.34', NULL, 1706530607260),
(77, 'gnosis-gno', 'Gnosis', 'GNO', '77', '213.77391745', '198.09251134', '4589925.01679905', '553586371.00000000', 3000000, 2589588, 3000000, NULL, '0.86', NULL, 1706530607260),
(78, 'pendle', 'Pendle', 'PENDLE', '78', '2.26371651', '18706.85308786', '612744.38356402', '539184648.00000000', 258446029, 238185588, 258446029, NULL, '-1.79', NULL, 1706530607260),
(79, 'fetch', 'Fetch.ai', 'FET', '79', '0.60852639', '69589.44268708', '15898171.25784482', '506098590.00000000', 0, 831678955, 0, NULL, '-2.11', NULL, 1706530607260),
(80, 'curve-dao-token', 'Curve DAO Token', 'CRV', '80', '0.46989970', '90119.25752346', '5511363.68055120', '505427907.00000000', 3303030299, 1075608063, 3303030299, NULL, '-0.12', NULL, 1706530607260),
(81, 'dydx', 'dYdX', 'DYDX', '81', '2.73400034', '15489.02956805', '19904324.77970320', '502415003.00000000', 1000000000, 183765523, 1000000000, NULL, '-0.97', NULL, 1706530607260),
(82, 'nexo', 'Nexo', 'NEXO', '82', '0.85405153', '49583.67342854', '686001.33119268', '478268866.00000000', 1000000000, 560000011, 1000000000, NULL, '-0.89', NULL, 1706530607260),
(83, 'gatetoken', 'GateToken', 'GT', '83', '4.81151721', '8801.17649415', '686634.00238731', '477062331.00000000', 0, 99150083, 0, NULL, '0.68', NULL, 1706530607260),
(84, 'huobi-token', 'Huobi Token', 'HT', '84', '2.86732617', '14768.81584950', '1018490.21609403', '465177346.00000000', 500000000, 162233844, 500000000, NULL, '15.07', NULL, 1706530607260),
(85, 'siacoin', 'Siacoin', 'SC', '85', '0.00823294', '5143609.36275805', '5538428.39486697', '462730195.00000000', 0, 56204753058, 0, NULL, '-4.19', NULL, 1706530607260),
(86, 'trust-wallet-token', 'Trust Wallet Token', 'TWT', '86', '1.10701147', '38253.45375834', '3015908.57874838', '461236219.00000000', 1000000000, 416649900, 1000000000, NULL, '-1.97', NULL, 1706530607260),
(87, '1inch', '1inch Network', '1INCH', '87', '0.40496473', '104569.63063739', '16185897.94468946', '459383267.00000000', 0, 1134378462, 0, NULL, '1.80', NULL, 1706530607260),
(88, 'compound', 'Compound', 'COMP', '88', '54.40278672', '778.39784904', '12371131.02575982', '438973031.00000000', 10000000, 8068944, 10000000, NULL, '-0.29', NULL, 1706530607260),
(89, 'casper', 'Casper', 'CSPR', '89', '0.03553955', '1191546.08960818', '6361826.96521125', '416585189.00000000', 0, 11721734965, 0, NULL, '-3.65', NULL, 1706530607260),
(90, 'aelf', 'aelf', 'ELF', '90', '0.57935249', '73093.69094730', '2736832.55989905', '414016919.00000000', 1000000000, 714620068, 1000000000, NULL, '-1.64', NULL, 1706530607260),
(91, 'fei-protocol', 'Fei Protocol', 'FEI', '91', '0.96265873', '43989.64104746', '962048.52125021', '409126283.00000000', 0, 424996178, 0, NULL, '-0.19', NULL, 1706530607260),
(92, 'enjin-coin', 'Enjin Coin', 'ENJ', '92', '0.29266474', '144694.61598396', '4659694.56343293', '403831032.00000000', 0, 1379841766, 0, NULL, '0.50', NULL, 1706530607260),
(93, 'iotex', 'IoTeX', 'IOTX', '93', '0.04271398', '991408.82250955', '4123014.86214297', '403278829.00000000', 10000000000, 9441378955, 10000000000, NULL, '0.12', NULL, 1706530607260),
(94, 'uma', 'UMA', 'UMA', '94', '5.21415106', '8121.55452505', '49580587.08530419', '396243130.00000000', 0, 75993796, 0, NULL, '0.29', NULL, 1706530607260),
(95, 'skale-network', 'SKALE', 'SKL', '95', '0.07606055', '556753.96442194', '14787541.58737759', '390512175.00000000', 7000000000, 5134227671, 7000000000, NULL, '-0.38', NULL, 1706530607260),
(96, 'gas', 'Gas', 'GAS', '96', '5.70822336', '7418.59760218', '788475.18733530', '377326653.00000000', 0, 66102293, 0, NULL, '-0.57', NULL, 1706530607260),
(97, 'zcash', 'Zcash', 'ZEC', '97', '22.98716595', '1842.16480932', '43857034.21392563', '375377967.00000000', 21000000, 16328269, 21000000, NULL, '-0.51', NULL, 1706530607260),
(98, 'paxos-standard', 'Pax Dollar', 'USDP', '98', '1.00118290', '42296.97916668', '1060356.58426466', '363773333.00000000', 0, 363343534, 0, NULL, '-0.06', NULL, 1706530607260),
(99, 'zilliqa', 'Zilliqa', 'ZIL', '99', '0.02044815', '2070946.25832908', '7175528.00913132', '355229353.00000000', 21000000000, 17372203179, 21000000000, NULL, '-1.40', NULL, 1706530607260),
(100, 'celo', 'Celo', 'CELO', '100', '0.65761803', '64394.54230489', '7751092.74674923', '345092142.00000000', 1000000000, 524760766, 1000000000, NULL, '-3.73', NULL, 1706530607260),
(101, 'sri-lankan-rupee', NULL, 'LKR', NULL, '0.00313864', '13491883.49184847', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(102, 'jersey-pound', NULL, 'JEP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(103, 'lebanese-pound', NULL, 'LBP', NULL, '0.00006635', '638264798.56099117', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(104, 'guinean-franc', NULL, 'GNF', NULL, '0.00011600', '365040875.62182313', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(105, 'palladium-ounce', NULL, 'XPD', NULL, '952.48073608', '44.45879752', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(106, 'saudi-riyal', NULL, 'SAR', NULL, '0.26665472', '158805.16985669', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(107, 'honduran-lempira', NULL, 'HNL', NULL, '0.04043553', '1047250.98798487', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(108, 'bhutanese-ngultrum', NULL, 'BTN', NULL, '0.01200806', '3526476.48589235', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(109, 'united-arab-emirates-dirham', NULL, 'AED', NULL, '0.27226442', '155533.16767852', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(110, 'sentinel', NULL, 'DVPN', NULL, '0.00151006', '28042605.94893186', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(111, 'belarusian-ruble', NULL, 'BYN', NULL, '0.30470944', '138972.22431455', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(112, 'burundian-franc', NULL, 'BIF', NULL, '0.00034958', '121133948.17957181', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(113, 'special-drawing-rights', NULL, 'XDR', NULL, '1.32745712', '31900.20035274', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(114, 'nicaraguan-córdoba', NULL, 'NIO', NULL, '0.02722571', '1555373.55712580', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(115, 'ghanaian-cedi', NULL, 'GHS', NULL, '0.08060056', '525382.77444993', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(116, 'macanese-pataca', NULL, 'MOP', NULL, '0.12389036', '341803.41405752', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(117, 'polish-zloty', NULL, 'PLN', NULL, '0.24764085', '170998.23507355', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(118, 'mexican-peso', NULL, 'MXN', NULL, '0.05829150', '726454.94754333', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(119, 'danish-krone', NULL, 'DKK', NULL, '0.14519617', '291647.83156722', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(120, 'zambian-kwacha', NULL, 'ZMW', NULL, '0.03706337', '1142533.75959893', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(121, 'kazakhstani-tenge', NULL, 'KZT', NULL, '0.00221333', '19132306.37334330', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(122, 'south-korean-won', NULL, 'KRW', NULL, '0.00074806', '56608234.68683781', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(123, 'brazilian-real', NULL, 'BRL', NULL, '0.20336363', '208228.71448327', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(124, 'aruban-florin', NULL, 'AWG', NULL, '0.55555556', '76223.06673782', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(125, 'jamaican-dollar', NULL, 'JMD', NULL, '0.00640217', '6614340.63093248', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(126, 'cfa-franc-beac', NULL, 'XAF', NULL, '0.00164994', '25665216.58934929', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(127, 'são-tomé-and-príncipe-dobra', NULL, 'STN', NULL, '0.04426122', '956732.41211882', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(128, 'uruguayan-peso', NULL, 'UYU', NULL, '0.02566576', '1649908.36872442', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(129, 'mongolian-tugrik', NULL, 'MNT', NULL, '0.00028986', '146094211.24747446', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(130, 'british-pound-sterling', NULL, 'GBP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(131, 'moldovan-leu', NULL, 'MDL', NULL, '0.05635472', '751421.47428412', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(132, 'tunisian-dinar', NULL, 'TND', NULL, '0.32123354', '131823.55930824', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(133, 'bangladeshi-taka', NULL, 'BDT', NULL, '0.00908588', '4660653.36018487', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(134, 'pakistani-rupee', NULL, 'PKR', NULL, '0.00356715', '11871159.49560891', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(135, 'bulgarian-lev', NULL, 'BGN', NULL, '0.55347235', '76509.96189179', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(136, 'mauritian-rupee', NULL, 'MUR', NULL, '0.02180074', '1942417.81736881', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(137, 'peruvian-nuevo-sol', NULL, 'PEN', NULL, '0.26470734', '159973.45773904', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(138, 'australian-dollar', NULL, 'AUD', NULL, '0.65994012', '64166.65254116', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(139, 'falkland-islands-pound', NULL, 'FKP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(140, 'qtum', NULL, 'QTUM', NULL, '2.91597883', '14522.10410610', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(141, 'platinum-ounce', NULL, 'XPT', NULL, '909.51259220', '46.55916647', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(142, 'netherlands-antillean-guilder', NULL, 'ANG', NULL, '0.55329351', '76534.69204233', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(143, 'guatemalan-quetzal', NULL, 'GTQ', NULL, '0.12751646', '332083.78735630', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(144, 'ethiopian-birr', NULL, 'ETB', NULL, '0.01762246', '2402964.19259785', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(145, 'manx-pound', NULL, 'IMP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(146, 'cape-verdean-escudo', NULL, 'CVE', NULL, '0.00983449', '4305881.52293717', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(147, 'ukrainian-hryvnia', NULL, 'UAH', NULL, '0.02635602', '1606697.42749845', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(148, 'barbadian-dollar', NULL, 'BBD', NULL, '0.50000000', '84692.29637536', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(149, 'seychellois-rupee', NULL, 'SCR', NULL, '0.07392562', '572821.00461103', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(150, 'swedish-krona', NULL, 'SEK', NULL, '0.09542058', '443784.24531502', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(151, 'samoan-tala', NULL, 'WST', NULL, '0.35714286', '118569.21492550', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(152, 'hong-kong-dollar', NULL, 'HKD', NULL, '0.12800590', '330814.03810289', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(153, 'kuwaiti-dinar', NULL, 'KWD', NULL, '3.25046807', '13027.70779764', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(154, 'salvadoran-colón', NULL, 'SVC', NULL, '0.11396743', '371563.60946545', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(155, 'chinese-yuan-renminbi', NULL, 'CNY', NULL, '0.13924668', '304108.86320981', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(156, 'turkish-lira', NULL, 'TRY', NULL, '0.03294600', '1285319.93209615', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(157, 'cuban-convertible-peso', NULL, 'CUC', NULL, '1.00000000', '42346.14818768', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(158, 'belize-dollar', NULL, 'BZD', NULL, '0.49470811', '85598.24986968', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(159, 'djiboutian-franc', NULL, 'DJF', NULL, '0.00560066', '7560918.30967738', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(160, 'cfa-franc-bceao', NULL, 'XOF', NULL, '0.00164994', '25665216.58934929', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(161, 'dominican-peso', NULL, 'DOP', NULL, '0.01693581', '2500390.90734850', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(162, 'guyanaese-dollar', NULL, 'GYD', NULL, '0.00476638', '8884334.82695220', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(163, 'cfp-franc', NULL, 'XPF', NULL, '0.00906960', '4669017.95249027', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(164, 'nepalese-rupee', NULL, 'NPR', NULL, '0.00750502', '5642378.89242555', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(165, 'iranian-rial', NULL, 'IRR', NULL, '0.00002376', '1782031781.11137605', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(166, 'dash', NULL, 'DASH', NULL, '28.17309472', '1503.07052214', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(167, 'surinamese-dollar', NULL, 'SRD', NULL, '0.02730674', '1550758.29278097', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(168, 'uzbekistan-som', NULL, 'UZS', NULL, '0.00008077', '524251112.80951744', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(169, 'colombian-peso', NULL, 'COP', NULL, '0.00025288', '167455191.12297615', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(170, 'indonesian-rupiah', NULL, 'IDR', NULL, '0.00006317', '670357640.22319865', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(171, 'icelandic-króna', NULL, 'ISK', NULL, '0.00728810', '5810314.99283135', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(172, 'afghan-afghani', NULL, 'AFN', NULL, '0.01358387', '3117385.67755682', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(173, 'georgian-lari', NULL, 'GEL', NULL, '0.37243948', '113699.40788392', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(174, 'paraguayan-guarani', NULL, 'PYG', NULL, '0.00013652', '310181444.15934741', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(175, 'vietnamese-dong', NULL, 'VND', NULL, '0.00004075', '1039169531.00305200', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(176, 'iraqi-dinar', NULL, 'IQD', NULL, '0.00076120', '55631015.88576937', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(177, 'united-states-dollar', NULL, 'USD', NULL, '1.00000000', '42346.14818768', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(178, 'kenyan-shilling', NULL, 'KES', NULL, '0.00617284', '6860076.00640395', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(179, 'serbian-dinar', NULL, 'RSD', NULL, '0.00923327', '4586257.23331833', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(180, 'trinidad-and-tobago-dollar', NULL, 'TTD', NULL, '0.14691776', '288230.28567773', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(181, 'euro', NULL, 'EUR', NULL, '1.08229208', '39126.35881023', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(182, 'ugandan-shilling', NULL, 'UGX', NULL, '0.00026155', '161903835.02202216', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(183, 'omani-rial', NULL, 'OMR', NULL, '2.59765224', '16301.70024477', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(184, 'namibian-dollar', NULL, 'NAD', NULL, '0.05312952', '797036.19461196', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(185, 'laotian-kip', NULL, 'LAK', NULL, '0.00004812', '880013643.44451380', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(186, 'gibraltar-pound', NULL, 'GIP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(187, 'waves', NULL, 'WAVES', NULL, '2.20787052', '19179.63388491', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(188, 'armenian-dram', NULL, 'AMD', NULL, '0.00245840', '17225083.54073295', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(189, 'tajikistani-somoni', NULL, 'TJS', NULL, '0.09148584', '462871.05172609', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(190, 'bahraini-dinar', NULL, 'BHD', NULL, '2.65280840', '15962.76167468', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(191, 'haitian-gourde', NULL, 'HTG', NULL, '0.00758110', '5585755.95210716', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(192, 'bosnia-herzegovina-convertible-mark', NULL, 'BAM', NULL, '0.55445343', '76374.58125604', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(193, 'romanian-leu', NULL, 'RON', NULL, '0.21742912', '194758.40474477', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(194, 'north-korean-won', NULL, 'KPW', NULL, '0.00111111', '38111533.36891095', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(195, 'panamanian-balboa', NULL, 'PAB', NULL, '1.00000000', '42346.14818768', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(196, 'sudanese-pound', NULL, 'SDG', NULL, '0.00166389', '25450035.06079485', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(197, 'libyan-dinar', NULL, 'LYD', NULL, '0.20706510', '204506.44571143', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(198, 'lesotho-loti', NULL, 'LSL', NULL, '0.05312952', '797036.19461196', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(199, 'czech-republic-koruna', NULL, 'CZK', NULL, '0.04369567', '969115.48450260', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(200, 'azerbaijani-manat', NULL, 'AZN', NULL, '0.58823529', '71988.45191905', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(201, 'maldivian-rufiyaa', NULL, 'MVR', NULL, '0.06493506', '652130.68209025', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(202, 'vanuatu-vatu', NULL, 'VUV', NULL, '0.00842304', '5027419.40513754', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(203, 'venezuelan-bolívar-soberano', NULL, 'VES', NULL, '0.02772120', '1527572.88737911', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(204, 'new-taiwan-dollar', NULL, 'TWD', NULL, '0.03202562', '1322258.51950641', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(205, 'new-zealand-dollar', NULL, 'NZD', NULL, '0.61130486', '69271.73478207', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(206, 'malaysian-ringgit', NULL, 'MYR', NULL, '0.21123785', '200466.66552047', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(207, 'rwandan-franc', NULL, 'RWF', NULL, '0.00078529', '53924480.72322054', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(208, 'brunei-dollar', NULL, 'BND', NULL, '0.74426913', '56896.28470496', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(209, 'hungarian-forint', NULL, 'HUF', NULL, '0.00278005', '15232127.62811749', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(210, 'cayman-islands-dollar', NULL, 'KYD', NULL, '1.19664461', '35387.40565600', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(211, 'gold-ounce', NULL, 'XAU', NULL, '2028.15073216', '20.87919183', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(212, 'cuban-peso', NULL, 'CUP', NULL, '0.03883495', '1090413.31583272', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(213, 'indian-rupee', NULL, 'INR', NULL, '0.01202568', '3521311.52619789', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(214, 'israeli-new-sheqel', NULL, 'ILS', NULL, '0.27077156', '156390.67717933', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(215, 'myanma-kyat', NULL, 'MMK', NULL, '0.00047486', '89175583.23263054', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(216, 'swiss-franc', NULL, 'CHF', NULL, '1.15883852', '36541.88869404', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(217, 'moroccan-dirham', NULL, 'MAD', NULL, '0.10014961', '422828.87276901', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(218, 'papua-new-guinean-kina', NULL, 'PGK', NULL, '0.26275747', '161160.58965733', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(219, 'botswanan-pula', NULL, 'BWP', NULL, '0.07334460', '577358.73315852', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(220, 'eritrean-nakfa', NULL, 'ERN', NULL, '0.06666667', '635192.22281518', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(221, 'zimbabwean-dollar', NULL, 'ZWL', NULL, '0.00310559', '13635459.71643245', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(222, 'mauritanian-ouguiya', NULL, 'MRU', NULL, '0.02520688', '1679944.02582631', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(223, 'singapore-dollar', NULL, 'SGD', NULL, '0.74527588', '56819.42644600', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(224, 'argentine-peso', NULL, 'ARS', NULL, '0.00121427', '34873846.60365943', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(225, 'thai-baht', NULL, 'THB', NULL, '0.02811358', '1506252.49103572', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(226, 'cambodian-riel', NULL, 'KHR', NULL, '0.00024435', '173304707.25789523', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(227, 'macedonian-denar', NULL, 'MKD', NULL, '0.01759000', '2407398.42715916', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(228, 'yemeni-rial', NULL, 'YER', NULL, '0.00399441', '10601360.99363114', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(229, 'bolivian-boliviano', NULL, 'BOB', NULL, '0.14431728', '293423.95606065', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(230, 'tanzanian-shilling', NULL, 'TZS', NULL, '0.00039139', '108194408.61952469', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(231, 'east-caribbean-dollar', NULL, 'XCD', NULL, '0.37002091', '114442.58278461', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(232, 'fijian-dollar', NULL, 'FJD', NULL, '0.44718719', '94694.45657729', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(233, 'philippine-peso', NULL, 'PHP', NULL, '0.01772484', '2389085.11549089', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(234, 'japanese-yen', NULL, 'JPY', NULL, '0.00676241', '6261989.59593822', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(235, 'congolese-franc', NULL, 'CDF', NULL, '0.00036261', '116780015.12720264', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(236, 'swazi-lilangeni', NULL, 'SZL', NULL, '0.05312041', '797172.84563216', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(237, 'silver-ounce', NULL, 'XAG', NULL, '23.00265106', '1840.92468670', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(238, 'liberian-dollar', NULL, 'LRD', NULL, '0.00527357', '8029889.78985762', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(239, 'persistence', NULL, 'XPRT', NULL, '0.31849000', '132959.11435183', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(240, 'comorian-franc', NULL, 'KMF', NULL, '0.00220604', '19195514.52081975', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(241, 'south-sudanese-pound', NULL, 'SSP', NULL, '0.00767695', '5516009.26292700', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(242, 'syrian-pound', NULL, 'SYP', NULL, '0.00039801', '106395967.70598370', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(243, 'gambian-dalasi', NULL, 'GMD', NULL, '0.01485332', '2850954.42673545', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(244, 'norwegian-krone', NULL, 'NOK', NULL, '0.09585103', '441791.26619671', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(245, 'south-african-rand', NULL, 'ZAR', NULL, '0.05327350', '794882.08839980', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(246, 'são-tomé-and-príncipe-dobra-(pre-2018)', NULL, 'STD', NULL, '0.00004488', '943548404.68813372', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(247, 'mozambican-metical', NULL, 'MZN', NULL, '0.01565558', '2704859.79202648', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(248, 'chilean-unit-of-account-(uf)', NULL, 'CLF', NULL, '29.91951650', '1415.33531088', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(249, 'kyrgystani-som', NULL, 'KGS', NULL, '0.01119570', '3782357.95612342', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(250, 'malagasy-ariary', NULL, 'MGA', NULL, '0.00022045', '192088078.15186536', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(251, 'somali-shilling', NULL, 'SOS', NULL, '0.00174481', '24269796.75665683', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(252, 'jordanian-dinar', NULL, 'JOD', NULL, '1.40984069', '30036.12290952', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(253, 'russian-ruble', NULL, 'RUB', NULL, '0.01114000', '3801270.04232709', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(254, 'chinese-yuan-(offshore)', NULL, 'CNH', NULL, '0.13904222', '304556.03853467', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(255, 'qatari-rial', NULL, 'QAR', NULL, '0.27343786', '154865.70769079', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(256, 'chilean-peso', NULL, 'CLP', NULL, '0.00108432', '39053311.70460334', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(257, 'sierra-leonean-leone', NULL, 'SLL', NULL, '0.00004769', '887977554.42146528', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(258, 'guernsey-pound', NULL, 'GGP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(259, 'nigerian-naira', NULL, 'NGN', NULL, '0.00111049', '38132706.44300468', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(260, 'egyptian-pound', NULL, 'EGP', NULL, '0.03236560', '1308368.94055470', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(261, 'solomon-islands-dollar', NULL, 'SBD', NULL, '0.11858103', '357107.26966640', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(262, 'malawian-kwacha', NULL, 'MWK', NULL, '0.00059240', '71481837.08452402', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(263, 'croatian-kuna', NULL, 'HRK', NULL, '0.14363823', '294811.13118299', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(264, 'saint-helena-pound', NULL, 'SHP', NULL, '1.26950596', '33356.39969662', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(265, 'bermudan-dollar', NULL, 'BMD', NULL, '1.00000000', '42346.14818768', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(266, 'turkmenistani-manat', NULL, 'TMT', NULL, '0.28571429', '148211.51865687', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(267, 'angolan-kwanza', NULL, 'AOA', NULL, '0.00120299', '35200604.09249644', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(268, 'algerian-dinar', NULL, 'DZD', NULL, '0.00742638', '5702126.51267255', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(269, 'costa-rican-colón', NULL, 'CRC', NULL, '0.00195141', '21700236.84628170', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557),
(270, 'canadian-dollar', NULL, 'CAD', NULL, '0.74442980', '56884.00432199', '0.00000000', '0.00000000', 0, 0, 0, NULL, '0.00', NULL, 1706530603557);

-- --------------------------------------------------------

--
-- Table structure for table `codono_coinpay_ipn`
--

DROP TABLE IF EXISTS `codono_coinpay_ipn`;
CREATE TABLE IF NOT EXISTS `codono_coinpay_ipn` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'index',
  `funded` varchar(1) DEFAULT '0' COMMENT 'Check if fund has been added to user account',
  `address` varchar(100) DEFAULT NULL COMMENT 'user exchange address map',
  `dest_tag` varchar(80) DEFAULT NULL COMMENT 'XRP/XMR like ',
  `cid` varchar(100) DEFAULT NULL COMMENT 'coinpay id [withdrawal only]',
  `amount` varchar(50) DEFAULT NULL COMMENT 'coinpay fees',
  `amounti` varchar(50) DEFAULT NULL COMMENT 'coinpay fees',
  `confirms` int(11) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL COMMENT 'COIN NAME in Caps',
  `deposit_id` varchar(50) DEFAULT NULL,
  `fee` varchar(50) DEFAULT NULL COMMENT 'coinpay fees',
  `feei` varchar(50) DEFAULT NULL,
  `fiat_amount` varchar(50) DEFAULT '0.00000000',
  `fiat_amounti` varchar(50) DEFAULT '0',
  `fiat_coin` varchar(3) DEFAULT NULL,
  `fiat_fee` varchar(50) DEFAULT NULL,
  `fiat_feei` varchar(20) DEFAULT NULL,
  `ipn_id` varchar(50) DEFAULT NULL,
  `ipn_mode` varchar(25) DEFAULT NULL,
  `ipn_type` varchar(20) DEFAULT NULL,
  `ipn_version` varchar(50) DEFAULT NULL,
  `merchant` varchar(32) DEFAULT NULL COMMENT 'dont expose it to users',
  `status` int(11) DEFAULT NULL,
  `status_text` varchar(50) DEFAULT NULL,
  `txn_id` varchar(100) DEFAULT NULL,
  `note` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_coin_comment`
--

DROP TABLE IF EXISTS `codono_coin_comment`;
CREATE TABLE IF NOT EXISTS `codono_coin_comment` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `content` varchar(500) DEFAULT NULL,
  `cjz` int(11) UNSIGNED DEFAULT NULL,
  `tzy` int(11) UNSIGNED DEFAULT NULL,
  `xcd` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(10) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_coin_comment`
--

INSERT INTO `codono_coin_comment` (`id`, `userid`, `coinname`, `content`, `cjz`, `tzy`, `xcd`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 38, 'eth', 'Nice mate', NULL, NULL, NULL, NULL, 1612770658, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_coin_json`
--

DROP TABLE IF EXISTS `codono_coin_json`;
CREATE TABLE IF NOT EXISTS `codono_coin_json` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_coin_json`
--

INSERT INTO `codono_coin_json` (`id`, `name`, `data`, `type`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'btc', '[0,0,null,null]', NULL, NULL, 1640131199, NULL, NULL),
(2, 'btc', '[0,0,\"0.00000000\",null]', NULL, NULL, 1640217599, NULL, NULL),
(3, 'btc', '[0,0,null,null]', NULL, NULL, 1640303999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_competition`
--

DROP TABLE IF EXISTS `codono_competition`;
CREATE TABLE IF NOT EXISTS `codono_competition` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `type_id` int(11) UNSIGNED NOT NULL,
  `userid` int(11) NOT NULL COMMENT 'user id',
  `type` enum('1','2') NOT NULL COMMENT 'type',
  `voted_favor` varchar(50) NOT NULL DEFAULT '',
  `voted_against` varchar(50) DEFAULT NULL,
  `votecoin` varchar(50) DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT NULL,
  `addtime` int(11) DEFAULT 0,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_competition`
--

INSERT INTO `codono_competition` (`id`, `type_id`, `userid`, `type`, `voted_favor`, `voted_against`, `votecoin`, `fees`, `addtime`, `status`) VALUES
(7, 7, 11, '1', 'doge', 'xrp', 'usd', '10.00', 1683553117, 1),
(8, 9, 11, '2', 'mog', 'shrim', 'usd', '10.00', 1683553251, 1),
(9, 9, 11, '1', 'shirm', 'mog', 'usd', '10.00', 1683553265, 1),
(10, 9, 11, '2', 'mog', 'shirm', 'usd', '10.00', 1683553839, 1),
(11, 8, 11, '2', 'MEMO', 'DOGE', 'usd', '2.00', 1683555225, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_competition_type`
--

DROP TABLE IF EXISTS `codono_competition_type`;
CREATE TABLE IF NOT EXISTS `codono_competition_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `coin_1` varchar(255) NOT NULL DEFAULT '',
  `coin_2` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `img_1` varchar(255) DEFAULT NULL,
  `img_2` varchar(255) DEFAULT NULL,
  `votes_1` int(10) UNSIGNED DEFAULT 0,
  `votes_2` int(10) UNSIGNED DEFAULT 0,
  `start_date` int(11) DEFAULT 0,
  `end_date` int(11) DEFAULT 0,
  `votecoin` varchar(50) DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_competition_type`
--

INSERT INTO `codono_competition_type` (`id`, `coin_1`, `coin_2`, `status`, `featured`, `img_1`, `img_2`, `votes_1`, `votes_2`, `start_date`, `end_date`, `votecoin`, `fees`) VALUES
(7, 'xrp', 'doge', 1, 0, '.\\Upload\\Competition/6458ecd11a7a2.png', '.\\Upload\\Competition/6458ecdecc348.png', 0, 0, 1683549466, 1683599466, 'usd', '2.50'),
(8, 'DOGE', 'MEMO', 1, 0, '.\\Upload\\Competition\\6458f5637f894.png', NULL, 100, 99, 0, 0, 'usd', '2.00'),
(9, 'shirm', 'mog', 1, 1, '.\\Upload\\Competition\\6458f5c4e80b3.jpg', '.\\Upload\\Competition\\6458f5c8cc07a.png', 2, 39, 1683549466, 1689599466, 'usd', '10.00');

-- --------------------------------------------------------

--
-- Table structure for table `codono_config`
--

DROP TABLE IF EXISTS `codono_config`;
CREATE TABLE IF NOT EXISTS `codono_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `footer_logo` varchar(200) NOT NULL COMMENT ' ',
  `topup_zidong` varchar(200) NOT NULL COMMENT 'name',
  `topup_openid` varchar(200) NOT NULL COMMENT 'name',
  `topup_appkey` varchar(200) NOT NULL COMMENT 'name',
  `login_verify` varchar(200) NOT NULL COMMENT 'Set up',
  `fee_meitian` varchar(200) NOT NULL COMMENT 'Set up',
  `web_name` varchar(200) DEFAULT NULL,
  `web_title` varchar(200) DEFAULT NULL,
  `web_logo` varchar(200) DEFAULT NULL,
  `web_keywords` text DEFAULT NULL,
  `web_description` text DEFAULT NULL,
  `web_close` text DEFAULT NULL,
  `web_close_cause` text DEFAULT NULL,
  `web_icp` text DEFAULT NULL,
  `web_html_footer` text DEFAULT NULL,
  `web_ren` text DEFAULT NULL,
  `web_reg` text DEFAULT NULL,
  `market_mr` text DEFAULT NULL,
  `xnb_mr` text DEFAULT NULL,
  `rmb_mr` text DEFAULT NULL,
  `web_waring` text DEFAULT NULL,
  `issue_warning` text DEFAULT NULL,
  `cellphone_type` text DEFAULT NULL,
  `cellphone_url` text DEFAULT NULL,
  `cellphone_user` text DEFAULT NULL,
  `cellphone_pwd` text DEFAULT NULL,
  `contact_cellphone` text DEFAULT NULL,
  `contact_twitter` text DEFAULT NULL,
  `contact_facebook` text DEFAULT NULL,
  `contact_pinterest` varchar(200) DEFAULT NULL,
  `contact_youtube` varchar(200) DEFAULT NULL,
  `contact_linkedin` varchar(200) DEFAULT NULL,
  `contact_instagram` varchar(200) DEFAULT NULL,
  `contact_qq` text DEFAULT NULL,
  `contact_qqun` text DEFAULT NULL,
  `contact_telegram` text DEFAULT NULL,
  `contact_telegram_img` text DEFAULT NULL,
  `contact_app_img` text DEFAULT NULL,
  `google_play` varchar(255) DEFAULT NULL,
  `apple_store` varchar(255) DEFAULT NULL,
  `contact_email` text DEFAULT NULL,
  `contact_alipay` text DEFAULT NULL,
  `contact_alipay_img` text DEFAULT NULL,
  `contact_bank` text DEFAULT NULL,
  `user_truename` text DEFAULT NULL,
  `user_cellphone` text DEFAULT NULL,
  `user_alipay` text DEFAULT NULL,
  `user_bank` text DEFAULT NULL,
  `user_text_truename` text DEFAULT NULL,
  `user_text_cellphone` text DEFAULT NULL,
  `user_text_alipay` text DEFAULT NULL,
  `user_text_bank` text DEFAULT NULL,
  `user_text_log` text DEFAULT NULL,
  `user_text_password` text DEFAULT NULL,
  `user_text_paypassword` text DEFAULT NULL,
  `mytx_min` text DEFAULT NULL,
  `mytx_max` text DEFAULT NULL,
  `mytx_bei` text DEFAULT NULL,
  `mytx_coin` text DEFAULT NULL,
  `mytx_fee` text DEFAULT NULL,
  `trade_min` text DEFAULT NULL,
  `trade_max` text DEFAULT NULL,
  `trade_limit` text DEFAULT NULL,
  `trade_text_log` text DEFAULT NULL,
  `issue_ci` text DEFAULT NULL,
  `issue_jian` text DEFAULT NULL,
  `issue_min` text DEFAULT NULL,
  `issue_max` text DEFAULT NULL,
  `money_min` text DEFAULT NULL,
  `money_max` text DEFAULT NULL,
  `money_bei` text DEFAULT NULL,
  `money_text_index` text DEFAULT NULL,
  `money_text_log` text DEFAULT NULL,
  `money_text_type` text DEFAULT NULL,
  `invit_type` text DEFAULT NULL,
  `invit_fee1` text DEFAULT NULL,
  `invit_fee2` text DEFAULT NULL,
  `invit_fee3` text DEFAULT NULL,
  `invit_text_txt` text DEFAULT NULL,
  `invit_text_log` text DEFAULT NULL,
  `text_footer` text DEFAULT NULL,
  `shop_text_index` text DEFAULT NULL,
  `shop_text_log` text DEFAULT NULL,
  `shop_text_addr` text DEFAULT NULL,
  `shop_text_view` text DEFAULT NULL,
  `topup_text_index` text DEFAULT NULL,
  `topup_text_log` text DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  `shop_coin` varchar(200) NOT NULL COMMENT 'Calculation',
  `shop_logo` varchar(200) NOT NULL COMMENT 'MallLOGO',
  `shop_login` varchar(200) NOT NULL COMMENT 'Do you want to log in',
  `trade_moshi` varchar(50) DEFAULT NULL,
  `reg_award` tinyint(1) DEFAULT 0,
  `reg_award_coin` varchar(50) DEFAULT NULL,
  `reg_award_num` decimal(20,8) DEFAULT NULL,
  `ref_award` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Enable Referrer rewards',
  `ref_award_coin` varchar(30) NOT NULL COMMENT 'Referrer rewards currency',
  `ref_award_num` decimal(20,8) NOT NULL COMMENT 'Referrer rewards Amount',
  `lever_risk_rate` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `lever_interest` decimal(7,4) NOT NULL DEFAULT 0.0000,
  `lever_bs` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `lever_max_times` decimal(8,0) NOT NULL DEFAULT 0 COMMENT 'maximum leverage allowed for trading',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='System Configuration Table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_config`
--

INSERT INTO `codono_config` (`id`, `footer_logo`, `topup_zidong`, `topup_openid`, `topup_appkey`, `login_verify`, `fee_meitian`, `web_name`, `web_title`, `web_logo`, `web_keywords`, `web_description`, `web_close`, `web_close_cause`, `web_icp`, `web_html_footer`, `web_ren`, `web_reg`, `market_mr`, `xnb_mr`, `rmb_mr`, `web_waring`, `issue_warning`, `cellphone_type`, `cellphone_url`, `cellphone_user`, `cellphone_pwd`, `contact_cellphone`, `contact_twitter`, `contact_facebook`, `contact_pinterest`, `contact_youtube`, `contact_linkedin`, `contact_instagram`, `contact_qq`, `contact_qqun`, `contact_telegram`, `contact_telegram_img`, `contact_app_img`, `google_play`, `apple_store`, `contact_email`, `contact_alipay`, `contact_alipay_img`, `contact_bank`, `user_truename`, `user_cellphone`, `user_alipay`, `user_bank`, `user_text_truename`, `user_text_cellphone`, `user_text_alipay`, `user_text_bank`, `user_text_log`, `user_text_password`, `user_text_paypassword`, `mytx_min`, `mytx_max`, `mytx_bei`, `mytx_coin`, `mytx_fee`, `trade_min`, `trade_max`, `trade_limit`, `trade_text_log`, `issue_ci`, `issue_jian`, `issue_min`, `issue_max`, `money_min`, `money_max`, `money_bei`, `money_text_index`, `money_text_log`, `money_text_type`, `invit_type`, `invit_fee1`, `invit_fee2`, `invit_fee3`, `invit_text_txt`, `invit_text_log`, `text_footer`, `shop_text_index`, `shop_text_log`, `shop_text_addr`, `shop_text_view`, `topup_text_index`, `topup_text_log`, `addtime`, `status`, `shop_coin`, `shop_logo`, `shop_login`, `trade_moshi`, `reg_award`, `reg_award_coin`, `reg_award_num`, `ref_award`, `ref_award_coin`, `ref_award_num`, `lever_risk_rate`, `lever_interest`, `lever_bs`, `lever_max_times`) VALUES
(1, '6660298adcf833.42116715.png', '1', '', '', '1', '', 'Codono.com', 'Advanced cryptocurrency exchange to buy and sell Bitcoin, Ethereum, Litecoin, Monero, ZCash, DigitalNote,', '65b7710a3e799.png', 'bitcoin, buy bitcoin, bitcoin exchange, low fees, trading terminal, trading api, btc to usd, btc to eur', 'Codono is advanced cryptocurrency exchange to buy and sell Bitcoin, Ethereum, Litecoin, Monero, ZCash, DigitalNote,', '1', 'We are currently upgrading our system. Please come back in some time..', 'Copyright 2024 Cryptocurrency Exchange Softwares', '', '100', 'Codono Terms of Service\r\n 1. Terms\r\n &nbsp; By accessing the website at https://codono.com, you are agreeing to be bound by these terms of service, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws. If you do not agree with any of these terms, you are prohibited from using or accessing this site. The materials contained in this website are protected by applicable copyright and trademark law.\r\n 2. Use License\r\n ampamp;amp; \r\n ; Permission is granted to temporarily download one copy of the materials (information or software) on Codonos website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:\r\n ampamp;amp;\r\n amp;\r\n ; modify or copy the materials;\r\n ampamp;amp;\r\n amp;\r\n ; use the materials for any commercial purpose, or for any public display (commercial or non-commercial);\r\n ampamp;amp;\r\n amp;\r\n ; attempt to decompile or reverse engineer any software contained on Codonos website;\r\n ampamp;amp;\r\n amp;\r\n ; remove any copyright or other proprietary notations from the materials; or\r\n ampamp;amp;\r\n amp;\r\n ; transfer the materials to another person or mirror the materials on any other server.\r\n ampamp;amp;\r\n ; This license shall automatically terminate if you violate any of these restrictions and may be terminated by Codono at any time. Upon terminating your viewing of these materials or upon the termination of this license, you must destroy any downloaded materials in your possession whether in electronic or printed format.\r\n 3. Disclaimer\r\n ampamp;amp;\r\n ; The materials on Codonos website are provided on an as is basis. Codono makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.\r\n ampamp;amp;\r\n ; Further, Codono does not warrant or make any representations concerning the accuracy, likely results, or reliability of the use of the materials on its website or otherwise relating to such materials or on any sites linked to this site.\r\n 4. Limitations\r\n &nbsp; In no event shall Codono or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Codonos website, even if Codono or a Codono authorized representative has been notified orally or in writing of the possibility of such damage. Because some jurisdictions do not allow limitations on implied warranties, or limitations of liability for consequential or incidental damages, these limitations may not apply to you.\r\n 5. Accuracy of materials\r\n &nbsp; The materials appearing on Codono website could include technical, typographical, or photographic errors. Codono does not warrant that any of the materials on its website are accurate, complete or current. Codono may make changes to the materials contained on its website at any time without notice. However Codono does not make any commitment to update the materials.\r\n 6. Links\r\n &nbsp; Codono has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Codono of the site. Use of any such linked website is at the users own risk.\r\n 7. Modifications\r\n &nbsp; Codono may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.\r\n 8. Governing Law\r\n &nbsp; These terms and conditions are governed by and construed in accordance with the laws of British Virgin Islands and you irrevocably submit to the exclusive jurisdiction of the courts in that State or location.', 'btc_usdt', 'btc', 'usd', '<p>\n	Hello\n</p>\n<p>\n	<strong>HII</strong>\n</p>', 'ICO risk warning advises against investing more than one&amp;#39;s financial capacity for risk. It cautions not to invest in assets without understanding their fundamentals. Additionally, it warns against heeding any online recommendations to purchase coins under the guise of investment advice. Investors are urged to firmly resist pyramid schemes, wire fraud, money laundering, and any other forms of illegal arbitrage activities', '1', 'SMS Platform', '8b3c13ea', 'CgtfExSXljvm9oxh', '001-213-513-895', 'https://twitter.com/codono', 'https://facebook.com/', 'https://pinterest.com/', 'https://youtube.com/', 'https://linkedin.com/', 'https://instagram.com/', '123123123123', '123123123123', 'https://t.me/codono', '5a25579dbb418.png', '5a2557a174748.png', 'https://play.google.com/store/apps/details?id=io.metamask', 'https://apps.apple.com/us/app/metamask-blockchain-wallet/id1438144202', 'support@codono.com', 'something@domain.com', '56f98e6d7245d.jpg', 'Bank of America|Business Name|0000 0000 0000 0000', '2', '2', '2', '2', '&lt;span&gt;&lt;span&gt;Hello Member,Be sure to fill in your real name and true identity card number.&lt;/span&gt;&lt;/span&gt;', '&lt;span&gt;Hello Member,Be sure to authenticate the phone with their mobile phone number,After receiving the authentication codes may be used to.&lt;/span&gt;', '&lt;span&gt;Hello Member,Be sure to fill in the correct Alipay &amp;nbsp;(The same as the real-name authentication name) real names and Alipay account,Late withdrawals only basis.&lt;/span&gt;', '&lt;span&gt;Hello Member,&lt;/span&gt;&lt;span&gt;&lt;span&gt;Be sure to fill out the card information correctly Withdraw the sole basis.&lt;/span&gt;&lt;span&gt;&lt;/span&gt;&lt;/span&gt;', '&lt;span&gt;Their past records and operations login and registration spot.&lt;/span&gt;', '&lt;span&gt;Hello Member,After change my password, do not forget.If you remember the old password,Please click--&lt;/span&gt;&lt;span style=&quot;color:#EE33EE;&quot;&gt;forget password&lt;/span&gt;', '&lt;span&gt;Hello Member,After modifying transaction password please do not forget.If you remember the old transaction password,Please click--&lt;/span&gt;&lt;span style=&quot;color:#EE33EE;&quot;&gt;forget password&lt;/span&gt;', '100', '50000', '100', 'usd', '1', '1', '10000000', '10', '&lt;span&gt;&lt;span&gt;After you record the commission to buy or sell a successful trading.&lt;/span&gt;&lt;/span&gt;', '5', '24', '1', '100000', '1', '100000', '100', 'Money Home', 'Financial records', 'Money type', '1', '5', '3', '2', 'BTCCoin.Org Crypto Trading Platform', '&lt;span&gt;&lt;span&gt;To see your friend promotion,Please click&lt;/span&gt;&lt;span style=&quot;color:#EE33EE;&quot;&gt;+&lt;/span&gt;&lt;span&gt;,Meanwhile correct guidance and the sale of real-name authentication friend,Earn promotion revenue and transaction fees.&lt;/span&gt;&lt;/span&gt;', '', '', '', '', '', '', '', 1467383018, 0, '', '/Upload/shop/5ab0d2f822e98.png', '0', '0', 1, 'etc', '1.00000000', 1, 'usd', '1.50020000', '2.00000000', '0.0500', '10.00000000', '3');

-- --------------------------------------------------------

--
-- Table structure for table `codono_debug`
--

DROP TABLE IF EXISTS `codono_debug`;
CREATE TABLE IF NOT EXISTS `codono_debug` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(80) DEFAULT NULL,
  `code` text DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COMMENT='codono logging table for admin view';

-- --------------------------------------------------------

--
-- Table structure for table `codono_dex_coins`
--

DROP TABLE IF EXISTS `codono_dex_coins`;
CREATE TABLE IF NOT EXISTS `codono_dex_coins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `is_token` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=token, 0=coin',
  `symbol` varchar(10) DEFAULT NULL,
  `contract_address` varchar(60) DEFAULT NULL,
  `decimals` int(2) NOT NULL DEFAULT 8,
  `img` varchar(40) DEFAULT NULL,
  `price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `buy_max` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `buy_min` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_dex_coins`
--

INSERT INTO `codono_dex_coins` (`id`, `name`, `is_token`, `symbol`, `contract_address`, `decimals`, `img`, `price`, `buy_max`, `buy_min`, `is_default`, `status`) VALUES
(2, 'Uniswap', 1, 'uni', '0x1f9840a85d5af5bf1d1762f925bdaddc4201f984', 18, '6113d051e051d.png', '0.00200000', '100.00000000', '0.00010000', 0, 1),
(3, 'DAI', 1, 'dai', '0xad6d458402f60fd3bd25163575031acdce07538d', 18, 'DAI.png', '0.01010000', '10000.00000000', '0.01000000', 0, 1),
(4, 'ethereum', 0, 'eth', NULL, 8, 'ETH.png', '0.00030001', '1000.00000000', '0.00001000', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_dex_config`
--

DROP TABLE IF EXISTS `codono_dex_config`;
CREATE TABLE IF NOT EXISTS `codono_dex_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receiver` varchar(50) DEFAULT NULL COMMENT 'main address',
  `receiver_priv` varchar(250) DEFAULT NULL COMMENT 'main address priv encoded',
  `coin` varchar(10) NOT NULL DEFAULT 'bnb' COMMENT 'eth or bnb',
  `network` varchar(10) NOT NULL DEFAULT 'mainnet',
  `token_name` varchar(30) DEFAULT NULL,
  `token_symbol` varchar(10) DEFAULT NULL,
  `token_image` varchar(60) DEFAULT NULL COMMENT 'token image',
  `token_decimals` tinyint(2) NOT NULL DEFAULT 8,
  `token_address` varchar(50) DEFAULT NULL COMMENT 'token_contract_address',
  `token_min` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'min buy token',
  `token_max` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'max buy token',
  `lastblock_coin` int(11) NOT NULL DEFAULT 0 COMMENT 'last block read for coin deposit check cron',
  `lastblock_token` int(11) NOT NULL DEFAULT 0 COMMENT 'last block read for token deposit check cron',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_dex_config`
--

INSERT INTO `codono_dex_config` (`id`, `receiver`, `receiver_priv`, `coin`, `network`, `token_name`, `token_symbol`, `token_image`, `token_decimals`, `token_address`, `token_min`, `token_max`, `lastblock_coin`, `lastblock_token`) VALUES
(1, '0x2f7f81a71f455156fdd9bcb289621a3537e630b9', 'encodedhash', 'eth', 'ropsten', 'Z1 Finances', 'ztu', '625d0de658984.png', 8, '0x5fa128ea1eebb38895cc2d5246888f6b062f30b6', '1.00000000', '100000.00000000', 11901878, 10938131);

-- --------------------------------------------------------

--
-- Table structure for table `codono_dex_deposit`
--

DROP TABLE IF EXISTS `codono_dex_deposit`;
CREATE TABLE IF NOT EXISTS `codono_dex_deposit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `qid` varchar(50) DEFAULT NULL,
  `in_hash` varchar(80) DEFAULT NULL,
  `in_address` varchar(50) DEFAULT NULL,
  `coin` varchar(10) DEFAULT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `in_time` int(11) DEFAULT NULL,
  `payout_status` tinyint(1) NOT NULL DEFAULT 0,
  `payout_hash` varchar(80) DEFAULT NULL,
  `payout_qty` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'tokens paid to address',
  `payout_time` int(11) DEFAULT NULL COMMENT 'paid time',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_dex_order`
--

DROP TABLE IF EXISTS `codono_dex_order`;
CREATE TABLE IF NOT EXISTS `codono_dex_order` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `qid` varchar(60) NOT NULL DEFAULT '0_0' COMMENT 'uid_time',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `spend_coin` varchar(7) DEFAULT NULL COMMENT 'coin bought or sold',
  `buy_coin` varchar(7) DEFAULT NULL COMMENT 'base coin',
  `price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `qty` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'qty',
  `total` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'price x qty',
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `expire` int(11) UNSIGNED DEFAULT NULL COMMENT 'price x qty',
  `connected_address` varchar(64) DEFAULT NULL COMMENT 'connected_address',
  `received` tinyint(1) DEFAULT 0 COMMENT '0=unpaid,1=paid',
  `receive_txid` varchar(64) DEFAULT NULL COMMENT 'txid for payment',
  `sent` tinyint(1) DEFAULT 0 COMMENT '0=unpaid,1=paid',
  `sent_txid` varchar(64) DEFAULT NULL COMMENT 'txid for payment',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COMMENT='Dex Order records';

--
-- Dumping data for table `codono_dex_order`
-- --------------------------------------------------------

--
-- Table structure for table `codono_dex_transactions`
--

DROP TABLE IF EXISTS `codono_dex_transactions`;
CREATE TABLE IF NOT EXISTS `codono_dex_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `ischecked` tinyint(1) NOT NULL DEFAULT 0,
  `coinname` varchar(30) NOT NULL DEFAULT '',
  `blockNumber` int(11) NOT NULL DEFAULT 0,
  `timeStamp` int(10) NOT NULL DEFAULT 0,
  `hash` varchar(200) NOT NULL DEFAULT '',
  `nonce` int(11) NOT NULL DEFAULT 0,
  `blockHash` varchar(200) NOT NULL DEFAULT '',
  `transactionIndex` int(11) NOT NULL DEFAULT 0,
  `from` varchar(200) NOT NULL DEFAULT '',
  `to` varchar(200) NOT NULL DEFAULT '',
  `value` varchar(50) NOT NULL DEFAULT '0',
  `isError` tinyint(1) NOT NULL DEFAULT 0,
  `txreceipt_status` tinyint(1) NOT NULL DEFAULT 0,
  `contractAddress` varchar(200) DEFAULT NULL,
  `confirmations` int(11) NOT NULL DEFAULT 0,
  `tokenSymbol` varchar(20) DEFAULT NULL,
  `tokenName` varchar(40) DEFAULT NULL,
  `tokenDecimal` int(11) DEFAULT NULL,
  `islost` tinyint(1) NOT NULL DEFAULT 0,
  `isdone` tinyint(1) NOT NULL DEFAULT 0,
  `methodId` varchar(42) DEFAULT NULL,
  `endtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE,
  KEY `ischecked` (`ischecked`) USING BTREE,
  KEY `coinname` (`coinname`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_dice`
--

DROP TABLE IF EXISTS `codono_dice`;
CREATE TABLE IF NOT EXISTS `codono_dice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `call` enum('low','high') DEFAULT NULL,
  `number` tinyint(3) NOT NULL DEFAULT 0 COMMENT 'bid',
  `userid` int(11) NOT NULL,
  `result` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=win,2=loose',
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `coinname` varchar(30) DEFAULT NULL,
  `winamount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `addtime` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb3;


--
-- Table structure for table `codono_dividend`
--

DROP TABLE IF EXISTS `codono_dividend`;
CREATE TABLE IF NOT EXISTS `codono_dividend` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `coinjian` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL COMMENT 'Airdrop description',
  `image` varchar(255) DEFAULT '/Upload/airdrop/default_airdrop.png' COMMENT 'airdrop promo image',
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'featured',
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0 COMMENT '0= processed, 1= to be processed',
  `active` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'active/inactive',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_dividend`
--

INSERT INTO `codono_dividend` (`id`, `name`, `coinname`, `coinjian`, `content`, `image`, `num`, `is_featured`, `sort`, `addtime`, `endtime`, `status`, `active`) VALUES
(1, 'Doge to Eos', 'doge', 'eos', NULL, '/Upload/airdrop/default_airdrop.png', '25.00000000', 0, 1, 1564132478, 1564132478, 0, 0),
(2, 'Waves airdrop', 'waves', 'doge', '', '/Upload/airdrop/5d3e9a704ce78.png', '10000.00000000', 0, 1, 1676205966, 1707914779, 1, 1),
(3, 'Litecoin to power airdrop', 'ltct', 'powr', 'Get more than $30 with a few simple steps and join the airdrop: \r\n  \r\n \r\n You’ve reached the official CURESToken airdrop with 100% guaranteed token rewards. Take part with the following tasks and become part of the upcoming healthcare revolution while monetizing your efforts.\r\n \r\n  \r\n \r\n Remember to return every 24 hours for the daily tasks that would grant you additional rewards.\r\n \r\n Airdrop Token Price: 1 CRS = 0.001 ETH\r\n \r\n Tokens will be given away after the end of the ICO – for more information check our website.\r\n \r\n *Telegram Members should remain such until the tokens are given away.\r\n \r\n * Instagram Followers should remain such until the tokens are given away.\r\n \r\n *CURES Token remains the right to disqualify participants and reject inappropriate comments on Bitcointalk & Telegram.', '/Upload/airdrop/default_airdrop.png', '10000.00000000', 1, 10, 1564168792, 1564531200, 0, 1),
(4, 'Hold EOS Get Doge', 'eos', 'doge', '100k Doge to be distributed \r\n Start 10 Oct :00:00:00\r\n Start 11 Oct :23:59:59\r\n \r\n ', '/Upload/airdrop/5d76391fc2178.jpg', '100000.00000000', 1, 1, 1570665600, 1570838399, 0, 1),
(5, 'rjkkj', 'etn', 'etn', '', '', '100.00000000', 0, 1, 1589477528, 1589736728, 0, 0),
(6, 'Satoshi Airdrops', 'btc', 'btc', 'Hold BTC and Get BTC, We are distributing  Satoshi', '/Upload/airdrop/63eb35e3aaac1.png', '2.00000000', 0, 1, 1644364800, 1707436800, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_dividend_log`
--

DROP TABLE IF EXISTS `codono_dividend_log`;
CREATE TABLE IF NOT EXISTS `codono_dividend_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `coinjian` varchar(50) DEFAULT NULL,
  `fenzong` varchar(50) DEFAULT NULL,
  `fenchi` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0 COMMENT '1=enable , 0 disable',
  `userid` int(11) UNSIGNED NOT NULL COMMENT 'userid',
  PRIMARY KEY (`id`),
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_dividend_log`

--
-- Table structure for table `codono_dust`
--

DROP TABLE IF EXISTS `codono_dust`;
CREATE TABLE IF NOT EXISTS `codono_dust` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT 0,
  `from_coin` varchar(20) DEFAULT NULL,
  `from_amount` decimal(20,8) DEFAULT 0.00000000,
  `to_coin` varchar(20) DEFAULT NULL,
  `to_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COMMENT='dust conversion records';

--
-- Dumping data for table `codono_dust`


-- --------------------------------------------------------

--
-- Table structure for table `codono_electrum`
--

DROP TABLE IF EXISTS `codono_electrum`;
CREATE TABLE IF NOT EXISTS `codono_electrum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coinname` varchar(30) DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `deposited` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_esmart`
--

DROP TABLE IF EXISTS `codono_esmart`;
CREATE TABLE IF NOT EXISTS `codono_esmart` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) DEFAULT NULL,
  `address` varchar(42) NOT NULL,
  `addtime` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_exeggex`
--

DROP TABLE IF EXISTS `codono_exeggex`;
CREATE TABLE IF NOT EXISTS `codono_exeggex` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `_id` varchar(255) DEFAULT NULL COMMENT 'uniqueid',
  `symbol` varchar(50) DEFAULT NULL,
  `primaryName` varchar(100) DEFAULT NULL,
  `primaryTicker` varchar(50) DEFAULT NULL,
  `lastPrice` decimal(20,10) DEFAULT 0.0000000000,
  `yesterdayPrice` decimal(20,10) DEFAULT 0.0000000000,
  `highPrice` decimal(20,10) DEFAULT 0.0000000000,
  `lowPrice` decimal(20,10) DEFAULT 0.0000000000,
  `volume` decimal(20,10) DEFAULT 0.0000000000,
  `lastTradeAt` bigint(20) DEFAULT 0,
  `priceDecimals` int(11) DEFAULT 0,
  `quantityDecimals` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `primaryAsset` varchar(255) DEFAULT NULL,
  `secondaryAsset` varchar(255) DEFAULT NULL,
  `bestAsk` decimal(20,10) DEFAULT 0.0000000000,
  `bestBid` decimal(20,10) DEFAULT 0.0000000000,
  `createdAt` bigint(20) DEFAULT 0,
  `updatedAt` bigint(20) DEFAULT 0,
  `lineChart` text DEFAULT NULL,
  `bestAskNumber` decimal(20,10) DEFAULT 0.0000000000,
  `bestBidNumber` decimal(20,10) DEFAULT 0.0000000000,
  `changePercent` varchar(10) DEFAULT NULL,
  `changePercentNumber` decimal(10,2) DEFAULT 0.00,
  `highPriceNumber` decimal(20,10) DEFAULT 0.0000000000,
  `lastPriceNumber` decimal(20,10) DEFAULT 0.0000000000,
  `lowPriceNumber` decimal(20,10) DEFAULT 0.0000000000,
  `volumeNumber` decimal(20,10) DEFAULT 0.0000000000,
  `yesterdayPriceNumber` decimal(20,10) DEFAULT 0.0000000000,
  `volumeUsdNumber` decimal(20,10) DEFAULT 0.0000000000,
  `marketcapNumber` bigint(20) DEFAULT 0,
  `primaryCirculation` decimal(20,10) DEFAULT 0.0000000000,
  `primaryUsdValue` decimal(20,10) DEFAULT 0.0000000000,
  `secondaryCirculation` decimal(20,10) DEFAULT 0.0000000000,
  `secondaryUsdValue` decimal(20,10) DEFAULT 0.0000000000,
  `spreadPercent` decimal(10,3) DEFAULT 0.000,
  `lastPriceUpDown` varchar(10) DEFAULT NULL,
  `engineId` int(11) DEFAULT 0,
  `isPaused` tinyint(1) DEFAULT 0,
  `imageUUID` varchar(255) DEFAULT NULL,
  `volumeSecondary` decimal(20,10) DEFAULT 0.0000000000,
  `volumeSecondaryNumber` decimal(20,10) DEFAULT 0.0000000000,
  `spreadPercentNumber` decimal(10,3) DEFAULT 0.000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `codono_faucet`
--

DROP TABLE IF EXISTS `codono_faucet`;
CREATE TABLE IF NOT EXISTS `codono_faucet` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `num` bigint(20) UNSIGNED DEFAULT NULL,
  `deal` int(11) UNSIGNED DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `ulimit` varchar(11) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `tian` varchar(255) DEFAULT NULL,
  `ci` varchar(255) DEFAULT NULL,
  `jian` varchar(255) DEFAULT NULL,
  `min` varchar(255) DEFAULT NULL,
  `max` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `invit_coin` varchar(50) DEFAULT NULL,
  `invit_1` varchar(50) DEFAULT NULL,
  `invit_2` varchar(50) DEFAULT NULL,
  `invit_3` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `tuijian` tinyint(1) NOT NULL DEFAULT 2,
  `homepage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Show on homepage',
  `paixu` int(5) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COMMENT='Faucet List';

--
-- Dumping data for table `codono_faucet`
--

INSERT INTO `codono_faucet` (`id`, `name`, `coinname`, `buycoin`, `num`, `deal`, `price`, `ulimit`, `time`, `tian`, `ci`, `jian`, `min`, `max`, `content`, `invit_coin`, `invit_1`, `invit_2`, `invit_3`, `sort`, `addtime`, `endtime`, `status`, `image`, `tuijian`, `homepage`, `paixu`) VALUES
(12, 'Faucet every 10th Hour', 'eth', NULL, 10, 5, '0.00100000', '10', '2021-05-06 16:30:30', '10', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1620376246, 1651941041, 1, NULL, 2, 0, 0),
(13, 'Show time', 'doge', NULL, 20, 5, '0.01000000', '0', '2020-12-04 13:02:07', '8', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1620376432, 1670601600, 1, NULL, 2, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_faucet_log`
--

DROP TABLE IF EXISTS `codono_faucet_log`;
CREATE TABLE IF NOT EXISTS `codono_faucet_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fid` int(11) NOT NULL DEFAULT 0 COMMENT 'faucetid',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `username` varchar(50) NOT NULL DEFAULT '********' COMMENT 'masked username',
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `num` int(20) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `ci` int(11) UNSIGNED DEFAULT NULL,
  `jian` varchar(255) DEFAULT NULL,
  `unlock` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COMMENT='Faucet Usages';


--
-- Table structure for table `codono_finance`
--

DROP TABLE IF EXISTS `codono_finance`;
CREATE TABLE IF NOT EXISTS `codono_finance` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `userid` int(11) UNSIGNED NOT NULL COMMENT 'userid',
  `coinname` varchar(50) NOT NULL COMMENT 'Currencies',
  `num_a` decimal(20,8) UNSIGNED DEFAULT NULL COMMENT 'Prior to normal',
  `num_b` decimal(20,8) UNSIGNED DEFAULT NULL COMMENT 'Before freezing',
  `num` decimal(20,8) UNSIGNED NOT NULL COMMENT 'Before Total',
  `fee` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'The number of operations',
  `type` varchar(50) NOT NULL COMMENT 'Action Type',
  `name` varchar(50) NOT NULL COMMENT 'Operation Name',
  `nameid` bigint(20) NOT NULL COMMENT 'Action Details',
  `remark` varchar(50) NOT NULL COMMENT 'Action Remark',
  `mum_a` decimal(20,8) UNSIGNED DEFAULT NULL COMMENT 'The remaining normal',
  `mum_b` decimal(20,8) UNSIGNED DEFAULT NULL COMMENT 'The remaining freeze',
  `mum` decimal(20,8) UNSIGNED NOT NULL COMMENT 'Total surplus',
  `move` varchar(68) NOT NULL COMMENT 'Additional',
  `addtime` int(11) UNSIGNED NOT NULL COMMENT 'add time',
  `status` tinyint(4) UNSIGNED NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `coinname` (`coinname`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=230 DEFAULT CHARSET=utf8mb3 COMMENT='Financial records table';

--
-- Table structure for table `codono_footer`
--

DROP TABLE IF EXISTS `codono_footer`;
CREATE TABLE IF NOT EXISTS `codono_footer` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `pid` int(10) NOT NULL DEFAULT 0 COMMENT 'parent',
  `name` varchar(255) NOT NULL COMMENT 'name',
  `title` varchar(255) NOT NULL COMMENT 'name',
  `url` varchar(255) NOT NULL COMMENT 'url',
  `ico` varchar(30) DEFAULT NULL COMMENT 'Font awesome Icon',
  `mobile_ico` varchar(50) DEFAULT NULL COMMENT 'mobile icons',
  `featured` varchar(30) DEFAULT NULL COMMENT 'featured block',
  `subtext` varchar(60) DEFAULT NULL,
  `sort` int(11) UNSIGNED NOT NULL COMMENT 'Sequence',
  `addtime` int(11) UNSIGNED NOT NULL COMMENT 'add time',
  `endtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Edit time',
  `is_external` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_footer`
--

INSERT INTO `codono_footer` (`id`, `pid`, `name`, `title`, `url`, `ico`, `mobile_ico`, `featured`, `subtext`, `sort`, `addtime`, `endtime`, `is_external`, `status`) VALUES
(1, 0, 'finance', 'Finance', 'Finance/index', NULL, NULL, NULL, NULL, 1, 0, 0, 0, -1),
(2, 0, 'user', 'User', 'User/index', NULL, NULL, NULL, NULL, 2, 0, 0, 0, -1),
(4, 0, 'Blog', 'News', 'Article/index', '', NULL, NULL, NULL, 7, 0, 0, 0, -1),
(6, 24, 'Store', 'Store', 'Store/index', 'ion-md-lock', NULL, NULL, NULL, 5, 0, 0, 0, 1),
(7, 24, 'Vote', 'Vote', 'Vote/index', 'ion-md-lock', NULL, NULL, NULL, 6, 0, 0, 0, 1),
(8, 0, 'ICO', 'ICO', 'Issue/index', NULL, NULL, NULL, NULL, 4, 1474183878, 0, 0, -1),
(15, 20, 'Spot', 'Spot', 'Trade', 'ion-md-lock', NULL, '', 'Trade on our award-winning platform', 3, 1522312746, 0, 0, 1),
(16, 0, 'System Health', 'Health', 'Content/health', 'heartbeat', NULL, NULL, NULL, 10, 1524302866, 0, 0, -1),
(18, 0, 'Mining', 'Mining', 'Pool', '', NULL, NULL, NULL, 20, 1619506686, 0, 0, -1),
(19, 31, 'Swap', 'Swap', 'Otc', 'exchange', '', 'New', 'Easy Trade', 10, 1619518072, 0, 0, 1),
(20, 0, 'Trade', 'Trade', 'Trade', '', NULL, NULL, NULL, 30, 1630916925, 0, 0, 1),
(21, 0, 'Market', 'Market', 'Content/market', '', NULL, NULL, NULL, 3, 1631095835, 0, 0, 1),

(23, 31, 'Lab', 'Lab', 'Issue', 'flask', '', 'Innovate', 'Blockchain Research and Investments', 10, 1633007654, 0, 0, 1),
(24, 0, 'About', 'About', '#', '', '', '', '', 20, 1633007905, 0, 0, 1),
(25, 24, 'Apps', 'Apps', 'Content/apps', 'ion-md-lock', NULL, NULL, NULL, 0, 1633008047, 0, 0, 1),
(29, 0, ' ', ' ', '#', 'cube', '', '', '', 0, 1675323619, 0, 1, -1),
(30, 0, 'Mining', 'Mining', 'Pool', 'heavy-metal', '', 'Mine to Earn', 'Rent mining machines', 0, 1675366471, 0, 1, 1),
(31, 0, 'Product', 'Product', '#', NULL, NULL, NULL, NULL, 0, 1676019870, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_fx`
--

DROP TABLE IF EXISTS `codono_fx`;
CREATE TABLE IF NOT EXISTS `codono_fx` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `deal_buy` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'total bought yet',
  `deal_sell` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'total sold yet',
  `limit` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `min` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `max` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(5) NOT NULL DEFAULT 0,
  `ownerid` int(11) DEFAULT 0 COMMENT 'Fee userid',
  `commission` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `buy_commission` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `sell_commission` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `tier_1` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'fx commission for tier 1',
  `tier_2` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'fx commission for tier 2',
  `tier_3` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'fx commission for tier 3',
  `fees` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COMMENT='fx Coin table';

--
-- Dumping data for table `codono_fx`
--

INSERT INTO `codono_fx` (`id`, `name`, `coinname`, `num`, `deal_buy`, `deal_sell`, `limit`, `min`, `max`, `addtime`, `endtime`, `sort`, `ownerid`, `commission`, `buy_commission`, `sell_commission`, `tier_1`, `tier_2`, `tier_3`, `fees`, `status`) VALUES
(12, 'usd', 'usd', '0.00000000', '347.00000000', '0.00000000', '0.00000000', '10.00000000', '10000.00000000', 1636965208, NULL, 0, 0, '0.00000000', '1.00000000', '1.00000000', '0.00', '0.00', '0.00', '0.00000000', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_fx_bank`
--

DROP TABLE IF EXISTS `codono_fx_bank`;
CREATE TABLE IF NOT EXISTS `codono_fx_bank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `coin` varchar(10) DEFAULT NULL,
  `truename` varchar(60) DEFAULT NULL,
  `bank` varchar(200) DEFAULT NULL,
  `bankaddr` varchar(200) DEFAULT NULL,
  `bankcard` varchar(150) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_fx_bank`
--

INSERT INTO `codono_fx_bank` (`id`, `userid`, `coin`, `truename`, `bank`, `bankaddr`, `bankcard`, `addtime`, `status`) VALUES
(2, 38, 'zar', 'Martin R', 'Bank of America', 'B73438483', '3843849893489', 1636968217, 1),
(3, 38, 'zar', 'Martin R', 'Bank of America', 'BOF872378343jj', '9845749857498754', 1637041150, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_fx_log`
--

DROP TABLE IF EXISTS `codono_fx_log`;
CREATE TABLE IF NOT EXISTS `codono_fx_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `qid` varchar(24) NOT NULL DEFAULT '0_0' COMMENT 'uid_time',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL COMMENT 'type = buy/sell',
  `trade_coin` varchar(7) DEFAULT NULL COMMENT 'coin bought or sold',
  `base_coin` varchar(7) DEFAULT NULL COMMENT 'base coin',
  `final_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `profit` decimal(20,8) DEFAULT 0.00000000 COMMENT 'commission earned ',
  `fees_paid` decimal(20,8) DEFAULT 0.00000000 COMMENT 'fees earned',
  `qty` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'qty',
  `final_total` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'price x num',
  `bank` text DEFAULT NULL COMMENT 'bank info in json',
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `fill` tinyint(1) DEFAULT 0,
  `memo` varchar(50) DEFAULT NULL COMMENT 'bank payment info',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3 COMMENT='fx trade records';

--
-- Dumping data for table `codono_fx_log`
--

INSERT INTO `codono_fx_log` (`id`, `qid`, `userid`, `type`, `trade_coin`, `base_coin`, `final_price`, `profit`, `fees_paid`, `qty`, `final_total`, `bank`, `addtime`, `endtime`, `status`, `fill`, `memo`) VALUES
(21, '38_1637042771', 38, 'buy', 'usd', 'zar', '15.00000000', '15.00000000', '0.00000000', '100.00000000', '1538.00000000', '{\"id\":\"2\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"B73438483\",\"bankcard\":\"3843849893489\",\"addtime\":\"1636968217\",\"status\":\"1\"}', 1637042771, NULL, 1, 1, 'abc123'),
(22, '38_1637051517', 38, 'buy', 'usd', 'zar', '15.00000000', '15.00000000', '0.00000000', '100.00000000', '1538.00000000', '{\"id\":\"3\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"BOF872378343jj\",\"bankcard\":\"9845749857498754\",\"addtime\":\"1637041150\",\"status\":\"1\"}', 1637051517, NULL, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_fx_quotes`
--

DROP TABLE IF EXISTS `codono_fx_quotes`;
CREATE TABLE IF NOT EXISTS `codono_fx_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qid` varchar(30) DEFAULT NULL,
  `data` text DEFAULT NULL COMMENT 'quote information',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_fx_quotes`
--

INSERT INTO `codono_fx_quotes` (`id`, `qid`, `data`) VALUES
(123, '38_1637042610', '{\"qid\":\"38_1637042610\",\"trade_type\":\"buy\",\"trade\":\"usd\",\"base\":\"zar\",\"symbol\":\"usdzar\",\"qty\":\"65.16932845\",\"final_price\":\"15.37533106\",\"final_total\":1002,\"profit\":\"9.92079138\",\"addtime\":1637042610,\"bankinfo\":{\"id\":\"3\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"BOF872378343jj\",\"bankcard\":\"9845749857498754\",\"addtime\":\"1637041150\",\"status\":\"1\"}}'),
(124, '38_1637042771', '{\"qid\":\"38_1637042771\",\"trade_type\":\"buy\",\"trade\":\"usd\",\"base\":\"zar\",\"symbol\":\"usdzar\",\"qty\":100,\"final_price\":\"15.37533106\",\"final_total\":1538,\"profit\":\"15.22309900\",\"addtime\":1637042771,\"bankinfo\":{\"id\":\"2\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"B73438483\",\"bankcard\":\"3843849893489\",\"addtime\":\"1636968217\",\"status\":\"1\"}}'),
(125, '0', '{\"qid\":0,\"trade_type\":\"buy\",\"trade\":\"usd\",\"base\":\"zar\",\"symbol\":\"usdzar\",\"qty\":1000,\"final_price\":\"15.37533106\",\"final_total\":15375,\"profit\":\"152.23099000\",\"addtime\":1637051209,\"bankinfo\":{\"id\":\"3\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"BOF872378343jj\",\"bankcard\":\"9845749857498754\",\"addtime\":\"1637041150\",\"status\":\"1\"}}'),
(126, '38_1637051517', '{\"qid\":\"38_1637051517\",\"trade_type\":\"buy\",\"trade\":\"usd\",\"base\":\"zar\",\"symbol\":\"usdzar\",\"qty\":100,\"final_price\":\"15.37533106\",\"final_total\":1538,\"profit\":\"15.22309900\",\"addtime\":1637051517,\"bankinfo\":{\"id\":\"3\",\"userid\":\"38\",\"coin\":\"zar\",\"truename\":\"Martin R\",\"bank\":\"Bank of America\",\"bankaddr\":\"BOF872378343jj\",\"bankcard\":\"9845749857498754\",\"addtime\":\"1637041150\",\"status\":\"1\"}}');

-- --------------------------------------------------------

--
-- Table structure for table `codono_giftcard`
--

DROP TABLE IF EXISTS `codono_giftcard`;
CREATE TABLE IF NOT EXISTS `codono_giftcard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL DEFAULT 0,
  `coin` varchar(30) DEFAULT '0',
  `card_img` varchar(40) DEFAULT NULL COMMENT 'card image url',
  `public_code` varchar(34) DEFAULT NULL COMMENT 'public code',
  `secret_code` varchar(120) DEFAULT NULL COMMENT 'secret code',
  `value` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `nonce` int(11) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `consumer_id` int(11) NOT NULL DEFAULT 0 COMMENT 'use who consumed',
  `usetime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=invalid, 1 =avail, 2=consumed',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_giftcard`
--

INSERT INTO `codono_giftcard` (`id`, `owner_id`, `coin`, `card_img`, `public_code`, `secret_code`, `value`, `nonce`, `addtime`, `consumer_id`, `usetime`, `status`) VALUES
(10, 382, 'usdt', '1', '163257202265', 'bjR2Z3QyQnNCa2FvR2o5ZzlZbTBheG5MWDl3aWZLZjlLZnNQeG40Y0dtMD0=', '1.11000000', 1632572012, 1632572022, 382, 1632581522, 2),
(11, 382, 'usdt', '8', '163257409246', 'elY4R1FTT25aSm1sUjhYZVAyRCtwRUNoc1Bza3pjd2w3SGh0MjA0WERRMD0=', '4.00000000', 1632574083, 1632574092, 0, NULL, 1),
(12, 382, 'eth', '2', '163257410320', 'b0hOVWJXODRGS2F2cHZ2Z1lVR2hyYUQ1UTVYZkQ2SldWTlpjMnJreFhPWT0=', '0.10000000', 1632574092, 1632574103, 382, 1632579374, 2),
(13, 382, 'btc', '2', '163257411701', 'Y2RxL1lzVTc0V2c2YjNUQ1ducTl0OWhoTVkveEhRVG1YWEJBWHg0WUFSMD0=', '0.01000000', 1632574103, 1632574117, 382, 1632642850, 2),
(14, 382, 'doge', '8', '163257412931', 'ODJqZ3ltU1plQ21jZmsraWN2WVJUaVZuK01XTHdoQWQ3dmpUMm5oaXhvaz0=', '1000.00000000', 1632574118, 1632574129, 382, 1632643218, 2),
(15, 38, 'eth', '9', '38163481012109', 'TFdaSVZ5Tmh5eis5RnY0b0N2ZEFKbm9zSCtYZUZxeWJSdWIzNDNic1NLaz0=', '0.25000000', 1634810103, 1634810121, 38, 1634810214, 2),
(16, 38, 'usd', '9', '38163481081831', 'NDVUcVlZYkwzMGo1aVVUTEtyY3JWSzdpMm1hcnI3T3FLWkZuV1IwYzVoUT0=', '3.56788523', 1634810322, 1634810818, 38, 1680625753, 2),
(17, 38, 'bnb', '9', '38167863136874', 'VjN5M1l5Vm5uWXZ6UVUzeXNwaGdZV0Y3azFkRWpyTVRyUm9aQXZTMlNpZz0=', '0.27766554', 1678631357, 1678631368, 38, 1678631375, 2),
(18, 38, 'btc', '9', '38168062515255', 'QVN1eVdYTlB1WFBWTXkxWFA2dFcxc0JvMk9tZ2Jocm9jSWZNTVd6WHU4UT0=', '0.02000000', 1680625142, 1680625152, 38, 1680625765, 2),
(19, 38, 'usdt', '9', '38168062517515', 'RkpOZDVSd3VzK3k2dGxoS2VhWWFZZi8zWHZtdkFKMWhhOERDS3V4N2grZz0=', '1226.64228942', 1680625163, 1680625175, 38, 1711531101, 2),
(20, 38, 'waves', '3', '38168062574307', 'QzEzVFkxQmtUM1dvOHZzOUp2b3hrTTQwR2phYnRGNmlIQVNudko4MHJDMD0=', '5.32127850', 1680625663, 1680625743, 38, 1687942688, 2),
(21, 3, 'ugx', '9', '3168449467848', 'bW9XczVyL2V0UmcrRkVEQkFsTVhKSWFnNWNPb3JtTy9JQVhqUmtpaG1vdz0=', '726.52888906', 1684494621, 1684494678, 0, NULL, 1),
(22, 38, 'usdt', '9', '38168794296973', 'VklaWVBGN3hlOXdVYlFtZVFVdUlTc2syOVJHTG51ZmYvb1FqTkN6VHRFWT0=', '200.00000000', 1687942852, 1687942969, 38, 1689244056, 2),
(23, 38, 'btc', '9', '38169331410838', 'dmgwMmVqaVdVdE9TR2FwRDBwcnNKbzMrQU8yUnBKSEZrRkF4a2R1SjJXRT0=', '0.00834500', 1693314101, 1693314108, 38, 1693314125, 2),
(24, 38, 'usdt', '1', '38172379886849', 'K0U0YlVhQlhYbnVBRHdJdEN5OU94UzZsRGNQRjUzUHJ0bzRJMkRRVVlTcz0=', '20.50000000', 123, 1723798868, 38, 1724231343, 2),
(25, 38, 'usd', '9', '38172587573126', 'dUQ5WU9ROGc4K3AzZVZoUzlzYkZSRFZwRXZ1cmJObTNONVkwMGZHbG1Qcz0=', '305.60000000', 1725875720, 1725875731, 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_giftcard_images`
--

DROP TABLE IF EXISTS `codono_giftcard_images`;
CREATE TABLE IF NOT EXISTS `codono_giftcard_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) DEFAULT NULL,
  `image` varchar(30) DEFAULT NULL,
  `sort` int(5) NOT NULL DEFAULT 0 COMMENT 'sorting order',
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_giftcard_images`
--

INSERT INTO `codono_giftcard_images` (`id`, `title`, `image`, `sort`, `addtime`, `status`) VALUES
(1, 'Birthday', '1.png', 0, NULL, 1),
(2, 'New year', '2.png', 0, NULL, 1),
(3, 'XMas', '3.png', 0, NULL, 1),
(4, 'Sale Offer', '4.png', 0, NULL, 1),
(5, 'Technofest', '5.png', 0, NULL, 1),
(6, 'Dragon Fight', '6.png', 0, NULL, 1),
(7, 'Ninja Tech', '7.png', 0, NULL, 1),
(8, 'Vision Nation', '8.png', 0, 1634460803, 1),
(9, 'Omega', '616d1aef25817.png', 11, 1634568944, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_initial`
--

DROP TABLE IF EXISTS `codono_initial`;
CREATE TABLE IF NOT EXISTS `codono_initial` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `show_coin` varchar(200) DEFAULT NULL COMMENT 'json with coins user can pay in',
  `num` bigint(20) UNSIGNED DEFAULT NULL,
  `deal` int(11) UNSIGNED DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `limit` int(11) UNSIGNED DEFAULT NULL,
  `time` varchar(40) DEFAULT NULL,
  `tian` int(5) DEFAULT NULL,
  `ci` int(5) DEFAULT NULL,
  `jian` decimal(20,8) DEFAULT 0.00000000,
  `min` decimal(20,8) DEFAULT 0.00000000,
  `max` decimal(20,8) DEFAULT 0.00000000,
  `content` text DEFAULT NULL,
  `invit_coin` varchar(50) DEFAULT NULL,
  `invit_1` decimal(20,8) DEFAULT NULL,
  `invit_2` decimal(20,8) DEFAULT NULL,
  `invit_3` decimal(20,8) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `tuijian` tinyint(1) NOT NULL DEFAULT 2,
  `homepage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Show on homepage',
  `paixu` int(5) NOT NULL DEFAULT 0,
  `video` varchar(255) DEFAULT NULL,
  `convertcurrency` varchar(30) DEFAULT NULL COMMENT 'currency in which user pays',
  `icobench` varchar(250) DEFAULT NULL,
  `icomark` varchar(250) DEFAULT NULL,
  `trackico` varchar(250) DEFAULT NULL,
  `facebook` varchar(250) DEFAULT NULL,
  `twitter` varchar(250) DEFAULT NULL,
  `telegram` varchar(250) DEFAULT NULL,
  `ownerid` int(10) DEFAULT 0 COMMENT 'ICO owner userid',
  `commission` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'Site commission',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='IEO issuing table';

-- --------------------------------------------------------

--
-- Table structure for table `codono_initial_log`
--

DROP TABLE IF EXISTS `codono_initial_log`;
CREATE TABLE IF NOT EXISTS `codono_initial_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `convertcurrency` varchar(50) DEFAULT NULL COMMENT 'Coin in which payment was made',
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `convert_price` decimal(20,8) DEFAULT NULL COMMENT 'converted payment',
  `num` int(20) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `ci` int(11) UNSIGNED DEFAULT NULL,
  `jian` varchar(255) DEFAULT NULL,
  `unlock` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='IEO record form';

-- --------------------------------------------------------

--
-- Table structure for table `codono_initial_timeline`
--

DROP TABLE IF EXISTS `codono_initial_timeline`;
CREATE TABLE IF NOT EXISTS `codono_initial_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `initial_id` int(11) DEFAULT NULL,
  `phase_time` varchar(255) DEFAULT NULL,
  `phase_name` varchar(255) DEFAULT NULL,
  `phase_desc` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_invit`
--

DROP TABLE IF EXISTS `codono_invit`;
CREATE TABLE IF NOT EXISTS `codono_invit` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `invit` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `coin` varchar(30) DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `invit` (`invit`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb3 COMMENT='Promotion reward table';

-- --------------------------------------------------------

--
-- Table structure for table `codono_issue`
--

DROP TABLE IF EXISTS `codono_issue`;
CREATE TABLE IF NOT EXISTS `codono_issue` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `show_coin` varchar(200) DEFAULT NULL COMMENT 'json with coins user can pay in',
  `num` bigint(20) UNSIGNED DEFAULT NULL,
  `deal` int(11) UNSIGNED DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `limit` int(11) UNSIGNED DEFAULT NULL,
  `time` varchar(40) DEFAULT NULL,
  `tian` int(5) DEFAULT NULL,
  `ci` int(5) DEFAULT NULL,
  `jian` decimal(20,8) DEFAULT 0.00000000,
  `min` decimal(20,8) DEFAULT 0.00000000,
  `max` decimal(20,8) DEFAULT 0.00000000,
  `content` text DEFAULT NULL,
  `invit_coin` varchar(50) DEFAULT NULL,
  `invit_1` decimal(20,8) DEFAULT NULL,
  `invit_2` decimal(20,8) DEFAULT NULL,
  `invit_3` decimal(20,8) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `tuijian` tinyint(1) NOT NULL DEFAULT 2,
  `homepage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Show on homepage',
  `paixu` int(5) NOT NULL DEFAULT 0,
  `video` varchar(255) DEFAULT NULL,
  `convertcurrency` varchar(30) DEFAULT NULL COMMENT 'currency in which user pays',
  `icobench` varchar(250) DEFAULT NULL,
  `icomark` varchar(250) DEFAULT NULL,
  `trackico` varchar(250) DEFAULT NULL,
  `facebook` varchar(250) DEFAULT NULL,
  `twitter` varchar(250) DEFAULT NULL,
  `telegram` varchar(250) DEFAULT NULL,
  `ownerid` int(10) DEFAULT 0 COMMENT 'ICO owner userid',
  `commission` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'Site commission',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COMMENT='ICO issuing table';

--
-- Dumping data for table `codono_issue`
--

INSERT INTO `codono_issue` (`id`, `name`, `coinname`, `buycoin`, `show_coin`, `num`, `deal`, `price`, `limit`, `time`, `tian`, `ci`, `jian`, `min`, `max`, `content`, `invit_coin`, `invit_1`, `invit_2`, `invit_3`, `sort`, `addtime`, `endtime`, `status`, `image`, `tuijian`, `homepage`, `paixu`, `video`, `convertcurrency`, `icobench`, `icomark`, `trackico`, `facebook`, `twitter`, `telegram`, `ownerid`, `commission`) VALUES
(5, 'ANASTOMS', 'ast', 'usd', NULL, 21000000, 21000000, '0.20000000', 10000000, '2017-08-01 00:00:00', 365, 0, '0.00000000', '10.00000000', '10000000.00000000', '', 'usd', '3.00000000', '2.00000000', '1.00000000', 0, 1571304704, 0, 1, '59846ad70be5f.png', 2, 0, 2, '', 'usd', '', '', '', '', '', '', 0, '0.00000000'),
(6, 'MTC', 'btc', 'eth', '[\"btc\",\"usdt\"]', 21000000, 10002000, '0.20000000', 1000, '2017-08-01 00:00:00', 365, 0, '0.00000000', '1000.00000000', '1000.00000000', '\r\n 	What is mitcoin?\r\n \r\n \r\n 	mitcoin is an experimental new digital currency that enables instant payments to anyone, anywhere in the world. mitcoin uses peer-to-peer technology to operate with no central authority: managing transactions and issuing money are carried out collectively by the network. mitcoin Core is the name of open source software which enables the use of this currency.\r\n \r\n \r\n 	For more information, as well as an immediately useable, binary version of the mitcoin Core software, see&amp;nbsp;https://www.mitcoin.org/en/download.\r\n \r\n \r\n 	License\r\n \r\n \r\n 	mitcoin Core is released under the terms of the MIT license. See&amp;nbsp;COPYING&amp;nbsp;for more information or see&amp;nbsp;http://opensource.org/licenses/MIT.\r\n \r\n \r\n 	Development process\r\n \r\n \r\n 	Developers work in their own trees, then submit pull requests when they think their feature or bug fix is ready.\r\n \r\n \r\n 	If it is a simple/trivial/non-controversial change, then one of the mitcoin development team members simply pulls it.\r\n \r\n \r\n 	If it is a&amp;nbsp;more complicated or potentially controversial&amp;nbsp;change, then the patch submitter will be asked to start a discussion (if they havent already) on the&amp;nbsp;mailing list\r\n \r\n \r\n 	The patch will be accepted if there is broad consensus that it is a good thing. Developers should expect to rework and resubmit patches if the code doesnt match the projects coding conventions (see&amp;nbsp;doc/developer-notes.md) or are controversial.\r\n \r\n \r\n 	The&amp;nbsp;master&amp;nbsp;branch is regularly built and tested, but is not guaranteed to be completely stable.&amp;nbsp;Tags&amp;nbsp;are created regularly to indicate new official, stable release versions of mitcoin.\r\n \r\n \r\n 	Testing\r\n \r\n \r\n 	Testing and code review is the bottleneck for development; we get more pull requests than we can review and test on short notice. Please be patient and help out by testing other peoples pull requests, and remember this is a security-critical project where any mistake might cost people lots of money.\r\n ', 'usd', '0.00000000', '0.00000000', '0.00000000', 0, 1677824717, 0, 1, '5ae81e86dc758.jpg', 2, 0, 1, '', NULL, '', '', '', '', '', '', 0, '0.00000000'),
(7, 'GRIN ICO', 'grin', 'eth', '[\"btc\",\"eth\",\"usdt\"]', 10000, 4611, '1.00000000', 1000000, '2020-03-07 19:13:07', 180, 0, '0.00000000', '1.00000000', '100000.00000000', '&lt;div class=&quot;whitelist row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Whitelist\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		No\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;token-sale-hard-cap row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Token Sale Hard Cap\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		TBD\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;token-sale-soft-cap row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Token Sale Soft Cap\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		TBD\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;token-symbol row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Token Symbol\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		POWR\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;token-type row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Token Type\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		ERC20, Ethereum\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;token-distribution row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Token Distribution\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		70% Platform dev  operations 15 % General overhead costs 15% Marketing\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;initial_token-price row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Initial Token Price\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		1 POWR = 0.0838 USD\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;kyc row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		KYC\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		No\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;particpation-restrictions row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Participation Restrictions\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		USA  China\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;\r\n &lt;div class=&quot;accepts row&quot; style=&quot;margin-left:-20px;color:#333333;font-family:lato, sans-serif;font-size:15px;background-color:#FFFFFF;&quot;&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-3 dim&quot; style=&quot;color:#BDC3C7;&quot;&gt;\r\n 		Accepts\r\n 	&lt;/div&gt;\r\n 	&lt;div class=&quot;col-xs-12 col-md-9&quot;&gt;\r\n 		ETH, BTC, LTC\r\n 	&lt;/div&gt;\r\n &lt;/div&gt;', 'usd', '5.00000000', '4.00000000', '3.00000000', NULL, 1648882056, NULL, 1, '5ab0f8d883978.jpg', 2, 1, 10, '', 'btc', '', '', '', '', '', '', 51, '50.00000000'),
(8, 'Arcona', 'eth', 'usd', '[\"btc\",\"eth\",\"xrp\"]', 12500000, 9001000, '1.00000000', 10000, '2023-03-08 19:14:23', 120, 0, '0.00000000', '10.00000000', '1000.00000000', '\r\n 	\r\n 		\r\n 			Arcona is an Augmented Reality Ecosystem which is based on the usage of blockchain technology to merge both the virtual world with the real world. Irrespective of location, users around the globe can link up with each other in this world of virtual and augmented reality - this enables the execution of virtual projects from anywhere around the globe without the need for users to leave the comfort of their homes.\r\n 		\r\n 		\r\n 			\r\n 		\r\n 	\r\n ', 'usd', '5.00000000', '0.00000000', '3.00000000', NULL, 1677825301, NULL, 1, '5af57bf59e408.png', 2, 1, 10, '', 'usd', '', '', '', '', '', '', 0, '0.00000000'),
(9, 'Electronium Launch', 'etn', 'usd', '[\"btc\",\"eth\",\"usdt\",\"dash\"]', 36800000, 16801460, '0.00100000', 10000, '2024-02-18 15:49:34', 1000, 0, '0.00000000', '10.00000000', '10000.00000000', '\r\n 	ICC Device is providing a hardware crypto wallet which enables you can&#39;t&nbsp;generate and create passwords when you need it. ICC Device is very particularly about the security of your crypto assets as such its products are of state-of-the-art and top notch secure technological standards.&nbsp; &nbsp;\r\n \r\n \r\n 	Hello\r\n \r\n \r\n 	It is also easy to use so that even when you forget your password, you are able to follow simple steps and perform tasks only known to you to recover your passwords and gain access to your digital assets.\r\n ', 'usd', '10.00000000', '0.00000000', '1.00000000', NULL, 1718465449, NULL, 1, '5af58aa8e4138.png', 2, 0, 0, 'HkglJzuuAcg', 'eth', 'alphax', 'alphax', 'blabber', 'https://www.facebook.com/alphaxofficialsupport/', 'https://twitter.com/alphax_official', 'https://t.me/alphaxcommunity', 51, '10.50000000'),
(11, 'Shuttle Swap', 'css', 'usdtr', NULL, 100000000, 150, '1.00000000', 50000000, '2023-05-21 00:22:04', 5, 0, '30.00000000', '500.00000000', '10000.00000000', 'Shuttle Swap\r\n Shuttle Swap is Part of Btcix Ecosystem. Btcix Ecosystem is a Type of Coin with an Evm-Based Structure and Can Carry 3 Modes. For example POA , POW , POS\r\n \r\n Coin Shuttle Swap [CSS] is a token without its own blockchain. The most actual price for one Coin Shuttle Swap [CSS] is $2.08. Coin Shuttle Swap is listed on 1 exchanges with a sum of 1 active markets. The 24h volume of [CSS] is $589 956, while the Coinamp;amp;nbsp;...', 'btcix', '0.00000000', '0.00000000', '0.00000000', NULL, 1684618032, NULL, 1, '645238c4d1355.png', 2, 1, 100, '', 'btcix', 'Coin Shuttle Swap', '', '', '#', '#', '#', 500, '0.00000000'),
(12, 'Lukoil', 'lukoil', 'btcix', NULL, 1000000, 5, '1.00000000', 500000, '2023-05-21 00:27:14', 5, 0, '0.00000000', '0.00000000', '0.00000000', '', 'btc', '0.00000000', '0.00000000', '0.00000000', NULL, 1684618227, NULL, 1, '64693b9e11848.png', 2, 1, 1, '', NULL, 'Lukoil To The Moon', NULL, NULL, '#', '#', '#', 0, '10.00000000');

-- --------------------------------------------------------

--
-- Table structure for table `codono_issue_log`
--

DROP TABLE IF EXISTS `codono_issue_log`;
CREATE TABLE IF NOT EXISTS `codono_issue_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `buycoin` varchar(50) DEFAULT NULL,
  `convertcurrency` varchar(50) DEFAULT NULL COMMENT 'Coin in which payment was made',
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `convert_price` decimal(20,8) DEFAULT NULL COMMENT 'converted payment',
  `num` int(20) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `ci` int(11) UNSIGNED DEFAULT NULL,
  `jian` varchar(255) DEFAULT NULL,
  `unlock` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb3 COMMENT='ICO record form';

--
-- Dumping data for table `codono_issue_log`
--

INSERT INTO `codono_issue_log` (`id`, `userid`, `name`, `coinname`, `buycoin`, `convertcurrency`, `price`, `convert_price`, `num`, `mum`, `ci`, `jian`, `unlock`, `sort`, `addtime`, `endtime`, `status`) VALUES
(19, 382, 'Token', 'bbt', 'usdt', 'usdt', '0.00000010', '0.00000000', 100, '0.00000000', 0, '0', 1, NULL, 1630140004, 1630140004, 0),
(20, 382, 'Token', 'bbt', 'usdt', 'usdt', '0.00000010', '0.00000010', 1001, '0.00010010', 0, '0', 1, NULL, 1630140207, 1630140207, 0),
(21, 382, 'ICC', 'etn', 'usd', 'eth', '100.00000000', '0.03086000', 10, '0.30860000', 0, '0', 1, NULL, 1630143419, 1630143419, 0),
(22, 382, 'Token', 'bbt', 'usdt', 'usdt', '0.00000010', '0.00000010', 1000333, '0.10003330', 0, '0', 1, NULL, 1630143438, 1630143438, 0),
(23, 382, 'ICC', 'etn', 'usd', 'eth', '100.00000000', '0.03440800', 10, '0.34408000', 0, '0', 1, NULL, 1633007436, 1633007436, 0),
(24, 38, 'ICC', 'etn', 'usd', 'eth', '0.00100000', '0.00000040', 10, '0.00000400', 0, '0', 1, NULL, 1643985306, 1643985306, 0),
(25, 38, 'ICC', 'etn', 'usd', 'eth', '0.00100000', '0.00000040', 10, '0.00000400', 0, '0', 1, NULL, 1643986280, 1643986280, 0),
(26, 38, 'ICC', 'etn', 'usd', 'eth', '0.00100000', '0.00000040', 10, '0.00000400', 0, '0', 1, NULL, 1643986481, 1643986481, 0),
(27, 38, 'ICC', 'etn', 'usd', 'btc', '0.00100000', '0.00000002', 11, '0.00000022', 0, '0', 1, NULL, 1643986888, 1643986888, 0),
(28, 38, 'ICC', 'etn', 'usd', 'trx', '0.00100000', '0.01806119', 1000, '18.06119000', 0, '0', 1, NULL, 1643989437, 1643989437, 0),
(29, 38, 'ICC', 'etn', 'usd', 'trx', '0.00100000', '0.01806119', 100, '1.80611900', 0, '0', 1, NULL, 1643989472, 1643989472, 0),
(30, 38, 'ICC', 'etn', 'usd', 'btc', '0.00100000', '0.00000004', 1000, '0.00004000', 0, '0.00000000', 1, NULL, 1677826317, 1677826317, 0),
(51, 38, 'Electronium Launch', 'etn', 'usd', 'usdt', '0.00100000', '0.00099899', 199, '0.19879901', 0, '0.00000000', 1, NULL, 1708342448, 1708342448, 0),
(55, 38, 'Electronium Launch', 'etn', 'usd', 'usdt', '0.00100000', '0.00099899', 100, '0.09989900', 0, '0.00000000', 1, NULL, 1708344806, 1708344806, 0),
(66, 38, 'Electronium Launch', 'etn', 'usd', 'usdt', '0.00100000', '0.00099899', 10, '0.00998990', 0, '0.00000000', 1, NULL, 1725261550, 1725261550, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_issue_timeline`
--

DROP TABLE IF EXISTS `codono_issue_timeline`;
CREATE TABLE IF NOT EXISTS `codono_issue_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) DEFAULT NULL,
  `phase_time` varchar(255) DEFAULT NULL,
  `phase_name` varchar(255) DEFAULT NULL,
  `phase_desc` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_issue_timeline`
--

INSERT INTO `codono_issue_timeline` (`id`, `issue_id`, `phase_time`, `phase_name`, `phase_desc`, `sort`, `status`) VALUES
(1, 5, '2018 Q4', 'Development', 'ICO Wallet Development', 0, 1),
(4, 5, 'Q4 2018', 'Wallet Realease', 'We will release wallet in this phase', 0, 1),
(9, 5, '2018 Q4', 'Development', 'ICO Wallet Development', 0, 1),
(10, 5, '2018 Q4', 'Developmentxe', 'ICO Wallet Development', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_lever`
--

DROP TABLE IF EXISTS `codono_lever`;
CREATE TABLE IF NOT EXISTS `codono_lever` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `currency` varchar(30) NOT NULL,
  `b_order` varchar(30) NOT NULL,
  `borrow` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `interest` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `interest_fee` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `new_price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `dy_coin` varchar(30) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `addtime` int(11) DEFAULT 0,
  `endtime` int(11) DEFAULT 0,
  `r_order` varchar(30) DEFAULT NULL,
  `repayment` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_lever`
--

INSERT INTO `codono_lever` (`id`, `userid`, `currency`, `b_order`, `borrow`, `interest`, `interest_fee`, `new_price`, `dy_coin`, `status`, `addtime`, `endtime`, `r_order`, `repayment`) VALUES
(1, 38, 'btc', '331674617624903', '5.02380000', '0.00251190', '0.05000000', '26924.79000000', 'btc', 1, 1680267461, 0, NULL, '0.00000000'),
(2, 38, 'btc', '331676965555228', '0.00200000', '0.00000100', '0.05000000', '26924.79000000', 'btc', 1, 1680267696, 0, NULL, '0.00000000'),
(3, 38, 'btc', '331677040576757', '0.00020000', '0.00000010', '0.05000000', '26924.79000000', 'btc', 1, 1680267704, 0, NULL, '0.00000000'),
(4, 38, 'btc', '331677090581377', '0.00020000', '0.00000010', '0.05000000', '26924.79000000', 'btc', 1, 1680267709, 0, NULL, '0.00000000'),
(5, 38, 'usdt', '331677143978270', '2.00000000', '0.00100000', '0.05000000', '0.00000000', 'usdt', 1, 1680267714, 0, NULL, '0.00000000');

-- --------------------------------------------------------

--
-- Table structure for table `codono_lever_coin`
--

DROP TABLE IF EXISTS `codono_lever_coin`;
CREATE TABLE IF NOT EXISTS `codono_lever_coin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(30) DEFAULT NULL,
  `market` varchar(30) DEFAULT NULL,
  `yue` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'avail balance quote example btc',
  `yued` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'frozen balance quote example btc',
  `p_yue` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'avail balance base example usdt',
  `p_yued` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'frozen balance base example usdt',
  `borrow` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `p_borrow` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `jz` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_lever_coin`
--

INSERT INTO `codono_lever_coin` (`id`, `userid`, `name`, `market`, `yue`, `yued`, `p_yue`, `p_yued`, `borrow`, `p_borrow`, `jz`) VALUES
(1, 38, NULL, 'btc_usdt', '5.58188690', '0.00000000', '1356.99900000', '0.00000000', '5.02368690', '1.99900000', '0.00000000');

-- --------------------------------------------------------

--
-- Table structure for table `codono_link`
--

DROP TABLE IF EXISTS `codono_link`;
CREATE TABLE IF NOT EXISTS `codono_link` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `img` varchar(200) DEFAULT NULL,
  `mytx` varchar(200) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb3 COMMENT='Common Bank Address';

--
-- Dumping data for table `codono_link`
--

INSERT INTO `codono_link` (`id`, `name`, `title`, `url`, `img`, `mytx`, `remark`, `sort`, `addtime`, `endtime`, `status`) VALUES
(50, 'Banner1', 'Banner1', 'https://codono.com', '1.png', NULL, NULL, 0, 1631030813, 1631030819, 1),
(51, 'Banner2', 'Banner2', 'https://codono.com', '2.png', NULL, NULL, 0, 1522762059, 1522762059, 1),
(52, 'Banner3', 'Banner3', 'https://codono.com', '3.png', NULL, NULL, 0, 1522762249, 1522762249, 1),
(53, 'Banner4', 'Banner4', '', '4.png', NULL, NULL, 0, 1631030883, 1631002087, 1),
(56, 'Banner5', 'Banner5', 'https://codono.com', '5.png', NULL, NULL, 0, 1631030813, 1631030819, 1),
(57, 'Banner6', 'Banner6', 'https://codono.com', '6.png', NULL, NULL, 0, 1522762059, 1522762059, 1),
(58, 'Banner7', 'Banner7', 'https://codono.com', '1.png', NULL, NULL, 0, 1522762249, 1522762249, 1),
(59, 'Banner8', 'Banner8', '', '4.png', NULL, NULL, 0, 1631030883, 1631002087, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_log`
--

DROP TABLE IF EXISTS `codono_log`;
CREATE TABLE IF NOT EXISTS `codono_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `num` int(20) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `unlock` int(11) UNSIGNED DEFAULT NULL,
  `ci` int(11) UNSIGNED DEFAULT NULL,
  `recycle` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_market`
--

DROP TABLE IF EXISTS `codono_market`;
CREATE TABLE IF NOT EXISTS `codono_market` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(25) DEFAULT NULL,
  `ownerid` int(11) NOT NULL DEFAULT 0 COMMENT 'ownerid where commission goes',
  `round` tinyint(2) UNSIGNED DEFAULT 8,
  `fee_buy` decimal(20,8) DEFAULT 0.00000000,
  `fee_sell` decimal(20,8) DEFAULT 0.00000000,
  `buy_min` decimal(20,8) DEFAULT 0.00000000,
  `buy_max` decimal(25,8) DEFAULT 0.00000000,
  `sell_min` decimal(25,8) DEFAULT 0.00000000,
  `sell_max` decimal(25,8) DEFAULT 0.00000000,
  `trade_min` decimal(25,8) DEFAULT 0.00000000,
  `trade_max` decimal(25,8) DEFAULT 0.00000000,
  `invit_buy` tinyint(1) DEFAULT 0,
  `invit_sell` tinyint(1) DEFAULT 0,
  `invit_1` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `invit_2` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `invit_3` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `zhang` varchar(255) DEFAULT NULL,
  `die` varchar(255) DEFAULT NULL,
  `hou_price` decimal(25,8) DEFAULT 0.00000000,
  `tendency` varchar(1000) DEFAULT NULL,
  `trade` int(11) UNSIGNED DEFAULT 0,
  `new_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `buy_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `sell_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `min_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `max_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `volume` decimal(40,12) DEFAULT 0.000000000000,
  `change` decimal(20,8) DEFAULT 0.00000000,
  `api_min` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
  `api_max` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `api_max_qty` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'max qty for selfengine ',
  `begintrade` varchar(8) DEFAULT '00:00:00',
  `endtrade` varchar(8) DEFAULT '23:59:59',
  `sort` int(11) UNSIGNED DEFAULT 0,
  `addtime` int(11) UNSIGNED DEFAULT 0,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `switch` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=only market trade, 1 all on',
  `auto_match` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'use automatic matching engine',
  `jiaoyiqu` tinyint(1) DEFAULT NULL,
  `market_ico_price` float(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Issue price',
  `ext_price_update` tinyint(1) NOT NULL DEFAULT 1,
  `ext_fake_trades` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'generate  fake trade logs',
  `ext_orderbook` tinyint(1) NOT NULL DEFAULT 0,
  `socket_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=ajax 1=huobi 2=selfengine',
  `socket_pair` varchar(20) DEFAULT NULL COMMENT 'external market pair mapping',
  `orderbook_markup` decimal(10,6) DEFAULT 0.000000,
  `ext_charts` tinyint(1) NOT NULL DEFAULT 0,
  `charts_symbol` varchar(35) DEFAULT NULL,
  `xtrade` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'allow cross trading',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=487 DEFAULT CHARSET=utf8mb3 COMMENT='Quotes configuration table';

--
-- Dumping data for table `codono_market`
--

INSERT INTO `codono_market` (`id`, `name`, `ownerid`, `round`, `fee_buy`, `fee_sell`, `buy_min`, `buy_max`, `sell_min`, `sell_max`, `trade_min`, `trade_max`, `invit_buy`, `invit_sell`, `invit_1`, `invit_2`, `invit_3`, `zhang`, `die`, `hou_price`, `tendency`, `trade`, `new_price`, `buy_price`, `sell_price`, `min_price`, `max_price`, `volume`, `change`, `api_min`, `api_max`, `api_max_qty`, `begintrade`, `endtrade`, `sort`, `addtime`, `endtime`, `status`, `switch`, `auto_match`, `jiaoyiqu`, `market_ico_price`, `ext_price_update`, `ext_fake_trades`, `ext_orderbook`, `socket_type`, `socket_pair`, `orderbook_markup`, `ext_charts`, `charts_symbol`, `xtrade`) VALUES
(5, 'eth_try', 0, 3, '0.12500000', '0.13000000', '0.01000000', '10000000.00000000', '0.00000100', '10000000.00000000', '0.00000100', '10000000.00000000', 0, 0, '3.0000', '2.0000', '1.0000', '', '', NULL, '[[1665732676,0],[1665747076,0],[1665761476,0],[1665775876,0],[1665790276,0],[1665804676,0],[1665819076,0],[1665833476,0],[1665847876,0],[1665862276,0],[1665876676,0],[1665891076,0],[1665905476,0],[1665919876,0],[1665934276,0],[1665948676,0],[1665963076,0],[1665977476,0],[1665991876,0]]', 1, '113606.00000000', '113608.00000000', '113592.00000000', '112859.00000000', '118867.00000000', '955.551300000000', '-0.40900000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, 0, 1, 1, 1, 3, 0.00, 1, 0, 1, 2, 'ethusdt', '2.000000', 1, 'ETHTRY', 1),
(6, 'etc_try', 0, 3, '0.00000001', '0.00000000', '0.01000000', '10000000.00000000', '0.01000000', '10000000.00000000', '100.00000000', '10000000.00000000', 1, 1, '3.0000', '2.0000', '1.0000', '', '', NULL, '[[1665732676,0],[1665747076,0],[1665761476,0],[1665775876,0],[1665790276,0],[1665804676,0],[1665819076,0],[1665833476,0],[1665847876,0],[1665862276,0],[1665876676,0],[1665891076,0],[1665905476,0],[1665919876,0],[1665934276,0],[1665948676,0],[1665963076,0],[1665977476,0],[1665991876,0]]', 1, '837.80000000', '834.60000000', '831.40000000', '827.30000000', '867.00000000', '1185.525000000000', '-0.25000000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, 0, 1, 1, 1, 3, 0.00, 1, 0, 1, 2, '', '2.000000', 1, 'ETCTRY', 0),
(7, 'ltc_try', 0, 3, '0.10000000', '0.12500000', '0.01000000', '10000000.00000000', '0.01000000', '10000000.00000000', '100.00000000', '10000000.00000000', 1, 1, '3.0000', '2.0000', '1.0000', '', '', NULL, '[[1665732676,0],[1665747076,0],[1665761476,0],[1665775876,0],[1665790276,0],[1665804676,0],[1665819076,0],[1665833476,0],[1665847876,0],[1665862276,0],[1665876676,0],[1665891076,0],[1665905476,0],[1665919876,0],[1665934276,0],[1665948676,0],[1665963076,0],[1665977476,0],[1665991876,0]]', 1, '122.40000000', '122.40000000', '122.30000000', '110.30000000', '123.20000000', '658416.303000000000', '8.70300000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, 0, 1, 1, 1, 3, 0.00, 1, 0, 2, 0, NULL, '2.000000', 1, 'LTCTRY', 0),
(13, 'eth_btc', 0, 6, '0.90000000', '0.10000000', '0.01000000', '10000000.00000000', '0.01000000', '10000000.00000000', '0.01000000', '10000000.00000000', 0, 0, '3.0000', '2.0000', '1.0000', '', '', NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '0.05190000', '0.05191000', '0.05190000', '0.05168000', '0.05253000', '30937.582000000000', '-0.51800000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 2, 0.00, 1, 0, 1, 0, '', '2.000000', 1, 'ETHBTC', 0),
(15, 'ltc_btc', 0, 8, '0.01000000', '0.02000000', '0.00010000', '10000000.00000000', '0.00010000', '10000000.00000000', '0.00100000', '10000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '0.00000000', '97.91180000', '104.15444400', '0.00000000', '0.00000000', '0.000000000000', '0.00000000', '100.00000000', '102.00000000', '30.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 1, 0.00, 1, 0, 2, 0, '', '2.000000', 1, '', 0),
(16, 'doge_btc', 0, 8, '0.15000000', '0.15000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000.00000000', '0.00000001', '1000000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '0.00000212', '0.00000212', '0.00000211', '0.00000205', '0.00000217', '40194879.000000000000', '2.91300000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 1, 0.00, 1, 0, 1, 0, '', '2.000000', 1, '', 0),
(18, 'btc_usdt', 0, 8, '0.10000000', '0.10000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '67523.98000000', '67523.99000000', '67523.98000000', '66969.98000000', '69999.00000000', '39561.424400000000', '0.38800000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 0, 0.00, 1, 1, 1, 2, '', '1.522200', 1, 'BINANCE:BTCUSDT', 0),
(19, 'eth_usdt', 0, 8, '0.10000000', '0.10000000', '0.10000000', '10000000.00000000', '0.10000000', '10000000.00000000', '0.10000000', '10000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '3505.20000000', '3505.20000000', '3505.19000000', '3476.21000000', '3659.01000000', '411678.711200000000', '-0.10100000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 0, 0.00, 1, 1, 1, 2, '', '0.000000', 1, 'BINANCE:ETHUSDT', 0),
(20, 'bbt_usdt', 0, 8, '0.10000000', '0.10000000', '0.00001000', '1000000.00000000', '0.00001000', '1000000.00000000', '0.00001000', '100000000.00000000', 0, 0, '0.0000', '0.0000', '0.0000', NULL, NULL, NULL, '[[1665732677,0],[1665747077,0],[1665761477,0],[1665775877,0],[1665790277,0],[1665804677,0],[1665819077,0],[1665833477,0],[1665847877,0],[1665862277,0],[1665876677,0],[1665891077,0],[1665905477,0],[1665919877,0],[1665934277,0],[1665948677,0],[1665963077,0],[1665977477,0],[1665991877,0]]', 1, '60.36379111', '61.90904150', '64.15340705', '60.36379111', '60.36379111', '58.000000000000', '-7.68000000', '62.03000000', '64.00300000', '1000.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 0, 0, 0, 0.00, 1, 1, 2, 1, '', '0.000000', 0, '', 0),
(21, 'usdt_rub', 0, 8, '0.10000000', '0.10000000', '0.00000010', '100000.00000000', '0.00000010', '1000000.00000000', '0.00000010', '100000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', NULL, NULL, NULL, NULL, 1, '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '0.000000000000', '1.00400000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 0, 0.00, 1, 1, 1, 0, 'USDTRUB', '0.500000', 1, 'USDTRUB', 1),
(23, 'ada_usdt', 0, 6, '1.00000000', '1.00000000', '0.02000000', '100000.00000000', '0.02000000', '100000.00000000', '0.01000000', '1000.00000000', 0, 0, '0.0000', '0.0000', '0.0000', NULL, NULL, '0.00000000', NULL, 1, '0.02000000', '0.00000000', '0.02000000', '0.02000000', '0.02099900', '14395.342248000000', '0.00000000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, 0, NULL, 1, 1, 1, 0, 0.00, 0, 0, 0, 0, '', '0.000000', 0, '', 0),
(485, 'bnb_usdt', 0, 8, '0.10000000', '0.10000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '', 1, '608.70000000', '608.70000000', '608.60000000', '602.80000000', '635.40000000', '454754.301000000000', '-0.16400000', '0.00000000', '0.00000000', '0.00000000', '00:00:00', '23:59:00', 0, NULL, NULL, 1, 1, 1, 1, 0.00, 1, 1, 1, 2, '1', '1.522200', 1, 'BNBUSDT', 1),
(486, 'zb_usdt', 0, 8, '0.10000000', '0.10000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000.00000000', '0.00000001', '10000000000.00000000', 1, 1, '0.0000', '0.0000', '0.0000', '', '', NULL, '', 1, '0.00000000', '0.01653623', '0.01763429', '0.00000000', '0.00000000', '0.000000000000', '0.00000000', '0.01681707', '0.01733695', '34.04715512', '00:00:00', '23:59:00', 0, NULL, NULL, 1, 1, 1, 0, 0.00, 1, 0, 2, 2, '', '1.522200', 1, 'ZBUSDT', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_market_json`
--

DROP TABLE IF EXISTS `codono_market_json`;
CREATE TABLE IF NOT EXISTS `codono_market_json` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_market_json`
--

INSERT INTO `codono_market_json` (`id`, `name`, `data`, `type`, `sort`, `addtime`, `endtime`, `status`) VALUES
(2, 'btcix_usdt', '[\"1830.42616272\",\"1938.78492233\",\"1.93878478\",\"1.93878478\"]', NULL, NULL, 1641686399, NULL, NULL),
(4, 'trx_usdt', '[\"61.62731500\",\"6.29644058\",\"0.00629644\",\"0.00629644\"]', NULL, NULL, 1630713599, NULL, NULL),
(5, 'btcix_usdt', '[\"0.00000306\",\"0.00000309\",\"0.00000000\",\"0.00000000\"]', NULL, NULL, 1641772799, NULL, NULL),
(6, 'btcix_usdt', '[\"24444.04800000\",\"24822.19494876\",\"24.82219364\",\"24.82219364\"]', NULL, NULL, 1641859199, NULL, NULL),
(8, 'btc_usdt', '[\"10.00000000\",\"454841.75680399\",\"454.84175675\",\"454.84175675\"]', NULL, NULL, 1631231999, NULL, NULL),
(10, 'eth_usd', '[\"42.94745000\",\"148745.42219000\",\"14.87454221\",\"14.87454221\"]', NULL, NULL, 1634169599, NULL, NULL),
(75, 'eth_try', '', NULL, NULL, 259199, NULL, NULL),
(76, 'etc_try', '', NULL, NULL, 259199, NULL, NULL),
(78, 'eth_btc', '', NULL, NULL, 259199, NULL, NULL),
(79, 'ltc_btc', '', NULL, NULL, 259199, NULL, NULL),
(80, 'doge_btc', '', NULL, NULL, 345599, NULL, NULL),
(90, 'usdt_rub', '[\"1714.30730000\",\"161279.15580610\",\"0.00000000\",\"0.00000000\"]', NULL, NULL, 1698278399, NULL, NULL),
(94, 'eth_usdt', '[\"10.81579000\",\"19397.35972923\",\"0.00000000\",\"0.00000000\"]', NULL, NULL, 1698278399, NULL, NULL),
(95, 'bbt_usdt', '[\"20015.84348000\",\"1998026.76829654\",\"1686.90164795\",\"1686.90164795\"]', NULL, NULL, 1698278399, NULL, NULL),
(96, 'bbt_usdt', '', NULL, NULL, 1698364799, NULL, NULL),
(97, 'eth_usdt', '', NULL, NULL, 1698451199, NULL, NULL),
(119, 'btc_usdt', '[\"6.24104000\",\"230012.69220316\",\"0.00000000\",\"0.00000000\"]', NULL, NULL, 1700783999, NULL, NULL),
(120, 'btc_usdt', '', NULL, NULL, 1701215999, NULL, NULL),
(121, 'btc_usdt', '[\"4.96945930\",\"177617.88128552\",\"0.05625734\",\"0.05625734\"]', NULL, NULL, 1701129599, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_menu`
--

DROP TABLE IF EXISTS `codono_menu`;
CREATE TABLE IF NOT EXISTS `codono_menu` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'FileID',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT 'title',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sub-headingsID',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sort (effectively the same level)',
  `url` char(255) NOT NULL DEFAULT '' COMMENT 'link address',
  `hide` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Whether to hide',
  `tip` varchar(255) NOT NULL DEFAULT '' COMMENT 'prompt',
  `group` varchar(50) DEFAULT '' COMMENT 'Packet',
  `is_dev` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Whether visible only developer mode',
  `ico_name` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=510 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_menu`
--

INSERT INTO `codono_menu` (`id`, `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`, `ico_name`) VALUES
(1, 'Dashboard', 0, 1, 'Index/index', 0, '', '', 0, 'bx bx-layout'),
(2, 'Content', 0, 1, 'Article/index', 0, '', '', 0, 'bx bx-detail'),
(3, 'User', 0, 1, 'User/index', 0, '', '', 0, 'bx bx-user-circle'),
(4, 'Finance', 0, 1, 'Finance/index', 0, '', '', 0, 'bx bx-receipt'),
(5, 'Trade', 0, 1, 'Trade/index', 0, '', '', 0, 'bx bx-transfer'),
(6, 'Community', 0, 1, 'Vote/index', 0, '', '', 0, 'bx bx-group'),
(7, 'Config', 0, 80, 'Config/index', 0, '', '', 0, 'bx bx-cog'),
(8, 'Bonus Logs', 4, 100, 'Operate/index', 0, '', 'Financial', 0, 'share'),
(9, 'Tools', 0, 90, 'Tools/index', 0, '', '', 0, 'bx bx-wrench'),
(10, 'Email', 2, 3, 'Email/index', 0, '', NULL, 0, 'bx bx-mail-send'),
(11, 'Dashboard', 1, 1, 'Index/index', 0, '', NULL, 0, 'home'),
(12, 'Market', 1, 3, 'Index/market', 0, '', NULL, 0, 'home'),
(13, 'Articles', 2, 1, 'Article/index', 0, '', NULL, 0, 'list-alt'),
(14, 'Edit Add', 13, 1, 'Article/edit', 1, '', NULL, 0, 'home'),
(15, 'Modify status', 13, 100, 'Article/status', 1, '', NULL, 0, 'home'),
(16, 'Upload image', 13, 2, 'Article/images', 1, '', NULL, 0, '0'),
(18, 'Edit', 17, 2, 'Adver/edit', 1, '', NULL, 0, '0'),
(19, 'Modify', 17, 2, 'Adver/status', 1, '', NULL, 0, '0'),
(21, 'Transfer/Staking Income', 1, 4, 'Fees/income', 0, '', NULL, 0, 'home'),
(22, 'Modify', 20, 3, 'Chat/status', 1, '', 'Chat', 0, '0'),
(23, 'Text Tips', 2, 1, 'Text/index', 1, '', 'Tips', 0, 'exclamation-sign'),
(24, 'Edit', 23, 1, 'Text/edit', 1, '', 'Tips', 0, '0'),
(25, 'Modify', 23, 1, 'Text/status', 1, '', 'Tips', 0, '0'),
(26, 'Users', 3, 1, 'User/index', 0, '', 'User', 0, 'user'),
(39, 'New User Group', 38, 0, 'AuthManager/createGroup', 1, '', 'authority management', 0, '0'),
(40, 'Edit User Groups', 38, 0, 'AuthManager/editgroup', 1, '', 'authority management', 0, '0'),
(41, 'Update User Group', 38, 0, 'AuthManager/writeGroup', 1, '', 'authority management', 0, '0'),
(42, 'Change state', 38, 0, 'AuthManager/changeStatus', 1, '', 'authority management', 0, '0'),
(43, 'Access authorization', 38, 0, 'AuthManager/access', 1, '', 'authority management', 0, '0'),
(44, 'Auth Category', 38, 0, 'AuthManager/category', 1, '', 'authority management', 0, '0'),
(45, 'Members of the authorized', 38, 0, 'AuthManager/user', 1, '', 'authority management', 0, '0'),
(46, 'Members of the list of authorized', 38, 0, 'AuthManager/tree', 1, '', 'authority management', 0, '0'),
(47, 'user group', 38, 0, 'AuthManager/group', 1, '', 'authority management', 0, '0'),
(48, 'Added to the user group', 38, 0, 'AuthManager/addToGroup', 1, '', 'authority management', 0, '0'),
(49, 'User group removed', 38, 0, 'AuthManager/removeFromGroup', 1, '', 'authority management', 0, '0'),
(50, 'Classified added to the user group', 38, 0, 'AuthManager/addToCategory', 1, '', 'authority management', 0, '0'),
(51, 'Model added to the user group', 38, 0, 'AuthManager/addToModel', 1, '', 'authority management', 0, '0'),
(53, 'Configuration', 52, 1, 'Finance/config', 1, '', 'Finance Config', 0, '0'),
(55, 'Types of', 52, 1, 'Finance/type', 1, '', 'Finance Type', 0, '0'),
(56, 'Modify status', 52, 1, 'Finance/type_status', 1, '', 'Finance Status', 0, '0'),
(60, 'modify', 57, 3, 'Mycz/status', 1, '', 'Top management', 0, '0'),
(61, 'Modify status', 57, 3, 'Mycztype/status', 1, '', 'Top management', 0, '0'),
(64, 'Modify status', 62, 5, 'Mytx/status', 1, '', 'Withdraw management', 0, '0'),
(65, 'cancel', 62, 5, 'Mytx/excel', 1, '', 'Withdraw management', 0, '0'),
(66, 'Importingexcel', 9, 5, 'Mytx/exportExcel', 1, '', 'Withdraw management', 0, '0'),
(68, 'Trade', 5, 1, 'Trade/index', 0, '', 'Trade', 0, 'stats'),
(69, 'Logs', 5, 2, 'Trade/log', 0, '', 'Trade', 0, 'stats'),
(70, 'Modify status', 68, 0, 'Trade/status', 1, '', 'Trade', 0, '0'),
(71, 'Revoked pending', 68, 0, 'Trade/reject', 1, '', 'Trade', 0, '0'),
(74, 'Edit ICO', 455, 2, 'Issue/edit', 1, '', 'ICO', 0, '0'),
(75, 'Modify ICO', 455, 2, 'Issue/status', 1, '', 'ICO', 0, '0'),
(79, 'Basic', 7, 1, 'Config/index', 0, '', NULL, 0, 'cog'),
(80, 'SMS', 7, 2, 'Config/cellphone', 0, '', NULL, 0, 'cog'),
(81, 'Support', 7, 3, 'Config/contact', 0, '', NULL, 0, 'cog'),
(82, 'Wallet Transfer Fees', 7, 2, 'Fees/index', 0, '', NULL, 0, 'cog'),
(85, 'edit', 84, 4, 'Coin/edit', 0, '', 'Profiles', 0, '0'),
(87, 'Modify status', 84, 4, 'Coin/status', 1, '', 'Profiles', 0, '0'),
(89, 'Editing Market', 88, 4, 'Market/edit', 0, '', 'Market', 0, '0'),
(92, 'Captcha', 95, 7, 'Verify/code', 1, '', 'Profiles', 0, '0'),
(93, 'Phone code', 95, 7, 'Verify/mobile', 1, '', 'Profiles', 0, '0'),
(94, 'Mail Code', 95, 7, 'Verify/email', 0, '', 'Profiles', 0, '0'),
(95, 'Misc Config', 7, 6, 'Config/misc', 0, '', NULL, 0, 'cog'),
(96, 'Other Config', 7, 7, 'Options/index', 0, '', NULL, 0, 'cog'),
(97, 'Promotion', 8, 2, 'Invit/config', 0, '', 'Promotion', 0, 'cog'),
(101, 'Other module calls', 9, 4, 'Tools/invoke', 1, '', 'other', 0, '0'),
(102, 'Table Optimization', 9, 4, 'Tools/optimize', 1, '', 'other', 0, '0'),
(103, 'Repair Tables', 9, 4, 'Tools/repair', 1, '', 'other', 0, '0'),
(104, 'Removing Backup Files', 9, 4, 'Tools/del', 1, '', 'other', 0, '0'),
(105, 'backup database', 9, 4, 'Tools/export', 1, '', 'other', 0, ''),
(106, 'Restore Database', 9, 4, 'Tools/import', 1, '', 'other', 0, '0'),
(107, 'Export Database', 9, 4, 'Tools/excel', 1, '', 'other', 0, '0'),
(108, 'Export Excel', 9, 4, 'Tools/exportExcel', 1, '', 'other', 0, '0'),
(109, 'ImportingExcel', 9, 4, 'Tools/importExecl', 1, '', 'other', 0, '0'),
(115, 'image', 111, 0, 'Shop/images', 0, '', 'Store', 0, '0'),
(116, 'Menu Manager', 7, 5, 'Menu/index', 1, '', 'Admin Config', 0, 'list'),
(117, 'Sequence', 116, 5, 'Menu/sort', 1, '', 'Admin Config', 0, '0'),
(118, 'Add to', 116, 5, 'Menu/add', 1, '', 'Admin Config', 0, '0'),
(119, 'Edit', 116, 5, 'Menu/edit', 1, '', 'Admin Config', 0, '0'),
(120, 'Delete', 116, 5, 'Menu/del', 1, '', 'Admin Config', 0, '0'),
(121, 'Hide', 116, 5, 'Menu/toogleHide', 1, '', 'Admin Config', 0, '0'),
(122, 'Development', 116, 5, 'Menu/toogleDev', 1, '', 'Admin Config', 0, '0'),
(123, 'Import File', 7, 5, 'Menu/importFile', 1, '', 'Admin Config', 0, 'log-in'),
(124, 'Importing', 7, 5, 'Menu/import', 1, '', 'Admin Config', 0, 'log-in'),
(127, 'User login', 3, 0, 'Login/index', 1, '', 'User Configuration', 0, '0'),
(128, 'User exits', 3, 0, 'Login/loginout', 1, '', 'User Configuration', 0, '0'),
(129, 'Change Password', 3, 0, 'User/setpwd', 1, '', 'Admin', 0, 'home'),
(131, 'User Details', 3, 4, 'User/detail', 1, '', 'Frontend user management', 0, 'time'),
(138, 'edit', 2, 1, 'Articletype/edit', 1, '', 'Content Management', 0, 'list-alt'),
(140, 'edit', 139, 2, 'Link/edit', 1, '', 'Content Management', 0, '0'),
(141, 'modify', 139, 2, 'Link/status', 1, '', 'Content Management', 0, '0'),
(155, 'Server queue', 9, 3, 'Tools/queue', 1, '', 'Tools', 0, 'wrench'),
(156, 'Check the wallet', 9, 3, 'Tools/wallet', 1, '', 'Tools', 0, 'wrench'),
(157, 'Coin stats', 1, 2, 'Index/coin', 0, '', NULL, 0, 'home'),
(163, 'Tips', 7, 5, 'Config/text', 0, '', NULL, 0, 'cog'),
(220, 'Coin Reviews', 5, 4, 'Trade/comment', 0, '', 'Trade', 0, 'stats'),
(278, 'Categories', 2, 2, 'Article/type', 0, '', NULL, 0, 'list-alt'),
(279, 'Big Slider', 2, 3, 'Article/adver', 0, '', NULL, 0, 'list-alt'),
(280, 'Small Slider', 2, 4, 'Article/link', 0, '', NULL, 0, 'list-alt'),
(281, 'Signup Attempts', 3, 4, 'User/signup_log', 0, '', 'User', 0, 'user'),
(282, 'Signin Log', 3, 4, 'User/log', 0, '', 'User', 0, 'user'),
(283, 'Users wallet', 3, 5, 'User/wallet', 0, '', 'User', 0, 'user'),
(284, 'Withdraw Address', 3, 6, 'User/bank', 0, '', 'User', 0, 'user'),
(285, 'User Spot Balances', 3, 7, 'User/coin', 0, '', 'User', 0, 'user'),
(286, 'Address', 3, 9, 'User/goods', 0, '', 'User', 0, 'user'),
(287, 'Chat', 5, 3, 'Trade/chat', 0, '', 'Trade', 0, 'stats'),
(288, 'Market', 5, 5, 'Trade/market', 0, '', 'Trade', 0, 'stats'),
(289, 'Invite', 5, 6, 'Trade/invit', 0, '', 'Trade', 0, 'stats'),
(290, 'Financial details', 4, 1, 'Finance/index', 0, '', 'Financial', 0, 'th-list'),
(291, 'Fiat Deposit', 4, 2, 'Finance/mycz', 0, '', 'Fiat', 0, 'th-list'),
(292, 'Payment Gateways', 4, 3, 'Finance/myczType', 0, '', 'Fiat', 0, 'th-list'),
(293, 'Fiat Withdrawal', 4, 4, 'Finance/mytx', 0, '', 'Fiat', 0, 'th-list'),
(294, 'Transfers', 4, 2, 'Fees/transfers', 0, 'Financial', '0', 0, 'th-list'),
(295, 'Crypto Deposit', 4, 6, 'Finance/myzr', 0, '', 'Financial', 0, 'th-list'),
(296, 'Crypto Withdraw', 4, 7, 'Finance/myzc', 0, '', 'Financial', 0, 'th-list'),
(297, 'Modify status', 291, 100, 'Finance/myczStatus', 1, '', 'Financial', 0, 'home'),
(298, 'Confirm arrival', 291, 100, 'Finance/myczConfirm', 1, '', 'Financial', 0, 'home'),
(299, 'Edit Add', 292, 1, 'Finance/myczTypeEdit', 1, '', 'Financial', 0, 'home'),
(300, 'Modify status', 292, 2, 'Finance/myczTypeStatus', 1, '', 'Financial', 0, 'home'),
(301, 'upload image', 292, 2, 'Finance/myczTypeImage', 1, '', 'Financial', 0, 'home'),
(302, 'Modify status', 293, 2, 'Finance/mytxStatus', 1, '', 'Financial', 0, 'home'),
(303, 'Export selected', 293, 3, 'Finance/mytxExcel', 1, '', 'Financial', 0, 'home'),
(304, 'Processing', 293, 4, 'Finance/mytxChuli', 1, '', 'Financial', 0, 'home'),
(305, 'Undo withdrawals', 293, 5, 'Finance/mytxReject', 1, '', 'Financial', 0, 'home'),
(306, 'Confirm Withdraw', 293, 6, 'Finance/mytxConfirm', 1, '', 'Financial', 0, 'home'),
(307, 'Confirm turn out', 296, 6, 'Finance/myzcConfirm', 1, '', 'Financial', 0, 'home'),
(309, 'Clear cache', 9, 1, 'Tools/index', 0, '', NULL, 0, 'wrench'),
(310, 'Backup Database', 9, 2, 'Tools/dataExport', 1, '', 'Tools', 0, 'wrench'),
(311, 'Restore Database', 9, 2, 'Tools/dataImport', 1, '', 'Tools', 0, 'wrench'),
(312, 'Admins', 3, 2, 'User/admin', 0, '', 'Admin', 0, 'user'),
(313, 'Permissions', 3, 3, 'User/auth', 0, '', 'Admin', 0, 'user'),
(314, 'Edit Add', 26, 1, 'User/edit', 0, '', 'User', 0, 'home'),
(315, 'Modify status', 26, 1, 'User/status', 0, '', 'User', 0, 'home'),
(316, 'Edit Add', 312, 1, 'User/adminEdit', 1, '', 'Admin', 0, 'home'),
(317, 'Modify status', 312, 1, 'User/adminStatus', 1, '', 'Admin', 0, 'home'),
(318, 'Edit Add', 313, 1, 'User/authEdit', 1, '', 'Admin', 0, 'home'),
(319, 'Modify status', 313, 1, 'User/authStatus', 1, '', 'Admin', 0, 'home'),
(320, 'Permission to re-initialize', 313, 1, 'User/authStart', 1, '', 'User', 0, 'home'),
(321, 'Edit Add', 282, 1, 'User/logEdit', 1, '', 'User', 0, 'home'),
(322, 'Modify status', 282, 1, 'User/logStatus', 1, '', 'User', 0, 'home'),
(323, 'Edit Add', 283, 1, 'User/walletEdit', 1, '', 'User', 0, 'home'),
(324, 'Modify status', 283, 1, 'User/walletStatus', 1, '', 'User', 0, 'home'),
(325, 'Edit Add', 284, 1, 'User/bankEdit', 1, '', 'User', 0, 'home'),
(326, 'Modify status', 284, 1, 'User/bankStatus', 1, '', 'User', 0, 'home'),
(327, 'Edit Add', 285, 1, 'User/coinEdit', 1, '', 'User', 0, 'home'),
(328, 'Coin Log', 285, 1, 'User/coinLog', 1, '', 'User', 0, 'home'),
(329, 'Edit Add', 286, 1, 'User/goodsEdit', 1, '', 'User', 0, 'home'),
(330, 'Modify status', 286, 1, 'User/goodsStatus', 1, '', 'User', 0, 'home'),
(331, 'Edit Add', 278, 1, 'Article/typeEdit', 1, '', 'Articles', 0, 'home'),
(332, 'Modify status', 278, 100, 'Article/typeStatus', 1, '', 'Articles', 0, 'home'),
(333, 'Edit Add', 280, 1, 'Article/linkEdit', 1, '', 'Content', 0, 'home'),
(334, 'Modify status', 280, 100, 'Article/linkStatus', 1, '', 'Content', 0, 'home'),
(335, 'Edit Add', 279, 1, 'Article/adverEdit', 1, '', 'Content', 0, 'home'),
(336, 'Modify status', 279, 100, 'Article/adverStatus', 1, '', 'Content', 0, 'home'),
(337, 'Image Update', 279, 100, 'Article/adverImage', 1, '', 'Content', 0, 'home'),
(377, 'Access authorization', 313, 1, 'User/authAccess', 1, '', 'Admin', 0, 'home'),
(378, 'Access unauthorized modification', 313, 1, 'User/authAccessUp', 1, '', 'Admin', 0, 'home'),
(379, 'Members of the authorized', 313, 1, 'User/authUser', 1, '', 'Admin', 0, 'home'),
(380, 'Members of the authorized increase', 313, 1, 'User/authUserAdd', 1, '', 'Admin', 0, 'home'),
(381, 'Members of the authorized lifted', 313, 1, 'User/authUserRemove', 1, '', 'Admin', 0, 'home'),
(382, 'Coins', 7, 4, 'Config/coin', 0, '', NULL, 0, 'cog'),
(383, 'Promotion award', 8, 1, 'Operate/index', 0, '', '', 0, 'share'),
(384, 'APP Config', 8, 1, 'App/config', 0, '', 'APP', 0, 'time'),
(385, 'APP VIP', 8, 2, 'App/vip_config_list', 0, '', 'APP', 0, 'time'),
(386, 'WAP Banners', 8, 3, 'Admin/App/ads_list/block_id/1', 0, '', 'WAP Banners', 0, 'time'),
(387, 'APP Ads', 8, 4, 'App/ads_user', 0, '', 'APP management', 0, 'time'),
(388, 'Top Menu', 7, 7, 'Config/navigation', 0, '', NULL, 0, 'cog'),
(389, 'Footer Menu', 7, 7, 'Config/footer', 0, '', NULL, 0, 'cog'),
(425, 'Products', 6, 1, 'Shop/index', 0, '', 'Store', 0, 'globe'),
(426, 'Config', 6, 2, 'Shop/config', 0, '', 'Store', 0, 'globe'),
(427, 'Categories', 6, 3, 'Shop/type', 0, '', 'Store', 0, 'globe'),
(428, 'Payment method', 6, 4, 'Shop/coin', 0, '', 'Store', 0, 'globe'),
(429, 'Orders', 6, 5, 'Shop/log', 0, '', 'Store', 0, 'globe'),
(430, 'Shipping address', 6, 6, 'Shop/goods', 0, '', 'Store', 0, 'globe'),
(433, 'Airdrop', 6, 3, 'Dividend/index', 0, '', 'Airdrop', 0, 'plane'),
(434, 'Records', 6, 5, 'Dividend/log', 0, '', 'Airdrop', 0, 'th-list'),
(435, 'Recharge record', 6, 1, 'Topup/index', 1, '', 'Prepaid recharge', 0, 'globe'),
(436, 'Recharge Configuration', 6, 1, 'Topup/config', 1, '', 'Prepaid recharge', 0, 'globe'),
(437, 'Recharge amount', 6, 3, 'Topup/type', 1, '', 'Prepaid recharge', 0, 'globe'),
(438, 'Topup method', 6, 4, 'Topup/coin', 1, '', 'Prepaid recharge', 0, 'globe'),
(439, 'Voting Record', 6, 1, 'Vote/index', 0, '', 'Voting', 0, 'globe'),
(440, 'Voting type', 6, 1, 'Vote/type', 0, '', 'Voting', 0, 'globe'),
(441, 'Money Management', 6, 1, 'Money/index', 1, '', 'Money Management', 0, 'globe'),
(442, 'Money Log', 6, 2, 'Money/log', 1, '', 'Money Management', 0, 'globe'),
(443, 'Financial details', 6, 3, 'Money/fee', 1, '', 'Money Management', 0, 'globe'),
(448, 'ICO', 455, 1, 'Issue/index', 0, '', 'ICO', 0, 'globe'),
(449, 'Records', 455, 1, 'Issue/log', 0, '', 'ICO', 0, 'globe'),
(450, 'User Other Balances', 3, 8, 'User/assets', 0, '', 'User', 0, 'user'),
(452, 'Faucet', 6, 1, 'Faucet/index', 0, '', 'Faucet', 0, 'tree-deciduous'),
(453, 'Logs', 6, 1, 'Faucet/log', 0, '', 'Faucet', 0, 'tree-deciduous'),
(455, 'Earn', 0, 70, 'Staking/index', 0, '', '', 0, 'bx bx-server'),
(457, 'Staking', 455, 0, 'Staking/Index', 0, '', 'Staking', 0, '0'),
(458, 'Staking Logs', 455, 0, 'Staking/stakingLog', 0, '', 'Staking', 0, 'bank'),
(459, 'DiceRolls', 455, 0, 'Staking/dicerolls', 0, '', 'Staking', 0, 'dice'),
(460, 'Supported Banks', 4, 10, 'Bank/Index', 0, 'Bank for withdrawal', 'Fiat', 0, '0'),
(469, 'OTC plans', 5, 9, 'Otc/index', 0, '', 'OTC', 0, '0'),
(470, 'OTC logs', 5, 11, 'Otc/log', 0, '', 'OTC', 0, '0'),
(471, 'Roadmap', 2, 0, 'Misc/roadmap', 1, '', 'Content', 0, '0'),
(472, 'Additional Bonus', 7, 0, 'Misc/bonus', 1, '', NULL, 0, '0'),
(473, 'Do Deposit/Withdraw', 4, 10, 'Activity/index', 0, 'Activity', 'Financial', 0, 'th-list'),
(474, 'Mining Machines', 455, 0, 'Pool/index', 0, '', 'Mining', 0, 'server'),
(475, 'User Machines', 455, 0, 'Pool/userMachines', 0, '', 'Mining', 0, 'user'),
(476, 'Mining Rewards', 455, 0, 'Pool/userRewards', 0, '', 'Mining', 0, 'money'),
(481, 'Giftcard', 4, 20, 'Giftcard/index', 0, '', 'Giftcard', 0, 'money'),
(482, 'Giftcard Banners', 4, 21, 'Giftcard/banner', 0, '', 'Giftcard', 0, '0'),
(489, 'Withdrawal Address', 5, 15, 'User/bank', 0, '', 'User', 0, 'exchange'),
(491, 'Dex Config', 5, 100, 'Hybrid/config', 0, '', 'Dex', 0, 'server'),
(492, 'Dex Quotes', 5, 100, 'Hybrid/quotes', 0, '', 'Dex', 0, 'user'),
(493, 'Dex Coins', 5, 100, 'Hybrid/coins', 0, '', 'Dex', 0, 'money'),
(494, 'Dex Deposit', 5, 100, 'Hybrid/index', 0, '', 'Dex', 0, 'money'),
(502, 'FX plans', 5, 9, 'Fx/index', 0, '', 'FX', 0, '0'),
(503, 'FX logs', 5, 11, 'Fx/log', 0, '', 'FX', 0, '0'),
(504, 'Safety Screen', 9, 5, 'Tools/safety', 0, '', NULL, 0, 'lock'),
(505, 'Debug', 9, 6, 'Tools/debug', 0, 'Debugging Logs', NULL, 0, 'warning-sign'),
(506, 'Competition Logs', 6, 1, 'Competition/index', 0, '', 'Competition', 0, 'globe'),
(507, 'Competition', 6, 1, 'Competition/type', 0, '', 'Competition', 0, 'globe'),
(508, 'Signup Activity', 9, 6, 'Tools/register', 0, '', NULL, 0, 'plus'),
(509, 'Mining Fees', 455, 0, 'Pool/fees', 0, 'Mining Fees Earned by site', 'Mining', 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `codono_message`
--

DROP TABLE IF EXISTS `codono_message`;
CREATE TABLE IF NOT EXISTS `codono_message` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `addip` varchar(200) DEFAULT NULL,
  `addr` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(10) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_message_log`
--

DROP TABLE IF EXISTS `codono_message_log`;
CREATE TABLE IF NOT EXISTS `codono_message_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `addip` varchar(200) DEFAULT NULL,
  `addr` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(10) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


-- --------------------------------------------------------

--
-- Table structure for table `codono_money`
--

DROP TABLE IF EXISTS `codono_money`;
CREATE TABLE IF NOT EXISTS `codono_money` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `num` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `deal` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `tian` int(11) UNSIGNED DEFAULT NULL,
  `fee` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COMMENT='Finance and investment table';

--
-- Dumping data for table `codono_money`
--

INSERT INTO `codono_money` (`id`, `name`, `coinname`, `num`, `deal`, `tian`, `fee`, `sort`, `addtime`, `endtime`, `status`) VALUES
(3, 'Bean Staking', 'beam', 10000, 10, 100, 1, 0, 0, 2465337600, 1),
(4, 'Bitcoin', 'btc', 1000, 22, 1, 2, 0, 0, 1651190400, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_money_fee`
--

DROP TABLE IF EXISTS `codono_money_fee`;
CREATE TABLE IF NOT EXISTS `codono_money_fee` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `money_id` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `num` int(6) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_money_log`
--

DROP TABLE IF EXISTS `codono_money_log`;
CREATE TABLE IF NOT EXISTS `codono_money_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `feecoin` varchar(20) DEFAULT NULL COMMENT 'feecoin',
  `num` int(11) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `feea` decimal(20,8) UNSIGNED DEFAULT NULL,
  `tian` int(11) UNSIGNED DEFAULT NULL,
  `tiana` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  `money_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Financial record sheet';

--
-- Dumping data for table `codono_money_log`
--


-- --------------------------------------------------------

--
-- Table structure for table `codono_mycz`
--

DROP TABLE IF EXISTS `codono_mycz`;
CREATE TABLE IF NOT EXISTS `codono_mycz` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `coin` varchar(20) DEFAULT 'usd' COMMENT 'fiat coin',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `num` float(11,2) UNSIGNED DEFAULT NULL,
  `mum` float(11,2) UNSIGNED DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `tradeno` varchar(50) DEFAULT NULL,
  `remark` varchar(250) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  `ipn_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'ipn status ',
  `ipn_response` text DEFAULT NULL COMMENT 'json response',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb3 COMMENT='Recharge record form';


-- --------------------------------------------------------

--
-- Table structure for table `codono_mycz_invit`
--

DROP TABLE IF EXISTS `codono_mycz_invit`;
CREATE TABLE IF NOT EXISTS `codono_mycz_invit` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `userid` int(11) UNSIGNED NOT NULL COMMENT 'userid',
  `invitid` int(11) UNSIGNED NOT NULL COMMENT 'inviteid',
  `num` decimal(20,2) UNSIGNED NOT NULL COMMENT 'Operating Amount',
  `fee` decimal(20,8) UNSIGNED NOT NULL COMMENT 'Credits',
  `coinname` varchar(50) NOT NULL COMMENT 'Currency gift',
  `mum` decimal(20,8) UNSIGNED NOT NULL COMMENT 'Amount arrival',
  `remark` varchar(250) NOT NULL COMMENT 'Remark',
  `sort` int(11) UNSIGNED NOT NULL COMMENT 'Sequence',
  `addtime` int(11) UNSIGNED NOT NULL COMMENT 'add time',
  `endtime` int(11) UNSIGNED NOT NULL COMMENT 'Edit time',
  `status` tinyint(4) NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Prepaid gift';

-- --------------------------------------------------------

--
-- Table structure for table `codono_mycz_type`
--

DROP TABLE IF EXISTS `codono_mycz_type`;
CREATE TABLE IF NOT EXISTS `codono_mycz_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `max` varchar(200) NOT NULL COMMENT 'name',
  `min` varchar(200) NOT NULL COMMENT 'name',
  `kaihu` varchar(200) NOT NULL COMMENT 'name',
  `type` varchar(10) NOT NULL DEFAULT 'bank',
  `truename` varchar(200) NOT NULL COMMENT 'name',
  `name` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `show_coin` varchar(255) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `img` varchar(50) DEFAULT NULL,
  `extra` varchar(50) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if this is default',
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COMMENT='Recharge type';

--
-- Dumping data for table `codono_mycz_type`
--

INSERT INTO `codono_mycz_type` (`id`, `max`, `min`, `kaihu`, `type`, `truename`, `name`, `title`, `show_coin`, `url`, `username`, `password`, `img`, `extra`, `remark`, `sort`, `addtime`, `endtime`, `is_default`, `status`) VALUES
(1, '100000', '50', 'Alipay business', 'gateway', 'Codono Inc', 'alipay', 'Alipay transfers', NULL, '', 'support@codono.com', '', '595607f635afa.png', '', 'Alipay account needs to be set inside Contact', 0, 0, 0, 0, 0),
(2, '1000', '1', 'Authorize.net', 'gateway', 'Codonocom', 'authorize', 'Authorize.net', NULL, '', 'CodonoCom', 'CODONO', 'authorizenet.png', '', 'You need to set up accounts in the micro-channel c', 0, 0, 0, 0, 0),
(3, '50000', '100', 'Bank of America [ SWIFT:BOFAUS3N ] [IBAN:47857475887455478]', 'bank', 'Codono Inc', 'bank', 'Online bank transfer', '[\"1\",\"83\",\"81\"]', '', '4325657823456789', '31495965', '5acc83366c0e8.png', '', 'Information required in the format in which the nu', 0, 0, 0, 1, 1),
(5, '1000', '100', 'TR00 0000 0000 0000 0000 0000 01', 'bank', 'CODONO NAME', 'turkey', 'Online bank transfer2', '[\"1\",\"81\",\"83\"]', NULL, '2646346', '78378578', '6155bd6dee134.png', '0', NULL, NULL, NULL, NULL, 1, 1),
(6, '1000', '1', 'Yoco Payment', 'gateway', '', 'yoco', 'yoco', '[\"83\"]', NULL, 'pk_test_7c980306jBNRw1Eb9ef4', '', '5fab8424370f9.png', '3.9', NULL, NULL, NULL, NULL, 0, 0),
(7, '7000000', '50000', 'YoPayments', 'gateway', '', 'youganda', 'Yo Payments Uganda', '[\"86\"]', NULL, '', '', '5fcdd98b25659.jpg', '3', NULL, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_mytx`
--

DROP TABLE IF EXISTS `codono_mytx`;
CREATE TABLE IF NOT EXISTS `codono_mytx` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL COMMENT 'Payment info',
  `coin` varchar(20) NOT NULL DEFAULT 'usd' COMMENT 'fiat coin type',
  `num` int(11) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,2) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,2) UNSIGNED DEFAULT NULL,
  `truename` varchar(32) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `bank` varchar(250) DEFAULT NULL,
  `bankprov` varchar(50) DEFAULT NULL,
  `bankcity` varchar(50) DEFAULT NULL,
  `bankaddr` varchar(50) DEFAULT NULL,
  `bankcard` varchar(200) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `urgent` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb3 COMMENT='Withdraw record form';

--
-- Dumping data for table `codono_mytx`
--


--
-- Table structure for table `codono_myzc`
--

DROP TABLE IF EXISTS `codono_myzc`;
CREATE TABLE IF NOT EXISTS `codono_myzc` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `dest_tag` varchar(80) DEFAULT NULL COMMENT 'xmr.xrp etc',
  `coinname` varchar(15) DEFAULT NULL,
  `network` varchar(15) DEFAULT NULL COMMENT 'actual chain network for withdrawal ',
  `txid` varchar(80) DEFAULT NULL,
  `memo` varchar(80) DEFAULT NULL,
  `hash` varchar(80) DEFAULT NULL COMMENT 'Saving eth hash',
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `zc_coin` varchar(30) DEFAULT NULL COMMENT 'withdrawal fees coin',
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=670 DEFAULT CHARSET=utf8mb3;


-- --------------------------------------------------------

--
-- Table structure for table `codono_myzc_fee`
--

DROP TABLE IF EXISTS `codono_myzc_fee`;
CREATE TABLE IF NOT EXISTS `codono_myzc_fee` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `username` varchar(200) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `coinname` varchar(200) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `txid` varchar(200) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` varchar(200) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fee` decimal(20,8) DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_myzc_fee`
--

INSERT INTO `codono_myzc_fee` (`id`, `userid`, `username`, `coinname`, `txid`, `type`, `fee`, `num`, `mum`, `sort`, `addtime`, `endtime`, `status`) VALUES
(32, 0, '0', 'usdt', '8', '2', '0.00207713', '0.01542694', '0.01542694', NULL, 1706896899, NULL, 2),
(33, 0, '0', 'bnb', NULL, '2', '0.00205000', '0.01000000', '0.01000000', NULL, 1716977049, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_myzr`
--

DROP TABLE IF EXISTS `codono_myzr`;
CREATE TABLE IF NOT EXISTS `codono_myzr` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `username` varchar(120) DEFAULT NULL,
  `coinname` varchar(30) DEFAULT NULL,
  `chain` varchar(42) DEFAULT NULL COMMENT 'network of deposit',
  `type` varchar(10) NOT NULL DEFAULT '0' COMMENT 'eth,qbb,rgb, [Mainly for eth type]',
  `txid` varchar(120) DEFAULT NULL,
  `memo` varchar(100) DEFAULT NULL,
  `shifted_to_main` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'if eth type deposit shiifted to main account',
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=41092 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_myzr`
--


-- --------------------------------------------------------

--
-- Table structure for table `codono_navigation`
--

DROP TABLE IF EXISTS `codono_navigation`;
CREATE TABLE IF NOT EXISTS `codono_navigation` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `pid` int(10) NOT NULL DEFAULT 0 COMMENT 'parent',
  `name` varchar(255) NOT NULL COMMENT 'name',
  `title` varchar(255) NOT NULL COMMENT 'name',
  `url` varchar(255) NOT NULL COMMENT 'url',
  `ico` varchar(30) DEFAULT NULL COMMENT 'Font awesome Icon',
  `mobile_ico` varchar(50) DEFAULT NULL COMMENT 'mobile icons',
  `featured` varchar(30) DEFAULT NULL COMMENT 'featured block',
  `subtext` varchar(60) DEFAULT NULL,
  `sort` int(11) UNSIGNED NOT NULL COMMENT 'Sequence',
  `addtime` int(11) UNSIGNED NOT NULL COMMENT 'add time',
  `endtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Edit time',
  `is_external` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_navigation`
--

INSERT INTO `codono_navigation` (`id`, `pid`, `name`, `title`, `url`, `ico`, `mobile_ico`, `featured`, `subtext`, `sort`, `addtime`, `endtime`, `is_external`, `status`) VALUES
(1, 0, 'finance', 'Finance', 'Finance/index', NULL, NULL, NULL, NULL, 1, 0, 0, 0, -1),
(2, 0, 'user', 'User', 'User/index', NULL, NULL, NULL, NULL, 2, 0, 0, 0, -1),
(4, 0, 'Blog', 'News', 'Article/index', '', NULL, NULL, NULL, 7, 0, 0, 0, -1),
(7, 31, 'Vote', 'Vote', 'Vote/index', 'check-correct', '', '', '', 6, 0, 0, 0, 1),
(8, 0, 'ICO', 'ICO', 'Issue/index', NULL, NULL, NULL, NULL, 4, 1474183878, 0, 0, -1),
(15, 20, 'Spot Classic', 'Spot Classic', 'Trade/classic', 'send-to-back', '', '', 'Trade on our award-winning platform', 3, 1522312746, 0, 0, 1),
(16, 0, 'System Health', 'Health', 'Content/health', 'heartbeat', NULL, NULL, NULL, 10, 1524302866, 0, 0, -1),
(18, 0, 'Mining', 'Mining', 'Pool', '', NULL, NULL, NULL, 20, 1619506686, 0, 0, -1),
(19, 20, 'Easy Convert', 'Easy Convert', 'Easy', 'exchange', '', 'New', 'Easy Trade', 10, 1619518072, 0, 0, 1),
(20, 0, 'Trade', 'Trade', 'Trade', '', 'exchange-four', NULL, NULL, 30, 1630916925, 0, 0, 1),
(21, 0, 'Market', 'Market', 'Content/market', '', NULL, NULL, NULL, 3, 1631095835, 0, 0, 1),

(23, 29, 'Lab', 'Lab', 'Issue', 'flask', '', 'Innovate', 'Blockchain Research and Investments', 10, 1633007654, 0, 0, 1),
(24, 0, 'Finance', 'Finance', 'Finance', '', '', '', '', 20, 1633007905, 0, 0, 0),
(25, 29, 'Apps', 'Apps', 'Content/apps', 'devices', '', '', '', 0, 1633008047, 0, 0, 1),
(29, 0, ' ', ' ', '#', 'system', 'more-four', '', '', 0, 1675323619, 0, 1, 1),
(30, 29, 'Mining', 'Mining', 'Pool', 'heavy-metal', '', 'Mine to Earn', 'Rent mining machines', 0, 1675366471, 0, 0, 1),
(31, 0, 'Listing', 'Listing', '#', '', '', '', '', 0, 1676294130, 0, 0, 1),
(32, 20, 'Otc', 'Otc', 'Otc', 'cycle-movement', '', '', 'Over The Counter Liquidity', 0, 1676294440, 0, 0, 1),
(33, 20, 'Dex', 'Dex', 'Dex', 'connection-arrow', '', '', 'Use metamask to buy token', 0, 1676294673, 0, 0, 1),
(34, 0, 'Earn', 'Earn', '#', '', '', 'Hot', '', 0, 1676357127, 0, 0, 1),
(35, 34, 'Staking', 'Staking', 'Staking/index', 'broadcast-one', '', '', 'Stake and Get Rewards', 0, 1676357567, 0, 0, 1),
(36, 34, 'Airdrop', 'Airdrop', 'Airdrop', 'parachute', 'parachute', '', 'Earn Tokens', 0, 1676357666, 0, 0, 1),
(37, 34, 'Faucet', 'Faucet', 'Faucet', 'water', '', 'Grab', 'Free Coins and Tokens', 0, 1676359488, 0, 0, 1),
(38, 29, 'Store', 'Store', 'Shop', 'buy', '', '', 'Buy merchandise using crypto', 0, 1676365244, 0, 0, 1),
(39, 0, 'Google', 'Google', 'https://google.com', '', '', '', '', 0, 1677565846, 0, 1, -1),
(40, 20, 'Spot Professional', 'Spot Professional', 'Trade/tradepro', 'exchange-three', '', 'Hot', 'Tools for Pro', 0, 1680352779, 0, 1, 1),
(41, 31, 'Competition', 'Competition', 'Competition/index', 'ranking-list', '', '', '', 0, 1717061953, 0, 0, 1),
(42, 0, 'Leaderboard', 'Leaderboard', 'Leaderboard/index', '', '', 'New', '', 0, 1717062029, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_notification`
--

DROP TABLE IF EXISTS `codono_notification`;
CREATE TABLE IF NOT EXISTS `codono_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'index',
  `to_email` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'Recepient',
  `subject` text COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'email subject',
  `content` text COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'email content',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unsent, 1 =sent',
  `addtime` int(11) DEFAULT NULL,
  `sent_time` int(11) DEFAULT NULL COMMENT 'Request time',
  `priority` int(2) NOT NULL DEFAULT 0 COMMENT 'Experimental',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `codono_notification`
--

-- --------------------------------------------------------

--
-- Table structure for table `codono_options`
--

DROP TABLE IF EXISTS `codono_options`;
CREATE TABLE IF NOT EXISTS `codono_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL COMMENT 'Description',
  `value` varchar(500) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_options`
--

INSERT INTO `codono_options` (`id`, `name`, `title`, `value`, `status`) VALUES
(2, 'blockgum_url', 'Blockgum URL', 'http://167.99.248.185:9151', 1),
(3, 'blockgum_jwt', 'JWT Secret', 'bTZDZVpOZyNKcVcwPT02SyRsM2dqJG5wJmFtcDthbXA7dUhMYlpFc0ZPRXNJNXBRSGRhUHp5UWheb0ZCMkt0UWlwdGM=', 1),
(4, 'blockgum_security_type', 'Advance Security 1 or 0', '0', 1),
(5, 'blockgum_client_id', 'Blockgum Client ID', 'ss', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_otc`
--

DROP TABLE IF EXISTS `codono_otc`;
CREATE TABLE IF NOT EXISTS `codono_otc` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `deal_buy` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'total bought yet',
  `deal_sell` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'total sold yet',
  `limit` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `min` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `max` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `sort` int(5) NOT NULL DEFAULT 0,
  `ownerid` int(11) DEFAULT 0 COMMENT 'Fee userid',
  `commission` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `buy_commission` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `sell_commission` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `tier_1` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'otc commission for tier 1',
  `tier_2` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'otc commission for tier 2',
  `tier_3` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'otc commission for tier 3',
  `fees` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COMMENT='OTC Coin table';

--
-- Dumping data for table `codono_otc`
--

INSERT INTO `codono_otc` (`id`, `name`, `coinname`, `num`, `deal_buy`, `deal_sell`, `limit`, `min`, `max`, `addtime`, `endtime`, `sort`, `ownerid`, `commission`, `buy_commission`, `sell_commission`, `tier_1`, `tier_2`, `tier_3`, `fees`, `status`) VALUES
(10, 'btc', 'btc', '0.00000000', '12.88000186', '7.92060080', '1000.00000000', '0.01000000', '10.00000000', 1644302231, NULL, 0, 1, '2.50000000', '2.00000000', '1.00000000', '3.00', '2.50', '2.00', '0.00000000', 1),
(11, 'eth', 'eth', '0.00000000', '33.27831238', '17.40000000', '10000.00000000', '0.10000000', '100.00000000', 1634920041, NULL, 0, 1, '1.50000000', '1.00000000', '1.00000000', '0.00', '0.00', '0.00', '0.00000000', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_otc_log`
--

DROP TABLE IF EXISTS `codono_otc_log`;
CREATE TABLE IF NOT EXISTS `codono_otc_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `qid` varchar(24) NOT NULL DEFAULT '0_0' COMMENT 'uid_time',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL COMMENT 'type = buy/sell',
  `trade_coin` varchar(7) DEFAULT NULL COMMENT 'coin bought or sold',
  `base_coin` varchar(7) DEFAULT NULL COMMENT 'base coin',
  `final_price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `profit` decimal(20,8) DEFAULT 0.00000000 COMMENT 'commission earned ',
  `fees_paid` decimal(20,8) DEFAULT 0.00000000 COMMENT 'fees earned',
  `qty` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'qty',
  `final_total` decimal(20,8) UNSIGNED DEFAULT 0.00000000 COMMENT 'price x num',
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `fill` int(1) DEFAULT 0,
  `fill_id` varchar(30) DEFAULT NULL COMMENT 'binance fill record id',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3 COMMENT='OTC trade records';

--
-- Dumping data for table `codono_otc_log`
--

INSERT INTO `codono_otc_log` (`id`, `qid`, `userid`, `type`, `trade_coin`, `base_coin`, `final_price`, `profit`, `fees_paid`, `qty`, `final_total`, `addtime`, `endtime`, `status`, `fill`, `fill_id`) VALUES
(14, '382_1628171510', 382, 'buy', 'btc', 'usd', '38554.06404000', '1.96078410', '0.00000000', '0.00259376', '100.00000000', 1628171510, NULL, 1, 1, '6'),
(15, '382_1632982703', 382, 'buy', 'btc', 'usd', '44500.67322000', '0.19607345', '0.00000000', '0.00022471', '10.00000000', 1632982703, NULL, 1, 1, '7'),
(16, '382_1634920046', 382, 'buy', 'eth', 'ugx', '14169947.32196447', '99.00864875', '0.00000000', '0.00070571', '10000.00000000', 1634920046, NULL, 1, 0, NULL),
(17, '38_1644302991', 38, 'buy', 'eth', 'usdt', '3207.07825000', '9.90099009', '0.00000000', '0.31181029', '1000.00000000', 1644302991, NULL, 1, 1, '1'),
(18, '38_1646135539', 38, 'sell', 'btc', 'zar', '675193.70971800', '6820.13848200', '0.00000000', '1.00000000', '675194.00000000', 1646135539, NULL, 1, 0, NULL),
(19, '38_1646137223', 38, 'buy', 'btc', 'zar', '717653.24378100', '7035.81611550', '0.00000000', '0.50000000', '358827.00000000', 1646137223, NULL, 1, 0, NULL),
(20, '38_1661778878', 38, 'buy', 'btc', 'usdt', '20320.59861000', '398.44311000', '0.00000000', '1.00000000', '20321.00000000', 1661778878, NULL, 1, 0, NULL),
(21, '38_1676370040', 38, 'buy', 'eth', 'usdt', '1525.59035500', '29.69306927', '0.00000000', '1.96579638', '2999.00000000', 1676370040, NULL, 1, 0, NULL),
(22, '38_1680015031', 38, 'buy', 'eth', 'usdt', '1761.39505500', '401.10976500', '0.00000000', '23.00000000', '40512.00000000', 1680015031, NULL, 1, 0, NULL),
(23, '38_1708344837', 38, 'buy', 'eth', 'usdt', '2941.30937500', '29.12187500', '0.00000000', '1.00000000', '2941.00000000', 1708344837, NULL, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_otc_quotes`
--

DROP TABLE IF EXISTS `codono_otc_quotes`;
CREATE TABLE IF NOT EXISTS `codono_otc_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qid` varchar(30) DEFAULT NULL,
  `data` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_otc_quotes`
--

INSERT INTO `codono_otc_quotes` (`id`, `qid`, `data`) VALUES
(1, '0', '{\"qid\":0,\"trade_type\":\"buy\",\"trade\":\"eth\",\"base\":\"usdt\",\"symbol\":\"ethusdt\",\"qty\":\"0.38944451\",\"final_price\":\"2567.75986500\",\"final_total\":1000,\"profit\":\"9.90098992\",\"addtime\":1705396391}'),
(2, '38_1708344837', '{\"qid\":\"38_1708344837\",\"trade_type\":\"buy\",\"trade\":\"eth\",\"base\":\"usdt\",\"symbol\":\"ethusdt\",\"qty\":\"1\",\"final_price\":\"2941.30937500\",\"final_total\":2941,\"profit\":\"29.12187500\",\"addtime\":1708344837}'),
(3, '0', '{\"qid\":0,\"trade_type\":\"buy\",\"trade\":\"eth\",\"base\":\"usdt\",\"symbol\":\"ethusdt\",\"qty\":\"1\",\"final_price\":\"3980.56503500\",\"final_total\":3981,\"profit\":\"39.41153500\",\"addtime\":1716798319}'),
(4, '0', '{\"qid\":0,\"trade_type\":\"buy\",\"trade\":\"eth\",\"base\":\"usdt\",\"symbol\":\"ethusdt\",\"qty\":\"1\",\"final_price\":\"2618.35076500\",\"final_total\":2618,\"profit\":\"25.92426500\",\"addtime\":1723455938}');

-- --------------------------------------------------------


--
-- Table structure for table `codono_paybyemail`
--

DROP TABLE IF EXISTS `codono_paybyemail`;
CREATE TABLE IF NOT EXISTS `codono_paybyemail` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_userid` int(11) UNSIGNED DEFAULT NULL,
  `to_userid` varchar(200) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL COMMENT 'user identification email',
  `coinname` varchar(200) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL COMMENT 'code for verification',
  `txid` varchar(50) DEFAULT NULL COMMENT 'system generated some hash',
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `from_userid` (`from_userid`),
  KEY `status` (`status`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_paybyemail`
--

INSERT INTO `codono_paybyemail` (`id`, `from_userid`, `to_userid`, `email`, `coinname`, `code`, `txid`, `num`, `fee`, `mum`, `addtime`, `endtime`, `status`) VALUES
(1, 38, '39', 'shree@codono.com', 'usdt', '1222', 'd3fd6101f7aea4c9a587706cc9865630', '100.00000000', '0.50000000', '99.50000000', 1693378374, NULL, 1),
(2, 38, '1', 'admin@codono.com', 'btc', 'AK696524', 'db6eb6fb8001008e869db3ce8e036767', '1.00000000', '0.00500000', '0.99500000', 1716814731, NULL, 1),
(3, 38, '3', 'DXJGZY', 'btc', 'AK696524', '43b52a1c098c9a8fefc0aad3669270c2', '1.00000000', '0.00500000', '0.99500000', 1716815238, NULL, 1),
(4, 38, '382', 'crowdphp@gmail.com', 'btc', 'YF698675', '3dd2b9014c7fc08f24cf60f147a56490', '0.01000000', '0.00000000', '0.01000000', 1717407109, NULL, 1),
(5, 38, '6', 'GDFPBU', 'btc', 'SR672488', '45a9571ef294afec3737c6ae2a9edc6f', '0.01000000', '0.00000000', '0.01000000', 1717407218, NULL, 1),
(6, 38, '382', 'crowdphp@gmail.com', 'usdt', 'FX599822', '0d1158b0f31ad243e4398b3bec7fd12c', '10.00000000', '0.50000000', '9.50000000', 1717411445, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_pending`
--

DROP TABLE IF EXISTS `codono_pending`;
CREATE TABLE IF NOT EXISTS `codono_pending` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `username` varchar(120) DEFAULT NULL,
  `coinname` varchar(30) DEFAULT NULL,
  `chain` varchar(42) DEFAULT NULL COMMENT 'network of deposit',
  `type` varchar(10) NOT NULL DEFAULT '0' COMMENT 'eth,qbb,rgb, [Mainly for eth type]',
  `txid` varchar(120) DEFAULT NULL,
  `memo` varchar(100) DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee` decimal(20,8) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(2) DEFAULT NULL,
  `aml_status` tinyint(2) DEFAULT NULL COMMENT 'AML check status: 0=pending, 1=approved, 2=rejected',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `coinname` (`coinname`),
  KEY `txid` (`txid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_pending`
--

INSERT INTO `codono_pending` (`id`, `userid`, `username`, `coinname`, `chain`, `type`, `txid`, `memo`, `num`, `mum`, `fee`, `sort`, `addtime`, `endtime`, `status`, `aml_status`) VALUES
(1, 1, 'testuser', 'btc', 'bitcoin', 'btc', 'testtxid1234567890', 'Test deposit', '0.01000000', '0.00950000', '0.00050000', NULL, 1723018332, NULL, 1, 1),
(2, 1, 'testuser', 'btc', 'bitcoin', 'btc', 'testtxid12345678d', 'Test deposit', '0.01000000', '0.00950000', '0.00050000', NULL, 1723018609, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_pool`
--

DROP TABLE IF EXISTS `codono_pool`;
CREATE TABLE IF NOT EXISTS `codono_pool` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `getcoin` varchar(10) DEFAULT NULL COMMENT 'Mining reward coin',
  `ico` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `days` int(11) UNSIGNED DEFAULT 1,
  `quantity` int(10) NOT NULL DEFAULT 0 COMMENT 'initial quantity',
  `stocks` int(10) DEFAULT 0 COMMENT 'inventory',
  `user_limit` int(10) NOT NULL DEFAULT 1 COMMENT 'per user mine rent limit',
  `power` varchar(20) DEFAULT NULL COMMENT 'hashing power [marketing]',
  `daily_profit` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'daily profit',
  `charge_coin` varchar(30) DEFAULT NULL,
  `charge_price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `sort` int(4) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if popular',
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COMMENT='Mining machine type table';

--
-- Dumping data for table `codono_pool`
--

INSERT INTO `codono_pool` (`id`, `name`, `coinname`, `getcoin`, `ico`, `price`, `days`, `quantity`, `stocks`, `user_limit`, `power`, `daily_profit`, `charge_coin`, `charge_price`, `sort`, `addtime`, `endtime`, `is_popular`, `status`) VALUES
(1, 'Mining Pool', 'ltc', 'ltc', '60818c927e5e1.png', '1.00000000', 2, 100, 0, 1, '1.6ghz', '0.01000000', NULL, '0.00000000', 1, 1618833815, 1618843815, 1, 1),
(4, 'ETH Mining Rigs', 'eth', 'eth', '60818d6959fff.png', '1.00000000', 11, 2, 0, 1, '170-180 MH', '0.02000000', NULL, '0.00000000', 100, NULL, NULL, 0, 0),
(5, 'BNB Mining', 'usdt', 'bnb', '', '100.00000000', 5, 20, 12, 2, '1.6GHZ', '0.01010000', 'ada', '0.00000000', 100, NULL, NULL, 0, 1),
(6, 'Slow Node: Daily Mining 0.1 GNSS', 'usdt', 'gnss', '655b498d76f31.png', '8.00000000', 30, 100, 82, 1, '5.2 ghz', '0.10000000', 'bnb', '0.00200000', 1, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_pool_fees`
--

DROP TABLE IF EXISTS `codono_pool_fees`;
CREATE TABLE IF NOT EXISTS `codono_pool_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `pool_id` int(11) NOT NULL DEFAULT 0,
  `rent_id` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `coin` varchar(20) DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0= unknown ,1=rent , 2 =income',
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COMMENT='Site income using mining pool';

--
-- Dumping data for table `codono_pool_fees`
--

INSERT INTO `codono_pool_fees` (`id`, `userid`, `pool_id`, `rent_id`, `amount`, `coin`, `type`, `addtime`, `status`) VALUES
(1, 38, 6, 1, '8.00000000', 'usdt', 1, 1700556853, 1),
(2, 38, 6, 2, '8.00000000', 'usdt', 1, 1700557249, 1),
(3, 38, 6, 2, '0.00200000', 'bnb', 2, 1700557260, 1),
(4, 38, 6, 3, '8.00000000', 'usdt', 1, 1700559268, 1),
(5, 38, 6, 3, '0.00200000', 'bnb', 2, 1700559710, 1),
(6, 38, 4, 4, '1.00000000', 'eth', 1, 1708344023, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_pool_log`
--

DROP TABLE IF EXISTS `codono_pool_log`;
CREATE TABLE IF NOT EXISTS `codono_pool_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `poolid` int(11) DEFAULT 0,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `getcoin` varchar(20) DEFAULT NULL COMMENT 'reward coin',
  `name` varchar(50) DEFAULT NULL,
  `ico` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `days` int(3) UNSIGNED DEFAULT 0 COMMENT 'number of days',
  `stocks` int(10) DEFAULT 0 COMMENT 'inventory of machines',
  `power` varchar(50) DEFAULT NULL,
  `daily_profit` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'daily profit',
  `num` int(11) UNSIGNED DEFAULT NULL,
  `collected` int(4) UNSIGNED DEFAULT 0,
  `sort` int(11) UNSIGNED DEFAULT 0,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COMMENT='Minerals Management';

--
-- Dumping data for table `codono_pool_log`
--

INSERT INTO `codono_pool_log` (`id`, `poolid`, `userid`, `coinname`, `getcoin`, `name`, `ico`, `price`, `days`, `stocks`, `power`, `daily_profit`, `num`, `collected`, `sort`, `addtime`, `endtime`, `status`) VALUES
(3, 6, 38, 'usdt', 'gnss', 'Slow Node: Daily Mining 0.1 GNSS', '655b498d76f31.png', '8.00000000', 30, 0, '5.2 ghz', '0.10000000', 1, 1, 0, 1700449268, 1700559710, 1),
(4, 4, 38, 'eth', 'eth', 'ETH Mining Rigs', '60818d6959fff.png', '1.00000000', 11, 0, '170-180 MH', '0.02000000', 1, 0, 0, 1708344023, 1723445641, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_pool_rewards`
--

DROP TABLE IF EXISTS `codono_pool_rewards`;
CREATE TABLE IF NOT EXISTS `codono_pool_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poolid` int(11) NOT NULL,
  `logid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `coinname` varchar(10) DEFAULT NULL,
  `amount` decimal(20,8) DEFAULT 0.00000000,
  `hash` varchar(64) DEFAULT NULL COMMENT 'hash computed',
  `addtime` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_pool_rewards`
--

INSERT INTO `codono_pool_rewards` (`id`, `poolid`, `logid`, `userid`, `coinname`, `amount`, `hash`, `addtime`) VALUES
(1, 5, 1, 38, 'bnb', '0.01010000', 'd31bb6e847a57f1d6154cc51c789e0e3ab2a65a9', 1700464164),
(4, 6, 5, 38, 'gnss', '0.10000000', 'fa8ca628255ecaec4ca538b5a1a720e9e3293c39', 1700493985),
(5, 6, 6, 38, 'gnss', '0.10000000', '0db8f90b6f9fbb4f3a8e0be752c29c6819d5497c', 1700495287),
(6, 6, 7, 38, 'gnss', '0.10000000', 'f82d0b084bcff4a7f79a2b28ae2069a30c9585ed', 1700495590),
(7, 6, 8, 38, 'gnss', '0.10000000', 'aa5464b3ca81fde86d5efe8629d8db8db8bd3868', 1700496198),
(8, 6, 9, 38, 'gnss', '0.10000000', 'accdeb258396ef489d4afc9492a60383d502afcc', 1700496978),
(9, 6, 10, 38, 'gnss', '0.10000000', '4b3c55d8e2aa8cfe375eb53557775988da90b704', 1700498051),
(10, 6, 11, 38, 'gnss', '0.10000000', 'c5f556f7cb269089af9007d7e5d774ddaa2a54d9', 1700498304),
(11, 6, 12, 38, 'gnss', '0.10000000', '5a903c3db5380e074b2efd3915ac3c40e0898061', 1700546934),
(12, 6, 13, 38, 'gnss', '0.10000000', '84755ff1106e6418ef9f34700c3b06688dc9ebdb', 1700548215),
(13, 6, 14, 38, 'gnss', '0.10000000', '0f722c5833e4178d171af8cde7836bf7ea8e362c', 1700550128),
(14, 6, 15, 38, 'gnss', '0.10000000', '87e07dfcf1de676baac759272d46272a5fc38d46', 1700551729),
(15, 6, 16, 38, 'gnss', '0.10000000', '480d6886a8b624d243c01acc25d3ad139cbb5397', 1700553797),
(16, 6, 17, 38, 'gnss', '0.10000000', '0a8145ae337ebe033cb7c15cb1cc6179861dbf7c', 1700555053),
(17, 5, 1, 38, 'bnb', '0.01010000', 'e3f15fc79ae625da9a51a6ef15c91c22480f516f', 1700555079),
(18, 6, 1, 38, 'gnss', '0.10000000', 'f4dd8e79539a243fcc0a54f6a46872be91a8d4ff', 1700557155),
(19, 6, 2, 38, 'gnss', '0.10000000', 'abba9e08a8062eaa96bc8e5067dadf09dcef82de', 1700557260),
(20, 6, 3, 38, 'gnss', '0.10000000', 'ba5227880f9d773e113885a5fa91fefc4d05bae7', 1700559710);

-- --------------------------------------------------------

--
-- Table structure for table `codono_prompt`
--

DROP TABLE IF EXISTS `codono_prompt`;
CREATE TABLE IF NOT EXISTS `codono_prompt` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `img` varchar(200) DEFAULT NULL,
  `mytx` varchar(200) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_qrlogin`
--

DROP TABLE IF EXISTS `codono_qrlogin`;
CREATE TABLE IF NOT EXISTS `codono_qrlogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `desktop_ip` varchar(50) DEFAULT NULL,
  `created_at` int(11) DEFAULT 0,
  `qr_secret` varchar(50) NOT NULL,
  `login_at` int(11) DEFAULT 0,
  `userid` int(11) NOT NULL,
  `mobile_ip` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `codono_rapid_deposit`
--

DROP TABLE IF EXISTS `codono_rapid_deposit`;
CREATE TABLE IF NOT EXISTS `codono_rapid_deposit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qid` varchar(50) NOT NULL,
  `coin` varchar(20) DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `addtime` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1 = received,2 =deposited, 3=invalid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_rapid_deposit`
--

INSERT INTO `codono_rapid_deposit` (`id`, `qid`, `coin`, `userid`, `amount`, `addtime`, `status`) VALUES
(1, '38_c18be3417e41b1e1f7a3f7df9918c296', 'bnb', 38, '1.00000000', 1641836742, 0),
(2, '38_60490065332aa230f53e3f53a41e1e72', 'bnb', 38, '1.00000000', 1641836760, 0),
(3, '38_4d309ac0317644842e21ab523ed25c02', 'bnb', 38, '1.20000000', 1641836780, 0),
(4, '38_d0a2d292f8d371c150298b856b00fb9e', 'bnb', 38, '0.10000000', 1641836821, 0),
(5, '38_71e9f12bf9de289cef6f01d4d0d4a660', 'busdt', 38, '1000.00000000', 1677823384, 0),
(6, '8_5b3e6b663283003534e4cc5e5e232758', 'busdt', 8, '1.00000000', 1718873574, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_roadmap`
--

DROP TABLE IF EXISTS `codono_roadmap`;
CREATE TABLE IF NOT EXISTS `codono_roadmap` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `year` varchar(30) NOT NULL DEFAULT 'Q3 2020',
  `date` varchar(50) NOT NULL DEFAULT 'Jul-Sept 2020',
  `text` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_roadmap`
--

INSERT INTO `codono_roadmap` (`id`, `year`, `date`, `text`, `status`) VALUES
(1, 'Q1 2020', 'Jan-Mar 2020', 'Whitepaper Release', 1),
(2, 'Q2 2020', 'Apr-Jun  2020', 'MVP Release', 1),
(3, 'Q3 2020', 'Jul-Sept 2020', 'Development and release of exchange', 1),
(4, 'Q4 2020', 'Oct-Dec 2020', 'Staking Feature', 2),
(5, 'Q1 2020', 'Jan-Mar 2021', 'Voting Release', 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_safety`
--

DROP TABLE IF EXISTS `codono_safety`;
CREATE TABLE IF NOT EXISTS `codono_safety` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `is_bad` tinyint(1) NOT NULL DEFAULT 0,
  `comment` varchar(50) DEFAULT NULL COMMENT 'Reason',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_safety`
--

INSERT INTO `codono_safety` (`id`, `email`, `ip`, `addtime`, `is_bad`, `comment`) VALUES
(15, 'turndealer@gmail.com', '0.0.0.0', 1699437489, 0, 'Verify/email_findpwd'),
(17, 'turndealer@gmail.com', '::1', 1718468848, 0, 'Verify/email_findpwd'),
(18, 'amber@codono.com', '127.0.0.1', 1725451919, 0, 'Account Freezed by User for Abnormal Activity'),
(19, 'amber@codono.com', '127.0.0.1', 1725451927, 0, 'Account Freezed by User for Abnormal Activity'),
(20, 'amber@codono.com', '127.0.0.1', 1725451930, 0, 'Account Freezed by User for Abnormal Activity'),
(21, 'amber@codono.com', '127.0.0.1', 1725451977, 0, 'Account Freezed by User for Abnormal Activity'),
(22, 'amber@codono.com', '127.0.0.1', 1725451979, 0, 'Account Freezed by User for Abnormal Activity'),
(23, 'amber@codono.com', '127.0.0.1', 1725452157, 0, 'Account Freezed by User for Abnormal Activity'),
(24, 'amber@codono.com', '127.0.0.1', 1725454306, 0, 'Account Freezed by User for Abnormal Activity');

-- --------------------------------------------------------

--
-- Table structure for table `codono_session`
--

DROP TABLE IF EXISTS `codono_session`;
CREATE TABLE IF NOT EXISTS `codono_session` (
  `session_id` varchar(255) CHARACTER SET latin1 NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob DEFAULT NULL,
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Table structure for table `codono_shop`
--

DROP TABLE IF EXISTS `codono_shop`;
CREATE TABLE IF NOT EXISTS `codono_shop` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `coinlist` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `buycoin` varchar(255) NOT NULL DEFAULT 'usd',
  `price` decimal(20,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `num` decimal(20,0) UNSIGNED NOT NULL DEFAULT 0,
  `deal` decimal(20,0) UNSIGNED NOT NULL DEFAULT 0,
  `content` text DEFAULT NULL,
  `max` varchar(255) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `shipping` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = no shipping , 1 = shipping',
  `market_price` decimal(20,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT 'Price',
  `codono_awardcoinnum` int(4) NOT NULL DEFAULT 0,
  `codono_awardcoin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`),
  KEY `deal` (`deal`),
  KEY `price` (`price`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COMMENT='Shopping Center commercial table';

--
-- Dumping data for table `codono_shop`
--

INSERT INTO `codono_shop` (`id`, `name`, `coinlist`, `img`, `type`, `buycoin`, `price`, `num`, `deal`, `content`, `max`, `sort`, `addtime`, `endtime`, `status`, `shipping`, `market_price`, `codono_awardcoinnum`, `codono_awardcoin`) VALUES
(1, 'MacBook Pro MF839CH/A 13.3 Inch', '', '/Upload/shop/5822a937b9874.png', 'electronics', 'usd', '888.00', '11110', '2', 'MacBook Pro<br />\r\n <br />\r\n WithRetina Display<br />\r\n <br />\r\n Each pixel particles, filling the surging power.<br />\r\n <br />\r\n Eye-catching Retina Display<br />\r\n <br />\r\n Ahead of its time several megapixels<br />\r\n <br />\r\n 15 Inch model has more than 500 CMOS,13 Inch model has more than 400 CMOS. So whether you are in the retouch photos or clips HDHD home videos can get stunning clarity. Text is also sharp and clear, let them browse the web and modify the document everyday things have become more enjoyable than ever. Such a display before being worthy of this extremely advanced notebook computer.<br />\r\n <br />\r\n Force Touch Touch version<br />\r\n <br />\r\n Let a new level of depth corresponding formula<br />\r\n <br />\r\n 13 inch MacBook Pro And bring you Mac The new way of interaction. Compact design Force Touch Touch pad, so you either tap the surface of which position, can get sensitive and consistent results click response. Below the touchpad, the intensity sensor can detect the intensity of your tap, add a new dimension to touch operation. You can enable long press by forcing a series of new features, such as simply increasing the intensity of pressing on the touchpad, you can quickly view the definition of words or preview files. You can also experience the tactile feedback, the touchpad will be issued a tactile vibration, so everything you see on the screen, but also feel it. All of these advanced features, all with deeply Mac Users favorite Multi-Touch With the use of gestures. Between easy, and you Mac Between communication, entered a new realm. Only13Inch models.<br />\r\n <br />\r\n A number of new high-performance technology<br />\r\n <br />\r\n Technology work together to quickly fulfill all<br />\r\n <br />\r\n It has strong power of dual-core and quad-core Intel Processors, advanced graphics processor, based on PCIe High-speed flash memory and fast Thunderbolt 2 Port, with Retina Display MacBook Pro Bring a full range of high performanceAnd meet all of your expectations Correct laptop.Whether you areBrowseSite or building site,Or play streaming video or video clips,MacBook Pro Able to unimaginable power and speed the rapid handling extremely complex (And less complex) Task.<br />\r\n <br />\r\n Thin, light, strong<br />\r\n <br />\r\n Between the least bit, hold unlimited innovation<br />\r\n <br />\r\n MacBook Pro The essence of the design is full of strong performance in a limited space. Because we believe, we should pursue high performance without sacrificing portability. Although the new 13 inch MacBook Pro So lightweight, but can still provide up 10 hourBatteryusetime,ratio ago product for an hour longer*.<br />\r\n <br />\r\n With a range of powerfulAPP<br />\r\n <br />\r\n Everything work smoothly, entertainment to get started<br />\r\n <br />\r\n Every new Mac They are equipped with iPhoto,iMovie,GarageBand,Pages,Numbers with Keynote. From the moment it opened, you can use photos, videos, music, documents, spreadsheets and presentations to Get creative. To cope with OS X Yosemite The beautifully designed, these app Have been upgraded. At the same time, you also enjoy a variety of exciting appTo send and receive email, surf the web, send text information, FaceTime Video calls, and even a appWe can help you find new app.<br />\r\n <br />\r\n OS X Yosemite<br />\r\n <br />\r\n Advanced computer operating system<br />\r\n <br />\r\n OS X Yosemite Easy to use, elegant and beautiful, more carefully built so that Mac Hardware functions into full play, called the advanced computer operating system. It is equipped with a series of outstanding appTo meet your daily needs. In addition, it lets you Mac with iOS Equipment can be a great way to tacit cooperation.<br />\r\n <br />\r\n Retina Display<br />\r\n <br />\r\n Several megapixels of good scenery<br />\r\n <br />\r\n <br />\r\n <br />\r\n 13 inch MacBook Pro<br />\r\n <br />\r\n 13 Inch equipped Retina Display MacBook Pro<br />\r\n <br />\r\n When you put so much into the pixels of a display:13 Inch model to achieve 400 Multi-million pixels,15 Inch model to achieve 500 Multi-million pixels, the effect is absolutely eye-catching. Its high pixel density more than the human eye can distinguish the image fidelity to a whole new realm.13 inch MacBook Pro has an amazing 2560 x 1600 pixel resolution, 15 inch MacBook Pro We have equally impressive 2880 x 1800 pixel resolution, can let you in the high resolution image pixel accuracy lucidity. And the language is so sharp that you have the feeling of reading email, web pages and File on paper.<br />\r\n <br />\r\n Retina Display while maintaining exceptional image quality and color, reducing the glare of. Its high-contrast black make more full-bodied, brighter white, all other colors also appear to be richer and more vivid.IPS Technology allows you to be able to 178 Wide viewing angle of viewing everything on the screen, so you can almost feel the difference from any angle. And you will certainly fascinated by everything he saw.<br />\r\n <br />\r\n 13 Inch equipped Retina Display MacBook Pro ratio HDTV More recent 200 CMOS,15 Inch model is more 300 CMOS.<br />\r\n <br />\r\n advanced Intel Mobile Processor<br />\r\n <br />\r\n Dual-core, quad-core, the powerful can not be discounted<br />\r\n <br />\r\n 13 Inch equipped Retina Display MacBook Pro,CarryThe fifth-generation dual-core Intel Core i5 or Intel Core i7 Processor, anytime, anywhere easily meet those high demands on performance appThis means that no matter where you go with the camera, your entire digital photo studio will be moving in unison. Each model incorporates Hyper-Threading technology that enhances performance by letting each core handle multiple tasks at the same time. Fast reached 3.1GHz Processing speed, up to 4MB Shared L3 cache and up3.4GHz of Turbo Boost Turbo Boost technology, these processors can cope with any task at any time.<br />\r\n <br />\r\n High-performance graphics processor<br />\r\n <br />\r\n Screen performance, thoroughly<br />\r\n <br />\r\n 13 Inch equipped Retina Display MacBook Pro Carry Intel Iris Graphics 6100 Graphics processor, is to perform a variety of daily tasks and graphics-intensive creative app The ideal choice. You can easily scroll through large photo libraries, playing those games is full of wonderful details, but also to connect one or two external displays, this is 13 inch MacBook Pro Once again a wonderful interpretation of the compact size and exceptional performance.<br />\r\n <br />\r\n Long battery life<br />\r\n <br />\r\n Life up 10 hour<br />\r\n <br />\r\n 13 inch MacBook Pro Charging time can run up 10 hour,More than a generationhour.and 15 Inch model can run for up to 8hour. For any laptop, such a battery are impressive. But is equipped with ultra-high resolution display, advanced processors and graphics processors, and high-performance notebook slim design, this is absolutely unbelievable. Built-in battery can provide up to you 1000 Last full charge and discharge cycles.<br />\r\n <br />\r\n Faster all-flash<br />\r\n <br />\r\n The name of flash memory, is not a mere name<br />\r\n <br />\r\n based on PCle Flash has amazing read and write speeds,No matter what you do,You can feel the difference:Very fast to start,app Open fast, even the desktop is also very smooth, very fast response.13 Flash Qantas inch model than the previous generation 2 Times, so you can import massive gallery at the moment. In the 15 On-inch models, flash memory and quad-core processors and high-performance graphics processor combine to make Final Cut Pro X In demanding editing tasks can be done quickly. Since these MacBook Pro Models are equipped with up to 1TB of flash memory, therefore you can carry all the important files with you. In addition, flash does not have any moving parts, so it is extremely durable and quiet.<br />\r\n <br />\r\n Mac Wonderful<br />\r\n <br />\r\n Wonderful in it can help you do everything<br />\r\n <br />\r\n Each Mac They are equipped with numerous inspire creativity and enhance the efficiency of app. At the same time, also has an impressive range of app To deal with daily affairs,includeBrowsenetwork page,Send e-mail and information,as well asOrganize your calendar.Theres even a appWe can help you find new app. So, your Mac Not only functional, but also heavily armed.<br />', '', 1, 1510156800, 1510202634, 1, 0, '900.00', 0, 'etn'),
(2, 'Universal Mobile Stand', '', '/Upload/shop/5822a9af793d6.png', 'electronics', 'usd', '45.00', '9998', '2', 'Product Description here', '', 2, 1521537542, 1521537542, 1, 0, '78.00', 0, ''),
(3, 'A1 Handheld HD mini projector', '', '/Upload/shop/5822a9ff1e0f6.png', 'electronics', 'usd', '115.00', '994', '5', '<div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n 	<p style=\"font-family:tahoma, \" font-size:35px;text-align:center;\"=\"\">\r\n 		Czech Republic, as the A1Wireless Micro Projector\r\n 		</p>\r\n <p>\r\n 		Based on the worlds firstDLPTechnology.LEDMicro projector light Technology\r\n 	</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;text-align:center;text-indent:28px;\"=\"\">\r\n 		A1It is based on the worlds firstDLPTechnology.0.3inchDMDFull function self-decoding chip (up to1080PResolution video)LEDLIGHT SOURCE mini projector, built-in wireless communication module interconnect technology, coupled with a variety of digital products, no cumbersome external data lines and power lines, not the space, limits the geographical environment.LEDMini projector, also known as pocket projectors, portable projectors, is the traditional large projector is compact, portable, miniaturized, entertainment, practical, living closer to the projector and entertainment. As a result of world-class (OSRAMOSRAM Semiconductor)LEDLight source technology, in view of theLEDSuper life, the average life of the aircraft over3Million hours.\r\n 		</p>\r\n \r\n </div>\r\n <div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n 	<p style=\"font-family:tahoma, \" font-size:35px;text-align:center;\"=\"\">\r\n 		Small projector, big screen\r\n 		</p>\r\n <p>\r\n 		Work, play a machine in place, anytime, anywhere to share with others\r\n 	</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;text-align:center;text-indent:28px;\"=\"\">\r\n 		The industrys first built-in wireless high-speedWi-FiModules and1080PHD decoder chip, can interpret and transmit more video sources Ninetowns, to make your online video everywhere, small projectors, large screen, anytime, anywhere to share videos, pictures and other resources with others, the use of direct readingTFMemory card or machineOFFICEDocuments, process documents faster and more convenient.A1Wireless mini projector for mobile commerce (in particular:IT, Advisory, consultancy, finance, insurance, direct marketing, etc.), product display, playbackOFFICEDocuments, digital products, video sharing, video games, small meetings and education, outdoor recreation,PARTYEtc., childrens education and entertainment.\r\n 		</p>\r\n </div>\r\n <div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n 	<p style=\"font-family:tahoma, \" font-size:35px;text-align:center;\"=\"\">\r\n 		Built-in wireless communication module\r\n 		</p>\r\n <p>\r\n 		Phone remote control, wired and wireless computer connection, in one step\r\n 	</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;text-align:center;text-indent:28px;\"=\"\">\r\n 		Support mobile remote control, mobile phone can be used as a remote control to manipulate projected. Connected to the computer, may be wired, wireless connected to the computer. The product not only can be read only byTFMemory card or machine function, can also be connected via the built-in wireless communication module with wireless smart phone, the phone screen display wirelessly to the projector and projected out, with Apple support Andrews phone system, while supporting the system iPad,PCEtc., and can be connected to wired and wireless laptop or desktop computer and is mirrored in the screen content.\r\n 		</p>\r\n </div>\r\n <div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n 	<p style=\"font-family:tahoma, \" font-size:35px;text-align:center;\"=\"\">\r\n 		The worlds first user-friendly operator interface\r\n 		</p>\r\n <p>\r\n 		Easy to read, easy to understand, easy to operate\r\n 	</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;text-align:center;text-indent:28px;\"=\"\">\r\n 		The worlds first user-friendly interface, easy to read, compared with similar products, the first contact with the user of the product, can master most of the functions operate in a shorter period of time, allowing users to go to a happy mood enjoy favorite video screen. Handheld projection products, this moderate brightness, image contrast1000:1,854x480High-resolution, colorful transparent, reproduction is good, the pictures are clear, sharp, high-definition players and other text documents, good detail.\r\n 		</p>\r\n </div>\r\n <div style=\"margin:0px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n 			</div>\r\n 			<div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black;background-color:#F8F8F8;color:#666666;\">\r\n 				<p style=\"font-family:tahoma, \" font-size:20px;\"=\"\">\r\n 		Compared with other similar products, it has the following characteristics and advantages:\r\n 					</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		. The minimum volume of the whole industry in the same function, the lightest weight, better portability.<br />\r\n . Via wired and wireless connectionswindowsSystems, system products Apple, Android products.<br />\r\n . At the same time distance projection, large screen area than similar products10About inches, the picture looks more intuitive and comfortable, smooth.<br />\r\n . Machine built-in wireless communication module, data processing and receiving stable, free from outside environmental factors, moving, shaking, etc., causing wireless communication signal is interrupted or affect play smooth, high yield when the unit quantities.<br />\r\n . The use of the polymer lithium battery, the safety of the market than similar models battery, and service life than normal life lithium battery.<br />\r\n . Optimization of cooling duct design, low noise, less overall opening ratio, dust absorption probability is small, the appearance of better treatment process, the whole low fault repair rate.\r\n 				</p>\r\n 			</div>\r\n 			<div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black, Arial;color:#666666;background-color:#FFFFFF;\">\r\n 				\r\n 			</div>\r\n 			<div style=\"margin:10px auto;padding:0px;font-family:Microsoft elegant black, Arial;color:#666666;background-color:#FFFFFF;\">\r\n 						</div>\r\n 						<div style=\"margin:0px auto;padding:0px;font-family:Microsoft elegant black;background-color:#FFFFFF;color:#333333;\">\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		1Whether all mobile phones can be connected to the wireless mini-projector?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: Currently supported phone operating system Android4.0Or later, Apple System version5.1(Including the version) to use, does not supportwindowsphoneSeries mobile phone operating system.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		2The maximum area of   the screen can be projected much?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		answer:40Inch left and right/1M, in a dark room and projected a larger area, the largest80Inches, depending on the Environment and to cast to determine the size of the projected area.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		3, The machine has built-in internal memory?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: The machine can remove externalTFCard (up to32G), The own internal4GMemory, it can store the user wants to store content.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		4, You can remote control, remote control why not?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: The design of this product for handheld products, machine surface to meet the basic remote control key functions, the desktop is not large projection, key functions more convenient to operate, there is no remote corners, easy to carry . Second, we designed with smart phones can be wireless communications, the use of mobile phones with wirelessWi-fiConnection, you can achieve massive online content transmission, while the phone can also be installedEZCONTROLThis oneAPPSoftware, software to operate the projector through this entire function. Most mobile phones can replace the remote control, mobile phone touch operation more convenient.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		5, External speakers can do?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: You can add multimedia or other wired active speakers.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		6You can use mobile power supply to the machine do? Choose what kind of mobile power supply? General mobile power can use how long?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: may be used, according to our charging adapter for an external power supply parameters, the mobile power is selected5V/2Ausage ofMicro usbThe plug can be used depends on the time the battery capacity of the contents and mobile power slideshow.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		7, The whole of life for how long, how to ensure after-sales?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: The worlds leading machine usedLEDLight source technology, the column has been mentioned above, a normal life3More than million hours, according to daily use8hourCompute,8hourX365=2920hour,Minimum machine canuse8In the above, the difficulty that the micro-projection structure design and reasonable cooling duct design, different from the machine on the market some products, opening rate, dispersion duct design, easily sucked into the dust, resulting in damage to the circuit board and the machine stops cooling fan turn; Second, some of the machines on the market due to the irrational thermal design, high heat, the machine at work, obviously felt the body heat, even more than the heat-resistant temperature of the user, to the user experience bad sense. This machine Machine noise is also lower than similar products, will not hear the annoying noise when you use; the machine has passed the national3CCertification, safety and quality assurance, do not worry about the use of safety and service.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		8, The brightness will decay it?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: Use3To5In the future, it may be the overall brightness decay15%, Electronic product itself is a consumable, after long-term use, light attenuation is a normal situation, but the machine will not stop working, slightly lower on the projection screen brightness.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		9This projector is why there is no curtain? Direct investment in the white wall effect is affected?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		answer: This is a portable projector, projection design idea is anywhere, it is not necessary with a projection screen, projected on the wall is no effect without stain white walls can be, projection.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		10Why the machine in use, some feel the body heat?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: light source inside the projector generates heat during operation, the air passage through the interior of the heat flows, the heat in the process of moving through the machine housing part may, it will feel some heat machine housing, it is normal, not Affect.\r\n 								</p>\r\n <p style=\"font-family:tahoma, \" font-size:15px;font-weight:bold;\"=\"\">\r\n 		11, Caution priorities?\r\n 							</p>\r\n <p style=\"font-family:tahoma, \" font-size:14px;\"=\"\">\r\n 		A: Non inlet, an air outlet foreign matter blocking file, or other objects covered by the body, leading to the heat generated when the working machine can not timely and effectively discharged, body heat causing significant damage to the machine; prohibited in the environment or sand acid and alkali environments; non-water machines other liquids so as to avoid damage to the machine.\r\n 								</p>\r\n 									</div>', '', 3, 1521537472, 1521537472, 1, 0, '150.00', 0, ''),
(4, 'Apple iPad Air 2 9.7 Inch 16G Wi', '', '/Upload/shop/5822aa3f7dbac.png', 'electronics', 'usd', '0.10', '33314', '20', '	&nbsp;&nbsp;&nbsp;&nbsp;iPad Air 2\r\n 		\r\n \r\n 		Gently, change everything.\r\n 	\r\n More than before and more energy, so that you do not want to let go;\r\n 		\r\n  \r\n Light and thin, so that you feel in the hand.\r\n 	\r\n \r\n 		for iPadWe always had a seemingly contradictory goals: to create an extremely powerful, yet lightweight slim to feel in your hand equipment; you can make a full sway, but effortless equipment. iPad Air 2 Not only to achieve all this, and even exceeded our expectations.\r\n 		\r\n \r\n 	\r\n 	\r\n 		\r\n 			\r\n 				\r\n 				\r\n 							\r\n \r\n 								\r\n \r\n 		numerous App,for iPad Tailored,\r\n \r\n Also for the achievements of everything you want to do.\r\n 							\r\n \r\n 		iPad Air 2 It builds a variety of powerful apps to get you on the go, such as browsing the web, checking emails, edit videos and photos, writing reports and reading books. Not only that,App Store There are thousands of models appDesigned for iPad Large multi-touch Retina Display and well-designed, more than just a mobile phone app Simple zoom. So whether you are interested in photography, games, travel, or want to manage their own finances, there is always a app You will do better.\r\n 								\r\n 							\r\n \r\n 								\r\n 								\r\n 									\r\n 			iOS 8 with iPad Air 2,\r\n Joined forces.\r\n 										\r\n \r\n 			iOS 8 Ahead of its mobile operating system, its advanced features let iPad Air 2 It becomes indispensable. Continuous interworking function lets you start a project on this device, and then continue on another device. Family Sharing feature allows you to easily share books with up to six people and app. ICloud Drive lets you safely store various types of files and access them from your various devices. In fact,iOS 8 Everything is in order and not only on iPad Air 2 Tacit understanding with the design, but also to the powerful A8X Chip, fast wireless connectivity, and brilliant Retina Advantages of the display to the fullest and to build.\r\n 									\r\n 											\r\n 										', '', 4, 1665424129, 1665424126, 1, 1, '345.00', 0, 'usd');

-- --------------------------------------------------------

--
-- Table structure for table `codono_shop_addr`
--

DROP TABLE IF EXISTS `codono_shop_addr`;
CREATE TABLE IF NOT EXISTS `codono_shop_addr` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `truename` varchar(50) NOT NULL DEFAULT '0',
  `cellphone` varchar(500) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_shop_addr`
--

INSERT INTO `codono_shop_addr` (`id`, `userid`, `truename`, `cellphone`, `name`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 38, 'Mark Shawn', '1375734574', 'Road 5, Block 2-3, La, CA, USA', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_shop_coin`
--

DROP TABLE IF EXISTS `codono_shop_coin`;
CREATE TABLE IF NOT EXISTS `codono_shop_coin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `shopid` int(11) UNSIGNED NOT NULL COMMENT 'Productid',
  `usd` varchar(50) DEFAULT NULL,
  `btc` varchar(50) DEFAULT NULL,
  `eth` varchar(50) DEFAULT NULL,
  `etc` varchar(50) DEFAULT NULL,
  `ltc` varchar(50) DEFAULT NULL,
  `bcc` varchar(50) DEFAULT NULL,
  `ast` varchar(50) DEFAULT NULL,
  `mtc` varchar(50) DEFAULT NULL,
  `eos` varchar(50) DEFAULT NULL,
  `ico` varchar(50) DEFAULT NULL,
  `powr` varchar(50) NOT NULL,
  `doge` varchar(50) DEFAULT NULL,
  `tbtc` varchar(50) DEFAULT NULL,
  `waves` varchar(50) DEFAULT NULL,
  `ltct` varchar(50) DEFAULT NULL,
  `xrp` varchar(50) DEFAULT NULL,
  `gbp` varchar(50) DEFAULT NULL,
  `lsk` varchar(50) DEFAULT NULL,
  `xmr` varchar(50) DEFAULT NULL,
  `eur` varchar(50) DEFAULT NULL,
  `dwe` varchar(50) DEFAULT NULL,
  `zar` varchar(50) DEFAULT NULL,
  `krb` varchar(50) DEFAULT NULL,
  `tsf` varchar(50) DEFAULT NULL,
  `ugx` varchar(50) DEFAULT NULL,
  `wbnb` varchar(50) DEFAULT NULL,
  `bbt` varchar(50) DEFAULT NULL,
  `try` varchar(99) DEFAULT NULL,
  `wbtc` varchar(99) DEFAULT NULL,
  `btt` varchar(99) DEFAULT NULL,
  `bttold` varchar(99) DEFAULT NULL,
  `tht` varchar(99) DEFAULT NULL,
  `tusdt` varchar(99) DEFAULT NULL,
  `arbiusdt` varchar(99) DEFAULT NULL,
  `dot` varchar(99) DEFAULT NULL,
  `xud` varchar(99) DEFAULT NULL,
  `rub` varchar(99) DEFAULT NULL,
  `gnss` varchar(99) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shopid` (`shopid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COMMENT='Commodity payment table';

-- --------------------------------------------------------

--
-- Table structure for table `codono_shop_log`
--

DROP TABLE IF EXISTS `codono_shop_log`;
CREATE TABLE IF NOT EXISTS `codono_shop_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) DEFAULT NULL,
  `shopid` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
  `coinname` varchar(50) NOT NULL DEFAULT '0.00',
  `num` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
  `mum` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
  `addr` varchar(255) NOT NULL DEFAULT '0.0000',
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `xuyao` decimal(20,8) UNSIGNED NOT NULL COMMENT 'price',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COMMENT='Shopping recording table';

-- --------------------------------------------------------

--
-- Table structure for table `codono_shop_type`
--

DROP TABLE IF EXISTS `codono_shop_type`;
CREATE TABLE IF NOT EXISTS `codono_shop_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Categories';

--
-- Dumping data for table `codono_shop_type`
--

INSERT INTO `codono_shop_type` (`id`, `name`, `title`, `remark`, `sort`, `endtime`, `addtime`, `status`) VALUES
(1, 'health', 'Health', 'Health', 1, 1520524800, 1518105600, 1),
(2, 'electronics', 'Electronics', 'Electronics', 2, 1678654204, 1678654189, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_signup`
--

DROP TABLE IF EXISTS `codono_signup`;
CREATE TABLE IF NOT EXISTS `codono_signup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(32) COLLATE utf8mb3_unicode_ci NOT NULL,
  `invit` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `invit_1` int(11) NOT NULL DEFAULT 0,
  `invit_2` int(11) NOT NULL DEFAULT 0,
  `invit_3` int(11) NOT NULL DEFAULT 0,
  `addip` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `addr` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `verify` varchar(32) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `accounttype` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=individual,2=institutional',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


--
-- Table structure for table `codono_sms`
--

DROP TABLE IF EXISTS `codono_sms`;
CREATE TABLE IF NOT EXISTS `codono_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_code` varchar(5) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `content` varchar(160) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0 COMMENT '0 - unsent, 1 - sent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `codono_sms`
--

INSERT INTO `codono_sms` (`id`, `country_code`, `phone_number`, `userid`, `content`, `addtime`, `status`) VALUES
(1, '+90', '5342424841', 38, 'Hello', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `codono_staking`
--

DROP TABLE IF EXISTS `codono_staking`;
CREATE TABLE IF NOT EXISTS `codono_staking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `allow_withdrawal` tinyint(1) NOT NULL DEFAULT 0,
  `coinname` varchar(50) DEFAULT NULL,
  `percentage` text NOT NULL COMMENT 'JSON encoded string of percentages for various periods',
  `period` varchar(50) DEFAULT NULL COMMENT 'JSON encoded string of periods',
  `penalty_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `penalty_coin` varchar(30) DEFAULT NULL COMMENT 'penalty coin when premature withdrawn',
  `minvest` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `maxvest` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `creatorid` int(30) NOT NULL DEFAULT 0,
  `action` varchar(255) NOT NULL DEFAULT '{"noaction":"1"}' COMMENT 'saved in json format, coin,market info',
  `invite_1` double(5,2) NOT NULL DEFAULT 0.00,
  `invite_2` double(5,2) NOT NULL DEFAULT 0.00,
  `invite_3` double(5,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `addtime` int(11) NOT NULL DEFAULT 0 COMMENT 'when added',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_staking`
--

INSERT INTO `codono_staking` (`id`, `title`, `allow_withdrawal`, `coinname`, `percentage`, `period`, `penalty_amount`, `penalty_coin`, `minvest`, `maxvest`, `creatorid`, `action`, `invite_1`, `invite_2`, `invite_3`, `status`, `addtime`) VALUES
(1, 'ETH New Year Staking', 0, 'eth', '{\"180\":\"6\",\"365\":\"10\"}', 'null', '2.00000000', 'usdt', '1.00000000', '1000.00000000', 0, '{\"coin\":{\"name\":\"\",\"value\":\"\"},\"market\":{\"name\":\"\",\"buy\":\"\",\"sell\":\"\"}}', 3.00, 2.00, 1.00, 1, 0),
(2, 'Near Year GNSS Staking ', 1, 'gnss', '{\"1\":\"1\",\"7\":\"2\",\"60\":\"3\",\"120\":\"4\",\"180\":\"5\",\"365\":\"6\"}', NULL, '1.00000000', 'usdt', '0.01000000', '1000.00000000', 0, '{\"coin\":{\"name\":\"\",\"value\":\"\"},\"market\":{\"name\":\"\",\"buy\":\"\",\"sell\":\"\"}}', 10.00, 5.00, 2.00, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_staking_log`
--

DROP TABLE IF EXISTS `codono_staking_log`;
CREATE TABLE IF NOT EXISTS `codono_staking_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `staking_id` int(10) NOT NULL DEFAULT 0 COMMENT 'staking id',
  `docid` varchar(30) NOT NULL DEFAULT '0' COMMENT 'staking certificate number',
  `period` int(3) NOT NULL DEFAULT 0 COMMENT 'user selected period',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(20,8) DEFAULT 0.00000000,
  `begintime` int(11) NOT NULL DEFAULT 0,
  `endtime` int(11) NOT NULL DEFAULT 0,
  `withdrawn` int(11) NOT NULL DEFAULT 0 COMMENT 'premature withdraw time',
  `maturity` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'amount on maturity',
  `credited` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `userid` int(15) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COMMENT='User staking id';

--
-- Dumping data for table `codono_staking_log`
--

INSERT INTO `codono_staking_log` (`id`, `staking_id`, `docid`, `period`, `percentage`, `amount`, `begintime`, `endtime`, `withdrawn`, `maturity`, `credited`, `status`, `userid`) VALUES
(2, 1, '1IB38QY389456', 365, '10.00', '1.00000000', 1707914544, 1739450544, 0, '1.09999999', '0.00000000', 1, 38),
(3, 2, '2IB38KY328691', 120, '4.00', '900.00000000', 1707915266, 1718283266, 0, '911.83561200', '0.00000000', 0, 38),
(4, 2, '2IB38EM324972', 180, '5.00', '299.00000000', 1707916155, 1723468155, 0, '306.37260147', '299.00000000', 0, 38),
(5, 2, '2IB38MU461157', 365, '6.00', '299.00000000', 1707916404, 1739452404, 0, '316.93999103', '299.00000000', 0, 38),
(6, 2, '2IB38UF618225', 120, '4.00', '100.00000000', 1707917312, 1718285312, 1724742154, '101.31506800', '101.31506800', 3, 38),
(7, 2, '2IB38QP183158', 180, '5.00', '1000.00000000', 1707918022, 1723470022, 1724742154, '1024.65753000', '1024.65753000', 3, 38),
(8, 2, '2IB38BI673362', 7, '2.00', '200.00000000', 1707923513, 1708528313, 1724742154, '400.07671200', '400.07671200', 3, 38),
(9, 2, '2IB38CD911762', 365, '6.00', '1000.00000000', 1707927926, 1739463926, 0, '1059.99997000', '0.00000000', 1, 38),
(10, 2, '2IB38LA577284', 365, '6.00', '1000.00000000', 1707928792, 1707928792, 1724742154, '1059.99997000', '1059.99997000', 3, 38);

-- --------------------------------------------------------

--
-- Table structure for table `codono_stop`
--

DROP TABLE IF EXISTS `codono_stop`;
CREATE TABLE IF NOT EXISTS `codono_stop` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT 0,
  `market` varchar(50) DEFAULT NULL,
  `compare` enum('lt','gt') NOT NULL DEFAULT 'lt' COMMENT 'lt=stop < current price, gt is stop  > current price',
  `price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `stop` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `num` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `deal` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `mum` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `fee` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `type` tinyint(2) UNSIGNED DEFAULT NULL COMMENT '1=Buy , 2 = Sale',
  `sort` int(11) UNSIGNED DEFAULT 0,
  `addtime` int(11) UNSIGNED DEFAULT 0,
  `endtime` int(11) UNSIGNED DEFAULT 0,
  `status` tinyint(2) DEFAULT 0 COMMENT '0=Pending , 1 = Processed ,2 =Cancelled',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `market` (`market`,`type`,`status`),
  KEY `num` (`num`,`deal`),
  KEY `status` (`status`),
  KEY `market_2` (`market`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Stop market table';

-- --------------------------------------------------------

--
-- Table structure for table `codono_text`
--

DROP TABLE IF EXISTS `codono_text`;
CREATE TABLE IF NOT EXISTS `codono_text` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_text`
--

INSERT INTO `codono_text` (`id`, `name`, `title`, `content`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'vote', 'New Coin Voting', 'Please modify the content here in the backoffice', 0, 1469733741, 0, 0),
(2, 'finance_index', 'Financial Center', 'Financial Center', 0, 1475325266, 0, 1),
(3, 'finance_myzr', 'Deposit Coins', 'Deposits', 0, 1475325312, 0, 1),
(4, 'finance_myzc', 'Withdraw Coins', 'Edit in backend ', 0, 1475325321, 0, 1),
(5, 'finance_mywt', 'My Orders', 'My Orders', 0, 1475325496, 0, 0),
(6, 'finance_mycj', 'My Transactions', 'Discover all the buying and selling transaction records', 0, 1475325508, 0, 1),
(7, 'finance_mycz', 'Account Recharge', 'Edit from backend', 0, 1475325515, 0, 1),
(8, 'user_index', 'User Center', 'Edit from backend', 0, 1475325658, 0, 1),
(9, 'finance_mytx', 'Account Withdrawal', 'Check in rates', 0, 1475325679, 0, 1),
(10, 'user_cellphone', 'Mobile', 'Edit from backend', 0, 1475351892, 0, 1),
(11, 'finance_mytj', 'Every invitation is a new win!', 'Invite your friends, earn commission!', 0, 1475352280, 0, 1),
(12, 'finance_mywd', 'My Referrals', 'Edit from backend', 0, 1475352284, 0, 1),
(13, 'finance_myjp', 'My Rewards', 'Edit from backend', 0, 1475352285, 0, 1),
(14, 'issue', 'ICO Center', 'Edit from backend', 0, 1475352288, 0, 1),
(15, 'issue_log', 'ICO records', 'Edit from backend', 0, 1475352293, 0, 1),
(16, 'game_dividend', '20', '<br />', 0, 1475352294, 0, 0),
(17, 'game_dividend_log', 'Dividend Record', 'Hold XYZ coin every day and get dividend in %', 0, 1475352296, 0, 1),
(18, 'game_money', '18', 'Please modify the content here in the background', 0, 1475352297, 0, 0),
(19, 'game_money_log', '17', 'Please modify the content here in the backoffice', 0, 1475352298, 0, 0),
(20, 'user_paypassword', 'Funding Password', 'Edit your transaction password', 0, 1475352694, 0, 1),
(21, 'user_password', '69', 'Edit from backend', 0, 1475352695, 0, 0),
(22, 'user_nameauth', 'KYC', 'KYC', 0, 1475352696, 0, 1),
(23, 'user_tpwdset', 'Transaction password input settings', 'Funding Password', 0, 1475352698, 0, 1),
(24, 'shop_index', '13', 'Modify from backend', 0, 1475352702, 0, 0),
(25, 'issue_buy', '12', 'Edit this content from Backend', 0, 1475352722, 0, 0),
(26, 'game_topup', 'Prepaid recharge', 'Prepaid recharge', 0, 1475359119, 0, 0),
(27, 'user_bank', 'Bank management', 'Bank Management', 0, 1475359192, 0, 1),
(28, 'user_wallet', 'Wallet address management', 'Edit from backend\r\n ', 0, 1475359195, 0, 1),
(29, 'user_log', 'My Logs', 'You can modify this text from backend!', 0, 1475359241, 0, 0),
(30, 'user_ga', '2FA Setup\r\n ', 'You can modify this text from backend!', 0, 1475395398, 0, 0),
(31, 'user_alipay', 'Binding Alipay', '<span><span><span>Please bind your real Alipay</span></span></span>', 0, 1475395410, 0, 1),
(32, 'user_goods', 'Address Management', 'You can modify this text from backend!', 0, 1475395413, 0, 1),
(33, 'shop_view', '3', 'You can modify this text from backend!', 0, 1476000366, 0, 0),
(34, 'shop_log', '2', 'You can modify this text from backend!', 0, 1476002906, 0, 0),
(35, 'shop_goods', '1', 'You can modify this text from backend!', 0, 1476002907, 0, 0),
(36, 'finance_myaward', '', 'You can modify this text from backend!', 0, 1482927855, 0, 1),
(37, 'index_info', '1A', 'We have added a new currency set today Check <a href=\"/trade/index/market/ltc_usd/\">LTC/USD</a>', NULL, NULL, NULL, 1),
(38, 'index_warning', '1 B', 'We have disabled POW deposits for maintenance', NULL, NULL, NULL, 1),
(39, 'game_bazaar', 'Android', 'Please check our latest mobile app!', NULL, 1523003258, NULL, 1),
(40, 'game_bazaar_mycj', NULL, 'You can modify this text from backend!', NULL, 1523003290, NULL, 1),
(41, 'game_vote', NULL, 'You can modify this text from backend!', NULL, 1536420134, NULL, 1),
(42, 'game_issue', NULL, 'You can modify this text from backend!', NULL, 1536848733, NULL, 1),
(43, 'game_issue_buy', NULL, 'You can modify this text from backend!', NULL, 1536848740, NULL, 1),
(44, 'game_issue_log', NULL, 'You can modify this text from backend!', NULL, 1542363500, NULL, 1),
(45, 'game_shop', NULL, 'You can modify this text from backend!', NULL, 1542450549, NULL, 1),
(46, 'game_shop_view', NULL, 'Edit from backend', NULL, 1554555254, NULL, 1),
(47, 'game_shop_log', NULL, 'Edit from backend', NULL, 1565261915, NULL, 1),
(48, 'initial', 'IEO Center', 'Sample Content', 0, 1475352293, 0, 1),
(49, 'initial_log', 'IEO records', 'Sample Content', 0, 1475352293, 0, 1),
(50, 'initial_buy', '12', 'Edit this content from Backend', 0, 1475352722, 0, 0),
(51, 'game_initial_buy', NULL, 'You can modify this text from backend!', NULL, 1536848740, NULL, 1),
(52, 'game_initial_log', NULL, 'You can modify this text from backend!', NULL, 1542363500, NULL, 1),
(53, 'game_initial', NULL, 'You can modify this text from backend!', NULL, 1536848733, NULL, 1),
(60, 'game_otc_log', NULL, 'You can modify this text from backend!', NULL, 1596278787, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_topup`
--

DROP TABLE IF EXISTS `codono_topup`;
CREATE TABLE IF NOT EXISTS `codono_topup` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `cellphone` varchar(255) DEFAULT NULL,
  `num` int(11) UNSIGNED DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `mum` decimal(20,8) DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_topup_coin`
--

DROP TABLE IF EXISTS `codono_topup_coin`;
CREATE TABLE IF NOT EXISTS `codono_topup_coin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `coinname` varchar(50) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_topup_coin`
--

INSERT INTO `codono_topup_coin` (`id`, `coinname`, `price`, `status`) VALUES
(1, 'eos', '10', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_topup_type`
--

DROP TABLE IF EXISTS `codono_topup_type`;
CREATE TABLE IF NOT EXISTS `codono_topup_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_topup_type`
--

INSERT INTO `codono_topup_type` (`id`, `name`, `title`, `status`) VALUES
(1, '10', '10 USD prepaid recharge', 1),
(2, '20', '20 USD prepaid recharge', 1),
(3, '30', '30 USD prepaid recharge', 1),
(4, '50', '50 USD prepaid recharge', 1),
(5, '100', '100 USD prepaid recharge', 1),
(6, '300', '300 USD prepaid recharge', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_trade`
--

DROP TABLE IF EXISTS `codono_trade`;
CREATE TABLE IF NOT EXISTS `codono_trade` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) UNSIGNED DEFAULT 0,
  `market` varchar(50) NOT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `num` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `deal` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `mum` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `fee` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `type` tinyint(2) UNSIGNED NOT NULL COMMENT '1=Buy , 2 = Sale',
  `sort` int(11) UNSIGNED DEFAULT 0,
  `addtime` int(11) UNSIGNED DEFAULT 0,
  `endtime` int(11) UNSIGNED DEFAULT 0,
  `status` tinyint(2) NOT NULL DEFAULT 0,
  `flag` bigint(20) NOT NULL DEFAULT 0 COMMENT 'flag',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `market` (`market`,`type`,`status`),
  KEY `num` (`num`,`deal`),
  KEY `status` (`status`),
  KEY `market_2` (`market`),
  KEY `idx_market_type_price` (`market`,`type`,`price`)
) ENGINE=InnoDB AUTO_INCREMENT=4773 DEFAULT CHARSET=utf8mb3 COMMENT='Under a single transaction table';


--
-- Table structure for table `codono_tradeinvoice_queue`
--

DROP TABLE IF EXISTS `codono_tradeinvoice_queue`;
CREATE TABLE IF NOT EXISTS `codono_tradeinvoice_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` int(11) NOT NULL,
  `to_date` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `fees` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `trades` int(11) NOT NULL DEFAULT 0,
  `market` varchar(20) DEFAULT NULL,
  `invoice_sent` tinyint(1) NOT NULL DEFAULT 0,
  `addtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_tradeinvoice_stats`
--

DROP TABLE IF EXISTS `codono_tradeinvoice_stats`;
CREATE TABLE IF NOT EXISTS `codono_tradeinvoice_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` int(11) NOT NULL,
  `to_date` int(11) NOT NULL,
  `user_distinct` int(10) NOT NULL DEFAULT 0,
  `trades` int(10) NOT NULL DEFAULT 0,
  `market` varchar(20) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_tradeinvoice_stats`
--

INSERT INTO `codono_tradeinvoice_stats` (`id`, `from_date`, `to_date`, `user_distinct`, `trades`, `market`, `addtime`) VALUES
(1, 17052021, 24052021, 2, 23, 'bitra_usdt', 1621814400);

-- --------------------------------------------------------

--
-- Table structure for table `codono_trade_fees`
--

DROP TABLE IF EXISTS `codono_trade_fees`;
CREATE TABLE IF NOT EXISTS `codono_trade_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `market` varchar(30) DEFAULT NULL,
  `fee_buy` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `fee_sell` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `addtime` int(11) DEFAULT NULL COMMENT 'added',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_trade_json`
--

DROP TABLE IF EXISTS `codono_trade_json`;
CREATE TABLE IF NOT EXISTS `codono_trade_json` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `market` varchar(100) DEFAULT NULL,
  `data` varchar(200) DEFAULT NULL,
  `type` int(4) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `market` (`market`)
) ENGINE=InnoDB AUTO_INCREMENT=1501 DEFAULT CHARSET=utf8mb3 COMMENT='Trading chart table';


--
-- Table structure for table `codono_trade_lever`
--

DROP TABLE IF EXISTS `codono_trade_lever`;
CREATE TABLE IF NOT EXISTS `codono_trade_lever` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `market` varchar(30) DEFAULT NULL,
  `type` tinyint(1) NOT NULL,
  `userid` int(11) NOT NULL DEFAULT 0,
  `price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `num` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `deal` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `mum` decimal(20,0) NOT NULL DEFAULT 0,
  `fee` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `addtime` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `trade_pt` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_trade_log`
--

DROP TABLE IF EXISTS `codono_trade_log`;
CREATE TABLE IF NOT EXISTS `codono_trade_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) UNSIGNED DEFAULT NULL,
  `peerid` bigint(20) UNSIGNED DEFAULT NULL,
  `market` varchar(50) DEFAULT NULL,
  `price` decimal(20,8) UNSIGNED DEFAULT NULL,
  `num` decimal(20,8) UNSIGNED DEFAULT NULL,
  `mum` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee_buy` decimal(20,8) UNSIGNED DEFAULT NULL,
  `fee_sell` decimal(20,8) UNSIGNED DEFAULT NULL,
  `type` tinyint(2) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT 0,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  `fill` int(1) DEFAULT 0 COMMENT 'liq fill optional',
  `fill_id` bigint(20) NOT NULL DEFAULT 0 COMMENT 'binance_tradeid',
  `fill_qty` decimal(20,8) NOT NULL DEFAULT 0.00000000 COMMENT 'externally ,filled quantity',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`),
  KEY `peerid` (`peerid`),
  KEY `main` (`market`,`status`,`addtime`) USING BTREE,
  KEY `idx_market_type_fill_peerid_status_price_id` (`market`,`type`,`fill`,`peerid`,`status`,`price`,`id`),
  KEY `idx_userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=344 DEFAULT CHARSET=utf8mb3;


-- --------------------------------------------------------

--
-- Table structure for table `codono_trade_log_lever`
--

DROP TABLE IF EXISTS `codono_trade_log_lever`;
CREATE TABLE IF NOT EXISTS `codono_trade_log_lever` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `peerid` int(11) NOT NULL,
  `market` varchar(30) NOT NULL,
  `price` decimal(18,8) NOT NULL,
  `num` decimal(18,8) NOT NULL,
  `mum` decimal(18,8) NOT NULL,
  `type` int(11) NOT NULL,
  `fee_buy` decimal(18,8) NOT NULL,
  `fee_sell` decimal(18,8) NOT NULL,
  `addtime` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_transfer`
--

DROP TABLE IF EXISTS `codono_transfer`;
CREATE TABLE IF NOT EXISTS `codono_transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `coin` varchar(30) DEFAULT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `from_account` varchar(10) DEFAULT NULL,
  `to_account` varchar(10) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_transfer`
--

INSERT INTO `codono_transfer` (`id`, `userid`, `coin`, `amount`, `from_account`, `to_account`, `created_at`, `status`) VALUES
(1, 38, 'btc', '0.10000000', 'spot', 'p2p', 1723798002, 1),
(2, 38, 'usdt', '211.96209039', 'spot', 'staking', 1724685218, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_tron`
--

DROP TABLE IF EXISTS `codono_tron`;
CREATE TABLE IF NOT EXISTS `codono_tron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT 0,
  `private_key` varchar(500) NOT NULL COMMENT 'encrypted',
  `public_key` varchar(130) NOT NULL,
  `address_hex` varchar(42) NOT NULL,
  `address_base58` varchar(34) NOT NULL,
  `salt` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 COMMENT='tron keys';

--

-- --------------------------------------------------------

--
-- Table structure for table `codono_tron_hash`
--

DROP TABLE IF EXISTS `codono_tron_hash`;
CREATE TABLE IF NOT EXISTS `codono_tron_hash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL,
  `hash` varchar(80) DEFAULT NULL,
  `contract_hex` varchar(42) DEFAULT NULL,
  `contract` varchar(34) DEFAULT NULL,
  `to_address_hex` varchar(42) DEFAULT NULL,
  `raw_amount` varchar(50) NOT NULL DEFAULT '0.00000000',
  `addtime` int(11) DEFAULT NULL,
  `issue` tinyint(1) NOT NULL DEFAULT 0,
  `deposited` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_ucenter_member`
--

DROP TABLE IF EXISTS `codono_ucenter_member`;
CREATE TABLE IF NOT EXISTS `codono_ucenter_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `last_login_ip` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_ucid`
--

DROP TABLE IF EXISTS `codono_ucid`;
CREATE TABLE IF NOT EXISTS `codono_ucid` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) DEFAULT NULL,
  `name` varchar(70) DEFAULT NULL,
  `symbol` varchar(40) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `rank` varchar(10) NOT NULL DEFAULT '0',
  `first_historical_data` varchar(70) DEFAULT NULL,
  `last_historical_data` varchar(70) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `platform` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='coinmarketcap unified asset data';

-- --------------------------------------------------------

--
-- Table structure for table `codono_unconfirmed`
--

DROP TABLE IF EXISTS `codono_unconfirmed`;
CREATE TABLE IF NOT EXISTS `codono_unconfirmed` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chain` varchar(30) DEFAULT NULL COMMENT 'eth, bnb , ftm ,chain name only as coin',
  `type` varchar(10) NOT NULL DEFAULT '0' COMMENT 'eth,qbb,rgb, [Mainly for eth type]',
  `txid` varchar(120) DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending , 1=deposited 2=issues/failed',
  `msg` varchar(255) DEFAULT NULL COMMENT 'response after processing',
  PRIMARY KEY (`id`),
  KEY `coinname` (`chain`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_user`
--

DROP TABLE IF EXISTS `codono_user`;
CREATE TABLE IF NOT EXISTS `codono_user` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `allow_username_change` tinyint(1) NOT NULL DEFAULT 0,
  `cellphones` varchar(10) NOT NULL DEFAULT '+1',
  `cellphone` varchar(50) DEFAULT NULL,
  `cellphonetime` int(11) UNSIGNED DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `tpwdsetting` tinyint(1) DEFAULT 1,
  `paypassword` varchar(32) DEFAULT NULL,
  `antiphishing` varchar(20) DEFAULT NULL COMMENT 'antiphishing',
  `invit_1` bigint(20) DEFAULT 0,
  `invit_2` bigint(20) DEFAULT 0,
  `invit_3` bigint(20) DEFAULT 0,
  `truename` varchar(32) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `city` varchar(40) DEFAULT NULL,
  `dob` varchar(11) DEFAULT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending,1=male,2=female,3=unspecified	',
  `zip` varchar(20) DEFAULT NULL,
  `applicantid` varchar(50) NOT NULL DEFAULT '0' COMMENT 'KYC applicantid for sumsub',
  `idcard` varchar(32) DEFAULT NULL,
  `idcardauth` tinyint(1) NOT NULL DEFAULT 0,
  `address` varchar(200) DEFAULT NULL COMMENT 'address',
  `kyc_comment` text DEFAULT NULL COMMENT 'If kyc was rejected then why?',
  `idcardimg1` text DEFAULT NULL COMMENT 'images name',
  `idcardimg2` text DEFAULT NULL,
  `idcardinfo` text DEFAULT NULL,
  `logins` int(11) NOT NULL DEFAULT 0 COMMENT 'Login Counts',
  `ga` varchar(50) DEFAULT NULL,
  `addip` varchar(50) DEFAULT NULL,
  `addr` varchar(64) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `freeze_reason` varchar(60) DEFAULT NULL,
  `fiat` varchar(25) DEFAULT NULL COMMENT 'preferred fiat for conversion',
  `accounttype` tinyint(1) DEFAULT 1 COMMENT '1=individual,2=institutional',
  `email` varchar(100) DEFAULT NULL COMMENT 'mailbox',
  `alipay` varchar(20) DEFAULT NULL COMMENT 'Alipay',
  `invit` varchar(50) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `apikey` varchar(34) DEFAULT NULL COMMENT 'v2 api keys',
  `regaward` tinyint(1) NOT NULL DEFAULT 0,
  `usertype` tinyint(1) NOT NULL DEFAULT 0,
  `is_merchant` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'is verified p2p merchant',
  `p2p_last_seen` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=390 DEFAULT CHARSET=utf8mb3 COMMENT='User information table';

--
-- Dumping data for table `codono_user`
--

INSERT INTO `codono_user` (`id`, `username`, `allow_username_change`, `cellphones`, `cellphone`, `cellphonetime`, `password`, `tpwdsetting`, `paypassword`, `antiphishing`, `invit_1`, `invit_2`, `invit_3`, `truename`, `firstname`, `lastname`, `country`, `state`, `city`, `dob`, `gender`, `zip`, `applicantid`, `idcard`, `idcardauth`, `address`, `kyc_comment`, `idcardimg1`, `idcardimg2`, `idcardinfo`, `logins`, `ga`, `addip`, `addr`, `sort`, `addtime`, `endtime`, `status`, `freeze_reason`, `fiat`, `accounttype`, `email`, `alipay`, `invit`, `token`, `apikey`, `regaward`, `usertype`, `is_merchant`, `p2p_last_seen`) VALUES
(1, 'technicator', 0, '+1', '6546546546', 2018, '5f4dcc3b5aa765d61d8327deb882cf99', 3, '5f4dcc3b5aa765d61d8327deb882cf99', NULL, 0, 0, 0, 'Tim Ron', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'fn9gh598hh4', '411081198706187279', 0, NULL, '', NULL, NULL, NULL, 62, '', '1.192.21.236', '', 0, 1497339574, 0, 1, NULL, NULL, 1, 'admin@codono.com', NULL, 'MSARVD', 'c3c79cfdf993080b56539fa4303b2ca6', '86bb7db424d2f2f7d74dfda7b3ad1652', 1, 1, 0, NULL),
(2, 'joseph', 0, '+1', '18530861253', 2017, '58e0ab543e9309651370850e5ca73826', 1, '4bc5fb692a01e5faa3250a448f2b4be9', NULL, 0, 0, 0, 'Paul Smith', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411081199403281254', 1, NULL, '', NULL, NULL, NULL, 4, '', '182.119.95.84', 'Unassigned or intranetIP', 0, 1497339574, 0, 1, NULL, NULL, 1, 'someemail@codono.com', NULL, 'FNEHUA', 'b86aa1eaad5c24f1541dfc1af827c80c', 'f2894495e8bf14af8bf843f106b303b3', 1, 0, 0, NULL),
(3, 'alma', 0, '+1', '15240403002', 2017, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '9c959088612d298ac59f10d840eefee9', 'DFRFSRRDS', 0, 0, 0, 'Andrew Ford', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '320322199603285018', 0, NULL, '', NULL, NULL, '', 1, '', '125.123.83.105', 'Unassigned or intranetIP', 0, 1497344824, 0, 1, NULL, NULL, 1, 'alma@codono.com', NULL, 'DXJGZY', '55b11850e3782cbddc5e464e71e0278f', 'eae7675ab641d3f5ebc1ea208a3f467c', 1, 0, 0, NULL),
(5, 'rone', 0, '+1', '15993674328', 2017, '71b3b26aaa319e0cdf6fdb8429c112b0', 1, 'e35cf7b66449df565f93c607d5a81d09', NULL, 0, 0, 0, 'Pandora Box', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411081198901221276', 0, NULL, '', '5943930243b5f.jpg_5943930980bef.jpg_594393110e4e1.jpg', NULL, '', 0, '', '222.137.197.41', 'Unassigned or intranetIP', 0, 1497600444, 0, 1, NULL, NULL, 1, NULL, NULL, 'RULAXQ', '9e967fc1d2b26572f820ada8b4dda707', '654be62088c00ee421a89eed9f4184c8', 0, 0, 0, NULL),
(6, 'millers', 0, '+1', '15903639320', 2017, 'bf36ea675fc9a7d00a33878c3d3b70ac', 1, 'b5a747937aec219a7a9b1cdf4293d663', NULL, 0, 0, 0, 'Lin Paolo', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411282198211244021', 0, NULL, '', NULL, NULL, '', 1, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497600490, 0, 1, NULL, NULL, 1, NULL, NULL, 'GDFPBU', '57cd7c5dd0dd8638c1e7af27309a1b0c', NULL, 0, 0, 0, NULL),
(7, 'rockpaper', 0, '+1', '15038987283', 2017, 'c6a416bda168be4b3f0af90871138e54', 1, 'e10adc3949ba59abbe56e057f20f883e', NULL, 0, 0, 0, 'Rosa Micado', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411081199911141258', 0, NULL, '', NULL, NULL, '', 0, '', '222.137.197.41', 'Unassigned or intranetIP', 0, 1497600535, 0, 1, NULL, NULL, 1, NULL, NULL, 'TJNIQZ', 'dda9dbd0b56fa14e718ac7418f6d6fdc', '04914f28b0089063884907188cd16dae', 0, 0, 0, NULL),
(8, 'ninjabyte', 0, '+1', '15890898515', 2017, 'ffaa0ad89b568bebafcb2990bca85cca', 1, '4072ad92cbf0257d8d66ea9ea84a5af7', NULL, 0, 0, 0, 'Nora Ali', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411327198906253910', 0, NULL, '', NULL, NULL, '', 0, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497600841, 0, 1, NULL, NULL, 1, NULL, NULL, 'ZXVRPC', 'ed9da172d6ff3e9238d018882c615470', '6240de9930f8ec376129222ee3fd5d7b', 0, 0, 0, NULL),
(9, 'penolope', 0, '+1', '15837109811', 2017, '21b8a4e0039b5215899fbf2c08f070cf', 1, '8c38754af97d5122a0f0b88e5ae5993d', NULL, 0, 0, 0, 'Bate Norman', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '410102198909130191', 0, NULL, '', NULL, NULL, '', 0, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497600844, 0, 1, NULL, NULL, 1, NULL, NULL, 'LVYDGM', 'df40c3fc59c68ae54e119d0effec9dfa', '10ef3f4222300fa5d71c5439b788a885', 0, 0, 0, NULL),
(10, 'kareesh', 0, '+1', '13523740282', 2017, '0adbd170421cd84f7665ebba5dbfd52e', 1, '184918de24299d318dd205a9349e82ca', NULL, 0, 0, 0, 'Timothy Mira', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'jJJ', 2, 'NoAddress', '', '6631e79c30dc9.png_6631e79f0baee.png_6631e7a4b88a5.png_6631e7a769390.png', NULL, 'drivers_license', 0, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497600860, 0, 1, NULL, NULL, 1, NULL, NULL, 'ZTCWHN', '39bf195f68c09b18beb07b9924023594', 'af395011c6be8d7bd5ede5800424fdcb', 0, 0, 0, 1724833388),
(11, 'micheal', 0, '+1', '13253366809', 0, '9619cfa4aabc670a2e2de1793d2726e0', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Roshov Micovich', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411627199808141158', 0, NULL, '', NULL, NULL, '', 1, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497601348, 0, 1, NULL, NULL, 1, NULL, NULL, 'VDZMJY', 'f036a264c4453ac7d94af108f7da8ae8', NULL, 0, 0, 0, NULL),
(12, 'george', 0, '+1', '13253655507', 2017, '29775f673b52cebda60554af3a3a53e3', 1, 'b921a87f4171a684f9a6d7da4e9c8b26', NULL, 0, 0, 0, 'Punto Gina', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '410326199006241030', 0, NULL, '', NULL, NULL, '', 0, '', '222.137.197.41', 'Unassigned or intranetIP', 0, 1497602187, 0, 0, 'transfer', NULL, 1, NULL, NULL, 'KEWUBT', 'e7edcca69f237a8f56c75af33d0b9a40', '122ccec0b323b7d36790a1bccf7b7292', 0, 0, 0, NULL),
(13, 'robert', 0, '+1', '13409377100', 2017, '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'e8a9bf77a8546e8a290323554733c4d8', NULL, 0, 0, 0, 'Xi Longman', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '411081198910057279', 1, NULL, '', NULL, NULL, '', 2, '', '1.197.135.113', 'Unassigned or intranetIP', 0, 1497603048, 0, 1, NULL, NULL, 1, 'longman@codono.com', NULL, 'YSMVJT', '41dc9efebbfae90a9937c3f16afd6ccc', '89d312a9060f6f1749bf37b023f4924f', 0, 0, 0, NULL),
(14, 'methew', 0, '+1', '15036918568', 0, 'bd099d13f5e2482a677ed6a776b1fb08', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Agoba Ginimo', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '412823199809132815', 0, NULL, '', NULL, NULL, '', 1, '', '1.192.29.82', 'Unassigned or intranetIP', 0, 1497603509, 0, 1, NULL, 'USD', 1, NULL, NULL, 'EYNWVX', 'b9b29ce7285c917d49779a604a57d1c1', '44468e2fb306b153185ba461d70e5d69', 0, 0, 0, NULL),
(15, 'pinope', 0, '+1', '17755193851', 1498715405, '6340b8d9750a18f9a6eb04eed93f23fd', 1, 'e10adc3949ba59abbe56e057f20f883e', NULL, 0, 0, 0, 'Joe Smith', 'Joe', 'Smith', 'HK', NULL, NULL, '2000-02-01', 0, NULL, '870eb7740244d94079180f57c7b4614262a0', '420351199305155345', 1, NULL, 'Please provide clear documents', NULL, NULL, NULL, 9, '', '122.191.204.16', 'Unassigned or intranetIP', 0, 1726557605, 0, 1, NULL, NULL, 1, NULL, NULL, 'SVWZAH', 'c1c9f6f51e8fed71d5374ad290e58baf', 'fa16ca5c636296a9b772b6d21851df7b', 0, 3, 0, NULL),
(16, 'rhyme', 0, '+1', '15502171747', 2018, '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'd64588fb904fa3e9635d1a4c01d38a92', NULL, 0, 0, 0, 'Lily Mimo', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '110199902034569', 1, NULL, '', NULL, NULL, NULL, 1, '', '45.113.160.58', 'Unassigned or intranetIP', 0, 1500711895, 0, 1, NULL, NULL, 1, NULL, NULL, 'WXNYJB', '8edcdd803dca285b7f67fdc629153350', NULL, 0, 0, 0, NULL),
(17, 'gorden', 0, '+1', '13059840358', 1501516800, '5f4dcc3b5aa765d61d8327deb882cf99', 1, 'e0a26e70cf3482669b81e77397eb1507', NULL, 0, 0, 0, 'Ryan Ming', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '110123199003031010', 0, NULL, '', '59b20de72634d.jpg_59b20deb4c191.jpg_59b20dee3b0c5.jpg', NULL, '', 11, '', '', '', 0, 0, 0, 1, NULL, NULL, 1, NULL, NULL, 'GDCRSQ', '7b0a1322a52efc36783988caa414b96e', '4e47cc93010a6edc7edbf4a9b6bcdb63', 0, 0, 0, NULL),
(18, 'testing', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, '', NULL, NULL, NULL, 0, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 1, NULL, NULL, 1, 'testing@testing123.com', NULL, 'GXBTLR', NULL, NULL, 0, 0, 0, NULL),
(19, 'mancore', 0, '+1', NULL, NULL, '7c82b7d848b2b07aee3846940113fbd9', 1, 'e26b2ce801e381a77c156726c2d447cc', NULL, 0, 0, 0, 'Mancore', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'AGH4374364D', 1, NULL, '', NULL, NULL, NULL, 1, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 1, NULL, NULL, 1, 'mancore@mancore.com', NULL, 'NGACLQ', 'dbbf1d5deffbf8983ab539f54ea2be99', NULL, 0, 0, 0, NULL),
(20, 'timkox', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, '', NULL, NULL, NULL, 0, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 0, NULL, NULL, 1, 'timkox@timkox.com', NULL, 'HTLWGN', NULL, NULL, 0, 0, 0, NULL),
(21, 'markman', 0, '', '', 0, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Timmothy Nord', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'ET56353', 1, NULL, '', '5a2fa1ea927c0.jpg_5a2fa1f6f3688.jpg_5a2fa2102cec0.jpg', NULL, '', 1, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1513071677, NULL, 1, NULL, NULL, 1, 'markman@markman.com', NULL, 'XNGCBM', '52f1f674380d40da427d879b51a48fd5', NULL, 0, 0, 0, NULL),
(22, 'demo123', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '6e9bece1914809fb8493146417e722f6', NULL, 0, 0, 0, 'Josh Rolan', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '5d6e40770a975a25f7c9fe15', 'HJ7878H', 0, NULL, '', NULL, NULL, NULL, 1, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 1, NULL, NULL, 1, 'demo@codono.com', NULL, 'FEZCQV', 'c53331e030807c78b1586a1d6b119b41', NULL, 0, 0, 0, NULL),
(23, 'demouser', 0, '+1', '7635474365375', 0, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '91017d590a69dc49807671a51f10ab7f', NULL, 1, 0, 0, 'Simon', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'H43434j', 1, NULL, '', NULL, NULL, NULL, 0, '', '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 1, NULL, NULL, 1, 'demouser@codono.com', NULL, 'WVZKUG', '87dfe1840cf965e28a5c5e05435a8f4b', 'a3a6ed87d812e36d3d60aa5870b5af01', 0, 1, 0, NULL),
(30, 'apollo', 0, '', NULL, 2018, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '5f4dcc3b5aa765d61d8327deb882cf99', NULL, 0, 0, 0, 'quickuser', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'quickuser', 1, NULL, '', NULL, NULL, NULL, 203, '', '127.0.0.1', 'Unassigned or intranet IP', NULL, 1523351829, NULL, 1, NULL, NULL, 1, NULL, NULL, 'TAPFZL', '56217e2d26505b31964c9b3a84f55d37', '2c2e6ebf1fc02eaab1568a262f7aeeae', 0, 0, 0, NULL),
(31, 'moonmagic', 0, '+852', '3216549870', 2018, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Rita Corner', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '48775748568765', 1, NULL, '', '5ae77a3bae8f8.png', NULL, '', 3, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1535099825, NULL, 1, NULL, NULL, 1, NULL, NULL, 'BJUCLK', 'c74488b70c9e312039eb47fa0459126b', NULL, 0, 0, 0, NULL),
(32, 'seveneighty', 0, '+1', '7894561230', 1525120535, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '7c6a180b36896a0a8c02787eeafb0e4c', NULL, 0, 0, 0, 'Sigora Desmond', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '754656465', 1, NULL, '', '5ae7852bd7168.png', NULL, '', 0, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1535108826, NULL, 1, NULL, NULL, 2, NULL, NULL, 'YPNVGZ', NULL, 'cdf0f7e589b1f6570537ef8b9c717277', 0, 0, 0, NULL),
(33, 'sinnora1', 0, '+1', '', 0, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Sinnora Simon', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'h6745764754', 0, NULL, '', '5af496f4f116c.png', NULL, NULL, 2, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, 'sinnora@codono.com', NULL, 'MJFQSZ', 'c48dead63f45a7ac64b204c4b9a476ff', '33e2abf7ac295b837590412872213a43', 0, 0, 0, NULL),
(34, 'ashper', 0, '+1', '458745648756', 1525375116, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 0, NULL, '', NULL, NULL, NULL, 0, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, NULL, NULL, 'JCUWBH', NULL, '06f558f3ffc0daf094cd30f8bc8ec505', 0, 0, 0, NULL),
(35, 'gelatoguy', 0, '+39', '47564574658', 1970, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '', 0, NULL, '', NULL, NULL, '', 1, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, NULL, NULL, 'VIRFHY', '54bcc7ebbe490f396b83fa913f516ab5', '280b0f026a2d0d548ad48a74e3ae4583', 0, 0, 0, NULL),
(36, 'simmon', 0, '+1', '74574757778', 2018, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'Parco Libro', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'H787878H', 0, NULL, '', '5aecc4d81b968.jpg_5aecc824ce8b0.gif', NULL, '', 8, NULL, '127.0.0.1', 'Unassigned or intranet IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, NULL, NULL, 'PXBZKY', '83c9713434c4a3221e0a59e723629507', NULL, 0, 0, 0, NULL),
(37, 'jondoe', 0, '+1', '548976526', 0, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 1, 0, 0, 'Turn Dealer', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'F6766667767', 0, NULL, '', '5aed8aba54f60.gif', NULL, NULL, 14, '43NNDSTQZLBBQDSY|1|1', '127.0.0.1', 'LocalIP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, 'admin@codono.com', NULL, 'UDGYPJ', 'b7638d895bfc8792df3159f9443d7809', '926550a4e8890e05016df7ab45c2fbfb', 0, 0, 0, NULL),
(38, 'amber', 0, '+90', '5342424841', 1718106791, '5f4dcc3b5aa765d61d8327deb882cf99', 3, '613d3b9c91e9445abaeca02f2342e5a6', 'TrustedGuy123', 1, 2, 3, 'Scott Rogers', 'Scott', 'Rogers', 'AT', 'd', 'j', '2000-02-02', 1, '8878787', 'd7fd0c1a06c0b940b91978877e0bcbf3410e', 'H83r3h8r3', 0, 'address', 'Verified', 'f4h89gh48gh48gj.jpg_f4h89gh48gh48gj.jpg_', '', 'dl', 843, '7WKCK4RIJOY7YRB2|1|1', '0.0.0.0', 'Hello Man / 87787 , hfhj2', NULL, 1672321420, NULL, 1, 'Abnormal Activity', 'USD', 1, 'amber@codono.com', NULL, 'WERGYJ', '3fe19077de033f67aeb7887443a071e0', '9f04682aba3399ad01f4749c2e7c7d2d', 0, 1, 1, 1724840720),
(39, 'shree', 0, '+852', '3574785874', 1592818958, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'shree', 'Shreex', 'Mo', 'Turkey', 'Istanbul', 'Istanbul', '2021-01-01', 0, NULL, '5d5e81370a975a6c2cc4ea01', 'OKOK', 0, NULL, '', '5b7fc95755410.png_60265e6fe129f.png', NULL, 'NationalID', 309, NULL, '0.0.0.0', 'Unassigned or Local IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, 'shree@codono.com', NULL, 'MHBTLD', 'afdd9dc5016d0d57f54f1ce732f971a8', '5a98841c3b8feb979a6a1b2a088db8fd', 0, 0, 1, NULL),
(41, 'sheil', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, 'ko', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', 'ko', 0, NULL, '', '5b83ccbf14ff0.jpg', NULL, NULL, 0, NULL, '0.0.0.0', 'Unassigned or Local IP', NULL, 1666003981, NULL, 0, NULL, NULL, 1, 'mocha@codono.com', NULL, 'FYADMS', NULL, 'ac7ceaf67c26f1866044aa3f62d25c2f', 0, 0, 0, NULL),
(49, 'wroupon', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, '', NULL, NULL, NULL, 9, NULL, '0.0.0.0', 'Unassigned or Local IP', NULL, 1711349430, NULL, 1, NULL, NULL, 1, 'wroupon@gmail.com', NULL, 'ZMWXLP', 'f3b52d5215c2eebadd520dbf79f3aeff', '64ba01041462c5e264b0531de1b6e7fe', 0, 0, 1, NULL),
(50, 'jackpotsoft', 0, '+1', '', 0, '371ca43477123f205c14e6bd40a3a0de', 1, '371ca43477123f205c14e6bd40a3a0de', NULL, 0, 0, 0, 'T R', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '78778778', 0, NULL, '', '5cd961e1d7d20.png_5cd961ec04268.png_5cd961ef0f230.png', NULL, 'HD', 48, '', '0.0.0.0', 'Unassigned or Local IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, 'jackpotsoft@gmail.com', NULL, 'FXKUDC', 'a1f2270045c208a7fd64597f98f429fc', '2b0e3989efaacfc7caf502468e190490', 0, 0, 0, NULL),
(62, 'test22', 0, '+1', '', 0, '4d42bf9c18cb04139f918ff0ae68f8a0', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 38, 1, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '', 0, NULL, 'kk', '', NULL, '', 0, NULL, '0.0.0.0', 'Unassigned or Local IP', NULL, 1666003981, NULL, 1, NULL, NULL, 1, 'test22@test22.com', NULL, 'IWFNZK', NULL, NULL, 1, 0, 0, NULL),
(380, 'blackhawk', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '5f4dcc3b5aa765d61d8327deb882cf99', NULL, 0, 0, 0, 'steve jobs', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1666003981, 1617897114, 1, NULL, NULL, 1, 'someone@black.hawk', NULL, 'YXSWFC', 'bce5ae8bd15be0857485ddb5f065f214', '883c5e600a1eb491cae4fad76da0b046', 0, 0, 0, NULL),
(382, 'crowdphp', 0, '+1', '2784787434', 1632996863, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 38, 1, 0, NULL, 'Katex', 'Simmon', 'Turkey', 'Ankara', 'Instanbul', '1984-05-25', 0, NULL, '0', 'H489598498', 0, NULL, NULL, NULL, NULL, 'NationalID', 116, NULL, '0.0.0.0', '0.0.0.0', NULL, 1666003981, NULL, 1, 'login,activities', 'USD', 1, 'crowdphp@gmail.com', NULL, 'IASUKY', 'b2249c799a1104d98e0d4737a0cbab43', '162f1bdd543f3dfcdb2f84f53b25d313', 1, 0, 1, NULL),
(383, 'turndealer', 0, '+1', '123456', 0, '5f4dcc3b5aa765d61d8327deb882cf99', 1, '95c359186ccd09eaa3a1f18c791b1c36', NULL, 0, 0, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '', 1, NULL, '', NULL, NULL, NULL, 16, NULL, '0.0.0.0', '0.0.0.0', NULL, 1666004018, NULL, 1, NULL, NULL, 1, 'turndealer@gmail.com', NULL, 'ZQUIKP', 'a7f6c06a31f582dc8a5a2b2bc4fa22db', '3529c7ca6e82955eddf83c8ac30e77ad', 0, 0, 0, NULL),
(384, 'hello', 0, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, NULL, NULL, 382, 38, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, '0.0.0.0', '0.0.0.0', NULL, 1666003981, NULL, 1, NULL, NULL, 1, '737467@6776.com', NULL, 'JYHGTP', 'e591261323624b65508195a84c43bf08', '591c6d9df1f5872d8e3178e29ae750fe', 0, 0, 0, NULL),
(387, '116621172324524751490', 1, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, NULL, NULL, 38, 1, 0, NULL, 'Exchange', 'Java', NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1, 'exchjava@gmail.com', NULL, 'KLGJMC', '4daa74a752073cf9dc9670049c6ba7e4', 'c9418bfb700967b97ebdda428aaec311', 0, 0, 0, NULL),
(388, '109844358307929392554', 1, '+1', NULL, NULL, '5f4dcc3b5aa765d61d8327deb882cf99', 1, NULL, NULL, 0, 0, 0, NULL, 'Hitoma', 'Chan', NULL, NULL, NULL, NULL, 0, NULL, '0', NULL, 0, NULL, NULL, '63e64b3f93b63.png_63e64b4e42abf.png', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1, 'hitmota21@gmail.com', NULL, 'GHNAVU', 'd4b94860afa91415af2e13260cae45c6', 'ced1d28e8fd92231cc68a6eba93a66b1', 0, 0, 0, NULL),
(389, 'okokok', 0, '+1', '', 0, '04b02bd7c45326ee23d4c4ca1cc357f0', 1, NULL, NULL, 0, 0, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '0', '', 1, NULL, '', NULL, NULL, NULL, 0, '', '0.0.0.0', '0.0.0.0', NULL, 1715606970, NULL, 0, 'login,activities', NULL, 1, 'okok@ok.com', NULL, 'UGNIVC', NULL, '82e924f203b77098fd183e1f27bd3c46', 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_assets`
--

DROP TABLE IF EXISTS `codono_user_assets`;
CREATE TABLE IF NOT EXISTS `codono_user_assets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'userid',
  `coin` varchar(30) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `account` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 P2P 2 Other2  3 Other3',
  `balance` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000 COMMENT 'balance',
  `freeze` decimal(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000 COMMENT 'frozen',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `version` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `uid_coin` (`uid`,`coin`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='User assets';

--
-- Dumping data for table `codono_user_assets`
--

INSERT INTO `codono_user_assets` (`id`, `uid`, `coin`, `account`, `balance`, `freeze`, `created_at`, `updated_at`, `version`) VALUES
(1, 38, 'xmr', 1, '0.00000000', '0.00000000', 1680625820, NULL, 1),
(2, 38, 'usdt', 1, '5035.55408192', '582.39302808', 1680625875, NULL, 1),
(3, 38, 'ltc', 1, '0.00000000', '0.00000000', 1680625922, NULL, 1),
(4, 3, 'ugx', 1, '772.90307347', '0.00000000', 1684488784, NULL, 1),
(5, 39, 'usdt', 1, '20.00000000', '0.00000000', NULL, NULL, 1),
(6, 16, 'usdt', 1, '100289.21353223', '0.00000000', 1705655879, NULL, 1),
(7, 38, 'usd', 1, '45.03100000', '0.00000000', 1706533858, NULL, 1),
(8, 38, 'bbt', 1, '0.06246573', '0.00000000', 1706534083, NULL, 1),
(9, 38, 'btc', 1, '1.13456249', '0.00000000', 1706534690, NULL, 1),
(10, 38, 'bnb', 1, '0.01274820', '0.00000000', 1707067263, NULL, 1),
(11, 38, 'bnb', 4, '0.00000000', '0.00000000', 1707067713, NULL, 1),
(12, 38, 'gnss', 4, '12763.03541600', '0.00000000', 1707067762, NULL, 1),
(13, 38, 'bbt', 4, '0.13903663', '0.00000000', 1707067775, NULL, 1),
(14, 38, 'xrp', 4, '3179.99991000', '0.00000000', NULL, NULL, 1),
(15, 38, 'btc', 5, '1.90000000', '0.00000000', 1707988094, NULL, 1),
(16, 50, 'tlnt', 5, '1990.80039600', '0.00000000', 1708938123, NULL, 1),
(17, 50, 'btc', 5, '4.65300000', '0.00000000', 1708938656, NULL, 1),
(18, 50, 'eth', 5, '2.00000100', '0.00000000', 1708938657, NULL, 1),
(19, 50, 'usdt', 5, '233.33004300', '0.00000000', 1708938657, NULL, 1),
(20, 49, 'usdt', 1, '210.69883687', '0.00000000', 1711362360, NULL, 1),
(21, 38, 'usdt', 4, '611.96209039', '0.00000000', 1724685218, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_award`
--

DROP TABLE IF EXISTS `codono_user_award`;
CREATE TABLE IF NOT EXISTS `codono_user_award` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL DEFAULT 0,
  `award_id` int(11) NOT NULL COMMENT 'References codono_awards.id',
  `awardname` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=confirmed, 2=failed',
  `addtime` int(11) NOT NULL DEFAULT 0 COMMENT 'Award time',
  `dealtime` int(11) DEFAULT NULL COMMENT 'Time when award was processed',
  `username` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_bank`
--

DROP TABLE IF EXISTS `codono_user_bank`;
CREATE TABLE IF NOT EXISTS `codono_user_bank` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `truename` varchar(50) DEFAULT NULL COMMENT 'ac holders name',
  `bank` varchar(200) DEFAULT NULL,
  `bankprov` varchar(200) DEFAULT NULL,
  `bankcity` varchar(200) DEFAULT NULL,
  `bankaddr` varchar(200) DEFAULT NULL,
  `bankcard` varchar(200) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `paytype` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_user_bank`
--

INSERT INTO `codono_user_bank` (`id`, `userid`, `name`, `truename`, `bank`, `bankprov`, `bankcity`, `bankaddr`, `bankcard`, `sort`, `addtime`, `endtime`, `paytype`, `status`) VALUES
(1, 2, 'test', NULL, 'Ping An Bank', 'Zhejiang', 'Ningbo', 'Testing Branch', '2245122145454545', 0, 1494863694, 0, 0, 1),
(4, 1, 'BOA TIM', NULL, 'Bank of America', 'USA', 'New York', '150 Broadway', '75648756485684564', NULL, 1524825963, NULL, 0, 1),
(5, 36, 'JJ', NULL, 'Bank of America', 'Wales', 'Wales', '4875648756', '1234123412341234', NULL, 1536224238, NULL, 0, 1),
(6, 39, 'Roger', NULL, 'Bank of America', 'Brazil', 'Rio', 'Rio Street 5', '3785638756876587', NULL, 1543493374, NULL, 0, 1),
(12, 38, 'Martins', 'Martin Col', 'Bank of America', 'Hong Kong', 'kowloon', 'Branch1', '3487573693869369564', NULL, 1599479802, NULL, 0, 1),
(13, 38, 'myboa', 'Amber Shawn', 'Bank of America', 'USA', 'LA', 'HDS7634367', '8945675986759687987', NULL, 1600342453, NULL, 0, 1),
(14, 382, 'Crowdphp', 'Scott Otten', 'ICBC', 'Hong Kong', 'Kowloon', 'Kowlook', '77838758548758', NULL, 1626867742, NULL, 0, 2),
(16, 382, '382_1631805548', NULL, 'ICBC', 'NA', 'NA', 'Hongkong', '7854785747', NULL, 1631805548, NULL, 0, 1),
(18, 382, '382_1632295978', NULL, 'HSBC', 'NA', 'NA', 'Road 4', '874754788548775', NULL, 1632295978, NULL, 0, 1),
(19, 49, '49_1711353705', 'GoergeGeorge', 'ROMBANK', 'NA', 'NA', 'H434636', '8745776545', NULL, 1711353705, NULL, 0, 1),
(20, 38, '38_1724841084', NULL, 'HSBC', 'NA', 'NA', 'Los3', '378545487', NULL, 1724841084, NULL, 1, 1),
(21, 38, '38_1725256961', NULL, 'HSBC', 'NA', 'NA', 'HSBC523523', '4875454', NULL, 1725256961, NULL, 1, 1),
(22, 38, '38_1725257083', NULL, 'HSBC Kowloon', 'NA', 'NA', 'HSBC523521', '48754542', NULL, 1725257083, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_bank_type`
--

DROP TABLE IF EXISTS `codono_user_bank_type`;
CREATE TABLE IF NOT EXISTS `codono_user_bank_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT 'bank' COMMENT 'bank,crypto,paypal,others',
  `name` varchar(200) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `img` varchar(200) DEFAULT NULL,
  `mytx` varchar(200) DEFAULT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COMMENT='Common Bank Address';

--
-- Dumping data for table `codono_user_bank_type`
--

INSERT INTO `codono_user_bank_type` (`id`, `type`, `name`, `title`, `url`, `img`, `mytx`, `remark`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 'bank', 'boc', 'Bank of China', 'http://www.boc.cn/', 'img_56937003683ce.jpg', '', '', 0, 1452503043, 0, 1),
(2, 'bank', 'abc', 'ABC', 'http://www.abchina.com/cn/', 'img_569370458b18d.jpg', '', '', 0, 1452503109, 0, 1),
(3, 'bank', 'bccb', 'Bank of Beijing', 'http://www.bankofbeijing.com.cn/', 'img_569370588dcdc.jpg', '', '', 0, 1452503128, 0, 1),
(4, 'bank', 'ccb', 'Construction Bank', 'http://www.ccb.com/', 'img_5693709bbd20f.jpg', '', '', 0, 1452503195, 0, 1),
(5, 'bank', 'ceb', 'China Everbright Bank', 'http://www.bankofbeijing.com.cn/', 'img_569370b207cc8.jpg', '', '', 0, 1452503218, 0, 1),
(6, 'bank', 'cib', 'Industrial Bank', 'http://www.cib.com.cn/cn/index.html', 'img_569370d29bf59.jpg', '', '', 0, 1452503250, 0, 1),
(7, 'bank', 'citic', 'CITIC Bank', 'http://www.ecitic.com/', 'img_569370fb7a1b3.jpg', '', '', 0, 1452503291, 0, 1),
(8, 'bank', 'cmb', 'China Merchants Bank', 'http://www.cmbchina.com/', 'img_5693710a9ac9c.jpg', '', '', 0, 1452503306, 0, 1),
(9, 'bank', 'cmbc', 'Minsheng Bank', 'http://www.cmbchina.com/', 'img_5693711f97a9d.jpg', '', '', 0, 1452503327, 0, 1),
(10, 'bank', 'comm', 'Bank of Communications&amp;uuml;', 'http://www.bankcomm.com/BankCommSite/default.shtml', 'img_5693713076351.jpg', '', '', 20, 1452503344, 0, 1),
(11, 'bank', 'gdb', 'Guangdong Development Bank', 'http://www.cgbchina.com.cn/', 'img_56937154bebc5.jpg', '', '', 0, 1452503380, 0, 1),
(12, 'bank', 'icbc', 'ICBC', 'http://www.icbc.com.cn/icbc/', 'img_56937162db7f5.jpg', '', '', 0, 1452503394, 0, 1),
(15, 'bank', 'szpab', 'Ping An Bank', 'http://bank.pingan.com/', '56c2e4c9aff85.jpg', '', '', 0, 1455613129, 0, 1),
(16, 'bank', 'alipay', 'Alipay', 'http://www.alipay.com', '', '', '', 1, 1452503439, 0, 1),
(17, 'bank', 'BOA', 'Bank of Americaü s', 'https://www.bankofamerica.com', '', NULL, NULL, 2, NULL, NULL, 1),
(18, 'crypto', 'USDT', 'USDT', 'https://tether.to/', '', NULL, NULL, 1, NULL, NULL, 1),
(24, 'bank', 'kotak', 'Kotak Mahindra', 'https://kotakbank.com', NULL, NULL, NULL, 3, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_coin`
--

DROP TABLE IF EXISTS `codono_user_coin`;
CREATE TABLE IF NOT EXISTS `codono_user_coin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `addtime` int(11) DEFAULT NULL,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `evm` varchar(42) DEFAULT NULL COMMENT 'esmart evm address for newer version of source',
  `usd` decimal(20,8) DEFAULT 0.00000000,
  `usdd` decimal(20,8) DEFAULT 0.00000000,
  `krb_tag` varchar(42) DEFAULT NULL COMMENT 'Tag for xrp,xmr',
  `xmr_tag` varchar(42) DEFAULT NULL COMMENT 'Tag for xrp,xmr',
  `xrp_tag` varchar(42) DEFAULT NULL COMMENT 'Tag for xrp',
  `dot` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dotd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dotb` varchar(99) DEFAULT NULL,
  `btc` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `btcd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `btcb` varchar(42) DEFAULT NULL,
  `eth` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ethd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ethb` varchar(42) DEFAULT NULL,
  `xrp` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xrpd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xrpb` varchar(42) DEFAULT NULL,
  `bch` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bchd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bchb` varchar(42) DEFAULT NULL,
  `usdt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `usdtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `usdtb` varchar(42) DEFAULT NULL,
  `eos` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `eosd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `eosb` varchar(42) DEFAULT NULL,
  `ltc` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ltcd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ltcb` varchar(42) DEFAULT NULL,
  `bsv` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bsvd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bsvb` varchar(42) DEFAULT NULL,
  `bnb` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bnbd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bnbb` varchar(42) DEFAULT NULL,
  `xlm` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xlmd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xlmb` varchar(42) DEFAULT NULL,
  `link` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `linkd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `linkb` varchar(42) DEFAULT NULL,
  `ada` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `adad` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `adab` varchar(42) DEFAULT NULL,
  `xmr` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xmrd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xmrb` varchar(42) DEFAULT NULL,
  `trx` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `trxd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `trxb` varchar(42) DEFAULT NULL,
  `dash` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dashd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dashb` varchar(42) DEFAULT NULL,
  `etc` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `etcd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `etcb` varchar(42) DEFAULT NULL,
  `neo` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `neod` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `neob` varchar(42) DEFAULT NULL,
  `atom` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `atomd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `atomb` varchar(42) DEFAULT NULL,
  `zec` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zecd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zecb` varchar(42) DEFAULT NULL,
  `doge` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `doged` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dogeb` varchar(42) DEFAULT NULL,
  `bat` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `batd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `batb` varchar(42) DEFAULT NULL,
  `rvn` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rvnd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rvnb` varchar(42) DEFAULT NULL,
  `waves` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `wavesd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `wavesb` varchar(42) DEFAULT NULL,
  `etn` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `etnd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `etnb` varchar(42) DEFAULT NULL,
  `grin` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `grind` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `grinb` varchar(42) DEFAULT NULL,
  `beam` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `beamd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `beamb` varchar(42) DEFAULT NULL,
  `dft` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dftd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `dftb` varchar(42) DEFAULT NULL,
  `eur` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `eurd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `eurb` varchar(42) DEFAULT NULL,
  `zar` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zard` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zarb` varchar(42) DEFAULT NULL,
  `krb` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `krbd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `krbb` varchar(42) DEFAULT NULL,
  `tsf` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tsfd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tsfb` varchar(42) DEFAULT NULL,
  `ugx` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ugxd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `ugxb` varchar(42) DEFAULT NULL,
  `bbt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bbtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bbtb` varchar(42) DEFAULT NULL,
  `try` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tryd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tryb` varchar(42) DEFAULT NULL,
  `wbtc` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `wbtcd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `wbtcb` varchar(42) DEFAULT NULL,
  `btt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bttd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bttb` varchar(42) DEFAULT NULL,
  `bttold` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bttoldd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `bttoldb` varchar(42) DEFAULT NULL,
  `tht` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `thtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `thtb` varchar(42) DEFAULT NULL,
  `tusdt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tusdtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tusdtb` varchar(42) DEFAULT NULL,
  `busdt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `busdtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `busdtb` varchar(42) DEFAULT NULL,
  `arbiusdt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `arbiusdtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `arbiusdtb` varchar(42) DEFAULT NULL,
  `xud` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xudd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `xudb` varchar(42) DEFAULT NULL,
  `rub` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rubd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rubb` varchar(42) DEFAULT NULL,
  `gnss` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `gnssd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `gnssb` varchar(42) DEFAULT NULL,
  `rota` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rotad` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `rotab` varchar(42) DEFAULT NULL,
  `tlnt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tlntd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `tlntb` varchar(42) DEFAULT NULL,
  `zrt` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zrtd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zrtb` varchar(42) DEFAULT NULL,
  `zb` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zbd` decimal(20,8) UNSIGNED DEFAULT 0.00000000,
  `zbb` varchar(42) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb3 COMMENT='Users Balance';

--
-- Dumping data for table `codono_user_coin`
--

INSERT INTO `codono_user_coin` (`id`, `addtime`, `userid`, `evm`, `usd`, `usdd`, `krb_tag`, `xmr_tag`, `xrp_tag`, `dot`, `dotd`, `dotb`, `btc`, `btcd`, `btcb`, `eth`, `ethd`, `ethb`, `xrp`, `xrpd`, `xrpb`, `bch`, `bchd`, `bchb`, `usdt`, `usdtd`, `usdtb`, `eos`, `eosd`, `eosb`, `ltc`, `ltcd`, `ltcb`, `bsv`, `bsvd`, `bsvb`, `bnb`, `bnbd`, `bnbb`, `xlm`, `xlmd`, `xlmb`, `link`, `linkd`, `linkb`, `ada`, `adad`, `adab`, `xmr`, `xmrd`, `xmrb`, `trx`, `trxd`, `trxb`, `dash`, `dashd`, `dashb`, `etc`, `etcd`, `etcb`, `neo`, `neod`, `neob`, `atom`, `atomd`, `atomb`, `zec`, `zecd`, `zecb`, `doge`, `doged`, `dogeb`, `bat`, `batd`, `batb`, `rvn`, `rvnd`, `rvnb`, `waves`, `wavesd`, `wavesb`, `etn`, `etnd`, `etnb`, `grin`, `grind`, `grinb`, `beam`, `beamd`, `beamb`, `dft`, `dftd`, `dftb`, `eur`, `eurd`, `eurb`, `zar`, `zard`, `zarb`, `krb`, `krbd`, `krbb`, `tsf`, `tsfd`, `tsfb`, `ugx`, `ugxd`, `ugxb`, `bbt`, `bbtd`, `bbtb`, `try`, `tryd`, `tryb`, `wbtc`, `wbtcd`, `wbtcb`, `btt`, `bttd`, `bttb`, `bttold`, `bttoldd`, `bttoldb`, `tht`, `thtd`, `thtb`, `tusdt`, `tusdtd`, `tusdtb`, `busdt`, `busdtd`, `busdtb`, `arbiusdt`, `arbiusdtd`, `arbiusdtb`, `xud`, `xudd`, `xudb`, `rub`, `rubd`, `rubb`, `gnss`, `gnssd`, `gnssb`, `rota`, `rotad`, `rotab`, `tlnt`, `tlntd`, `tlntb`, `zrt`, `zrtd`, `zrtb`, `zb`, `zbd`, `zbb`) VALUES
(87, 1700552924, 20, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(88, 1700552924, 41, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(89, 1700552924, 389, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(90, 1700552924, 1, NULL, '0.03086870', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '1.03300000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '29.12187500', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '27.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(91, 1700552924, 2, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '4.01209691', '0.00000000', '6176daefa7269b4b9ecf93fab698e262', '0.00100000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '262417.97187740', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '13.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(92, 1700552924, 3, NULL, '0.00308687', '0.00000000', NULL, NULL, '364418691', '0.00000000', '0.00000000', NULL, '3.99500000', '0.00000000', '3ec1a390e21bd270e286123ee1b2c260', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '300.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TLrAb25rL9JKfZB6vphytWH3r8db4TfavN', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TLrAb25rL9JKfZB6vphytWH3r8db4TfavN', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '5.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(93, 1700552924, 5, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '9.00040833', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '884.98510911', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(94, 1700552924, 6, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '4.01000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '400.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(95, 1700552924, 7, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '4.00000000', '0.00000000', '401007573e81a4d3af83363984e541c0', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '400.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(96, 1700552924, 8, NULL, '0.00000000', '0.00000000', NULL, NULL, '815866682', '0.00000000', '0.00000000', NULL, '9.00000000', '0.00000000', 'e2a6874aba7d63b26a5d3315cbb174fa', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '900.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(97, 1700552924, 9, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '6.00000000', '0.00000000', 'c9aca982eb65b43ee62a75fb615b0736', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '600.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(98, 1700552924, 10, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '4.19054543', '0.89813075', NULL, '13.31199299', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '794.19697320', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TPLpKW4RYVdcFuzF2ECL3Fr3vw88VbhfLS', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TPLpKW4RYVdcFuzF2ECL3Fr3vw88VbhfLS', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(99, 1700552924, 11, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '6.84689070', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '300.00198098', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.09211716', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(100, 1700552924, 12, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '1.00214557', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.90086087', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(101, 1700552924, 13, NULL, '100.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '6.00000000', '0.00000000', '1c762234363a3edc10a2930d330db099', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '70342.65961601', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(102, 1700552924, 14, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '12.28594721', '0.00000000', '21eedd88435c71f953911337a9c9418e', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '14011.04599481', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(103, 1700552924, 15, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '7.82902057', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '6882.27317102', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(104, 1700552924, 16, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '6.00000000', '0.00000000', NULL, '0.04752342', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '300.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(105, 1700552924, 17, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '3.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '185553.58349540', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '1.02579598', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(106, 1700552924, 18, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(107, 1700552924, 19, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(108, 1700552924, 21, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(109, 1700552924, 22, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(110, 1700552924, 23, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '3.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '300.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(111, 1700552924, 30, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '8.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '800.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TMdMDxBhFPQod2n9CySkd3TYQpoAqUxhUw', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TMdMDxBhFPQod2n9CySkd3TYQpoAqUxhUw', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(112, 1700552924, 31, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(113, 1700552924, 32, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL);
INSERT INTO `codono_user_coin` (`id`, `addtime`, `userid`, `evm`, `usd`, `usdd`, `krb_tag`, `xmr_tag`, `xrp_tag`, `dot`, `dotd`, `dotb`, `btc`, `btcd`, `btcb`, `eth`, `ethd`, `ethb`, `xrp`, `xrpd`, `xrpb`, `bch`, `bchd`, `bchb`, `usdt`, `usdtd`, `usdtb`, `eos`, `eosd`, `eosb`, `ltc`, `ltcd`, `ltcb`, `bsv`, `bsvd`, `bsvb`, `bnb`, `bnbd`, `bnbb`, `xlm`, `xlmd`, `xlmb`, `link`, `linkd`, `linkb`, `ada`, `adad`, `adab`, `xmr`, `xmrd`, `xmrb`, `trx`, `trxd`, `trxb`, `dash`, `dashd`, `dashb`, `etc`, `etcd`, `etcb`, `neo`, `neod`, `neob`, `atom`, `atomd`, `atomb`, `zec`, `zecd`, `zecb`, `doge`, `doged`, `dogeb`, `bat`, `batd`, `batb`, `rvn`, `rvnd`, `rvnb`, `waves`, `wavesd`, `wavesb`, `etn`, `etnd`, `etnb`, `grin`, `grind`, `grinb`, `beam`, `beamd`, `beamb`, `dft`, `dftd`, `dftb`, `eur`, `eurd`, `eurb`, `zar`, `zard`, `zarb`, `krb`, `krbd`, `krbb`, `tsf`, `tsfd`, `tsfb`, `ugx`, `ugxd`, `ugxb`, `bbt`, `bbtd`, `bbtb`, `try`, `tryd`, `tryb`, `wbtc`, `wbtcd`, `wbtcb`, `btt`, `bttd`, `bttb`, `bttold`, `bttoldd`, `bttoldb`, `tht`, `thtd`, `thtb`, `tusdt`, `tusdtd`, `tusdtb`, `busdt`, `busdtd`, `busdtb`, `arbiusdt`, `arbiusdtd`, `arbiusdtb`, `xud`, `xudd`, `xudb`, `rub`, `rubd`, `rubb`, `gnss`, `gnssd`, `gnssb`, `rota`, `rotad`, `rotab`, `tlnt`, `tlntd`, `tlntb`, `zrt`, `zrtd`, `zrtb`, `zb`, `zbd`, `zbb`) VALUES
(114, 1700552924, 33, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(115, 1700552924, 34, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(116, 1700552924, 35, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(117, 1700552924, 36, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(118, 1700552924, 37, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(119, 1700552924, 38, NULL, '649.40000000', '0.00000000', NULL, NULL, '382583843', '0.00000000', '0.00000000', NULL, '2.00000000', '0.00000000', 'f5cb2c9d1c4524007c8e629df8933dee', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '266981.64691132', '17712.15543600', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.31631936', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TDpKwMkBFgtT9Jdpan6AoKvLTfrsDjkcAZ', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '309.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '109.11733263', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', 'TDpKwMkBFgtT9Jdpan6AoKvLTfrsDjkcAZ', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '20060.49997000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(120, 1700552924, 39, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(121, 1700552924, 49, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(122, 1700552924, 50, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '6.02043588', '0.00000000', '10e8fa5fdd0555b43f261bbce8e1b9d8', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '65264.43609630', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '3703.00000000', '0.00000000', NULL, '287313.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(123, 1700552924, 62, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(124, 1700552924, 380, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(125, 1700552924, 382, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.01000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '9.50000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(126, 1700552924, 383, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(127, 1700552924, 384, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(128, 1700552924, 387, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(129, 1700552924, 388, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(130, NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.50000000', '0.00000000', '0', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL),
(131, NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, NULL, NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00205000', '0.00000000', '0', '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL, '0.00000000', '0.00000000', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_goods`
--

DROP TABLE IF EXISTS `codono_user_goods`;
CREATE TABLE IF NOT EXISTS `codono_user_goods` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `truename` varchar(200) DEFAULT NULL,
  `idcard` varchar(200) DEFAULT NULL,
  `cellphone` varchar(200) DEFAULT NULL,
  `addr` varchar(200) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  `prov` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_user_goods`
--

INSERT INTO `codono_user_goods` (`id`, `userid`, `name`, `truename`, `idcard`, `cellphone`, `addr`, `sort`, `addtime`, `endtime`, `status`, `prov`, `city`) VALUES
(2, 30, 'Leave at door', 'Scott Ot', '', '0123456789', 'Some address', NULL, 1523187302, NULL, 0, 'Beijing', 'Dongcheng Area'),
(3, 30, 'My Home', 'Scott', NULL, '386485768', 'SKhk', NULL, 1523260259, NULL, 1, 'Beijing', 'Dongcheng Area'),
(4, 30, 'Office Address', 'Codono Inc', NULL, '01123456789', 'Suit #5 , Street 5 , Block 2020', NULL, 1523260882, NULL, 1, 'Beijing', 'Dongcheng Area'),
(5, 30, 'Office 2', 'Simmon Ron', NULL, '75648756874', 'Glan Street Block #5 , Apt 206, 91011', NULL, 1523261144, NULL, 1, 'Beijing', 'Dongcheng Area'),
(6, 1, 'Home', 'hhhh', NULL, '8778686886', 'kjfjhdfd6 d78fgdf d78fhh,n jhdfdg', NULL, 1524817165, NULL, 1, 'USA', 'Los angeles'),
(9, 382, 'Test', 'Hello Man', NULL, '988388', 'nfn48f40999', NULL, 1635960880, NULL, 1, 'Turkey', 'istanbul'),
(11, 38, NULL, 'Mark Shawn', NULL, '1375734574', 'Road 5, Block 2-3, La, CA, USA', NULL, NULL, NULL, NULL, NULL, 'los angeles');

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_level`
--

DROP TABLE IF EXISTS `codono_user_level`;
CREATE TABLE IF NOT EXISTS `codono_user_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_id` tinyint(2) NOT NULL,
  `name` varchar(32) DEFAULT NULL,
  `form_id` varchar(64) DEFAULT NULL,
  `group_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=individual,1=institution',
  `limit_from` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `limit_to` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `codono_user_level`
--

INSERT INTO `codono_user_level` (`id`, `level_id`, `name`, `form_id`, `group_type`, `limit_from`, `limit_to`, `status`) VALUES
(1, 1, 'kyc1', 'e13941a90691154e452944782e5df3a66a55', 1, '100.00000000', '15000.00000000', 1),
(2, 2, 'kyc2', '44d5ad0b05502945860bb6f4cab7f7ef7572', 1, '15000.00000000', '50000.00000000', 1),
(3, 3, 'kyc3', '18596f110eb5b7444839df90d79848746c43', 1, '50000.00000000', '150000.00000000', 1),
(4, 1, 'kyb1', '65ca91480a475549d4290b78d5d01b227c4f', 2, '100.00000000', '10000.00000000', 1),
(5, 2, 'kyb2', '993bc15c0aa9274bad3afeb052ee3df19d75', 2, '10000.00000000', '50000.00000000', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_log`
--

DROP TABLE IF EXISTS `codono_user_log`;
CREATE TABLE IF NOT EXISTS `codono_user_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(200) DEFAULT NULL,
  `remark` varchar(200) DEFAULT NULL,
  `addip` varchar(40) DEFAULT NULL,
  `addr` varchar(200) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(10) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COMMENT='Users record sheet';

--
-- Dumping data for table `codono_user_log`
--

INSERT INTO `codono_user_log` (`id`, `userid`, `type`, `remark`, `addip`, `addr`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1717401870, NULL, 1),
(2, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1717586863, NULL, 1),
(3, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1718105905, NULL, 1),
(4, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1722243866, NULL, 1),
(5, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1722408514, NULL, 1),
(6, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1722420210, NULL, 1),
(7, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1722430211, NULL, 1),
(8, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1722430876, NULL, 1),
(9, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1723444790, NULL, 1),
(10, 38, 'APP log in', 'API User Login:2024-08-12 09:45:28', '127.0.0.1', '127.0.0.1', NULL, 1723455928, NULL, 1),
(11, 38, 'APP log in', 'API Username Login', '127.0.0.1', '127.0.0.1', NULL, 1723455928, NULL, 1),
(12, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724412810, NULL, 1),
(13, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724671391, NULL, 1),
(14, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724682094, NULL, 1),
(15, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724682727, NULL, 1),
(16, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724683249, NULL, 1),
(17, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724683560, NULL, 1),
(18, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724683611, NULL, 1),
(19, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724685147, NULL, 1),
(20, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1724833880, NULL, 1),
(21, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1725455111, NULL, 1),
(22, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1725868313, NULL, 1),
(23, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1726126195, NULL, 1),
(24, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1726150245, NULL, 1),
(25, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1726292170, NULL, 1),
(26, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1726575576, NULL, 1),
(27, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1729497691, NULL, 1),
(28, 38, 'Log In', 'Login with username', '::1', '::1', NULL, 1729497738, NULL, 1),
(29, 38, 'APP log in', 'API Username Login', '::1', '::1', NULL, 1730981289, NULL, 1),
(30, 13, 'APP log in', 'API Username Login', '::1', '::1', NULL, 1730998335, NULL, 1),
(31, 38, 'APP Login', '', '::1', '::1', NULL, 1731066953, NULL, 1),
(32, 38, 'APP Login', '', '::1', '::1', NULL, 1731067389, NULL, 1),
(33, 38, 'APP Login', '', '::1', '::1', NULL, 1731068307, NULL, 1),
(34, 38, 'APP Login', '', '::1', '::1', NULL, 1731068401, NULL, 1),
(35, 13, 'APP log in', 'API Username Login', '::1', '::1', NULL, 1731087417, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_logcoin`
--

DROP TABLE IF EXISTS `codono_user_logcoin`;
CREATE TABLE IF NOT EXISTS `codono_user_logcoin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adminid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `beforeedit` text DEFAULT NULL,
  `afteredit` text DEFAULT NULL,
  `account` tinyint(1) DEFAULT 0 COMMENT 'spot:0 p2p:1 nft:2 margin:3 staking:4 stock:5',
  `ipaddr` varchar(40) NOT NULL DEFAULT '--',
  `edittime` int(10) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_user_logcoin`
--

INSERT INTO `codono_user_logcoin` (`id`, `adminid`, `userid`, `beforeedit`, `afteredit`, `account`, `ipaddr`, `edittime`, `status`) VALUES
(17, 1, 383, '{\"xud\":\"0.00000000\",\"id\":\"78\"}', '{\"xud\":\"10000\",\"id\":\"78\"}', 0, '0.0.0.0', 1676984070, 0),
(18, 1, 38, '{\"btc\":\"39998879.62368746\",\"id\":\"32\"}', '{\"btc\":\"300\",\"id\":\"32\"}', 0, '0.0.0.0', 1678629424, 0),
(19, 1, 38, '{\"ugx\":\"131528912.22050999\",\"id\":\"32\"}', '{\"ugx\":\"2500\",\"id\":\"32\"}', 0, '0.0.0.0', 1678629606, 0),
(20, 1, 38, '{\"btc\":\"0.00000000\",\"id\":\"32\"}', '{\"btc\":\"1\",\"id\":\"32\"}', 0, '0.0.0.0', 1679384792, 0),
(21, 1, 38, '{\"btc\":\"102.80902031\",\"eth\":\"26.40763924\",\"usdt\":\"853.71833090\",\"ltc\":\"5.73568800\",\"bnb\":\"1.15926475\",\"id\":\"32\"}', '{\"btc\":\"1\",\"eth\":\"1\",\"usdt\":\"10000\",\"ltc\":\"1\",\"bnb\":\"1\",\"id\":\"32\"}', 0, '0.0.0.0', 1680265750, 0),
(22, 1, 11, '{\"usd\":\"0.00000000\",\"id\":\"11\"}', '{\"usd\":\"1000.00000000\",\"id\":\"11\"}', 0, '0.0.0.0', 1683553112, 0),
(23, 1, 5, '{\"bbt\":\"0.00000000\",\"id\":\"5\"}', '{\"bbt\":\"123\",\"id\":\"5\"}', 0, '0.0.0.0', 1685000624, 0),
(24, 1, 38, '{\"bnb\":\"0.97256417\",\"id\":\"32\"}', '{\"bnb\":\"0.0\",\"id\":\"32\"}', 0, '0.0.0.0', 1700548165, 0),
(25, 1, 38, '{\"bnb\":\"0.00000000\",\"id\":\"101\"}', '{\"bnb\":\"1\",\"id\":\"101\"}', 0, '0.0.0.0', 1700551724, 0),
(26, 1, 38, '{\"usdt\":\"0.00000000\",\"id\":\"119\"}', '{\"usdt\":\"16\",\"id\":\"119\"}', 0, '0.0.0.0', 1700553037, 0),
(27, 1, 38, '{\"bnb\":\"0.00000000\",\"id\":\"119\"}', '{\"bnb\":\"0.1\",\"id\":\"119\"}', 0, '0.0.0.0', 1700553069, 0),
(28, 1, 38, '{\"usdt\":\"0.00000000\",\"id\":\"119\"}', '{\"usdt\":\"100\",\"id\":\"119\"}', 0, '0.0.0.0', 1700556846, 0),
(29, 1, 38, '{\"usd\":\"0.00000000\",\"id\":\"119\"}', '{\"usd\":\"2\",\"id\":\"119\"}', 0, '0.0.0.0', 1700734257, 0),
(30, 1, 38, '{\"usdt\":\"76.00000000\",\"id\":\"119\"}', '{\"usdt\":\"77.00000000\",\"id\":\"119\"}', 0, '0.0.0.0', 1700734282, 0),
(31, 1, 38, '{\"usdt\":\"0.00000000\",\"id\":\"119\"}', '{\"usdt\":\"100\",\"id\":\"119\"}', 0, '::1', 1706535768, 0),
(32, 1, 38, '{\"gnss\":\"0.00000000\",\"id\":\"119\"}', '{\"gnss\":\"20000\",\"id\":\"119\"}', 0, '::1', 1707915225, 0),
(33, 1, 38, '{\"eth\":\"0.84117181\",\"id\":\"119\"}', '{\"eth\":\"2.84117181\",\"id\":\"119\"}', 0, '::1', 1708344015, 0),
(34, 1, 50, '{\"rota\":\"0.00000000\",\"tlnt\":\"0.00000000\",\"id\":\"122\"}', '{\"rota\":\"2.2\",\"tlnt\":\"2.3\",\"id\":\"122\"}', 0, '::1', 1708937202, 0),
(35, 1, 50, '{\"rota\":\"10304.20000000\",\"tlnt\":\"287248.30000000\",\"id\":\"122\"}', '{\"rota\":\"0\",\"tlnt\":\"0\",\"id\":\"122\"}', 0, '::1', 1708937948, 0),
(36, 1, 49, '{\"usdt\":\"0.00000000\",\"id\":\"121\"}', '{\"usdt\":\"1000\",\"id\":\"121\"}', 0, '::1', 1711362316, 0),
(37, 1, 38, '{\"usd\":\"0.00000000\",\"id\":\"119\"}', '{\"usd\":\"1000\",\"id\":\"119\"}', 0, '::1', 1724661801, 0),
(38, 1, 13, '{\"usd\":\"0.00000000\",\"id\":\"101\"}', '{\"usd\":\"100\",\"id\":\"101\"}', 0, '::1', 1731051690, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_shopaddr`
--

DROP TABLE IF EXISTS `codono_user_shopaddr`;
CREATE TABLE IF NOT EXISTS `codono_user_shopaddr` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `truename` varchar(200) NOT NULL DEFAULT '0',
  `cellphone` varchar(500) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_star`
--

DROP TABLE IF EXISTS `codono_user_star`;
CREATE TABLE IF NOT EXISTS `codono_user_star` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `pair` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_user_star`
--

INSERT INTO `codono_user_star` (`id`, `userid`, `pair`) VALUES
(1, 382, 'btc_usdt'),
(7, 38, 'bbt_usdt'),
(8, 38, 'ltc_try'),
(9, 38, 'eth_usdt'),
(10, 38, 'bnb_usdt');

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_subscription`
--

DROP TABLE IF EXISTS `codono_user_subscription`;
CREATE TABLE IF NOT EXISTS `codono_user_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL DEFAULT 0,
  `subid` int(10) NOT NULL DEFAULT 0,
  `addtime` int(10) DEFAULT NULL,
  `endtime` int(10) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `codono_user_wallet`
--

DROP TABLE IF EXISTS `codono_user_wallet`;
CREATE TABLE IF NOT EXISTS `codono_user_wallet` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `coinname` varchar(200) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `addr` varchar(200) DEFAULT NULL,
  `dest_tag` varchar(200) DEFAULT NULL COMMENT 'for xrp or xmr',
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `userid` (`userid`),
  KEY `coinname` (`coinname`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb3 COMMENT='Users wallet table';

--
-- Dumping data for table `codono_user_wallet`
--

INSERT INTO `codono_user_wallet` (`id`, `userid`, `coinname`, `name`, `addr`, `dest_tag`, `sort`, `addtime`, `endtime`, `status`) VALUES
(47, 382, 'btc', 'bin', '1CVCX8zSGmi3NDD5LjmZusQUcCUMWTEQtk', '', NULL, 1631799248, NULL, 1),
(48, 38, 'trx', 'trustlink', 'TRtmDCttcKfBQuizaZch16nRPbKiWG8jRc', '', NULL, 1640105902, NULL, 1),
(49, 38, 'tht', 'trustlink', 'TRtmDCttcKfBQuizaZch16nRPbKiWG8jRc', '', NULL, 1640152864, NULL, 1),
(50, 38, 'tusdt', 'tronlink', 'TRtmDCttcKfBQuizaZch16nRPbKiWG8jRc', '', NULL, 1640159409, NULL, 1),
(51, 38, 'usdt', 'jkjjkjk', 'TRtmDCttcKfBQuizaZch16nRPbKiWG8jRc', '', NULL, 1641288968, NULL, 1),
(52, 38, 'btc', 'btc binance', '1CVCX8zSGmi3NDD5LjmZusQUcCUMWTEQtk', '', NULL, 1641292244, NULL, 1),
(53, 38, 'xrp', 'Dont withdraw on it', 'r3kmLJN5D28dHuH8vZNUZpMC43pEHpaocV', '122434', NULL, 1641292411, NULL, 1),
(54, 39, 'xrp', 'local', 'r4r28F2rXSsHvGF3Yjouq2XBWGUhrDtpX2', '234335', NULL, 1654013930, NULL, 1),
(55, 38, 'trx', 'efc', 'TPPT4Cu3WHy7SKqM56C4EQ1xh9rgoRF3u7', '', NULL, 1658752044, NULL, 1),
(56, 38, 'trx', 'efcgood', 'TBHajSJHg9DTPPeAkdjVst75TGPY3M8mj5', '', NULL, 1658752218, NULL, 1),
(57, 38, 'trx', 'efcfinal', 'TECJkNfMqacZhS6ksUWo8XkTTx9ioYaJWA', '', NULL, 1658752991, NULL, 1),
(58, 38, 'dot', 'randomwestend', '5GaFTgkCF1vhXvTr3vhty75uTdobLU8ARMktT3TuR141WVHJ', '', NULL, 1664200749, NULL, 1),
(59, 383, 'usdt', 'USDT', '0x0711f7c5759Ff8F8e69b1c3113479Cd03a5936FB', '', NULL, 1676970884, NULL, 1),
(60, 383, 'arbiusdt', 'arbi goe', '0x0711f7c5759Ff8F8e69b1c3113479Cd03a5936FB', '', NULL, 1676978607, NULL, 1),
(61, 383, 'bnb', 'bg', '0x0711f7c5759Ff8F8e69b1c3113479Cd03a5936FB', '', NULL, 1676980280, NULL, 1),
(62, 383, 'xud', 'xud bg', '0x0711f7c5759Ff8F8e69b1c3113479Cd03a5936FB', '', NULL, 1676983941, NULL, 1),
(63, 38, 'btc', 'bin2', '1CV', 'no', NULL, 1678774673, NULL, 1),
(64, 38, 'eth', 'binance', '0xeb324ef91e6f2aa1cd2e09a6232e3cf0b7080882', '', NULL, 1679757442, NULL, 1),
(65, 38, 'btc', 'hh', '983j4j8fg3fiuf4n3i', '', NULL, 1696325605, NULL, 1),
(66, 38, 'dot', 'bin-dot', '0xeb324ef91e6f2aa1cd2e09a6232e3cf0b7080882', '', NULL, 1701937342, NULL, 1),
(67, 38, 'bnb', 'binance bnb', '0xeb324ef91e6f2aa1cd2e09a6232e3cf0b7080882', '', NULL, 1706895590, NULL, 1),
(68, 14, 'tusdt', 'trx', 'TGBCUF4B3ppD5zNymmXEHgFMWuStr2tTzt', '', NULL, 1726478226, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_verify`
--

DROP TABLE IF EXISTS `codono_verify`;
CREATE TABLE IF NOT EXISTS `codono_verify` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'index',
  `uid` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT NULL COMMENT 'email',
  `code` varchar(16) DEFAULT NULL COMMENT 'verification code',
  `createdon` timestamp NULL DEFAULT NULL COMMENT 'when',
  `attempts` tinyint(2) NOT NULL DEFAULT 0 COMMENT 'number of attempts',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `codono_verify`
--

INSERT INTO `codono_verify` (`id`, `uid`, `email`, `code`, `createdon`, `attempts`) VALUES
(1, 0, 'wroupon@gmail.com', '373840', NULL, 0),
(2, 0, 'wroupon@gmail.com', '632528', NULL, 0),
(3, 0, 'wroupon@gmail.com', '729962', NULL, 0),
(4, 0, 'wroupon@gmail.com', '303850', NULL, 1),
(5, 38, 'not-applicable', 'H2ZLWNAEDMKVSM4X', NULL, 4),
(6, 38, 'not-applicable', '7WKCK4RIJOY7YRB2', NULL, 1),
(7, 0, 'amber@codono.com', '989970', NULL, 0),
(8, 0, 'amber@codono.com', '791882', NULL, 0),
(9, 13, 'not-applicable', 'UC4K3TU56OAZCASN', NULL, 0),
(10, 13, 'not-applicable', 'BIWO7HWMGKNPN4QW', NULL, 0),
(11, 13, 'not-applicable', 'XLIJPVPDGC227NSO', NULL, 0),
(12, 13, 'not-applicable', 'RTYMOZ7ELSTMHII4', NULL, 0),
(13, 13, 'not-applicable', 'D3CHARDEGO54XD67', NULL, 0),
(14, 13, 'not-applicable', 'ED22FMWOWLJMJZ6U', NULL, 0),
(15, 13, 'not-applicable', '7E3H74MJTSLKKPBI', NULL, 0),
(16, 13, 'not-applicable', 'GE5PB4CL3IGEACKH', NULL, 0),
(17, 13, 'not-applicable', 'BWYVL4J2TFLVYKGR', NULL, 0),
(18, 13, 'not-applicable', 'LBTHG743MGO233NB', NULL, 0),
(19, 13, 'not-applicable', 'FJR7NZGXID5SFIGZ', NULL, 0),
(20, 13, 'not-applicable', 'CGGLOV2KKXVYEYFB', NULL, 0),
(21, 13, 'not-applicable', '3PWXIXQCGNI2V5H7', NULL, 0),
(22, 13, 'not-applicable', 'GCJ24F62JV6UKTQ2', NULL, 0),
(23, 13, 'not-applicable', '2L7V37DLKL6YTLXH', NULL, 0),
(24, 13, 'not-applicable', 'RYFPUKNGQ6AT5U5F', NULL, 0),
(25, 13, 'not-applicable', '3TNO6BTCQM47FYIO', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_version`
--

DROP TABLE IF EXISTS `codono_version`;
CREATE TABLE IF NOT EXISTS `codono_version` (
  `name` varchar(50) NOT NULL COMMENT 'Version number',
  `number` int(11) NOT NULL COMMENT 'Serial number, date generally designated by numeral',
  `title` varchar(50) NOT NULL COMMENT 'Version name',
  `create_time` int(11) NOT NULL COMMENT 'release time',
  `update_time` int(11) NOT NULL COMMENT 'Update of time',
  `log` text NOT NULL COMMENT 'Update Log',
  `url` varchar(150) NOT NULL COMMENT 'Link to a remote article',
  `is_current` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `id` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Automatic Updates table' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `codono_version_game`
--

DROP TABLE IF EXISTS `codono_version_game`;
CREATE TABLE IF NOT EXISTS `codono_version_game` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `gongsi` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'name',
  `shuoming` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'name',
  `class` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'name',
  `name` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `number` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='Application management table' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_version_game`
--

INSERT INTO `codono_version_game` (`id`, `gongsi`, `shuoming`, `class`, `name`, `title`, `status`) VALUES
(1, 'CODONOV2', 'online store', 'shop', 'shop', 'online store', 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_vote`
--

DROP TABLE IF EXISTS `codono_vote`;
CREATE TABLE IF NOT EXISTS `codono_vote` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  `coinname` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `type` int(20) UNSIGNED DEFAULT NULL,
  `sort` int(11) UNSIGNED DEFAULT NULL,
  `addtime` int(11) UNSIGNED DEFAULT NULL,
  `endtime` int(11) UNSIGNED DEFAULT NULL,
  `status` int(4) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_vote`
--

INSERT INTO `codono_vote` (`id`, `userid`, `coinname`, `title`, `type`, `sort`, `addtime`, `endtime`, `status`) VALUES
(1, 30, 'Recox', 'Recox', 1, NULL, 1523273210, NULL, 1),
(2, 30, 'Recox', 'Recox', 1, NULL, 1523273215, NULL, 1),
(3, 30, 'Recox', 'Recox', 1, NULL, 1523273365, NULL, 1),
(4, 30, 'Recox', 'Recox', 1, NULL, 1523274858, NULL, 1),
(5, 30, 'Recox', 'Recox', 1, NULL, 1523275144, NULL, 1),
(6, 30, 'Recox', 'Recox', 1, NULL, 1523275159, NULL, 1),
(7, 1, 'NEO', 'NEO', 1, NULL, 1526045968, NULL, 1),
(8, 1, 'XMR', 'Monero', 1, NULL, 1526045975, NULL, 1),
(9, 1, 'PART', 'Particl', 2, NULL, 1526046294, NULL, 1),
(10, 1, 'IOTA', 'IOTA', 1, NULL, 1526046817, NULL, 1),
(11, 1, 'IOTA', 'IOTA', 1, NULL, 1526046825, NULL, 1),
(12, 1, 'NXT', 'nxt', 1, NULL, 1526046845, NULL, 1),
(13, 38, 'NEO', 'NEO', 1, NULL, 1569841024, NULL, 1),
(14, 38, 'Recox', 'Recox', 1, NULL, 1569841033, NULL, 1),
(15, 38, 'doge', NULL, 1, NULL, 1569841042, NULL, 1),
(16, 38, 'PART', 'Particl', 1, NULL, 1598691487, NULL, 1),
(17, 38, 'neo', 'NEO', 1, NULL, 1614370526, NULL, 1),
(18, 38, 'neo', 'NEO', 1, NULL, 1614370553, NULL, 1),
(19, 38, 'Recox', 'Recox', 1, NULL, 1620376085, NULL, 1),
(20, 38, 'XMR', 'Monero', 1, NULL, 1666266512, NULL, 1),
(21, 38, 'XMR', 'Monero', 2, NULL, 1687430811, NULL, 1),
(22, 38, 'neo', 'NEO', 1, NULL, 1687430816, NULL, 1),
(23, 38, 'XMR', 'Monero', 1, NULL, 1689180457, NULL, 1),
(24, 38, 'PART', 'Particl', 1, NULL, 1705665212, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codono_vote_type`
--

DROP TABLE IF EXISTS `codono_vote_type`;
CREATE TABLE IF NOT EXISTS `codono_vote_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID AUTO INC',
  `coinname` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL COMMENT 'status',
  `img` varchar(255) DEFAULT NULL,
  `zhichi` bigint(20) UNSIGNED DEFAULT 0,
  `fandui` bigint(20) UNSIGNED DEFAULT 0,
  `zongji` bigint(20) UNSIGNED DEFAULT 0,
  `bili` float DEFAULT 0,
  `votecoin` varchar(50) DEFAULT NULL,
  `assumnum` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `codono_vote_type`
--

INSERT INTO `codono_vote_type` (`id`, `coinname`, `title`, `status`, `img`, `zhichi`, `fandui`, `zongji`, `bili`, `votecoin`, `assumnum`) VALUES
(1, 'Recox', 'Recox', 1, '/Upload/vote/5acb63f40afc8.jpg', 20, 2, 0, 1, 'xrp', '1'),
(2, 'NXT', 'nxt', 1, '/Upload/vote/5acb66eb0bb80.png', 0, 0, 0, 0, 'usd', '1'),
(3, 'IOTA', 'IOTA', 1, '/Upload/vote/5acb670765130.png', 0, 0, 0, 0, 'usd', '2'),
(4, 'XMR', 'Monero', 1, '/Upload/vote/5acb6746e5bc8.png', 0, 0, 0, 0, 'usd', '1'),
(5, 'neo', 'NEO', 1, '/Upload/vote/5acb678e6c660.png', 0, 0, 0, 0, 'usd', '1'),
(6, 'PART', 'Particl', 1, '/Upload/vote/5acb67be1ccf0.png', 0, 0, 0, 0, 'usd', '10');

-- --------------------------------------------------------

--
-- Table structure for table `codono_wallet_coin`
--

DROP TABLE IF EXISTS `codono_wallet_coin`;
CREATE TABLE IF NOT EXISTS `codono_wallet_coin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `coin` varchar(30) DEFAULT NULL,
  `fee_coin` varchar(30) DEFAULT NULL,
  `out_fee` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name_coin` (`name`,`coin`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `codono_wallet_fees`
--

DROP TABLE IF EXISTS `codono_wallet_fees`;
CREATE TABLE IF NOT EXISTS `codono_wallet_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `in_let` tinyint(1) NOT NULL DEFAULT 1,
  `out_let` tinyint(1) NOT NULL DEFAULT 1,
  `wallet_enable` tinyint(1) NOT NULL DEFAULT 1,
  `out_fee` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `codono_wallet_fees`
--

INSERT INTO `codono_wallet_fees` (`id`, `type`, `name`, `in_let`, `out_let`, `wallet_enable`, `out_fee`) VALUES
(14, 0, 'spot', 1, 1, 1, 0),
(15, 4, 'staking', 1, 1, 1, 0),
(16, 5, 'stock', 1, 1, 1, 0),
(17, 1, 'p2p', 1, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `codono_wallet_income`
--

DROP TABLE IF EXISTS `codono_wallet_income`;
CREATE TABLE IF NOT EXISTS `codono_wallet_income` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `tid` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `coin` varchar(20) DEFAULT NULL,
  `module` varchar(30) NOT NULL DEFAULT 'transfer',
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COMMENT='Site income using wallet transfer';
