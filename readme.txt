=== StyleSwitch ===
Contributors: iiic
Tags: dark mode, color scheme, style switcher, alternate stylesheet, widget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A widget that lets visitors switch the site between a light and a dark color scheme. Follows the operating system until the visitor chooses.

== Description ==

StyleSwitch adds a widget with a switch ( or a drop-down list, or radio buttons ) for the color scheme of the site.

* Without a choice the site follows the color scheme of the operating system ( `prefers-color-scheme` ).
* The visitor's choice is stored in a cookie and used on every page and on the next visit.
* The style changes at once, without reloading the page, also in other open tabs.
* The chosen style is applied already in the HTML from the server, so the page does not flash in the default style.
* It uses the browser's native alternate style sheets, so Firefox users can also switch in View → Page Style.
* Settings → StyleSwitch offers the settings of the StyleSwitch library: the default style, names of the styles, all texts, the cookie, the page without CSS ( also automatically for the CSS Naked Day ) and the markup of the form control.
* Translated into Czech, the language follows the language of WordPress ( of the site, in the administration of the user ).
* No external requests, no tracking, no dependencies.

The plugin is a WordPress wrapper of the [StyleSwitch](https://github.com/iiic/StyleSwitch) library ( version 1.4.2, bundled without changes ).

= What changes on the page =

The default style sheets of the plugin set only the CSS property `color-scheme` of the page ( `light` or `dark` ). Your theme decides what the colors are:

* Themes which define their colors with the CSS function `light-dark()`, for example `--color-background: light-dark(#ffffff, #1b1b1b);`, switch completely.
* Themes which use system colors ( `Canvas`, `CanvasText` ) or leave the colors to the browser switch too.
* For other themes the browser switches only its own parts ( form controls, scroll bars ). Such a theme can offer its own style sheets with the filter `style_switch_styles` ( see below ).

= For theme developers =

Filter `style_switch_styles` – the style sheets to switch between, in the order they are offered:

`add_filter( 'style_switch_styles', function ( $styles ) {
	$styles['dark']['src'] = get_theme_file_uri( 'css/dark.css' );
	$styles['dark']['title'] = 'Night';
	return $styles;
} );`

Each style sheet has `title`, `src` ( keep the URL stable, it is stored in the cookie ), an optional `media` query ( the style is used automatically while it matches, by default `(prefers-color-scheme: dark)` for the dark style ) and `default` ( the style used when nothing else applies ). More than two styles are offered in a drop-down list or as radio buttons. The filter gets the styles already adjusted by the settings page and can override them.

Filter `style_switch_settings` – settings for the StyleSwitch library built from the settings page and the widget, see `StyleSwitch.DEFAULT_SETTINGS` in `assets/js/style-switch.mjs`. Only the settings which differ from the defaults of the library are written into the page.

Filter `style_switch_apply_choice_on_server` – whether the choice of the visitor is applied already in the HTML, default from the settings page.

== Installation ==

1. Install and activate the plugin.
2. Go to Appearance → Widgets and add the widget "StyleSwitch – light / dark" to a widget area.
3. Optionally set the title and the form control ( switch, drop-down list or radio buttons ) in the widget.
4. Optionally adjust the other settings in Settings → StyleSwitch ( also the link "Settings" at the plugin on the Plugins screen ).

The plugin needs a theme with widget areas.

== Frequently Asked Questions ==

= The switch changes nothing on my site =

The theme sets its colors without `light-dark()`. See "What changes on the page" above – the theme can define its colors with `light-dark()`, or offer its own style sheets with the filter `style_switch_styles`.

= Why is the widget empty? =

The form control is built by JavaScript. It needs a browser with the Cookie Store API ( Chrome and Edge 87+, Firefox 140+, Safari 18.4+ ). Only one StyleSwitch widget is shown on a page.

= Does the plugin set cookies? =

Only when the visitor chooses a color scheme: the cookie `stylesheets` with the addresses of the style sheets of the site and whether each of them is turned on, for one year by default ( Settings → StyleSwitch → Cookie ). It is a functional cookie, it does not identify or track the visitor. The plugin suggests a paragraph for your privacy policy ( Settings → Privacy ).

= Does it work with page caching? =

Yes. With a full page cache turn off "Apply the choice of the visitor already in the HTML of the page" in Settings → StyleSwitch, otherwise one visitor's choice could be cached in the HTML for others.

= How do I translate the plugin? =

On [translate.wordpress.org](https://translate.wordpress.org/), translations from there are installed automatically and take precedence. The plugin also bundles translations in the folder `languages` ( Czech ), `languages/style-switch.pot` is the template for new ones.

= Why are some settings of StyleSwitch missing? =

The plugin depends on a few of them ( the id of the settings element, the container element, the cookie name and others ). The settings page lists them with the reason at the bottom.

== Changelog ==

= 1.0.0 =
* First version, StyleSwitch library 1.4.2: widget, settings page, the choice applied on the server, Czech translation.

== Credits ==

The bundled files `assets/js/style-switch.mjs` and `assets/js/style-switch-cookie-listener-example.js` are the [StyleSwitch](https://github.com/iiic/StyleSwitch) library by ic, licensed under CC BY-SA 4.0, which is one-way compatible with the GNU GPL version 3. They are distributed here without changes as part of this plugin under the GPL version 3 or later. `assets/css/switch.css` is adapted from `example-css/switch.css` of the same library.
