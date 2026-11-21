<?php
/**
 * The StyleSwitch widget.
 *
 * @package StyleSwitch
 */

namespace StyleSwitch;

defined( 'ABSPATH' ) || exit;

/**
 * Widget with the form control for switching the style of the site, the control is built by StyleSwitch in the browser.
 */
class Widget extends \WP_Widget {

	/**
	 * Sets up the widget. Nothing is loaded here, the constructor runs on every request.
	 */
	public function __construct() {
		parent::__construct(
			WIDGET_ID_BASE,
			__( 'StyleSwitch – light / dark', 'style-switch' ),
			array(
				'classname'             => 'widget_style_switch',
				'description'           => __( 'Lets visitors switch the site between the light and the dark color scheme.', 'style-switch' ),
				'show_instance_in_rest' => true,
			)
		);
	}

	/**
	 * Title of the widget, shown by StyleSwitch as the caption of the form control. Empty: the caption from the
	 * settings page is used.
	 *
	 * @param array $instance Settings of the widget instance.
	 */
	public static function get_title( array $instance ): string {
		return isset( $instance['title'] ) && is_string( $instance['title'] ) ? trim( $instance['title'] ) : '';
	}

	/**
	 * Output format of StyleSwitch, one of FORMATS.
	 *
	 * @param array $instance Settings of the widget instance.
	 */
	public static function get_format( array $instance ): string {
		return isset( $instance['format'] ) && in_array( $instance['format'], FORMATS, true ) ? $instance['format'] : FORMAT_SWITCH;
	}

	/**
	 * Outputs the settings for StyleSwitch and the element StyleSwitch fills with the form control.
	 *
	 * The title is not printed separately, StyleSwitch shows it as the caption ( label ) of the form control.
	 *
	 * @param array $args     Display arguments of the widget area.
	 * @param array $instance Settings of the widget instance.
	 */
	public function widget( $args, $instance ) {
		static $is_rendered = false;

		// StyleSwitch reads one set of settings and fills only the first container on the page.
		if ( $is_rendered || count( get_styles() ) < 2 ) {
			return;
		}
		$is_rendered = true;

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup of the widget area from the theme.
		wp_print_inline_script_tag(
			(string) wp_json_encode( get_library_settings( (array) $instance ) ),
			array(
				'type' => 'application/json',
				'id'   => SETTINGS_ELEMENT_ID,
			)
		);
		echo '<style-switch></style-switch>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup of the widget area from the theme.
	}

	/**
	 * Outputs the settings form of the widget.
	 *
	 * @param array $instance Current settings.
	 * @return string
	 */
	public function form( $instance ) {
		$instance = (array) $instance;
		$title    = self::get_title( $instance );
		$caption  = '' !== Settings::get( 'text_caption' ) ? Settings::get( 'text_caption' ) : __( 'Color scheme', 'style-switch' );
		$format   = self::get_format( $instance );
		$formats  = array(
			FORMAT_SWITCH => __( 'Switch (for two styles, otherwise a drop-down list)', 'style-switch' ),
			FORMAT_SELECT => __( 'Drop-down list', 'style-switch' ),
			FORMAT_RADIOS => __( 'Radio buttons', 'style-switch' ),
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'style-switch' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $caption ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php esc_html_e( 'Form control:', 'style-switch' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>">
				<?php foreach ( $formats as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $format, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description"><?php esc_html_e( 'Only one StyleSwitch widget is shown on a page. Visitors without JavaScript or with a browser without the Cookie Store API do not see it.', 'style-switch' ); ?></p>
		<?php if ( current_user_can( Settings::CAPABILITY ) ) : ?>
			<p><a href="<?php echo esc_url( Settings::get_page_url() ); ?>"><?php esc_html_e( 'More settings: Settings → StyleSwitch', 'style-switch' ); ?></a></p>
		<?php endif; ?>
		<?php
		return '';
	}

	/**
	 * Sanitizes the settings of the widget instance.
	 *
	 * @param array $new_instance Settings submitted in the form.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = (array) $new_instance;
		return array(
			'title'  => isset( $new_instance['title'] ) ? sanitize_text_field( (string) $new_instance['title'] ) : '',
			'format' => self::get_format( $new_instance ),
		);
	}
}
