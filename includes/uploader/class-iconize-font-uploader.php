<?php
/**
 * Iconize_Font_Uploader
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 * @since 1.2.2
 */

/**
 * Iconize_Font_Uploader class
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 */
class Iconize_Font_Uploader {

	/**
	 * WP_Filesystem
	 *
	 * @var $wp_filesystem
	 */
	private $wp_filesystem;

	/**
	 * Path upload folder
	 *
	 * @var $upload_dir
	 */
	private $upload_dir;

	/**
	 * URL upload folder
	 *
	 * @var $upload_url
	 */
	private $upload_url;

	/**
	 * Uploads dir folder name
	 *
	 * @var $folder_name
	 */
	private $folder_name;

	/**
	 * Get things going
	 */
	public function __construct() {

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$this->wp_filesystem = $wp_filesystem;

		$upload = wp_upload_dir();

		$this->folder_name = apply_filters( 'iconize_uploads_folder', 'iconize_fonts' );
		$this->upload_dir  = $upload['basedir'] . '/' . $this->folder_name;
		$this->upload_url  = $upload['baseurl'] . '/' . $this->folder_name;

		// SSL fix because WordPress core function wp_upload_dir() doesn't check protocol.
		if ( is_ssl() ) {
			$this->upload_url = str_replace( 'http://', 'https://', $this->upload_url );
		}

		// add admin styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		$action = $this->get_request( 'action' );

		// ajax events.
		if ( ! empty( $action ) && is_callable( array( $this, $action ) ) ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}

		add_action( 'iconize_after_custom_fonts_form', array( $this, 'add_upload_field' ) );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function admin_enqueue_scripts() {

		wp_enqueue_style( 'iconize-settings', ICONIZE_PLUGIN_URI . 'css/iconize-settings.min.css', array(), ICONIZE_PLUGIN_VERSION );

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'iconize-settings', ICONIZE_PLUGIN_URI . "js/iconize-settings$suffix.js", array( 'jquery' ), ICONIZE_PLUGIN_VERSION, true );
		$args = array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'exist'         => __( "This font file already exists. Make sure you're giving it a unique name!", 'iconize' ),
			'failedopen'    => __( 'Failed to open the ZIP archive. If you uploaded a valid Fontello ZIP file, your host may be blocking this PHP function. Please get in touch with them.', 'iconize' ),
			'failedextract' => __( 'Failed to extract the ZIP archive. Your host may be blocking this PHP function. Please get in touch with them.', 'iconize' ),
			'emptyfile'     => __( 'Your browser failed to upload the file. Please try again.', 'iconize' ),
			'updatefailed'  => __( 'Plugin failed to update the WP options table.', 'iconize' ),
			'delete'        => __( 'Are you sure you want to delete this font?', 'iconize' ),
			'deletefailed'  => __( 'Plugin failed to delete font.', 'iconize' ),
		);
		wp_localize_script( 'iconize-settings', 'iconizeOptionsParams', $args );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function add_upload_field() {
		?>
		<div class="iconize-upload-progress">
			<div class="bar"></div>
		</div>
		<table class="form-table iconize-upload-option-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Upload Fontello Zip:', 'iconize' ); ?></th>
					<td class="iconize-upload-field">
						<input id="iconize-upload-files" name="files[]" type="file" accept=".zip" />
						<?php wp_nonce_field( 'iconize_icons_nonce' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get uploaded font package's config file
	 *
	 * @param string $file_name File name.
	 */
	public function get_config_font( $file_name ) {

		if ( ! is_dir( $this->upload_dir . '/' . $file_name ) ) {
			return false;
		}

		$file_config = glob( $this->upload_dir . '/' . $file_name . '/*/*' );
		$data        = array();
		$css_folder  = '';

		foreach ( $file_config as $key => $file ) {

			if ( strpos( $file, 'config.json' ) !== false ) {
				$file_info               = json_decode( file_get_contents( $file ) );
				$data['name']            = trim( $file_info->name );
				$data['css_prefix_text'] = $file_info->css_prefix_text;
			}

			if ( is_string( $file ) && strpos( $file, 'css' ) !== false ) {
				$file_part          = explode( $this->folder_name . '/', $file );
				$data['css_folder'] = $file;
				$css_folder         = $file_part[1];
			}

			if ( is_string( $file ) && strpos( $file, 'font' ) !== false ) {
				$file_part        = explode( $this->folder_name . '/', $file );
				$data['font_url'] = $file_part[1];
			}
		}

		if ( empty( $data['name'] ) ) {
			$noname            = explode( '.', $file_name );
			$data['name']      = $noname[0];
			$data['nameempty'] = true;
			$data['css_root']  = $data['css_folder'] . '/fontello.css';
			$data['css_url']   = $this->upload_url . '/' . $css_folder . '/fontello.css';

		} else {
			$data['css_root'] = $data['css_folder'] . '/' . $data['name'] . '.css';
			$data['css_url']  = $this->upload_url . '/' . $css_folder . '/' . $data['name'] . '.css';
		}

		$data['json_url']  = $this->upload_url . '/' . $file_name . '/' . $data['name'] . '.json';
		$data['file_name'] = $file_name;

		return $data;
	}

	/**
	 * Parse CSS file to get proper icon names
	 *
	 * @param string $css_file CSS file path.
	 * @param string $name Name.
	 * @param string $url Url.
	 */
	protected function parse_css( $css_file, $name, $url ) {

		$css_source = @file_get_contents( $css_file );

		if ( $css_source === false ) {
			$response = wp_remote_get( $url );

			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$css_source = $response['body'];
			} else {
				return null;
			}
		}

		$icons = array();
		preg_match_all( "/\.\w*?\-(.*?):\w*?\s*?{?\s*?{\s*?\w*?:\s*?\'\\\\?(\w*?)\'.*?}/", $css_source, $matches, PREG_SET_ORDER, 0 );
		foreach ( $matches as $match ) {
			$icons[ $match[1] ] = $match[2];
		}

		return $icons;
	}

	/**
	 * Remove folder (recursive)
	 *
	 * @param string $dir Directory.
	 */
	protected function rrmdir( $dir ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object != '.' && $object != '..' ) {
					if ( is_dir( $dir . '/' . $object ) ) {
						$this->rrmdir( $dir . '/' . $object );
					} else {
						unlink( $dir . '/' . $object );
					}
				}
			}
			rmdir( $dir );
		}
	}

	/**
	 * Get reguest param
	 *
	 * @param string $name Name.
	 * @param bool   $default Default.
	 * @param string $type Tupe.
	 */
	protected function get_request( $name, $default = false, $type = 'POST' ) {

		$TYPE = 'post' === strtolower( $type ) ? $_POST : $_GET;
		if ( ! empty( $TYPE[ $name ] ) ) {
			return sanitize_text_field( $TYPE[ $name ] );
		}

		return $default;
	}

	/**
	 * Generate JSON files
	 *
	 * @param array $font_data Font data.
	 * @param array $icons Icons data.
	 */
	private function generate_json( $font_data, $icons ) {

		if ( empty( $font_data ) ) {
			return false;
		}

		if ( ! empty( $icons ) && is_array( $icons ) ) {

			$json          = array();
			$json['icons'] = array();
			$icons_charmap = array();

			foreach ( $icons as $name_icon => $code ) {
				$json['icons'][]             = $name_icon;
				$icons_charmap[ $name_icon ] = '&#x' . $code . ';';
			}

			if ( is_dir( $this->upload_dir ) ) {
				$created_json_1 = file_put_contents( $this->upload_dir . '/' . $font_data['file_name'] . '/' . $font_data['name'] . '.json', wp_json_encode( $json ) );
				$created_json_2 = file_put_contents( $this->upload_dir . '/' . $font_data['file_name'] . '/' . $font_data['name'] . '-charmap.json', wp_json_encode( $icons_charmap ) );
				return (bool) $created_json_1 && (bool) $created_json_2;
			}
		}

		return false;
	}

	/**
	 * Generate CSS files
	 *
	 * @param array $font_data Font data.
	 * @param array $icons Icons data.
	 */
	private function generate_css( $font_data, $icons ) {

		if ( empty( $font_data ) ) {
			return false;
		}

		$css_content  = '';
		$fontfilename = isset( $font_data['nameempty'] ) && true === $font_data['nameempty'] ? 'fontello' : strtolower( $font_data['name'] );
		$randomver    = wp_rand();

		$css_content .= "@font-face {
					font-family: 'iconize" . $fontfilename . "';
					src: url('../" . $font_data['font_url'] . '/' . $fontfilename . '.eot?' . $randomver . "');
					src: url('../" . $font_data['font_url'] . '/' . $fontfilename . '.eot?' . $randomver . "#iefix') format('embedded-opentype'),
						url('../" . $font_data['font_url'] . '/' . $fontfilename . '.woff2?' . $randomver . "') format('woff2'),
						url('../" . $font_data['font_url'] . '/' . $fontfilename . '.woff?' . $randomver . "') format('woff'),
						url('../" . $font_data['font_url'] . '/' . $fontfilename . '.ttf?' . $randomver . "') format('truetype'),
						url('../" . $font_data['font_url'] . '/' . $fontfilename . '.svg?' . $randomver . '#' . $fontfilename . "') format('svg');
					font-weight: normal;
					font-style: normal;
				}\n";

		if ( ! empty( $icons ) && is_array( $icons ) ) {
			$css_content .= '.font-' . $fontfilename . "::before { font-family: 'iconize" . $fontfilename . "'; }\n";
			foreach ( $icons as $name_icon => $code ) {
				$css_content .= '.font-' . $fontfilename . '.glyph-' . $name_icon . "::before { content: '\\" . $code . "'; }\n";
			}
		}

		$css_content = preg_replace( '/\t+/', '', $css_content );

		$css_content_so = '.sow-icon-iconizefont' . $fontfilename . '{display:inline-block;font-family:"iconize' . $fontfilename . '";speak:none;font-style:normal;font-weight:normal;font-variant:normal;text-transform:none;text-rendering:auto;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;}.sow-icon-iconizefont' . $fontfilename . '[data-sow-icon]:before{content:attr(data-sow-icon);}';

		if ( is_dir( $this->upload_dir ) ) {
			$created_css_1 = file_put_contents( $this->upload_dir . '/' . $font_data['file_name'] . '/' . $fontfilename . '.css', $css_content );
			$created_css_2 = file_put_contents( $this->upload_dir . '/' . $font_data['file_name'] . '/' . $fontfilename . '-so.css', $css_content_so );
			return (bool) $created_css_1 && (bool) $created_css_2;
		}

		return false;
	}

	/**
	 * Upload ZIP file AJAX handler.
	 */
	public function iconize_icons_save_font() {

		$result = array();

		if ( wp_verify_nonce( $this->get_request( '_wpnonce' ), 'iconize_icons_nonce' ) && current_user_can( 'manage_options' ) ) {

			if ( ! class_exists( 'ZipArchive' ) ) {
				$result['status_save'] = 'failedopen';
				die( wp_json_encode( $result ) );
			}

			$file_name = $this->get_request( 'file_name', 'font' );

			if ( ! empty( $_FILES ) && ! empty( $_FILES['source_file'] ) ) {

				$zip = new ZipArchive;
				$res = $zip->open( $_FILES['source_file']['tmp_name'] );
				if ( $res === true ) {
					$ex = $zip->extractTo( $this->upload_dir . '/' . $file_name );
					$zip->close();
					if ( $ex === false ) {
						$result['status_save'] = 'failedextract';
						die( wp_json_encode( $result ) );
					}
				} else {
					$result['status_save'] = 'failedopen';
					die( wp_json_encode( $result ) );
				}

				$font_data = $this->get_config_font( $file_name );

				$icons = $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );

				if ( ! empty( $icons ) && is_array( $icons ) ) {
					$generated_json = $this->generate_json( $font_data, $icons );
					$generated_css  = $this->generate_css( $font_data, $icons );

					$result['status_save'] = $this->update_options( $font_data );
				} else {
					$result['status_save'] = 'emptyfile';
				}
			} else {
				$result['status_save'] = 'emptyfile';
			}

			die( wp_json_encode( $result ) );
		}

		$result['status_save'] = 'emptyfile';
		die( wp_json_encode( $result ) );
	}

	/**
	 * Update Options table
	 *
	 * @param array $font_data Font data.
	 */
	private function update_options( $font_data ) {

		if ( empty( $font_data['name'] ) ) {
			return null;
		}

		$options = get_option( 'iconize_uploaded_fonts_data', array() );
		if ( ! empty( $options[ $font_data['name'] ] ) ) {
			return 'exist';
		}

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options[ $font_data['name'] ] = array(
			'data' => wp_json_encode( $font_data ),
		);

		if ( update_option( 'iconize_uploaded_fonts_data', $options ) ) {
			return 'updated';
		} else {
			return 'updatefailed';
		}
	}

	/**
	 * Delete ZIP file and remove from option.
	 */
	public function iconize_icons_delete_font() {

		$result = array();

		if ( wp_verify_nonce( $this->get_request( '_wpnonce' ), 'iconize_icons_nonce' ) && current_user_can( 'manage_options' ) ) {

			$file_name = $this->get_request( 'font_key', 'font' );

			$options = get_option( 'iconize_uploaded_fonts_data' );

			if ( empty( $options[ $file_name ] ) ) {
				return false;
			}

			$result['status_save'] = 'none';

			$data = json_decode( $options[ $file_name ]['data'], true );

			// Remove from option.
			unset( $options[ $file_name ] );

			// Remove foler.
			$this->rrmdir( $this->upload_dir . '/' . $data['file_name'] );

			if ( update_option( 'iconize_uploaded_fonts_data', $options ) ) {
				$result['status_save'] = 'remove';
			}
		}

		die( wp_json_encode( $result ) );
	}
}
