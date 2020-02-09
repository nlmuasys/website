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
# Author         : Matrix                                               #
# Created Date   : 12-09-2019                                                #
# Purpose        : Predefine Theme and Plugin install and activate in one go #
/****************************************************************************/
/**
 * Wpjelly Import Theme Setup
 */
class wpJellyImportThemeSetup
{
	public function configureTheme() 
	{ 
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$theme = wp_get_theme();

		if ( !$theme ) {
			return false;
		}

		$theme_installed = false;
		$theme_activated = false;

		if ( ( $theme->get_stylesheet() === 'oceanwp' ) && ( $theme->get( 'Name' ) === 'OceanWP' ) ) {
			$theme_installed = true;
			$theme_activated = true;
		} else {
			foreach ( (array) wp_get_themes() as $site_theme ) {
				if ( ( $site_theme->get_stylesheet() === 'oceanwp' ) && ( $site_theme->get( 'Name' ) === 'OceanWP' ) ) {
					$theme_installed = true;
					break;
				}
			}
		}

		if ( !$theme_installed && !$theme_activated )
		{
	    	$theme_dir = wp_normalize_path( get_theme_root() );

	        $source = "https://downloads.wordpress.org/theme/oceanwp.latest-stable.zip";

			$zipFile = $theme_dir . '/oceanwp.zip';

			if ( !$this->downloadThemeFile( $source, $zipFile ) ) {
				return false;
			}

			if ( !wpjellyExtractArchive( $zipFile, $theme_dir ) ) {
				return false;
			}

			switch_theme( 'oceanwp' );
		} else if ( $theme_installed && !$theme_activated ) {
			switch_theme( 'oceanwp' );
		}

		return true;
	}

	public function downloadThemeFile( $source_url, $file_path )
	{
		$response = wp_remote_get(
			$source_url,
			array(
				'timeout'  => 30,
				'stream'   => true,
				'filename' => $file_path
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return true;
	}
}
