<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class BSME_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$status = get_option('wpjellyChecklistStatus');
		if($status=='enable')
		{
			if(isset($_GET['page']) && $_GET['page']=='wpjelly-template-importer-menu')
			{
				add_action('admin_enqueue_scripts',array($this,'enqueue_styles'));
				add_action('admin_enqueue_scripts',array($this,'enqueue_scripts'));
			}
		}
		else
		{
			if(isset($_GET['page']) && $_GET['page']=='wpjelly-theme-pannel')
			{
				add_action('admin_enqueue_scripts',array($this,'enqueue_styles'));
				add_action('admin_enqueue_scripts',array($this,'enqueue_scripts'));
			}
		}


	}
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function enqueue_styles($hook) {
		$status=get_option('wpjellyChecklistStatus');
		$flag=array('toplevel_page_wpjelly-theme-pannel','wpjelly_page_wpjelly-template-importer-menu');
		if(!in_array($hook, $flag)) {
                return;
        }

		wp_enqueue_style( 'bsme-bootstrap-css-add', WP_JELLY_CSS. '/bootstrap.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'bsme-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'bsme-custom', WP_JELLY_CSS. '/bsm_custom.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'bsme-bootstrap-js-add', WP_JELLY_JS. '/bootstrap.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script('wpjelly-export-admin', WP_JELLY_JS.'/export-admin.js',array('jquery'), $this->version, false);
		$bsmarg = array(
		    'post_type' => 'elementor_library',
		);
		$getallmetaquery = get_posts($bsmarg);
		$metaData = array();
		foreach($getallmetaquery as $key => $value){
		    $metaData[] = get_post_meta($value->ID,'_bsm_mainelement', true);
		}
		$metaDataEncoded=json_encode($metaData);
		$siteUrl=get_site_url();
		wp_localize_script('wpjelly-export-admin', 'wpjellyTemplateControl', array(
		   'meta' => $metaDataEncoded,
		   'ajaxurl' => admin_url('admin-ajax.php'),
		   'backurl' => admin_url('admin.php?page=wpjelly-template-importer-menu'),
		   'siteUrl' => $siteUrl,
		   'imageUrl' => WP_JELLY_IMG
		));
	}
}
?>