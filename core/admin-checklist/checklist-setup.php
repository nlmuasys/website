<?php
/**
 * Wpjelly subsites theme panel setup
 */


function wpjellyChecklistPluginInitCheckListData()
{
    $request = wp_remote_get( 'https://wpjelly.com/wp-json/export/v1/checklistSetup' );
      if( is_wp_error( $request ) ) {
        return false; // Bail early
      }
      $body = wp_remote_retrieve_body( $request );
      $data = json_decode( $body );
      if( ! empty( $data ) ) {
        $points=$data->checkListOptions;
        $points=preg_replace('/\\\\\"/', "\"", $points);
        $checklistPointsKey = 'wpjellyChecklistPointsList';
        update_option($checklistPointsKey, $points);
        $stat=$data->status;
        $checklistStat = 'wpjellyChecklistStatus';
        update_option($checklistStat, $stat);
      }
}




class wpjellyChecklistThemePanel
{
	
	function __construct()
	{
		$this->initialise();
	}
	public function initialise()
	{
		add_action('admin_menu', array($this,'wpjellyChecklistThemePanelMenu'));
		add_action('admin_enqueue_scripts',array($this,'scriptsStyles'));
		add_action('admin_head',array($this,'adminHeadStyle'));
		add_action( 'admin_notices',array($this,'wpjellySiteExpirationAndChecklistAdminNoticeBar') );
		add_action('wp_ajax_wpcd_checklist_admin_form',array($this,'wpcd_checklist_admin_form'));
		
	}
	public function wpjellyChecklistThemePanelMenu()
	{
		$stat=get_option('wpjellyChecklistStatus');
    
    if($stat=='enable')
    {
      add_menu_page('WPJelly', 'WPJelly', 'manage_options', 'wpjelly-theme-pannel',array($this,'wpjellyChecklistThemePanelManagePage'),WP_JELLY_IMG.'/wpjelly-icon2.svg',10);
      add_submenu_page( 'wpjelly-theme-pannel', 'WP Jelly Checklist', 'WP Jelly Checklist',
        'manage_options', 'wpjelly-theme-pannel',array($this,'wpjellyChecklistThemePanelManagePage'));
      add_submenu_page( 'wpjelly-theme-pannel', 'Wp Jelly Template Importer', 'Wp Jelly Template Importer',
        'manage_options', 'wpjelly-template-importer-menu','bsm_my_menu_output');
    }
    else
    {
      add_menu_page('WPJelly', 'WPJelly', 'manage_options', 'wpjelly-theme-pannel','bsm_my_menu_output',WP_JELLY_IMG.'/wpjelly-icon2.svg',10);
      add_submenu_page( 'wpjelly-theme-pannel', 'Wp Jelly Template Importer', 'Wp Jelly Template Importer',
        'manage_options', 'wpjelly-theme-pannel','bsm_my_menu_output');
    }
	}
	public function wpjellyChecklistThemePanelManagePage()
	{
		$obj=new wpjellyDashboadChecklistManagePage();
		?>
		<div class="wrap">
			<div class="wpjelly-subsite-admin-loader-wrapper">
				<img class="wpjelly-subsite-admin-loader" src="<?php echo WP_JELLY_IMG.'/jellyfish.gif'?>">
			</div>
			<form action="" method="post" class="wpcd-chceklist-subadmin-form wpjelly-subsite-admin-parent">
				<?php echo $obj->wpjellySubsiteChecklistFormsSubsite();?>
			</form>
		</div>
		<?php
	}
	public function scriptsStyles()
	{	    
	    wp_enqueue_script( 'wse-checklist-script', WP_JELLY_JS . '/admin-checklist.js', array(), WP_JELLY_VERSION, true );
      wp_localize_script('wse-checklist-script', 'wpcdControl', array(
	       'ajaxUrl' => admin_url('admin-ajax.php')
	    ));
	}
	public function adminHeadStyle()
	{
    if(isset($_GET['page']) && $_GET['page']=='wpjelly-theme-pannel')
    {
      echo '<style>.update-nag, .updated, .error, .is-dismissible { display: none; }</style>';
    }
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo WP_JELLY_CSS . '/admin-checklist.css';?>">
		<?php
	}
	public function wpjellySiteExpirationAndChecklistAdminNoticeBar() {
		
    $stat=get_option('wpjellyChecklistStatus');
    if($stat=='enable'):
		$count=$this->getSubsiteCheckListPercentage();
		ob_start();
		?>
		<div class="wpjelly-theme-panel-notice-container">
		<table class="form-table" role="presentation">
		   <tbody>
		      <tr>
		         <th scope="row"><a href="<?php echo admin_url('admin.php?page=wpjelly-theme-pannel'); ?>" class="wpjelly-notice-button" role="button">View Checklist</a></th>
		         <td>
		         	<span class="wpjelly-checklist-percentage"><?php echo $count.'%';?></span>
		            <div id="wpjelly-subsite-checklit-progress" data-count="<?php echo $count;?>">
					  <div id="wpjelly-subsite-checklit-progress-Bar"></div>
					</div>
		         </td>
		      </tr>
		   </tbody>
		</table>
		</div>
		<?php
		$htmlIntermediateData = ob_get_contents();
		ob_end_clean();
		echo $htmlIntermediateData;
    endif;
	}
	public function getSubsiteCheckListPercentage()
	{
      $pointCount=0;
      $total=0;
      $checkListOptions=get_option('wpjellyChecklistPointsList');
      $checkListFlag=true;
      if($checkListOptions!=NULL)
      {
          $checkListFlag = @unserialize($checkListOptions);
      }
      else
      {
          $checkListFlag=false;
      }
      $points=array();
      if($checkListFlag!==false)
      {
        $points=unserialize($checkListOptions);
      }
      if(count($points)>0)
      {
        
        $blogCheckList=$this->getSubSiteChcekedOptions();
        $unitCheckList=array();
        if(strlen($blogCheckList)>0)
        {
          $unitCheckList=unserialize($blogCheckList);
        }
        foreach($points as $point):
    		$total++;	
            $unitCheck=$unitCheckList[$point['wpcd_slug']];
            if($point['wpcd_preCheck']=='Yes' || $unitCheck==1)
            {
              $pointCount++;
            }
        endforeach;
        $percentage=$pointCount/$total;
      	$percentage=$percentage*100;
      	$percentage=round($percentage);
       }
       else
       {
       	$percentage=0;
       }
      
      return $percentage;
	}
	public function getSubSiteChcekedOptions()
	{
	    $blogKey='wpjellycheckedPoints';
	    $blogCheckdata=get_option($blogKey);
	    return $blogCheckdata;
	}
	public function wpcd_checklist_admin_form()
	{
		$data=$_POST['data'];
	    $info=array();
	    parse_str($data,$info);
      $temp=array();
      $keys=$info['wpcd_slug'];
      foreach ($keys as $key ) {
        
        $temp[$key]=(isset($info[$key])?$info[$key]:0);
      }

	    $tempData=serialize($temp);
	    $blogKey='wpjellycheckedPoints';
	    $blogCheckdata=get_option($blogKey);
	    if(strlen($blogCheckdata)>0)
	    {
	      update_option($blogKey, $tempData);
	    }
	    else
	    {
	      add_option($blogKey, $tempData);
	    }
	    echo $this->getSubsiteCheckListPercentage();
	 	die;
	}
}
new wpjellyChecklistThemePanel();



/**
 * 
 */
class wpjellyDashboadChecklistManagePage
{
  public function getSubsiteOption()
  {
    $subsiteOption = 'wpjellyChecklistPointsList' ;
    $optionValue=get_option( $subsiteOption );
    if($optionValue!==false)
    {
      return $optionValue;
    }
    else
    {
      return NULL;
    }
  }
  public function wpjellySubsiteChecklistListing()
  {
    $tabs='';
    $checkListOptions=$this->getSubsiteOption();
    $checkListFlag=true;
    if($checkListOptions!=NULL)
    {
        $checkListFlag = @unserialize($checkListOptions);
    }
    else
    {
        $checkListFlag=false;
    }
    $checkListPoints=array();
    if($checkListFlag!==false)
    {
      $checkListPoints=unserialize($checkListOptions);
    }
    if(count($checkListPoints)>0):
    $tabs.='<ul class="wpcd-super-tabs-menu wpcd-super-reorder-ul wpcd-reorder-photos-list">';
      if(count($checkListPoints)>0):
    $wpcd_count=1;
        foreach ($checkListPoints as  $key=>$slide):
          $tabs.='<li id="image_li_'.$key.'" class="ui-sortable-handle '.($key==1?'current':'').'" 
                        ><a style="float:none;" href="#wpcd-super-tab-'.$key.'" class="wpcd-image-link">'.$slide['wpcd_title'].'</a><a href="#" class="wpcd-del"><span class="dashicons dashicons-trash"></span></a></li>';
    
                    $wpcd_count++;
                    endforeach;
                    endif;
                   
      $tabs.='</ul>';
      else:
        $tabs.='<ul class="wpcd-super-tabs-menu wpcd-super-reorder-ul wpcd-reorder-photos-list">';
        $tabs.='<li id="image_li_1" class="ui-sortable-handle current" 
                        ><a style="float:none;" href="#wpcd-super-tab-1" class="wpcd-image-link">UNTITLED</a><a href="#" class="wpcd-del"><span class="dashicons dashicons-trash"></span></a></li>';
        $tabs.='</ul>';
      endif;
      return $tabs;  
  }
  public function wpjellySubsiteChecklistForms()
  {
    $res='';
    $checkListOptions=$this->getSubsiteOption();
    $checkListFlag=true;
    if($checkListOptions!=NULL)
    {
        $checkListFlag = @unserialize($checkListOptions);
    }
    else
    {
        $checkListFlag=false;
    }
    $points=array();
      if($checkListFlag!==false)
      {
        $points=unserialize($checkListOptions);
      }
      if(count($points)>0):
                $wpcd_tab=1;
                foreach ($points as $key=>$slide):
                  $option_name = 'wpcd_logo[]';
                  $check=$slide['wpcd_preCheck'];
                  $check=(strlen($check)>0?$check:'No');
                $res.='<div id="wpcd-super-tab-'.$key.'" class="wpcd-super-tab-content '.($key==1?'current-tab-content':'').'">';
                $res.='<form class="wpcd-super-validate wpcd-valid">
                             <div class="wpcd-super-form-container">
                                 <h2>Check List Point Title</h2>
                                 <input type="text" name="wpcd_title[]" class="wpcd-super-title" required value="'.$slide['wpcd_title'].'">
                                <input type="hidden" name="wpcd_slug[]" class="wpcd-super-slug" value="'.$slide['wpcd_slug'].'">
                                 <h2>Check List Point Description</h2>
                                 <textarea name="wpcd_desc[]">'.$slide['wpcd_desc'].'</textarea>
                                 <h2>Check List Point Admin Url</h2>
                                 <input type="url"  name="wpcd_url[]" value="'.$slide['wpcd_url'].'">
                                 <h2>Check List Point Pre Checked</h2>
                                 <div class="wpcd-radio-control">
                                    Yes<input type="radio" name="wpcd-choice-select'.$key.'" value="Yes" '.($check=='Yes'?'checked':'').'>
                                    No<input type="radio" name="wpcd-choice-select'.$key.'" value="No" '.($check=='No'?'checked':'').'>
                                 </div>
                             </div>
                             <div class="wpcd-super-submit-container">
                                <input type="submit" value="Please Confirm" class="wpcd-super-form-submit wpcd-confirm">
                             </div>
                             <span class="wpcd-error">The Logo is a mandatory Field</span>
                             <img src="'.WPCD_WPJELLY_IMG. '/30.gif" class="wit-logo-slider-sub-loader" style="display: none;">
                          </form>
                       </div>
                    ';
                    
    $wpcd_tab++;
    endforeach;
    else:
      $res.='<div id="wpcd-super-tab-1" class="wpcd-super-tab-content current-tab-content">';
                $res.='<form class="wpcd-super-validate">
                             <div class="wpcd-super-form-container">
                                 <h2>Check List Point Title</h2>
                                 <input type="text" name="wpcd_title[]" class="wpcd-super-title" required value="">
                                 <input type="hidden" name="wpcd_slug[]" class="wpcd-super-slug" value="">
                                 <h2>Check List Point Description</h2>
                                 <textarea name="wpcd_desc[]"></textarea>
                                 <h2>Check List Point AdminUrl</h2>
                                 <input type="url"  name="wpcd_url[]" value="">
                             </div>
                             <div class="wpcd-super-submit-container">
                                <input type="submit" value="Please Confirm" class="wpcd-super-form-submit wpcd-confirm">
                             </div>
                             <span class="wpcd-error">The Logo is a mandatory Field</span>
                             <img src="'.WPCD_WPJELLY_IMG . '/30.gif" class="wit-logo-slider-sub-loader" style="display: none;">
                          </form>
                       </div>
                    ';
    endif;

    return $res;
  }
  public function wpjellySubsiteChecklistFormsSubsite()
  {
    $res='';
    $checkListOptions=get_option('wpjellyChecklistPointsList');
    $checkListFlag=true;
    if($checkListOptions!=NULL)
    {
        $checkListFlag = @unserialize($checkListOptions);
    }
    else
    {
        $checkListFlag=false;
    }
    $points=array();
    if($checkListFlag!==false)
    {
      $points=unserialize($checkListOptions);
    }
    if(count($points)>0)
    {
     
      $blogCheckList=$this->getSubSiteChcekedOptions();
      $unitCheckList=array();
      if(strlen($blogCheckList)>0)
      {
        $unitCheckList=unserialize($blogCheckList);
      }
      ob_start();
      ?>
      <div class="wpjelly-subsite-checklist-grid-container">
        <?php foreach($points as $point):
              $unitCheck=$unitCheckList[$point['wpcd_slug']];
              $check=($point['wpcd_preCheck']=='Yes' || $unitCheck==1?'checked':'');
              $disable=($point['wpcd_preCheck']=='Yes'?'readonly onclick="return false;"':'');
          ?>
        <div class="wpjelly-subsite-checklist-grid-item">
          <div class="wpjelly-subsite-checklist-sub-box">
            <div id="checkbox" class="wpjelly-subsite-checklist-media-content">

              <label class="custom-check">
              <input type="checkbox" name="<?php echo $point['wpcd_slug'];?>" value="1" <?php echo $check;?> <?php echo $disable;?> class="wpcd-subsite-checklist-control"> 
              <input type="hidden" name="wpcd_slug[]" value="<?php echo $point['wpcd_slug'];?>">
              <span class="checkmark"></span>
            </label>


            </div>
            <h3><?php echo $point['wpcd_title'];?></h3>
            <div class="wpjelly-subsite-content">
            <?php echo apply_filters('the_content',$point['wpcd_desc']);?>
             </div>
            <a href="<?php echo $point['wpcd_url'];?>" target="_blank">GO TO THE OPTION</a>
          </div>
        </div>
        <?php endforeach;?>  
      </div>
      <?php
      $htmlIntermediateData = ob_get_contents();
      ob_end_clean();
      return $htmlIntermediateData;
    }
    else
    {
      ob_start();
      ?>
      <center><h1>Welcome To Wpjelly</h1></center>
      <?php
      $htmlIntermediateData = ob_get_contents();
      ob_end_clean();
      return $htmlIntermediateData;
    }
  }
  public function getSubSiteChcekedOptions()
  {
    $blogKey='wpjellycheckedPoints';
    $blogCheckdata=get_option($blogKey);
    return $blogCheckdata;
  }

}


class wpjellyApiChecklist 
{
  function __construct()
  {
    add_action( 'rest_api_init',array($this,'getAdminSetCheckPoints'));
  }
  public function getAdminSetCheckPoints()
  {
    register_rest_route( 'export/v1', '/checklistSetup', array(
          'methods' => 'GET',
          'callback' => array($this,'getAdminSetCheckPointsCallBack'),
        ));
  }
  public function getAdminSetCheckPointsCallBack()
  {
    return 'hellow';
  }
}
new wpjellyApiChecklist();