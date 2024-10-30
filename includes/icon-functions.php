<?php
/**
 * Iconize WordPress Plugin.
 *
 * Functions for retriving icons configured using backend interface.
 *
 * @package   Iconize_WP
 * @author    THATplugin <admin@thatplugin.com>
 * @license   license.txt
 * @link      https://thatplugin.com/
 * @copyright 2021 THATplugin
 */

/**
 * Function to return single icon HTML markup.
 *
 * @since 1.0.0
 *
 * @param array  $args Icon CSS classes and color.
 * @param string $icon_place String which will be used for additional icon class and for filters.
 * @param string $after HTML after icon.
 *
 * @return string  $icon_html or empty string on fail
 */
function iconize_get_icon( $args = '', $icon_place = '', $after = '&nbsp;&nbsp;' ) {

	$r = wp_parse_args(
		$args,
		array(
			'icon_set'            => '',
			'icon_name'           => '',
			'icon_transform'      => '',
			'icon_color'          => '',
			'icon_size'           => '',
			'icon_align'          => '',
			'icon_custom_classes' => '',
			'icon_position'       => '', // for menus, if position is "after" space will be before icon.
		)
	);

	$icon_place = empty( $icon_place ) || ! is_string( $icon_place ) ? 'single' : $icon_place;

	$after_icon_default = ! is_string( $after ) ? '&nbsp;&nbsp;' : $after;
	$after_icon_all     = apply_filters( 'iconize_after_icon', $after_icon_default );
	$after_icon         = apply_filters( "iconize_after_{$icon_place}_icon", $after_icon_all );

	$html_icon_tag = apply_filters( "iconize_{$icon_place}_icon_tag", 'span' );

	$icon_html = '';

	if ( ! empty( $r['icon_set'] ) && ! empty( $r['icon_name'] ) ) {

		$icon_color = ! empty( $r['icon_color'] ) ? ' style="color:' . esc_attr( $r['icon_color'] ) . ';"' : '';

		if ( 'after' === $r['icon_position'] ) {
			$icon_html .= $after_icon;
		}

		$icon_html .= '<' . $html_icon_tag . ' class="' . $icon_place . '-icon iconized ' . esc_attr( $r['icon_set'] ) . ' ' . esc_attr( $r['icon_name'] );

		if ( ! empty( $r['icon_transform'] ) ) {
			$icon_html .= ' ' . esc_attr( $r['icon_transform'] );
		}

		if ( ! empty( $r['icon_size'] ) ) {
			$icon_html .= ' ' . esc_attr( $r['icon_size'] );
		}

		if ( ! empty( $r['icon_align'] ) ) {

			$icon_html .= ' ' . esc_attr( $r['icon_align'] );
			$after_icon = '';
		}

		if ( ! empty( $r['icon_custom_classes'] ) ) {

			$icon_custom_classes = str_replace( ',', ' ', $r['icon_custom_classes'] );
			$icon_html          .= ' ' . esc_attr( $icon_custom_classes );
		}

		$icon_html .= '" aria-hidden="true"';
		$icon_html .= $icon_color;
		$icon_html .= '></' . $html_icon_tag . '>';

		if ( 'after' !== $r['icon_position'] ) {
			$icon_html .= $after_icon;
		}
	}

	return $icon_html;
}


/**
 * Function for retriving taxonomy icons configured using taxonomy edit screens
 *
 * @since   1.0.0
 *
 * @param string     $field Either 'slug', 'name' or 'id' (term_id).
 * @param string|int $value Search for this term value.
 * @param string     $taxonomy Taxonomy Name.
 * @param boolean    $format return html markup or array? Either 'html' or 'array'.
 * @param string     $after_icon Markup placed after icon.
 *
 * @return string  $icon_html, array of settings or empty string on failure
 */
function iconize_get_term_icon_by( $field = 'id', $value, $taxonomy, $format = 'html', $after_icon = '&nbsp;&nbsp;' ) {

	$fields = array( 'name', 'slug', 'id' );

	$after = ! is_string( $after_icon ) ? '&nbsp;&nbsp;' : $after_icon;

	$format = ! is_string( $format ) ? 'html' : $format;

	if ( ! taxonomy_exists( $taxonomy ) || ! in_array( $field, $fields, true ) ) {
		return '';
	}

	// get term object.
	$term = get_term_by( $field, $value, $taxonomy );

	if ( ! $term || is_wp_error( $term ) ) {

		return '';
	}

	$term_id = $term->term_id;

	$icon_args = array();

	$opt_array = get_option( 'iconize_taxonomy_icons' );

	if ( $opt_array && array_key_exists( $taxonomy, $opt_array ) && array_key_exists( $term_id, $opt_array[ $taxonomy ] ) ) {

		$icon_args['icon_name']           = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_name'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_name'] : '';
		$icon_args['icon_set']            = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_set'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_set'] : '';
		$icon_args['icon_transform']      = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_transform'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_transform'] : '';
		$icon_args['icon_size']           = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_size'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_size'] : '';
		$icon_args['icon_align']          = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_align'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_align'] : '';
		$icon_args['icon_custom_classes'] = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_custom_classes'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_custom_classes'] : '';
		$icon_args['icon_color']          = ( isset( $opt_array[ $taxonomy ][ $term_id ]['icon_color'] ) ) ? $opt_array[ $taxonomy ][ $term_id ]['icon_color'] : '';
	}

	if ( 'array' === $format ) {

		return $icon_args;

	} else {

		return iconize_get_icon( $icon_args, $taxonomy, $after );
	}
}
