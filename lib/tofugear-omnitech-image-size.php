<?php

function create_tofugear_settings(){
  // Add to admin_menu function
	add_menu_page(
	  'Theme page title',              // page_title
    'Omnitech',             		 		 // menu_title
    'manage_options',                // capability
    'tofugear-options',              // menu_slug
    'tofugear_settings_page'         // callback
	);

	//call register settings function
	add_action('admin_init', 'register_tofugear_settings');
}

function register_tofugear_settings(){
	register_setting(
		'tofugear_settings',
		'cdn_url'
	);
	register_setting(
		'tofugear_settings',
		'mobile_thumbnail_w'
	);
	register_setting(
		'tofugear_settings',
		'mobile_thumbnail_h'
	);
	register_setting(
		'tofugear_settings',
		'mobile_large_w'
	);
	register_setting(
		'tofugear_settings',
		'mobile_large_h'
	);
}

function tofugear_settings_page(){

$title = "Mobile Image Size Settings";

	?>

<div class="wrap">
	<h2><?php echo $title;?></h2>

	<form method="post" action="options.php">
			<?php settings_fields( 'tofugear_settings' ); ?>
	    <?php do_settings_sections( 'tofugear_settings' ); ?>
	    <table class="form-table">
	    		<tr valign="top">
	        <th scope="row">CDN URL:</th>
	        <td>
	        	<input class = 'all-options' type="url" name="cdn_url" value="<?php echo esc_attr( get_option('cdn_url') ); ?>" />
	        </td>
	        </tr>
	        <tr valign="top">
	        <th scope="row">Mobile Thumbnail:</th>
	        <td>
	        	<label>Max Width:</label>
	        	<input class = 'small-text' type="number" step='1' min='0' name="mobile_thumbnail_w" value="<?php echo esc_attr( get_option('mobile_thumbnail_w', 340) ); ?>" />
	        	<label>Max Height:</label>
	        	<input class = 'small-text' type="number" step='1' min='0' name="mobile_thumbnail_h" value="<?php echo esc_attr( get_option('mobile_thumbnail_h', 340) ); ?>" />
	        </td>
	        </tr>
	        <tr valign="top">
	        <th scope="row">Mobile Large:</th>
	        <td>
	        	<label>Max Width:</label>
	        	<input class = 'small-text' type="number" step='1' min='0' name="mobile_large_w" value="<?php echo esc_attr( get_option('mobile_large_w', 1024) ); ?>" />
	        	<label>Max Height:</label>
	        	<input class = 'small-text' type="number" step='1' min='0' name="mobile_large_h" value="<?php echo esc_attr( get_option('mobile_large_h', 1024) ); ?>" />
	        </td>
	        </tr>
	    </table>

	    <?php submit_button(); ?>
	</form>
</div>

	<?php
}


function tgom_add_image_sizes() {
  if ( function_exists( 'add_image_size' ) ) {
      add_image_size( 'mobile_thumbnail', get_option('mobile_thumbnail_w', 480), get_option('mobile_thumbnail_h', 480));
      add_image_size( 'mobile_large', get_option('mobile_large_w', 1080), get_option('mobile_large_h', 1080) );
  }
}
