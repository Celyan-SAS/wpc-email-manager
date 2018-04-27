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

include_once(dirname(__FILE__) . '/inc/mail_events.php');
include_once(dirname(__FILE__) . '/inc/emailmanager.php');

load_plugin_textdomain( 'wpc_emailmanager', false, plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );

/** Controller Class **/
global $wpc_emailevent_o;
$wpc_emailevent_o = new WPC_mail_events();

global $wpc_emailmanager_o;
$wpc_emailmanager_o = new WPC_mail();