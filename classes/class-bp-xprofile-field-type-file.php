<?php
/**
 * BuddyPress XProfile File Field Classes
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * File xprofile field type.
 *
 * @since BuddyPress XProfile File Field (2.0.0)
 */
class BP_XProfile_Field_Type_File extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the file field type
	 *
	 * @since BuddyPress XProfile File Field (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Custom Fields', 'bp-xprofile-file-field' );
		$this->name     = __( 'File', 'bp-xprofile-file-field' );

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_File class.
		 *
		 * @since BuddyPress XProfile File Field (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_File $this Current instance of
		 *                                             the field type file.
		 */
		do_action( 'bp_xprofile_field_type_file', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes} that you want to add.
	 * @since BuddyPress XProfile File Field (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

                do_action('bpxp_file_field_before_edit_render');
                
		// user_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}
                
                $field_id = bp_get_the_profile_field_id();
                $file_field_input_name = bp_get_the_profile_field_input_name();
                $field_name_hidden = 'field_' . $field_id . '_hidden';
                $field_name_delete = 'field_' . $field_id . '_delete';
                $file_id = 'bpxp_file_' . $field_id;
                $file = bp_get_the_profile_field_edit_value();

                $file_link = $file;
                if(strpos($file, WP_CONTENT_URL) === false) {
                    $file_link = WP_CONTENT_URL . $file;
                }
                
                
                $file_field_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'file',
                                                                            'name' => $file_field_input_name,
                                                                            'id' => $file_field_input_name,
                                                                            'value' => $file,
                                                                            ) 
                                                  );
                
                $file_field_hidden_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'hidden',
                                                                            'name' => $field_name_hidden,
                                                                            'id' => $field_name_hidden,
                                                                            'value' => $file,
                                                                            ) 
                                                  );
                
                $file_field_delete_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'hidden',
                                                                            'name' => $field_name_delete,
                                                                            'id' => $field_name_delete,
                                                                            'value' => '',
                                                                            ) 
                                                  );
                
                ob_start();
                    
                    if ( version_compare( BP_VERSION, '7.2.0', '>=' ) ) {
                        ?>
                            <legend id="<?php bp_the_profile_field_input_name(); ?>-1">
                                <?php bp_the_profile_field_name(); ?> 
                                <?php bp_the_profile_field_required_label(); ?>
                            </legend>
                        
                        <?php
                    }
                    else {
                        ?>
                            <label for="<?php bp_the_profile_field_input_name(); ?>">
                                <?php bp_the_profile_field_name(); ?> 
                                <?php if ( bp_get_the_profile_field_is_required() ) : ?>
                                    <?php _e( '(required)', 'bp-xprofile-file-field' ); ?>
                                <?php endif; ?>
                            </label>
                        
                        <?php
                    }
                ?>
                <?php
                    /** This action is documented in bp-xprofile/bp-xprofile-classes */
                    do_action( bp_get_the_profile_field_errors_action() ); 

                    if ( version_compare( BP_VERSION, '7.2.0', '>=' ) ) {
                        ?>
                            <input <?php echo $this->get_edit_field_html_elements( $file_field_input ); ?>  aria-labelledby="<?php bp_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bp_the_profile_field_input_name(); ?>-3">
                        
                            <?php if ( bp_get_the_profile_field_description() ) : ?>
                                <p class="description" id="<?php bp_the_profile_field_input_name(); ?>-3"><?php bp_the_profile_field_description(); ?></p>
                            <?php endif; ?>
                        <?php
                    }
                    else {
                        ?>
                            <input <?php echo $this->get_edit_field_html_elements( $file_field_input ); ?>>
                        
                        <?php
                    }
                        
                ?>                        
                        
                        <input <?php echo $this->get_edit_field_html_elements( $file_field_hidden_input ); ?>>
                
                <?php
                    if(!empty($file)) {
                ?>    
                        <input <?php echo $this->get_edit_field_html_elements( $file_field_delete_input ); ?>>
                        <a href="<?php echo $file_link; ?>" id="<?php echo $file_id; ?>"><?php bp_the_profile_field_name(); ?></a>
                        <a href="#" data-delete_id="<?php echo $field_name_delete; ?>" data-file_id="<?php echo $file_id; ?>" class="rtd-button delete-icon"><?php _e('Delete', 'bp-xprofile-file-field'); ?></a>

                <?php
                    }
                    $output = ob_get_contents();
                ob_end_clean();

                echo $output;
                
		do_action('bpxp_file_field_after_edit_render', $output);
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress XProfile File Field (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {

                do_action('bpxp_file_field_before_admin_render');
                
		$r = bp_parse_args( $raw_properties, array( 'type' => 'file',
                                                            'disabled' => 'disabled'
                                                           ) 
                                   ); 
                
                ob_start();
                    ?>

                        <input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

                    <?php

                    $output = ob_get_contents();
                ob_end_clean();
                
                do_action('bpxp_file_field_after_admin_render', $output);
                
                echo $output;
	}
        
        

	/**
	 * Format File for display.
	 *
	 * @since BuddyPress XProfile File Field (2.0.0)
	 * @since BuddyPress XProfile File Field (2.0.2) Added `$field_id` parameter.
	 *
	 * @param string $field_value The URL value, as saved in the database.
	 * @return string URL converted to a link.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
            
            $field_type = BP_XProfile_File_Field::FIELD_TYPE_NAME;
            $raw_field_value = bp_unserialize_profile_field( $field_value );

            $bpxp_field_value = $raw_field_value;
            if(strpos($raw_field_value, WP_CONTENT_URL) === false) {
                $bpxp_field_value = WP_CONTENT_URL . $raw_field_value;
            }
            
            $bpxp_field_value = "<a href=\"{$bpxp_field_value}\">" . __('file', 'bp-xprofile-file-field') . "</a>";

            $filtered_field_value = apply_filters('bpxp_file_field_frontend_field_value', $bpxp_field_value, $field_value, $raw_field_value, $field_type, $field_id);
        

            return $filtered_field_value;
	}
        

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress XProfile File Field (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}
