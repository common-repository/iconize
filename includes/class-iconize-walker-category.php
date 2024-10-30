<?php
/**
 * Iconize_Walker_Category class for WordPress 3.6+.
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 * @since 1.0.0
 */

/**
 * Iconize_Walker_Category class
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 * @since 1.0.0
 * @uses Walker_Category
 */
class Iconize_Walker_Category extends Walker_Category {

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 1.0.0
	 *
	 * @uses iconize_get_term_icon_by()
	 * @uses iconize_get_icon()
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int    $depth    Depth of category in reference to parents. Default 0.
	 * @param array  $args     An array of arguments. @see wp_list_categories().
	 * @param int    $id       ID of the current category.
	 */
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

		extract( $args );

		// Retrive an array of settings for term icon configured in term edit screen if there is icon.
		$icon = iconize_get_term_icon_by( 'id', $category->term_id, $category->taxonomy );

		$term_icon_args = array();
		if ( ! empty( $icon ) ) {
			$term_icon_args = iconize_get_term_icon_by( 'id', $category->term_id, $category->taxonomy, 'array' );
		}

		// Validate custom settings passed with 'iconize' arg to wp_list_categories().
		$hover_effect         = ( isset( $iconized['hover_effect'] ) ) ? (string) $iconized['hover_effect'] : 'default';
		$color                = ( isset( $iconized['color'] ) ) ? (string) $iconized['color'] : 'default';
		$hover_effect_trigger = ( isset( $iconized['hover_effect_trigger'] ) ) ? (string) $iconized['hover_effect_trigger'] : 'link';
		$hover_color_change   = ( isset( $iconized['hover_color_change'] ) ) ? (bool) $iconized['hover_color_change'] : false;
		$fallback_icon_args   = ( isset( $iconized['fallback_icon'] ) ) ? (array) $iconized['fallback_icon'] : array();
		$override_icons       = ( isset( $iconized['override_icons'] ) ) ? (bool) $iconized['override_icons'] : false;
		$style                = ( isset( $iconized['style'] ) ) ? (string) $iconized['style'] : 'default';
		$after_icon           = ( isset( $iconized['after_icon'] ) ) ? (string) $iconized['after_icon'] : '&nbsp;&nbsp;';

		// Determine which icon to display.
		if ( true === $override_icons ) {

			$icon_args = $fallback_icon_args;

		} else {

			$icon_args = $term_icon_args;

			if ( empty( $icon_args ) && ! empty( $fallback_icon_args ) ) {
				$icon_args = $fallback_icon_args;
			}
		}

		// Modify icon args if needed.
		if ( ! empty( $icon_args ) ) {

			if ( 'iconize' === $style ) {
				$icon_args['icon_custom_classes'] .= ( ! empty( $icon_args['icon_custom_classes'] ) ) ? ',iconized-li' : 'iconized-li';
			}

			if ( true === $hover_color_change && false === strpos( $icon_args['icon_custom_classes'], 'hover-color-change' ) ) {
				$icon_args['icon_custom_classes'] .= ( ! empty( $icon_args['icon_custom_classes'] ) ) ? ',hover-color-change' : 'hover-color-change';
			}

			// Override effect and color if needed.
			if ( 'default' !== $hover_effect ) {
				$icon_args['icon_transform'] = $hover_effect;
			}

			if ( 'default' !== $color ) {
				$icon_args['icon_color'] = $color;
			}
		}

		// Generate icon html.
		$icon_html = iconize_get_icon( $icon_args, $category->taxonomy, $after_icon );

		// Generate iconized link.
		$cat_name = esc_attr( $category->name );
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );

		$link = '<a href="' . esc_url( get_term_link( $category ) ) . '" ';

		if ( 0 === $use_desc_for_title || empty( $category->description ) ) {

			/* translators: %s: category name */
			$link .= 'title="' . esc_attr( sprintf( __( 'View all posts filed under %s', 'iconize' ), $cat_name ) ) . '"';

		} else {

			$link .= 'title="' . esc_attr( wp_strip_all_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
		}

		// Add hover effect class to link if needed.
		if ( 'link' === $hover_effect_trigger && ! empty( $icon_html ) && ! empty( $icon_args['icon_transform'] ) && ! empty( $hover_effect ) ) {
			$link .= ' class="iconized-hover-trigger"';
		}

		$link .= '>';

		$link .= $icon_html;
		$link .= $cat_name . '</a>';

		// The rest.
		if ( ! empty( $feed_image ) || ! empty( $feed ) ) {

			$link .= ' ';

			if ( empty( $feed_image ) ) {

				$link .= '(';
			}

			$link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) ) . '"';

			if ( empty( $feed ) ) {

				/* translators: %s: category name */
				$alt = ' alt="' . sprintf( __( 'Feed for all posts filed under %s', 'iconize' ), $cat_name ) . '"';

			} else {

				$title = ' title="' . $feed . '"';
				$alt   = ' alt="' . $feed . '"';
				$name  = $feed;
				$link .= $title;
			}

			$link .= '>';

			if ( empty( $feed_image ) ) {

				$link .= $name;

			} else {

				$link .= "<img src='$feed_image'$alt$title" . ' />';
			}

			$link .= '</a>';

			if ( empty( $feed_image ) ) {
				$link .= ')';
			}
		}

		if ( ! empty( $show_count ) ) {
			$link .= ' (' . intval( $category->count ) . ')';
		}

		if ( 'list' === $args['style'] ) {

			$output .= "\t<li";
			$class   = 'cat-item cat-item-' . $category->term_id;

			if ( ! empty( $current_category ) ) {

				$_current_category = get_term( $current_category, $category->taxonomy );

				if ( (int) $category->term_id === (int) $current_category ) {

					$class .= ' current-cat';

				} elseif ( (int) $category->term_id === (int) $_current_category->parent ) {

					$class .= ' current-cat-parent';
				}
			}

			$output .= ' class="' . $class . '"';
			$output .= ">$link\n";

		} else {

			$output .= "\t$link<br />\n";
		}
	}
}
