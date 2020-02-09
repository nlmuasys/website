<?php
// add_action('init',function(){
// 	// require ABSPATH.'wp-content/plugins/elementor/includes/template-library/sources/local.php';
// 	// Source_Local::import_template('abc','https://my.wpjelly.com/template/wp-content/uploads/2019/11/elementor-516-2019-11-17-1.json');
// 	//die;
// });
// require ABSPATH.'wp-content/plugins/elementor/includes/template-library/sources/base.php';

class WPJellyEditorAddon
{
	
	function __construct()
	{
		$this->initialise();
		$this->editorAjax();
	}
	public function initialise()
	{
		add_action( 'elementor/editor/before_enqueue_scripts', array($this, 'scripts'));
		// add_action( 'wp_footer', array($this, 'test') );
		 //add_action('init',array($this,'checkIfImported'));
		add_action('wp_ajax_bistemaker_elements',array($this,'bistemaker_elements'));
		add_action('wp_ajax_nopriv_bistemaker_elements',array($this,'bistemaker_elements'));
		add_action('wp_ajax_wpjellyTemplateImportProcess',array($this,'wpjellyTemplateImportProcess'));
		add_action('wp_ajax_wpjellyTemplateImportDirect',array($this,'wpjellyTemplateImportDirect'));
		add_action('wp_ajax_wpjellyEditorGetTemplateByCategory',array($this,'wpjellyEditorGetTemplateByCategory'));
		add_action('wp_ajax_wpjellyEditorGetTemplateByCategorySingle',array($this,'wpjellyEditorGetTemplateByCategorySingle'));
		add_action('wp_ajax_wpjellyEditorGetBlockParents',array($this,'wpjellyEditorGetBlockParents'));
		add_action('wp_ajax_wpjellyEditorGetBlockChildren',array($this,'wpjellyEditorGetBlockChildren'));
		add_action('wp_ajax_wpjellyEditorGetTemplateChildren',array($this,'wpjellyEditorGetTemplateChildren'));
		add_action( 'elementor/editor/footer',  array($this, 'print_templates') );
		add_action('wp_ajax_wpjelly_block_search_editor',[$this,'wpjelly_block_search_editor']);
		add_action('wp_ajax_wpjelly_template_search_editor',[$this,'wpjelly_template_search_editor']);
		add_action('wp_ajax_wpjelly_category_search_editor',[$this,'wpjelly_category_search_editor']);
		add_action('wp_ajax_editor_universal_template_search',[$this,'editor_universal_template_search']);
	}
	public function print_templates()
	{
		include WP_JELLY_DIR.'core/template-importer/editor/template.php';
	}
	public function scripts()
	{
	    wp_enqueue_style( 'wpjelly-element-widget', WSE_EX_EDIT_CSS . 'bsite-element-editor.css', array(), WP_JELLY_VERSION, 'all' );
	    wp_enqueue_style( 'wpjelly-react-widget', WSE_EX_EDIT_CSS . 'react.css', array(), WP_JELLY_VERSION, 'all' );

	    wp_register_script( 'wpjelly-decode-widget', WSE_EX_EDIT_JS .'jquery.base64.min.js', array(), WP_JELLY_VERSION, true );
	    wp_register_script( 'wpjelly-element-editor', WSE_EX_EDIT_JS . 'bsite-element-editor.js', array(), WP_JELLY_VERSION, true );
	    wp_register_script( 'wpjelly-react', WSE_EX_EDIT_JS . 'react.js', array(), WP_JELLY_VERSION, true );

		$bsmarg = array(
		    'post_type' => 'elementor_library',
		);
		$getallmetaquery = get_posts($bsmarg);
		$metaData = array();
		foreach($getallmetaquery as $key => $value){
		    $metaData[] = get_post_meta($value->ID,'_bsm_mainelement', true);
		}

		$metaDataEncoded=json_encode($metaData);
		$blogId=get_current_blog_id();
		$siteUrl=get_site_url($blogId);
		$ajaxurl = array(
		    'ajaxurl' => admin_url( 'admin-ajax.php' ),
		    'logo'=>WSE_EX_EDIT_IMG.'Symbol-small.png',
		    'logoStyle'=>'background: #ffffff;
						  background-image: url('.WSE_EX_EDIT_IMG.'Symbol-small.png);
						  background-repeat: no-repeat;
						  background-position: 50% 50%;
						  background-size: 25px auto;
						  box-shadow: 1px 1px 4px #404040;
						  margin-left:5px;
						  ',
			'meta'=>$metaDataEncoded,
			'siteUrl'=>$siteUrl,
			'loader' =>WSE_EX_EDIT_IMG.'3.gif',
			'_wpjelly_elem_nonce' => wp_create_nonce( "elementor_clear_cache" ),
			'jelly_loader'=>WSE_EX_EDIT_IMG.'jellyfish.gif'
		);
		$settings=$this->get_public_settings();
		wp_localize_script("wpjelly-element-editor",'bsitemakr_elements_react',$settings);
		wp_localize_script( "wpjelly-element-editor", "wpjellyExportControl", $ajaxurl );
		wp_enqueue_script(  'wpjelly-decode-widget');
		wp_enqueue_script(  'wpjelly-element-editor');
		//wp_enqueue_script(  'wpjelly-react');
	}
	
	public function wpjellyTemplateImportProcess()
	{
		$template=$_POST['template'];
		$postId=$_POST['postId'];
		$type=$_POST['type'];
		$request = wp_remote_get( 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplateEditor/?id='.$template );
		if( is_wp_error( $request ) ) {
			$msg=array();
			$msg['stat']=0;
			$msg['error']='Oops! Looks like there is a problem please try again';
			echo json_encode($msg);
			die;
		}
		$body = wp_remote_retrieve_body( $request );
		$data = json_decode( $body );
		if( ! empty( $data ) ) {
			
			$jsonFile=$data->json;
			$fileContent = wp_remote_retrieve_body( wp_remote_get( $jsonFile ) );
		  	$json = json_decode($fileContent, true);
		  	$content = $json['content'];
		  	// $res=Source_Local::import_template($json['title'],$jsonFile);

		  	$title = $json['title'];
		  	$version = $json['version'];
		  	$page_settings = $json['page_settings'];
		  	$css=$json['css'];
		  	$form=$data->form;
		  	$url=WpjellysearchItemsByKey($content,'url');
			if(is_array($url) && count($url)>0)
		  	{
		  	 	$replaceUrls=array();
		  	 	$replaceUrls=WpjellyMigrateImages($url);
		  	 	$content=WpjellyfindandReplaceImages($content,$replaceUrls);
		  	}


		  	 
		  	 	$content=WpjellyFormatTemplateContent($content,$form);
		  	 

		  	
		  	$elementor_post = array(
			    'post_title'    => $title,
			    'post_content'  => '',
			    'post_status'   => 'publish',
			    'post_author'   => 1,
			    'post_type' => 'elementor_library',
			    'post_name' => $title
		  	);

		  	$insert_id = wp_insert_post( $elementor_post );
		  	add_post_meta( $insert_id, '_bsm_mainelement', $template);
		  	add_post_meta( $insert_id, '_bsm_pagedid', $insert_id);

		  	update_post_meta( $insert_id, '_elementor_template_type', $type );
		  	update_post_meta( $insert_id, '_elementor_edit_mode', 'builder' );
		  	update_post_meta( $insert_id, '_wp_page_template', 'elementor_header_footer' );
		  	update_post_meta( $insert_id, '_elementor_data', $content );
		  	update_post_meta( $insert_id, '_elementor_version', $version );
		  	update_post_meta( $insert_id, '_elementor_css',$css);
		  	update_post_meta( $insert_id, '_elementor_page_settings', $page_settings ); 


			$elementorSavedTemplate = $insert_id;
			/*----------------------------------------------------------------*/
			$info['stat']=1;
			$info['cid']=$elementorSavedTemplate;
			$info['changed']=array();
			$info['_changing']=false;
			$info['_events']=array();
			$info['options']=array();
			$info['content']=get_post_meta($elementorSavedTemplate,'_elementor_data',true);
			$info['template_id']=$elementorSavedTemplate;
			$info['source']='local';

			$info['attributes']=array();

			echo json_encode($info);
			/*----------------------------------------------------------------*/
			

		  	//$this->wpjellyTemplateImportDirectAfterProcessing($insert_id,$postId);
		 //  	$msg['stat']=1;
			// $msg['id']=$insert_id;
			//echo json_encode($data);
		}
		else
		{
			$msg['stat']=0;
			$msg['error']='Data Not Found';
			echo json_encode($msg);
		}
		die;
	}
	public function get_public_settings()
	{
		$cat['elementor'] = [
			'slug'        => 'elementor',
			'url'         => get_site_url(),
			'nav_title'   => 'Wp Jelly Template Importer',
			'edit_button' => 'Edit Template with Elementor',
			'page_title'  => 'Elementor  abc s',
			'main_nav'    => true,
			'type'        => 'templates',
			'subtypes'    => [
				'elementor'        => 'Template Kits',
				'elementor-blocks' => 'Blocks',
			],
		];

		$categories = $cat;
		$navigation = [];
		foreach ( $categories as $category_id => $category ) {
			if ( $category['main_nav'] ) {
				$subtypes = [];
				if ( ! empty( $category['subtypes'] ) ) {
					foreach ( $category['subtypes'] as $subtype => $subtype_name ) {
						$subtypes[] = [
							'slug' => $subtype,
							'name' => $subtype_name,
						];
					}
				}
				$navigation[] = [
					'slug'      => $category['slug'],
					'nav_title' => $category['nav_title'],
					'sub_nav'   => $subtypes,
					'new_flag'  => ! empty( $category['new_flag'] ),
				];
			}
		}
		$collections_url = admin_url( 'admin.php?page=BsiteMaker-importer-menu');
		$bits            = wp_parse_url( $collections_url );

		// Put the notifications into our server side rendered options so we can show them straight away.
		$has_viewed_notifications = [];
	
		$current_notifications = [];
		$unseen_notifications  = [];
		if ( $current_notifications ) {
			// figure out if anything has not been seen before.
			foreach ( $current_notifications as $notification ) {
				if ( ! empty( $notification['id'] ) && ! isset( $has_viewed_notifications[ $notification['id'] ] ) ) {
					$unseen_notifications[] = $notification['id'];
				}
			}
		}

		return [
			'api_nonce'             => wp_create_nonce( 'wp_rest' ),
			'api_url'               => admin_url( 'admin-ajax.php?action=bistemaker_elements&endpoint=' ),
			'license_activated'     => '1',
			'elements_status'       => 'inactive',
			'elements_project'      => 'abc',
			'maintenance_mode'      => false, // We can prevent API calls if in maintenance mode.
			'admin_base'            => trailingslashit( dirname( $bits['path'] ) ),
			'admin_slug'            => $bits['query'],
			'collections_base'      => $bits['path'] . '?' . $bits['query'],
			'categories'            => $categories,
			'navigation'            => $navigation,
			'license_deactivate'    => wp_nonce_url( admin_url( 'admin.php?action=envato_elements_deactivate' ), 'deactivate' ),
			'elements_token_url'    => admin_url( 'admin-ajax.php?action=envato_elements&endpoint=' ),
			'unseen_notifications'  => $unseen_notifications,
			'current_notifications' => $current_notifications,
			'token_exit_question'   => '',
			//'has_elementor_pro'     => Elementor::get_instance()->has_elementor_pro(),
		];
	}
	public function bistemaker_elements()
	{
		
		$data=array();
		$page=$_POST['elementsSearch']['pg'];
		$json_string = wp_remote_retrieve_body( wp_remote_get( 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/?source=popup&pagenumber='.$page ) );
	    $json_array = json_decode($json_string, TRUE);

		$countTemplate=0;
	    if(count($json_array)>0)
	    {

	    	$info['all_results']=$json_array['0']['0']['totalpages'];
	    	$info['page_number']=$_POST['elementsSearch']['pg'];
	    	$info['per_page']=5;
	    	$results=array();
	    	if(count($json_array['1'])>0)
	    	{	$c=1;
	    		foreach ($json_array['1'] as $key => $value) {
	    			$temp=array();
	    			$id=$value['id'];
					// https://testyourprojects.net/wpnew2018/envado/wp-json/pm/v1/getcategory/?cat=
					$categoriesSlug=(strlen($value['getcategory'])>0?explode(',', $value['getcategory']):'');
					$catSlug=(is_array($categories)?$categories[0]:'Default');
					$categoriesName=(strlen($value['getcategoryName'])>0?explode(',', $value['getcategoryName']):'');
					$catName=(is_array($categoriesName)?$categoriesName[0]:'Default');
					$categoriesIds=(strlen($value['getcategoryIds'])>0?explode(',', $value['getcategoryIds']):'');
					$catIds=(is_array($categoriesIds)?$categoriesIds[0]:'0');
					$temp['categorySlug']='elementor';
					$temp['collectionId']=md5($id);
					$temp['collectionName']=$value['title'];
					$temp['collectionThumbnail']="";
					$temp['collectionUrl']=admin_url('?page=BsiteMaker-importer-menu');
					$temp['features']=array();
					$temp['filter']['industry']['food drink']=html_entity_decode($catName);
					$temp['filter']['search_template_count']=false;
					$templates=array();
					$countTemplate++;

					
					/*https://my.wpjelly.com/template/*/
					/*https://testyourprojects.net/wpnew2018/envado/*/
					$json_template_string = wp_remote_retrieve_body( wp_remote_get( 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplatechild/'.$id.'/innerkits' ) );
	        		$json_template_array = json_decode($json_template_string, TRUE);

					if(count($json_template_array['1'])>0)
					{
						foreach ($json_template_array['1'] as $key => $val) {
							$tempdata=array();
							$importFlag=$this->checkIfImported($val['id']);
							$tempdata['itemImported']=$importFlag;
							$tempdata['id']=$val['id'];

							$tempdata['largeThumb']['height']=$val['thumbnailimageheight'];
							$tempdata['largeThumb']['src']=$val['thumbnailimage'];
							$tempdata['largeThumb']['width']=$val['thumbnailimageWidth'];
							$tempdata['previewThumb']= $val['thumbnailimage'];
							$tempdata['previewThumbAspect']="100%";
							$tempdata['previewThumbHeight']= $val['prevheight'];
							$tempdata['previewUrl']= "";
							$tempdata['templateError']=true;
							$tempdata['templateFeatures']=array();
							$tempdata['templateId']=$val['id'].'|'.md5($val['id']);
							$tempdata['templateImportText']= "Import Template";
							$tempdata['templateInserted']=array();
							$tempdata['templateInstalled']=false;
							$tempdata['templateInstalledText']= "Edit Template";
							$tempdata['templateInstalledURL']= "#";
							$tempdata['templateMissingPlugins']=array();
							$tempdata['templateName']=html_entity_decode($val['title']);
							$tempdata['templateType']=array();
							$templates[]=$tempdata;
							
						}
					}
					else
					{
						$tempdata=array();
						$importFlag=$this->checkIfImported($id);
						$tempdata['itemImported']=$importFlag;
						$tempdata['id']=$id;

						$tempdata['largeThumb']['height']=$value['thumbnailimageheight'];
						$tempdata['largeThumb']['src']=$value['thumbnailimage'];
						$tempdata['largeThumb']['width']=$value['thumbnailimageWidth'];
						$tempdata['previewThumb']= $value['thumbnailimage'];
						$tempdata['previewThumbAspect']="100%";
						$tempdata['previewThumbHeight']= $value['prevheight'];
						$tempdata['previewUrl']= "";
						$tempdata['templateError']=true;
						$tempdata['templateFeatures']=array();
						$tempdata['templateId']=$value['id'].'|'.md5($value['id']);
						$tempdata['templateImportText']= "Import Template";
						$tempdata['templateInserted']=array();
						$tempdata['templateInstalled']=false;
						$tempdata['templateInstalledText']= "Edit Template";
						$tempdata['templateInstalledURL']= "#";
						$tempdata['templateMissingPlugins']=array();
						$tempdata['templateName']=html_entity_decode($value['title']);
						$tempdata['templateType']=array();
						$templates[]=$tempdata;
						//$countTemplate++;
					}
					$temp['res']=$info['all_results'];
					

					$temp['templates']=$templates;
					$temp['uuid']=md5($id);
					$results[]=$temp;
					$c++;
					if($c>5)
					{
						break;
					}
	    		}
	    	}
	    	
			
			$info['results']=$results;
			$info['show_coming_soon']=false;
			$info['total_results']=$json_array['0']['0']['totalpages'];

			$data['status']=1;
			$data['message']='All ok';
			$data['data']=$info;
			$meta['item_count']=array('collections'=>$json_array['0']['0']['totalpages'],
									  'is_filtered_count'=>false,
									  'is_tag_count'=>false,
									  'templates'=>$countTemplate);
			$data['meta']=$meta;
	    }
		// $info['all_results']=112;
		
		
		
		
		echo json_encode($data);
		die;
	}
	public function checkIfImported($value=NULL) {
		global $wpdb;
		$meta = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='_bsm_mainelement' AND meta_value='".$wpdb->escape($value)."'");
		if (!empty($meta)) {
			return true;
		}		
		else
		{
			return false;
		}
	}
	public function wpjellyTemplateImportDirect()
	{
		$template=$_POST['template'];
		$postId=$_POST['postId'];
		global $wpdb;
		$elementorSavedTemplate = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='_bsm_mainelement' AND meta_value='".$wpdb->escape($template)."'");
		if(!empty($elementorSavedTemplate))
		{
			$data['stat']=1;
			$data['cid']=$elementorSavedTemplate;
			$data['changed']=array();
			$data['_changing']=false;
			$data['_events']=array();
			$data['options']=array();
			$content=get_post_meta($elementorSavedTemplate,'_elementor_data',true);
			foreach ($content as $k=>$con) {
				$content[$k]['id']=$con['id'].time();
			}
			
			$data['content']=$content;
			$data['template_id']=$elementorSavedTemplate;
			$data['source']='local';

			$data['attributes']=array();
			echo json_encode($data);
		}
		else
		{
			$data['stat']=0;
			$data['error']='Template Not Found...';
		}

		
		die;
		/*----------------------------------------------------------------------------------------------------------*/
		// $template=$_POST['template'];
		// $postId=$_POST['postId'];
		// global $wpdb;
		// $elementorSavedTemplate = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='_bsm_mainelement' AND meta_value='".$wpdb->escape($template)."'");
		// $keys=array('_elementor_template_type','_elementor_page_settings','_wp_page_template','_elementor_edit_mode','_elementor_css','_elementor_version');
		// foreach ($keys as $key) {
		// 	$temp = get_post_meta( $elementorSavedTemplate, $key, true );
		// 	update_post_meta( $postId, $key, $temp );
			
		// }
		// $data=get_post_meta($elementorSavedTemplate,'_elementor_data',true);
		// $encodeddata=wp_slash($data);
		// update_post_meta( $postId, '_elementor_data', $encodeddata );
		// $msg=array();
		// $msg['stat']=1;
		// echo json_encode($msg);
		// die;
		/*----------------------------------------------------------------------------------------------------------*/
	}
	public function wpjellyTemplateImportDirectAfterProcessing($template,$postId)
	{
		global $wpdb;
		$elementorSavedTemplate = $template;
		$keys=array('_elementor_template_type','_elementor_page_settings','_wp_page_template','_elementor_edit_mode','_elementor_css','_elementor_version');
		foreach ($keys as $key) {
			$temp = get_post_meta( $elementorSavedTemplate, $key, true );
			update_post_meta( $postId, $key, $temp );
			
		}
		$data=get_post_meta($elementorSavedTemplate,'_elementor_data',true);
		$encodeddata=wp_slash($data);
		update_post_meta( $postId, '_elementor_data', $encodeddata );
	}
	public function wpjellyEditorGetTemplateByCategory()
	{
		$slug=$_POST['slug'];
		$generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getcategory/?cat='.$slug;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	?>
        	<div class="_1beWw6dhJqtlaSujooBD-i_wpjelly  " data-cy="results" style="">
        		
        		<?php foreach($data as $temp):?>
        			<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly   _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
			            <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
			                <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $temp->thumbnailimage;?>');"></div>
			                <?php if(in_array($temp->id, $metaData)):?>
			                <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"><span class="_1c-OSLjnymOFE8v6WI1DRM_wpjelly">Imported</span></div>
			            	<?php endif;?>
			                <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-id="<?php echo $temp->id;?>" data-imported="<?php echo(in_array($temp->id, $metaData)?'yes':'no');?>" data-type="page">&nbsp;</a>
			            </div>
			            <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
			                <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
			                    <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $temp->title?></h3>
			                </div>
			            </div>
		        	</div>
        		<?php endforeach;?>
        	</div>
        	<?php
        }
		die;
	}
	public function wpjellyEditorGetTemplateByCategorySingle()
	{
		$id=$_POST['templateId'];
		$stat=$_POST['stat'];
		$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/'.$id.'/checkJson';
		$request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	$temp=$data['0'];
		?>
		<div class="_1G8MTYEGch6S7EIP18rZZX_wpjelly" style="left: 60px; top: 65px; right: 59px; bottom: 8.70312px;">
            <div class="_3ceWlPqxcVsMxmRVBC5nW7_wpjelly">
            	<a class="S4My3zpRpm4AoQjEsVmRe_wpjelly wpjelly-back" href="#" data-pos="<?php echo $_POST['ref']?>">Back to Template Kit</a>
            </div>
		    <div class="_3QBZLRa-A77ImXOKPt4QuF_wpjelly" style="opacity: 1;">
		        <div class="HkZFhp-_JOEbNoIFo05pr_wpjelly">
		            <h3 class="_1AE4QdJp6k3lSwgrBW29H_wpjelly"><?php echo $temp->title;?></h3>
		        </div>
		        <div class="_-0rlK9vkhtu-cQLWwUr9R_wpjelly">
		            <div class="_39XoPAHQ9TPfb4rNYcozOV_wpjelly O0SnMFvj0Yj6E7krd6iNR_wpjelly" style="background-image: url('<?php echo $temp->thumbnailimage;?>'); height: 1360px; max-width: 3608px;">
		                <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div>
		                <img src="<?php echo $temp->thumbnailimage;?>" width="3608" height="1360" alt="Lobster" class="_3hZs6EpOrnn_tZlU_0trtK_wpjelly">
		            </div>
		        </div>
		        <div class="_2uvjKWnkHS3ThhMWs_FLxO_wpjelly">
		            <div class="wpjelly-template-editor-wrap">
		                <h3>Import Template</h3>
		                <div class="wpjelly-template-editor-desc-wrap">
		                    <p>Import this template to make it available in your Elementor Saved Templates list for future use.</p>
		                </div>
		                <div class="_99v9ImSmsc9WaLo1JRKTv_wpjelly"></div>
		                <button type="button" class="<?php echo (in_array($temp->id, $metaData)?'wpjelly-direct':'wpjelly-process');?> wpjelly-import _3HTeJ2APvEEbw6fuNZusDc_wpjelly" id="exporttemp" 
		                	data-template="<?php echo $temp->id;?>">
		                	<span class="wpjelyy-stat"><?php echo (in_array($temp->id, $metaData)?'Open Template in Library':'Import Template');?></span>
		                	<span class="wpjelly-load"><img src="<?php echo WSE_EX_EDIT_IMG;?>3.gif"></span>
		                </button>
		            </div>
		        </div>
		    </div>
		</div>
		<?php
		}
		die;
	}
	public function wpjellyEditorGetBlockParents()
	{
		$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getBlock';
		$request = wp_remote_get($generatedlink);
        ?>
        <div>
	        <div class="-cJ75CRkRvVzmNwM_Vydw_wpjelly">
	            <h1 class="SopIxoGl2fWJBUXAjkupc_wpjelly">Free Block Kits for Elementor</h1>
	            <div class="_1ppz4Z7Bufl0W3R-J5zlSZ_wpjelly"></div>
	        </div>
    	</div>
		<?php
		if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
		if (!empty($data)) {
        	?>
        	<div class="_1beWw6dhJqtlaSujooBD-i_wpjelly  " data-cy="results">
		        <?php foreach($data as $dat):
		        	?>
		        <div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly   _1W4G4Sg0msWYKbZSmgPErc_wpjelly wpjellySubBlockContainer231561dcssf">
		            <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
		                <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $dat->thumbnailimage;?>');"></div>
		                <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-sub-list blocks" href="<?php echo $dat->id;?>">&nbsp;</a></div>
		            <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
		                <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
		                    <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $dat->title;?></h3><?php echo $dat->childpage;?> Block Templates in this Kits</div>
		            </div>
		        </div>
		    	<?php endforeach;?>
    		</div>
        	<?php
        }
		die;
	}
	public function wpjellyEditorGetBlockChildren()
	{
		$parent=$_POST['parent'];
		$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getSubBlocks?parent='.$parent;
		$request = wp_remote_get($generatedlink);
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }
		if (is_wp_error($request)) {
           ?>
           
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
		if (!empty($data)) {
        	?>
        	<div class="_27svi568eoY6iGHsH0AspH_wpjelly" style="left: 60px; top: 64px; right: 59px; bottom: 7.15625px;">
	            <div class="_3odISSW0ZCb0xJqRipssxF_wpjelly"><a class="S4My3zpRpm4AoQjEsVmRe_wpjelly wpjelly-back" href="#" data-pos="1">Back to Elementor Templates</a></div>
	            <div class="vQ4ZqODCNdk0nVE-vOeMm_wpjelly">
	                <div class="_3w6jnp88EiiUQH4y8fa84w_wpjelly">
	                    <h3 class="_3_ok-fv1rQMCqBnmvULZLE_wpjelly"><?php echo $data->parent->title;?></h3><?php echo count($data->children);?> Block Templates in this Kits
	                </div>
	       
	                <div class="_32Ig5BZCWc8hMqZIGe92f1_wpjelly">
	                    <?php if(count($data->children)>0):
	                    		foreach ($data->children as $child):
	                    	?>
	                    <div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly wpjellySubBlockContainer231561dcssf">
	                        <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                            <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $child->thumbnailimage;?>');"></div>
	                            <?php if(in_array($child->id, $metaData)):?>
	                            	<div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"><span class="_1c-OSLjnymOFE8v6WI1DRM_wpjelly">Imported</span></div>
	                            <?php endif;?>
	                            <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="2" data-id="<?php echo $child->id;?>" data-type="section">&nbsp;</a></div>
	                        <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                            <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                                <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $child->title;?></h3>
	                            </div>
	                        </div>
	                    </div>
	                	<?php 
	            				endforeach;
	            			endif;?>
	                </div>
	            </div>
        	</div>
        	<?php
        }
		die;
	}
	public function wpjellyEditorGetTemplateChildren()
	{
		$parent=$_POST['parent'];
		$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getTemplatechild/'.$parent.'/innerkits';
		$request = wp_remote_get($generatedlink);
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }
		if (is_wp_error($request)) {
           ?>
           
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
		if (!empty($data)) {
			?>
			<div class="_27svi568eoY6iGHsH0AspH_wpjelly" style="left: 60px; top: 64px; right: 59px; bottom: 7.15625px;">
	            <div class="_3odISSW0ZCb0xJqRipssxF_wpjelly"><a class="S4My3zpRpm4AoQjEsVmRe_wpjelly wpjelly-back" href="#" data-pos="1">Back to Elementor Templates</a></div>
	            <div class="vQ4ZqODCNdk0nVE-vOeMm_wpjelly">
	                <div class="_3w6jnp88EiiUQH4y8fa84w_wpjelly">
	                    <h3 class="_3_ok-fv1rQMCqBnmvULZLE_wpjelly"><?php echo $data->parent->title;?></h3>
	                    <?php echo $data['0']['0']->totalpages;?> Page Templates in this Kits
	                </div>
	                <div class="_32Ig5BZCWc8hMqZIGe92f1_wpjelly">
	                    <?php if(count($data['1'])>0):
	                    		foreach ($data['1'] as $child):
	                    	?>
	                    <div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly">
	                        <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                            <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $child->thumbnailimage;?>');"></div>
	                            <?php if(in_array($child->id, $metaData)):?>
	                            	<div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"><span class="_1c-OSLjnymOFE8v6WI1DRM_wpjelly">Imported</span></div>
	                            <?php endif;?>
	                            <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="2" data-id="<?php echo $child->id;?>" data-type="section">&nbsp;</a></div>
	                        <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                            <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                                <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $child->title;?></h3>
	                            </div>
	                        </div>
	                    </div>
	                	<?php 
	            				endforeach;
	            			endif;?>
	                </div>
	            </div>
	        </div>
			<?php
		}
		die;
	}
	public function editorAjax()
	{
		add_action('wp_ajax_wpjellyEditorAllTemplates',[$this,'wpjellyEditorAllTemplates']);
		add_action('wp_ajax_wpjellygetBlockList',[$this,'getBlockListEditor']);
		add_action('wp_ajax_wpjellygetcatListEditor',[$this,'wpjellygetcatListEditor']);
		add_action('wp_ajax_wpjellyEditorAllTemplatesPaginate',[$this,'wpjellyEditorAllTemplatesPaginate']);
	}
	public function getCatListEditor()
    {
        $request = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getCatList');
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
            ob_start();
            foreach ($data as $value) {
                ?>
                <li class="_3kdBDJ8iqP12wNsiIE5fTu_wpjelly "><a class="_1RpgDdKG1S9mFx90CkOypP_wpjelly wpjelly_editorGetCat" href="<?php echo $value->slug; ?>"><?php echo $value->name; ?></a></li>
                <?php
        }
            $output = ob_get_contents();
            ob_end_clean();
            echo $output;
        }
    }
    public function getBlockList()
    {
    	$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getBlock';
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach($data as $i):
        	?>
        	<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly wpjellySubBlockContainer231561dcssf">
                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-sub-list blocks" href="<?php echo $i->id;?>">&nbsp;</a></div>
                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage;?> Block Templates in this Kits</div>
                </div>
            </div>
        	<?php
        endforeach;
        }
    }
    public function wpjellyEditorAllTemplates()
    {
   
    	$request = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/?source=popup');
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
            $getitems   = $data[0];
            $totalpages = $getitems[0]->totalpages;
            $countval   = $totalpages % 5;
            $count_post = $totalpages / 5;
            $pagecount  = round($count_post);
            $pagecount=($countval==0?$pagecount:$pagecount+1);
            foreach ($data['1'] as $i):
            	?>
            	<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-sub-list temp" href="<?php echo $i->id;?>">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage+1;?> Page Templates in this Kits</div>
	                </div>
	            </div>
            	<?php
            endforeach;
            ?>
            <ul class="uyNi5vnpqR70ai6xTfPZx_wpjelly">
                <li class="previous wLzaFB0hsFHbLmhvihjOJ_wpjelly"><a tabindex="0" role="button" aria-disabled="true">Previous</a></li>
               
                <?php echo wpjellyTemplatePaginationLinks::create(1, $pagecount);?>
                <li class="next"><a tabindex="0" role="button" data-page="next" <?php if($pagecount >1){ echo 'disabled'; } ?>>Next</a></li>
            </ul>
            <?php
        }
    	die;
    }
    public function wpjellyEditorAllTemplatesPaginate()
    {
    	$page=$_POST['page'];
    	$request = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/?source=popup&pagenumber='.$page);
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
            $getitems   = $data[0];
            $totalpages = $getitems[0]->totalpages;
            $countval   = $totalpages % 5;
            $count_post = $totalpages / 5;
            $pagecount  = round($count_post);
            $pagecount=($countval==0?$pagecount:$pagecount+1);
            foreach ($data['1'] as $i):
            	?>
            	<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-sub-list temp" href="<?php echo $i->id;?>">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage+1;?> Page Templates in this Kits</div>
	                </div>
	            </div>
            	<?php
            endforeach;

            ?>
            <ul class="uyNi5vnpqR70ai6xTfPZx_wpjelly">
                <li class="previous <?php echo($page<=1?'wLzaFB0hsFHbLmhvihjOJ_wpjelly':'');?>"><a tabindex="0" role="button" <?php echo($page<=1?'disabled':'');?> data-page="previous">Previous</a></li>
                <?php echo wpjellyTemplatePaginationLinks::create($page, $pagecount);?>
                <li class="next <?php echo($page>=$pagecount?'wLzaFB0hsFHbLmhvihjOJ_wpjelly':'');?>"><a tabindex="0" role="button" data-page="next" <?php echo($page>=$pagecount?'disabled':'');?>>Next</a></li>
            </ul>
            <?php
        }
    	die;
    }
    public function getBlockListEditor()
    {
    	$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getBlock';
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach($data as $i):
        	?>
	        	<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly wpjellySubBlockContainer231561dcssf">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-sub-list blocks" href="<?php echo $i->id;?>">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage;?> Block Templates in this Kits</div>
	                </div>
	            </div>
	        	<?php
	        endforeach;
        }
        die;
    }
    public function wpjellygetcatListEditor()
    {
    	$cat=$_POST['cat'];
    	$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getcategory/?cat='.$cat;
    	$request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach($data as $i):
        	?>
	        	<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <?php if(in_array($i->id, $metaData)):?><div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"><span class="_1c-OSLjnymOFE8v6WI1DRM_wpjelly">Imported</span></div><?php endif;?>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="1" data-id="<?php echo $i->id;?>">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3>
	                    </div>
	                </div>
	            </div>
	        	<?php
	        endforeach;
        }
    	die;
    }
    public function wpjelly_block_search_editor()
    {
    	$search=$_POST['s'];
    	$generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getBlockSearch?s='.$search;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	if(count($data)>0)
        	{
        		foreach ($data as $i) {
        		?>
        		<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly wpjellySubBlockContainer231561dcssf">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="1" data-id="<?php echo $i->id;?>" data-type="block">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage;?> Block Templates in this Kits</div>
	                </div>
	            </div>
        		<?php
        		}
        	}
        }
        else
        {
        	?>
        	<h3>No Results Found...</h3>
        	<?php
        }
    	die;
    }
    public function wpjelly_template_search_editor()
    {
    	$search=$_POST['s'];
    	$generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getallTemplateSearch?s='.$search;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach ($data as $i) {
        		?>
        		<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="1" data-id="<?php echo $i->id;?>" data-type="block">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage;?> Page Templates in this Kits</div>
	                </div>
	            </div>
        		<?php
        		}
        }
        else
        {
        	?>
        	<h3>No Results Found...</h3>
        	<?php
        }
        die;
    }
	public function wpjelly_category_search_editor()
	{
		$s=$_POST['s'];
        $catSlug=$_POST['cat'];
        $generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getCatSearch/?cat='.$catSlug.'&s='.$s;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach ($data as $i) { ?>
    		<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="1" data-id="<?php echo $i->id;?>" data-type="block">&nbsp;</a></div>
                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3><?php echo $i->childpage;?> Page Templates in this Kits</div>
                </div>
            </div>
    		<?php
    		}
        }
        else
        {
        	?>
        	<h3>No Results Found...</h3>
        	<?php
        }
        die;
	}
	public function editor_universal_template_search()
	{
		$search=$_POST['s'];
        $generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getUniSearch?s='.$search;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $bsmarg = array(
            'post_type' => 'elementor_library',
        );
        $getallmetaquery = get_posts($bsmarg);
        $metaData        = array();
        foreach ($getallmetaquery as $key => $value) {
            $metaData[] = get_post_meta($value->ID, '_bsm_mainelement', true);
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
        	foreach ($data as $i) {
        		?>
        		<div class="_6PH2ENYVVxP4Z-t9P8NGU_wpjelly  _1W4G4Sg0msWYKbZSmgPErc_wpjelly">
	                <div class="_144U0mjGxdSZEV_F9Xu426_wpjelly">
	                    <div class="_2MEJJMnQIcT6LpO-TuHpFf_wpjelly" style="background-image: url('<?php echo $i->thumbnailimage;?>');"></div>
	                    <div class="_1tf5Eqr9_5mXzG13BAnsQC_wpjelly"></div><a class="_1smM86XuFFLr0aS1IMSLbO_wpjelly wpjelly-editor-getSingleCatPage" href="#" data-ref="1" data-id="<?php echo $i->id;?>" data-type="block">&nbsp;</a></div>
	                <div class="_2Kl35RH8ZJUlumz3zg5GZ7_wpjelly">
	                    <div class="_3QGg9xmV4eIhaFDiKOzech_wpjelly">
	                        <h3 class="_1QQJIvJdcib1Nqth0eNZj0_wpjelly"><?php echo $i->title;?></h3></div>
	                </div>
            	</div>
        		<?php
        	}
        }
        else
        {
        	?>
        	<h3>No Results Found...</h3>
        	<?php
        }
        die;
	}
}
$WPJellyEditorAddon=new WPJellyEditorAddon();

function WpjellyMigrateImages($url)
{
	include_once( ABSPATH . 'wp-admin/includes/image.php' );
	$wpjellyreplace_image_ids=array();
	foreach ($url as $dat)
	 {
		if($dat!="#")
		{
			$imgDat=explode('|', $dat);
			$value=$imgDat['0'];
			$filename = basename( $value );
			$file_content = wp_remote_retrieve_body( wp_safe_remote_get( $value ) );
			if ( !empty( $file_content ) ) {
					$upload = wp_upload_bits(
					$filename,
					null,
					$file_content
			         );
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
				update_post_meta( $post_id, '_elementor_source_image_hash',sha1($value));
					$new_attachment = [
						'id' => $post_id,
						'url' => $upload['url'],
					];
					$wpjellyreplace_image_ids[$imgDat['0']]=$new_attachment;
				}
			}
		}
	 }
	 return $wpjellyreplace_image_ids;
}

function WpjellyFormatTemplateContent($content,$forms)
{
	$shortcode=WpjellysearchItemsByKey($content,'shortcode');
	$wp=WpjellysearchItemsByKey($content,'form_id');
	if(count($forms)>0 && count($shortcode)>0)
	{
		$replaceforms=array();
		foreach($forms as $formVal)
		{
			if(in_array($formVal->shortcode, $shortcode))
			{
				$importUrl=$formVal->export_file->url;
				$importData=wp_remote_retrieve_body( wp_remote_get( $importUrl ) );
				$importDecode=json_decode($importData);
				$newdata=WpjellyTemplateobjToArray($importDecode);
				foreach ($newdata as $form) {
					$oldId=$form['id'];
					$title  = ! empty( $form['settings']['form_title'] ) ? $form['settings']['form_title'] : '';
					$desc   = ! empty( $form['settings']['form_desc'] ) ? $form['settings']['form_desc'] : '';
					$new_id = wp_insert_post( array(
						'post_title'   => $title,
						'post_status'  => 'publish',
						'post_type'    => 'wpforms',
						'post_excerpt' => $desc,
					) );
					if ( $new_id ) {
						$form['id'] = $new_id;
						wp_update_post(
							array(
								'ID'           => $new_id,
								'post_content' => wp_slash( wp_json_encode( $form )),
							)
						);
						//echo $formVal->shortcode;
						$newShortcode=str_replace($oldId, $new_id, $formVal->shortcode);
						$replaceforms[$formVal->shortcode]=$newShortcode;
					}

				}

			}
		}
		if(count($replaceforms)>0)
		{
			$content=WpjellyfindandReplaceForms($content,$replaceforms,'shortcode');
		}
	}
	if(count($forms)>0 && count($wp)>0)
	{
		$replaceformID=array();
		foreach($forms as $formVal)
		{
			if(in_array($formVal->id, $wp))
			{
				$importUrl=$formVal->export_file->url;
				$importData=wp_remote_retrieve_body( wp_remote_get( $importUrl ) );
				$importDecode=json_decode($importData);
				$newdata=WpjellyTemplateobjToArray($importDecode);
				foreach ($newdata as $form) {
					$oldId=$form['id'];
					$title  = ! empty( $form['settings']['form_title'] ) ? $form['settings']['form_title'] : '';
					$desc   = ! empty( $form['settings']['form_desc'] ) ? $form['settings']['form_desc'] : '';
					$new_id = wp_insert_post( array(
						'post_title'   => $title,
						'post_status'  => 'publish',
						'post_type'    => 'wpforms',
						'post_excerpt' => $desc,
					) );
					if ( $new_id ) {
						$form['id'] = $new_id;
						wp_update_post(
							array(
								'ID'           => $new_id,
								'post_content' => wp_slash( wp_json_encode( $form )),
							)
						);
						//echo $formVal->shortcode;
						// $newShortcode=str_replace($oldId, $new_id, $formVal->shortcode);
						$replaceformID[$oldId]=$new_id;
					}

				}
			}
		}
		if(count($replaceformID)>0)
		{
			$content=WpjellyfindandReplaceForms($content,$replaceformID,'form_id');
		}
	}
		
	return $content;
}

function WpjellysearchItemsByKey($array, $key) {
	$results = array();

	if ( is_array( $array ) ) {
		if ( isset( $array[$key] ) && strlen( $array[$key] ) > 0 ) {
			$results[] = $array[$key];
		}

		foreach ( $array as $sub_array ) {
			$results = array_merge( $results, WpjellysearchItemsByKey( $sub_array, $key ) );
		}
	}

	return  $results;
}

function WpjellyfindandReplaceImages( &$array, $test ) {
	foreach( $array as $key => &$value ) { 
		if ( is_array( $value ) ) { 
			WpjellyfindandReplaceImages( $value, $test ); 
		} else {
			if ( $key == 'url' && !empty( $test[$value] ) ) {
				$array['id'] = $test[$value]['id'];
				$array[$key] = $test[$value]['url'];

				break;
			}
		}
	}

	return $array;
}

function WpjellyfindandReplaceForms(&$array,$test,$keyNew) {
        foreach($array as $key => &$value)
        { 
            if(is_array($value))
            { 
                WpjellyfindandReplaceForms($value,$test,$keyNew); 
            }
            else{
                
                if ($key==$keyNew && in_array($value, array_keys($test))) {
                	
                    $array[$key] = $test[$array[$key]];
                    
                    break;
                }
            } 
        }

        return $array;
}

function WpjellyTemplateobjToArray($obj)
{
    // Not an object or array
    if (!is_object($obj) && !is_array($obj)) {
        return $obj;
    }

    // Parse array
    foreach ($obj as $key => $value) {
        $arr[$key] = WpjellyTemplateobjToArray($value);
    }

    // Return parsed array
    return $arr;
}