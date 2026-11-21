<?php
/**
 * Settings page of the plugin ( Settings → StyleSwitch ).
 *
 * @package StyleSwitch
 */

namespace StyleSwitch;

defined( 'ABSPATH' ) || exit;

/**
 * Settings of the plugin and of the StyleSwitch library, stored in one option and edited with the Settings API.
 */
final class Settings {

	/** Option with all settings. */
	const OPTION = 'style_switch_settings';

	/** Settings group ( settings_fields() ). */
	const GROUP = 'style_switch';

	/** Slug of the settings page ( options-general.php?page=… ). */
	const PAGE = 'style-switch';

	/** Capability needed to change the settings. */
	const CAPABILITY = 'manage_options';

	/** Values of the setting default_style. */
	const DEFAULT_STYLE_SYSTEM = 'system';
	const DEFAULT_STYLE_LIGHT  = STYLE_LIGHT;
	const DEFAULT_STYLE_DARK   = STYLE_DARK;

	/** Element names allowed for captions inside a label ( phrasing content, StyleSwitch supports only inline elements there ). */
	const INLINE_ELEMENTS = array( 'strong', 'b', 'em', 'i', 'span', 'small', 'mark' );

	/** Element names allowed for the caption of the radio buttons. */
	const CAPTION_ELEMENTS = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong', 'span', 'div' );

	/** Sections of the settings page. */
	const SECTION_STYLES   = 'style_switch_styles';
	const SECTION_BEHAVIOR = 'style_switch_behavior';
	const SECTION_COOKIE   = 'style_switch_cookie';
	const SECTION_TEXTS    = 'style_switch_texts';
	const SECTION_NAKED    = 'style_switch_naked';
	const SECTION_MARKUP   = 'style_switch_markup';

	/** Field types. */
	const TYPE_TEXT        = 'text';
	const TYPE_SPACED_TEXT = 'spaced_text'; // Spaces at the ends are kept ( divider ).
	const TYPE_CHECKBOX    = 'checkbox';
	const TYPE_SELECT      = 'select';
	const TYPE_NUMBER      = 'number';
	const TYPE_CLASS_NAME  = 'class_name';
	const TYPE_ID_PREFIX   = 'id_prefix';
	const TYPE_PATH        = 'path';

	/**
	 * Default values of all settings. Empty texts mean "the default text" ( translatable, see get_library_settings() ).
	 * Values differing from StyleSwitch.DEFAULT_SETTINGS are choices of this plugin ( e.g. no roles in tooltips ).
	 */
	const DEFAULTS = array(
		// Styles.
		'default_style'          => self::DEFAULT_STYLE_SYSTEM,
		'light_title'            => '',
		'dark_title'             => '',
		// Behavior.
		'color_scheme_change'    => 'onlyWithoutCookie',
		'reverse_order'          => false,
		'apply_on_server'        => true,
		// Cookie.
		'cookie_days'            => 365,
		'cookie_path'            => '/',
		'cookie_same_site'       => 'strict',
		'cookie_partitioned'     => true,
		// Texts.
		'text_caption'           => '',
		'switch_caption'         => '',
		'switch_title'           => '',
		'switch_off'             => '',
		'switch_on'              => '',
		'switch_divider'         => '',
		'select_caption'         => '',
		'select_group_styles'    => '',
		'select_group_naked'     => '',
		'radios_caption'         => '',
		// Style without CSS.
		'naked_use'              => false,
		'naked_caption'          => '',
		'naked_day_auto'         => false,
		'naked_day_month'        => 4, // April, 1–12 here, StyleSwitch counts months from 0.
		'naked_day_day'          => 9,
		// Markup.
		'id_prefix'              => 'style-switch-result-',
		'switch_roles_title'     => false,
		'switch_caption_element' => 'strong',
		'switch_status_element'  => 'small',
		'switch_label_class'     => SWITCH_CLASS_NAME,
		'switch_visual_class'    => 'visual',
		'switch_state_class'     => 'state',
		'switch_css'             => true,
		'select_roles_title'     => false,
		'select_caption_element' => 'strong',
		'radios_roles_title'     => false,
		'radios_caption_element' => 'h3',
	);

	/**
	 * Saved settings merged with the defaults.
	 *
	 * @var array|null
	 */
	private static $values = null;

	/**
	 * Hooks of the settings page.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( self::class, 'add_action_link' ) );
		add_action( 'update_option_' . self::OPTION, array( self::class, 'forget_values' ) );
		add_action( 'add_option_' . self::OPTION, array( self::class, 'forget_values' ) );
	}

	/**
	 * Value of one setting.
	 *
	 * @param string $key Key from DEFAULTS.
	 * @return mixed
	 */
	public static function get( string $key ) {
		if ( null === self::$values ) {
			$stored       = get_option( self::OPTION, array() );
			self::$values = array_merge( self::DEFAULTS, is_array( $stored ) ? array_intersect_key( $stored, self::DEFAULTS ) : array() );
		}
		return self::$values[ $key ] ?? null;
	}

	/**
	 * Forgets the cached values after the option changes.
	 */
	public static function forget_values(): void {
		self::$values = null;
	}

	/**
	 * URL of the settings page.
	 */
	public static function get_page_url(): string {
		return admin_url( 'options-general.php?page=' . self::PAGE );
	}

	/**
	 * Adds the link "Settings" to the row of the plugin on the Plugins screen.
	 *
	 * @param array $links Action links of the plugin.
	 * @return array
	 */
	public static function add_action_link( $links ) {
		$links = (array) $links;
		if ( current_user_can( self::CAPABILITY ) ) {
			array_unshift( $links, '<a href="' . esc_url( self::get_page_url() ) . '">' . esc_html__( 'Settings', 'style-switch' ) . '</a>' );
		}
		return $links;
	}

	/**
	 * Adds the page Settings → StyleSwitch.
	 */
	public static function add_page(): void {
		add_options_page(
			__( 'StyleSwitch settings', 'style-switch' ),
			'StyleSwitch',
			self::CAPABILITY,
			self::PAGE,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Registers the option, the sections and the fields ( Settings API ).
	 */
	public static function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		foreach ( self::get_sections() as $id => $section ) {
			add_settings_section( $id, $section['title'], array( self::class, 'render_section' ), self::PAGE );
		}
		foreach ( self::get_fields() as $key => $field ) {
			add_settings_field(
				'style-switch-' . $key,
				$field['label'],
				array( self::class, 'render_field' ),
				self::PAGE,
				$field['section'],
				array(
					'key'       => $key,
					'label_for' => self::TYPE_CHECKBOX === $field['type'] ? null : 'style-switch-' . $key,
				)
			);
		}
	}

	/**
	 * Sections of the settings page.
	 *
	 * @return array<string, array{title: string, description: string}>
	 */
	private static function get_sections(): array {
		return array(
			self::SECTION_STYLES   => array(
				'title'       => __( 'Styles', 'style-switch' ),
				'description' => __( 'The style sheets the visitor switches between. The style sheets of the plugin only set the CSS property color-scheme of the page, the colors come from the theme ( for example with the CSS function light-dark() ).', 'style-switch' ),
			),
			self::SECTION_BEHAVIOR => array(
				'title'       => __( 'Behavior', 'style-switch' ),
				'description' => '',
			),
			self::SECTION_COOKIE   => array(
				'title'       => __( 'Cookie', 'style-switch' ),
				/* translators: %s: name of the cookie */
				'description' => sprintf( __( 'The choice of the visitor is stored in the cookie "%s" ( its name cannot be changed, see the fixed settings below ).', 'style-switch' ), COOKIE_NAME ),
			),
			self::SECTION_TEXTS    => array(
				'title'       => __( 'Texts', 'style-switch' ),
				'description' => __( 'Empty fields use the default text shown in grey.', 'style-switch' ),
			),
			self::SECTION_NAKED    => array(
				'title'       => __( 'Style without CSS', 'style-switch' ),
				'description' => __( 'Offers the page without any CSS ( naked HTML ) as one more style, for example for the CSS Naked Day. There are three styles then, so the widget shows a drop-down list instead of a switch.', 'style-switch' ),
			),
			self::SECTION_MARKUP   => array(
				'title'       => __( 'Markup of the form control', 'style-switch' ),
				'description' => __( 'HTML which StyleSwitch builds in the widget.', 'style-switch' ),
			),
		);
	}

	/**
	 * Fields of the settings page.
	 *
	 * Each field: section, type, label, optional description, library ( the key in StyleSwitch.DEFAULT_SETTINGS ),
	 * placeholder ( default text ), choices ( select ), min and max ( number ).
	 *
	 * @return array<string, array>
	 */
	private static function get_fields(): array {
		global $wp_locale;

		$inline_elements  = array_combine( self::INLINE_ELEMENTS, self::INLINE_ELEMENTS );
		$caption_elements = array_combine( self::CAPTION_ELEMENTS, self::CAPTION_ELEMENTS );
		$months           = array();
		for ( $month = 1; $month <= 12; $month++ ) {
			$months[ $month ] = $wp_locale instanceof \WP_Locale ? $wp_locale->get_month( $month ) : (string) $month;
		}
		$roles_description = __( 'Role of the style sheet in the document ( persistent, preferred, alternate ) as a tooltip. Meant for developers.', 'style-switch' );

		return array(
			// Styles.
			'default_style'          => array(
				'section'     => self::SECTION_STYLES,
				'type'        => self::TYPE_SELECT,
				'label'       => __( 'Style before the visitor chooses', 'style-switch' ),
				'choices'     => array(
					self::DEFAULT_STYLE_SYSTEM => __( 'By the operating system of the visitor', 'style-switch' ),
					self::DEFAULT_STYLE_LIGHT  => __( 'Light', 'style-switch' ),
					self::DEFAULT_STYLE_DARK   => __( 'Dark', 'style-switch' ),
				),
				'description' => __( 'By the operating system: the dark style sheet gets media="(prefers-color-scheme: dark)" ( "preferred" style sheet ). Light or dark: the other style is only an "alternate" style sheet, used after the visitor chooses it.', 'style-switch' ),
			),
			'light_title'            => array(
				'section'     => self::SECTION_STYLES,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Name of the light style', 'style-switch' ),
				'placeholder' => __( 'Light', 'style-switch' ),
				'description' => __( 'Attribute title of the style sheet, shown in the widget ( and in Firefox in View → Page Style ).', 'style-switch' ),
			),
			'dark_title'             => array(
				'section'     => self::SECTION_STYLES,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Name of the dark style', 'style-switch' ),
				'placeholder' => __( 'Dark', 'style-switch' ),
			),
			// Behavior.
			'color_scheme_change'    => array(
				'section' => self::SECTION_BEHAVIOR,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'When the operating system switches between light and dark colors', 'style-switch' ),
				'library' => 'resultSnippetAppearance.preferredColorSchemeChangeBehavior',
				'choices' => array(
					'onlyWithoutCookie' => __( 'Follow it only until the visitor chooses a style', 'style-switch' ),
					'always'            => __( 'Always follow it, even when the visitor has chosen a style ( the choice is deleted )', 'style-switch' ),
					'never'             => __( 'Never follow it in the widget', 'style-switch' ),
				),
			),
			'reverse_order'          => array(
				'section' => self::SECTION_BEHAVIOR,
				'type'    => self::TYPE_CHECKBOX,
				'label'   => __( 'Order', 'style-switch' ),
				'text'    => __( 'Offer the styles in reverse order', 'style-switch' ),
				'library' => 'resultSnippetAppearance.reverseOrder',
			),
			'apply_on_server'        => array(
				'section'     => self::SECTION_BEHAVIOR,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Server side', 'style-switch' ),
				'text'        => __( 'Apply the choice of the visitor already in the HTML of the page', 'style-switch' ),
				'description' => __( 'The page is shown in the chosen style from the first moment. Turn it off when the HTML of the pages is cached for all visitors together ( full page cache ), the choice is then applied by JavaScript after the page loads.', 'style-switch' ),
			),
			// Cookie.
			'cookie_days'            => array(
				'section' => self::SECTION_COOKIE,
				'type'    => self::TYPE_NUMBER,
				'label'   => __( 'Keep the choice for ( days )', 'style-switch' ),
				'library' => 'cookie.timeBeforeExpire',
				'min'     => 1,
				'max'     => 3650,
			),
			'cookie_path'            => array(
				'section'     => self::SECTION_COOKIE,
				'type'        => self::TYPE_PATH,
				'label'       => __( 'Path', 'style-switch' ),
				'library'     => 'cookie.path',
				'description' => __( 'Part of the site the choice applies to, "/" is the whole domain.', 'style-switch' ),
			),
			'cookie_same_site'       => array(
				'section' => self::SECTION_COOKIE,
				'type'    => self::TYPE_SELECT,
				'label'   => 'SameSite',
				'library' => 'cookie.sameSite',
				'choices' => array(
					'strict' => 'Strict',
					'lax'    => 'Lax',
					'none'   => 'None',
				),
			),
			'cookie_partitioned'     => array(
				'section'     => self::SECTION_COOKIE,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Partitioned', 'style-switch' ),
				'text'        => __( 'Partitioned cookie ( CHIPS )', 'style-switch' ),
				'library'     => 'cookie.partitioned',
				'description' => __( 'When the site is embedded in another site, the choice is kept separately there.', 'style-switch' ),
			),
			// Texts.
			'text_caption'           => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Caption of the form control', 'style-switch' ),
				'library'     => 'texts.caption',
				'placeholder' => __( 'Color scheme', 'style-switch' ),
				'description' => __( 'Used when the widget has no title.', 'style-switch' ),
			),
			'switch_caption'         => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Caption of the switch', 'style-switch' ),
				'library'     => 'texts.switch.caption',
				'description' => __( 'Only for the switch, instead of the title of the widget.', 'style-switch' ),
			),
			'switch_title'           => array(
				'section' => self::SECTION_TEXTS,
				'type'    => self::TYPE_TEXT,
				'label'   => __( 'Tooltip of the switch', 'style-switch' ),
				'library' => 'texts.switch.title',
			),
			'switch_off'             => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Text of the switch turned off', 'style-switch' ),
				'library'     => 'texts.switch.stateOffCaption',
				'placeholder' => __( 'name of the first style', 'style-switch' ),
			),
			'switch_on'              => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Text of the switch turned on', 'style-switch' ),
				'library'     => 'texts.switch.stateOnCaption',
				'placeholder' => __( 'name of the second style', 'style-switch' ),
			),
			'switch_divider'         => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_SPACED_TEXT,
				'label'       => __( 'Divider in the tooltip of the switch', 'style-switch' ),
				'library'     => 'texts.switch.versusDividerForRadio',
				'placeholder' => ' / ',
				'description' => __( 'Joins the roles of the style sheets when they are shown as the tooltip ( see Markup ). Spaces are kept.', 'style-switch' ),
			),
			'select_caption'         => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Caption of the drop-down list', 'style-switch' ),
				'library'     => 'texts.select.caption',
				'description' => __( 'Only for the drop-down list, instead of the title of the widget.', 'style-switch' ),
			),
			'select_group_styles'    => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Group of the styles in the drop-down list', 'style-switch' ),
				'library'     => 'texts.select.optGroupLabelForStyles',
				'description' => __( 'Label of the optgroup with the styles. Empty: no group.', 'style-switch' ),
			),
			'select_group_naked'     => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Group of the style without CSS in the drop-down list', 'style-switch' ),
				'library'     => 'texts.select.otpGroupLabelForNaked',
				'description' => __( 'Label of the optgroup with the style without CSS. Empty: no group.', 'style-switch' ),
			),
			'radios_caption'         => array(
				'section'     => self::SECTION_TEXTS,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Caption of the radio buttons', 'style-switch' ),
				'library'     => 'texts.radioList.caption',
				'description' => __( 'Only for the radio buttons, instead of the title of the widget.', 'style-switch' ),
			),
			// Style without CSS.
			'naked_use'              => array(
				'section' => self::SECTION_NAKED,
				'type'    => self::TYPE_CHECKBOX,
				'label'   => __( 'Style without CSS', 'style-switch' ),
				'text'    => __( 'Offer the page without any CSS as one more style', 'style-switch' ),
				'library' => 'nakedStyle.use',
			),
			'naked_caption'          => array(
				'section'     => self::SECTION_NAKED,
				'type'        => self::TYPE_TEXT,
				'label'       => __( 'Name of the style without CSS', 'style-switch' ),
				'library'     => 'texts.nakedStyleCaption',
				'placeholder' => __( 'Without style (naked HTML)', 'style-switch' ),
			),
			'naked_day_auto'         => array(
				'section'     => self::SECTION_NAKED,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'CSS Naked Day', 'style-switch' ),
				'text'        => __( 'Switch to the style without CSS automatically on the day below and back the next day', 'style-switch' ),
				'library'     => 'nakedStyle.celebrateNakedDay.switchAutomatically',
				'description' => __( 'Needs the style without CSS turned on. The day is the date of the visitor.', 'style-switch' ),
			),
			'naked_day_month'        => array(
				'section' => self::SECTION_NAKED,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'Month of the CSS Naked Day', 'style-switch' ),
				'library' => 'nakedStyle.celebrateNakedDay.monthNumber',
				'choices' => $months,
			),
			'naked_day_day'          => array(
				'section' => self::SECTION_NAKED,
				'type'    => self::TYPE_NUMBER,
				'label'   => __( 'Day of the CSS Naked Day', 'style-switch' ),
				'library' => 'nakedStyle.celebrateNakedDay.dayNumber',
				'min'     => 1,
				'max'     => 31,
			),
			// Markup.
			'id_prefix'              => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_ID_PREFIX,
				'label'       => __( 'Prefix of the id', 'style-switch' ),
				'library'     => 'resultSnippetAppearance.idPrefix',
				'description' => __( 'A random string is appended. Letters, digits, "-" and "_".', 'style-switch' ),
			),
			'switch_caption_element' => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'Switch: element of the caption', 'style-switch' ),
				'library' => 'resultSnippetAppearance.switch.captionElementName',
				'choices' => $inline_elements,
			),
			'switch_status_element'  => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'Switch: element of the state text', 'style-switch' ),
				'library' => 'resultSnippetAppearance.switch.statusElementName',
				'choices' => $inline_elements,
			),
			'switch_label_class'     => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_CLASS_NAME,
				'label'       => __( 'Switch: class of the label', 'style-switch' ),
				'library'     => 'resultSnippetAppearance.switch.labelClassName',
				'description' => __( 'StyleSwitch uses "switch" by default, the plugin uses its own class because themes often style "switch".', 'style-switch' ),
			),
			'switch_visual_class'    => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_CLASS_NAME,
				'label'   => __( 'Switch: class of the visual switch', 'style-switch' ),
				'library' => 'resultSnippetAppearance.switch.visualSwitchClassName',
			),
			'switch_state_class'     => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_CLASS_NAME,
				'label'   => __( 'Switch: class of the state', 'style-switch' ),
				'library' => 'resultSnippetAppearance.switch.stateClassName',
			),
			'switch_css'             => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Switch: look', 'style-switch' ),
				'text'        => __( 'Load the built-in look of the switch', 'style-switch' ),
				'description' => __( 'assets/css/switch.css, it expects the default class names above. Turn it off when the theme styles the switch.', 'style-switch' ),
			),
			'switch_roles_title'     => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Switch: roles', 'style-switch' ),
				'text'        => __( 'Roles of the style sheets as the tooltip', 'style-switch' ),
				'library'     => 'resultSnippetAppearance.switch.useRolesAsTitle',
				'description' => $roles_description,
			),
			'select_caption_element' => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'Drop-down list: element of the caption', 'style-switch' ),
				'library' => 'resultSnippetAppearance.select.captionElementName',
				'choices' => $inline_elements,
			),
			'select_roles_title'     => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Drop-down list: roles', 'style-switch' ),
				'text'        => __( 'Roles of the style sheets as tooltips of the options', 'style-switch' ),
				'library'     => 'resultSnippetAppearance.select.useRoleAsOptionTitle',
				'description' => $roles_description,
			),
			'radios_caption_element' => array(
				'section' => self::SECTION_MARKUP,
				'type'    => self::TYPE_SELECT,
				'label'   => __( 'Radio buttons: element of the caption', 'style-switch' ),
				'library' => 'resultSnippetAppearance.radioList.captionElementName',
				'choices' => $caption_elements,
			),
			'radios_roles_title'     => array(
				'section'     => self::SECTION_MARKUP,
				'type'        => self::TYPE_CHECKBOX,
				'label'       => __( 'Radio buttons: roles', 'style-switch' ),
				'text'        => __( 'Roles of the style sheets as tooltips of the items', 'style-switch' ),
				'library'     => 'resultSnippetAppearance.radioList.useRoleAsItemTitle',
				'description' => $roles_description,
			),
		);
	}

	/**
	 * Sanitizes the submitted settings: known keys only, values checked by the type of the field.
	 *
	 * Runs also on values sanitized before ( the first save of the option ), so it has to keep them unchanged.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array
	 */
	public static function sanitize( $input ): array {
		$input  = is_array( $input ) ? $input : array(); // options.php unslashes the values itself.
		$output = array();
		foreach ( self::get_fields() as $key => $field ) {
			$default = self::DEFAULTS[ $key ];
			$value   = $input[ $key ] ?? null;
			switch ( $field['type'] ) {
				case self::TYPE_CHECKBOX:
					$output[ $key ] = ! empty( $value );
					break;
				case self::TYPE_SELECT:
					$choices        = array_map( 'strval', array_keys( $field['choices'] ) );
					$output[ $key ] = is_scalar( $value ) && in_array( (string) $value, $choices, true ) ? $value : $default;
					if ( is_int( $default ) ) {
						$output[ $key ] = (int) $output[ $key ];
					}
					break;
				case self::TYPE_NUMBER:
					$number         = is_scalar( $value ) ? (int) $value : 0;
					$output[ $key ] = $number >= $field['min'] && $number <= $field['max'] ? $number : $default;
					break;
				case self::TYPE_SPACED_TEXT:
					$text           = is_scalar( $value ) ? wp_check_invalid_utf8( (string) $value ) : '';
					$output[ $key ] = mb_substr( str_replace( array( "\r", "\n", "\t" ), ' ', wp_kses( $text, array() ) ), 0, 50 );
					break;
				case self::TYPE_CLASS_NAME:
					$class          = is_scalar( $value ) ? sanitize_html_class( (string) $value ) : '';
					$output[ $key ] = '' !== $class ? $class : $default;
					break;
				case self::TYPE_ID_PREFIX:
					$prefix         = is_scalar( $value ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) : '';
					$output[ $key ] = '' !== $prefix ? $prefix : $default;
					break;
				case self::TYPE_PATH:
					$path           = is_scalar( $value ) ? preg_replace( '~[^A-Za-z0-9/._\~-]~', '', (string) $value ) : '';
					$output[ $key ] = '' !== $path && '/' === $path[0] ? $path : $default;
					break;
				default: // TYPE_TEXT.
					$output[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
			}
		}
		return $output;
	}

	/**
	 * Outputs the description of a section.
	 *
	 * @param array $section Section from add_settings_section().
	 */
	public static function render_section( array $section ): void {
		$sections = self::get_sections();
		if ( ! empty( $sections[ $section['id'] ]['description'] ) ) {
			echo '<p>' . esc_html( $sections[ $section['id'] ]['description'] ) . '</p>';
		}
		if ( self::SECTION_STYLES === $section['id'] && has_filter( 'style_switch_styles' ) ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'The active theme or another plugin adjusts the styles with the filter style_switch_styles, it can override the settings in this section.', 'style-switch' ) . '</p></div>';
		}
	}

	/**
	 * Outputs one field.
	 *
	 * @param array $args Arguments from add_settings_field(), key is the key of the setting.
	 */
	public static function render_field( array $args ): void {
		$fields = self::get_fields();
		$key    = $args['key'];
		$field  = $fields[ $key ];
		$id     = 'style-switch-' . $key;
		$name   = self::OPTION . '[' . $key . ']';
		$value  = self::get( $key );

		switch ( $field['type'] ) {
			case self::TYPE_CHECKBOX:
				printf(
					'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( $field['text'] )
				);
				break;
			case self::TYPE_SELECT:
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['choices'] as $choice => $label ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( (string) $choice ), selected( (string) $value, (string) $choice, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case self::TYPE_NUMBER:
				printf(
					'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d" step="1">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					(int) $field['min'],
					(int) $field['max']
				);
				break;
			default: // Texts, class names, id prefix, path.
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( $field['placeholder'] ?? '' )
				);
		}

		if ( ! empty( $field['description'] ) || ! empty( $field['library'] ) ) {
			echo '<p class="description">';
			if ( ! empty( $field['description'] ) ) {
				echo esc_html( $field['description'] ) . ' ';
			}
			if ( ! empty( $field['library'] ) ) {
				echo '<code>' . esc_html( $field['library'] ) . '</code>';
			}
			echo '</p>';
		}
	}

	/**
	 * Outputs the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>
				<?php esc_html_e( 'Settings of the StyleSwitch library for all StyleSwitch widgets. The title of the widget and the type of the form control ( switch, drop-down list, radio buttons ) are set in each widget.', 'style-switch' ); ?>
				<a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Widgets', 'style-switch' ); ?></a>
				·
				<a href="https://github.com/iiic/StyleSwitch#readme" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation of StyleSwitch', 'style-switch' ); ?></a>
			</p>
			<?php if ( ! is_widget_used() ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'The StyleSwitch widget is not in any widget area yet, nothing is shown on the site.', 'style-switch' ); ?></p></div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Fixed settings', 'style-switch' ); ?></h2>
			<p><?php esc_html_e( 'These settings of StyleSwitch are given by the plugin.', 'style-switch' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Setting', 'style-switch' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Value', 'style-switch' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Why', 'style-switch' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( self::get_fixed_settings() as $setting => $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $setting ); ?></code></td>
							<td><code><?php echo esc_html( $row[0] ); ?></code></td>
							<td><?php echo esc_html( $row[1] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Settings of StyleSwitch which the plugin does not let change, with the reason.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	private static function get_fixed_settings(): array {
		return array(
			'settingsIdentifier'                                  => array( SETTINGS_ELEMENT_ID, __( 'The widget writes the settings into the element with this id.', 'style-switch' ) ),
			'customElementName, rootElementQS'                    => array( CONTAINER_ELEMENT, __( 'The widget outputs the element <style-switch>, StyleSwitch fills it with the form control.', 'style-switch' ) ),
			'styleLinksQSA'                                       => array( 'link[rel~=stylesheet]', __( 'The cookie listener ( live switching ) and the server side work with all style sheets of the page.', 'style-switch' ) ),
			'cookie.name'                                         => array( COOKIE_NAME, __( 'The cookie listener, bundled without changes, has the name hard-coded.', 'style-switch' ) ),
			'resultSnippetAppearance.outputFormat'                => array( __( 'per widget', 'style-switch' ), __( 'Set in each widget.', 'style-switch' ) ),
			'resultSnippetAppearance.defaultResultSnippetElement' => array( 'div', __( 'Used only when there is no container, the widget always outputs one.', 'style-switch' ) ),
			'autoRun'                                             => array( 'true', __( 'The widget needs StyleSwitch to run by itself after it loads.', 'style-switch' ) ),
		);
	}
}
