<?php
/**
 * Plugin Name: Iconize
 * Description: Quickly and easily iconize posts, pages, menu items, widget titles, categories, tags, custom taxonomies,...
 * Version:     1.2.4
 * Author:      THATplugin
 * Author URI:  https://thatplugin.com/
 * Text Domain: iconize
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /lang
 *
 * @package   Iconize_WP
 * @author    THATplugin <admin@thatplugin.com>
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://thatplugin.com/
 * @copyright 2021 THATplugin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

defined( 'ICONIZE_PLUGIN_ROOT' ) || define( 'ICONIZE_PLUGIN_ROOT', dirname( __FILE__ ) );
defined( 'ICONIZE_PLUGIN_PATH' ) || define( 'ICONIZE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
defined( 'ICONIZE_PLUGIN_URI' ) || define( 'ICONIZE_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
defined( 'ICONIZE_PLUGIN_VERSION' ) || define( 'ICONIZE_PLUGIN_VERSION', '1.2.4' );


/**
 * Load classes.
 */

// Main plugin class.
require_once ICONIZE_PLUGIN_PATH . 'class-iconize-wp.php';

// Custom Walker classes for nav menu system.
require_once ICONIZE_PLUGIN_PATH . 'includes/menu-walkers/class-iconize-walker-nav-menu-edit.php';
require_once ICONIZE_PLUGIN_PATH . 'includes/menu-walkers/class-iconize-walker-nav-menu.php';

// Custom Walker classes for taxonomy lists.
require_once ICONIZE_PLUGIN_PATH . 'includes/class-iconize-walker-category.php';

// Custom Widgets.
require_once ICONIZE_PLUGIN_PATH . 'includes/widgets/class-iconize-widget-taxonomies.php';

// Load files with functions.
require_once ICONIZE_PLUGIN_PATH . 'includes/icon-functions.php';

add_action( 'plugins_loaded', array( 'Iconize_WP', 'get_instance' ) );
