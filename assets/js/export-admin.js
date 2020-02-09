jQuery(document).ready(function() {
    // var generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/';
    // var fetchassociated = JSON.parse(wpjellyTemplateControl.meta);

    setTimeout(function() {
        // jQuery.getJSON(generatedlink, (items) => {
        //     jQuery(".bsm-loader").fadeOut();
        //     jQuery(".wpjelly-template-layout-main-wrapper").fadeIn();
        // });
        jQuery.ajax({
            url: wpjellyTemplateControl.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpjellyAdminBlockImporter'
            },
            success: function(res) {
                jQuery(".bsm-loader").fadeOut();
                jQuery(".wpjelly-template-layout-main-wrapper").html(res);
                jQuery('.wpjelly-template-layout-main-wrapper').fadeIn();
            }
        });
    }, 1000);
    //onload
});

jQuery(function($){
    $(".bsm_back_class").click(function(e){
        e.preventDefault();
        var step=$(this).attr('data-step');
        if(step==1)
        {
             $(".wpjelly-template-layout-inner-wrapper").fadeOut();
            $(".wpjelly-template-layout-single-wrapper").fadeOut();
            $(".wpjelly-template-layout-main-wrapper").fadeIn();
            $(this).fadeOut();
            jQuery(".bsm-siteNav").fadeIn();
        }
        if(step==2)
        {
            $(".wpjelly-template-layout-single-wrapper").fadeOut();
            $(".wpjelly-template-layout-main-wrapper").fadeOut();
            $(this).attr('data-step',1);
            $(".wpjelly-template-layout-inner-wrapper").fadeIn();
            jQuery(".bsm-siteNav").fadeOut();
            $(this).fadeIn();
            
        }
        if(step==3)
        {
            $(".wpjelly-template-layout-inner-wrapper").fadeOut();
            $(".wpjelly-template-layout-single-wrapper").fadeOut();
            $(".wpjelly-template-layout-main-wrapper").fadeIn();
            $(this).fadeOut();
            jQuery(".bsm-siteNav").fadeIn();
            
        }
       
            

    });
});
function innerpage($data) {
    var getchildlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplatechild/' + $data + '/innerkits';
    jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
    jQuery(".bsm-siteNav").fadeOut();
    jQuery(".bsm-loader").fadeIn();
    jQuery.ajax({
        url: wpjellyTemplateControl.ajaxurl,
        method: 'POST',
        data: {
            url: getchildlink,
            action: 'wpjellyAdmintemplateImporterInnerpage'
        },
        success: function(res) {
            jQuery(".bsm-loader").fadeOut();
            jQuery(".wpjelly-template-layout-inner-wrapper").html(res);
            jQuery(".wpjelly-template-layout-inner-wrapper").fadeIn();
            jQuery(".bsm_back_class").fadeIn();
            // jQuery(".wpjelly-template-layout-main-wrapper").html(res);
            
            //jQuery(".wpjelly-template-layout-main-wrapper").fadeIn();
        }
    });

}
// getting single detail
function getDetail($data,back) {
    var detaillink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getTemplate/' + $data + '/checkJson';
    jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
    jQuery('.wpjelly-template-layout-inner-wrapper').fadeOut();
    jQuery(".bsm_back_class").attr('data-step',back);
    jQuery(".bsm-siteNav").fadeOut();
    jQuery(".bsm-loader").fadeIn();
    jQuery.ajax({
        url: wpjellyTemplateControl.ajaxurl,
        method: 'POST',
        data: {
            url: detaillink,
            action: 'wpjellyAdmintemplateImporterInnerpageDetail'
        },
        success: function(res) {
            jQuery(".bsm-loader").fadeOut();
            jQuery(".wpjelly-template-layout-single-wrapper").html(res);
            jQuery(".wpjelly-template-layout-single-wrapper").fadeIn();
            jQuery(".bsm_back_class").fadeIn();
            // jQuery(".wpjelly-template-layout-main-wrapper").html(res);
            
            //jQuery(".wpjelly-template-layout-main-wrapper").fadeIn();
        }
    });
}

function uploadingJson($file, $val) {
    jQuery("#load_page").show();
    bsm_url = $file;
    valstate = $val;
    ajaxpath = wpjellyTemplateControl.ajaxurl;
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: ajaxpath,
        data: {
            action: "save_elem_export",
            seturl: bsm_url,
            selectcall: valstate
        },
        success: function(response) {
            //console.log(response.stat);
            if (response.stat == '1') {
                jQuery("#bsmsetmessage").html(response.msg);
            } else {
                let showcontent = `<div id="message" style="display:block;" class="updated notice is-dismissible"><p>Error Occured</p></div>`
                jQuery("#bsmsetmessage").html(showcontent);
            }
            jQuery("#load_page").hide();
            jQuery(".jsonHeader").show();
        }
    })
}

function getcontentforpage($filejson, $id) {
    jQuery("#bsm_load_spage").show();
    // jQuery("#bsm-add-image").text("Uploading...")
    bsm_title = jQuery('#bsm-set-page-name').val();
    bsm_fetch_api = $filejson;
    ajaxpath = wpjellyTemplateControl.ajaxurl
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: ajaxpath,
        data: {
            action: "save_page_det",
            jsonurl: bsm_fetch_api,
            bsm_title: bsm_title,
            template: $id
        },
        success: function(response) {
            //console.log(response);
            if (response.checkslug == '1') {
                let showcontent2 = `<div id="message" style="display:block;" class="updated notice is-dismissible">Congrats! This draft page was just created: <a href="` + response.url + `" target="_blank">`;
                // showcontent2 += response.name;
                showcontent2 += `View Page`;
                showcontent2 += `</a></div>`;
                jQuery("#bsm_load_spage").hide();
                jQuery("#bsmsavetopage").html(showcontent2);
            } else {
                let showcontent = `<div id="message" style="display:block;" class="updated notice is-dismissible"><p>Error Occured!!.</p></div>`
                jQuery("#bsm_load_spage").hide();
                jQuery("#bsmsavetopage").html(showcontent);
            }
        }
    })
}

function bsmredirecturl($redirectid) {
    //jQuery("#bsm_load_spage").show();
    bsm_fetch_url = $redirectid;
    ajaxpath = wpjellyTemplateControl.ajaxurl
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: ajaxpath,
        data: {
            action: "redirecttosuccess",
            setpostid: bsm_fetch_url
        },
        success: function(response) {
            if (response !== '') {
                //console.log(response);
                bsmanchorurl = wpjellyTemplateControl.siteUrl + '/wp-admin/post.php?post=' + response + '&action=elementor';
                newlink = document.createElement('a');
                newlink.setAttribute("href", bsmanchorurl);
                newlink.setAttribute("target", '_blank');
                //console.log(newlink);
                newlink.click();
                //console.log(anchorurl);
                jQuery("#bsm_load_spage").hide();
            } else {
                let showcontent = `<div id="message" class="updated notice is-dismissible"><p>Error Occured!!.</p></div>`
                jQuery("#bsm_load_spage").hide();
                jQuery("#bsmsavetopage").html(showcontent);
            }
        }
    })
}

function getdetailbycategory($cateslug) {
        
    jQuery(".bsm-siteNav a").removeClass('active');
    jQuery(".wpjelly-temp-" + $cateslug).addClass("active");
    if($cateslug!='all')
    {
        var generatedlink = 'https://my.wpjelly.com/template/wp-json/pm/v1/getcategory/?cat=' + $cateslug;
        jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
        jQuery(".bsm-loader").fadeIn();

        jQuery.ajax({
            url: wpjellyTemplateControl.ajaxurl,
            method: 'POST',
            data: {
                url: generatedlink,
                catslug:$cateslug,
                action: 'wpjellyAdmintemplateImporterCategory'
            },
            success: function(res) {
                jQuery(".bsm-loader").fadeOut();
                jQuery(".wpjelly-template-layout-main-wrapper").html(res);
                jQuery('.wpjelly-template-layout-main-wrapper').fadeIn();
            }
        });
    }
    else
    {
        jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
        jQuery(".bsm-loader").fadeIn();

        jQuery.ajax({
            url: wpjellyTemplateControl.ajaxurl,
            method: 'POST',
            data: {
                action: 'wpjellyallTemplates'
            },
            success: function(res) {
                jQuery(".bsm-loader").fadeOut();
                jQuery(".wpjelly-template-layout-main-wrapper").html(res);
                jQuery('.wpjelly-template-layout-main-wrapper').fadeIn();
            }
        });
    }
    
       
}

function getBLockListing()
{
    jQuery(".bsm-siteNav a").removeClass('active');
    jQuery(".wpjelly-blocks").addClass("active");
    jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
    jQuery(".bsm-loader").fadeIn();

    jQuery.ajax({
        url: wpjellyTemplateControl.ajaxurl,
        method: 'POST',
        data: {
            action: 'wpjellyAdminBlockImporter'
        },
        success: function(res) {
            jQuery(".bsm-loader").fadeOut();
            jQuery(".wpjelly-template-layout-main-wrapper").html(res);
            jQuery('.wpjelly-template-layout-main-wrapper').fadeIn();
        }
    });
}


function getSubBlocks($parent,$pos)
{
    
    jQuery('.wpjelly-template-layout-main-wrapper').fadeOut();
    jQuery(".bsm-siteNav").fadeOut();
    jQuery(".bsm-loader").fadeIn();
    jQuery.ajax({
        url: wpjellyTemplateControl.ajaxurl,
        method: 'POST',
        data: {
            parent:$parent,
            action: 'wpjellyAdminSubBlockImporter'
        },
        success: function(res) {
            jQuery(".bsm-loader").fadeOut();
            jQuery(".wpjelly-template-layout-inner-wrapper").html(res);
            jQuery(".wpjelly-template-layout-inner-wrapper").fadeIn();
            jQuery(".bsm_back_class").fadeIn();
        }
    });
}



function paginationset(pagenu, url, totalpage) {

    //     let html2 = `
    // <div class="bsm-loader"><img src="` + wpjellyTemplateControl.imageUrl + `/jellyfish.gif" class="bsm_center"></div>
    // `;
    //     jQuery("#jsonBody").html(html2);
    jQuery(".wpjelly-template-layout-main-wrapper").fadeOut();
    jQuery(".bsm-loader").fadeIn();

    var pagenumber = pagenu;
    var gurl = url;
    var pagecount = totalpage;
    if (gurl.indexOf('?') != -1) {
        var addurlval = '&pagenumber=' + pagenumber;
        var newurl = gurl + addurlval;
    } else {
        var addurlval = '?pagenumber=' + pagenumber;
        var newurl = gurl + addurlval;
    }
    jQuery.ajax({
        url: wpjellyTemplateControl.ajaxurl,
        method: 'POST',
        data: {
            url: newurl,
            pagenumber: pagenumber,
            totalpage: totalpage,
            gurl: gurl,
            action: 'wpjellyAdmintemplateImporterpagination'
        },
        success: function(res) {
            jQuery(".wpjelly-template-layout-main-wrapper").html(res);
            jQuery(".bsm-loader").fadeOut();
            jQuery(".wpjelly-template-layout-main-wrapper").fadeIn();
            jQuery('html, body').animate({
                scrollTop: jQuery(".bsm_menu_header_main_title").offset().top
            }, 1000);
        }
    });
}


jQuery(function($){
    var timer = null;
    $("body").on('keyup','.wpjelly-search',function(e){
        e.preventDefault();
        var id=$(this).attr('id');
        var destiny=$(this).attr('data-destiny');
        var catslug=$(this).attr('data-cat');
         clearTimeout(timer); 
         timer = setTimeout(wpjellySearch, 300,destiny,id,catslug);
        
    });
    function wpjellySearch(destiny,id,catslug) {
        var act = {'wpjelly-block-sr':"wpjelly_block_search",'wpjelly-all-temp-sr':'wpjelly_all_emplate','wpjelly-all-temp-pg-sr':'wpjelly_all_emplate','wpjelly-cat-sr':'wpjelly_category_search'};
        $("body").find(".bsm-pagination").fadeOut();
        $("."+destiny).fadeOut();
        jQuery(".bsm-loader").fadeIn();
        var val=$('#'+id).val();
        if(val.length<1)
        {
            $("body").find(".bsm-siteNav li > .active").trigger('click');
            // $("body").find('.wpjelly-temp-all').trigger('click');
             $("body").find(".bsm-pagination").fadeIn();
            return false;
        }
        $.ajax({
            url:wpjellyTemplateControl.ajaxurl,
            method:'POST',
            // data:{s:val,catslug:catslug,action:act[id]},
            data:{s:val,action:'universal_template_search'},
            success:function(res)
            {
                jQuery(".bsm-loader").fadeOut();
                jQuery("body").find(".bsm-siteNav li a").removeClass("active");
                $("."+destiny).html(res);
                $("body").find(".wpjelly-temp-all").addClass('active');
                $("."+destiny).fadeIn();

            }
        });
    }
});

