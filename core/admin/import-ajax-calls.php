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
 * Import Wpjelly Ajax call setup from the Exporter
 */

class wpjellyXMLSetupImporter {

    private $stateCheck = null;

    function __construct() {
        $this->stateCheck = new wpjellyImportStateCheck();

        $this->stateCheck->recordStartTime();

        $this->initailise();
        $this->setRequestDefaults();
        $this->configureTimeLimit();

        ignore_user_abort( true );
    }

    public function initailise() {
        add_action('wp_ajax_wpjellyResetSiteStart', array($this, 'wpjellyResetSiteStart'));
        add_action('wp_ajax_wpjellyResetSiteProcess', array($this, 'wpjellyResetSiteProcess'));
        add_action('wp_ajax_wpjellyImportThemeData', array($this, 'wpjellyImportThemeData'));
        add_action('wp_ajax_wpjellyImportPlugin', array($this, 'wpjellyImportPlugin'));
        add_action('wp_ajax_wpjellyImportXmlStart', array($this, 'wpjellyImportXmlStart'));
        add_action('wp_ajax_wpjellyImportXmlMedia', array($this, 'wpjellyImportXmlMedia'));
        add_action('wp_ajax_wpjellyImportXmlFinish', array($this, 'wpjellyImportXmlFinish'));
        add_action('wp_ajax_wpjellyMigrateResources', array($this,'wpjellyMigrateResources'));
        add_action('wp_ajax_wpjellyMigrateFinish', array($this, 'wpjellyMigrateFinish'));
    }

    public function setRequestDefaults() {
        add_filter( 'http_request_timeout', array( $this, 'defaultRequestTimeout' ) );
    }

    public function defaultRequestTimeout( $value ) {
        return 10;
    }

    public function configureTimeLimit() {
        $disable_functions = @ini_get('disable_functions');
        $allowed = true;

        if ( !empty( $disable_functions ) ) {
            if ( stripos($disable_functions, 'set_time_limit') !== FALSE ) {
                $allowed = false;
            }
        }

        if ( $allowed && function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 0 );
        }
    }

    public function wpjellyResetSiteStart()
    {
        $seed = $_POST['seed'];

        $this->trackSeed($seed);

        $this->reportStart($seed);

        $resetObj = new wpjellyResetSiteToInitailState();
        $resetObj->resetStart();

        echo json_encode( array( 'success' => true ) );
        exit;
    }

    public function wpjellyResetSiteProcess()
    {
        $resetObj = new wpjellyResetSiteToInitailState();
        $resetObj->resetProcess();

        echo json_encode( array( 'success' => true ) );
        exit;
    }

    public function get_ip_address() {
        // check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check if multiple ips exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if ($this->validate_ip($ip))
                        return $ip;
                }
            } else {
                if ($this->validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];

        // return unreliable ip since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     */
    public function validate_ip($ip) {
        if (strtolower($ip) === 'unknown')
            return false;

        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);
            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) return false;
            if ($ip >= 167772160 && $ip <= 184549375) return false;
            if ($ip >= 2130706432 && $ip <= 2147483647) return false;
            if ($ip >= 2851995648 && $ip <= 2852061183) return false;
            if ($ip >= 2886729728 && $ip <= 2887778303) return false;
            if ($ip >= 3221225984 && $ip <= 3221226239) return false;
            if ($ip >= 3232235520 && $ip <= 3232301055) return false;
            if ($ip >= 4294967040) return false;
        }
        return true;
    }

    public function wpjellyImportThemeData() {
        $fileName = $_POST['fileName'];
        $fileDownloadUrl = $_POST['fileDownloadUrl'];
        $elementorUrl=$_POST['elementorUrl'];

        $upload_dir = wp_upload_dir();
        $destination_dir = $upload_dir['basedir'] . '/wpjelly-backup';

        if ( !is_dir( $destination_dir ) ) {
           mkdir( $destination_dir, 0755 );
        }

        $elementorData = wp_remote_retrieve_body( wp_remote_get( $elementorUrl, array( 'timeout' => 30 ) ) );

        if ( empty( $elementorData ) ) {
            echo json_encode( array( 'failure' => true ) );
            exit;
        }

        $filename = basename( $elementorUrl );
        $destination_file = $destination_dir . '/' . $filename;

        file_put_contents( $destination_file, $elementorData );

        if ( !wpjellyExtractArchive( $destination_file, $destination_dir ) ) {
            echo json_encode( array( 'failure' => true ) );
            exit;
        }

        $themeObj = new wpJellyImportThemeSetup();

        if ( !$themeObj->configureTheme() ) {
            echo json_encode( array( 'failure' => true ) );
            exit;
        }

        $pluginObj = new wpJellyImportPluginSetup();

        echo json_encode( array( 'success' => true, 'pluginsCount' => $pluginObj->getRequiredPluginsCount() ) );
        exit;
    }

    public function wpjellyImportPlugin() {
        if ( isset( $_POST['pluginsStart'] ) && isset( $_POST['pluginsCount'] ) ) {
            $pluginsStart = intval( $_POST['pluginsStart'] );
            $pluginsCount = intval( $_POST['pluginsCount'] );

            $pluginObj = new wpJellyImportPluginSetup();
            $pluginObj->processPlugins( $pluginsStart, $pluginsCount );
        }
        exit;
    }

    public function wpjellyImportXmlStart() {       
        $mediaCount = 0;

        $xmlUrl = $_POST['xmlUrl'];

        $pointList = $_POST['checklistPoints'];
        $pointList = preg_replace('/\\\\\"/', "\"", $pointList);
        $checklistPointsKey = 'wpjellyChecklistPointsList';
        update_option($checklistPointsKey, $pointList);

        $checkedPoints = $_POST['checkedPoints'];
        $checkedPoints = preg_replace('/\\\\\"/', "\"", $checkedPoints);
        $checkedPointsKey = 'wpjellycheckedPoints';
        update_option($checkedPointsKey, $checkedPoints);

        $mainUrl = $_POST['mainUrl'];
        $blogUrl = $_POST['blogUrl'];
        $mainId = $_POST['mainId'];

        if ( class_exists( 'wpJellyXmlImportSetup' ) ) {
        	$obj = new wpJellyXmlImportSetup( $this->stateCheck );
    
            if (!$obj->uploadXmlFileStart($xmlUrl, $mainUrl, $blogUrl, $mainId, $mediaCount)) {
                echo json_encode( array( 'failure' => true ) );
                exit;
            }
        }

        echo json_encode( array(
            'success'       => true,
            'mediaCount'    => $mediaCount,
            'mediaMax'      => WP_JELLY_MAX_ENTRIES_PER_REQUEST
        ) );

        exit;
    }

    public function wpjellyImportXmlMedia() {
        $xmlUrl = $_POST['xmlUrl'];
        $mainUrl = $_POST['mainUrl'];
        $blogUrl = $_POST['blogUrl'];
        $mainId = $_POST['mainId'];

        $mediaStart = intval( $_POST['mediaStart'] );

        $mediaProcessed = 0;

        if ( class_exists( 'wpJellyXmlImportSetup' ) ) {
        	$obj = new wpJellyXmlImportSetup( $this->stateCheck );

            if ( !$obj->uploadXmlFileMedia( $xmlUrl, $mainUrl, $blogUrl, $mainId, $mediaStart, $mediaProcessed ) ) {
                echo json_encode( array( 'failure' => true ) );
                exit;
            }
        }

        echo json_encode( array( 'success' => true, 'mediaProcessed' => $mediaProcessed ) );
        exit;
    }

    public function wpjellyImportXmlFinish() {
        $xmlUrl = $_POST['xmlUrl'];
        $mainUrl = $_POST['mainUrl'];
        $blogUrl = $_POST['blogUrl'];
        $mainId = $_POST['mainId'];

        if ( class_exists( 'wpJellyXmlImportSetup' ) ) {
        	$obj = new wpJellyXmlImportSetup( $this->stateCheck );

            if ( !$obj->uploadXmlFileFinish( $xmlUrl, $mainUrl, $blogUrl, $mainId ) ) {
                echo json_encode( array( 'failure' => true ) );
                exit;
            }
        }

        $resourcesCount = $this->wpjellyMigrateResourcesStart( $mainUrl, $blogUrl, $mainId );

        echo json_encode( array(
            'success'           => true,
            'resourcesCount'    => $resourcesCount,
            'resourcesMax'      => WP_JELLY_MAX_ENTRIES_PER_REQUEST
        ) );

        exit;
    }

    public function wpjellyMigrateResourcesStart( $mainUrl, $blogUrl, $mainId )
    {
        global $wpdb;

        $resourcesUrls = array();
        $resourcesPosts = array();
        $resourcesCount = 0;

        $deleteUrl = $mainUrl . "/wp-json/wpjellyExport/elementor/?blog=" . $mainId;
        $request = wp_remote_get( $deleteUrl );

        $elementorData = $wpdb->get_results( "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key='_elementor_data'" );

        if ( !empty( $elementorData ) ) {
            foreach ( $elementorData as $dataEntry ) {
                $meta = get_post_meta( $dataEntry->post_id, '_elementor_data', true );

                if ( !empty( $meta ) ) {
                    if ( is_string( $meta ) && ! empty( $meta ) ) {
                        $meta = json_decode( $meta, true );
                    }

                    $urlList = WpjellysearchItemsByKey( $meta, 'url' );

                    if ( is_array( $urlList ) && count( $urlList ) > 0 ) {
                        $resourcesUrls = array_merge( $resourcesUrls, $urlList );
                        $resourcesPosts[] = $dataEntry->post_id;
                    }
                }
            }
        }

        if ( !empty( $resourcesUrls ) ) {
            $resourcesData = array();
            sort( $resourcesUrls );
            $resourcesUrls = array_unique( $resourcesUrls );

            foreach ( $resourcesUrls as $url ) {
                if ( empty( $url ) || trim( $url ) == '#' ) {
                    continue;
                }

                $components = parse_url( $url );

                if ( $components && !empty( $components['path'] ) ) {
                    if ( empty( $components['host'] ) ) {
                        if ( !empty( $blogUrl ) ) {
                            $site_components = parse_url( $blogUrl );

                            if ( !empty( $site_components['host'] ) && !empty( $site_components['scheme'] ) ) {
                                $components['host'] = $site_components['host'];
                                $components['scheme'] = $site_components['scheme'];

                                $full_url = $this->makeUrl( $components );

                                if ( $this->isRemoteImage( $full_url ) ) {
                                    $resourcesData[$url] = array( 'full_url' => $full_url );
                                }
                            }
                        }
                    } else if ( ( $components['scheme'] == 'http' ) || ( $components['scheme'] == 'https' ) ) {
                        if ( $this->isRemoteImage( $url ) ) {
                            $resourcesData[$url] = array( 'full_url' => $url );
                        }
                    }
                }
            }

            update_option( 'wpjelly_import_state_resources_data', $resourcesData );
			update_option( 'wpjelly_import_state_resources_posts', $resourcesPosts );

            $resourcesCount = count( $resourcesData );
        }

        return $resourcesCount;
    }

    public function makeUrl( $parsed_url ) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public function wpjellyMigrateResources()
    {
        global $wpdb;

        $resourceIndex = 0;

        $resourcesStart = intval( $_POST['resourcesStart'] );

        $resourcesData = get_option( 'wpjelly_import_state_resources_data', array() );

        if ( !empty( $resourcesData ) && is_array( $resourcesData ) ) {

            foreach ( $resourcesData as $dateKey => $dataUrl ) {
                // Skip media files from the previous request
                if ( $resourceIndex < $resourcesStart ) {
                    $resourceIndex++;
                    continue;
                }

                $this->stateCheck->timeRecordStart();

                $replaceItem = $this->migrateSingleImage( $dataUrl['full_url'] );

                $this->stateCheck->timeRecordEnd();

                if ( is_wp_error( $replaceItem ) ) {
                    $process_error = $replaceItem;

                    if ( in_array( 'http_request_failed', $process_error->get_error_codes() ) ) {
                        break;
                    }
                }

                if ( !empty( $replaceItem['url'] ) ) {
                    $resourcesData[$dateKey]['url'] = $replaceItem['url'];

                    if ( !empty( $replaceItem['id'] ) ) {
                        $resourcesData[$dateKey]['id'] = $replaceItem['id'];
                    }
                }

                $resourceIndex++;

                // Stop adding media files if script takes too long to execute
                if ( $this->stateCheck->getTimeLeft() <= 2 * $this->stateCheck->maxImportTime() ) {
                    break;
                }

                // Stop adding if we reached limit of items per single request
                if ( ( $resourceIndex - $resourcesStart ) >= WP_JELLY_MAX_ENTRIES_PER_REQUEST ) {
                    break;
                }
            }
        }

        update_option( 'wpjelly_import_state_resources_data', $resourcesData );

        echo json_encode( array( 'success' => true, 'resourcesProcessed' => ( $resourceIndex - $resourcesStart ) ) );
        exit;
    }

    public function wpjellyReplaceResources() {
        $resourcesData = get_option( 'wpjelly_import_state_resources_data', array() );
        $resourcesPosts = get_option( 'wpjelly_import_state_resources_posts', array() );

        if ( !empty( $resourcesData ) && is_array( $resourcesData ) && !empty( $resourcesPosts ) && is_array( $resourcesPosts ) ) {
            foreach ( $resourcesPosts as $replacePostId ) {
                $meta = get_post_meta( $replacePostId, '_elementor_data', true );

                if ( is_string( $meta ) && ! empty( $meta ) ) {
                    $meta = json_decode( $meta, true );
                }

                $meta = $this->findAndReplaceFullMigrateImages( $meta, $resourcesData );
                $meta = wp_slash( wp_json_encode( $meta ) );
                update_post_meta( $replacePostId, '_elementor_data', $meta );
            }
        }
    }

    public function wpjellyMigrateFinish() {
        global $wpdb;

        $this->wpjellyReplaceResources();

        $upload_dir = wp_upload_dir();

        $mainId=$_POST['mainId'];
        $OceanfileName='oceanwp-'.$mainId.'.txt';
        $oceanUrl=$upload_dir['basedir'] . '/wpjelly-backup/'.$OceanfileName;
        $oceanwpOptions = file_get_contents($oceanUrl);
        $oceanwpOptionsList = preg_replace('/\\\\\"/', "\"", $oceanwpOptions);
        $oceanSettings = @unserialize($oceanwpOptionsList);
        if (function_exists('wp_update_custom_css_post') && isset($oceanSettings['wp_css']) && '' !== $oceanSettings['wp_css']) {
            wp_update_custom_css_post($oceanSettings['wp_css']);
        }
        if (isset($oceanSettings['wse_permalinkStructure']) && '' !== $oceanSettings['wse_permalinkStructure']) {
            update_option('permalink_structure', $oceanSettings['wse_permalinkStructure'], 'yes');
        }
        $wse_menuLocate = $oceanSettings['wse_menuLocate'];
        if (isset($oceanSettings['mods']) && count($oceanSettings['mods']) > 0) {
            if ('0' == json_last_error()) {
                foreach ($oceanSettings['mods'] as $mod => $value) {
                    if ($mod === 'nav_menu_locations') {
                        foreach ($value as $key => $val) {
                            $menuDetail = get_term_by('slug', $wse_menuLocate[$key], 'nav_menu');
                            if ( $menuDetail ) {
                                $value[$key] = $menuDetail->term_id;
                            }
                        }
                    }
                    if( $mod === 'ocean_center_header_left_menu') {
                        $termSlug=$oceanSettings['options']['ocean_center_header_left_menu'];
                        $new=$wpdb->get_var("SELECT term_id from `".$wpdb->prefix."terms` WHERE slug='".$termSlug."'");
                        $value=$new;
                    }
                    set_theme_mod($mod, $value);
                }
            }
        }
        
        $pageType = $oceanSettings['wse_FrontPageType'];
        update_option('show_on_front', $pageType, 'yes');
        if($pageType=='page')
        {
            $frontPage=$wpdb->get_var("SELECT post_id from `".$wpdb->prefix."postmeta` where meta_value='FrontPage'");
            update_option('page_on_front', $frontPage, 'yes');
            $blogpage=$wpdb->get_var("SELECT post_id from `".$wpdb->prefix."postmeta` where meta_value='BlogPage'");
            update_option('page_for_posts', $blogpage, 'yes');
        }
        
        if (isset($oceanSettings['wse_blogDesc']) && is_array($oceanSettings['wse_blogDesc']) && count($oceanSettings['wse_blogDesc']) > 0) {
            foreach ($oceanSettings['wse_blogDesc'] as $key => $value) {
                update_option($key, $value, 'yes');
            }
        }
        if(isset($oceanSettings['wse_wooPages']) && is_array($oceanSettings['wse_wooPages']) && count($oceanSettings['wse_wooPages'])>0)
        {
            foreach ($oceanSettings['wse_wooPages'] as $key => $value) {
                $woopage = get_page_by_path($value,OBJECT,'page');

                if ( $woopage ) {
                    update_option($key,$woopage->ID);
                }
            }
        }
        if(isset($oceanSettings['wse_elementorSettings']) && is_array($oceanSettings['wse_elementorSettings']) && count($oceanSettings['wse_elementorSettings'])>0)
        {
            foreach($oceanSettings['wse_elementorSettings'] as $key=>$value)
            {
                update_option($key, $value, 'yes');
            }
        }
        $widgetsfileName='widgets-'.$mainId.'.txt';
        $widgetsUrl=$upload_dir['basedir'] . '/wpjelly-backup/'.$widgetsfileName;
        $widgets = file_get_contents($widgetsUrl);
        $this->import_widgets($widgets);
        global $wp_rewrite;
        $permalink_structure='/%postname%/';
        if ( ! empty( $permalink_structure ) ) {
			$permalink_structure = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $permalink_structure ) );
			if ( $prefix && $blog_prefix )
				$permalink_structure = $prefix . preg_replace( '#^/?index\.php#', '', $permalink_structure );
			else
				$permalink_structure = $blog_prefix . $permalink_structure;
		}

		$permalink_structure = sanitize_option( 'permalink_structure', $permalink_structure );

		$wp_rewrite->set_permalink_structure( $permalink_structure );
        $wp_rewrite->flush_rules();

        $dir = $upload_dir['basedir'] . '/wpjelly-backup/';

        $files = scandir( $dir );
        foreach ( $files as $file ) {
            if ( '.' !== $file && '..' !== $file )
            {
                if ( is_file( $dir . $file ) ) {
                    unlink($dir . $file);
                }
            }
        }

        $plugins_status = '';

        if ( !$this->pluginActivationSuccessed( $plugins_status ) ) {
            if ( !empty( $plugins_status ) ) {
                $message = '<p>' . $plugins_status . '</p>' . '<p>' . __( 'All done.' ) . ' <a href="' . admin_url() . '">' . __( 'Navigate to dashboard!' ) . '</a></p>' . '<p>' . __( 'Thank You for using WpJelly' ) . '</p>';
            } else {
                $message = '<p>' . __( 'There was issue with installing required plugins.' ) . ' <a href="' . admin_url() . '">' . __( 'Navigate to dashboard!' ) . '</a></p>' . '<p>' . __( 'Thank You for using WpJelly' ) . '</p>';
            }
        } else {
            $message = '<p>' . __( 'All done.' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!' ) . '</a></p>' . '<p>' . __( 'Thank You for using WpJelly' ) . '</p>';
        }

        $this->cleanupResources();

        $this->reportFinish();

        echo json_encode( array( 'success' => true, 'message' => $message ) );
        exit;
    }

    public function cleanupResources() {
        delete_option( 'wpjelly_import_state_resources_data' );
        delete_option( 'wpjelly_import_state_resources_posts' );
    }

    public function import_widgets($widgets) {
        global $wp_registered_sidebars;
        $widgets = preg_replace('/\\\\\"/', "\"", $widgets);
        $data = json_decode($widgets);
        $available_widgets = $this->wpjelly_available_widgets();
        $widget_instances = array();
        foreach ($available_widgets as $widget_data) {
            $widget_instances[$widget_data['id_base']] = get_option('widget_' . $widget_data['id_base']);
        }
        // Begin results.
        $results = array();
        foreach ($data as $sidebar_id => $widgets) {
            // Skip inactive widgets (should not be in export file).
            if ('wp_inactive_widgets' === $sidebar_id) {
                continue;
            }
            // Check if sidebar is available on this site.
            // Otherwise add widgets to inactive, and say so.
            if (isset($wp_registered_sidebars[$sidebar_id])) {
                $sidebar_available = true;
                $use_sidebar_id = $sidebar_id;
                $sidebar_message_type = 'success';
                $sidebar_message = '';
            } else {
                $sidebar_available = false;
                $use_sidebar_id = 'wp_inactive_widgets'; // Add to inactive if sidebar does not exist in theme.
                $sidebar_message_type = 'error';
                $sidebar_message = '';
            }
            // Result for sidebar
            // Sidebar name if theme supports it; otherwise ID.
            $results[$sidebar_id]['name'] = !empty($wp_registered_sidebars[$sidebar_id]['name']) ? $wp_registered_sidebars[$sidebar_id]['name'] : $sidebar_id;
            $results[$sidebar_id]['message_type'] = $sidebar_message_type;
            $results[$sidebar_id]['message'] = $sidebar_message;
            $results[$sidebar_id]['widgets'] = array();
            // Loop widgets.
            foreach ($widgets as $widget_instance_id => $widget) {
                $fail = false;
                // Get id_base (remove -# from end) and instance ID number.
                $id_base = preg_replace('/-[0-9]+$/', '', $widget_instance_id);
                $instance_id_number = str_replace($id_base . '-', '', $widget_instance_id);
                // Does site support this widget?
                if (!$fail && !isset($available_widgets[$id_base])) {
                    $fail = true;
                    $widget_message_type = 'error';
                    $widget_message = '';
                }
                $widget = json_decode(wp_json_encode($widget), true);
                if (!$fail && isset($widget_instances[$id_base])) {
                    $sidebars_widgets = get_option('sidebars_widgets');
                    $sidebar_widgets = isset($sidebars_widgets[$use_sidebar_id]) ? $sidebars_widgets[$use_sidebar_id] : array();
                    $single_widget_instances = !empty($widget_instances[$id_base]) ? $widget_instances[$id_base] : array();
                    foreach ($single_widget_instances as $check_id => $check_widget) {
                        if (in_array("$id_base-$check_id", $sidebar_widgets, true) && (array)$widget === $check_widget) {
                            $fail = true;
                            $widget_message_type = 'warning';
                            $widget_message = 'Widget already exists';
                            break;
                        }
                    }
                }
                if (!$fail) {
                    // Add widget instance
                    $single_widget_instances = get_option('widget_' . $id_base); // All instances for that widget ID base, get fresh every time.
                    $single_widget_instances = !empty($single_widget_instances) ? $single_widget_instances : array('_multiwidget' => 1, // Start fresh if have to.
                    );
                    $single_widget_instances[] = $widget; // Add it.
                    $new_instance_id_number = key($single_widget_instances);
                    if ('0' === strval($new_instance_id_number)) {
                        $new_instance_id_number = 1;
                        $single_widget_instances[$new_instance_id_number] = $single_widget_instances[0];
                        unset($single_widget_instances[0]);
                    }
                    // Move _multiwidget to end of array for uniformity.
                    if (isset($single_widget_instances['_multiwidget'])) {
                        $multiwidget = $single_widget_instances['_multiwidget'];
                        unset($single_widget_instances['_multiwidget']);
                        $single_widget_instances['_multiwidget'] = $multiwidget;
                    }
                    // Update option with new widget.
                    update_option('widget_' . $id_base, $single_widget_instances);
                    // Assign widget instance to sidebar.
                    // Which sidebars have which widgets, get fresh every time.
                    $sidebars_widgets = get_option('sidebars_widgets');
                    // Avoid rarely fatal error when the option is an empty string
                    // https://github.com/churchthemes/widget-importer-exporter/pull/11.
                    if (!$sidebars_widgets) {
                        $sidebars_widgets = array();
                    }
                    // Use ID number from new widget instance.
                    $new_instance_id = $id_base . '-' . $new_instance_id_number;
                    // Add new instance to sidebar.
                    $sidebars_widgets[$use_sidebar_id][] = $new_instance_id;
                    // Save the amended data.
                    update_option('sidebars_widgets', $sidebars_widgets);
                    // After widget import action.
                    $after_widget_import = array('sidebar' => $use_sidebar_id, 'sidebar_old' => $sidebar_id, 'widget' => $widget, 'widget_type' => $id_base, 'widget_id' => $new_instance_id, 'widget_id_old' => $widget_instance_id, 'widget_id_num' => $new_instance_id_number, 'widget_id_num_old' => $instance_id_number,);
                    do_action('wie_after_widget_import', $after_widget_import);
                    // Success message.
                    if ($sidebar_available) {
                        $widget_message_type = 'success';
                        $widget_message = 'Imported';
                    } else {
                        $widget_message_type = 'warning';
                        $widget_message = 'Imported to Inactive';
                    }
                }
                // Result for widget instance
                $results[$sidebar_id]['widgets'][$widget_instance_id]['name'] = isset($available_widgets[$id_base]['name']) ? $available_widgets[$id_base]['name'] : $id_base; // Widget name or ID if name not available (not supported by site).
                $results[$sidebar_id]['widgets'][$widget_instance_id]['title'] = !empty($widget['title']) ? $widget['title'] : 'No Title'; // Show "No Title" if widget instance is untitled.
                $results[$sidebar_id]['widgets'][$widget_instance_id]['message_type'] = $widget_message_type;
                $results[$sidebar_id]['widgets'][$widget_instance_id]['message'] = $widget_message;
            }
        }
    }
    public function wpjelly_available_widgets() {
        global $wp_registered_widget_controls;
        $widget_controls = $wp_registered_widget_controls;
        $available_widgets = array();
        foreach ($widget_controls as $widget) {
            // No duplicates.
            if (!empty($widget['id_base']) && !isset($available_widgets[$widget['id_base']])) {
                $available_widgets[$widget['id_base']]['id_base'] = $widget['id_base'];
                $available_widgets[$widget['id_base']]['name'] = $widget['name'];
            }
        }
        return apply_filters('wpjelly_available_widgets', $available_widgets);
    }

    public function pluginActivationSuccessed( &$status )
    {
        $error_list = array();

        $site_requires_php_upgrade = false;
        $site_requires_wp_upgrade = false;

        $plugins_list = array(
            'Elementor' => 'elementor/elementor.php',
            'Ocean Extra' => 'ocean-extra/ocean-extra.php',
            'WooCommerce' => 'woocommerce/woocommerce.php',
            'WPforms Lite' => 'wpforms-lite/wpforms.php'
            );

        $active_plugins = (array) get_option( 'active_plugins', array() );

        foreach( $plugins_list as $plugin_name => $plugin ) {
            if ( !in_array( $plugin, $active_plugins ) ) {
                $readme_file = WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/readme.txt';
                $plugin_data = array(
                    'requires'     => '',
                    'requires_php' => '',
                );

                if ( file_exists( $readme_file ) ) {
                    $plugin_data = get_file_data(
                        $readme_file,
                        array(
                            'requires'     => 'Requires at least',
                            'requires_php' => 'Requires PHP',
                        ),
                        'plugin'
                    );
                }

                $plugin_data = array_merge( $plugin_data, get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ) );

                // Check for headers in the plugin's PHP file, give precedence to the plugin headers.
                $plugin_data['requires']     = ! empty( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : $plugin_data['requires'];
                $plugin_data['requires_php'] = ! empty( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : $plugin_data['requires_php'];

                $plugin_data['wp_compatible']  = empty( $plugin_data['requires'] ) || version_compare( get_bloginfo( 'version' ), $plugin_data['requires'], '>=' );
                $plugin_data['php_compatible'] = empty( $plugin_data['requires_php'] ) || version_compare( phpversion(), $plugin_data['requires_php'], '>=' );

                if ( ! $plugin_data['wp_compatible'] && ! $plugin_data['php_compatible'] ) {
                    $error_list[] = sprintf( __( '<strong>Warning:</strong> %s requies PHP %s and WordPress %s, but you have PHP version %s and WordPress version %s installed.' ),
                    $plugin_name, $plugin_data['requires_php'], $plugin_data['requires'], phpversion(), get_bloginfo( 'version' ) );

                    $site_requires_php_upgrade = true;
                    $site_requires_wp_upgrade = true;
                } else if ( ! $plugin_data['php_compatible'] ) {
                    $error_list[] = sprintf( __( '<strong>Warning:</strong> %s requies PHP %s, but you have version %s installed.' ),
                    $plugin_name, $plugin_data['requires_php'], phpversion() );

                    $site_requires_php_upgrade = true;
                } else if ( ! $plugin_data['wp_compatible'] ) {
                    $error_list[] = sprintf( __( '<strong>Warning:</strong> %s requies WordPress %s, but you have version %s installed.' ),
                    $plugin_name, $plugin_data['requires'], get_bloginfo( 'version' ) );

                    $site_requires_wp_upgrade = true;
                } else {
                    $error_list[] = sprintf( __( '<strong>Warning:</strong> %s plugin was not activated.' ), $plugin_name );
                }
            }
        }

        if ( !empty( $error_list ) && ( count( $error_list ) > 0 ) ) {
            $status = '<p>' . implode( '<br/>', $error_list ) . '</p>';

            if ( $site_requires_php_upgrade && $site_requires_wp_upgrade ) {
                $status .= '<p><strong>' . __( 'We did our best to import, but please upgrade PHP and WordPress and re-import the site.' ) . '</strong></p>';
            } else if ( $site_requires_php_upgrade ) {
                $status .= '<p><strong>' . __( 'We did our best to import, but please upgrade PHP and re-import the site.' ) . '</strong></p>';
            } else if ( $site_requires_wp_upgrade ) {
                $status .= '<p><strong>' . __( 'We did our best to import, but please upgrade WordPress and re-import the  site.' ) . '</strong></p>';
            } else {
                $status .= '<p><strong>' . __( 'We did our best to import, however installation may not be complete and you may re-import the site.' ) . '</strong></p>';
            }

            return false;
        }

        return true;
    }

    public function trackSeed($seed)
    {
        $seed['log'] = 'FREE-SITES';
        $seed['ip'] = $this->get_ip_address();

        $keys=array('log','email','path','source','name','ip');
        foreach ($keys as $key) {
            if(!isset($seed[$key]))
            {
                return false;
            }
        }
        $migrationUrl=get_site_url();
        $seed['migrationUrl']=$migrationUrl;
        $url='https://wpjelly.com/wp-json/track/v1/track';
        $request = wp_remote_post($url, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode($seed),
            'method'      => 'POST',
            'data_format' => 'body',
        ));
        if( is_wp_error( $request ) ) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body( $request );

        $data = json_decode( $body );
        if( ! empty( $data ) ) {
            return 1;
        }
    }

    public function reportStart( $info )
    {
        $info['ip'] = $this->get_ip_address();

        $keys = array( 'email', 'path', 'source', 'name', 'ip' );

        foreach ( $keys as $key ) {
            if ( !isset( $info[$key] ) ) {
                return false;
            }
        }

        $info['site_url'] = get_site_url();

        $url = 'https://wpjelly.com/wp-json/audit/v1/start';

        $request = wp_remote_post( $url, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode( $info )
        ));
    }

    public function reportFinish()
    {
        $info = array();

        $info['site_url'] = get_site_url();

        $url = 'https://wpjelly.com/wp-json/audit/v1/finish';

        $request = wp_remote_post($url, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode($info)
        ));
    }

    public function isRemoteImage( $value ) {
        if ( !empty( $value ) ) {
            $dot_pos = strrpos( $value, "." );

            if ( $dot_pos !== FALSE ) {
                $extension = substr( $value, $dot_pos + 1 );

                if ( !empty( $extension ) ) {
                    $extension = strtolower( $extension );

                    if (( $extension == 'jpg' ) ||
                        ( $extension == 'jpeg' ) ||
                        ( $extension == 'png' ) ||
                        ( $extension == 'gif' ) ||
                        ( $extension == 'pdf' ) ||
                        ( $extension == 'bmp' ) ||
                        ( $extension == 'svg' ) ||
                        ( $extension == 'ico' ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function addFileSuffix( $filename, $suffix ) {
        if ( !empty( $filename ) ) {
            $dot_pos = strrpos( $filename, "." );

            if ( $dot_pos !== FALSE ) {
                $file_name = substr( $filename, 0, $dot_pos );
                $file_ext = substr( $filename, $dot_pos + 1 );

                return "{$file_name}-{$suffix}.{$file_ext}";
            }
        }

        return $filename;
    }

    public function getMediaByName( $filename ) {
        $media_post = get_page_by_title( $filename, OBJECT, 'attachment' );

        if ( $media_post != NULL ) {
            return $media_post;
        }

        $dot_pos = strrpos( $filename, "." );

        if ( $dot_pos !== FALSE ) {
            $file_title = substr( $filename, 0, $dot_pos );

            $media_posts = get_posts( array( 'post_type' => 'attachment', 'title' => $file_title ) );

            if ( $media_posts ) {
                foreach ( $media_posts as $item ) {
                    if ( !empty( $item->ID ) ) {
                        $attached_filename = get_attached_file( $item->ID );
                        if ( !empty( $attached_filename ) ) {
                            if (( basename( $attached_filename ) == $filename ) ||
                                ( basename( $attached_filename ) == $this->addFileSuffix( $filename, 'scaled' ) ) ||
                                ( basename( $attached_filename ) == $this->addFileSuffix( $filename, 'rotated' ) ) ) {
                                return $item;
                            }
                        }
                    }
                }
            }
        }

        return NULL;
    }

    public function migrateSingleImage( $url )
    {
        $filename = basename( $url );

        $oriFile = $this->getMediaByName( $filename );

        if ( $oriFile != NULL ) {
            $new_attachment = [
                'id' => $oriFile->ID,
                'url' => wp_get_attachment_url( $oriFile->ID )
            ];

            return $new_attachment;
        } else {
            $import_request = wp_safe_remote_get( $url, array( 'timeout' => ceil( $this->stateCheck->getTimeLeft() / 2 ) ) );

            if ( is_wp_error( $import_request ) ) {
                return $import_request;
            }

            $file_content = wp_remote_retrieve_body( $import_request );

            if ( !empty( $file_content ) ) {
                $upload = wp_upload_bits( $filename, null, $file_content );

                $post = [
                    'post_title' => $filename,
                    'guid' => $upload['url'],
                ];

                $info = wp_check_filetype( $upload['file'] );

                if ( $info ) {
                    $post['post_mime_type'] = $info['type'];
                    $post_id = wp_insert_attachment( $post, $upload['file'] );
                    wp_update_attachment_metadata(
                        $post_id,
                        wp_generate_attachment_metadata( $post_id, $upload['file'] )
                    );

                    update_post_meta( $post_id, '_elementor_source_image_hash', sha1( $url ) );

                    $new_attachment = [
                        'id' => $post_id,
                        'url' => $upload['url'],
                    ];

                    return $new_attachment;
                }
            }
        }

        return NULL;
    }

    public function findAndReplaceFullMigrateImages( &$array, $source ) {
        foreach( $array as $key => &$value ) { 
            if ( is_array( $value ) ) { 
                $this->findAndReplaceFullMigrateImages( $value, $source ); 
            } else {
                if ( $key == 'url' && ( !empty( $source[$value]['id'] ) ) && ( !empty( $source[$value]['url'] ) ) ) {
                    if ( !empty( $array['id'] ) ) {
                        $array['id'] = $source[$value]['id'];
                    }
                    $array[$key] = $source[$value]['url'];                    
                    break;
                }
            } 
        }

        return $array;
    }

}
