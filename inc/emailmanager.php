<?php
/**
 * Main file to handle the custom mails
 */
class WPC_mail {
  
	/** singleton */
    private static $instance = null;
	
	/** vars **/
	private $_options_id_from		 = 'wpcmailoptionidfrom';
	private $_options_id_replyto	 = 'wpcmailoptionidreplyto';
	private $_options_id_tradutction = 'wpcmailoptionidtraduction';
	private $_options_template_default = 'wpcmailoptiontemplatedefault';
 
    /**
     * Creates or returns an instance
     */
    public static function get_instance(){ 
        if ( null == self::$instance ) {
            self::$instance = new self;
        } 
        return self::$instance; 
    }
	
	public function __construct(){
		add_filter( 'su/data/shortcodes', array($this,'register_avis_custom_shortcode'),10,1);

		add_action( 'init', array($this,'wpcem_register_cpts'),10);
		add_action( 'init', array($this,'wpcem_register_fields'),11);
		
		/**ADMIN**/
        if(!is_admin()){
            return;
        }
        add_action('admin_menu', array($this, 'wpcmail_menu'));
        add_action('admin_init', array($this, 'wpcmail_process_post'));
	}

  
	public function wpcmail_menu(){
		$page_title = 'WPC Email manager';
        $menu_title = 'WPC Email manager';
        $capability = 'manage_options';
        $menu_slug = 'wpcemailmanager';
        $function = array($this, 'wpcemailmanager_main_menu_options');
        $icon_url = 'dashicons-upload';

        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url);
	}
	
	public function wpcmail_process_post(){
		
		/** LIVE SEND MAIL **/
		if(isset($_POST['live_send_mail_form']) && $_POST['live_send_mail_form']){
			
			$data_mail = array(
			  'user_id'	=>$_POST['key_template'],
			  'body'	=>stripslashes($_POST['special_content']),
			  'to'		=>$_POST['toemail'],
			  'subject'	=>$_POST['subjectemail'],
			  'headers'	=>explode('|',$_POST['headersmail'])
			);
			
			$data_mail = apply_filters('data_before_live_send_mail_form',$data_mail,$_POST);
			
			$id_history_id = WPC_mail::get_instance()->wpcmail_mail_sender_live($_POST['key_template'], $data_mail);
			
			do_action('after_live_send_mail_form',$id_history_id);
		}		
		
		if(isset($_POST['form_from_default'])){
			$tosave_from = array();
			$tosave_from['name'] = $_POST['from_name_option'];
			$tosave_from['email'] = $_POST['from_email_option'];
			$tosave = json_encode($tosave_from);
			update_option($this->_options_id_from,$tosave);
		}
		
		if(isset($_POST['replyto_from_default'])){
			$tosave_replyto = array();
			$tosave_replyto['name'] = $_POST['replyto_name_option'];
			$tosave_replyto['email'] = $_POST['replyto_email_option'];
			$tosave = json_encode($tosave_replyto);
			update_option($this->_options_id_replyto,$tosave);			
		}
		
		if(isset($_POST['wpcmail_trad_id'])){
			update_option($this->_options_id_tradutction,$_POST['wpcmail_trad_id']);			
		}
		
		if(isset($_POST['wpcmail_template_options'])){			 
			
			$tosave = json_encode(
				array(
				  'header'=>$_POST['wpcmail_template_header'],
				  'footer'=>$_POST['wpcmail_template_footer']));
			update_option($this->_options_template_default,$tosave);	
		}
		
		if(isset($_POST['key_mail_template_test'])){
			$data = array();
			$data['tester'] = true;
			$data['array_replace_values_subject'] = 'ADDED SUBJECT';
			$data['array_replace_values_body'] = 'ADDED BODY';
			WPC_mail::get_instance()->wpcmail_mail_sender($_POST['key_mail_template_test'],$data);
		}
	}
	
	public function wpcmail_live_form_edit_mail_sender($key_template,$data_mail,$media_buttons=true){
		
		$email_infos = WPC_mail::get_instance()->wpcmail_mail_sender($key_template, $data_mail, false);
		ob_start();            		
		?>
		<div id="wpcmail_live_change" class="wrap" style="background-color:white;color:black;width: 50%;margin-left: 25%;">
			<form action="" method="post">
				<h2><?php echo apply_filters( 'wpc_email_live_title', __('LIVE email','wpc_emailmanager') ); ?></h2>
			
				<div>
					<span class="h-item"><?php echo __('Recipients:','wpc_emailmanager');?></span>
					<input type="text" name="toemail" value="<?php echo $email_infos['to']; ?>">
				</div>
				<div>
					<span class="h-item"><?php echo __('Subject:','wpc_emailmanager');?></span>
					<input type="text" name="subjectemail" value="<?php echo $email_infos['subject']; ?>">
				</div>
				<div>
					<span class="h-item"><?php echo __('Body:','wpc_emailmanager');?></span>
					<?php
					$editor_options = array(
					  'media_buttons'=>$media_buttons,
					);
					wp_editor( $email_infos['mail_text'], 'special_content',$editor_options); ?>
				</div>
				
				<?php do_action('emailmanager_hidden_fields_live_form'); ?>
				
				<input type="hidden" name="headersmail" value="<?php echo implode('|',$email_infos['headers']); ?>">
				<input type="hidden" name="key_template" value="<?php echo $key_template; ?>">
				<input type="hidden" name="user_id" value="<?php echo $data_mail['user_id']; ?>">
				<input type="hidden" name="live_send_mail_form" value="1">
				<input type="submit" class="button button-primary button-large" value="<?php echo apply_filters( 'wpc_email_live_submitval', __('Send','wpc_emailmanager') ); ?>">
				<?php
				$url_cancel = '';
				if(isset($data_mail['redirectafterlive'])){
					$url_cancel = $data_mail['redirectafterlive'];
				}
				?>
				<a class="acf-button button button-primary" 
				   style="margin-top: 10px;height: 30px;"
				   href="<?php echo$url_cancel; ?>"><?php echo apply_filters( 'wpc_email_live_cancelval', __('Cancel','wpc_emailmanager') ); ?></a>
			</form>
		</div>
		<?php
		$html_return = ob_get_contents();
		ob_end_clean();
		
		return $html_return;	
	}
	
	public function wpcemailmanager_main_menu_options(){

		//NORMAL PAGE
		echo '<div class="wrap">';
        echo '<h2>'.__('Options email manager','wpc_emailmanager').'</h2>';
		
		/* form option FROM default */
		$data_from = get_option($this->_options_id_from,false);		
		$value_name_from = "";
		$value_email_from = "";
		if($data_from){
			$data_from = json_decode($data_from,true);			
			$value_name_from = $data_from['name'];
			$value_email_from = $data_from['email'];
		}
		echo '<hr>';
		echo '<p>';
		echo '<form action="" method="POST" >';
		echo '<span>"From" default option</span>';
		echo '<div>';
			echo '<span>"From" name</span>';
			echo '<input type="text" name="from_name_option" value="'.$value_name_from.'">';
		echo '</div>';
		echo '<div>';
			echo '<span>"From" Email</span>';
			echo '<input type="text" name="from_email_option" value="'.$value_email_from.'">';
		echo '</div>';		
		echo '<input type="submit" value="'.__('Save','wpcemailmanager').'">';
		echo '<input type="hidden" name="form_from_default" value="form_from_default">';
		echo '</form>';
		echo '</p>';
		
		/* form option REPLY TO default */
		$data_replyto = get_option($this->_options_id_replyto,false);
		$value_name_replyto = "";
		$value_email_replyto = "";
		if($data_replyto){
			$data_replyto = json_decode($data_replyto,true);
			$value_name_replyto = $data_replyto['name'];
			$value_email_replyto = $data_replyto['email'];
		}
		echo '<hr>';
		echo '<p>';
		echo '<form action="" method="POST" >';
		echo '<span>"Reply to" default option</span>';
		echo '<div>';
			echo '<span>"Reply to" name</span>';
			echo '<input type="text" name="replyto_name_option" value="'.$value_name_replyto.'">';
		echo '</div>';
		echo '<div>';
			echo '<span>"From" Email</span>';
			echo '<input type="text" name="replyto_email_option" value="'.$value_email_replyto.'">';
		echo '</div>';			
		echo '<input type="submit" value="'.__('Save','wpcemailmanager').'">';
		echo '<input type="hidden" name="replyto_from_default" value="replyto_from_default">';
		echo '</form>';
		echo '</p>';
		
		/* form option traductoin id name default */
		$data_trad = get_option($this->_options_id_tradutction,false);
		$value_email_trad = "";
		if($data_trad){
			$value_email_trad = $data_trad;
		}
		echo '<hr>';
		echo '<p>';
		echo '<form action="" method="POST" >';
		echo '<div>';
			echo '<span>Traduction ID for POEDIT option</span>';
			echo '<input type="text" name="wpcmail_trad_id" value="'.$value_email_trad.'">';
		echo '</div>';		
		echo '<input type="submit" value="'.__('Save','wpcemailmanager').'">';
		echo '</form>';
		echo '</p>';
		
		/* form option traductoin id name default */
		$data_template = get_option($this->_options_template_default,false);
		$template_header = "";
		$template_footer = "";
		if($data_template){
			$data_template = json_decode($data_template,true);
			$template_header = $data_template['header'];
			$template_footer = $data_template['footer'];
		}
		echo '<hr>';
		echo '<p>';
		echo '<form action="" method="POST" >';
		echo '<div>';
			echo '<span>Template header</span>';
			echo '<input type="text" name="wpcmail_template_header" value="'.$template_header.'">';
		echo '</div>';
		echo '<div>';
			echo '<span>Template footer</span>';
			echo '<input type="text" name="wpcmail_template_footer" value="'.$template_footer.'">';
		echo '</div>';
		echo '<input type="submit" value="'.__('Save','wpcemailmanager').'">';
		echo '<input type="hidden" name="wpcmail_template_options" value="wpcmail_trad_id">';
		echo '</form>';
		echo '</p>';		
		
		/* test section */
		echo '<hr>';
		echo '<p>';
		echo '<form action="" method="POST" >';
		echo '<span>TEST MAIL</span>';
		echo '<input type="text" name="key_mail_template_test">';
		echo '<input type="submit" value="Test mail">';
		echo '</form>';
		echo '</p>';
		
		echo '</div>';
	}
	
	public function get_headerfooter_html($key_template,$headerfooter,$user_id){
		$poly_locale = false;
		if(is_plugin_active('polylang/polylang.php')){
			$poly_locale = pll_current_language('slug');
			if($user_id){
				$user_locale = get_user_locale($user_id);
				$poly_locale = substr($user_locale, 0,2);
			}
		}
		$text_html = '';
		
		if($key_template != ''){
			$sql = "SELECT * FROM `wp_options` WHERE `option_value` LIKE '".$key_template."' ORDER BY `option_id` ASC";
			global $wpdb;
			$results = $wpdb->get_results($sql);
			
			foreach($results as $result){			
				$front_key = str_replace('key_'.$headerfooter.'_name','',$result->option_name);
				
				$language_term_id = get_option($front_key.'language_'.$headerfooter);
				$language = get_term_by('id',$language_term_id,'language');
				$text_html = get_option($front_key.''.$headerfooter.'_html');
				
				if(isset($language->slug) && $poly_locale && $language->slug == $poly_locale){
					
					break;
				}
			}
		}
		return $text_html;
	}
	
	public function wpcmail_mail_sender_live($key,$data = array()){
		//used to find the post by an acf field
		$user_id = false;
		if(isset($data['user_id']) && $data['user_id']!=0 && $data['user_id']!=''){
			$user_id = $data['user_id'];
		}
				
		$post_acf_data = $this->wpcmail_get_email_type_by_field($key,$user_id);
		
		if(!$post_acf_data){
			return false;
		}
		
		//$template_part_header = get_field('template_name_header',$post_acf_data->ID);		
		$template_part_header_KEY = get_field('key_header_html_key',$post_acf_data->ID);		
		$template_part_header = $this->get_headerfooter_html($template_part_header_KEY,'header',$user_id);
		//footer
		//$template_part_footer = get_field('template_name_footer',$post_acf_data->ID);
		$template_part_footer_KEY = get_field('key_footer_html_key',$post_acf_data->ID);		
		$template_part_footer = $this->get_headerfooter_html($template_part_footer_KEY,'footer',$user_id);
		
		$body = $data['body'];
		
		$mail_text = $this->wpcmail_format_email_text($body,$data,$template_part_header,$template_part_footer);
		
		$to = $data['to'];
		$subject = $data['subject'];
		$headers = $data['headers'];
		
		$result_send_mail = wp_mail($to, $subject, $mail_text, $headers);
		$id_history_id = $this->wpcmail_save_history_mail($to, $subject, $mail_text, $key,$data);
		do_action('emailmanager_after_mail_send',$data,$id_history_id);
		
		return $id_history_id;
	}
	
	/**
	 * $data support :
	 * $data['list_emails'] -> add 
	 * $data['subject'] -> surcharge le sujet dans le template
	 * $data['array_replace_values_subject'] -> array of values to replace
	 * $data['array_replace_values_body'] -> array of values to replace
	 * $data['user_id'] -> user id to find the language template link to get_locale of the user
	 * 
	 * @param string $key
	 * @param array $data
	 */
	public function wpcmail_mail_sender($key,$data = array(),$send_email = true){

		//used to find the post by an acf field
		$user_id = false;
		if(isset($data['user_id']) && $data['user_id']!=0 && $data['user_id']!=''){
			$user_id = $data['user_id'];
		}
				
		$post_acf_data = $this->wpcmail_get_email_type_by_field($key,$user_id);
		
		if(!$post_acf_data){
			return false;
		}
		
		//HEADERS
		$headers = array();
		$headers[] = 'Content-Type: text/html; charset=UTF-8';		 

		//DESTINATAIRE
		if(!isset($data['string_to'])){
			$data['string_to'] = true;
		}
		$to = $this->wpcmail_get_destinataires_by_postid($post_acf_data->ID,$data);
		//SUBJECT
		$subject_data = get_field('email_template_subject',$post_acf_data->ID);
		$subject = $this->wpcmail_format_email_subject($subject_data,$data);
		//BODY TEXT
		$body = get_field('email_template_body',$post_acf_data->ID);
		
		//header
		$template_part_header = '';
		$template_part_footer = '';
		if($send_email){
			//$template_part_header = get_field('template_name_header',$post_acf_data->ID);		
			$template_part_header_KEY = get_field('key_header_html_key',$post_acf_data->ID);		
			$template_part_header = $this->get_headerfooter_html($template_part_header_KEY,'header',$user_id);
			//footer
			//$template_part_footer = get_field('template_name_footer',$post_acf_data->ID);
			$template_part_footer_KEY = get_field('key_footer_html_key',$post_acf_data->ID);		
			$template_part_footer = $this->get_headerfooter_html($template_part_footer_KEY,'footer',$user_id);
		}
				
		$mail_text = $this->wpcmail_format_email_text($body,$data,$template_part_header,$template_part_footer);
		//FROM
		$from_name = get_field('email_template_sender',$post_acf_data->ID);
		$from_email = get_field('email_template_sender_email',$post_acf_data->ID);
		if($from_name && $from_email){
			$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
		}elseif($from_email && !$from_email){
			$headers[] = 'From: '.$from_email.'';
		}		 
		//REPLY TO
		$reply_to_name = get_field('email_template_reply-to',$post_acf_data->ID);
		$reply_to_email = get_field('email_template_reply-to_email',$post_acf_data->ID);
		if($reply_to_name && $reply_to_email){
			$headers[] = 'Reply-To: '.$reply_to_name.' <'.$reply_to_email.'>';
		}elseif($reply_to_email){
			$headers[] = 'Reply-To: '.$reply_to_email.'';
		}

		// send email
		if($send_email){
			$result_send_mail = wp_mail($to, $subject, $mail_text, $headers);
			$id_history_id = $this->wpcmail_save_history_mail($to, $subject, $mail_text, $key,$data);
			
			do_action('emailmanager_after_mail_send',$data,$id_history_id);
			
			return $id_history_id;
		}else{
			
			return array(
			  'to'=>$to,
			  'subject'=>$subject,
			  'mail_text'=>$mail_text,
			  'headers'=>$headers
				);
		}
	}
	
    public function wpcem_register_cpts() {
		/** COURRIERS **/
		$labels_courriers = array(
			"name" => "Courriers",
			"singular_name" => "Courriers",
			"menu_name" => "Courriers"
		);
		$args_courriers = array(
			"labels" => $labels_courriers,
			"description" => "",
			"public" => true,
			"show_ui" => true,
			"has_archive" => false,
			"show_in_menu" => true,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => "", "with_front" => false ),
			"query_var" => false,			
			"publicly_queryable" => false,
			"supports" => array( "title" ),
		);
		register_post_type( "wpcem_mail_template", $args_courriers );
		
		/** E-MAILS **/
		$labels_emails = array(
			"name" => "E-mails historique",
			"singular_name" => "E-mail historique",
			"menu_name" => "E-mails historique"
		);
		$args_emails = array(
			"labels" => $labels_emails,
			"description" => "",
			"public" => true,
			"show_ui" => true,
			"has_archive" => false,
			"show_in_menu" => true,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => "", "with_front" => false ),
			"query_var" => false,			
			"publicly_queryable" => false,
			"supports" => array( "title" ),
		);
		register_post_type( "wpcem_email_history", $args_emails );
    }
  
	public function wpcem_register_fields(){
		//Get all array roles
		$list_roles = $this->get_all_user_roles();
		//Get all pre fabricated events
		global $wpc_emailevent_o;
		$keys_code_list = $wpc_emailevent_o->get_mailevents();
		array_unshift($keys_code_list,__('Select an event','wpc_emailmanager'));
		//GET FROM data
		$data_from = get_option($this->_options_id_from,'');
		if($data_from && $data_from!=''){
			$data_from = json_decode($data_from,true);
		}else{
			$data_from['name'] = '';
			$data_from['email'] = '';
		}
		//GET REPLY TO data
		$data_replyto = get_option($this->_options_id_replyto,'');
		if($data_replyto && $data_replyto!=''){
			$data_replyto = json_decode($data_replyto,true);
		}else{
			$data_replyto['name'] = '';
			$data_replyto['email'] = '';
		}
		//GET default template
		$data_template = get_option($this->_options_template_default,false);
		$template_header = '';
		$template_footer = '';
		if($data_replyto){
			$data_template = json_decode($data_template,true);
			$template_header = $data_template['header'];
			$template_footer = $data_template['footer'];
		}
		
		if( function_exists('acf_add_local_field_group') ){
			acf_add_local_field_group(array(
				'key' => 'group_5aa90ba279ba0',
				'title' => 'Email templates data',
				'fields' => array(
					array(
						'key' => 'field_5aa90bc702081',
						'label' => 'Subject',
						'name' => 'email_template_subject',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5aa90c2502082',
						'label' => 'Body',
						'name' => 'email_template_body',
						'type' => 'wysiwyg',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'tabs' => 'all',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5aa90c5602083',
						'label' => 'Sender name',
						'name' => 'email_template_sender',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => $data_from['name'],
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5aa90c5602084',
						'label' => 'Sender email',
						'name' => 'email_template_sender_email',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => $data_from['email'],
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),				  
					array(
						'key' => 'field_5aa90c9a02084',
						'label' => 'Reply-to name',
						'name' => 'email_template_reply-to',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => $data_replyto['name'],
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5aa90c9a02085',
						'label' => 'Email reply-to',
						'name' => 'email_template_reply-to_email',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => $data_replyto['email'],
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),				  
					array(
						'key' => 'field_5aa90f9202085',
						'label' => 'Target user group',
						'name' => 'email_template_target_user_group',
						'type' => 'checkbox',				  
						'instructions' => '', 
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => $list_roles,	
						'allow_custom' => 0,
						'save_custom' => 0,
						'default_value' => array(
						),
						'layout' => 'horizontal',
						'toggle' => 0,
						'return_format' => 'value',
					),
					array(
						'key' => 'field_5aa90fef02086',
						'label' => 'Target manual add',
						'name' => 'email_template_target_manual_add',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5aa91027ddee8',
						'label' => 'Email id code',
						'name' => 'email_id_code',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5ab22b1d0b013',
						'label' => 'Email id code selector',
						'name' => 'email_id_code_selector',
						'type' => 'select',				  
						'instructions' => '', 
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => $keys_code_list,
						'allow_custom' => 0,
						'save_custom' => 0,
						'default_value' => array(
						),
						'layout' => 'horizontal',
						'toggle' => 0,
						'return_format' => 'value',
					),
//					array(
//						'key' => 'field_5aa91027ddea2',
//						'label' => 'Template name header',
//						'name' => 'template_name_header',
//						'type' => 'text',
//						'instructions' => 'Name without the .php',
//						'required' => 0,
//						'conditional_logic' => 0,
//						'wrapper' => array(
//							'width' => '',
//							'class' => '',
//							'id' => '',
//						),
//						'default_value' => $template_header,
//						'placeholder' => '',
//						'prepend' => '',
//						'append' => '',
//						'maxlength' => '',
//					),
//					array(
//						'key' => 'field_5aa91027ddea3',
//						'label' => 'Template name footer',
//						'name' => 'template_name_footer',
//						'type' => 'text',
//						'instructions' => 'Name without the .php',
//						'required' => 0,
//						'conditional_logic' => 0,
//						'wrapper' => array(
//							'width' => '',
//							'class' => '',
//							'id' => '',
//						),
//						'default_value' => $template_footer,
//						'placeholder' => '',
//						'prepend' => '',
//						'append' => '',
//						'maxlength' => '',
//					),					  
					array(
						'key' => 'field_5aa91025ddea5',
						'label' => 'Key header name',
						'name' => 'key_header_html_key',
						'type' => 'text',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5aa91026ddea6',
						'label' => 'Key footer name',
						'name' => 'key_footer_html_key',
						'type' => 'text',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),				  
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'wpcem_mail_template',
						),
					),
//					array(
//						array(
//							'param' => 'post_type',
//							'operator' => '==',
//							'value' => 'wpcem_email_history',
//						),
//					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => 1,
				'description' => '',
			));

		}//end if function_exists("register_field_group")
		
		/** EMAILS SAVE **/
		acf_add_local_field_group(array(
			'key' => 'group_5aa91c23cb5c3',
			'title' => 'E-mails sauvegarde',
			'fields' => array(
				array(
					'key' => 'field_5aa90bc702083',
					'label' => 'Subject',
					'name' => 'email_subject_history',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				),			  
				array(
					'key' => 'field_5aa90c2502084',
					'label' => 'Body',
					'name' => 'email_body_history',
					'type' => 'wysiwyg',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'tabs' => 'all',
					'toolbar' => 'full',
					'media_upload' => 1,
					'delay' => 0,
				),			  
				array(
					'key' => 'field_5aa90fef02095',
					'label' => 'Target manual add', 
					'name' => 'email_to_history',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				),
				array(
					'key' => 'field_5aa91027ddee9',
					'label' => 'Email id code',
					'name' => 'email_id_code_history',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				),
				array(
					'key' => 'field_5aa91f52e14da',
					'label' => 'Mandrill Status',
					'name' => 'email_save_mandrill_status',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				),
				array(
					'key' => 'field_5aa920b6c6e12',
					'label' => 'Mandrill historic status',
					'name' => 'email_save_mandrill_historic_status',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				),			  
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'wpcem_email_history',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => 1,
			'description' => '',
		));
		
		
    }
	
	/**
	 * Return the post with data title, content and destinataire
	 * @param string $key_field_value
	 * @return post object
	 */
	private function wpcmail_get_email_type_by_field($key_field_value,$target_user_id,$field_key="email_id_code"){
		
		$args = array(
			'posts_per_page'	=> 1,
			'post_type'		=> 'wpcem_mail_template',
			'meta_query'	=> array(
				array(
					'key'		=> $field_key,
					'value'		=> $key_field_value,
					'compare'	=> '='
				)
			)
		);	
		$posts = get_posts( $args );
		if(isset($posts[0])){
			$return_post = $posts[0];

			if(is_plugin_active('polylang/polylang.php')){
				if(!$target_user_id){
					$target_user_id = get_current_user_id();
				}
				$user_locale = get_user_locale($target_user_id);
				$poly_locale = substr($user_locale, 0,2);
				$poly_locale = apply_filters('locale_polylang_find_post',$poly_locale,$field_key,$target_user_id);
				$post_id_translated = pll_get_post($posts[0]->ID,$poly_locale);
				if($post_id_translated){
					$return_post = get_post($post_id_translated);
				}
			}
		}else{
			$return_post = false;
		}
		
		return $return_post;
	}

	private function wpcmail_save_history_mail($to, $subject, $mail_text,$key, $data){
		//create history post
		$post_data = array(
			'post_type' => 'wpcem_email_history',
			'post_status' => 'publish',
			'post_title' => $subject,
		);
		$saved_post_id = wp_insert_post($post_data);
		if($saved_post_id){
			//do acf data
			update_field('email_to_history',$to,$saved_post_id);
			update_field('email_subject_history',$subject,$saved_post_id);
			update_field('email_body_history',$mail_text,$saved_post_id);
			update_field('email_id_code_history',$key,$saved_post_id);
		}
		return $saved_post_id;
	}
	
	/**
	 * Return subject 
	 * @param type $subject
	 * @param type $data
	 * @return string subject of the mail
	 */
	private function wpcmail_format_email_subject($subject,$data){
		
		//if isset overload the $subject of the template
		if(isset($data['subject'])){
			$subject = $data['subject'];
		}
		//translation if needed
		if(isset($this->namefiletranslation)){
			$subject = __($subject,$this->namefiletranslation);
		}
		//filter in case
		$subject = apply_filters( 'wpcmail_format_email_subject_filter', $subject);
		//change text with %client% specific changes (called from mailevents)
		$subject = $this->generic_text_change($subject,$data);
		//replace elements if there is
		if(isset($data['array_replace_values_subject']) && count($data['array_replace_values_subject'])>0){
			$subject = vsprintf($subject,$data['array_replace_values_subject']);			
		}
		
		$subject = preg_replace('#\%.*\%#', '', $subject);
		return $subject;
	}
	
	private function replace_all_links_in_text($text){
		
		$text = str_replace('<p>http', '<p> http', $text);
		
		$regex = '#(["><]?)(https?://[^\s"><\]]+)#im';
		$text = preg_replace_callback(
			$regex,
			function( $matches ) {
				if( !empty($matches[1]) ) {
					// do nothing
					return $matches[0];
				}
				$emailbrut = $matches[2];
				$replacement = '<a href="'.$emailbrut.'">'.$emailbrut.'</a>';
				return $replacement;
			},
			$text
		);
			
		return $text;
	}
	
	private function replace_elements_woocommerce($data){
		do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
		
		ob_start();
		
		
			echo $text;
			
			
			//save
			$mail_text = ob_get_contents();
		ob_end_clean();
		
		return $mail_text;
		
	}
	
	/**
	* format text for email
	* FILTER -> wpcmail_format_email_text_filter
	*/
	private function wpcmail_format_email_text($text,$data,$template_part_header,$template_part_footer){
		
		remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		
		if(isset($this->namefiletranslation)){
			$text = __($text,$this->namefiletranslation);
		}
		
		//change text with %client% specific changes (called from mailevents)
		$text = $this->generic_text_change($text,$data);		
		//change text generic data
		if(isset($data['array_replace_values_body']) && count($data['array_replace_values_body'])>0){
			$text = vsprintf($text,$data['array_replace_values_body']);			
		}
		//CHANGE LINKS to real links
		$text = $this->replace_all_links_in_text($text);
				
		//filster 
		$text = apply_filters( 'wpcmail_format_email_text_filter', $text);
		//apply the filters of wordpress
		$text = apply_filters('the_content', $text);
		//$text = $this->replace_elements_woocommerce($text,$data);
		
		if(isset($data['woocommerce'])){
			
			//woocommerce_email_order_details
			//woocommerce_email_order_meta
			//woocommerce_email_customer_details
			foreach ($data['woocommerce'] as $key_action=>$data_wook){
				$sent_to_admin = '';
				if(isset($data_wook['sent_to_admin'])){
					$sent_to_admin = $data_wook['sent_to_admin'];
				}
				$plain_text = '';
				if(isset($data_wook['plain_text'])){
					$plain_text = $data_wook['plain_text'];
				}
				$email = '';
				if(isset($data_wook['email'])){
					$email = $data_wook['email'];
				}
				
				ob_start();
					do_action( $key_action, $data_wook['order'] , $sent_to_admin, $plain_text, $email );
					//save
					$mail_text_woocommerce = ob_get_contents();
				ob_end_clean();
				$text = str_replace('%'.$key_action.'%', $mail_text_woocommerce, $text);
			}
			
		}
		
		$text = do_shortcode($text);
		
		$template_part_header = $this->replace_all_links_in_text($template_part_header);		
		$template_part_header = apply_filters('the_content', $template_part_header);
		
		$template_part_footer = $this->replace_all_links_in_text($template_part_footer);
		$template_part_footer = apply_filters('the_content', $template_part_footer);
		
		$text = apply_filters( 'emailmanager_email_body', $text );
		$template_part_header = apply_filters( 'emailmanager_email_header', $template_part_header );
		$template_part_footer = apply_filters( 'emailmanager_email_footer', $template_part_footer );
		ob_start();            
			//header
//			if($template_part_header && $template_part_header!=""){
//				get_template_part($template_part_header);
//			}
			echo $template_part_header;
			//text mail
			echo $text;
			//footer
			echo $template_part_footer;
//			if($template_part_footer && $template_part_footer!=""){
//				get_template_part($template_part_footer);
//			}
			//save
			$mail_text = ob_get_contents();
		ob_end_clean();
		
		//last clean
		$mail_text = preg_replace('#\%.*\%#', '', $mail_text);		
		return $mail_text;
	}
	
	private function generic_text_change($text_to_return,$data){
		if(isset($data['selectiv_change_text'])){
			foreach($data['selectiv_change_text'] as $code=>$texttoreplace){		
				$text_to_return = str_replace('%'.$code.'%', $texttoreplace, $text_to_return);			
			}		
		}
		//in case there is another %% not changed=> chenge for ""
		//$text_to_return = preg_replace('#\%.*\%#', '', $text_to_return);
		return $text_to_return;
	}
	
	private function get_all_user_roles(){
		$roles = array();
		if ( ! function_exists( 'get_editable_roles' ) ) {
			 require_once ABSPATH . 'wp-admin/includes/user.php';
		 }
		foreach (get_editable_roles() as $role_name => $role_info){
			$roles[strtolower($role_name)] = $role_name;
		}
		return $roles;
	}
   
	/**
	 * get all email coniguren in the email type
	 * ->DATA infos possibles
	 * 1)'string' true/false array or string return
	 * 2)'list_emails' string or array, add emails from the call of the function
	 * 
	 */
	private function wpcmail_get_destinataires_by_postid($post_ID_emailtype,$data = array()){
		
		$to_list = array();
		
		/* get group users */
		$target_user_group = get_field('email_template_target_user_group',$post_ID_emailtype);
		if($target_user_group){
			foreach($target_user_group as $key_edi=>$edi){
				$args_users = array(
					'role'		=> $edi, //role__in ne marchais pas
					'fields'	=> 'all',
				 ); 
				$all_users = get_users( $args_users );
				if($all_users){
					foreach($all_users as $user_to){
						$to_list[] = $user_to->user_email;
					}
				}
			}
		}
		
		/* get user mails list (string) */
		$target_manual_add = get_field('email_template_target_manual_add',$post_ID_emailtype);
		if($target_manual_add){
			$to_list[] = $target_manual_add;
		}
		
		/* list emails added from function call */
		if(isset($data['list_emails'])){
			if(is_array($data['list_emails'])){
				$to_list = array_merge($to_list,$data['list_emails']);
			}else{
				$to_list[] = $data['list_emails'];
			}
		}
		
		/* return string or array depending of option, default : array */
		if(isset($data['string_to'])){
			$to_list = implode(',', $to_list);
		}		
		return $to_list;		
	}   
}