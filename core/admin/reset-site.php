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
 * Reset Initial Site
 */
class wpjellyResetSiteToInitailState 
{
	private $core_tables = array('commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'termmeta', 'terms', 'usermeta', 'users');
	private $delete_count = 0;

	public function resetStart()
	{
        $this->doDeactivatePlugins();
	}

	public function resetProcess()
	{
		$this->core_tables = array_map(function ($tbl) {
        	global $wpdb;
        	return $wpdb->prefix . $tbl;
       	}, $this->core_tables);

		$this->doDeleteTransients();
        $this->doResetThemeOptions(true);
        $this->doResetPlugins();
		$this->dropCustomTables();
        $this->truncateDefaultTables();
        $this->resetUploadFolder();
	}

	public function doDeleteTransients()
	{
	    global $wpdb;

	    $count = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_%' OR option_name LIKE '\_site\_transient\_%'");

	    wp_cache_flush();
		return $count;
	}
	public function doResetThemeOptions($all_themes = true)
	{
	    global $wpdb;
		$count = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'theme_mods\_%' OR option_name LIKE 'mods\_%'");
	    return $count;
	}
	public function doDeactivatePlugins()
	{
		if ( !function_exists( 'get_plugins' ) ) {
	      	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	    }

		if ( !function_exists( 'request_filesystem_credentials' ) ) {
	      	require_once ABSPATH . 'wp-admin/includes/file.php';
	    }

		$active_plugins = get_option( 'active_plugins', array() );

		if ( ( !empty( $active_plugins ) ) && ( count( $active_plugins ) > 0 ) ) {
			unset( $active_plugins[array_search( WP_JELLY_BASE, $active_plugins )] );

			if ( !empty( $active_plugins ) ) {
				deactivate_plugins( $active_plugins );
    	    }
    	}
	}
	public function doResetPlugins()
	{
		if ( !function_exists( 'get_plugins' ) ) {
	      	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	    }

		if ( !function_exists( 'request_filesystem_credentials' ) ) {
	      	require_once ABSPATH . 'wp-admin/includes/file.php';
	    }

    	$all_plugins = get_plugins();

		if ( ( !empty( $all_plugins ) ) && ( count( $all_plugins ) > 0 ) )
    	{
			unset( $all_plugins[WP_JELLY_BASE] );

			if ( !empty( $all_plugins ) ) {
    	    	delete_plugins( array_keys( $all_plugins ) );
    	    }
    	}
	}
	public function dropCustomTables()
	{
		global $wpdb;
        $custom_tables = $this->get_custom_tables();
        if(count($custom_tables)>0)
        {
            foreach ($custom_tables as $tbl) {
    	      $wpdb->query('DROP TABLE IF EXISTS ' . $tbl['name']);
    	    } 
        }

	}
	public function get_custom_tables()
	{
	    global $wpdb;
	    $custom_tables = array();

	    $table_status = $wpdb->get_results('SHOW TABLE STATUS');
	    if (is_array($table_status)) {
	      foreach ($table_status as $index => $table) {
	        if (0 !== stripos($table->Name, $wpdb->prefix)) {
	          continue;
	        }
	        if (empty($table->Engine)) {
	          continue;
	        }

	        if (false === in_array($table->Name, $this->core_tables)) {
	          $custom_tables[] = array('name' => $table->Name, 'rows' => $table->Rows, 'data_length' => $table->Data_length, 'index_length' => $table->Index_length);
	        }
	      } // foreach
	    }

	    return $custom_tables;
	} // get_custom tables
	public function resetUploadFolder()
	{
		$upload_dir = wp_get_upload_dir();
		$this->delete_folder($upload_dir['basedir'], $upload_dir['basedir']);
	}
	private function delete_folder($folder, $base_folder)
  	{
	    $files = array_diff(scandir($folder), array('.', '..'));

	    foreach ($files as $file) {
	      if (is_dir($folder . DIRECTORY_SEPARATOR . $file)) {
	        $this->delete_folder($folder . DIRECTORY_SEPARATOR . $file, $base_folder);
	      } else {
	        $tmp = @unlink($folder . DIRECTORY_SEPARATOR . $file);
	        $this->delete_count = $this->delete_count + (int) $tmp;
	      }
	    } // foreach

	    if ($folder != $base_folder) {
	      $tmp = @rmdir($folder);
	      $this->delete_count = $this->delete_count + (int) $tmp;
	      return $tmp;
	    } else {
	      return true;
	    }
  	} // delete_folder
  	public function truncateDefaultTables()
  	{
  		global $wpdb;
  		$core_tables = array('commentmeta', 'comments', 'links', 'postmeta', 'posts', 'term_relationships', 'term_taxonomy', 'termmeta', 'terms');
  		foreach ($core_tables as $tableName) {
  			$table=$wpdb->prefix.$tableName;
  	        $wpdb->query('TRUNCATE TABLE ' . $table);
  		}

  	}
}		