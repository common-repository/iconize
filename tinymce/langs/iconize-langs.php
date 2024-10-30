<?php
/**
 * TinyMCE language strings.
 *
 * @package   Iconize_WP
 * @author    THATplugin <admin@thatplugin.com>
 */

$strings = 'tinyMCE.addI18n( "' . _WP_Editors::$mce_locale . '.iconize_mce", {
	insert_icon_title: "' . esc_js( __( 'Insert/Edit icon', 'iconize' ) ) . '",
	swap_pos_title: "' . esc_js( __( 'Swap positions of stacked icons', 'iconize' ) ) . '",
	swap_size_title:"' . esc_js( __( 'Swap sizes of stacked icons', 'iconize' ) ) . '",
	remove_icon_title:"' . esc_js( __( 'Remove icon', 'iconize' ) ) . '"
} )';
