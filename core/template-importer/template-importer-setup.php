<?php
defined('ABSPATH') || exit;
class bsm_export {

	protected $loader;
	protected $bsm_export;
	protected $version;
	
	public function __construct() {
		if ( defined( 'WP_JELLY_VERSION' ) ) {
			$this->version = WP_JELLY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->bsm_export = 'bsm_export';

		$this->load_dependencies();
		$this->define_admin_hooks();
		add_action('admin_head',array($this,'StaticStyle'));

	}
	public function StaticStyle()
	{
		if(isset($_GET['page']) && $_GET['page']=='wpjelly-template-importer-menu')
		{
			echo '<style>.update-nag, .updated, .error, .is-dismissible { display: none; }</style>';
		}
		
	}
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once WP_JELLY_DIR. 'core/template-importer/inc/class-bsm_export-core.php';


		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once WP_JELLY_DIR. 'core/template-importer/inc/class-bsm_export-admin.php';

		/**
		* Setting Admin setting
		*/
		require_once WP_JELLY_DIR. 'core/template-importer/inc/admin-settings.php';

		/**
		* Setting Admin core Functions
		*/
		require_once WP_JELLY_DIR. 'core/template-importer/inc/admin-function-core.php';
		
		$this->loader = new bsm_export_core();

	}
	private function define_admin_hooks() {

		$plugin_admin = new BSME_Admin( $this->get_plugin_name(), $this->get_version() );		
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

	}
	private function define_public_hooks() {

		$plugin_public = new BSME_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}
	public function run() {
		$this->loader->run();
	}
	public function get_plugin_name() {
		return $this->bsm_export;
	}
	public function get_loader() {
		return $this->loader;
	}
	public function get_version() {
		return $this->version;
	}

}
