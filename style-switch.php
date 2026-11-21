<?php
/**
 * Plugin Name:       StyleSwitch
 * Plugin URI:        https://iiic.dev/style-switch
 * Description:       A widget that lets visitors switch the site between a light and a dark color scheme. Built on the StyleSwitch library: alternate style sheets, prefers-color-scheme and a cookie with the visitor's choice.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            ic
 * Author URI:        https://iiic.dev/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       style-switch
 * Domain Path:       /languages
 *
 * @package StyleSwitch
 */

namespace StyleSwitch;

defined( 'ABSPATH' ) || exit;

/** Version of this plugin. */
const VERSION = '1.0.0';

/** Main file of the plugin. */
const PLUGIN_FILE = __FILE__;

/** Version of the bundled StyleSwitch library ( assets/js/style-switch.mjs ), used as ?ver of its files. */
const LIBRARY_VERSION = '1.4.2';

/** Handle of the StyleSwitch script module. */
const HANDLE = 'style-switch';

/** Handle of the script module which switches the style sheets live when the cookie changes. */
const LISTENER_HANDLE = 'style-switch-cookie-listener';

/** Handle of the style sheet with the look of the "switch" output format. */
const SWITCH_STYLE_HANDLE = 'style-switch-switch';

/** Handle of the default style sheet, always loaded ( "persistent" in StyleSwitch ). */
const PERSISTENT_STYLE_HANDLE = 'style-switch-persistent';

/** Prefix of the handles of the switchable style sheets, the key from style_switch_styles is appended. */
const STYLE_HANDLE_PREFIX = 'style-switch-style-';

/** Keys of the default styles. */
const STYLE_LIGHT = 'light';
const STYLE_DARK  = 'dark';

/** Cookie with the visitor's choice, default cookie.name of StyleSwitch, also hard-coded in the cookie listener. */
const COOKIE_NAME = 'stylesheets';

/** Key of the naked style ( page without any CSS ) in the cookie. */
const NAKED_STYLE_PATH = '';

/** Id of the JSON element StyleSwitch reads its settings from ( settingsIdentifier ). */
const SETTINGS_ELEMENT_ID = 'styleSwitchSettings';

/** Element StyleSwitch fills with the form control ( customElementName ). */
const CONTAINER_ELEMENT = 'style-switch';

/** Class of the label of the "switch" output format, instead of the default "switch" which themes often style. */
const SWITCH_CLASS_NAME = 'style-switch-toggle';

/** Id base of the widget, its instances are stored in the option widget_style_switch. */
const WIDGET_ID_BASE = 'style_switch';

/** Output formats of StyleSwitch ( StyleSwitch.OUTPUT_FORMATS ). */
const FORMAT_SWITCH = 'switch';
const FORMAT_SELECT = 'select';
const FORMAT_RADIOS = 'radioList';
const FORMATS       = array( FORMAT_SWITCH, FORMAT_SELECT, FORMAT_RADIOS );

/** Media query of the dark style, used automatically when the operating system prefers dark colors. */
const DARK_MEDIA = '(prefers-color-scheme: dark)';

/**
 * StyleSwitch.DEFAULT_SETTINGS of the bundled library ( only the keys the plugin sets ). Only the settings which differ
 * from them are written into the page.
 */
const LIBRARY_DEFAULTS = array(
	'customElementName'       => 'style-switch',
	'cookie'                  => array(
		'name'             => 'stylesheets',
		'timeBeforeExpire' => 365 * 24 * 60 * 60 * 1000,
		'partitioned'      => true,
		'path'             => '/',
		'sameSite'         => 'strict',
	),
	'texts'                   => array(
		'caption'           => 'Style switch',
		'nakedStyleCaption' => 'Without style (naked HTML)',
		'switch'            => array(
			'caption'               => '',
			'title'                 => '',
			'versusDividerForRadio' => ' / ',
			'stateOnCaption'        => 'on',
			'stateOffCaption'       => 'off',
		),
		'select'            => array(
			'caption'                => '',
			'optGroupLabelForStyles' => '',
			'otpGroupLabelForNaked'  => '',
		),
		'radioList'         => array(
			'caption' => '',
		),
	),
	'nakedStyle'              => array(
		'use'               => false,
		'celebrateNakedDay' => array(
			'switchAutomatically' => false,
			'monthNumber'         => 3,
			'dayNumber'           => 9,
		),
	),
	'resultSnippetAppearance' => array(
		'idPrefix'                           => 'style-switch-result-',
		'outputFormat'                       => 'select',
		'preferredColorSchemeChangeBehavior' => 'onlyWithoutCookie',
		'reverseOrder'                       => false,
		'switch'                             => array(
			'useRolesAsTitle'       => true,
			'labelClassName'        => 'switch',
			'captionElementName'    => 'strong',
			'visualSwitchClassName' => 'visual',
			'stateClassName'        => 'state',
			'statusElementName'     => 'small',
		),
		'select'                             => array(
			'useRoleAsOptionTitle' => true,
			'captionElementName'   => 'strong',
		),
		'radioList'                          => array(
			'captionElementName' => 'h3',
			'useRoleAsItemTitle' => true,
		),
	),
);

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-widget.php';

Settings::init();

/**
 * Loads the translations bundled in the folder languages ( style-switch-{locale}.l10n.php or .mo ).
 *
 * Translations from translate.wordpress.org ( wp-content/languages/plugins ) take precedence, the bundled ones are
 * used for languages without them. Hooked before widgets_init ( init, priority 1 ), the widget translates its name.
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'style-switch', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' ); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- bundled translations.
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain', 0 );

/**
 * Style sheets the visitor can switch between, in the order they are offered in the widget.
 *
 * The default style sheets only set the CSS property color-scheme. Colors follow it in themes which use
 * light-dark() or system colors. Other themes can offer their own style sheets with the filter style_switch_styles.
 *
 * @return array<string, array{title: string, src: string, media: string, default: bool}> Keyed by a style key.
 */
function get_styles(): array {
	static $styles = null;

	if ( null !== $styles ) {
		return $styles;
	}

	$default_style = Settings::get( 'default_style' );
	$light_title   = Settings::get( 'light_title' );
	$dark_title    = Settings::get( 'dark_title' );
	$defaults      = array(
		STYLE_LIGHT => array(
			'title'   => '' !== $light_title ? $light_title : __( 'Light', 'style-switch' ),
			'src'     => plugins_url( 'assets/css/light.css', __FILE__ ),
			'media'   => '',
			'default' => Settings::DEFAULT_STYLE_DARK !== $default_style,
		),
		STYLE_DARK  => array(
			'title'   => '' !== $dark_title ? $dark_title : __( 'Dark', 'style-switch' ),
			'src'     => plugins_url( 'assets/css/dark.css', __FILE__ ),
			'media'   => Settings::DEFAULT_STYLE_SYSTEM === $default_style ? DARK_MEDIA : '',
			'default' => Settings::DEFAULT_STYLE_DARK === $default_style,
		),
	);

	/**
	 * Filters the style sheets the StyleSwitch widget switches between.
	 *
	 * Each style sheet is an array with:
	 * - title   (string) Name shown in the widget, required.
	 * - src     (string) URL of the style sheet, required. Keep it stable ( no changing version parameter ),
	 *                    the URL is stored in the visitor's cookie.
	 * - media   (string) Optional media query. While it matches and the visitor has not chosen a style yet,
	 *                    the style sheet is used automatically, e.g. '(prefers-color-scheme: dark)'.
	 * - default (bool)   The style used when nothing else applies, it is always loaded first. Exactly one style
	 *                    should be the default one, otherwise the first one is used.
	 *
	 * @param array $styles Style sheets keyed by a style key ( used in handles, sanitize_key() is applied ).
	 */
	$filtered = apply_filters( 'style_switch_styles', $defaults );

	$styles      = array();
	$has_default = false;
	if ( is_array( $filtered ) ) {
		foreach ( $filtered as $key => $style ) {
			$key = sanitize_key( (string) $key );
			if (
				'' === $key
				|| ! is_array( $style )
				|| empty( $style['title'] ) || ! is_string( $style['title'] )
				|| empty( $style['src'] ) || ! is_string( $style['src'] )
			) {
				continue;
			}
			$is_default     = ! $has_default && ! empty( $style['default'] );
			$has_default    = $has_default || $is_default;
			$media          = isset( $style['media'] ) && is_string( $style['media'] ) ? trim( $style['media'] ) : '';
			$styles[ $key ] = array(
				'title'   => $style['title'],
				'src'     => $style['src'],
				'media'   => $is_default ? '' : $media,
				'default' => $is_default,
			);
		}
	}
	if ( $styles && ! $has_default ) {
		$first                        = array_key_first( $styles );
		$styles[ $first ]['default'] = true;
		$styles[ $first ]['media']   = '';
	}

	return $styles;
}

/**
 * Whether the widget is placed in an active widget area.
 */
function is_widget_used(): bool {
	return (bool) is_active_widget( false, false, WIDGET_ID_BASE, true );
}

/**
 * Whether some instance of the widget uses the given output format.
 *
 * @param string $format One of FORMATS.
 */
function is_format_used( string $format ): bool {
	global $wp_widget_factory;

	$widget = $wp_widget_factory instanceof \WP_Widget_Factory ? $wp_widget_factory->get_widget_object( WIDGET_ID_BASE ) : null;
	if ( ! $widget instanceof \WP_Widget ) {
		return false;
	}
	foreach ( $widget->get_settings() as $instance ) {
		if ( is_array( $instance ) && Widget::get_format( $instance ) === $format ) {
			return true;
		}
	}
	return false;
}

/**
 * Enqueues the switchable style sheets and both scripts, only when the widget is used.
 *
 * Output order: the default style sheet ( persistent ), then every style sheet with a title in the order of
 * get_styles() – with a media query as "preferred", without it as "alternate", the default one again as
 * "alternate ( clone of persistent )". This is the markup StyleSwitch expects. The style sheets go late in the
 * head so that they come after the style sheets of the theme.
 */
function enqueue_assets(): void {
	if ( ! is_widget_used() ) {
		return;
	}
	$styles = get_styles();
	if ( count( $styles ) < 2 ) {
		return; // Nothing to switch between.
	}

	foreach ( $styles as $style ) {
		if ( $style['default'] ) {
			// Without a version: the URL must be the same as the URL of the clone below and it is stored in the cookie.
			wp_enqueue_style( PERSISTENT_STYLE_HANDLE, $style['src'], array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			break;
		}
	}
	foreach ( $styles as $key => $style ) {
		$handle = STYLE_HANDLE_PREFIX . $key;
		// Without a version: the URL is stored in the visitor's cookie and has to stay the same.
		wp_enqueue_style( $handle, $style['src'], array( PERSISTENT_STYLE_HANDLE ), null, '' !== $style['media'] ? $style['media'] : 'all' ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_style_add_data( $handle, 'title', $style['title'] );
		if ( '' === $style['media'] ) {
			wp_style_add_data( $handle, 'alt', true );
		}
	}

	if ( Settings::get( 'switch_css' ) && is_format_used( FORMAT_SWITCH ) ) {
		wp_enqueue_style( SWITCH_STYLE_HANDLE, plugins_url( 'assets/css/switch.css', __FILE__ ), array(), VERSION );
	}

	wp_enqueue_script_module( LISTENER_HANDLE, plugins_url( 'assets/js/style-switch-cookie-listener-example.js', __FILE__ ), array(), LIBRARY_VERSION );
	wp_enqueue_script_module( HANDLE, plugins_url( 'assets/js/style-switch.mjs', __FILE__ ), array(), LIBRARY_VERSION );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets', 100 );

/**
 * Whether today ( in the time zone of the site ) is the CSS Naked Day from the settings.
 */
function is_naked_day(): bool {
	return (int) current_time( 'n' ) === (int) Settings::get( 'naked_day_month' )
		&& (int) current_time( 'j' ) === (int) Settings::get( 'naked_day_day' );
}

/**
 * Path of the style sheet chosen by the visitor ( NAKED_STYLE_PATH for the page without CSS ), or null when the visitor
 * has not chosen yet, the cookie points to a style sheet this plugin does not offer or the widget is not used.
 *
 * The cookie is written by StyleSwitch, e.g. {"https://example.com/…/dark.css":{"disabled":false},…}.
 */
function get_chosen_path(): ?string {
	static $chosen = false;

	if ( false !== $chosen ) {
		return $chosen;
	}
	$chosen = null;

	/**
	 * Filters whether the style chosen by the visitor is applied already in the HTML of the page.
	 *
	 * Without it the page is shown in the default style first and switched by JavaScript after it loads. Return false
	 * when the HTML of the pages is cached for all visitors together ( full page cache ).
	 *
	 * @param bool $apply Apply the choice on the server, default from the settings page ( on ).
	 */
	if ( ! apply_filters( 'style_switch_apply_choice_on_server', (bool) Settings::get( 'apply_on_server' ) ) ) {
		return $chosen;
	}
	if ( ! isset( $_COOKIE[ COOKIE_NAME ] ) || ! is_string( $_COOKIE[ COOKIE_NAME ] ) || ! is_widget_used() ) {
		return $chosen;
	}
	// The value is JSON: it is decoded, its structure checked and the path is only compared with known URLs below.
	$cookie = json_decode( wp_unslash( $_COOKIE[ COOKIE_NAME ] ), true, 3 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! is_array( $cookie ) ) {
		return $chosen;
	}
	foreach ( $cookie as $path => $state ) {
		if ( is_array( $state ) && isset( $state['disabled'] ) && false === $state['disabled'] ) {
			$path = (string) $path;
			if ( NAKED_STYLE_PATH === $path ) {
				// The naked style set automatically for the CSS Naked Day ends with the day ( StyleSwitch then deletes the cookie ).
				$is_over = ! empty( $state['byNakedDay'] ) && ! is_naked_day();
				if ( Settings::get( 'naked_use' ) && ! $is_over ) {
					$chosen = $path;
				}
			} elseif ( in_array( $path, array_column( get_styles(), 'src' ), true ) ) {
				$chosen = $path;
			}
			break;
		}
	}

	return $chosen;
}

/**
 * Applies the style chosen by the visitor to one link element of the plugin, the same way as example-usage.php from
 * StyleSwitch ( and its cookie listener ) does it.
 *
 * @param \WP_HTML_Tag_Processor $processor Processor at the link element.
 * @param string                 $chosen    Path of the chosen style sheet, NAKED_STYLE_PATH for the naked style.
 */
function apply_choice_to_link( \WP_HTML_Tag_Processor $processor, string $chosen ): void {
	$title = $processor->get_attribute( 'title' );
	if ( ! is_string( $title ) || '' === $title ) { // Persistent style sheet, disabled only by the naked style.
		if ( NAKED_STYLE_PATH === $chosen ) {
			$processor->set_attribute( 'disabled', true );
		}
		return;
	}
	if ( $processor->get_attribute( 'href' ) !== $chosen ) {
		$processor->set_attribute( 'disabled', true );
		return;
	}
	// The chosen style sheet: hide the attributes which would stop the browser from using it.
	if ( false !== strpos( (string) $processor->get_attribute( 'rel' ), 'alternate' ) ) {
		$processor->set_attribute( 'rel', 'stylesheet' );
		$processor->set_attribute( 'data-alternate', '' );
	}
	$processor->set_attribute( 'data-title', $title );
	$processor->remove_attribute( 'title' );
	$media = $processor->get_attribute( 'media' );
	if ( is_string( $media ) && '' !== $media ) {
		$processor->set_attribute( 'data-media', $media );
		$processor->remove_attribute( 'media' );
	}
}

/**
 * Adjusts the link elements of style sheets.
 *
 * - Style sheets of the plugin: removes media="all" ( StyleSwitch takes every link with a matching media attribute for
 *   the one preferred by media ) and applies the style chosen by the visitor, so the page is shown in the chosen style
 *   from the first paint.
 * - Other style sheets: with the naked style chosen every persistent style sheet is disabled, as the cookie listener
 *   does it after the page loads.
 *
 * @param string $tag    The link tag.
 * @param string $handle The style's handle.
 * @return string
 */
function filter_style_tag( $tag, $handle ) {
	$is_own = PERSISTENT_STYLE_HANDLE === $handle || 0 === strpos( (string) $handle, STYLE_HANDLE_PREFIX );
	$chosen = get_chosen_path();
	if ( ! $is_own && NAKED_STYLE_PATH !== $chosen ) {
		return $tag;
	}

	$processor = new \WP_HTML_Tag_Processor( (string) $tag );
	while ( $processor->next_tag( 'link' ) ) {
		if ( $is_own ) {
			if ( in_array( $processor->get_attribute( 'media' ), array( 'all', '' ), true ) ) {
				$processor->remove_attribute( 'media' );
			}
			if ( null !== $chosen ) {
				apply_choice_to_link( $processor, $chosen );
			}
		} else {
			$rel   = strtolower( trim( (string) $processor->get_attribute( 'rel' ) ) );
			$title = $processor->get_attribute( 'title' );
			if ( 'stylesheet' === $rel && ( ! is_string( $title ) || '' === $title ) ) {
				$processor->set_attribute( 'disabled', true );
			}
		}
	}

	return $processor->get_updated_html();
}
add_filter( 'style_loader_tag', __NAMESPACE__ . '\\filter_style_tag', 10, 2 );

/**
 * Only the settings which differ from the defaults.
 *
 * @param array $settings Settings.
 * @param array $defaults Default settings of the same structure.
 * @return array
 */
function diff_settings( array $settings, array $defaults ): array {
	$diff = array();
	foreach ( $settings as $key => $value ) {
		if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
			$nested = diff_settings( $value, $defaults[ $key ] );
			if ( $nested ) {
				$diff[ $key ] = $nested;
			}
		} elseif ( ! array_key_exists( $key, $defaults ) || $defaults[ $key ] !== $value ) {
			$diff[ $key ] = $value;
		}
	}
	return $diff;
}

/**
 * Settings for StyleSwitch from the settings page and the widget instance ( only the keys which differ from
 * StyleSwitch.DEFAULT_SETTINGS ).
 *
 * @param array $instance Settings of the widget instance.
 * @return array
 */
function get_library_settings( array $instance ): array {
	$titles = array_column( get_styles(), 'title' );
	if ( Settings::get( 'reverse_order' ) ) {
		$titles = array_reverse( $titles );
	}
	$caption = Widget::get_title( $instance );
	if ( '' === $caption ) {
		$caption = '' !== Settings::get( 'text_caption' ) ? Settings::get( 'text_caption' ) : __( 'Color scheme', 'style-switch' );
	}
	// The switch shows the name of the current style instead of "on" / "off" ( only two styles can be a switch ).
	$state_off = Settings::get( 'switch_off' );
	$state_on  = Settings::get( 'switch_on' );
	if ( '' === $state_off ) {
		$state_off = 2 === count( $titles ) ? $titles[0] : __( 'off', 'style-switch' );
	}
	if ( '' === $state_on ) {
		$state_on = 2 === count( $titles ) ? $titles[1] : __( 'on', 'style-switch' );
	}

	$settings = array(
		'customElementName'       => CONTAINER_ELEMENT,
		'cookie'                  => array(
			'name'             => COOKIE_NAME,
			'timeBeforeExpire' => (int) Settings::get( 'cookie_days' ) * DAY_IN_SECONDS * 1000,
			'partitioned'      => (bool) Settings::get( 'cookie_partitioned' ),
			'path'             => Settings::get( 'cookie_path' ),
			'sameSite'         => Settings::get( 'cookie_same_site' ),
		),
		'texts'                   => array(
			'caption'           => $caption,
			'nakedStyleCaption' => '' !== Settings::get( 'naked_caption' ) ? Settings::get( 'naked_caption' ) : __( 'Without style (naked HTML)', 'style-switch' ),
			'switch'            => array(
				'caption'               => Settings::get( 'switch_caption' ),
				'title'                 => Settings::get( 'switch_title' ),
				'versusDividerForRadio' => '' !== Settings::get( 'switch_divider' ) ? Settings::get( 'switch_divider' ) : ' / ',
				'stateOnCaption'        => $state_on,
				'stateOffCaption'       => $state_off,
			),
			'select'            => array(
				'caption'                => Settings::get( 'select_caption' ),
				'optGroupLabelForStyles' => Settings::get( 'select_group_styles' ),
				'otpGroupLabelForNaked'  => Settings::get( 'select_group_naked' ), // Sic, the key in StyleSwitch.
			),
			'radioList'         => array(
				'caption' => Settings::get( 'radios_caption' ),
			),
		),
		'nakedStyle'              => array(
			'use'               => (bool) Settings::get( 'naked_use' ),
			'celebrateNakedDay' => array(
				'switchAutomatically' => (bool) Settings::get( 'naked_day_auto' ),
				'monthNumber'         => (int) Settings::get( 'naked_day_month' ) - 1, // StyleSwitch counts months from 0.
				'dayNumber'           => (int) Settings::get( 'naked_day_day' ),
			),
		),
		'resultSnippetAppearance' => array(
			'idPrefix'                           => Settings::get( 'id_prefix' ),
			'outputFormat'                       => Widget::get_format( $instance ),
			'preferredColorSchemeChangeBehavior' => Settings::get( 'color_scheme_change' ),
			'reverseOrder'                       => (bool) Settings::get( 'reverse_order' ),
			'switch'                             => array(
				'useRolesAsTitle'       => (bool) Settings::get( 'switch_roles_title' ),
				'labelClassName'        => Settings::get( 'switch_label_class' ),
				'captionElementName'    => Settings::get( 'switch_caption_element' ),
				'visualSwitchClassName' => Settings::get( 'switch_visual_class' ),
				'stateClassName'        => Settings::get( 'switch_state_class' ),
				'statusElementName'     => Settings::get( 'switch_status_element' ),
			),
			'select'                             => array(
				'useRoleAsOptionTitle' => (bool) Settings::get( 'select_roles_title' ),
				'captionElementName'   => Settings::get( 'select_caption_element' ),
			),
			'radioList'                          => array(
				'captionElementName' => Settings::get( 'radios_caption_element' ),
				'useRoleAsItemTitle' => (bool) Settings::get( 'radios_roles_title' ),
			),
		),
	);

	/**
	 * Filters the settings passed to StyleSwitch, see StyleSwitch.DEFAULT_SETTINGS in assets/js/style-switch.mjs.
	 *
	 * Do not change cookie.name, the cookie listener and the server side expect the default one.
	 *
	 * @param array $settings Settings merged by StyleSwitch into its default settings.
	 * @param array $instance Settings of the widget instance.
	 */
	$settings = apply_filters( 'style_switch_settings', $settings, $instance );

	return is_array( $settings ) ? diff_settings( $settings, LIBRARY_DEFAULTS ) : array();
}

/**
 * Registers the widget.
 */
function register_widgets(): void {
	register_widget( Widget::class );
}
add_action( 'widgets_init', __NAMESPACE__ . '\\register_widgets' );

/**
 * Suggests a paragraph about the cookie for the privacy policy of the site.
 */
function add_privacy_policy_content(): void {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$content = sprintf(
		/* translators: %s: name of the cookie */
		__( 'When you choose a color scheme of this site, your choice is saved in the cookie "%s" in your browser, so that the site is shown in the same color scheme on your next visit. The cookie contains only the addresses of the style sheets of this site and whether each of them is turned on. It is not used to identify or track you.', 'style-switch' ),
		COOKIE_NAME
	);
	wp_add_privacy_policy_content( 'StyleSwitch', wp_kses_post( wpautop( $content, false ) ) );
}
add_action( 'admin_init', __NAMESPACE__ . '\\add_privacy_policy_content' );
