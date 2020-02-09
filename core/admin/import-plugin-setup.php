<?php
defined('ABSPATH') || exit;
/****************************************************************************/
# Predefine Theme and Plugin install in one go                               #
# Functionality:                                                             #
#    1. Ocean Wp Theme Download and activate Automatically                   #
#    2. Plugin Like woocomerce,elmentor,wp Forms Download and activate       #
#       Automatically                                                        #
#                                                                            #
#                                                                            #
# Author         : Matrix                                                    #
# Created Date   : 12-09-2019                                                #
# Purpose        : Predefine Theme and Plugin install and activate in one go #
/****************************************************************************/

/**
 * wpJelly Import Plugin Setup
 */
class wpJellyImportPluginSetup  {

	function __construct() {
		$this->initailise();
	}

	public function initailise() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/misc.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php' );
	}

	public function getRequiredPluginsList() {
		return array(
			array( 'name' => 'elementor', 'path' => 'https://downloads.wordpress.org/plugin/elementor.latest-stable.zip', 'install' => 'elementor/elementor.php' ),
			array( 'name' => 'ocean-extra', 'path' => 'https://downloads.wordpress.org/plugin/ocean-extra.latest-stable.zip', 'install' => 'ocean-extra/ocean-extra.php' ),
			array( 'name' => 'woocommerce', 'path' => 'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip', 'install' => 'woocommerce/woocommerce.php' ),
			array( 'name' => 'wpforms-lite', 'path' => 'https://downloads.wordpress.org/plugin/wpforms-lite.latest-stable.zip', 'install' => 'wpforms-lite/wpforms.php' )
		);
	}

	public function getRequiredPluginsCount() {
		return count( $this->getRequiredPluginsList() );
	}

	public function processPlugins( $pluginsStart, $pluginsCount ) {
		$plugins = $this->getRequiredPluginsList();

		$pluginIndex = 0;

		add_filter( 'upgrader_package_options', array( $this, 'set_upgrader_options' ), 10, 1 );
		add_filter( 'auto_update_translation', array( $this, 'disable_update_translation' ), 10, 2 );

		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
		remove_action( 'upgrader_process_complete', 'wp_version_check' );
		remove_action( 'upgrader_process_complete', 'wp_update_plugins' );
		remove_action( 'upgrader_process_complete', 'wp_update_themes' );

		foreach ( $plugins as $singlePlugin ) {
			// Skip plugins from the previous request
			if ($pluginIndex < $pluginsStart) {
				$pluginIndex++;
				continue;
			}

			// Stop adding plugins if we reached maximum limit for the current request
			if ($pluginIndex >= $pluginsStart + $pluginsCount) {
				break;
			}

			$pluginIndex++;

			$this->pluginDownloadAndActivate( $singlePlugin['name'], $singlePlugin['path'], $singlePlugin['install'] );
		}
	}

	public function pluginDownloadAndActivate( $name, $path, $install ) {
	    $plugin_installed = true;

		if ( $this->isPluginInstalled( $install ) ) {
			$plugin_installed = true;
		} else {
			$plugin_installed = $this->installPlugin( $path );
		}

		if ( !is_wp_error( $plugin_installed ) && $plugin_installed ) {
			$activate = activate_plugin( $install );
		}

		return $plugin_installed;
    } 

	public function isPluginInstalled( $slug ) {
		$all_plugins = get_plugins();

		if ( !empty( $all_plugins[$slug] ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function set_upgrader_options( $options ) {
		$options['abort_if_destination_exists'] = false;

		return $options;
	}

	public function disable_update_translation( $update, $item ) {
		return false;
	}

	public function installPlugin( $plugin_zip ) {
		wp_cache_flush();

		$upgrader = new Plugin_Upgrader();
		$installed = $upgrader->install( $plugin_zip );

		return $installed;
	}
}