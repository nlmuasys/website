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

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$wpjellyclass_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $wpjellyclass_wp_importer ) )
		require $wpjellyclass_wp_importer;
}

/**
 * wpJelly Xml Import Setup
 */
if ( class_exists( 'WP_Importer' ) ) {
	class wpJellyXmlImportSetup extends WP_Importer
	{
		public $wpJellymax_wxr_version = 1.0;

		public $id;

		public $version;
		public $authors = array();
		public $posts = array();
		public $terms = array();
		public $categories = array();
		public $tags = array();
		public $base_url = '';

		// mappings from old information to new
		public $processed_authors = array();
		public $author_mapping = array();
		public $processed_terms = array();
		public $processed_posts = array();
		public $post_orphans = array();
		public $processed_menu_items = array();
		public $menu_item_orphans = array();
		public $missing_menu_items = array();

		public $fetch_attachments = false;
		public $url_remap = array();
		public $featured_images = array();
		public $errorsXml = array();
		public $mainUrlInfo = '';
		public $blogUrlInfo = '';
		public $mainIdData;
		public $element = array();

		private $stateCheck = null;

		public function __construct( $stateCheck ) {
			$this->stateCheck = $stateCheck;
		}

		public function uploadXmlFileStart($xmlUrl, $mainUrl, $blogUrl, $mainId, &$mediaCount)
		{
			$this->mainUrlInfo=$mainUrl;
			$this->blogUrlInfo=$blogUrl;
			$this->mainIdData=$mainId;

			$xmlFile = wp_remote_retrieve_body( wp_remote_get( $xmlUrl ) );

			if ( empty( $xmlFile ) ) {
				$this->errorsXml[]='<p><strong>Sorry, failed to fetch remote XML file.</strong></p>';

				return FALSE;
			}

			$filename = basename( $xmlUrl );
			$upload_dir = wp_upload_dir();
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			  $file = $upload_dir['path'] . '/' . $filename;
			}
			else {
			  $file = $upload_dir['basedir'] . '/' . $filename;
			}

			file_put_contents( $file, $xmlFile );

			$wp_filetype = wp_check_filetype( $filename, null );
            
			$attachment = array(
			  'post_mime_type' => $wp_filetype['type'],
			  'post_title' => sanitize_file_name( $filename ),
			  'post_content' => '',
			  'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $file );
			wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', array( $attach_id ) );
 
		    $xmlFileImportdata=array(
		        'file' => $file,
		        'id'   => $attach_id,
		    );
			if ( isset( $xmlFileImportdata['error'] ) ) {
				$this->errorsXml[]= '<p><strong>Sorry, there has been an error.</strong><br />'.esc_html( $xmlFileImportdata['error'] ) . '</p>';
			} else if ( ! file_exists( $xmlFileImportdata['file'] ) ) {
				$this->errorsXml[]= '<p><strong>Sorry, there has been an error.</strong><br />The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.</p>';
			}

			$this->id = (int) $xmlFileImportdata['id'];
			$import_data = $this->parse( $xmlFileImportdata['file'] ,$this->mainIdData,$this->mainUrlInfo);

			if ( is_wp_error( $import_data ) ) {
				$this->errorsXml[]='<p><strong>Sorry, there has been an error.</strong><br />'.esc_html( $import_data->get_error_message() ) . '</p>';
			} else {
				$this->get_authors_from_import( $import_data );
			}

			if ( count( $this->errorsXml ) > 0 ) {
				$this->saveImportState();

				return FALSE;
			} else {
				$this->fetch_attachments = 1;
				$this->id = (int) $xmlFileImportdata['id'];
				$file = get_attached_file( $this->id );

				$mediaCount = $this->importStart( $file );

				$this->saveImportState();

				return TRUE;
			}
		}

		public function uploadXmlFileMedia( $xmlUrl, $mainUrl, $blogUrl, $mainId, $mediaStart, &$mediaProcessed )
		{
			$this->restoreImportState();

			$this->mainUrlInfo = $mainUrl;
			$this->blogUrlInfo = $blogUrl;
			$this->mainIdData = $mainId;

			if ( !$this->id ) {
				return FALSE;
			}

			$xml_path = get_attached_file($this->id);

			$import_data = $this->parse( $xml_path, $this->mainIdData, $this->mainUrlInfo);

			if ( is_wp_error( $import_data ) ) {
				$this->errorsXml[]='<p><strong>Sorry, there has been an error.</strong><br />'.esc_html( $import_data->get_error_message() ) . '</p>';
			}

			$this->get_authors_from_import( $import_data );

			if ( count( $this->errorsXml ) > 0) {
				$this->saveImportState();

				return FALSE;
			} else {
				$this->fetch_attachments = 1;
				$file = get_attached_file( $this->id );

				$mediaProcessed = $this->importMedia( $file, $mediaStart );

				$this->saveImportState();

				return TRUE;
			}
		}
		
		public function uploadXmlFileFinish($xmlUrl, $mainUrl, $blogUrl, $mainId)
		{
			$this->restoreImportState();

			$this->mainUrlInfo = $mainUrl;
			$this->blogUrlInfo = $blogUrl;
			$this->mainIdData = $mainId;

			if ( !$this->id ) {
				return FALSE;
			}

			$xml_path = get_attached_file($this->id);

			$import_data = $this->parse( $xml_path, $this->mainIdData, $this->mainUrlInfo);

			if ( is_wp_error( $import_data ) ) {
				$this->errorsXml[]='<p><strong>Sorry, there has been an error.</strong><br />'.esc_html( $import_data->get_error_message() ) . '</p>';
			}

			$this->get_authors_from_import( $import_data );

			if ( count( $this->errorsXml ) > 0) {
				$this->removeImportState();

				return FALSE;
			} else {
				$this->fetch_attachments = 1;
				$file = get_attached_file( $this->id );

				$this->importFinish( $file );

				$this->removeImportState();

				return TRUE;
			}
		}

		public function parse( $file ,$id=NULL,$mainUrl=NULL)
		{
			$parser = new wpjellyXmlParser();
			return $parser->parse( $file ,$id,$mainUrl);
		}

		public function get_authors_from_import( $import_data )
		{
			if ( ! empty( $import_data['authors'] ) ) {
				$this->authors = $import_data['authors'];
				// no author information, grab it from the posts
			} else {
				foreach ( $import_data['posts'] as $post ) {
					$login = sanitize_user( $post['post_author'], true );
					if ( empty( $login ) ) {
						$this->errorsXml[]='Failed to import author %s. Their posts will be attributed to the current user.'.esc_html( $post['post_author'] ).'<br />';
						continue;
					}

					if ( ! isset($this->authors[$login]) )
						$this->authors[$login] = array(
							'author_login' => $login,
							'author_display_name' => $post['post_author']
						);
				}
			}
		}

		public function saveImportState() {
			$importState = array(
				'id' => $this->id,
				'processed_posts' => $this->processed_posts,
				'post_orphans' => $this->post_orphans,
				'processed_authors' => $this->processed_authors,
				'featured_images' => $this->featured_images,
				'url_remap' => $this->url_remap,
				'processed_terms' => $this->processed_terms,
				'missing_menu_items' => $this->missing_menu_items,
				'processed_menu_items' => $this->processed_menu_items,
				'menu_item_orphans' => $this->menu_item_orphans
			);

			update_option( 'wpjelly_import_state', $importState );
		}

		public function getImportValue( $array, $field, $default = array() ) {
			if ( !empty( $array[ $field ] ) ) {
				return $array[ $field ];
			} else {
				return $default;
			}
		}

		public function restoreImportState() {
			$importState = get_option( 'wpjelly_import_state', array() );

			$this->id = $this->getImportValue( $importState, 'id', 0 );
			$this->processed_posts = $this->getImportValue( $importState, 'processed_posts', array() );
			$this->post_orphans = $this->getImportValue( $importState, 'post_orphans', array() );
			$this->processed_authors = $this->getImportValue( $importState, 'processed_authors', array() );
			$this->featured_images = $this->getImportValue( $importState, 'featured_images', array() );
			$this->url_remap = $this->getImportValue( $importState, 'url_remap', array() );
			$this->processed_terms = $this->getImportValue( $importState, 'processed_terms', array() );
			$this->missing_menu_items = $this->getImportValue( $importState, 'missing_menu_items', array() );
			$this->processed_menu_items = $this->getImportValue( $importState, 'processed_menu_items', array() );
			$this->menu_item_orphans = $this->getImportValue( $importState, 'menu_item_orphans', array() );
		}

		public function removeImportState() {
			delete_option( 'wpjelly_import_state' );
		}

		public function importStart( $file )
		{
			add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

			$this->import_start( $file );

			wp_suspend_cache_invalidation( true );
			$this->process_categories();
			$this->process_tags();
			$this->process_terms();
			$this->process_posts();
			wp_suspend_cache_invalidation( false );

			$mediaCount = 0;

			foreach ( $this->posts as $post ) {
                if ($post['post_type'] == 'attachment') {
					$mediaCount++;
				}
			}

			return $mediaCount;
		}

		public function importMedia( $file, $mediaStart )
		{
			add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

			$this->import_start( $file );

			wp_suspend_cache_invalidation( true );
			$mediaProcessed = $this->process_attachmentMigration( $mediaStart );
			wp_suspend_cache_invalidation( false );

			return $mediaProcessed;
		}

		public function importFinish( $file )
		{
			add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

			$this->import_start( $file );

			wp_suspend_cache_invalidation( true );
			$this->process_navigation();
			wp_suspend_cache_invalidation( false );

			// update incorrect/missing information in the DB
 			$this->backfill_parents();
 			$this->backfill_attachment_urls();
			$this->remap_featured_images();
			$this->import_end();
		}

		public function is_valid_meta_key( $key )
		{
			if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata', '_edit_lock' ) ) )
				return false;
			return $key;
		}
		public function bump_request_timeout( $val )
		{
			return 300;
		}
		public function import_start( $file )
		{
			if ( ! is_file($file) ) {
				$this->errorsXml[]= '<p><strong>Sorry, there has been an error.</strong><br />The file does not exist, please try again.</p>';
			}
       
			$import_data = $this->parse( $file ,$this->mainIdData,$this->mainUrlInfo);
			//$this->process_blogOptions($import_data['options']);
			if ( is_wp_error( $import_data ) ) {
				$this->errorsXml[]= '<p><strong>Sorry, there has been an error.</strong><br />'.esc_html( $import_data->get_error_message() ) . '</p>';
			}

			$this->version = $import_data['version'];
			$this->get_authors_from_import( $import_data );
			$this->posts = $import_data['posts'];
			$this->terms = $import_data['terms'];
			$this->categories = $import_data['categories'];
			$this->tags = $import_data['tags'];
			$this->base_url = esc_url( $import_data['base_url'] );
		
			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );

			do_action( 'import_start' );
		}

		public function process_blogOptions($options)
		{
			foreach ($options as $option) {
				update_option($option['option_name'],$option['option_value'],$option['autoload']);
			}
		}
		public function process_categories()
		{
			require_once( ABSPATH . '/wp-admin/includes/taxonomy.php');
			$this->categories = apply_filters( 'wp_import_categories', $this->categories );

			if ( empty( $this->categories ) )
				return;

			foreach ( $this->categories as $cat ) {
				// if the category already exists leave it alone
				$term_id = term_exists( $cat['category_nicename'], 'category' );
				if ( $term_id ) {
					if ( is_array($term_id) ) $term_id = $term_id['term_id'];
					if ( isset($cat['term_id']) )
						$this->processed_terms[intval($cat['term_id'])] = (int) $term_id;
					continue;
				}

				$category_parent = empty( $cat['category_parent'] ) ? 0 : category_exists( $cat['category_parent'] );
				$category_description = isset( $cat['category_description'] ) ? $cat['category_description'] : '';
				$catarr = array(
					'category_nicename' => $cat['category_nicename'],
					'category_parent' => $category_parent,
					'cat_name' => $cat['cat_name'],
					'category_description' => $category_description
				);
				$catarr = wp_slash( $catarr );

				$id = wp_insert_category( $catarr );
				if ( ! is_wp_error( $id ) ) {
					if ( isset($cat['term_id']) )
						$this->processed_terms[intval($cat['term_id'])] = $id;
				} else {
					$this->errorsXml[]='Failed to import category'.esc_html($cat['category_nicename']);
					continue;
				}

				$this->process_termmeta( $cat, $id['term_id'] );
			}

			unset( $this->categories );
		}
		public function process_tags()
		{
			$this->tags = apply_filters( 'wp_import_tags', $this->tags );

			if ( empty( $this->tags ) )
				return;

			foreach ( $this->tags as $tag ) {
				// if the tag already exists leave it alone
				$term_id = term_exists( $tag['tag_slug'], 'post_tag' );
				if ( $term_id ) {
					if ( is_array($term_id) ) $term_id = $term_id['term_id'];
					if ( isset($tag['term_id']) )
						$this->processed_terms[intval($tag['term_id'])] = (int) $term_id;
					continue;
				}

				$tag = wp_slash( $tag );
				$tag_desc = isset( $tag['tag_description'] ) ? $tag['tag_description'] : '';
				$tagarr = array( 'slug' => $tag['tag_slug'], 'description' => $tag_desc );

				$id = wp_insert_term( $tag['tag_name'], 'post_tag', $tagarr );
				if ( ! is_wp_error( $id ) ) {
					if ( isset($tag['term_id']) )
						$this->processed_terms[intval($tag['term_id'])] = $id['term_id'];
				} else {
					$this->errorsXml[]='Failed to import post tag'.esc_html($tag['tag_name']) ;
					continue;
				}

				$this->process_termmeta( $tag, $id['term_id'] );
			}

			unset( $this->tags );
		}
		public function process_terms()
		{
			$this->terms = apply_filters( 'wp_import_terms', $this->terms );

			if ( empty( $this->terms ) )
				return;

			foreach ( $this->terms as $term ) {
				// if the term already exists in the correct taxonomy leave it alone
				$term_id = term_exists( $term['slug'], $term['term_taxonomy'] );
				if ( $term_id ) {
					if ( is_array($term_id) ) $term_id = $term_id['term_id'];
					if ( isset($term['term_id']) )
						$this->processed_terms[intval($term['term_id'])] = (int) $term_id;
					continue;
				}

				if ( empty( $term['term_parent'] ) ) {
					$parent = 0;
				} else {
					$parent = term_exists( $term['term_parent'], $term['term_taxonomy'] );
					if ( is_array( $parent ) ) $parent = $parent['term_id'];
				}
				$term = wp_slash( $term );
				$description = isset( $term['term_description'] ) ? $term['term_description'] : '';
				$termarr = array( 'slug' => $term['slug'], 'description' => $description, 'parent' => intval($parent) );

				$id = wp_insert_term( $term['term_name'], $term['term_taxonomy'], $termarr );
				if ( ! is_wp_error( $id ) ) {
					if ( isset($term['term_id']) )
						$this->processed_terms[intval($term['term_id'])] = $id['term_id'];
				} else {
					$this->errorsXml[]='Failed to import taxonomy';
					continue;
				}

				$this->process_termmeta( $term, $id['term_id'] );
			}

			unset( $this->terms );
		}
		protected function process_termmeta( $term, $term_id )
		{
			if ( ! isset( $term['termmeta'] ) ) {
				$term['termmeta'] = array();
			}

			$term['termmeta'] = apply_filters( 'wp_import_term_meta', $term['termmeta'], $term_id, $term );

			if ( empty( $term['termmeta'] ) ) {
				return;
			}

			foreach ( $term['termmeta'] as $meta ) {
				$key = apply_filters( 'import_term_meta_key', $meta['key'], $term_id, $term );
				if ( ! $key ) {
					continue;
				}

				// Export gets meta straight from the DB so could have a serialized string
				$value = maybe_unserialize( $meta['value'] );

				add_term_meta( $term_id, $key, $value );
				do_action( 'import_term_meta', $term_id, $key, $value );
			}
		}
		 
		public function process_posts()
		{
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
			$this->posts = apply_filters( 'wp_import_posts', $this->posts );

			foreach ( $this->posts as $post ) {
				$post = apply_filters( 'wp_import_post_data_raw', $post );
				$exclude=array('attachment','nav_menu_item');
                if(!in_array($post['post_type'], $exclude))
                {

				if ( isset( $this->processed_posts[$post['post_id']] ) && ! empty( $post['post_id'] ) )
					continue;

				if ( $post['status'] == 'auto-draft' )
					continue;

				// if ( 'nav_menu_item' == $post['post_type'] ) {
				// 	$this->process_menu_item( $post );
				// 	continue;
				// }

				$post_type_object = get_post_type_object( $post['post_type'] );

				$post_exists = post_exists( $post['post_title'], '', $post['post_date'] );

				$post_exists = apply_filters( 'wp_import_existing_post', $post_exists, $post );

				if ( $post_exists && get_post_type( $post_exists ) == $post['post_type'] ) {
					// $this->errorsXml[] = sprintf( __('%s &#8220;%s&#8221; already exists.'), $post_type_object->labels->singular_name, esc_html($post['post_title']) );
					$comment_post_ID = $post_id = $post_exists;
					$this->processed_posts[ intval( $post['post_id'] ) ] = intval( $post_exists );
				} 
				else 
				{
					$post_parent = (int) $post['post_parent'];
					if ( $post_parent ) {
				// 		// if we already know the parent, map it to the new local ID
						if ( isset( $this->processed_posts[$post_parent] ) ) {
							$post_parent = $this->processed_posts[$post_parent];
				// 		// otherwise record the parent for later
						} else {
							$this->post_orphans[intval($post['post_id'])] = $post_parent;
							$post_parent = 0;
						}
					}

				// 	// map the post author
					$author = sanitize_user( $post['post_author'], true );
					$author = (int) get_current_user_id();

					$postdata = array(
						'import_id' => $post['post_id'], 'post_author' => $author, 'post_date' => $post['post_date'],
						'post_date_gmt' => $post['post_date_gmt'], 'post_content' => $post['post_content'],
						'post_excerpt' => $post['post_excerpt'], 'post_title' => $post['post_title'],
						'post_status' => $post['status'], 'post_name' => $post['post_name'],
						'comment_status' => $post['comment_status'], 'ping_status' => $post['ping_status'],
						'guid' => $post['guid'], 'post_parent' => $post_parent, 'menu_order' => $post['menu_order'],
						'post_type' => $post['post_type'], 'post_password' => $post['post_password']
					);

					$original_post_ID = $post['post_id'];
					$postdata = apply_filters( 'wp_import_post_data_processed', $postdata, $post );

					$postdata = wp_slash( $postdata );

					if ( $postdata['post_type']!='attachment') {
					
						$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
						do_action( 'wp_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
					// $remote_url = ! empty($post['attachment_url']) ? $post['attachment_url'] : $post['guid'];

					// 	$postdata['upload_date'] = $post['post_date'];
					// 	if ( isset( $post['postmeta'] ) ) {
					// 		foreach( $post['postmeta'] as $meta ) {
					// 			if ( $meta['key'] == '_wp_attached_file' ) {
					// 				if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) )
					// 					$postdata['upload_date'] = $matches[0];
					// 				break;
					// 			}
					// 		}
					// 	}

					//  $comment_post_ID = $post_id = $this->process_attachment( $postdata, $remote_url );
					}
					// else 
					// {
					// 	$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
					// 	do_action( 'wp_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
					// }

					if ( is_wp_error( $post_id ) ) {
						$this->errorsXml[] = sprintf( __( 'Failed to import %s &#8220;%s&#8221;' ),
							$post_type_object->labels->singular_name, esc_html($post['post_title']) );
						continue;
					}

					if ( $post['is_sticky'] == 1 )
						stick_post( $post_id );
				}

				//get elementor css file


				// map pre-import ID to local ID
				$this->processed_posts[intval($post['post_id'])] = (int) $post_id;

				if ( ! isset( $post['terms'] ) )
					$post['terms'] = array();

				$post['terms'] = apply_filters( 'wp_import_post_terms', $post['terms'], $post_id, $post );

				// // add categories, tags and other terms
				if ( ! empty( $post['terms'] ) ) {
					$terms_to_set = array();
					foreach ( $post['terms'] as $term ) {
						// back compat with WXR 1.0 map 'tag' to 'post_tag'
						$taxonomy = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
						$term_exists = term_exists( $term['slug'], $taxonomy );
						$term_id = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
						if ( ! $term_id ) {
							$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
							if ( ! is_wp_error( $t ) ) {
								$term_id = $t['term_id'];
								do_action( 'wp_import_insert_term', $t, $term, $post_id, $post );
							} else {
								$this->errorsXml[] = sprintf( __( 'Failed to import %s %s' ), esc_html($taxonomy), esc_html($term['name']) );
								do_action( 'wp_import_insert_term_failed', $t, $term, $post_id, $post );
								continue;
							}
						}
						$terms_to_set[$taxonomy][] = intval( $term_id );
					}

					foreach ( $terms_to_set as $tax => $ids ) {
						$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
						do_action( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $post );
					}
					unset( $post['terms'], $terms_to_set );
				}

				if ( ! isset( $post['comments'] ) )
					$post['comments'] = array();

				$post['comments'] = apply_filters( 'wp_import_post_comments', $post['comments'], $post_id, $post );

				// // add/update comments
				if ( ! empty( $post['comments'] ) ) {
					$num_comments = 0;
					$inserted_comments = array();
					foreach ( $post['comments'] as $comment ) {
						$comment_id	= $comment['comment_id'];
						$newcomments[$comment_id]['comment_post_ID']      = $comment_post_ID;
						$newcomments[$comment_id]['comment_author']       = $comment['comment_author'];
						$newcomments[$comment_id]['comment_author_email'] = $comment['comment_author_email'];
						$newcomments[$comment_id]['comment_author_IP']    = $comment['comment_author_IP'];
						$newcomments[$comment_id]['comment_author_url']   = $comment['comment_author_url'];
						$newcomments[$comment_id]['comment_date']         = $comment['comment_date'];
						$newcomments[$comment_id]['comment_date_gmt']     = $comment['comment_date_gmt'];
						$newcomments[$comment_id]['comment_content']      = $comment['comment_content'];
						$newcomments[$comment_id]['comment_approved']     = $comment['comment_approved'];
						$newcomments[$comment_id]['comment_type']         = $comment['comment_type'];
						$newcomments[$comment_id]['comment_parent'] 	  = $comment['comment_parent'];
						$newcomments[$comment_id]['commentmeta']          = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
						if ( isset( $this->processed_authors[$comment['comment_user_id']] ) )
							$newcomments[$comment_id]['user_id'] = $this->processed_authors[$comment['comment_user_id']];
					}
					ksort( $newcomments );

					foreach ( $newcomments as $key => $comment ) {
						// if this is a new post we can skip the comment_exists() check
						if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
							if ( isset( $inserted_comments[$comment['comment_parent']] ) )
								$comment['comment_parent'] = $inserted_comments[$comment['comment_parent']];
							$comment = wp_slash( $comment );
							$comment = wp_filter_comment( $comment );
							$inserted_comments[$key] = wp_insert_comment( $comment );
							do_action( 'wp_import_insert_comment', $inserted_comments[$key], $comment, $comment_post_ID, $post );

							foreach( $comment['commentmeta'] as $meta ) {
								$value = maybe_unserialize( $meta['value'] );
								add_comment_meta( $inserted_comments[$key], $meta['key'], $value );
							}

							$num_comments++;
						}
					}
					unset( $newcomments, $inserted_comments, $post['comments'] );
				}
        
				if ( ! isset( $post['postmeta'] ) )
					$post['postmeta'] = array();

				$post['postmeta'] = apply_filters( 'wp_import_post_meta', $post['postmeta'], $post_id, $post );
                
				// // add/update post meta
				
				
				
				if ( ! empty( $post['postmeta'] ) ) {

					foreach ( $post['postmeta'] as $meta ) {
					   
						$key = apply_filters( 'import_post_meta_key', $meta['key'], $post_id, $post );
						$value = false;

						if ( '_edit_last' == $key ) {
							if ( isset( $this->processed_authors[intval($meta['value'])] ) )
								$value = $this->processed_authors[intval($meta['value'])];
							else
								$key = false;
						}

						if ( $key ) {
						    
							// export gets meta straight from the DB so could have a serialized string
							if ( ! $value )
							if($key=='_elementor_data')
							{
							    $fileName='elementor-data-'.$this->mainIdData.'-'.$original_post_ID.'.json';
								$upload_dir=wp_upload_dir();
								$elementPath=$upload_dir['basedir'] . '/wpjelly-backup/' . $fileName;
                                $value=file_get_contents($elementPath);

							   global $wpdb;
							   $wpdb->insert($wpdb->prefix.'postmeta', array(
                                    'post_id' => $post_id,
                                    'meta_key' => $key,
                                    'meta_value' => $value, 
                                ));
							    do_action( 'import_post_meta', $post_id, $key, $value );
							}
							else
							{
							    $value = maybe_unserialize( $meta['value'] );
							    add_post_meta( $post_id, $key, $value );
							    do_action( 'import_post_meta', $post_id, $key, $value );
							}
							// if the post has a featured image, take note of this in case of remap
							if ( '_thumbnail_id' == $key )
								$this->featured_images[$post_id] = (int) $value;
						}
					}
                    add_post_meta( $post_id, 'wpjelly_import_orig_id', $original_post_ID );
					

					}
                }
               
                
				
				
			/*---------------*/
			}

			//unset( $this->posts );
		}
		public function process_navigation()
		{
		    require_once( ABSPATH . 'wp-admin/includes/post.php' );
		    $this->posts = apply_filters( 'wp_import_posts', $this->posts );

			foreach ( $this->posts as $post ) {
				$post = apply_filters( 'wp_import_post_data_raw', $post );
                if($post['post_type']=='nav_menu_item')
                {

				if ( isset( $this->processed_posts[$post['post_id']] ) && ! empty( $post['post_id'] ) )
					continue;

				if ( $post['status'] == 'auto-draft' )
					continue;

				if ( 'nav_menu_item' == $post['post_type'] ) {
					$this->process_menu_item( $post );
					continue;
				}

				$post_type_object = get_post_type_object( $post['post_type'] );

				$post_exists = post_exists( $post['post_title'], '', $post['post_date'] );

				$post_exists = apply_filters( 'wp_import_existing_post', $post_exists, $post );

				if ( $post_exists && get_post_type( $post_exists ) == $post['post_type'] ) {
					// $this->errorsXml[] = sprintf( __('%s &#8220;%s&#8221; already exists.'), $post_type_object->labels->singular_name, esc_html($post['post_title']) );
					$comment_post_ID = $post_id = $post_exists;
					$this->processed_posts[ intval( $post['post_id'] ) ] = intval( $post_exists );
				} 
				else 
				{
					$post_parent = (int) $post['post_parent'];
					if ( $post_parent ) {
				// 		// if we already know the parent, map it to the new local ID
						if ( isset( $this->processed_posts[$post_parent] ) ) {
							$post_parent = $this->processed_posts[$post_parent];
				// 		// otherwise record the parent for later
						} else {
							$this->post_orphans[intval($post['post_id'])] = $post_parent;
							$post_parent = 0;
						}
					}

				// 	// map the post author
					$author = sanitize_user( $post['post_author'], true );
					$author = (int) get_current_user_id();

					$postdata = array(
						'import_id' => $post['post_id'], 'post_author' => $author, 'post_date' => $post['post_date'],
						'post_date_gmt' => $post['post_date_gmt'], 'post_content' => $post['post_content'],
						'post_excerpt' => $post['post_excerpt'], 'post_title' => $post['post_title'],
						'post_status' => $post['status'], 'post_name' => $post['post_name'],
						'comment_status' => $post['comment_status'], 'ping_status' => $post['ping_status'],
						'guid' => $post['guid'], 'post_parent' => $post_parent, 'menu_order' => $post['menu_order'],
						'post_type' => $post['post_type'], 'post_password' => $post['post_password']
					);

					$original_post_ID = $post['post_id'];
					add_post_meta( $post_id, 'wpjelly_import_orig_id', $original_post_ID );
					$postdata = apply_filters( 'wp_import_post_data_processed', $postdata, $post );

					$postdata = wp_slash( $postdata );

					if ( 'attachment' == $postdata['post_type'] ) {
					$remote_url = ! empty($post['attachment_url']) ? $post['attachment_url'] : $post['guid'];

						$postdata['upload_date'] = $post['post_date'];
						if ( isset( $post['postmeta'] ) ) {
							foreach( $post['postmeta'] as $meta ) {
								if ( $meta['key'] == '_wp_attached_file' ) {
									if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) )
										$postdata['upload_date'] = $matches[0];
									break;
								}
							}
						}

					 $comment_post_ID = $post_id = $this->process_attachment( $postdata, $remote_url );
					}
					else 
					{
						$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
						do_action( 'wp_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
					}

					if ( is_wp_error( $post_id ) ) {
						$this->errorsXml[] = sprintf( __( 'Failed to import %s &#8220;%s&#8221;' ),
							$post_type_object->labels->singular_name, esc_html($post['post_title']) );
						continue;
					}

					if ( $post['is_sticky'] == 1 )
						stick_post( $post_id );
				}

				//get elementor css file


				// map pre-import ID to local ID
				$this->processed_posts[intval($post['post_id'])] = (int) $post_id;

				if ( ! isset( $post['terms'] ) )
					$post['terms'] = array();

				$post['terms'] = apply_filters( 'wp_import_post_terms', $post['terms'], $post_id, $post );

				// // add categories, tags and other terms
				if ( ! empty( $post['terms'] ) ) {
					$terms_to_set = array();
					foreach ( $post['terms'] as $term ) {
						// back compat with WXR 1.0 map 'tag' to 'post_tag'
						$taxonomy = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
						$term_exists = term_exists( $term['slug'], $taxonomy );
						$term_id = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
						if ( ! $term_id ) {
							$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
							if ( ! is_wp_error( $t ) ) {
								$term_id = $t['term_id'];
								do_action( 'wp_import_insert_term', $t, $term, $post_id, $post );
							} else {
								$this->errorsXml[] = sprintf( __( 'Failed to import %s %s' ), esc_html($taxonomy), esc_html($term['name']) );
								do_action( 'wp_import_insert_term_failed', $t, $term, $post_id, $post );
								continue;
							}
						}
						$terms_to_set[$taxonomy][] = intval( $term_id );
					}

					foreach ( $terms_to_set as $tax => $ids ) {
						$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
						do_action( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $post );
					}
					unset( $post['terms'], $terms_to_set );
				}

				if ( ! isset( $post['comments'] ) )
					$post['comments'] = array();

				$post['comments'] = apply_filters( 'wp_import_post_comments', $post['comments'], $post_id, $post );

				// // add/update comments
				if ( ! empty( $post['comments'] ) ) {
					$num_comments = 0;
					$inserted_comments = array();
					foreach ( $post['comments'] as $comment ) {
						$comment_id	= $comment['comment_id'];
						$newcomments[$comment_id]['comment_post_ID']      = $comment_post_ID;
						$newcomments[$comment_id]['comment_author']       = $comment['comment_author'];
						$newcomments[$comment_id]['comment_author_email'] = $comment['comment_author_email'];
						$newcomments[$comment_id]['comment_author_IP']    = $comment['comment_author_IP'];
						$newcomments[$comment_id]['comment_author_url']   = $comment['comment_author_url'];
						$newcomments[$comment_id]['comment_date']         = $comment['comment_date'];
						$newcomments[$comment_id]['comment_date_gmt']     = $comment['comment_date_gmt'];
						$newcomments[$comment_id]['comment_content']      = $comment['comment_content'];
						$newcomments[$comment_id]['comment_approved']     = $comment['comment_approved'];
						$newcomments[$comment_id]['comment_type']         = $comment['comment_type'];
						$newcomments[$comment_id]['comment_parent'] 	  = $comment['comment_parent'];
						$newcomments[$comment_id]['commentmeta']          = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
						if ( isset( $this->processed_authors[$comment['comment_user_id']] ) )
							$newcomments[$comment_id]['user_id'] = $this->processed_authors[$comment['comment_user_id']];
					}
					ksort( $newcomments );

					foreach ( $newcomments as $key => $comment ) {
						// if this is a new post we can skip the comment_exists() check
						if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
							if ( isset( $inserted_comments[$comment['comment_parent']] ) )
								$comment['comment_parent'] = $inserted_comments[$comment['comment_parent']];
							$comment = wp_slash( $comment );
							$comment = wp_filter_comment( $comment );
							$inserted_comments[$key] = wp_insert_comment( $comment );
							do_action( 'wp_import_insert_comment', $inserted_comments[$key], $comment, $comment_post_ID, $post );

							foreach( $comment['commentmeta'] as $meta ) {
								$value = maybe_unserialize( $meta['value'] );
								add_comment_meta( $inserted_comments[$key], $meta['key'], $value );
							}

							$num_comments++;
						}
					}
					unset( $newcomments, $inserted_comments, $post['comments'] );
				}

				if ( ! isset( $post['postmeta'] ) )
					$post['postmeta'] = array();

				$post['postmeta'] = apply_filters( 'wp_import_post_meta', $post['postmeta'], $post_id, $post );

				// // add/update post meta
				if ( ! empty( $post['postmeta'] ) ) {
					foreach ( $post['postmeta'] as $meta ) {
						$key = apply_filters( 'import_post_meta_key', $meta['key'], $post_id, $post );
						$value = false;

						if ( '_edit_last' == $key ) {
							if ( isset( $this->processed_authors[intval($meta['value'])] ) )
								$value = $this->processed_authors[intval($meta['value'])];
							else
								$key = false;
						}

						if ( $key ) {
							// export gets meta straight from the DB so could have a serialized string
							if ( ! $value )
								$value = maybe_unserialize( $meta['value'] );

							add_post_meta( $post_id, $key, $value );
							do_action( 'import_post_meta', $post_id, $key, $value );

							// if the post has a featured image, take note of this in case of remap
							if ( '_thumbnail_id' == $key )
								$this->featured_images[$post_id] = (int) $value;
						}
					}
					
				}
				
                }
			}

			unset( $this->posts );
		}

		public function process_attachmentMigration( $mediaStart )
		{
		    require_once( ABSPATH . 'wp-admin/includes/post.php' );
		    $this->posts = apply_filters( 'wp_import_posts', $this->posts );

			$mediaIndex = 0;

			foreach ( $this->posts as $post ) {
				$post = apply_filters( 'wp_import_post_data_raw', $post );

				if ( $post['post_type'] == 'attachment' ) {
					// Skip media files from the previous request
					if ( $mediaIndex < $mediaStart ) {
						$mediaIndex++;
						continue;
					}

					if ( isset( $this->processed_posts[$post['post_id']] ) && ! empty( $post['post_id'] ) ) {
						$mediaIndex++;
						continue;
					}

					if ( $post['status'] == 'auto-draft' ) {
						$mediaIndex++;
						continue;
					}

					if ( 'nav_menu_item' == $post['post_type'] ) {
						$this->process_menu_item( $post );
						$mediaIndex++;
						continue;
					}

					$post_type_object = get_post_type_object( $post['post_type'] );

					$post_exists = post_exists( $post['post_title'], '', $post['post_date'] );

					$post_exists = apply_filters( 'wp_import_existing_post', $post_exists, $post );

					if ( $post_exists && get_post_type( $post_exists ) == $post['post_type'] ) {
						$comment_post_ID = $post_id = $post_exists;
						$this->processed_posts[ intval( $post['post_id'] ) ] = intval( $post_exists );
					} else {
						$post_parent = (int) $post['post_parent'];
						if ( $post_parent ) {
					 		// if we already know the parent, map it to the new local ID
							if ( isset( $this->processed_posts[$post_parent] ) ) {
								$post_parent = $this->processed_posts[$post_parent];
					 		// otherwise record the parent for later
							} else {
								$this->post_orphans[intval($post['post_id'])] = $post_parent;
								$post_parent = 0;
							}
						}

					 	// map the post author
						$author = sanitize_user( $post['post_author'], true );
						$author = (int) get_current_user_id();

						$postdata = array(
							'import_id' => $post['post_id'], 'post_author' => $author, 'post_date' => $post['post_date'],
							'post_date_gmt' => $post['post_date_gmt'], 'post_content' => $post['post_content'],
							'post_excerpt' => $post['post_excerpt'], 'post_title' => $post['post_title'],
							'post_status' => $post['status'], 'post_name' => $post['post_name'],
							'comment_status' => $post['comment_status'], 'ping_status' => $post['ping_status'],
							'guid' => $post['guid'], 'post_parent' => $post_parent, 'menu_order' => $post['menu_order'],
							'post_type' => $post['post_type'], 'post_password' => $post['post_password']
						);

						$original_post_ID = $post['post_id'];
						$postdata = apply_filters( 'wp_import_post_data_processed', $postdata, $post );

						$postdata = wp_slash( $postdata );

						if ( 'attachment' == $postdata['post_type'] ) {

							$remote_url = ! empty($post['attachment_url']) ? $post['attachment_url'] : $post['guid'];

							$postdata['upload_date'] = $post['post_date'];
							if ( isset( $post['postmeta'] ) ) {
								foreach( $post['postmeta'] as $meta ) {
									if ( $meta['key'] == '_wp_attached_file' ) {
										if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) )
											$postdata['upload_date'] = $matches[0];
										break;
									}
								}
							}

							$this->stateCheck->timeRecordStart();

							$comment_post_ID = $post_id = $this->process_attachment( $postdata, $remote_url );

							$this->stateCheck->timeRecordEnd();

							if ( is_wp_error( $post_id ) ) {
								$process_error = $post_id;

								if ( in_array( 'http_request_failed', $process_error->get_error_codes() ) ) {
									break;
								}
							}
						} else  {
							$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
							do_action( 'wp_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
						}

						if ( is_wp_error( $post_id ) ) {
							$this->errorsXml[] = sprintf( __( 'Failed to import %s &#8220;%s&#8221;' ),
								$post_type_object->labels->singular_name, esc_html($post['post_title']) );

							$mediaIndex++;
							continue;
						}

						if ( $post['is_sticky'] == 1 )
							stick_post( $post_id );
					}

					//get elementor css file

					// map pre-import ID to local ID
					$this->processed_posts[intval($post['post_id'])] = (int) $post_id;

					if ( ! isset( $post['terms'] ) )
						$post['terms'] = array();

					$post['terms'] = apply_filters( 'wp_import_post_terms', $post['terms'], $post_id, $post );

					// // add categories, tags and other terms
					if ( ! empty( $post['terms'] ) ) {
						$terms_to_set = array();
						foreach ( $post['terms'] as $term ) {
							// back compat with WXR 1.0 map 'tag' to 'post_tag'
							$taxonomy = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
							$term_exists = term_exists( $term['slug'], $taxonomy );
							$term_id = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
							if ( ! $term_id ) {
								$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
								if ( ! is_wp_error( $t ) ) {
									$term_id = $t['term_id'];
									do_action( 'wp_import_insert_term', $t, $term, $post_id, $post );
								} else {
									$this->errorsXml[] = sprintf( __( 'Failed to import %s %s' ), esc_html($taxonomy), esc_html($term['name']) );
									do_action( 'wp_import_insert_term_failed', $t, $term, $post_id, $post );

									$mediaIndex++;
									continue;
								}
							}
							$terms_to_set[$taxonomy][] = intval( $term_id );
						}

						foreach ( $terms_to_set as $tax => $ids ) {
							$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
							do_action( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $post );
						}
						unset( $post['terms'], $terms_to_set );
					}

					if ( ! isset( $post['comments'] ) )
						$post['comments'] = array();

					$post['comments'] = apply_filters( 'wp_import_post_comments', $post['comments'], $post_id, $post );

					// // add/update comments
					if ( ! empty( $post['comments'] ) ) {
						$num_comments = 0;
						$inserted_comments = array();
						foreach ( $post['comments'] as $comment ) {
							$comment_id	= $comment['comment_id'];
							$newcomments[$comment_id]['comment_post_ID']      = $comment_post_ID;
							$newcomments[$comment_id]['comment_author']       = $comment['comment_author'];
							$newcomments[$comment_id]['comment_author_email'] = $comment['comment_author_email'];
							$newcomments[$comment_id]['comment_author_IP']    = $comment['comment_author_IP'];
							$newcomments[$comment_id]['comment_author_url']   = $comment['comment_author_url'];
							$newcomments[$comment_id]['comment_date']         = $comment['comment_date'];
							$newcomments[$comment_id]['comment_date_gmt']     = $comment['comment_date_gmt'];
							$newcomments[$comment_id]['comment_content']      = $comment['comment_content'];
							$newcomments[$comment_id]['comment_approved']     = $comment['comment_approved'];
							$newcomments[$comment_id]['comment_type']         = $comment['comment_type'];
							$newcomments[$comment_id]['comment_parent'] 	  = $comment['comment_parent'];
							$newcomments[$comment_id]['commentmeta']          = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
							if ( isset( $this->processed_authors[$comment['comment_user_id']] ) )
								$newcomments[$comment_id]['user_id'] = $this->processed_authors[$comment['comment_user_id']];
						}
						ksort( $newcomments );

						foreach ( $newcomments as $key => $comment ) {
							// if this is a new post we can skip the comment_exists() check
							if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
								if ( isset( $inserted_comments[$comment['comment_parent']] ) )
									$comment['comment_parent'] = $inserted_comments[$comment['comment_parent']];
								$comment = wp_slash( $comment );
								$comment = wp_filter_comment( $comment );
								$inserted_comments[$key] = wp_insert_comment( $comment );
								do_action( 'wp_import_insert_comment', $inserted_comments[$key], $comment, $comment_post_ID, $post );

								foreach( $comment['commentmeta'] as $meta ) {
									$value = maybe_unserialize( $meta['value'] );
									add_comment_meta( $inserted_comments[$key], $meta['key'], $value );
								}

								$num_comments++;
							}
						}
						unset( $newcomments, $inserted_comments, $post['comments'] );
					}

					if ( ! isset( $post['postmeta'] ) )
						$post['postmeta'] = array();

					$post['postmeta'] = apply_filters( 'wp_import_post_meta', $post['postmeta'], $post_id, $post );

					// // add/update post meta
					if ( ! empty( $post['postmeta'] ) ) {
						foreach ( $post['postmeta'] as $meta ) {
						
							$key = apply_filters( 'import_post_meta_key', $meta['key'], $post_id, $post );
							$value = false;

							if ( '_edit_last' == $key ) {
								if ( isset( $this->processed_authors[intval($meta['value'])] ) )
									$value = $this->processed_authors[intval($meta['value'])];
								else
									$key = false;
							}

							if ( $key ) {
								// export gets meta straight from the DB so could have a serialized string
								if ( ! $value )
									$value = maybe_unserialize( $meta['value'] );
							
								add_post_meta( $post_id, $key, $value );
								do_action( 'import_post_meta', $post_id, $key, $value );

								// if the post has a featured image, take note of this in case of remap
								if ( '_thumbnail_id' == $key )
									$this->featured_images[$post_id] = (int) $value;
							}
						}
					}

					$mediaIndex++;

					// Stop adding media files if script takes too long to execute
					if ( $this->stateCheck->getTimeLeft() <= 2 * $this->stateCheck->maxImportTime() ) {
						break;
					}

					// Stop adding if we reached limit of items per single request
					if ( ( $mediaIndex - $mediaStart ) >= WP_JELLY_MAX_ENTRIES_PER_REQUEST ) {
						break;
					}
				}
			}

			return ( $mediaIndex - $mediaStart );
		}

		public function process_menu_item( $item ) 
		{
			// skip draft, orphaned menu items
			if ( 'draft' == $item['status'] )
				return;

			$menu_slug = false;
			if ( isset($item['terms']) ) {
				// loop through terms, assume first nav_menu term is correct menu
				foreach ( $item['terms'] as $term ) {
					if ( 'nav_menu' == $term['domain'] ) {
						$menu_slug = $term['slug'];
						break;
					}
				}
			}

			// no nav_menu term associated with this menu item
			if ( ! $menu_slug ) {
				$this->errorsXml[] = 'Menu item skipped due to missing menu slug';
				return;
			}

			$menu_id = term_exists( $menu_slug, 'nav_menu' );
			if ( ! $menu_id ) {
				$this->errorsXml[] = sprintf( __( 'Menu item skipped due to invalid menu slug: %s' ), esc_html( $menu_slug ) );
				return;
			} else {
				$menu_id = is_array( $menu_id ) ? $menu_id['term_id'] : $menu_id;
			}

			foreach ( $item['postmeta'] as $meta )
				${$meta['key']} = $meta['value'];

			if ( 'taxonomy' == $_menu_item_type && isset( $this->processed_terms[intval($_menu_item_object_id)] ) ) {
				$_menu_item_object_id = $this->processed_terms[intval($_menu_item_object_id)];
			} else if ( 'post_type' == $_menu_item_type && isset( $this->processed_posts[intval($_menu_item_object_id)] ) ) {
				$_menu_item_object_id = $this->processed_posts[intval($_menu_item_object_id)];
			} else if ( 'custom' != $_menu_item_type ) {
				// associated object is missing or not imported yet, we'll retry later
				$this->missing_menu_items[] = $item;
				return;
			}

			if ( isset( $this->processed_menu_items[intval($_menu_item_menu_item_parent)] ) ) {
				$_menu_item_menu_item_parent = $this->processed_menu_items[intval($_menu_item_menu_item_parent)];
			} else if ( $_menu_item_menu_item_parent ) {
				$this->menu_item_orphans[intval($item['post_id'])] = (int) $_menu_item_menu_item_parent;
				$_menu_item_menu_item_parent = 0;
			}

			// wp_update_nav_menu_item expects CSS classes as a space separated string
			$_menu_item_classes = maybe_unserialize( $_menu_item_classes );
			if ( is_array( $_menu_item_classes ) )
				$_menu_item_classes = implode( ' ', $_menu_item_classes );

			$args = array(
				'menu-item-object-id' => $_menu_item_object_id,
				'menu-item-object' => $_menu_item_object,
				'menu-item-parent-id' => $_menu_item_menu_item_parent,
				'menu-item-position' => intval( $item['menu_order'] ),
				'menu-item-type' => $_menu_item_type,
				'menu-item-title' => $item['post_title'],
				'menu-item-url' => $_menu_item_url,
				'menu-item-description' => $item['post_content'],
				'menu-item-attr-title' => $item['post_excerpt'],
				'menu-item-target' => $_menu_item_target,
				'menu-item-classes' => $_menu_item_classes,
				'menu-item-xfn' => $_menu_item_xfn,
				'menu-item-status' => $item['status']
			);

			$id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( $id && ! is_wp_error( $id ) )
				$this->processed_menu_items[intval($item['post_id'])] = (int) $id;
		}
		public function process_attachment( $post, $url )
		{
			include_once( ABSPATH . 'wp-admin/includes/image.php' );
			
			if ( ! $this->fetch_attachments )
				return new WP_Error( 'attachment_processing_error',
					__( 'Fetching attachments is not enabled' ) );
            
			// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
			if ( preg_match( '|^/[\w\W]+$|', $url ) )
				$url = rtrim( $this->base_url, '/' ) . $url;

			$upload = $this->fetch_remote_file( $url, $post );
			if ( is_wp_error( $upload ) )
				return $upload;

			if ( $info = wp_check_filetype( $upload['file'] ) )
				$post['post_mime_type'] = $info['type'];
			else
				return new WP_Error( 'attachment_processing_error', __('Invalid file type') );

			$post['guid'] = $upload['url'];

			// as per wp-admin/includes/upload.php
			$post_id = wp_insert_attachment( $post, $upload['file'] );
			wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );

			// remap resized image URLs, works by stripping the extension and remapping the URL stub.
			if ( preg_match( '!^image/!', $info['type'] ) ) {
				$parts = pathinfo( $url );
			   
				$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

				$parts_new = pathinfo( $upload['url'] );
				$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );
                
				$this->url_remap[$parts['dirname'] . '/' . $name] = $parts_new['dirname'] . '/' . $name_new;
			}

			return $post_id;
		}
		public function fetch_remote_file( $url, $post )
		{
			// extract the file name and extension from the url
			$file_name = basename( $url );

			// get placeholder file in the upload dir with a unique, sanitized filename
			$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
			if ( $upload['error'] )
				return new WP_Error( 'upload_dir_error', $upload['error'] );

			// fetch the remote url and write it to the placeholder file
			$remote_response = wp_safe_remote_get( $url, array(
					'timeout' => ceil( $this->stateCheck->getTimeLeft() / 2 ),
					'stream' => true,
					'filename' => $upload['file'],
	        	) );

			if ( is_wp_error( $remote_response ) ) {
				return $remote_response;
			}

			$headers = wp_remote_retrieve_headers( $remote_response );

			// request failed
			if ( ! $headers ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __('Remote server did not respond') );
			}

			$remote_response_code = wp_remote_retrieve_response_code( $remote_response );

			// make sure the fetch was successful
			if ( $remote_response_code != '200' ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s'), esc_html($remote_response_code), get_status_header_desc($remote_response_code) ) );
			}

			$filesize = filesize( $upload['file'] );

			if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __('Remote file is incorrect size') );
			}

			if ( 0 == $filesize ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', __('Zero size file downloaded') );
			}

			$max_size = (int) $this->max_attachment_size();
			if ( ! empty( $max_size ) && $filesize > $max_size ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', sprintf(__('Remote file is too large, limit is %s'), size_format($max_size) ) );
			}

			// keep track of the old and new urls so we can substitute them later
			$this->url_remap[$url] = $upload['url'];
			$this->url_remap[$post['guid']] = $upload['url']; // r13735, really needed?
			// keep track of the destination if the remote url is redirected somewhere else
			if ( isset($headers['x-final-location']) && $headers['x-final-location'] != $url )
				$this->url_remap[$headers['x-final-location']] = $upload['url'];

			return $upload;
		}
		public function backfill_parents()
		{
			global $wpdb;

			// find parents for post orphans
			foreach ( $this->post_orphans as $child_id => $parent_id ) {
				$local_child_id = $local_parent_id = false;
				if ( isset( $this->processed_posts[$child_id] ) )
					$local_child_id = $this->processed_posts[$child_id];
				if ( isset( $this->processed_posts[$parent_id] ) )
					$local_parent_id = $this->processed_posts[$parent_id];

				if ( $local_child_id && $local_parent_id ) {
					$wpdb->update( $wpdb->posts, array( 'post_parent' => $local_parent_id ), array( 'ID' => $local_child_id ), '%d', '%d' );
					clean_post_cache( $local_child_id );
				}
			}

			// all other posts/terms are imported, retry menu items with missing associated object
			$menuList=array();
			$missing_menu_items = $this->missing_menu_items;
			foreach ( $missing_menu_items as $item )
				$this->process_menu_item( $item );
		

			// find parents for menu item orphans
			foreach ( $this->menu_item_orphans as $child_id => $parent_id ) {
				$local_child_id = $local_parent_id = 0;
				if ( isset( $this->processed_menu_items[$child_id] ) )
					$local_child_id = $this->processed_menu_items[$child_id];
				if ( isset( $this->processed_menu_items[$parent_id] ) )
					$local_parent_id = $this->processed_menu_items[$parent_id];

				if ( $local_child_id && $local_parent_id )
					update_post_meta( $local_child_id, '_menu_item_menu_item_parent', (int) $local_parent_id );
			}
		}
		public function backfill_attachment_urls()
		{
			global $wpdb;

			// make sure we do the longest urls first, in case one is a substring of another
			uksort( $this->url_remap, array(&$this, 'cmpr_strlen') );
			
			foreach ( $this->url_remap as $from_url => $to_url ) {
				// remap urls in post_content
				 $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $from_url, $to_url) );
				// remap enclosure urls
				$result = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url) );
				
			}
			

		}
		public function isJSON($string){
           return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
        }
		public function cmpr_strlen( $a, $b )
		{
			return strlen($b) - strlen($a);
		}
		public function remap_featured_images()
		{
			// cycle through posts that have a featured image
			foreach ( $this->featured_images as $post_id => $value ) {
				if ( isset( $this->processed_posts[$value] ) ) {
					$new_id = $this->processed_posts[$value];
					// only update if there's a difference
					if ( $new_id != $value )
						update_post_meta( $post_id, '_thumbnail_id', $new_id );
				}
			}
		}

		public function import_end()
		{
			wp_import_cleanup( $this->id );

			wp_cache_flush();
			foreach ( get_taxonomies() as $tax ) {
				delete_option( "{$tax}_children" );
				_get_term_hierarchy( $tax );
			}

			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );
			
			do_action( 'import_end' );
		}

		public function getErros() {
			return $this->errorsXml;
		}

		public function max_attachment_size()
		{
			return apply_filters( 'import_attachment_size_limit', 0 );
		}
	}
}