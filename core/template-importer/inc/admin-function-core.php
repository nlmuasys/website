<?php
add_action('wp_ajax_save_elem_export', 'bsmexport');
add_action('wp_ajax_save_page_det', 'bsmexportpage');
add_action('wp_ajax_redirecttosuccess', 'bsmredirectpage');
//add_action('wp_ajax_save_page_det','bsmsavepost');
function bsmexport()
{
    $pmgetstr = $_POST['seturl'];
    $bsmtype  = $_POST['selectcall'];
    $request  = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplateEditor/?id=' . $bsmtype);
    if (is_wp_error($request)) {

        echo 0;
        die;
    }
    $body = wp_remote_retrieve_body($request);
    $data = json_decode($body);
    if (!empty($data)) {
        $fileContent = wp_remote_retrieve_body( wp_remote_get( $data->json ) );
        $json        = json_decode($fileContent, true);
        $content     = $json['content'];
        $title         = $json['title'];
        $type          = $json['type'];
        $version       = $json['version'];
        $page_settings = $json['page_settings'];
        $form          = $data->form;
        $url=WpjellysearchItemsByKey($content,'url');
        if (is_array($form) && count($form) > 0) {
            $content = WpjellyFormatTemplateContent($content, $form);
        }
         if(is_array($url) && count($url)>0)
         {
            WpjellyMigrateImages($url);
         }
        $elementor_post = array('post_title' => $title, 'post_content' => '', 'post_status' => 'publish', 'post_author' => 1, 'post_type' => 'elementor_library', 'post_name' => $title);
        // Insert the post into the database
        $insert_id = wp_insert_post($elementor_post);
        add_post_meta($insert_id, '_bsm_mainelement', $bsmtype);
        add_post_meta($insert_id, '_bsm_pagedid', $insert_id);
        //echo $insert_id;
        update_post_meta($insert_id, '_elementor_template_type', $type);
        update_post_meta($insert_id, '_elementor_edit_mode', 'builder');
        update_post_meta($insert_id, '_wp_page_template', 'elementor_header_footer');
        update_post_meta($insert_id, '_elementor_data', $content);
        update_post_meta($insert_id, '_elementor_version', $version);
        update_post_meta($insert_id, '_elementor_page_settings', $page_settings);
        $msg='<div id="message" style="display:block;" class="updated notice is-dismissible"><p>Added Successfully.</p></div>';
        $msg.='<br><a class="bsm_import_template" id="exporttemp" target="_blank" href="'.get_edit_post_link($insert_id).'&action=elementor">Open Template in Library</a>';
        echo json_encode(array('stat'=>1,'msg'=>$msg));
    } else {
        echo json_encode(array('stat'=>0,'msg'=>$msg));
    }
    die;
}
function bsmexportpage()
{
    $pmgetpageurl = $_POST['jsonurl'];
    $newtitle     = $_POST['bsm_title'];
    $template     = $_POST['template'];
    $request      = wp_remote_get('https://my.wpjelly.com/template/wp-json/pm/v1/getTemplateEditor/?id=' . $template);
    if (is_wp_error($request)) {
        echo json_encode(["checkslug" => "0"]);
        die;
    }
    $body = wp_remote_retrieve_body($request);
    $data = json_decode($body);
    if (!empty($data)) {
        $fileContent = wp_remote_retrieve_body( wp_remote_get( $data->json ) );
        $jsond       = json_decode($fileContent, true);
        $titles      = $jsond['title'];
        if (!empty($_POST['bsm_title'])) {
            $title = $newtitle;
        } else {
            $title = $titles;
        }
        $content = $jsond['content'];
        $form    = $data->form;
        $url=WpjellysearchItemsByKey($content,'url');
        if (is_array($form) && count($form) > 0) {
            $content = WpjellyFormatTemplateContent($content, $form);
        }

        if(is_array($url) && count($url)>0)
         {
            WpjellyMigrateImages($url);
         }

         

        $elementor_pagearry = array(
            'post_title'   => $title,
            'post_content' => 'adada',
            'post_status'  => 'draft',
            'post_date'    => date('Y-m-d H:i:s'),
            'post_author'  => 1,
            'post_type'    => 'page',
        );
        $insertpost_id = wp_insert_post($elementor_pagearry);
        if ($insertpost_id && !is_wp_error($insertpost_id)) {
            update_post_meta($insertpost_id, '_wp_page_template', 'elementor_header_footer');
            update_post_meta($insertpost_id, '_elementor_edit_mode', 'builder');
            update_post_meta($insertpost_id, '_elementor_data', $content);
        }
        $generate_url = get_edit_post_link($insertpost_id);
        $recreatedurl = str_replace("edit", "elementor", $generate_url);
        echo json_encode(["checkslug" => "1", "id" => $insertpost_id, "name" => $title, "url" => $recreatedurl]);
    } else {
        echo json_encode(["checkslug" => "0"]);
    }

    die;
}
function bsmredirectpage()
{
    $fetchedpostid = $_POST['setpostid'];
    // echo $fetchedpostid;
    $bsmarg          = array('post_type' => 'elementor_library', 'meta_query' => array(array('key' => '_bsm_mainelement', 'value' => $fetchedpostid)));
    $getallmetaquery = get_posts($bsmarg);
    $metaData        = array();
    foreach ($getallmetaquery as $key => $value) {
        $metaData[] = $value->ID;
    }
    echo $metaData[0];
    die;
}
