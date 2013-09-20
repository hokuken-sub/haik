<?php
/**
 *   I18n
 *   -------------------------------------------
 *   lib/i18n.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 13/01/18
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function __($text, $domain = 'default')
{
	return $text;
}

function _e($text, $domain = 'default')
{
	echo $text;
}

function _x($text, $context, $domain = 'default')
{
	return $text;
}

function _ex( $text, $context, $domain = 'default' ) {
	echo _x( $text, $context, $domain );
}

function _n( $single, $plural, $number, $domain = 'default' ) {
	return $single;
}

function _nx($single, $plural, $number, $context, $domain = 'default') {
	return $single;
}




/* End of file i18n.php */
/* Location: lib/i18n.php */