<?php 
/* 
Plugin Name: BuddyPress XProfile File Field
Plugin URI: https://alextheafrican.wordpress.com/2012/03/10/how-to-add-an-file-field-to-buddypress-extended-profile-fields/
Description: BuddyPress XProfile addon that adds an File field type
Version: 3.0.0
Author: Alex Githatu & Matt Jones
Author URI: https://alextheafrican.wordpress.com/about/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Copyright: 2024  Alex Githatu  ( email : githatu@syngence.tech )
*/


if( ! class_exists( 'BP_XProfile_File_Field' ) ) {
    class BP_XProfile_File_Field {
        
    const VERSION = '3.0.0';
	
	const FIELD_TYPE_NAME = 'file';
	
	private static $instance;
	
	public $file;
	
	public $plugin_path;
	
	public $plugin_url;
	
	public $plugin_name;
        
        
        public static function instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BP_XProfile_File_Field ) ) {
                self::$instance = new BP_XProfile_File_Field(__FILE__);
                
                if(version_compare( BP_VERSION, '2.0.2', '>' )) {
                    require dirname( __FILE__ ) . '/classes/class-bp-xprofile-field-type-file.php';
                }
                
            }
            return self::$instance;
	}
        
        private function __construct( $file ) {
		
		$this->file = $file;
		$this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		$this->plugin_path = trailingslashit( dirname( $file ) );
                $this->plugin_name = basename( dirname( __FILE__ ) );
		
		                
		
                if(version_compare( BP_VERSION, '2.0.2', '<=' )) {
                    // Add new file field type
                    add_filter( 'xprofile_field_types', array( $this, 'bpxp_file_field_add_field_type') );
                    
                    // Render the file field on the admin panel
                    add_filter( 'xprofile_admin_field', array( $this, 'bpxp_file_field_admin_render_field_type') );
                    
                    // Render the file field on the edit front-end
                    add_action( 'bp_custom_profile_edit_fields', array( $this, 'bpxp_file_field_edit_render_field') );
                }
                else {
                    // Add new file field type
                    add_filter( 'bp_xprofile_get_field_types', array( $this, 'bpxp_file_field_add_field_type') );
                }
                
                
                if(version_compare( BP_VERSION, '2.1.0', '<' )) {
                    // Display the file on the front-end
                    add_filter( 'bp_get_the_profile_field_value', array( $this, 'bpxp_file_field_frontend_render'), 10, 3 );
                }
                      
                                
                // take over the rendering of the frontend profile edit screen in order to handle the file field
                add_action( 'bp_actions', array( $this, 'bpxp_file_field_override_xprofile_screen_edit_profile'), 10 );

                // make sure required file fields pass validation during signup
                add_action( 'bp_signup_pre_validate', array( $this, 'bpxp_file_field_pre_validation'), 10 );
                add_action( 'bp_signup_validate', array( $this, 'bpxp_file_field_post_validation'), 10 );

                
                // save file fields during user signup process
                add_action( 'bp_core_signup_user', array( $this, 'bpxp_file_field_save_on_signup'), 10, 5 );

                // update file fields after user activation
                add_action( 'bp_core_activated_user', array( $this, 'bpxp_file_field_update_on_user_activation'), 10, 3 );
                
                $script_hook = 'wp_enqueue_scripts';
                if(is_admin()) {
                    
                    if(version_compare( BP_VERSION, '2.0.2', '>' )) {
                        // take over the rendering of the admin profile edit screen in order to handle the file field
                        add_action( 'bp_members_admin_update_user', array( $this, 'bpxp_file_field_save_on_admin_edit_profile'), 8, 4 );
                    }
                    
                    $script_hook = 'admin_enqueue_scripts';
                }
                
                // load javascript
                add_action( $script_hook, array( $this, 'bpxp_file_field_load_js') );
                
                // load css
                add_action( $script_hook, array( $this, 'bpxp_file_field_load_css') );
		
                // Language support
                add_action( 'init', array( $this, 'bpxp_file_field_l10n') );
		
	}
        
        
        function bpxp_file_field_load_css() {
            wp_enqueue_style( 'bpxp_file_field-css', $this->plugin_url . 'css/bp-xp-file-fld.css', array( ), '1.0.0' );
        }
        
        
        function bpxp_file_field_load_js() {
            wp_enqueue_script( 'version_compare-js', $this->plugin_url . 'js/version_compare.js', array( ), '0.0.4' );
            wp_enqueue_script( 'bpxp_file_field-js', $this->plugin_url . 'js/bp-xp-file-fld.js', array( 'jquery', 'version_compare-js' ), '1.4.0' );
            $bp_version = BP_VERSION;
            $bpxp_l10n = array('fileOptionLabel' => __( 'file', 'bp-xprofile-file-field' ),
                                'customFieldsLabel' => __( 'Custom Fields', 'bp-xprofile-file-field' ),
                                'bpVersion' => "{$bp_version}");
            wp_localize_script( 'bpxp_file_field-js', 'bpxpL10n', $bpxp_l10n );
        }
        
        function bpxp_file_field_l10n(){
            load_plugin_textdomain('bp-xprofile-file-field', false, $this->plugin_name . '/lang');
        }
        
        function bpxp_file_field_add_field_type($field_types){
            if(version_compare( BP_VERSION, '2.0.2', '<=' )) {
                $file_field_type = array(BP_XProfile_File_Field::FIELD_TYPE_NAME);
            }
            else {
                $file_field_type = array(BP_XProfile_File_Field::FIELD_TYPE_NAME => 'BP_XProfile_Field_Type_File');
            }
            
            $field_types = array_merge($field_types, $file_field_type);
            return $field_types;
        }

        
        function bpxp_file_field_admin_render_field_type($field, $echo = true){

            do_action('bpxp_file_field_before_admin_render');
            
            ob_start();
                switch ( $field->type ) {
                    case BP_XProfile_File_Field::FIELD_TYPE_NAME:
                        ?>
                            <input type="file" accept=".pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" value="" />
                        <?php
                        break;    
                    default :
                        ?>
                            <p><?php _e('Field type unrecognized', 'bp-xprofile-file-field'); ?></p>
                        <?php
                }

                $output = ob_get_contents();
            ob_end_clean();
            
            do_action('bpxp_file_field_after_admin_render', $output);

            if($echo){
                echo $output;
                return;
            }
            else{
                return $output;
            }

        }

        
        function bpxp_file_field_frontend_render($field_value, $field_type, $field_id){

            if ( $field_type == BP_XProfile_File_Field::FIELD_TYPE_NAME ){
                global $field;
                
                $raw_field_value = bp_unserialize_profile_field( $field->data->value );
                $bpxp_field_value = WP_CONTENT_URL . $raw_field_value;
                $bpxp_field_value = "<a href=\"{$bpxp_field_value}\" >" . __('file', 'bp-xprofile-file-field') . "</a>";
                
                $field_value = apply_filters('bpxp_file_field_frontend_field_value', $bpxp_field_value, $field_value, $raw_field_value, $field_type, $field_id);
            }
            
            return $field_value;

        }
        
        

        function bpxp_file_field_edit_render_field($echo = true){

            if ( bp_get_the_profile_field_type() == BP_XProfile_File_Field::FIELD_TYPE_NAME ){
                
                if(empty ($echo)){
                    $echo = true;
                }
                
                do_action('bpxp_file_field_before_edit_render');

                ob_start();
                    $field_id = bp_get_the_profile_field_id();
                    $file_field_input_name = bp_get_the_profile_field_input_name();
                    $field_name_hidden = 'field_' . $field_id . '_hidden';
                    $field_name_delete = 'field_' . $field_id . '_delete';
                    $file_id = 'bpxp_file_' . $field_id;
                    $file = bp_get_the_profile_field_edit_value();
                    $file_link = WP_CONTENT_URL . $file;

                ?>
                        <label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'bp-xprofile-file-field' ); ?><?php endif; ?></label>
                        <input type="file" accept="file/*" name="<?php echo $file_field_input_name; ?>" id="<?php echo $file_field_input_name; ?>" value="" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>/>
                        <input type="hidden" name="<?php echo $field_name_hidden; ?>" id="<?php echo $field_name_hidden; ?>" value="<?php echo bp_get_the_profile_field_edit_value(); ?>" />
                <?php
                    if(!empty($file)) {
                ?>    
                        <input type="hidden" name="<?php echo $field_name_delete; ?>" id="<?php echo $field_name_delete; ?>" value="" />
                        <a href="<?php echo $file_link; ?>" id="<?php echo $file_id; ?>"><?php bp_the_profile_field_name(); ?></a>
                        <a href="#" data-delete_id="<?php echo $field_name_delete; ?>" data-file_id="<?php echo $file_id; ?>" class="rtd-button delete-icon"><?php _e('Delete', 'bp-xprofile-file-field'); ?></a>

                <?php
                    }
                    $output = ob_get_contents();
                ob_end_clean();

                do_action('bpxp_file_field_after_edit_render', $output);
                
                
                if($echo){
                    echo $output;
                    return;
                }
                else{
                    return $output;
                }
                
            }

        }

        //save profile files during edit in admin
        function bpxp_file_field_save_on_admin_edit_profile($doaction = '', $user_id = 0, $request = array(), $redirect_to = ''){

            if ( isset( $_POST['field_ids'] ) ) {
                $action = 'edit-bp-profile_' . $user_id;
                if(wp_verify_nonce( $_POST['_wpnonce'], $action )){
                    global $bp;
                    
                    $bp->bpxp_file_field = new stdClass(); 
                    $bp->bpxp_file_field->user_id = $user_id;
                    
                    $posted_field_ids = explode(',', join( ',', $_POST['field_ids'] ));
                    $this->bpxp_file_field_save($posted_field_ids);

                    do_action( 'bpxp_file_field_save_on_admin_edit_profile', $doaction, $user_id, $request, $redirect_to );
                }
            }

        }
        

        // Override default action hook in order to support files
        function bpxp_file_field_override_xprofile_screen_edit_profile(){
            $screen_edit_profile_priority = has_filter('bp_screens', 'xprofile_screen_edit_profile');

            if($screen_edit_profile_priority !== false){
                
                if ( isset( $_POST['field_ids'] ) ) { //only override during post
                    //Remove the default profile_edit handler
                    remove_action( 'bp_screens', 'xprofile_screen_edit_profile', $screen_edit_profile_priority );

                    //Install replalcement hook
                    add_action( 'bp_screens', array( $this, 'bpxp_file_field_save_on_edit'), $screen_edit_profile_priority );
                }
            }
            
        }

        //save profile files during edit
        function bpxp_file_field_save_on_edit($trigger_xprofile_edit = true){

            if ( isset( $_POST['field_ids'] ) ) {
                if($trigger_xprofile_edit === "") {
                    $trigger_xprofile_edit = true;
                }
                
                if(wp_verify_nonce( $_POST['_wpnonce'], 'bp_xprofile_edit' )){
                    global $bp;
                    
                    $bp->bpxp_file_field = new stdClass(); 
                    $bp->bpxp_file_field->user_id = $bp->displayed_user->id;
                    
                    $posted_field_ids = explode( ',', $_POST['field_ids'] );
                    $this->bpxp_file_field_save($posted_field_ids);

                }
            }
            
            do_action( 'bpxp_file_field_save_on_edit', $bp->displayed_user->id );

            if($trigger_xprofile_edit){
                if(function_exists('xprofile_screen_edit_profile')){
                    xprofile_screen_edit_profile();
                }
            }

        }

        function bpxp_file_field_get_post_filler() {
            return "yaddah_blah";
        }

        
        // reduce rampant code duplication in processing fields
        function bpxp_file_field_process_file_fields($operation) {
            
            $posted_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

            foreach ( (array)$posted_field_ids as $field_id ) {
                $field_name = 'field_' . $field_id;

                if ( isset( $_FILES[$field_name] ) ) {
                    
                    $operation($field_name, $field_id);
                    
                }
            }
        }

        // prevent false negative validation
        function bpxp_file_field_pre_validation() {
            
            $place_holder = $this->bpxp_file_field_get_post_filler();
            
            $this->bpxp_file_field_process_file_fields( 
                function ($field_name, $field_id) use ($place_holder) { 
                
                    if ( empty( $_POST[$field_name] ) ) {
                        $field  = new BP_XProfile_Field( $field_id );

                        if($field->type === $this::FIELD_TYPE_NAME) {
                            $_POST[$field_name] = $place_holder;
                        }
                        
                    }

                } 
            );

        }

        function bpxp_file_field_post_validation() {
            
            $place_holder = $this->bpxp_file_field_get_post_filler();

            $this->bpxp_file_field_process_file_fields( 
                function ($field_name, $field_id) use ($place_holder) { 
                
                    if ( $_POST[$field_name] === $place_holder ) {
                        unset( $_POST[$field_name] );
                    }

                } 
            );

        }

        // save file fields during user sign-up
        // NOTE: 1. bp_core_signup_blog() registration branch is not supported since it does not return a user_id.
        //       2. The file upload functionality assumes that BP allows the creation of unactivated user accounts
        // UPDATE 2024-08-01: 
        //       1. bp_core_signup_blog() registration branch now supported by making use of the $signup->id.
        //       2. As of BP 14.0.0, users are not created on registration so $signup->id has to be used in place 
        //          of $user_id
        function bpxp_file_field_save_on_signup($user_id, $user_login, $user_password, $user_email, $usermeta){
            global $bp;
            global $wpdb;
             
            if(empty($user_id)) {
                $new_signup = bp_members_get_signup_by( 'user_email', $user_email );

                if(empty($new_signup)) {
                    $error_message = sprintf( __( 'The user "%1$s" was not properly initialized.', 'bp-xprofile-file-field' ), $user_login );
                    bp_core_add_message( $error_message, 'error' );
                    return;
                }
                
                $user_id = $new_signup->id;
            }

            $update_signup_meta = false;
            $bp->bpxp_file_field = new stdClass(); 
            $bp->bpxp_file_field->user_id = $user_id;
            $posted_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );
            
            $this->bpxp_file_field_save($posted_field_ids);
            
            
            foreach ( (array)$posted_field_ids as $field_id ) {
                $field_name = 'field_' . $field_id;

                if ( isset( $_FILES[$field_name] ) ) {
                    if ( empty( $_POST[$field_name] ) ) {
                        $file_name = $_FILES[$field_name]['name'];
                        $error_message = sprintf( __( 'The file %1$s was not uploaded.', 'bp-xprofile-file-field' ), $file_name );
                        bp_core_add_message( $error_message, 'error' );
                    }
                    else {
                        $file_path = $_POST[$field_name];
                        xprofile_set_field_data( $field_id, $user_id, $file_path );
                        
                        $file_url = WP_CONTENT_URL . $file_path;
                        $usermeta[$field_name] = $file_url;
                        
                        // Left in place for when BuddyPress allows rendering html
                        //$file_html = "<img src=\"{$file_url}\" alt=\"" . __('file', 'bp-xprofile-file-field') . "\" />";
                        //$usermeta[$field_name] = $file_html;

                        $update_signup_meta = true;
                    }
                }
            }
            
            if ($update_signup_meta) {
                $query = $wpdb->prepare( "UPDATE {$wpdb->prefix}signups SET meta = %s WHERE user_login = %s", maybe_serialize($usermeta), $user_login );
                
                if ( ! $wpdb->query( $query ) ) {
                    return new WP_Error( 'signup_error', __( 'The file name could not be saved to the sign-up data', 'bp-xprofile-file-field' ) );
                }
            }

            do_action( 'bpxp_file_field_save_on_signup', $user_id, $user_login, $user_password, $user_email, $usermeta );
        }

        // Update file fields after user activation
        // 2024-08-01: 
        //       1. As of BP 14.0.0, users are not created on registration so $signup->id is used in place 
        //          of $user_id. This has to be updated to the correct $user_id after the user is activated
        function bpxp_file_field_update_on_user_activation($user_id, $activation_key, $user_data){ //$activation_key is bogus; do not use
            
            $user = get_user_by('id', $user_id);
            $signups = BP_Signup::get(
                            array(
                                'user_email' => $user->data->user_email,
                                'active' => 1, // since the user has now been activated
                            )
                        );
            if(empty($signups['signups'])) {
                // abandon, as there's no signup_id to compare with
                return;
            }
            
            $signup = $signups['signups'][0];
            $signup_id = $signup->id;

            if($signup_id == $user_id) {
                // nothing to do
                return;
            }

            $usermeta = array();
            if ( isset( $user_data['meta'] ) ) {
                $usermeta = $user_data['meta'];
            }

            if ( isset( $usermeta['profile_field_ids'] ) ) {
                $xprofile_fields = wp_parse_id_list( $usermeta['profile_field_ids'] );
        
                $wp_upload_folder_info = wp_upload_dir();
                $profile_upload_folder_info = $this->bpxp_file_field_profile_upload_dir($wp_upload_folder_info);
                $profiles_folder = str_replace(site_url(), '', $profile_upload_folder_info['url']) . '/';

                foreach ( $xprofile_fields as $xprofile_field_id ) {
                    $field_type = bp_xprofile_get_field_type( $xprofile_field_id );
                                        
                    if(strtolower($field_type->name) !== $this::FIELD_TYPE_NAME) {
                        continue;
                    }
        
                    $field_name = 'field_' . $xprofile_field_id;
                    $old_file_location = $usermeta[$field_name];

                    if(strpos($old_file_location, WP_CONTENT_URL) !== false) {
                        $old_file_location = str_replace(site_url(), '', $old_file_location);
                    }

                    $old_file_location = $_SERVER['DOCUMENT_ROOT'] . $old_file_location;

                    $new_file_location = str_replace("$profiles_folder$signup_id/", "$profiles_folder$user_id/", $old_file_location);

                    $file_name = basename($new_file_location);
                    $new_file_folder = dirname($new_file_location);
                    $new_file_name = wp_unique_filename( $new_file_folder, $file_name );
                    $new_file_location = "$new_file_folder/$new_file_name";

                    if (!file_exists( $new_file_folder )) {
                        mkdir( $new_file_folder, 0777, true );
                    }

                    $file_copied = @copy( $old_file_location, $new_file_location );

                    if( $file_copied === false ) {
                        $error_message = sprintf( __( 'The file %1$s could not be relocated.', 'bp-xprofile-file-field' ), $file_name );
                        bp_core_add_message( $error_message, 'error' );
                        
                        continue;
                    }
                    
                    $new_file_url = str_replace($_SERVER['DOCUMENT_ROOT'], site_url(), $new_file_location);
                    $field_updated = xprofile_set_field_data( $xprofile_field_id, $user_id, $new_file_url );

                    if( $field_updated === false ) {
                        $error_message = sprintf( __( 'The field "%1$s" could not be updated for the user %2$s.', 'bp-xprofile-file-field' ), $file_name, $user->data->
                        display_name );
                        bp_core_add_message( $error_message, 'error' );
                        
                    }

                    unlink( $old_file_location );
                }

            }
        }
        
        
        // save profile files
        protected function bpxp_file_field_save($posted_field_ids) {
            
            $post_action_found = false;
            $post_action = '';
            if (isset($_POST['action'])){
                $post_action_found = true;

                $post_action = apply_filters('bpxp_file_field_preserve_post_action', $_POST['action']);

            }

            foreach ( (array)$posted_field_ids as $field_id ) {
                $field_name = 'field_' . $field_id;

                if ( isset( $_FILES[$field_name] ) ) {
                    if($_FILES[$field_name]['size'] > 0){
                        require_once( ABSPATH . '/wp-admin/includes/file.php' );

                        $uploaded_file = $_FILES[$field_name]['tmp_name'];

                        // Filter the upload location
                        add_filter( 'upload_dir', array( $this, 'bpxp_file_field_profile_upload_dir'), 10, 1 );

                        //ensure WP accepts the upload job
                        $_POST['action'] = 'wp_handle_upload';

                        $wp_uploaded_file = wp_handle_upload( $_FILES[$field_name] );

                        $db_uploaded_file = str_replace(WP_CONTENT_URL, '', $wp_uploaded_file['url']) ;

                        $uploaded_file = apply_filters('bpxp_file_field_file_uploaded', $db_uploaded_file, $wp_uploaded_file);

                        $_POST[$field_name] = $uploaded_file;
                    }
                    else{
                        $field_name_hidden = 'field_' . $field_id . '_hidden';
                        if ( isset( $_POST[$field_name_hidden] ) ) {
                            $field_name_delete = 'field_' . $field_id . '_delete';
                            if ( isset( $_POST[$field_name_delete] ) && $_POST[$field_name_delete] == 'deleted') {
                                $file_file_path = WP_CONTENT_DIR . $_POST[$field_name_hidden];
                                unlink($file_file_path);
                                $_POST[$field_name] = '';
                            }
                            else {
                                $_POST[$field_name] = $_POST[$field_name_hidden];
                            }
                        }
                    }

                }

            }

            if($post_action_found){
                $_POST['action'] = apply_filters('bpxp_file_field_restore_post_action', $post_action);
            }
            else{
                unset($_POST['action']);
            }
        }
        

        function bpxp_file_field_profile_upload_dir( $upload_dir ) {
            global $bp;

            $original_upload_dir = $upload_dir;
            $user_id = false;
            $profile_subdir = '/profiles';
            if( isset($bp->bpxp_file_field) && isset($bp->bpxp_file_field->user_id) ) {
                $user_id = $bp->bpxp_file_field->user_id;
                $profile_subdir .= '/' . $user_id;
            }

            $upload_dir['path'] = $upload_dir['basedir'] . $profile_subdir;
            $upload_dir['url'] = $upload_dir['baseurl'] . $profile_subdir;
            $upload_dir['subdir'] = $profile_subdir;

            $upload_dir = apply_filters('bpxp_file_field_upload_dir', $upload_dir, $original_upload_dir, $user_id);
            
            return $upload_dir;
        }
        
    } // end BP_XProfile_File_Field Class
}


function bpxp_file_field_error_wordpress_version() {
    global $wp_version;
    
    echo '<div class="error"><p>' . sprintf( __( 'Please upgrade WordPress to version 3.2.1 or later. This plugin may not work properly on version %1$s.', 'bp-xprofile-file-field' ), $wp_version ) . '</p></div>';
}

function bpxp_file_field_error_missing_xprofile() {
    
    echo '<div class="error"><p>' . sprintf( __( 'Please ensure you are running BuddyPress 1.5 or later and %1$s BuddyPress Extended Profiles Component %2$s is activated in order for this plugin to work.', 'bp-xprofile-file-field' ), '<a href="' . admin_url( 'options-general.php?page=bp-components' ) . '">', '</a>' ) . '</p></div>';
}


   /**
    * Creates the single BP_XProfile_File_Field instance.
    *
    * 
    * 
    * @return BP_XProfile_File_Field 
    * 
    */
function bpxp_file_field() {
    
    return BP_XProfile_File_Field::instance();
}

function bpxp_file_field_init() {
    global $wp_version;

    if ( !version_compare( $wp_version, '3.2.1', '>=' ) ) {
        add_action( 'all_admin_notices', 'bpxp_file_field_error_wordpress_version' );
    } 
    elseif ( class_exists( 'BP_XProfile_Component') && version_compare( BP_VERSION, '1.5', '>=' ) ) {
        
        do_action('bpxp_file_field_before_load');
            bpxp_file_field(); 
        do_action('bpxp_file_field_after_load', bpxp_file_field());
        
        define( 'BPXP_FILE_FIELD_IS_LOADED', 1 );
        
    } 
    else {
        add_action( 'all_admin_notices', 'bpxp_file_field_error_missing_xprofile' );
    }
}

add_action( 'bp_xprofile_includes', 'bpxp_file_field_init' ); // Ensures it's only loaded if XProfile is active