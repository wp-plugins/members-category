<?php
/*
Plugin Name: Members-Category
Plugin URI:  http://sabaoh.sakura.ne.jp/wordpress/
Description: Access restriction by category. WP-Members plugin is required. For more information visit <a href="http://sabaoh.sakura.ne.jp/wordpress/">http://sabaoh.sakura.ne.jp/wordpress/</a>. WP-Members(tm) is a trademark of butlerblog.com.
Version:     1.0.3
Author:      Eiji 'Sabaoh' Yamada
Author URI:  http://sabaoh.sakura.ne.jp/wordpress/
License:     GPLv2
*/


/*  Copyright 2012 Eiji 'Sabaoh' Yamada (email : age.yamada@kxa.biglobe.ne.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * start with any potential translation
 */
load_plugin_textdomain( 'members-category', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );


/**
 * override WP-Members' function
 */
if ( ! function_exists( 'wpmem_block' ) ):
/**
 * Determines if content should be blocked
 *
 * @since 2.6
 *
 * @return bool 
 */
function wpmem_block()
{
	if( is_single() ) {
		//check categories
		$block = false;
		$cats = get_the_category();
		foreach ( $cats as $cate ) {
			if ( get_option( 'sabaohmemcat_' . $cate->slug ) == 'true' )
				$block = true;
		}
		//$not_mem_area = 1; 
		if ( WPMEM_BLOCK_POSTS == 1 && $block && !get_post_custom_values( 'unblock' ) ) { return true; }
		if ( WPMEM_BLOCK_POSTS == 0 && $block &&  get_post_custom_values( 'block' ) )   { return true; }
	}

	if( is_page() && !is_page( 'members-area' ) && !is_page( 'register' ) ) { 
		//$not_mem_area = 1; 
		if ( WPMEM_BLOCK_PAGES == 1 && !get_post_custom_values( 'unblock' ) ) { return true; }
		if ( WPMEM_BLOCK_PAGES == 0 &&  get_post_custom_values( 'block' ) )   { return true; }
	}
	
	return false;
}
endif;


/**
 * filter hook function
 */
function memcat_filter_members( $content ) {
	$block = false;
	if( !is_single() && !is_user_logged_in() && get_option( 'sabaohmemcat_filtercontent' ) == 'true' ) {
		//check categories
		$cats = get_the_category();
		foreach ( $cats as $cate ) {
			if ( get_option( 'sabaohmemcat_' . $cate->slug ) == 'true' )
				$block = true;
		}
	}
	if ( WPMEM_BLOCK_POSTS == 1 && $block && !get_post_custom_values( 'unblock' ) ||
	     WPMEM_BLOCK_POSTS == 0 && $block &&  get_post_custom_values( 'block' ) ) {
		return get_option( 'sabaohmemcat_filterreplace' );
	}
	return $content;
}

add_filter( 'the_content', 'memcat_filter_members' );
add_filter( 'the_excerpt', 'memcat_filter_members' );

/**
 * Add sub menu
 */
function memcat_add_submenu()
{
	add_posts_page( __( 'Members Category Settings', 'members-category' ),
	                __( 'Members Category', 'members-category' ),
	                'manage_categories', __FILE__, 'memcat_settings' );
}

add_action( 'admin_menu', 'memcat_add_submenu' );


/**
 * Members Category Settings
 */
function memcat_settings()
{
	// get categories
	$cats = get_categories( 'hide_empty=0' );

	// save settings when form has posted
	if ( memcat_is_hash( $_POST, 'posted' ) ) {
		foreach ( $cats as $cate ) {
			if ( memcat_is_hash( $_POST, $cate->slug ) ) {
				update_option( 'sabaohmemcat_' . $cate->slug, 'true' );
			} else {
				update_option( 'sabaohmemcat_' . $cate->slug, 'false' );
			}
		}
		// filter options
		if ( memcat_is_hash( $_POST, 'filtercontent' ) ) {
			update_option( 'sabaohmemcat_filtercontent', 'true' );
		} else {
			update_option( 'sabaohmemcat_filtercontent', 'false' );
		}
		update_option( 'sabaohmemcat_filterreplace', $_POST['filterreplace'] );
	}
?>
<?php if ( memcat_is_hash( $_POST, 'posted' ) ) : ?><div class="updated"><p><strong><?php _e( 'Settings has saved', 'members-category' ); ?></strong></p></div><?php endif; ?>
<div class="wrap">
	<h2><?php _e( 'Members Category Settings', 'members-category' ); ?></h2>
	<p><?php _e( 'Please check for categories to be restricted.', 'members-category' ); ?></p>
	<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
		<input type="hidden" name="posted" value="Y">
		<table class="form-table">
<?php foreach ( $cats as $cate ) { ?>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $cate->slug; ?>"><?php echo $cate->name; ?><label></th>
				<td>
					<input name="<?php echo $cate->slug; ?>" type="checkbox" id="<?php echo $cate->slug; ?>" value="true" <?php if ( get_option( 'sabaohmemcat_' . $cate->slug ) == 'true' ) : ?>checked="checked"<?php endif; ?> />
				</td>
			</tr>
<?php } ?>
			<tr valign="top">
			</tr>
			<tr valign="top">
				<th scope="row"><label for="filtercontent"><?php _e( 'filtering contents', 'members-category' ); ?><label></th>
				<td>
					<input name="filtercontent" type="checkbox" id="filtercontent" value="true" <?php if ( get_option( 'sabaohmemcat_filtercontent' ) == 'true' ) : ?>checked="checked"<?php endif; ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="filterreplace"><?php _e( 'replace content with', 'members-category' ); ?><label></th>
				<td>
					<input name="filterreplace" type="text" id="filterreplace" value="<?php echo get_option( 'sabaohmemcat_filterreplace' ); ?>" class="regular-text code" />
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'members-category' ); ?>" />
		</p>
	</form>
</div>
<?php
}


/**
 * Utility function
 */
function memcat_is_hash( $hash, $key ) {
	while ( list( $k, $v ) = each( $hash ) )
		if ( $k == $key && $v ) return true;
	return false;
}

?>