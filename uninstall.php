<?php
/**
 * Removes the data of the plugin: its settings and the settings of its widgets.
 *
 * @package StyleSwitch
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'style_switch_settings' );
delete_option( 'widget_style_switch' );
