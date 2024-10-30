<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Iconize_WP
 * @author    THATplugin <admin@thatplugin.com>
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://thatplugin.com/
 * @copyright 2021 THATplugin
 */

// If uninstall is not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! defined( 'ICONIZE_REMOVE_ALL_DATA' ) ) {
	exit;
}

if ( true !== ICONIZE_REMOVE_ALL_DATA ) {
	exit;
}

// Delete options added by Iconize plugin from database.
$iconize_options_to_delete = array( 'iconize_fonts', 'iconize_integrations', 'iconize_taxonomy_icons', 'iconize_plugin_version' );

foreach ( $iconize_options_to_delete as $iconize_option ) {
	delete_option( $iconize_option );
}

// Delete icon nav menu meta from database and remove icon properties from nav menu item object.
$menus = wp_get_nav_menus();

if ( $menus ) {

	foreach ( $menus as $key => $menu ) {

		$menu_items = wp_get_nav_menu_items( $menu );

		foreach ( $menu_items as $key => $menu_item ) {

			delete_post_meta( $menu_item->ID, '_menu_item_icon_name' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_set' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_transform' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_color' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_size' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_align' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_custom_classes' );
			delete_post_meta( $menu_item->ID, '_menu_item_icon_position' );
		}

		unset( $menu_items[ $key ]->icon_name );
		unset( $menu_items[ $key ]->icon_set );
		unset( $menu_items[ $key ]->icon_transform );
		unset( $menu_items[ $key ]->icon_color );
		unset( $menu_items[ $key ]->icon_size );
		unset( $menu_items[ $key ]->icon_align );
		unset( $menu_items[ $key ]->icon_custom_classes );
		unset( $menu_items[ $key ]->icon_position );
	}
}

// Delete icon settings from widgets options.
global $wp_registered_widgets;

if ( ! empty( $wp_registered_widgets ) ) {

	foreach ( $wp_registered_widgets as $widget_id => $widget ) {

		$widget_opt = get_option( $widget['callback'][0]->option_name );
		$widget_num = $widget['params'][0]['number'];

		if ( isset( $widget_opt[ $widget_num ] ) ) {

			unset( $widget_opt[ $widget_num ]['icon_name'] );
			unset( $widget_opt[ $widget_num ]['icon_set'] );
			unset( $widget_opt[ $widget_num ]['icon_transform'] );
			unset( $widget_opt[ $widget_num ]['icon_color'] );
			unset( $widget_opt[ $widget_num ]['icon_size'] );
			unset( $widget_opt[ $widget_num ]['icon_align'] );
			unset( $widget_opt[ $widget_num ]['icon_custom_classes'] );
			unset( $widget_opt[ $widget_num ]['icon_position'] );

			update_option( $widget['callback'][0]->option_name, $widget_opt );
		}
	}
}
