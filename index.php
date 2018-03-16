<?php
/**
 *	@package W&Co Custom emails manager
 *	@author W&C
 *	@version 0.0.1
 */
/*
 Plugin Name: W&Co Custom emails manager
 Plugin URI: https://wordpressandco.fr/
 Description: WP&Co Custom emails manager
 Version: 0.0.1
 Author: Yann Dubois
 Author URI: https://wordpressandco.fr/
 License: GPL2
 */

include_once(dirname(__FILE__) . '/inc/emailmanager.php');

/** Controller Class **/
global $wpc_emailmanager_o;
$wpc_emailmanager_o = new WPC_mail();