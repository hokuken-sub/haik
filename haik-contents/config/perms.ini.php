<?php
/**
 *   Define Haik's file Permission
 *   -------------------------------------------
 *   /app/config/perms.ini.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/06/13
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

$perms = array(

	CONFIG_DIR    => 0777,
	BACKUP_DIR   => 0777,
	CACHE_DIR    => 0777,
	DATA_DIR     => 0777,
	DIFF_DIR     => 0777,
	META_DIR     => 0777,
	SKIN_DIR     => 0777,
	UPLOAD_DIR   => 0777,
	
	CSS_DIR      => 0755,
	IMAGE_DIR    => 0755,
	JS_DIR       => 0755,
	LIB_DIR      => 0755,
	PLUGIN_DIR   => 0755,
	
);

