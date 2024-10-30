<?php
/**
 * Iconized Taxonomies Widget
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 * @since 1.0.0
 */

/**
 * Iconize_Widget_Taxonomies class
 *
 * @since 1.0.0
 */
class Iconize_Widget_Taxonomies extends WP_Widget {

	/**
	 * Construct
	 */
	public function __construct() {

		$widget_ops = array(
			'classname'             => 'iconize_widget_taxonomies',
			'description'           => __( 'Display iconized taxonomy terms ( Categories, Tags, custom taxonomies ).', 'iconize' ),
			'show_instance_in_rest' => true,
		);
		parent::__construct( 'iconize_taxonomy', __( 'Iconized Taxonomies', 'iconize' ), $widget_ops );
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {

		$current_taxonomy = $this->get_current_taxonomy( $instance );

		if ( ! empty( $instance['title'] ) ) {

			$title = $instance['title'];

		} else {

			if ( 'post_tag' === $current_taxonomy ) {

				$title = __( 'Tags', 'iconize' );

			} else {

				$tax   = get_taxonomy( $current_taxonomy );
				$title = $tax->labels->name;
			}
		}

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$style              = isset( $instance['style'] ) ? $instance['style'] : 'list';
		$count              = ! empty( $instance['count'] ) ? '1' : '0';
		$hierarchical       = ! empty( $instance['hierarchical'] ) ? '1' : '0';
		$behavior           = isset( $instance['behavior'] ) ? $instance['behavior'] : 'no_fallback';
		$override_color     = ! empty( $instance['override_color'] ) ? true : false;
		$color              = $override_color ? $instance['color'] : 'default';
		$hover_effect       = isset( $instance['hover_effect'] ) ? $instance['hover_effect'] : 'default';
		$hover_color_change = ! empty( $instance['hover_color_change'] ) ? true : false;

		// Take falback icon if needed.
		$fallback_icon = array();
		if ( 'fallback' === $behavior || 'override' === $behavior ) {

			$fallback_icon['icon_name']           = $instance['fallback_icon_name'];
			$fallback_icon['icon_set']            = $instance['fallback_icon_set'];
			$fallback_icon['icon_transform']      = $instance['fallback_icon_transform'];
			$fallback_icon['icon_color']          = $instance['fallback_icon_color'];
			$fallback_icon['icon_size']           = $instance['fallback_icon_size'];
			$fallback_icon['icon_align']          = $instance['fallback_icon_align'];
			$fallback_icon['icon_custom_classes'] = $instance['fallback_icon_custom_classes'];
		}

		// Output.
		// Add class of default WP category/tag cloud widget ( try to match theme styles ).
		if ( 'list' === $style || 'iconize_list' === $style ) {

			$before_widget = preg_replace( '/class="/', 'class="widget_categories ', $args['before_widget'], 1 );

		} else {

			$before_widget = preg_replace( '/class="/', 'class="widget_tag_cloud ', $args['before_widget'], 1 );
		}

		echo wp_kses_post( $before_widget );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		// Generate 'iconized' array which will be passed to "wp_list_categories()" or "wp_tag_cloud()" as extra argument.
		$iconized_args = array(
			'hover_effect'       => $hover_effect,
			'color'              => $color,
			'hover_color_change' => $hover_color_change,
			'fallback_icon'      => $fallback_icon,
		);

		if ( 'override' === $behavior ) {
			$iconized_args['override_icons'] = true;
		}

		// Arguments for "wp_list_categories()".
		$list_args = array(
			'taxonomy'     => $current_taxonomy,
			'orderby'      => 'name',
			'show_count'   => $count,
			'hierarchical' => $hierarchical,
			'iconized'     => $iconized_args, // extra arg.
		);

		// Arguments for "wp_tag_cloud()".
		$cloud_args = array(
			'taxonomy' => $current_taxonomy,
			'iconized' => $iconized_args, // extra arg.
		);

		// Display list/tag cloud.
		if ( 'list' === $style || 'iconize_list' === $style ) {

			$list_args['title_li'] = '';

			// Should plugin style the list or not ( default - theme/custom styles, iconize - plugin styles ).
			$list_args['iconized']['style']      = ( 'list' === $style ) ? 'default' : 'iconize';
			$list_args['iconized']['after_icon'] = ( 'list' === $style ) ? '&nbsp;&nbsp;' : '';

			$list_class = ( 'list' === $style ) ? '' : ' class="iconized-ul"';
			?>
			<ul<?php echo wp_kses_post( $list_class ); ?>>
				<?php wp_list_categories( apply_filters( 'widget_iconize_taxonomies_list_args', $list_args ) ); ?>
			</ul>
			<?php

		} else {

			// Should plugin style the tagcloud or not ( default - theme/custom styles, iconize - plugin styles ).
			$cloud_args['iconized']['style']      = ( 'tag_cloud' === $style ) ? 'default' : 'iconize';
			$cloud_args['iconized']['after_icon'] = ( 'tag_cloud' === $style ) ? '&nbsp;' : '';
			?>
			<div class="tagcloud">
				<?php wp_tag_cloud( apply_filters( 'widget_iconize_taxonomies_tag_cloud_args', $cloud_args ) ); ?>
			</div>
			<?php
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * This function should check that `$new_instance` is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If false is returned, the instance won't be
	 * saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title']              = wp_strip_all_tags( $new_instance['title'] );
		$instance['taxonomy']           = stripslashes( $new_instance['taxonomy'] );
		$instance['style']              = stripslashes( $new_instance['style'] );
		$instance['behavior']           = stripslashes( $new_instance['behavior'] );
		$instance['count']              = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['hierarchical']       = ! empty( $new_instance['hierarchical'] ) ? 1 : 0;
		$instance['override_color']     = ! empty( $new_instance['override_color'] ) ? 1 : 0;
		$instance['color']              = $new_instance['color'];
		$instance['hover_effect']       = stripslashes( $new_instance['hover_effect'] );
		$instance['hover_color_change'] = ! empty( $new_instance['hover_color_change'] ) ? 1 : 0;

		$instance['fallback_icon_name']           = $new_instance['fallback_icon_name'];
		$instance['fallback_icon_set']            = $new_instance['fallback_icon_set'];
		$instance['fallback_icon_transform']      = $new_instance['fallback_icon_transform'];
		$instance['fallback_icon_color']          = $new_instance['fallback_icon_color'];
		$instance['fallback_icon_size']           = $new_instance['fallback_icon_size'];
		$instance['fallback_icon_align']          = $new_instance['fallback_icon_align'];
		$instance['fallback_icon_custom_classes'] = $new_instance['fallback_icon_custom_classes'];

		return $instance;
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {

		$current_taxonomy = $this->get_current_taxonomy( $instance );

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'                        => '',
				'style'                        => 'list',
				'behavior'                     => 'no_fallback',
				'color'                        => '',
				'hover_effect'                 => '',
				'fallback_icon_name'           => '',
				'fallback_icon_set'            => '',
				'fallback_icon_transform'      => '',
				'fallback_icon_color'          => '',
				'fallback_icon_size'           => '',
				'fallback_icon_align'          => '',
				'fallback_icon_custom_classes' => '',
			)
		);

		$title              = esc_attr( $instance['title'] );
		$count              = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$hierarchical       = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
		$override_color     = isset( $instance['override_color'] ) ? (bool) $instance['override_color'] : false;
		$hover_color_change = isset( $instance['hover_color_change'] ) ? (bool) $instance['hover_color_change'] : false;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'iconize' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'taxonomy' ) ); ?>"><?php esc_html_e( 'Taxonomy:', 'iconize' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'taxonomy' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'taxonomy' ) ); ?>">
			<?php
			foreach ( get_taxonomies() as $taxonomy ) {

				$tax = get_taxonomy( $taxonomy );

				$tax_support       = $this->get_iconize_taxonomy_support( $tax->name );
				$tax_icons_enabled = is_array( $tax_support ) ? $tax_support['enabled'] : false;

				if ( ! $tax->show_tagcloud || empty( $tax->labels->name ) || ! $tax_icons_enabled ) {

					continue;
				}
				?>
				<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $taxonomy, $current_taxonomy ); ?>><?php echo esc_html( $tax->labels->name ); ?></option>
				<?php
			}
			?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_html_e( 'Widget style:', 'iconize' ); ?></label>
			<select class="widefat iconize-mother-select" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
				<option value="list"<?php selected( $instance['style'], 'list' ); ?>><?php esc_html_e( 'List ( Theme Styles )', 'iconize' ); ?></option>
				<option value="iconize_list"<?php selected( $instance['style'], 'iconize_list' ); ?>><?php esc_html_e( 'List ( Iconize Styles )', 'iconize' ); ?></option>
				<option value="tag_cloud"<?php selected( $instance['style'], 'tag_cloud' ); ?>><?php esc_html_e( 'Cloud ( Theme Styles )', 'iconize' ); ?></option>
				<option value="iconize_tag_cloud"<?php selected( $instance['style'], 'iconize_tag_cloud' ); ?>><?php esc_html_e( 'Cloud ( Iconize Styles )', 'iconize' ); ?></option>
			</select>
		</p>

		<p class="mother-opt-<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?> mother-val-list mother-val-iconize_list">
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"<?php checked( $count ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Show post counts', 'iconize' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hierarchical' ) ); ?>"<?php checked( $hierarchical ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'hierarchical' ) ); ?>"><?php esc_html_e( 'Show hierarchy', 'iconize' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'behavior' ) ); ?>"><?php esc_html_e( "If term doesn't have icon assigned:", 'iconize' ); ?></label>
			<select class="widefat iconize-mother-select" id="<?php echo esc_attr( $this->get_field_id( 'behavior' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'behavior' ) ); ?>">
				<option value="no_fallback"<?php selected( $instance['behavior'], 'no_fallback' ); ?>><?php esc_html_e( 'Leave it as is', 'iconize' ); ?></option>
				<option value="fallback"<?php selected( $instance['behavior'], 'fallback' ); ?>><?php esc_html_e( 'Insert default icon ( select below )', 'iconize' ); ?></option>
				<option value="override"<?php selected( $instance['behavior'], 'override' ); ?>><?php esc_html_e( 'Override all icons with default icon ( select below )', 'iconize' ); ?></option>
			</select>
		</p>

		<p class="mother-opt-<?php echo esc_attr( $this->get_field_id( 'behavior' ) ); ?> mother-val-fallback mother-val-override">
			<label class="preview-icon-label">
				<?php esc_html_e( 'Default icon:', 'iconize' ); ?>
				<button type="button" class="preview-icon button iconized-hover-trigger"><span class="iconized <?php echo esc_attr( $instance['fallback_icon_name'] ); ?> <?php echo esc_attr( $instance['fallback_icon_set'] ); ?> <?php echo esc_attr( $instance['fallback_icon_transform'] ); ?>"></span></button>
			</label>
			<span>
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_name' ) ); ?>" class="iconize-input-name" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_name' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_name'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_set' ) ); ?>" class="iconize-input-set" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_set' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_set'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_transform' ) ); ?>" class="iconize-input-transform" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_transform' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_transform'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_color' ) ); ?>" class="iconize-input-color" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_color' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_color'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_size' ) ); ?>" class="iconize-input-size" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_size' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_size'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_align' ) ); ?>" class="iconize-input-align" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_align' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_align'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'fallback_icon_custom_classes' ) ); ?>" class="iconize-input-custom-classes" name="<?php echo esc_attr( $this->get_field_name( 'fallback_icon_custom_classes' ) ); ?>" value="<?php echo esc_attr( $instance['fallback_icon_custom_classes'] ); ?>">
			</span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hover_effect' ) ); ?>"><?php esc_html_e( 'Keep/Disable/Override icon effects:', 'iconize' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hover_effect' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hover_effect' ) ); ?>">
				<option value="default"<?php selected( $instance['hover_effect'], 'default' ); ?>><?php esc_html_e( 'Keep existing effects', 'iconize' ); ?></option>
				<option value=""<?php selected( $instance['hover_effect'], '' ); ?>><?php esc_html_e( 'Remove effects', 'iconize' ); ?></option>
				<?php
				$transforms = $this->get_iconize_effects( 'transform' );
				$animations = $this->get_iconize_effects( 'animate' );
				$hovers     = $this->get_iconize_effects( 'hover' );

				// Transformations.
				if ( ! empty( $transforms ) ) :
					?>
					<optgroup label="<?php esc_html_e( 'Transformation', 'iconize' ); ?>">
						<?php
						foreach ( $transforms as $class => $label ) :
							?>
							<option value="<?php echo esc_attr( $class ); ?>" <?php selected( $instance['hover_effect'], $class ); ?>><?php echo esc_html( $label ); ?></option>
							<?php
						endforeach;
						?>
					</optgroup>
					<?php
				endif;
				// Animations.
				if ( ! empty( $animations ) ) :
					?>
					<optgroup label="<?php esc_html_e( 'Animation', 'iconize' ); ?>">
						<?php
						foreach ( $animations as $class => $label ) :
							?>
							<option value="<?php echo esc_attr( $class ); ?>" <?php selected( $instance['hover_effect'], $class ); ?>><?php echo esc_html( $label ); ?></option>
							<?php
						endforeach;
						?>
					</optgroup>
					<?php
				endif;
				// Hover Effects.
				if ( ! empty( $hovers ) ) :
					?>
					<optgroup label="<?php esc_html_e( 'Hover Effect', 'iconize' ); ?>">
						<?php
						foreach ( $hovers as $class => $label ) :
							?>
							<option value="<?php echo esc_attr( $class ); ?>" <?php selected( $instance['hover_effect'], $class ); ?>><?php echo esc_html( $label ); ?></option>
							<?php
						endforeach;
						?>
					</optgroup>
					<?php
				endif;
				?>
			</select>
		</p>

		<p>
			<input type="checkbox" class="checkbox iconize-mother-checkbox" id="<?php echo esc_attr( $this->get_field_id( 'override_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'override_color' ) ); ?>"<?php checked( $override_color ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'override_color' ) ); ?>"><?php esc_html_e( 'Remove/Override icon colors?', 'iconize' ); ?></label><br />
		</p>

		<p class="mother-checkbox-<?php echo esc_attr( $this->get_field_id( 'override_color' ) ); ?>">
			<input type="text" class="iconize-color-picker" id="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'color' ) ); ?>" value="<?php echo esc_attr( $instance['color'] ); ?>" />
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hover_color_change' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hover_color_change' ) ); ?>"<?php checked( $hover_color_change ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'hover_color_change' ) ); ?>"><?php esc_html_e( 'Every icon inherits link color on hover?', 'iconize' ); ?></label><br />
		</p>
		<?php
	}

	/**
	 * Check if Iconize is enabled on taxonomy
	 *
	 * @param  string $tax Taxonomy.
	 */
	public function get_iconize_taxonomy_support( $tax ) {

		$plugin = Iconize_WP::get_instance();

		return $plugin->get_iconize_support_for( 'taxonomy_' . $tax );
	}

	/**
	 * Get Current Taxonomy
	 *
	 * @param  array $instance Current settings.
	 * @return string
	 */
	public function get_current_taxonomy( $instance ) {

		if ( ! empty( $instance['taxonomy'] ) && taxonomy_exists( $instance['taxonomy'] ) ) {
			return $instance['taxonomy'];
		}
	}

	/**
	 * Get dropdown options for transforms, animations or hover effects.
	 *
	 * @param string $type Type of options.
	 */
	public function get_iconize_effects( $type ) {

		$plugin = Iconize_WP::get_instance();

		return $plugin->get_iconize_dialog_dropdown_options_for( $type );
	}
}
