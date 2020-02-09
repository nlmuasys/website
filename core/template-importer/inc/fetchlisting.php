<?php

$obj=new wpjellyTemplateImporterAdminSettings();

?>
<style>
  /*#wpfooter{position: relative;}*/
</style>
<div class="wpjelly-template-exporter-wrap">
  <div id="json-elements-react">
    <div class="allbody">
    <div class="jsonHeader">
      <ul class="bsm-siteNav">
        <li>
          <div class="bsm-Header-logo">
            <a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=wpjelly-template-importer-menu" class="custom-logo-link" rel="home"><img src="<?php echo WP_JELLY_IMG; ?>/symbol.png" alt=""></a>
          </div>
        </li>
        <li><a class="wpjelly-blocks nav-link active" onclick="getBLockListing()">Blocks</a></li>
        <li><a class="nav-link wpjelly-temp-all" onclick="getdetailbycategory('all')">All</a></li>
        <?php $obj->getCatList();?>
        
      </ul>

      <a class="bsm_back_class" href="#" style="display: none;" data-step="1"><i class="fa fa-angle-left" aria-hidden="true"></i> Return Back</a>

    </div>
      <div class="bsm_content-section">
      <div class="jsonBody" id="jsonBody">
        <div class="bsm-loader">
          <img src="<?php echo WP_JELLY_IMG; ?>/jellyfish.gif" class="bsm_center">
        </div>
        <div class="wpjelly-template-layout-main-wrapper" style="display: none;"></div>
        <div class="wpjelly-template-layout-inner-wrapper" style="display: none;"></div>
        <div class="wpjelly-template-layout-single-wrapper" style="display: none;"></div>
        
      </div>
        <div class="jsonFooter">
        </div>
      </div>
    </div>
    </div>
</div>
