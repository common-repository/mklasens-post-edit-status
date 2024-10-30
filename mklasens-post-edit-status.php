<?php
defined( 'ABSPATH' ) or die( 'You can\'t access this file directly!');
/**
 * Plugin Name: mklasen's Post Edit Status
 * Plugin URI: https://mklasen.com
 * Description:
 * Version: 1.0
 * Author: Marinus Klasen
 * Author URI: http://mklasen.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html


 Copyright 2015  Marinus Klasen  (email : marinus@mklasen.nl)

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

/* **************************
#
#  Main function for checking the edit status of a post
#  - Inspired by wp_check_post_lock from includes/post.php
#  - The wp_check_post_lock cannot be used directly on the front-end (somehow).
#
*************************** */
function mk_check_edit_in_progress($content) {
  if (!is_single())
    return $content;
  if ( !$lock = get_post_meta( get_the_ID(), '_edit_lock', true ) )
    return false;

  $lock = explode( ':', $lock );
  $time = $lock[0];
  $user_id = isset( $lock[1] ) ? $lock[1] : get_post_meta( $post->ID, '_edit_last', true );
  $time_window = apply_filters( 'wp_check_post_lock_window', AUTOSAVE_INTERVAL * 2 );

  if ( $time && $time > time() - $time_window && $user_id != get_current_user_id() ) {
    $user = get_userdata($user_id);
    $text = 'is currently editing this post';
    $admin_defined = get_option( 'mklasens-post-edit-status-settings' );
    if (isset($admin_defined['display_text']))
      $text = $admin_defined['display_text'];
    $prepend = '<span class="mk-post-locked"><i>' . $user->user_nicename . ' ' . $text . '.</i></span>';
    return $prepend.$content;
  }
  return $content;
}
add_filter('the_content', 'mk_check_edit_in_progress');

/* **************************
#
#  Admin Settings
#  - For setting the text (is currently editing)
#
*************************** */
function mklasens_post_edit_status_menu() {
  add_options_page( __('Post Edit Status', 'mklasens-post-edit-status' ), __('Post Edit Status', 'mklasens-post-edit-status' ), 'manage_options', 'mklasens-post-edit-status', 'mklasens_post_edit_status_options_page' );
}
add_action( 'admin_menu', 'mklasens_post_edit_status_menu' );

function mklasens_post_edit_status_init() {
	register_setting( 'mklasens-post-edit-status-group', 'mklasens-post-edit-status-settings' );
	add_settings_section( 'section-general', __( 'General Settings', 'mklasens-post-edit-status' ), 'section_general_callback', 'mklasens-post-edit-status' );
	add_settings_field( 'display-text', __( 'Display Text', 'mklasens-post-edit-status' ), 'display_text_callback', 'mklasens-post-edit-status', 'section-general' );
}
add_action( 'admin_init', 'mklasens_post_edit_status_init' );

function mklasens_post_edit_status_options_page() {
  ?>
    <div class="wrap">
        <h2><?php _e('mklasens\'s Post Edit Status', 'mklasens-post-edit-status'); ?></h2>
        <form action="options.php" method="POST">
          <?php settings_fields('mklasens-post-edit-status-group'); ?>
          <?php do_settings_sections('mklasens-post-edit-status'); ?>
          <?php submit_button(); ?>
        </form>
    </div>
  <?php
}
function section_general_callback() {
	_e( 'The plugin only uses one field. What text to display after the username? <i>( is currently editing this post)</i>', 'mklasens-post-edit-status' );
}

function display_text_callback() {
	$settings = (array) get_option( 'mklasens-post-edit-status-settings' );
	$field = "display_text";
  $value = false;
  if (isset($settings[$field]))
	 $value = esc_attr( $settings[$field] );

	echo "<input type='text' placeholder='is currently editing this post' name='mklasens-post-edit-status-settings[$field]' value='$value' />";
}
