<?php
/**
 * Main file to handle the custom mails
 */
class WPC_mail_events {
  	
	/** vars **/
	private $_list_events = array();
 	
	public function __construct(){
		//$this->init_list_events();
		//carefull that add_action( 'init', array($this,'wpcem_register_fields'),300); in emailmanager.php contruct is later than this one
		add_action( 'init', array($this,'init_list_events'),200 ); 
	}
  
	/**
	 * @return array list of events
	 */
	public function get_mailevents(){
		return $this->_list_events;
	}
	
	/**
	 * List of events and loop to start all listeners events
	 */
	public function init_list_events(){
		$this->_list_events = array(
		  //'woocommerce_order_completed_init' => __('Woocommerce order completed','wpc_emailmanager'),
		  'wordpress_new_user_init' => __('Wordpress new user','wpc_emailmanager'),
		  'wordpress_new_user_admin_init' => __('Wordpress new user for admin','wpc_emailmanager'),
		  'wordpress_retrieve_password_init' => __('Wordpress retrieve password','wpc_emailmanager'),
		  'wordpress_retrieve_password_admin_init' => __('Wordpress retrieve password admin','wpc_emailmanager'),
		);
		
		foreach($this->_list_events as $list_events_key=>$list_events){
			$this->$list_events_key();
		}
	}
	
	/**
	 * WOOCOMMERCE EVENTS /////////////////////////////////////////////////////////////////////////
	 */
	
	/**
	 * Woocommerce order completed
	 * init add action
	 */
	private function woocommerce_order_completed_init(){
		if ( class_exists( 'WooCommerce' ) ) {
			add_action('woocommerce_order_status_changed', 'wpc_mail_order_completed', 90, 3);
		}
	}
	/**
	 * Woocommerce order completed
	 * send mail
	 * codes possibles : 
	 * %client%
	 */
	public function wpc_mail_order_completed($order_id, $old_status, $new_status){
		$wpc_mail_o = WPC_mail::get_instance();
//TODO get infos client or other and send it in data
		
//		$mail_template = $wpc_mail_o->wpcmail_get_email_type_by_field('wpc_mail_order_completed_key','email_id_code_selector');		
//		if($mail_template){
//			$data = array();
//$data['selectiv_change_text']['client'] = "name client TODO";
//			$wpc_mail_o->wpcmail_mail_sender('woocommerce_order_completed',$data);
//		}
	}
	
	/**
	 * WORDPRESS ACTIONS /////////////////////////////////////////////////////////////////////////
	 */
	
	/*
	 * NEW USER /////////////////////////////////////////////////////////////////////////
	 */	
	/**
	 * new user -> to whoever configure in the mail template
	 * @return type
	 */
	private function wordpress_new_user_admin_init(){
		//ADMIN PART
		$args = array(
			'posts_per_page'   => 1,
			'meta_key'         => 'email_id_code_selector',
			'meta_value'       => 'wordpress_new_user_admin_init',
			'post_type'        => 'wpcem_mail_template',
			'lang' => ''
		);
		$list_emails_template = get_posts($args);
		
		if(!$list_emails_template || count($list_emails_template)<1){
			//we dp not overwrite
			return;
		}
		
		// Moderate user upon registration
		add_action( 'register_new_user', array( $this, 'wordpress_new_user_admin_message' ), 201 );
	}
	
	public function wordpress_new_user_admin_message($user_id){
		global $wpdb;
		
		$wpc_mail_o = WPC_mail::get_instance();

		// Set user role to "pending"
		$user_info = get_userdata( $user_id );

		//data
		$data = array();
		$data['selectiv_change_text']['user_login'] = $user_info->user_login;
		$data['selectiv_change_text']['user_ip'] = $_SERVER['REMOTE_ADDR'];
		$data['selectiv_change_text']['user_edit_admin'] = site_url().'/wp-admin/user-edit.php?user_id='.$user_id;

		//to the user
		//$data['list_emails'] = 
		$response = $wpc_mail_o->wpcmail_mail_sender_from_mailevents('wordpress_new_user_admin_init',$data);	
	}
	
	/**
	 * new user 
	 * @return type
	 */
	private function wordpress_new_user_init(){
		
		//USER PART
		$args = array(
			'posts_per_page'   => 1,
			'meta_key'         => 'email_id_code_selector',
			'meta_value'       => 'wordpress_new_user_init',
			'post_type'        => 'wpcem_mail_template',
			'lang' => ''
		);
		$list_emails_template = get_posts($args);
				
		if(!$list_emails_template || count($list_emails_template)<1){
			//we dp not overwrite
			return;
		}
		
		// Remove default new user notification
		if ( has_action( 'register_new_user', 'wp_send_new_user_notifications' ) ){
			remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		}

		// Remove Custom Email new user notification
		if ( class_exists( 'Theme_My_Login_Custom_Email' ) ) {
			$custom_email = Theme_My_Login_Custom_Email::get_object();
			if ( has_action( 'register_new_user', array( $custom_email, 'new_user_notification' ) ) )
				remove_action( 'register_new_user', array( $custom_email, 'new_user_notification' ) );
		}
		
		// Moderate user upon registration
		add_action( 'register_new_user', array( $this, 'wordpress_new_user_message' ), 200 );
	}
	
	public function wordpress_new_user_message($user_id){
		global $wpdb;
		
		$wpc_mail_o = WPC_mail::get_instance();

		// Set user role to "pending"
		$user_info = get_userdata( $user_id );

		// Generate something random for a password reset key
		$key = wp_generate_password( 20, false );
		do_action( 'retrieve_password_key', $user_info->user_login, $key );
		// Now insert the key, hashed, into the DB
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_info->user_login ) );
		
		//data
		$data = array();
		$data['selectiv_change_text']['user_login'] = $user_info->user_login;
		$data['selectiv_change_text']['link_login'] = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_info->user_login ), 'login' );
		$data['selectiv_change_text']['link_login_simple'] = wp_login_url();

		//to the user
		$data['list_emails'] = $user_info->user_email;

		$response = $wpc_mail_o->wpcmail_mail_sender_from_mailevents('wordpress_new_user_init',$data);
	}
	
	/*
	 * RETRIEVE PASSWORD ADMIN /////////////////////////////////////////////////////////////////////////
	 */
	
	private function wordpress_retrieve_password_admin_init(){
		$args = array(
			'posts_per_page'   => 1,
			'meta_key'         => 'email_id_code_selector',
			'meta_value'       => 'wordpress_retrieve_password_admin_init',
			'post_type'        => 'wpcem_mail_template',
			'lang' => ''
		);
		$list_emails_template = get_posts($args);
		
		if(!$list_emails_template || count($list_emails_template)<1){
			//we dp not overwrite
			return;
		}
		add_action( 'retrieve_password', array( $this, 'add_password_reset_filters' ) );
	}
	
	public function add_password_reset_filters(){
		add_filter( 'retrieve_password_title',   array( $this, 'send_mail_retrieve_password_admin'   ), 10, 3 );
	}
	
	public function send_mail_retrieve_password_admin($title, $user_login, $user_data ) {
		
		$wpc_mail_o = WPC_mail::get_instance();
		//data
		$data = array();
		$data['selectiv_change_text']['user_login'] = $user_login;
		$data['selectiv_change_text']['user_ip'] = $_SERVER['REMOTE_ADDR'];
		$data['selectiv_change_text']['user_edit_admin'] = site_url().'wp-admin/user-edit.php?user_id='.$user_data->ID;
		$wpc_mail_o->wpcmail_mail_sender_from_mailevents('wordpress_retrieve_password_admin_init',$data);		
	
		//MANDATORY
		return $title;
	}
	
	
	/*
	 * RETRIEVE PASSWORD -> for user /////////////////////////////////////////////////////////////////////////
	 */	
	/**
	 * retrieve password 
	 * @return type
	 */
	private function wordpress_retrieve_password_init(){
		$args = array(
			'posts_per_page'   => 1,
			'meta_key'         => 'email_id_code_selector',
			'meta_value'       => 'wordpress_retrieve_password_init',
			'post_type'        => 'wpcem_mail_template',
			'lang' => ''
		);
		$list_emails_template = get_posts($args);
		
		if(!$list_emails_template || count($list_emails_template)<1){
			//we dp not overwrite
			return;
		}
		
		add_action( 'retrieve_password', array( $this, 'add_retrieve_pass_filters'  ) );
	}
	
	public function add_retrieve_pass_filters(){
		add_filter( 'wp_mail_content_type' , array($this,'retrieve_password_content_type'));
		add_filter( 'retrieve_password_title',   array( $this, 'retrieve_pass_title_filter'   ), 10, 3 );
		add_filter( 'retrieve_password_message', array( $this, 'retrieve_pass_message_filter' ), 10, 4 );
	}
	
	public function retrieve_password_content_type($content_type){
		return 'text/html';
	}
	
	public function retrieve_pass_title_filter($title, $user_login, $user_data ) {
		
		$wpc_mail_o = WPC_mail::get_instance();
		
		$data = array();
		//$data['selectiv_change_text']['loginurl'] = site_url( 'wp-login.php', 'login' );		
		
		$data_mail = $wpc_mail_o->wpcmail_mail_sender_from_mailevents('wordpress_retrieve_password_init',$data,false);
		
		$title = $data_mail['subject'];
		
		return $title;
	}
	
	public function retrieve_pass_message_filter( $message, $key, $user_login, $user_data ) {
		
		$wpc_mail_o = WPC_mail::get_instance();
		
		$data = array();
		$data['selectiv_change_text']['loginurl'] = site_url( 'wp-login.php', 'login' );
		$data['selectiv_change_text']['reseturl'] = site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );		
		
		$data_mail = $wpc_mail_o->wpcmail_mail_sender_from_mailevents('wordpress_retrieve_password_init',$data,false);
		
		$message = $data_mail['mail_text'];
			
		return $message;
	}
}