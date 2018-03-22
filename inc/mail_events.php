<?php
/**
 * Main file to handle the custom mails
 */
class WPC_mail_events {
  	
	/** vars **/
	private $_list_events = array();
 	
	public function __construct(){
		$this->init_list_events();
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
	private function init_list_events(){
		$this->_list_events = array(
		  'woocommerce_order_completed_init' => __('Woocommerce order completed','wpc_emailmanager'),
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
		
		$mail_template = $wpc_mail_o->wpcmail_get_email_type_by_field('wpc_mail_order_completed_key','email_id_code_selector');		
		if($mail_template){
			$data = array();
$data['selectiv_change_text']['client'] = "name client TODO";
			$wpc_mail_o->wpcmail_mail_sender('woocommerce_order_completed',$data);
		}
	}
}