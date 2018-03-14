<?php
/**
 * Main file to handle the custom mails
 */
class WPC_mail {
  
	/** singleton */
    private static $instance = null;
 
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
		add_action("wp_enqueue_scripts", array($this,"wpcmail_scripts_enqueue"));

		add_action( 'init', array($this,'wpcem_register_cpts'),10);
		add_action( 'init', array($this,'wpcem_register_fields'),11);
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
						'label' => 'Email sender',
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
						'key' => 'field_5aa90c9a02084',
						'label' => 'Email reply-to',
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
						'key' => 'field_5aa90f9202085',
						'label' => 'Target user group',
						'name' => 'email_template_target_user_group',
						'type' => 'checkbox',
						'instructions' => 'administrator : Administrateurs', //TODO AJOUTER DYNAMIQUEMENT la liste
ET IL Y A UNE ERREU 9A NE DOIS PAS ETRE DANS INSTRUCTIONS !!!
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array(
						),
						'allow_custom' => 0,
						'save_custom' => 0,
						'default_value' => array(
						),
						'layout' => 'vertical',
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
	
	public function wpcmail_scripts_enqueue(){

	}
	
	/**
	* format text for email
	*/
   private function wpcmail_format_email_text($text){
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
	
	/**
	* 
	* @param string $key
	* @param array $data
	*/
   public function wpcmail_mail_sender($key,$data = array()){
	   

//TODO  changer la façon dont data ammene els infos :
	    
	   
$id_compterendu = false;
$array_replace = array();
$to_direct=false;
	   
		//used to find the post by an acf field
		$post_acf_data = get_email_type_by_field($key);

		$to = $this->wpcmail_get_destinataires_by_postid($post_acf_data->ID);		
		if($to == ""){
			$to = $to_direct;
		}else{
			$to = $to.','.$to_direct;
		}

		$subject = $post_acf_data->post_title;
		$text = $post_acf_data->post_content;
		$text = apply_filters('the_content', $text);		
		if(count($array_replace)>0){
			$text = vsprintf($text,$array_replace);			
		}		
		$mail_text = $this->wpcmail_format_email_text($text);

		$headers = array();
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		// send email
		return wp_mail($to, $subject,$mail_text,$headers);
   }
   
	/**
	 * get all email coniguren in the email type
	 */
	function wpcmail_get_destinataires_by_postid($post_ID_emailtype,$data = array()){
		
//TODO REVOIR COMPLETEMENT CETTE PARTIE
//Dois prevoir de prendre le groups/la ligne direct ou eventuellement un de plus de 'lappel meme de la fonction
		
		
//		$email_destinataire_interne = get_field('email_destinataire_interne',$post_ID_emailtype);
//		$email_destinataire_interne_plus = get_field('email_to_add',$post_ID_emailtype);
//
//		$to_list = array();
//		if(isset($email_destinataire_interne) && $email_destinataire_interne && $email_destinataire_interne!=''){
//
//			$client = false;
//			if($post_id_compterendu){
//				$client_recherche =  get_field('cr_client_recherche',$post_id_compterendu);
//				$client = get_field('recherche_info_client',$client_recherche->ID);
//			}
//
//			//get all users
//			foreach($email_destinataire_interne as $key_edi=>$edi){
//
//				if($edi == 'chargeclientele'){
//
//					if($client){
//						//get the collab linked to this post
//						$gestionnaire = get_field('user_gestionnaire','user_'.$client['ID']);
//						$to_list[] = $gestionnaire['user_email'];
//					}
//
//				}elseif($edi == 'client'){
//
//					if($client){
//						$to_list[] = $client['user_email'];
//					}
//
//				}else{
//					$args_users = array(
//						'role' => $edi, //role__in ne marchais pas
//						'fields'       => 'all',
//					 ); 
//					$all_users = get_users( $args_users );
//					foreach($all_users as $user_to){
//						$to_list[] = $user_to->user_email;
//					}
//				}
//			}
//		}
//		if(isset($email_destinataire_interne_plus) && $email_destinataire_interne_plus && $email_destinataire_interne_plus!=''){
//			$to_list[] = $email_destinataire_interne_plus;
//		}
//		$list_text = implode(',', $to_list);
//
//		return $list_text;
	}   
}