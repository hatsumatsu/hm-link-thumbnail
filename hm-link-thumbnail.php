<?php
/*
Plugin Name: HM Link Thumbnail
Version: 0.1
Description: Automatically save a website screenshot as featured image for all posts that titles are a valid URLs.
Plugin URI: http://hatsumatsu.de/
Author: HATSUMATSU, Martin Wecke
Author URI: http://hatsumatsu.de/
*/

/**
 * I11n
 */
load_plugin_textdomain( 'hm-link-thumbnail', '/wp-content/plugins/hm-link-thumbnail/' );

/**
 * set the thumbnail provider's API URL
 *
 */
function hmlt_plugin_activate() {
	update_option( 'hmlt_service_url', 'http://api.snapito.com/?size=mc&url=' );
}

register_activation_hook( __FILE__, 'hmlt_plugin_activate' );


/**
 * Save post metadata when a post is saved.
 *
 * @param 	int 	$post_id The ID of the post.
 */
function hmlt_save_post( $post_id ) {

	// ignore autosave and post revisions
    if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // post title is valid URL
    if( hmlt_is_url( get_the_title( $post_id ) ) ) {

    	// delete current post thumbnail
    	if( has_post_thumbnail( $post_id ) ) {
    		hmlt_delete_post_thumbnail( get_post_thumbnail_id( $post_id ) );
    	}

    	$image_path = get_option( 'hmlt_service_url' ) . str_replace( 'http://', '', get_the_title( $post_id ) );

 		hmlt_create_attachment( $image_path, $post_id );

	}


}

add_action( 'save_post', 'hmlt_save_post' );


/**
 * create attachment
 *
 * @param 	string 	$url 		API url http://api.service.com/?url=myurl.com
 * @param 	string 	$file_name 	name of local file in wp-content/uploads/
 */
function hmlt_create_attachment( $url, $parent_id ) {

	$filename = 'hm-site-thumbnail-' . $parent_id . '.png';
	$file = hmlt_grab_remote_file( $url, $filename );

	$filetype = wp_check_filetype( $filename, null );
	$wp_upload_dir = wp_upload_dir();

	$attachment = array(
		'guid' => $wp_upload_dir['url'] . '/' . $filename, 
		'post_mime_type' => $filetype['type'],
		'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
		'post_content' => '',
		'post_status' => 'inherit'
	);

	$attachment_id = wp_insert_attachment( $attachment, $file, $parent_id );
	
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
	wp_update_attachment_metadata( $attachment_id, $attachment_data );

	hmlt_set_featured_image( $parent_id, $attachment_id );

}


/**
 * Get image from thumbnail provider
 *
 * @param 	string 	$url 		API url http://api.service.com/?url=myurl.com
 * @param 	string 	$file_name 	name of local file in wp-content/uploads/
 *
 * @return  string 	$file_path 	local file path of saved image
 */
function hmlt_grab_remote_file( $url, $filename ) {

	$wp_upload_dir = wp_upload_dir();

	$file = file_get_contents( $url );
	file_put_contents( $wp_upload_dir['path'] . '/' . $filename, $file );

	$filepath = $wp_upload_dir['path'] . '/' . $filename;

	return $filepath;
}


/**
 * set post thumbnail 
 *
 * @param 	int 	$parent_id 		parent post id
 * @param 	int 	$attachment_id 	thumbnail post id
 */
function hmlt_set_featured_image( $parent_id, $attachment_id ) {

	set_post_thumbnail( $parent_id, $attachment_id );

}


/**
 * delete post thumbnail 
 *
 * @param 	int 	$id 	thumbnail post id
 */
function hmlt_delete_post_thumbnail( $id ) {

	wp_delete_attachment( $id, true );	

}


/**
 * URL validation 
 *
 * @param 	string 	$url 	string to check
 *
 * @return 	boolean		
 */
function hmlt_is_url( $url ) {

	if( preg_match("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $url ) ) {

		return true;

	} else {

		return false;
	
	}

}