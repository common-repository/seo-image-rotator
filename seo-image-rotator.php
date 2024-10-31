<?php
/*
Plugin Name: SEO Image Rotator
Plugin URI: http://www.e-koncept.co.nz/seo-image-rotator/
Description: Based on WP-Cycle +Captions with modifications by E-Koncept to add SEO friendly title attribute, Add multiple rotators, Enable/disable captions
Version: 1.0
Author: Robert
Author URI: http://www.e-koncept.co.nz/ 
License: A "Slug" license name e.g. GPL2
*/

//	define our defaults (filterable)
$seo_image_rotator_defaults = apply_filters('seo_image_rotator_defaults', array(
	'caption'=> 1,
	'rotate' => 1,
	'effect' => 'fade',
	'delay' => 3,
	'duration' => 2,
	'img_width' => 300,
	'img_height' => 200,
	'random' => 0,
	'rotator_name' => 'rotator',
	'rotator_id'=>0
));

//	pull the settings from the db
$seo_image_rotators = get_option('seo_image_rotators');
$seo_image_settings = get_option('seo_image_settings');
$seo_image_rotator_settings = get_option('seo_image_rotator_settings');
$seo_image_rotator_images = get_option('seo_image_rotator_images');

//	fallback
$seo_image_rotator_settings = wp_parse_args($seo_image_rotator_settings, $seo_image_rotator_defaults);

/*
///////////////////////////////////////////////
This section hooks the proper functions
to the proper actions in WordPress
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/

//	this function registers our settings in the db
add_action('admin_init', 'seo_image_rotator_register_settings');
function seo_image_rotator_register_settings() {
	register_setting('seo_image_settings', 'seo_image_settings', 'seo_image_settings_validate');
	register_setting('seo_image_rotators', 'seo_image_rotators', 'seo_image_rotators_validate');
	register_setting('seo_image_rotator_images', 'seo_image_rotator_images', 'seo_image_rotator_images_validate');
}
//	this function adds the settings page to the Appearance tab
add_action('admin_menu', 'add_seo_image_rotator_menu');
function add_seo_image_rotator_menu() {
	// Create new top-level menu
	add_menu_page('SEO Image Rotator', 'Image Rotator', 'manage_options', 'seo_image_rotator', 'seo_image_rotator_list_page', plugin_dir_url( __FILE__ ).'img/icon_16x16.png');

	// Add sub-menus
	add_submenu_page('seo_image_rotator', 'SEO Image Rotator', 'All Rotators', 'manage_options', 'seo_image_rotator', 'seo_image_rotator_list_page');
	//add_submenu_page('seo_image_rotator', 'Add New Rotator', 'Add New', 'manage_options', 'seo_image_rotator_add_new', 'seo_image_rotator_add_page');
}

/********************************************************/
/*                    Settings page                     */
/********************************************************/

//	add "Settings" link to plugin page
/*add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'seo_image_rotator_plugin_action_links');
function seo_image_rotator_plugin_action_links($links) {
	$seo_image_rotator_settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'upload.php?page=seo-image-rotator' ), __('Settings') );
	array_unshift($links, $seo_image_rotator_settings_link);
	return $links;
} */

/*
///////////////////////////////////////////////
this function is the code that gets loaded when the
settings page gets loaded by the browser.  It calls 
functions that handle image uploads and image settings
changes, as well as producing the visible page output.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function seo_image_rotator_list_page() {
	global $rotator_id, $rotator_name, $seo_image_settings, $seo_image_rotator_images, $new_setting;
	$rotator_id = $_REQUEST['rotator_id'];
	$rotator_name = $_REQUEST['rotator_name'];
	
	if( $_REQUEST['action'] == 'rotator_edit' || $_REQUEST['action'] == 'wp_handle_upload' || $_REQUEST['action'] == 'image_delete' ) {
		echo '<div class="wrap">';
		
			//	handle image upload, if necessary
			if(isset( $_REQUEST["action"] ) && $_REQUEST['action'] == 'wp_handle_upload')
				seo_image_rotator_handle_upload();
			
			//	delete an image, if necessary
			if(isset($_REQUEST['delete']))
				seo_image_rotator_delete_upload($_REQUEST['delete']);
			
			//	the image management form
			seo_image_rotator_images_admin();
			
			//	the settings management form
			$new_setting = true;
			if ($seo_image_settings != null ) {
				foreach((array)$seo_image_settings as $image => $data) : 
					if ($data['rotator_id'] == $rotator_id) $new_setting = false;
				endforeach;	
			}
			seo_image_rotator_settings_admin();

		echo '</div>';
	} else {
		echo '<div class="wrap">';
			//	handle rotator add
			if(isset( $_REQUEST["action"] ) && $_REQUEST['action'] == 'rotator_add')
				seo_image_rotator_add();
			//	handle rotator delete
			if(isset( $_REQUEST["action"] ) && $_REQUEST['action'] == 'rotator_delete')
				seo_image_rotator_delete($_REQUEST['rotator_id'], $_REQUEST['rotator_name']);
				
			//	the rotator list form
			seo_image_rotator_list();
			
		echo '</div>';
	}	
}

/*
///////////////////////////////////////////////
this section handles uploading images, adding
the image data to the database, deleting images,
and deleting image data from the database.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function handles the rotator add,
function seo_image_rotator_add() {
	global $seo_image_rotators;
	$temp = 0;
	if ($seo_image_rotators != null) {
		foreach($seo_image_rotators as $rotator => $data) : 
			if ($data['rotator_id'] > $temp ) $temp =$data['rotator_id'] ;		
		endforeach;
	}
	$temp = intval($temp) + 1;
	
	//	add the image data to the array
	
	$seo_image_rotators[$temp] = array(
		'rotator_id' => $temp,
		'rotator_name' => $_REQUEST['rotator_name']
	);
	//	add the rotator information to the database
	update_option('seo_image_rotators', $seo_image_rotators);
}

function seo_image_rotator_delete($rotator_id, $rotator_name) {
	global $seo_image_rotators, $seo_image_settings, $seo_image_rotator_images;
	
	//	if the ID passed to this function is invalid, halt the process, and don't try to delete.
	if(!isset($seo_image_rotators[$rotator_id])) return;
	
	//	remove the rotator data from the db
	unset($seo_image_rotators[$rotator_id]);
	update_option('seo_image_rotators', $seo_image_rotators);
	
	// if the setting for this rotator exist, remove the setting data of the rotator from the db
	if(isset($seo_image_settings[$rotator_id])) {
		unset($seo_image_settings[$rotator_id]);
		update_option('seo_image_settings', $seo_image_settings);
	}
	
	// if the images for this rotator exist, remove the image data of the rotator from the db and delele the image files
	if( $seo_image_rotator_images != null ) {
		foreach((array)$seo_image_rotator_images as $id => $data) :
			if ($data['rotator_id'] == $rotator_id) {
				// delete the files of the rotator
				unlink($data['file']);
				unlink($data['thumbnail']);
				
				// remove the image data of the rotator from DB
				unset($seo_image_rotator_images[$id]);
				update_option('seo_image_rotator_images', $seo_image_rotator_images);
			}
		endforeach;		
	}
}

//	this function handles the file upload,
//	resize/crop, and adds the image data to the db
function seo_image_rotator_handle_upload() {
	global $seo_image_rotator_settings, $seo_image_rotator_images, $rotator_id;
	//	use the timestamp as the array key and id
	$time = date('YmdHis');
	
	//	upload the image
	$upload = wp_handle_upload($_FILES['seo_image_rotator'], 0);
	
	//	extract the $upload array
	extract($upload);
	
	//	the URL of the directory the file was loaded in
	$upload_dir_url = str_replace(basename($file), '', $url);
	
	//	get the image dimensions
	list($width, $height) = getimagesize($file);
	
	//	if the uploaded file is NOT an image
	if(strpos($type, 'image') === FALSE) {
		unlink($file); // delete the file
		echo '<div class="error" id="message"><p>Sorry, but the file you uploaded does not seem to be a valid image. Please try again.</p></div>';
		return;
	}
	
	//	if the image doesn't meet the minimum width/height requirements ...
	if($width < $seo_image_rotator_settings['img_width'] || $height < $seo_image_rotator_settings['img_height']) {
		unlink($file); // delete the image
		echo '<div class="error" id="message"><p>Sorry, but this image does not meet the minimum height/width requirements. Please upload another image</p></div>';
		return;
	}
	
	//	if the image is larger than the width/height requirements, then scale it down.
	if($width > $seo_image_rotator_settings['img_width'] || $height > $seo_image_rotator_settings['img_height']) {
		//	resize the image
		
		$image = wp_get_image_editor($file);
		
		if ( ! is_wp_error( $image ) ) {
			$image->resize($seo_image_rotator_settings['img_width'], $seo_image_rotator_settings['img_height'], true);
			$dest_file = $image->generate_filename( $time.'-resized', $dest_path );
			$final_image = $image->save( $dest_file );
		}
		if(isset($final_image)){
			if ( ! is_wp_error( $final_image ) ) {
				$resized_url = $upload_dir_url . basename($final_image['file']);
				//print_r($final_image);
				//	delete the original
				unlink($file);
				$file = $final_image['path'];
				$url = $resized_url;
			}
		}
	}
	
	//	make the thumbnail
	$thumb_height = round((100 * $seo_image_rotator_settings['img_height']) / $seo_image_rotator_settings['img_width']);
	if(isset($upload['file'])) {
		
		$thumbnail = wp_get_image_editor($file);
		
		if ( ! is_wp_error( $thumbnail ) ) {
			$thumbnail->resize( 100, $thumb_height, true );
			$dest_thumb = $thumbnail->generate_filename( 'thumb', $dest_path );
			$final_thumbnail = $thumbnail->save( $dest_thumb );
		}
		if(isset($final_thumbnail)){
			if ( ! is_wp_error( $final_thumbnail ) ) {
				$thumbnail_url = $upload_dir_url . basename($final_thumbnail['file']);
				$thumbnail = $final_thumbnail['path'];
			}
		}
		else{
			$thumbnail_url = "";
			$thumbnail = "";
		}
	}	
	
	//	add the image data to the array

	// UPDATE April 2011 by Chris Grab - added seo_image_rotator_image_caption to the array

	$seo_image_rotator_images[$time] = array(
		'rotator_id' => $rotator_id,
		'id' => $time,
		'file' => $file,
		'file_url' => $url,
		'thumbnail' => $thumbnail,
		'thumbnail_url' => $thumbnail_url,
		'image_links_to' => '',
		'seo_image_rotator_image_caption' => '',
		'seo_image_rotator_image_title' => ''
	);
	

	//	add the image information to the database
	$seo_image_rotator_images['update'] = 'Added';
	update_option('seo_image_rotator_images', $seo_image_rotator_images);
	
}

//	this function deletes the image,
//	and removes the image data from the db
function seo_image_rotator_delete_upload($id) {
	global $seo_image_rotator_images;
	
	//	if the ID passed to this function is invalid,
	//	halt the process, and don't try to delete.
	if(!isset($seo_image_rotator_images[$id])) return;
	
	//	delete the image and thumbnail
	unlink($seo_image_rotator_images[$id]['file']);
	unlink($seo_image_rotator_images[$id]['thumbnail']);
	
	//	indicate that the image was deleted
	$seo_image_rotator_images['update'] = 'Deleted';
	
	//	remove the image data from the db
	unset($seo_image_rotator_images[$id]);
	update_option('seo_image_rotator_images', $seo_image_rotator_images);
}


/*
///////////////////////////////////////////////
these two functions check to see if an update
to the data just occurred. if it did, then they
will display a notice, and reset the update option.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/

//	this function checks to see if we just updated the settings
//	if so, it displays the "updated" message.
function seo_image_rotator_settings_update_check() {
	global $seo_image_rotator_settings;
	if(isset($seo_image_rotator_settings['update'])) {
		echo '<div class="updated fade" id="message"><p>SEO Image Rotator Settings <strong>'.$seo_image_rotator_settings['update'].'</strong></p></div>';
		unset($seo_image_rotator_settings['update']);
		update_option('seo_image_rotator_settings', $seo_image_rotator_settings);
	}
}
//	this function checks to see if we just added a new image
//	if so, it displays the "updated" message.
function seo_image_rotator_images_update_check() {
	global $seo_image_rotator_images;
	if(isset( $seo_image_rotator_images['update'] ) && $seo_image_rotator_images['update'] == 'Added' || isset( $seo_image_rotator_images['update'] ) && $seo_image_rotator_images['update'] == 'Deleted' || isset( $seo_image_rotator_images['update'] ) && $seo_image_rotator_images['update'] == 'Updated') {
		echo '<div class="updated fade" id="message"><p>Image(s) '.$seo_image_rotator_images['update'].' Successfully</p></div>';
		unset($seo_image_rotator_images['update']);
		update_option('seo_image_rotator_images', $seo_image_rotator_images);
	}
}


/*
///////////////////////////////////////////////
these two functions display the front-end code
on the admin page. it's mostly form markup.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	display the images administration code
function seo_image_rotator_list() { ?>
	<?php global $seo_image_rotators; $time = date('YmdHis');?>
	<h2 style="padding: 20px 0 0;">
		<?php _e('SEO Image Rotator - List Rotators', 'SEO Image Rotator') ?>
	</h2>
	<div style = "padding:20px 0 20px 20px;">
		<form method="post" action="?page=seo_image_rotator">
			<span >Rotator Name : </span>
			<input type="text" name="rotator_name" value="" size="35" />
			<input type="submit" class="button-primary" value="<?php _e('Add rotator') ?>" style="margin:0 50px;"/>
			<input type="hidden" name="action" value="rotator_add" />
		</form>
	</div>
	<?php if(!empty($seo_image_rotators)) { ?>
	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th style='width:10%'>No</th>	
				<th >Rotator Name</th>				
				<th >Actions</th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<th style='width:10%'>No</th>	
				<th >Rotator Name</th>				
				<th >Actions</th>
			</tr>
		</tfoot>
		
		<tbody>
		
		<form method="post" action="options.php">
		<?php settings_fields('seo_image_rotators'); ?>
		<?php foreach($seo_image_rotators as $rotator => $data) :  ?>
			<tr>
				<td><?php echo $data['rotator_id'];?></td>				
				<td><input type="text" class="" name="seo_image_rotators[<?php echo $rotator; ?>][rotator_name]" value="<?php echo $data['rotator_name']; ?>" size="40" readonly /></td>
				<td>
					<a href="?page=seo_image_rotator&amp;action=rotator_edit&amp;rotator_id=<?php echo $rotator;?>" class="button">Edit</a>
					<a href="?page=seo_image_rotator&amp;action=rotator_delete&amp;rotator_id=<?php echo $rotator;?>" class="button">Delete</a>
				</td>
			</tr>
		<?php endforeach; ?>
		
		</form>
		
		</tbody>
	</table>
	<?php } else { ?>
		<div style = "padding:0 20px 20px;">
			<h3>No created rotators.</h3>	
		</div>
	<?php } ?>
<?php
}

//	display the images administration code
function seo_image_rotator_images_admin() { ?>
	<?php global $seo_image_rotator_images, $rotator_id, $rotator_name; ?>
	<?php seo_image_rotator_images_update_check(); ?>
	<h2 style="padding: 20px 0 20px;"><?php _e('SEO Image Rotator - Images', 'seo_image_rotator'); ?>
		<a href="?page=seo_image_rotator" class="add-new-h2"><?php _e('Go List', 'SEO Image Rotator') ?></a>
	</h2>
	<table class="form-table">
		<tr valign="top"><th scope="row">Upload New Image</th>
			<td>
			<form enctype="multipart/form-data" method="post" action="?page=seo_image_rotator&action=rotator_edit&rotator_id=<?php echo $rotator_id;?>&rotator_name=<?php echo $rotator_name;?>">
				<input type="hidden" name="post_id" id="post_id" value="0" />
				<input type="hidden" name="rotator_id" id="rotator_id" value="<?php echo $rotator_id;?>" />
				
				<input type="hidden" name="action" id="action" value="wp_handle_upload" />
				
				<label for="seo_image_rotator">Select a File: </label>
				<input type="file" name="seo_image_rotator" id="seo_image_rotator" />
				<input type="submit" class="button-primary" name="html-upload" value="Upload" />
			</form>
			</td>
		</tr>
	</table><br />
	
	<?php if(!empty($seo_image_rotator_images)) : ?>
	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th scope="col" class="column-slug1">Image</th>
				<th scope="col">Image Links To</th>
				<!-- update april 27 2011 by chris grab -- added image caption column to table -->
				<th scope="col">Image Caption</th>
				<th scope="col">Image Title</th>
				<th scope="col" class="column-slug1">Actions</th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<th scope="col" class="column-slug1">Image</th>
				<th scope="col">Image Links To</th>
				<!-- update april 27 2011 by chris grab -- added image caption column to table -->
				<th scope="col">Image Caption</th>
				<th scope="col">Image Title</th>
				<th scope="col" class="column-slug1">Actions</th>
			</tr>
		</tfoot>
		
		<tbody>
		<form method="post" action="options.php">
		<?php settings_fields('seo_image_rotator_images'); ?>
		<?php foreach((array)$seo_image_rotator_images as $image => $data) : ?>
			<?php if ($data['rotator_id'] == $rotator_id) { ?>
				<tr>
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
					
					<?php if($data['thumbnail']):?><input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][thumbnail]" value="<?php echo $data['thumbnail']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" /><?php endif;?>
					<th scope="row" class="column-slug">
						<?php if($data['thumbnail']):?><img src="<?php echo $data['thumbnail_url']; ?>" /><?php else:?><img width="100" src="<?php echo $data['file_url']; ?>" /><?php endif;?>
						<div><?php echo basename($data['file_url']); ?></div>
					</th>
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][image_links_to]" value="<?php echo $data['image_links_to']; ?>" size="35" /></td>
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][seo_image_rotator_image_caption]" value="<?php echo $data['seo_image_rotator_image_caption']; ?>" size="35" /></td>
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][seo_image_rotator_image_title]" value="<?php echo $data['seo_image_rotator_image_title']; ?>" size="35" /></td>
					<td class="column-slug">
						<input type="submit" class="button-primary" value="Update" /> 
						<a href="?page=seo_image_rotator&amp;action=image_delete&amp;delete=<?php echo $image; ?>&rotator_id=<?php echo $rotator_id;?>" class="button">Delete</a></td>
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][rotator_id]" value="<?php echo $rotator_id;?>" />		
				</tr>
			<?php } else { ?>
				<tr style='display:none'>
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
					
					<?php if($data['thumbnail']):?><input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][thumbnail]" value="<?php echo $data['thumbnail']; ?>" />
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" /><?php endif;?>
					<th scope="row" class="column-slug">
						<?php if($data['thumbnail']):?><img src="<?php echo $data['thumbnail_url']; ?>" /><?php else:?><img width="100" src="<?php echo $data['file_url']; ?>" /><?php endif;?>
						<div><?php echo basename($data['file_url']); ?></div>
					</th>
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][image_links_to]" value="<?php echo $data['image_links_to']; ?>" size="35" /></td>
					<!-- update arpil 27 2011 by chris grab -- added image caption field to form -->
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][seo_image_rotator_image_caption]" value="<?php echo $data['seo_image_rotator_image_caption']; ?>" size="35" /></td>
					<td><input type="text" name="seo_image_rotator_images[<?php echo $image; ?>][seo_image_rotator_image_title]" value="<?php echo $data['seo_image_rotator_image_title']; ?>" size="35" /></td>
					<td class="column-slug">
						<input type="submit" class="button-primary" value="Update" /> 
						<a href="?page=seo_image_rotator&amp;action=image_delete&amp;delete=<?php echo $image; ?>&rotator_id=<?php echo $rotator_id;?>&rotator_name=<?php echo $rotator_name;?>" class="button">Delete</a></td>
					<input type="hidden" name="seo_image_rotator_images[<?php echo $image; ?>][rotator_id]" value="<?php echo $data['rotator_id'];?>" />		
				</tr>
			<?php } ?>
		<?php endforeach; ?>
		<input type="hidden" name="seo_image_rotator_images[update]" value="Updated" />
		
		</form>
		
		</tbody>
	</table>
	<?php endif; ?>

<?php
}

//	display the settings administration code
function seo_image_rotator_settings_admin() { ?>
	<?php seo_image_rotator_settings_update_check(); ?>
	<h2 style="padding: 20px 0 0;"><?php _e('SEO Image Rotator - Settings', 'seo-image-rotator'); ?></h2>
	<form method="post" action="options.php">
	<?php settings_fields('seo_image_settings'); ?>
	<?php 
    global $seo_image_settings, $seo_image_rotator_settings, $seo_image_rotators, $rotator_id, $new_setting;	?>
	<table class="form-table">
	<?php if ( $seo_image_settings == null || $new_setting == true) { $options = $seo_image_rotator_settings;?>
		<tr valign="top"><th scope="row">Caption Show</th>
			<td><input name="seo_image_settings[<?php echo $rotator_id; ?>][caption]" type="checkbox" value="1" <?php checked('1', $options['caption']); ?> /> <label for="seo_image_settings[<?php echo $rotator_id; ?>][caption]">Check this box if you want to show the caption of the image</td>
			</tr>
			<tr><th scope="row">Enable Random Image Order</th>
				<td><input name="seo_image_settings[<?php echo $rotator_id; ?>][random]" type="checkbox" value="1" <?php checked('1', $options['random']); ?> /> <label for="seo_image_settings[<?php echo $rotator_id; ?>][random]">Check this box if you want to enable random image order</td>
			</td></tr>
			<tr valign="top"><th scope="row">Transition Enabled</th>
			<td><input name="seo_image_settings[<?php echo $rotator_id; ?>][rotate]" type="checkbox" value="1" <?php checked('1', $options['rotate']); ?> /> <label for="seo_image_settings[<?php echo $rotator_id; ?>][rotate]">Check this box if you want to enable the transition effects</td>
			</tr>
			<tr><th scope="row">Transition Effect</th>
			<td>Please select the effect you would like to use when your images rotate (if applicable):<br />
				<select name="seo_image_settings[<?php echo $rotator_id; ?>][effect]">
					<option value="fade" <?php selected('fade', $options['effect']); ?>>fade</option>
					<option value="wipe" <?php selected('wipe', $options['effect']); ?>>wipe</option>
					<option value="scrollUp" <?php selected('scrollUp', $options['effect']); ?>>scrollUp</option>
					<option value="scrollDown" <?php selected('scrollDown', $options['effect']); ?>>scrollDown</option>
					<option value="scrollLeft" <?php selected('scrollLeft', $options['effect']); ?>>scrollLeft</option>
					<option value="scrollRight" <?php selected('scrollRight', $options['effect']); ?>>scrollRight</option>
					<option value="cover" <?php selected('cover', $options['effect']); ?>>cover</option>
					<option value="shuffle" <?php selected('shuffle', $options['effect']); ?>>shuffle</option>
				</select>
			</td></tr>
			
			<tr><th scope="row">Transition Delay</th>
			<td>Length of time (in seconds) you would like each image to be visible:<br />
				<input type="text" name="seo_image_settings[<?php echo $rotator_id; ?>][delay]" value="<?php echo $options['delay'] ?>" size="4" />
				<label for="seo_image_settings[<?php echo $rotator_id; ?>][delay]">second(s)</label>
			</td></tr>
			
			<tr><th scope="row">Transition Length</th>
			<td>Length of time (in seconds) you would like the transition length to be:<br />
				<input type="text" name="seo_image_settings[<?php echo $rotator_id; ?>][duration]" value="<?php echo $options['duration'] ?>" size="4" />
				<label for="seo_image_settings[<?php echo $rotator_id; ?>][duration]">second(s)</label>
			</td></tr>

			<tr><th scope="row">Image Dimensions</th>
			<td>Please input the width of the image rotator:<br />
				<input type="text" name="seo_image_settings[<?php echo $rotator_id; ?>][img_width]" value="<?php echo $options['img_width'] ?>" size="4" />
				<label for="seo_image_settings[<?php echo $rotator_id; ?>][img_width]">px</label>
				<br /><br />
				Please input the height of the image rotator:<br />
				<input type="text" name="seo_image_settings[<?php echo $rotator_id; ?>][img_height]" value="<?php echo $options['img_height'] ?>" size="4" />
				<label for="seo_image_settings[<?php echo $rotator_id; ?>][img_height]">px</label>
			</td></tr>
			
			<tr><th scope="row">Rotator name</th>
			<td>Please indicate what you would like the rotator name to be:<br />
				<input type="text" name="seo_image_settings[<?php echo $rotator_id; ?>][rotator_name]" id="setting_container" value="<?php echo $seo_image_rotators[$rotator_id]['rotator_name']  ?>"  />
				<input type="hidden" name="seo_image_settings[<?php echo $rotator_id; ?>][rotator_id]" id="setting_container" value="<?php echo $rotator_id ?>"  />
			</td>
		</tr>
		<?php if ( $seo_image_settings != null ) { ?>
			<?php foreach((array)$seo_image_settings as $setting => $options) : ?>
                <?php if (is_array($options) ) { ?>
				<tbody style="display:none;">
				<tr valign="top"><th scope="row">Caption Show</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][caption]" type="checkbox" value="1" <?php checked('1', $options['caption']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][caption]">Check this box if you want to show the caption of the image</td>
					</tr>
					<tr>
					<th scope="row">Enable Random Image Order</th>
						<td><input name="seo_image_settings[<?php echo $setting; ?>][random]" type="checkbox" value="1" <?php checked('1', $options['random']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][random]">Check this box if you want to enable random image order</td>
					</td></tr>
					<tr valign="top"><th scope="row">Transition Enabled</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][rotate]" type="checkbox" value="1" <?php checked('1', $options['rotate']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][rotate]">Check this box if you want to enable the transition effects</td>
					</tr>
					<tr><th scope="row">Transition Effect</th>
					<td>Please select the effect you would like to use when your images rotate (if applicable):<br />
						<select name="seo_image_settings[<?php echo $setting; ?>][effect]">
							<option value="fade" <?php selected('fade', $options['effect']); ?>>fade</option>
							<option value="wipe" <?php selected('wipe', $options['effect']); ?>>wipe</option>
							<option value="scrollUp" <?php selected('scrollUp', $options['effect']); ?>>scrollUp</option>
							<option value="scrollDown" <?php selected('scrollDown', $options['effect']); ?>>scrollDown</option>
							<option value="scrollLeft" <?php selected('scrollLeft', $options['effect']); ?>>scrollLeft</option>
							<option value="scrollRight" <?php selected('scrollRight', $options['effect']); ?>>scrollRight</option>
							<option value="cover" <?php selected('cover', $options['effect']); ?>>cover</option>
							<option value="shuffle" <?php selected('shuffle', $options['effect']); ?>>shuffle</option>
						</select>
					</td></tr>
					
					<tr><th scope="row">Transition Delay</th>
					<td>Length of time (in seconds) you would like each image to be visible:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][delay]" value="<?php echo $options['delay'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][delay]">second(s)</label>
					</td></tr>
					
					<tr><th scope="row">Transition Length</th>
					<td>Length of time (in seconds) you would like the transition length to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][duration]" value="<?php echo $options['duration'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][duration]">second(s)</label>
					</td></tr>

					<tr><th scope="row">Image Dimensions</th>
					<td>Please input the width of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_width]" value="<?php echo $options['img_width'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_width]">px</label>
						<br /><br />
						Please input the height of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_height]" value="<?php echo $options['img_height'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_height]">px</label>
					</td></tr>
					
					<tr><th scope="row">Rotator name</th>
					<td>Please indicate what you would like the rotator name to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][rotator_name]" id="setting_container" value="<?php echo $options['rotator_name'] ?>" readonly />
						<input type="hidden" name="seo_image_settings[<?php echo $setting; ?>][rotator_id]" id="setting_container" value="<?php echo $options['rotator_id'] ?>" readonly />
					</td>
				</tr>
				</tbody>
                <?php } ?>
			<?php endforeach;?>
		<?php } ?>
	<?php } else { ?>
		<?php foreach((array)$seo_image_settings as $setting => $options) : ?>
			<?php if ($options['rotator_id'] == $rotator_id ) { ?>	
				<tr valign="top"><th scope="row">Caption Show</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][caption]" type="checkbox" value="1" <?php checked('1', $options['caption']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][caption]">Check this box if you want to show the caption of the image</td>
					</tr>
					<tr>
					<th scope="row">Enable Random Image Order</th>
						<td><input name="seo_image_settings[<?php echo $setting; ?>][random]" type="checkbox" value="1" <?php checked('1', $options['random']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][random]">Check this box if you want to enable random image order</td>
					</td></tr>
					<tr valign="top"><th scope="row">Transition Enabled</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][rotate]" type="checkbox" value="1" <?php checked('1', $options['rotate']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][rotate]">Check this box if you want to enable the transition effects</td>
					</tr>
					<tr><th scope="row">Transition Effect</th>
					<td>Please select the effect you would like to use when your images rotate (if applicable):<br />
						<select name="seo_image_settings[<?php echo $setting; ?>][effect]">
							<option value="fade" <?php selected('fade', $options['effect']); ?>>fade</option>
							<option value="wipe" <?php selected('wipe', $options['effect']); ?>>wipe</option>
							<option value="scrollUp" <?php selected('scrollUp', $options['effect']); ?>>scrollUp</option>
							<option value="scrollDown" <?php selected('scrollDown', $options['effect']); ?>>scrollDown</option>
							<option value="scrollLeft" <?php selected('scrollLeft', $options['effect']); ?>>scrollLeft</option>
							<option value="scrollRight" <?php selected('scrollRight', $options['effect']); ?>>scrollRight</option>
							<option value="cover" <?php selected('cover', $options['effect']); ?>>cover</option>
							<option value="shuffle" <?php selected('shuffle', $options['effect']); ?>>shuffle</option>
						</select>
					</td></tr>
					
					<tr><th scope="row">Transition Delay</th>
					<td>Length of time (in seconds) you would like each image to be visible:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][delay]" value="<?php echo $options['delay'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][delay]">second(s)</label>
					</td></tr>
					
					<tr><th scope="row">Transition Length</th>
					<td>Length of time (in seconds) you would like the transition length to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][duration]" value="<?php echo $options['duration'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][duration]">second(s)</label>
					</td></tr>

					<tr><th scope="row">Image Dimensions</th>
					<td>Please input the width of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_width]" value="<?php echo $options['img_width'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_width]">px</label>
						<br /><br />
						Please input the height of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_height]" value="<?php echo $options['img_height'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_height]">px</label>
					</td></tr>
					
					<tr><th scope="row">Rotator name</th>
					<td>Please indicate what you would like the rotator name to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][rotator_name]" id="setting_container" value="<?php echo $seo_image_rotators[$rotator_id]['rotator_name'] ?>"  />
						<input type="hidden" name="seo_image_settings[<?php echo $setting; ?>][rotator_id]" id="setting_container" value="<?php echo $rotator_id ?>"  />					
					</td>
				</tr>
			<?php } else if ( is_array($options) ){ ?>
				<tbody style="display:none;">
				<tr valign="top"><th scope="row">Caption Show</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][caption]" type="checkbox" value="1" <?php checked('1', $options['caption']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][caption]">Check this box if you want to show the caption of the image</td>
					</tr>
					<tr>
					<th scope="row">Enable Random Image Order</th>
						<td><input name="seo_image_settings[<?php echo $setting; ?>][random]" type="checkbox" value="1" <?php checked('1', $options['random']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][random]">Check this box if you want to enable random image order</td>
					</td></tr>
					<tr valign="top"><th scope="row">Transition Enabled</th>
					<td><input name="seo_image_settings[<?php echo $setting; ?>][rotate]" type="checkbox" value="1" <?php checked('1', $options['rotate']); ?> /> <label for="seo_image_settings[<?php echo $setting; ?>][rotate]">Check this box if you want to enable the transition effects</td>
					</tr>
					<tr><th scope="row">Transition Effect</th>
					<td>Please select the effect you would like to use when your images rotate (if applicable):<br />
						<select name="seo_image_settings[<?php echo $setting; ?>][effect]">
							<option value="fade" <?php selected('fade', $options['effect']); ?>>fade</option>
							<option value="wipe" <?php selected('wipe', $options['effect']); ?>>wipe</option>
							<option value="scrollUp" <?php selected('scrollUp', $options['effect']); ?>>scrollUp</option>
							<option value="scrollDown" <?php selected('scrollDown', $options['effect']); ?>>scrollDown</option>
							<option value="scrollLeft" <?php selected('scrollLeft', $options['effect']); ?>>scrollLeft</option>
							<option value="scrollRight" <?php selected('scrollRight', $options['effect']); ?>>scrollRight</option>
							<option value="cover" <?php selected('cover', $options['effect']); ?>>cover</option>
							<option value="shuffle" <?php selected('shuffle', $options['effect']); ?>>shuffle</option>
						</select>
					</td></tr>
					
					<tr><th scope="row">Transition Delay</th>
					<td>Length of time (in seconds) you would like each image to be visible:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][delay]" value="<?php echo $options['delay'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][delay]">second(s)</label>
					</td></tr>
					
					<tr><th scope="row">Transition Length</th>
					<td>Length of time (in seconds) you would like the transition length to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][duration]" value="<?php echo $options['duration'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][duration]">second(s)</label>
					</td></tr>

					<tr><th scope="row">Image Dimensions</th>
					<td>Please input the width of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_width]" value="<?php echo $options['img_width'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_width]">px</label>
						<br /><br />
						Please input the height of the image rotator:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][img_height]" value="<?php echo $options['img_height'] ?>" size="4" />
						<label for="seo_image_settings[<?php echo $setting; ?>][img_height]">px</label>
					</td></tr>
					
					<tr><th scope="row">Rotator name</th>
					<td>Please indicate what you would like the rotator name to be:<br />
						<input type="text" name="seo_image_settings[<?php echo $setting; ?>][rotator_name]" id="setting_container" value="<?php echo $options['rotator_name']  ?>" readonly />
						<input type="hidden" name="seo_image_settings[<?php echo $setting; ?>][rotator_id]" id="setting_container" value="<?php echo $options['rotator_id']  ?>" readonly />					
					</td>
				</tr>
				</tbody>
			<?php } ?>
		<?php endforeach; ?>
	<?php } ?>
		
		<input type="hidden" name="seo_image_settings[update]" value="Added" />
		
	</table>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
	</form>
	
	<!-- The Reset Option -->
	<form method="post" action="options.php">
	<?php settings_fields('seo_image_rotator_settings'); ?>
	<?php global $seo_image_rotator_defaults; // use the defaults ?>
	<?php foreach((array)$seo_image_rotator_defaults as $key => $value) : ?>
	<input type="hidden" name="seo_image_rotator_settings[<?php echo $key; ?>]" value="<?php echo $value; ?>" />
	<?php endforeach; ?>
	<input type="hidden" name="seo_image_rotator_settings[update]" value="RESET" />
	<input type="submit" class="button" value="<?php _e('Reset Settings') ?>" />
	</form>
	<!-- End Reset Option -->
	</p>

<?php
}


/*
///////////////////////////////////////////////
these two functions sanitize the data before it
gets stored in the database via options.php
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function sanitizes our settings data for storage
function seo_image_rotators_validate($input) {
	//$input['rotator_id'] = wp_filter_nohtml_kses($input['rotator_id']);
	
	return $input;
}

//	this function sanitizes our settings data for storage
function seo_image_settings_validate($input) {
	global $seo_image_rotators;
	$input['rotate'] = ($input['rotate'] == 1 ? 1 : 0);
	$input['random'] = ($input['random'] == 1 ? 1 : 0);
	$input['effect'] = wp_filter_nohtml_kses($input['effect']);
	$input['img_width'] = intval($input['img_width']);
	$input['img_height'] = intval($input['img_height']);
	foreach((array)$input as $key => $value) :
        if (is_array($value)) $seo_image_rotators[$value['rotator_id']]['rotator_name'] = $value['rotator_name'] ;
    endforeach;
	
	//	add the rotator information to the database
	update_option('seo_image_rotators', $seo_image_rotators);
	return $input;
}
//	this function sanitizes our image data for storage
function seo_image_rotator_images_validate($input) {
	foreach((array)$input as $key => $value) {
		if($key != 'update') {
			$input[$key]['file_url'] = esc_url($value['file_url']);
			$input[$key]['thumbnail_url'] = esc_url($value['thumbnail_url']);
			
			if($value['image_links_to'])
			$input[$key]['image_links_to'] = esc_url($value['image_links_to']);

			//sanitize caption before adding to DB
			if($value['seo_image_rotator_image_caption'])
			$input[$key]['seo_image_rotator_image_caption'] = wp_filter_nohtml_kses($value['seo_image_rotator_image_caption']);
			
			//sanitize caption before adding to DB
			if($value['seo_image_rotator_image_title'])
			$input[$key]['seo_image_rotator_image_title'] = wp_filter_nohtml_kses($value['seo_image_rotator_image_title']);
		}
	}
	return $input;
}

/*
///////////////////////////////////////////////
this final section generates all the code that
is displayed on the front-end of the WP Theme
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function seo_image_rotator($id, $content = null) {
	global $seo_image_rotator_settings, $seo_image_rotator_images, $seo_image_settings, $seo_image_rotators;
	
	
	// possible future use
	//$args = wp_parse_args($args, $seo_image_rotator_settings);
	
	$newline = "\n"; // line break
    if ($id == $seo_image_settings[$id]['rotator_id']) {
        echo '<div id="'.$seo_image_settings[$id]['rotator_id'].'">'.$newline;
        foreach((array)$seo_image_rotators as $rotator => $data) {
            if ($data['rotator_id'] == $id) $rotator_id = $rotator;             
        }
		
        foreach((array)$seo_image_rotator_images as $image => $data) {
            if ($rotator_id == $data['rotator_id']) {
                echo '<div>';
                if($data['image_links_to'])
                    echo '<a href="'.$data['image_links_to'].'" >';
                echo '<img src="'.$data['file_url'].'" width="'.$seo_image_settings[$id]['img_width'].'" height="'.$seo_image_settings[$id]['img_height'].'" class="'.$data['id'].'" alt="'. $data['seo_image_rotator_image_caption'] .'" title="'.$data['seo_image_rotator_image_title'].'" />';
                if($data['image_links_to'])
                    echo '</a>';
				if ($seo_image_settings[$id]['caption'])
					echo '<p id="caption">'. $data['seo_image_rotator_image_caption'].'</p>';
                echo '</div>';
            }             
        }
        
        echo '</div>'.$newline;        
        if ($seo_image_settings[$id]['rotate']) : if ( $seo_image_settings[$id]['random'] == 1 ) $random=1; else $random = 0; ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#<?php echo $seo_image_settings[$id]['rotator_id']; ?>").cycle({ 
                    fx: '<?php echo $seo_image_settings[$id]['effect']; ?>',
                    timeout: <?php echo ($seo_image_settings[$id]['delay'] * 1000); ?>,
                    speed: <?php echo ($seo_image_settings[$id]['duration'] * 1000); ?>,
                    random: <?php echo $random; ?>,        
                    pause: 1,
                    fit: 1,
                    after: function() {                               
                      }
                });

            });
        </script>
        <?php endif;
    }
	
	
	

}
//	create the shortcode [seo_image_rotator]
add_shortcode('seo_image_rotator', 'seo_image_rotator_shortcode');
function seo_image_rotator_shortcode($atts) {
	extract( shortcode_atts( array(
      'id' => 'caption',
      ), $atts ) );
	// Temp solution, output buffer the echo function.
	ob_start();
	seo_image_rotator(esc_attr($id));
	$output = ob_get_clean();
	
	return $output;
	
}


add_action('wp_print_scripts', 'seo_image_rotator_scripts');
function seo_image_rotator_scripts() {
	if(!is_admin())
	wp_enqueue_script('cycle', WP_CONTENT_URL.'/plugins/seo-image-rotator/jquery.cycle.all.min.js', array('jquery'), '', true);
}

add_action('wp_footer', 'seo_image_rotator_args', 15);
function seo_image_rotator_args() {
	global $seo_image_rotator_settings; ?>


<?php }

add_action( 'wp_head', 'seo_image_rotator_style' );
function seo_image_rotator_style() { 
	global $seo_image_rotator_settings;
?>
	
<style type="text/css" media="screen">
	
</style>
	
<?php }