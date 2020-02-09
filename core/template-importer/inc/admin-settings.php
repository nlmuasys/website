<?php
if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly
add_action('admin_menu', 'BSM_common_Theme');

function BSM_common_Theme()
{

    $page_title = 'WPJelly Template Importer';
    $menu_title = 'WPJelly Template Importer';
    $capability = 'manage_options';
    $menu_slug  = 'wpjelly-template-importer-menu';
    $function   = 'bsm_my_menu_output';
    //add_menu_page(  $page_title,  $menu_title,  $capability,  $menu_slug,  $function,'dashicons-images-alt2');
}
function bsm_my_menu_output()
{

    include WP_JELLY_DIR . 'core/template-importer/inc/fetchlisting.php';

}

/**
 * Admin Fetching Data
 */
class wpjellyTemplateImporterAdminSettings
{

    public function getCatList()
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
                <li><a class="nav-link wpjelly-temp-<?php echo $value->slug; ?>" onclick="getdetailbycategory('<?php echo $value->slug; ?>')">
                    <?php echo $value->name; ?></a></li>
                <?php
        }
            $output = ob_get_contents();
            ob_end_clean();
            echo $output;
        }
    }
    public function returnUrl()
    {
        $blogId  = get_current_blog_id();
        $siteUrl = get_site_url($blogId);
        return $siteUrl . '/wp-admin/admin.php?page=wpjelly-template-importer-menu';
    }
    public function allTemplates()
    {
        $generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/';
        $request = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/');
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
            $countval   = $totalpages % 15;
            $count_post = $totalpages / 15;
            $pagecount  = round($count_post);
            $pagecount=($countval==0?$pagecount:$pagecount+1);
            ?>
             <div class="wpjelly-template-layout-main-wrapper" style="display: none;">
                <div class="container-bsm-theme bsm-sm-3">
                    <div class="row">
                        <?php foreach ($data['1'] as $i): ?>
                            <div class="col-sm-3">
                                <div class="card bsm-image-block">
                                    <?php
                                    if (count($metaData) > 0 && in_array($i->id, $metaData)) {
                                        echo '<span class="bsm-install-tag">Installed</span>';
                                    }
                                    ?>
                                    <div class="bsm-imagewrapper">
                                        <img src="<?php echo $i->thumbnailimage; ?>" class="bsm-card-img-top" alt="img">
                                    </div>
                                    <div class="card-body bsm_card_body">
                                        <h5 class="card-title bsm_stylecontent"><?php echo $i->title; ?></h5>
                                        <p><?php echo $i->childpage ?> Page Templates in this Kits</p>
                                    </div>
                                    <?php
                                    if ($i->childpage <= 0) {
                                        echo '<button class="bsm-get-detail" onClick="getDetail(' . $i->id . ',3)"></button>';
                                    } else {
                                        echo '<button class="bsm-get-detail" onClick="innerpage(' . $i->id . ')"></button>';
                                    }
                                    ?>
                                </div>
                            </div>

                            </br>
                        <?php endforeach;?>
                    </div>
                </div>
                 <div class="bsm-pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                                <?php
                                 for ($checkcount = 0; $checkcount < $pagecount; $checkcount++) {
                                    if ($checkcount == 0) {
                                        $temp=$checkcount+1;
                                        echo '
                                        <li class="page-item active">
                                            <span class="page-link">'.$temp.'<span class="sr-only">(current)</span>
                                        </span><li>';
                                    }
                                    else
                                    {
                                        $temp=$checkcount+1;
                                        ?>
                                        <li class="page-item"><a class="page-link" onclick="paginationset(<?php echo $temp;?>,'<?php echo $generatedlink;?>',<?php echo $pagecount;?>)"><?php echo $temp;?></a></li>
                                        <?php
                                    }
                                }
                                ?>
                                <li class="page-item"><a class="page-link" onclick="paginationset(2,'<?php echo $generatedlink;?>',<?php echo $pagecount;?>)">Next</a></li>
                            </ul>
                 </div>
             </div>
             <div class="wpjelly-template-layout-inner-wrapper" style="display: none;"></div>
             <div class="wpjelly-template-layout-single-wrapper" style="display: none;"></div>
            <?php
        }
    }
    
}


class wpjellyTemplateImporterAdminSettingsAjax
{
    
    function __construct()
    {
        add_action('wp_ajax_wpjellyAdmintemplateImporterpagination',array($this,'wpjellyAdmintemplateImporterpagination'));
        add_action('wp_ajax_wpjellyAdmintemplateImporterInnerpage',array($this,'wpjellyAdmintemplateImporterInnerpage'));
        add_action('wp_ajax_wpjellyAdmintemplateImporterInnerpageDetail',array($this,'wpjellyAdmintemplateImporterInnerpageDetail'));
        add_action('wp_ajax_wpjellyAdmintemplateImporterCategory',array($this,'wpjellyAdmintemplateImporterCategory'));
        add_action('wp_ajax_wpjellyAdminBlockImporter',array($this,'wpjellyAdminBlockImporter'));
        add_action('wp_ajax_wpjellyAdminSubBlockImporter',array($this,'wpjellyAdminSubBlockImporter'));
        add_action('wp_ajax_wpjellyallTemplates',array($this,'allTemplates'));
    }
    public function allTemplates()
    {
        $generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/';
        $request = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/');
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
            $countval   = $totalpages % 15;
            $count_post = $totalpages / 15;
            $pagecount  = round($count_post);
            $pagecount=($countval==0?$pagecount:$pagecount+1);
            ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="bsm_menu_header_title col-sm-9">
                        <h1 class="bsm_menu_header_main_title">Free Template Kits for Elementor</h1>
                        
                    </div>
                    <div class="col-sm-3">
                       
                    <div class="searchings">
                        <span class="dashicons dashicons-search"></span>
                    <input type="text" data-destiny="wpjelly-all-template-container" id="wpjelly-all-temp-sr" placeholder="Type to search..." class="form-control wpjelly-search">
                   </div>
                
                
                </div>

                </div>
                    <div class="row wpjelly-all-template-container">
                       
                        <?php foreach ($data['1'] as $i): ?>
                            <div class="col-sm-3">
                                <div class="card bsm-image-block">
                                    <?php
                                    if (count($metaData) > 0 && in_array($i->id, $metaData)) {
                                        echo '<span class="bsm-install-tag">Installed</span>';
                                    }
                                    ?>
                                    <div class="bsm-imagewrapper">
                                        <img src="<?php echo $i->thumbnailimage; ?>" class="bsm-card-img-top" alt="img">
                                    </div>
                                    <div class="card-body bsm_card_body">
                                        <h5 class="card-title bsm_stylecontent"><?php echo $i->title; ?></h5>
                                        <p><?php echo $i->childpage+1; ?> Page Templates in this Kits</p>
                                    </div>
                                    <?php
                                    if ($i->childpage <= 0) {
                                        echo '<button class="bsm-get-detail" onClick="getDetail(' . $i->id . ',3)"></button>';
                                    } else {
                                        echo '<button class="bsm-get-detail" onClick="innerpage(' . $i->id . ')"></button>';
                                    }
                                    ?>
                                </div>
                            </div>

                            
                        <?php endforeach;?>
                       
                         <div class="bsm-pagination col-md-12">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                                <?php
                                 for ($checkcount = 0; $checkcount < $pagecount; $checkcount++) {
                                    if ($checkcount == 0) {
                                        $temp=$checkcount+1;
                                        echo '
                                        <li class="page-item active">
                                            <span class="page-link">'.$temp.'<span class="sr-only">(current)</span>
                                        </span><li>';
                                    }
                                    else
                                    {
                                        $temp=$checkcount+1;
                                        ?>
                                        <li class="page-item"><a class="page-link" onclick="paginationset(<?php echo $temp;?>,'<?php echo $generatedlink;?>',<?php echo $pagecount;?>)"><?php echo $temp;?></a></li>
                                        <?php
                                    }
                                }
                                ?>
                                <li class="page-item"><a class="page-link" onclick="paginationset(2,'<?php echo $generatedlink;?>',<?php echo $pagecount;?>)">Next</a></li>
                            </ul>
                        </div>
                    </div>
            </div>
           <?php
            die;
        }
    }
    public function wpjellyAdmintemplateImporterpagination()
    {
        $generatedlink = $_POST['url'];
        $request = wp_remote_get($generatedlink);
        $pagenumber=$_POST['pagenumber'];
        $gurl=$_POST['gurl'];
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
 
            $getitems   = $data[0];
            $totalpages = $getitems[0]->totalpages;
            $countval   = $totalpages % 15;
            $count_post = $totalpages / 15;
            $pagecount  = round($count_post);
            $pagecount=($countval==0?$pagecount:$pagecount+1);
            ?>
            <div class="container-bsm-theme bsm-sm-3">
                <div class="row">
                    <div class="bsm_menu_header_title col-sm-9">
                        <h1 class="bsm_menu_header_main_title">Free Template Kits for Elementor</h1>
                    </div>
                    <div class="col-sm-3">
                        <div class="searchings">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" data-destiny="wpjelly-all-template-paginate-container" id="wpjelly-all-temp-pg-sr" placeholder="Type to search..." class="form-control wpjelly-search">
                        </div>
                    </div>
                </div>
                <div class="row wpjelly-all-template-paginate-container">
                    <?php foreach ($data['1'] as $i): ?>
                        <div class="col-sm-3">
                            <div class="card bsm-image-block">
                                <?php
                                if (count($metaData) > 0 && in_array($i->id, $metaData)) {
                                    echo '<span class="bsm-install-tag">Installed</span>';
                                }
                                ?>
                                <div class="bsm-imagewrapper">
                                    <img src="<?php echo $i->thumbnailimage; ?>" class="bsm-card-img-top" alt="img">
                                </div>
                                <div class="card-body bsm_card_body">
                                    <h5 class="card-title bsm_stylecontent"><?php echo $i->title; ?></h5>
                                    <p><?php echo $i->childpage+1; ?> Page Templates in this Kits</p>
                                </div>
                                <?php
                                if ($i->childpage <= 0) {
                                    echo '<button class="bsm-get-detail" onClick="getDetail(' . $i->id . ',3)"></button>';
                                } else {
                                    echo '<button class="bsm-get-detail" onClick="innerpage(' . $i->id . ')"></button>';
                                }
                                ?>
                            </div>
                        </div>

                        </br>
                    <?php endforeach;?>
                </div>
                <div class="row">
                    <div class="bsm-pagination">
                <ul class="pagination justify-content-center">
                    <?php

                    if ($pagenumber > 1) {
                        $temp=$pagenumber-1;
                    ?>
                    <li class="page-item">
                        <a class="page-link" onclick="paginationset(<?php echo $temp;?>,'<?php echo $gurl;?>',<?php echo $pagecount;?>)">Previous</a>
                    </li>
                    <?php
                     } else {
                     ?>
                    <li class="page-item disabled">
                      <span class="page-link">Previous</span>
                    </li>
                    <?php
                    }
                    for ($checkcount = 0; $checkcount < $pagecount; $checkcount++) {
                        if (($pagenumber - 1) === $checkcount) {
                            $temp=$checkcount+1;
                            ?>

                         <li class="page-item active"><span class="page-link"><?php echo $temp;?><span class="sr-only">(current)</span>
                          </span></li>
                          <?php
                        } else {
                            $temp=$checkcount+1;
                            ?>

                    <li class="page-item"><a class="page-link" onclick="paginationset(<?php echo $temp;?>,'<?php echo $gurl;?>',<?php echo $pagecount;?>)"><?php echo $temp;?></a></li>
                    <?php
                }
            }
            if ($pagenumber < $pagecount) {
                $temp=$pagenumber+1;
                ?>
                <li class="page-item"><a class="page-link" onclick="paginationset(<?php echo $temp;?>,'<?php echo $gurl;?>',<?php echo $pagecount;?>)">Next</a></li>
                <?php
            } else {
                ?>
                <li class="page-item disabled"><span class="page-link">Next</span></li>
                <?php
            }
            ?>
                </ul>
             </div>
                </div>
             </div>
             
            <?php
        }
        else
        {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
    }
    public function wpjellyAdmintemplateImporterInnerpage()
    {
        $generatedlink = $_POST['url'];
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
            $getitems   = $data[0];
            $totalpages = $getitems[0]->totalpages;
            $countval   = $totalpages % 5;
            $count_post = $totalpages / 5;
            $pagecount  = round($count_post);
            ?>
            <div class="container-bsm-theme bsm-sm-3">
                <div class="row">
                    <?php foreach($data['1'] as $i):?>
           
                <div class="col-sm-3">
                    <div class="card bsm-image-block">
                        <?php if(count($metaData)>0 && in_array($i->id, $metaData)):?>
                            <span class="bsm-install-tag">Installed</span>
                        <?php endif;?>
                        <div class="bsm-imagewrapper">
                            <img src="<?php echo $i->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                        </div>
                        <div class="card-body bsm_card_body">
                            <h5 class="card-title bsm_stylecontent"><?php echo $i->title;?></h5>
                        </div>
                        <?php
                        if($i->childpage <= 0)
                        {
                            echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',2)"></button>';
                        }
                        else
                        {
                            echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',2)"></button>';
                         }
                         ?>
                    </div>
                </div>
            <?php endforeach;?>
            </div>
            </div>
            </br>
            <?php
                }
                else
                {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
    }
    public function wpjellyAdmintemplateImporterInnerpageDetail()
    {
        $generatedlink = $_POST['url'];
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
            ?>
            <div class="container-bsm-theme">
        <div class="row">
        <div class="col-sm-12 bsm-content-wrap">
            <h1 class="bsm_menu_header_main_title"><?php echo $data['0']->title;?></h1>
            <p class ="bsm_menu_header_subtitle"><?php echo $data['0']->content;?></p>
        </div>
       
        <div class="col-sm-7">
            <div class="bsm-full-img">
                <div class="bsm-imagescroll">
                    <img src="<?php echo $data['0']->mainimage;?>" class="bsm-card-img-top" alt="Main Img">
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="sidewrapper">
                <div class="bsm-template-wrap">
                <h3 class="bsm-template-title-wrap">Import Template</h3>
                <div class="bsm-template-desc-wrap">
                    <p>Import this template to make it available in your Elementor Saved Templates list for future use.</p>
                </div>
            <div id="bsmsetmessage">
            <?php if (count($metaData)>0 && in_array($data['0']->id, $metaData)) {
                echo '<button type="button" class="bsm_import_template" id="exporttemp" onClick="bsmredirecturl('.$data['0']->id.')">Open Template in Library</button>';
            } else {
                ?>
                <button type="button" class="bsm_import_template" id="exporttemp" onClick="uploadingJson('<?php echo $data['0']->json_url;?>',<?php echo $data['0']->id;?>)">Import Template<span id= "load_page" style="padding:5px; display:none" ><i class="fa fa-refresh fa-spin" style="font-size:10px padding:5px 5px"></i></span></button>
                <?php
            }
            ?>
            </div>
            </div>
                <div class="bsm-template-wrap">
                    <h3 class="bsm-template-title-wrap">Create Page from Template</h3>
                    <p>Create a new page from this template to make it available as a draft page in your Pages list.</p>
                    <div id="bsmsavetopage" style="padding-top:10px">
                        <input class="bsm_text_name" placeholder="Enter Page Name" id="bsm-set-page-name">
                        <button id="bsm-add-image" class="bsm_save_to_page" onClick="getcontentforpage('<?php echo $data['0']->json_url;?>',<?php echo $data['0']->id;?>)">Create Page<span id= "bsm_load_spage" style="padding:5px; display:none"><i class="fa fa-refresh fa-spin" style="color:blue;padding:5px 5px"></i></span></button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    
       </div>
        </div>
            <?php
        }
        else
        {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
    }
    public function wpjellyAdmintemplateImporterCategory()
    {
        $generatedlink = $_POST['url'];
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
            ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="bsm_menu_header_title col-sm-9">
                        <h1 class="bsm_menu_header_main_title">Free Template Kits for Elementor</h1>
                        
                    </div>
                    <div class="col-sm-3">

                    <div class="searchings">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" data-destiny="wpjelly-category-container" id="wpjelly-cat-sr" placeholder="Type to search..." class="form-control wpjelly-search" data-cat="<?php echo $_POST['catslug']?>">
                        </div>
                    </div>

                </div>
                <div class="row wpjelly-category-container">
                <?php foreach($data as $i):?>
                <div class="col-sm-3">
                    <div class="card bsm-image-block">
                <?php if (count($metaData)>0 && in_array($i->id, $metaData)){
                        echo '<span class="bsm-install-tag">Installed</span>';
                    }
                ?>
                <div class="bsm-imagewrapper">
                    <img src="<?php echo $i->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                </div>
                <div class="card-body bsm_card_body">
                    <h5 class="card-title bsm_stylecontent"><?php echo $i->title;?></h5>
                </div>
                <!-- <button class="bsm-get-detail" onClick="getDetail(<?php echo $i->id;?>,3)"></button> -->
                <?php
                if($i->childpage <= 0)
                {
                    echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',3)"></button>';
                }
                else
                {
                    echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',2)"></button>';
                 }
                 ?>
    
            </div>
            </div>
            <?php endforeach;?>
            </div>
            </div>
            <?php
        }
        else
        {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
    }
    public function wpjellyAdminBlockImporter()
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
            ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="bsm_menu_header_title col-sm-9">
                        <h1 class="bsm_menu_header_main_title">Free Template Kits for Elementor</h1>
                        
                    </div>
                    <div class="col-sm-3">
                      
                    <div class="searchings">
                        <span class="dashicons dashicons-search"></span>
                    <input type="text" data-destiny="wpjelly-block-container" id="wpjelly-block-sr" placeholder="Type to search..." class="form-control wpjelly-search">
                    </div>
                
                </div>

                </div>
                <div class="row wpjelly-block-container">
                    <?php foreach($data as $i):?>
                    <div class="col-sm-3">
                        <div class="card bsm-image-block" style="height: 200px;">
                
                            <div class="bsm-imagewrapper">
                                <img src="<?php echo $i->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                            </div>
                            <div class="card-body bsm_card_body" style="padding-bottom: 20px;">
                                <h5 class="card-title bsm_stylecontent"><?php echo $i->title;?></h5>
                                <p><?php echo $i->childpage;?> Block Templates in this Kits</p>
                            </div>
                    
                            <?php
                            if($i->childpage <= 0)
                            {
                                echo '<button class="bsm-get-detail" onClick="getBlockSingle('.$i->id.',3)"></button>';
                            }
                            else
                            {
                                echo '<button class="bsm-get-detail" onClick="getSubBlocks('.$i->id.',2)"></button>';
                             }
                             ?>
        
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
            <?php
        }
        die;
    }
    public function wpjellyAdminSubBlockImporter()
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
             <div class="container-bsm-theme bsm-sm-3">
                <div class="row">
                 <?php if(count($data->children)>0):
                        foreach ($data->children as $child):?>
                        <div class="col-sm-3">
                    <div class="card bsm-image-block" style="height: 200px;">
                         <?php if(count($metaData)>0 && in_array($child->id, $metaData)):?>
                             <span class="bsm-install-tag">Installed</span>
                         <?php endif;?>
                        <div class="bsm-imagewrapper">
                             <img src="<?php echo $child->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                         </div>
                         <div class="card-body bsm_card_body">
                             <h5 class="card-title bsm_stylecontent"><?php echo $child->title;?></h5>
                         </div>
                        <?php
                        
                        echo '<button class="bsm-get-detail" onClick="getDetail('.$child->id.',2)"></button>';
                        
                        ?>
                    </div>
                </div>
                <?php 
                        endforeach;
                        endif;?>
            
            </div>
             </div>
            </br>
             <?php
                }
                else
                {
             ?>
            <center><h2>No Result Found...</h2></center>
            <?php
                 }
           
        die;
        
    }
   
}
new wpjellyTemplateImporterAdminSettingsAjax();




class wpjellyTemplateImporterAjaxSearch 
{
    
    function __construct()
    {
       add_action('wp_ajax_wpjelly_block_search',array($this,'wpjelly_block_search'));
       add_action('wp_ajax_wpjelly_all_emplate',array($this,'wpjelly_all_emplate'));
       add_action('wp_ajax_wpjelly_category_search',array($this,'wpjelly_category_search'));
       add_action('wp_ajax_universal_template_search',[$this,'universal_template_search']);
    }
    public function wpjelly_block_search()
    {
        $s=$_POST['s'];
        $generatedlink='https://my.wpjelly.com/template/wp-json/pm/v1/getBlockSearch?s='.$s;
        $request = wp_remote_get($generatedlink);
        if (is_wp_error($request)) {
           ?>
           <center><h2 class="wpjelly-no-res">No Result Found...</h2></center>
           <?php
        }
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);
        if (!empty($data)) {
            foreach($data as $i):?>
            <div class="col-sm-3">
                <div class="card bsm-image-block" style="height: 200px;">
        
                    <div class="bsm-imagewrapper">
                        <img src="<?php echo $i->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                    </div>
                    <div class="card-body bsm_card_body" style="padding-bottom: 20px;">
                        <h5 class="card-title bsm_stylecontent"><?php echo $i->title;?></h5>
                    </div>
            
                    <?php echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',3)"></button>';?>

                </div>
            </div>
            <?php endforeach;
        }
        die;
    }
    public function wpjelly_all_emplate()
    {
        $s=$_POST['s'];
        $generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getallTemplateSearch?s='.$s;
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
            foreach ($data as $i):
                ?>
                <div class="col-sm-3">
                        <div class="card bsm-image-block">
                            <?php
                            if (count($metaData) > 0 && in_array($i->id, $metaData)) {
                                echo '<span class="bsm-install-tag">Installed</span>';
                            }
                            ?>
                            <div class="bsm-imagewrapper">
                                <img src="<?php echo $i->thumbnailimage; ?>" class="bsm-card-img-top" alt="img">
                            </div>
                            <div class="card-body bsm_card_body">
                                <h5 class="card-title bsm_stylecontent"><?php echo $i->title; ?></h5>
                                <p><?php echo $i->childpage ?> Page Templates in this Kits</p>
                            </div>
                            <?php
                            if ($i->childpage <= 0) {
                                echo '<button class="bsm-get-detail" onClick="getDetail(' . $i->id . ',3)"></button>';
                            } else {
                                echo '<button class="bsm-get-detail" onClick="innerpage(' . $i->id . ')"></button>';
                            }
                            ?>
                        </div>
                    </div>
                <?php
                endforeach;
        }
        die;
    }
    public function wpjelly_category_search()
    {
        $s=$_POST['s'];
        $catSlug=$_POST['catslug'];
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
            ?>
                <?php foreach($data as $i):?>
                <div class="col-sm-3">
                    <div class="card bsm-image-block">
                <?php if (count($metaData)>0 && in_array($i->id, $metaData)){
                        echo '<span class="bsm-install-tag">Installed</span>';
                    }
                ?>
                <div class="bsm-imagewrapper">
                    <img src="<?php echo $i->thumbnailimage;?>" class="bsm-card-img-top" alt="img">
                </div>
                <div class="card-body bsm_card_body">
                    <h5 class="card-title bsm_stylecontent"><?php echo $i->title;?></h5>
                </div>
                <!-- <button class="bsm-get-detail" onClick="getDetail(<?php echo $i->id;?>,3)"></button> -->
                <?php
                if($i->childpage <= 0)
                {
                    echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',3)"></button>';
                }
                else
                {
                    echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',2)"></button>';
                 }
                 ?>
    
            </div>
            </div>
            <?php endforeach;?>
            <?php
        }
        else
        {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
        die;
    }
    public function universal_template_search()
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
                <div class="col-sm-3">
                            <div class="card bsm-image-block">
                                <?php
                                if (count($metaData) > 0 && in_array($i->id, $metaData)) {
                                    echo '<span class="bsm-install-tag">Installed</span>';
                                }
                                ?>
                                <div class="bsm-imagewrapper">
                                    <img src="<?php echo $i->thumbnailimage; ?>" class="bsm-card-img-top" alt="img">
                                </div>
                                <div class="card-body bsm_card_body">
                                    <h5 class="card-title bsm_stylecontent"><?php echo $i->title; ?></h5>
                                    
                                </div>
                                <?php echo '<button class="bsm-get-detail" onClick="getDetail('.$i->id.',3)"></button>';?>
                            </div>
                        </div>
                <?php
                # code...
            }
        }
        else
        {
            ?>
           <center><h2>No Result Found...</h2></center>
           <?php
        }
        die;
    }

}
new wpjellyTemplateImporterAjaxSearch();