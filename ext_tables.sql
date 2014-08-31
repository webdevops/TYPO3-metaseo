#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_metaseo_pagetitle varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_rel varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_prefix varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_suffix varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_is_exclude int(1) DEFAULT '0' NOT NULL,
	tx_metaseo_inheritance int(11) DEFAULT '0' NOT NULL,
	tx_metaseo_canonicalurl varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_priority int(11) DEFAULT '0' NOT NULL,
	tx_metaseo_change_frequency int(4) DEFAULT '0' NOT NULL,
	tx_metaseo_geo_lat varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_geo_long varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_geo_place varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_geo_region varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
	tx_metaseo_pagetitle varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_rel varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_prefix varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_pagetitle_suffix varchar(255) DEFAULT '' NOT NULL,
	tx_metaseo_canonicalurl varchar(255) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'tx_metaseo_cache'
#
CREATE TABLE tx_metaseo_cache (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	page_uid int(11) DEFAULT '0' NOT NULL,
	cache_section varchar(10) DEFAULT '' NOT NULL,
	cache_identifier varchar(10) DEFAULT '' NOT NULL,
	cache_content blob,
	PRIMARY KEY (uid),
	UNIQUE cache_key (page_uid,cache_section,cache_identifier),
	KEY cache_sect_id (cache_section,cache_identifier)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_metaseo_sitemap'
#
CREATE TABLE tx_metaseo_sitemap (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	page_rootpid int(11) DEFAULT '0' NOT NULL,
	page_uid int(11) DEFAULT '0' NOT NULL,
	page_language int(11) DEFAULT '0' NOT NULL,
	page_url varchar(500) DEFAULT '' NOT NULL,
	page_hash varchar(32) DEFAULT '' NOT NULL,
	page_depth int(4) DEFAULT '0' NOT NULL,
	page_change_frequency int(4) DEFAULT '0' NOT NULL,
	page_type int(11) DEFAULT '0' NOT NULL,

	is_blacklisted int(1) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),

	UNIQUE page_identification (page_uid,page_language,page_hash),
	KEY language_path (page_rootpid,page_language,page_depth),
	KEY page_depth (page_depth),
	KEY blacklisted (is_blacklisted)
) ENGINE=InnoDB;


#
# Table structure for table 'tx_metaseo_setting_root'
#
CREATE TABLE tx_metaseo_setting_root (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,

	is_sitemap int(1) DEFAULT '1' NOT NULL,
	is_sitemap_page_indexer int(1) DEFAULT '1' NOT NULL,
	is_sitemap_typolink_indexer int(1) DEFAULT '1' NOT NULL,
	is_sitemap_language_lock int(1) DEFAULT '0' NOT NULL,
	sitemap_page_limit int(11) DEFAULT '0' NOT NULL,
	sitemap_priorty float DEFAULT '1' NOT NULL,
	sitemap_priorty_depth_multiplier float DEFAULT '1' NOT NULL,
	sitemap_priorty_depth_modificator float DEFAULT '1' NOT NULL,

	is_robotstxt int(1) DEFAULT '1' NOT NULL,
	is_robotstxt_sitemap_static int(1) DEFAULT '0' NOT NULL,
	robotstxt text,
	robotstxt_additional text,

	deleted int(1) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
  KEY pid (pid),
  KEY deleted (deleted)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_metaseo_tag'
#
CREATE TABLE tx_metaseo_metatag (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  cruser_id int(11) DEFAULT '0' NOT NULL,

  sys_language_uid int(11) DEFAULT '0' NOT NULL,

  tag_name varchar(50) DEFAULT '' NOT NULL,
  tag_subname varchar(50) DEFAULT '' NOT NULL,
  tag_value text,
  tag_group int(11) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  UNIQUE metatag (pid,sys_language_uid,tag_name,tag_group,tag_subname),
  KEY tag_group (tag_group),
  KEY sys_language_uid (sys_language_uid),
  KEY pid (pid)
) ENGINE=InnoDB;
