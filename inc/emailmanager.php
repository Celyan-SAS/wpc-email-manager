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
        add_action('admin_menu', array($this, 'importcsv_menu'));
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
		
		if(isset($_POST['form_from_default'])){
			$tosave_from = array();
			$tosave_from[] = $_POST['from_name_option'];
			$tosave_from[] = $_POST['from_email_option'];
			$tosave = json_encode($tosave_from);
			update_option($this->_options_id_from,$tosave);
		}
		
		if(isset($_POST['replyto_from_default'])){
			$tosave_replyto = array();
			$tosave_replyto[] = $_POST['replyto_name_option'];
			$tosave_replyto[] = $_POST['replyto_email_option'];
			$tosave = json_encode($tosave_replyto);
			update_option($this->_options_id_replyto,$tosave);			
		}
		
		if(isset($_POST['wpcmail_trad_id'])){
			
		}
	}
	
	public function wpcemailmanager_main_menu_options(){
		echo '<div class="wrap">';
        echo '<h2>'.__('Options email manager','wpc_emailmanager').'</h2>';
		
		/* form option FROM default */
		$data_from = get_option($this->_options_id_from,false);
		$value_name_from = "";
		$value_email_from = "";
		if($data_from){
			$data_from = json_decode($data_from);
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
			$data_replyto = json_decode($data_replyto);
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
		echo '<hr>';
		echo '<p>';		
		echo '<form action="" method="POST" >';
		echo '<div>';
			echo '<span>Traduction ID for POEDIT option</span>';
			echo '<input type="text" name="trad_id">';
		echo '</div>';		
		echo '<input type="submit" value="'.__('Save','wpcemailmanager').'">';
		echo '<input type="hidden" name="wpcmail_trad_id" value="wpcmail_trad_id">';
		echo '</form>';
		echo '</p>';		
		
		echo '</div>';
	}
	
	/**
	 * $data support :
	 * $data['list_emails'] -> add 
	 * $data['subject'] -> surcharge le sujet dans le template
	 * $data['array_replace_values_subject'] -> array of values to replace
	 * $data['array_replace_values_body'] -> array of values to replace
	 * 
	 * @param string $key
	 * @param array $data
	 */
	public function wpcmail_mail_sender($key,$data = array()){

		 //used to find the post by an acf field
		 $post_acf_data = $this->wpcmail_get_email_type_by_field($key);
		 if(!$post_acf_data){
			 return false;
		 }
		 
		 //HEADERS
		 $headers = array();
		 $headers[] = 'Content-Type: text/html; charset=UTF-8';		 
		 
		 //DESTINATAIRE
		 $to = $this->wpcmail_get_destinataires_by_postid($post_acf_data->ID,$data);
		 //SUBJECT
		 $subject_data = get_field('email_template_subject',$post_acf_data->ID);
		 $subject = $this->wpcmail_format_email_subject($subject_data,$data);
		 //BODY TEXT
		 $body = get_field('$post_acf_data->post_content',$post_acf_data->ID);
		 $mail_text = $this->wpcmail_format_email_text($body,$data);
		 //FROM
		 $from_name = get_field('email_template_sender',$post_acf_data->ID);
		 $from_email = get_field('email_template_sender_email',$post_acf_data->ID);
		 if($from_name && $from_email){
			 $headers[] = 'From: '.$from_name.' <'.$from_email.'>';
		 }elseif($from_email){
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
		 $result = wp_mail($to, $subject, $mail_text, $headers);
		 
		 if($result){
			$this->wpcmail_save_history_mail($to, $subject, $mail_text, $headers);
		 }		 
		 return $result;
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
						'default_value' => '',
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
						'default_value' => '',
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
						'default_value' => '',
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
						'default_value' => '',
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
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'wpcem_mail_template',
						),
					),
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

		}//end if function_exists("register_field_group")
		
		/** EMAILS SAVE **/
		acf_add_local_field_group(array(
			'key' => 'group_5aa91c23cb5c3',
			'title' => 'E-mails sauvegarde',
			'fields' => array(
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
	private function wpcmail_get_email_type_by_field($key_field_value){
		$args = array(
			'posts_per_page'	=> 1,
			'post_type'		=> 'wpcem_mail_template',
			'meta_query'	=> array(
				array(
					'key'		=> 'email_id_code',
					'value'		=> $key_field_value,
					'compare'	=> '='
				)
			)
		);	
		$posts = get_posts( $args );
		return $posts[0];
	}

	private function wpcmail_save_history_mail(){
		
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
		$subject = __($subject,$this->namefiletranslation);
		//filter in case
		$subject = apply_filters( 'wpcmail_format_email_subject_filter', $subject);
		
		//replace elements if there is
		if(count($data['array_replace_values_subject'])>0){
			$subject = vsprintf($subject,$data['array_replace_values_subject']);			
		}
		
		return $subject;
	}
	
	/**
	* format text for email
	 * FILTER -> wpcmail_format_email_text_filter
	*/
	private function wpcmail_format_email_text($text,$data){
		
		$text = __($text,$this->namefiletranslation);
		$text = apply_filters( 'wpcmail_format_email_text_filter', $text);
		$text = apply_filters('the_content', $text);		
		if(count($data['array_replace_values_body'])>0){
			$text = vsprintf($text,$data['array_replace_values_body']);			
		}
		 
		ob_start();            
			//header
			get_template_part('template-email_header');
			//text mail
			echo $text;
			//footer
			get_template_part('template-email_footer');
			//save
			$mail_text = ob_get_contents();
		ob_end_clean();
		return $mail_text;
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
		if(isset($data['string'])){
			$to_list = implode(',', $to_list);
		}		
		return $to_list;		
	}   
}