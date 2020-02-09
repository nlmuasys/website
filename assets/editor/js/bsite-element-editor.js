"undefined" != typeof jQuery && ! function(e) {
    e(function() {
        function n() {
            if (elementorCommon) {
                var n = jQuery(this).parents(".elementor-section-wrap").length > 0 ? jQuery(this).parents(".elementor-add-section").index() : -1;
                n >= 0 && jQuery(this).parents(".elementor-add-section-inline").remove(), window.ElementsReact && window.ElementsReact.elementorMagicButtonConfigSet({
                    insertIndex: n
                }), window.elementsModal || (window.elementsModal = elementorCommon.dialogsManager.createWidget("lightbox", {
                    id: "bsitemakr-elements-modal",
                    headerMessage: !1,
                    message: "",
                    hide: {
                        auto: !1,
                        onClick: !1,
                        onOutsideClick: !1,
                        onOutsideContextMenu: !1,
                        onBackgroundClick: !0
                    },
                    position: {
                        my: "center",
                        at: "center"
                    },
                    onShow: function() {
                        var layout=document.getElementById("wpjelly-editor-layout");
                        var content=layout.textContent || layout.innerText;
                        jQuery("body").find("#bsitemakr-elements-modal .dialog-lightbox-content").html(content);
                        var n = window.elementsModal.getElements("content");
                        window.ElementsReact && bsitemakr_elements_react && n.length > 0 && window.ElementsReact.elementorMagicButton(bsitemakr_elements_react, n.get(0)), e(".js-modal-close").click(function() {
                            return window.elementsModal.hide(), !1
                        })
                    },
                    onHide: function() {
                        var e = window.elementsModal.getElements("content");
                        window.ElementsReact && e.length > 0 && window.ElementsReact.elementor3rdPartyViewClose(e.get(0)), window.location.hash = ""
                    }
                }), window.elementsModal.getElements("header").remove(), window.elementsModal.getElements("message").append(window.elementsModal.addElement("content"))), window.elementsModal.show()
            }
        }
        window.elementsModal = null;
        var t = e("#tmpl-elementor-add-section");
        if (t.length > 0) {
            var o = t.text();
            o = o.replace('<div class="elementor-add-section-drag-title', '<div class="elementor-add-section-area-button elementor-add-bsitemakr-button" title="WPJelly Templates " style="'+wpjellyExportControl.logoStyle+'"> <i class="fa fa-folder" style="visibility:hidden;"></i> </div><div class="elementor-add-section-drag-title'), t.text(o), elementor.on("preview:loaded", function() {
                e(elementor.$previewContents[0].body).on("click", ".elementor-add-bsitemakr-button", n)
            })
        }
    })
}(jQuery);
/*-----------------------------------------------------------------------------------------------------------------------------------------------*/

jQuery(function($){
    $("body").on('click','.close-wpjelly-editor',function(e){
        $("body").find("#bsitemakr-elements-modal").fadeOut();
    });
});

jQuery(function($){
    
    $("body").on('click','.wpjelly-back',function(e){
        e.preventDefault();
        var pos=$(this).attr('data-pos');
        var back = {'1':"#wpjelly-editor-container", '2':".wpjelly-inner"};
        $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly').css('display','none');
        $("body").find(back[pos]).css('display','block');
    });
    
    function wpjellyLoader(stat)
    {
        if(stat=='show')
        {
             $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly').hide();
             $("body").find('#wpjelly-editor-container').hide();
             $("body").find(".wpjelly-editor-loader").show();
        }
        else
        {
             $("body").find('#wpjelly-editor-container').show();
             $("body").find(".wpjelly-editor-loader").hide();
        }
    }
    function wpjellyScroller(pos)
    {
        $('html, body').animate({
            scrollTop: $("#"+pos).offset().top
        }, 2000);
    }

    $("body").on('click','.wpjelly_editorGetCat',function(e){
        e.preventDefault();
        $('.wpjelly_editorGetCat').removeClass('active');
        $(this).addClass('active');
        var slug=$(this).attr('href');
        wpjellyLoader('show');
        var tags=['all','block'];
        var act={'all':"wpjellyEditorAllTemplates",'block':"wpjellygetBlockList"};
        $("body").find(".wpjelly-search").val('');
        if(jQuery.inArray(slug, tags) != -1) {
            //console.log(act[slug]);
                $.ajax({
                    url:wpjellyExportControl.ajaxurl,
                    method:"POST",
                    data:{action:act[slug]},
                    success:function(res)
                    {
                        $("body").find(".wpjelly-listing-container").html(res);
                        $("body").find(".wpjelly-listing-container").attr('data-type','all');
                        wpjellyLoader('hide');

                    }
                });
            } else {
                $.ajax({
                    url:wpjellyExportControl.ajaxurl,
                    method:"POST",
                    data:{action:'wpjellygetcatListEditor',cat:slug},
                    success:function(res)
                    {
                        $("body").find(".wpjelly-listing-container").html(res);
                        $("body").find(".wpjelly-listing-container").attr('data-type',slug);
                        wpjellyLoader('hide');

                    }
                });
            } 
    });

    $("body").on('click','.uyNi5vnpqR70ai6xTfPZx_wpjelly li a',function(e){
        e.preventDefault();
        wpjellyLoader('show');
        page=$(this).attr('data-page');
        if(page=='next')
        {
            page=$("body").find(".uyNi5vnpqR70ai6xTfPZx_wpjelly .current-page").attr('data-current');
            page=parseInt(page)+1;
        }
        if(page=='previous')
        {
            page=$("body").find(".uyNi5vnpqR70ai6xTfPZx_wpjelly .current-page").attr('data-current');
            page=parseInt(page)-1;
        }
        $.ajax({
            url:wpjellyExportControl.ajaxurl,
            method:"POST",
            data:{action:'wpjellyEditorAllTemplatesPaginate',page:page},
            success:function(res)
            {
                $("body").find(".wpjelly-listing-container").html(res);
                $("body").find(".wpjelly-listing-container").attr('data-type','all');
                wpjellyLoader('hide');
                wpjellyScroller('wpjelly-editor-container');

            }
        });
    });

    $("body").on('click',".wpjelly-blocks",function(e){
        e.preventDefault();
        $("body").find(".wpjellyFirstCat").addClass('passive');
        $("body").find(".wpjellyFirstCat").removeClass('active');

        var loader='<div class="wpjellyuni-loader-wrapper">';
        loader=loader+'<img class="wpjellyuni-loader" src="'+wpjellyExportControl.jelly_loader+'"></div>';
        $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly:nth-child(2)').html(loader);
        $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly').css('display','none');
        $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly:nth-child(2)').css('display','block');
        $.ajax({
                url:wpjellyExportControl.ajaxurl,
                method:"POST",
                data:{action:'wpjellyEditorGetBlockParents'},
                success:function(res)
                {
                    $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly:nth-child(2)').html(res);
                }
        });

    });
    $("body").on('click','.wpjelly-sub-list',function(e){
        e.preventDefault();
        var parent=$(this).attr('href');
        wpjellyLoader('show');
        if($(this).hasClass('blocks'))
        {
            action='wpjellyEditorGetBlockChildren';
        }
        if($(this).hasClass('temp'))
        {
            action='wpjellyEditorGetTemplateChildren';
        }
        $.ajax({
            url:wpjellyExportControl.ajaxurl,
            method:"POST",
            data:{parent:parent,action:action},
            success:function(res)
            {
                wpjellyLoader('hide');
                wpjellyAjaxRes('.wpjelly-inner',res);
                
            }
        });
    });

    
   
    $("body").on('click','.wpjelly-editor-getSingleCatPage',function(e){
        e.preventDefault();
        var id=$(this).attr('data-id');
        var type=$(this).attr('data-type');
        var importStat=$(this).attr('data-imported');
        var ref=$(this).attr('data-ref');
        wpjellyLoader('show');
        $.ajax({
            url:wpjellyExportControl.ajaxurl,
            method:"POST",
            data:{ref:ref,templateId:id,stat:importStat,action:'wpjellyEditorGetTemplateByCategorySingle'},
            success:function(res)
            {
                wpjellyLoader('hide');
                wpjellyAjaxRes('.wpjelly-single-inner',res);
                $("body").find("._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly:nth-child(3) .wpjelly-import").attr('data-type',type);
            }
        });
    });


    function wpjellyAjaxRes(pos,res)
    {
        $("body").find('._1pLw1-7WEGRYGZQ9N4xT7y_wpjelly').hide();
        $("body").find(pos).html(res);
        $("body").find(pos).show();
    }
    $("body").on('click','._1smM86XuFFLr0aS1IMSLbO_wpjelly',function(e){
        var url=$(this).attr('href');
        //var template=getUrlParameter(url,'templateId');
        var urlparams=url.split('&');
        var last=urlparams[urlparams.length-1];
        var pointA='templateId=';
        var start=pointA.length;
        var template=last.substring(start,last.length);
        if(template!=undefined)
        {
            // $("body").find("._2uvjKWnkHS3ThhMWs_FLxO_wpjelly").html('');
           $(function (){
                window.setTimeout(clearWpjellyImportArea, 500);//use yourfunction(); instead without timeout
            });
        }
    });
    var getUrlParameter = function getUrlParameter(url,sParam) {
    var sPageURL = url.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };
    function clearWpjellyImportArea() {
        var fetchassociated = JSON.parse(wpjellyExportControl.meta);
        var url=window.location.href;
        var urlparams=url.split('&');
        var last=urlparams[urlparams.length-1];
        var pointA='templateId=';
        var start=pointA.length;
        var temp=last.substring(start,last.length);
        temp=temp.split('|');
        var template=temp['0'];
        //template=$.base64.decode(template);
       
        var singlehtml='';
        singlehtml += '<div class="wpjelly-template-editor-wrap">';
          singlehtml += '<h3>Import Template</h3>';
          singlehtml += '<div class="wpjelly-template-editor-desc-wrap"><p>Import this template to make it available in your Elementor Saved Templates list for future use.</p>';
          singlehtml += '</div>';
          singlehtml +='<div class="_99v9ImSmsc9WaLo1JRKTv_wpjelly"></div>';
        if(jQuery.inArray(template, fetchassociated) != -1) {
            singlehtml += '<button type="button" class="wpjelly-direct wpjelly-import _3HTeJ2APvEEbw6fuNZusDc_wpjelly" id="exporttemp" data-template="'+template+'">';
            singlehtml+='<span class="wpjelyy-stat">Open Template in Library </span>';
            singlehtml+='<span class="wpjelly-load"><img src="'+wpjellyExportControl.loader+'"></span>';
            singlehtml+='</button>';
        } else {
            singlehtml += '<button type="button" class="wpjelly-process wpjelly-import _3HTeJ2APvEEbw6fuNZusDc_wpjelly" id="exporttemp" data-template="'+template+'">';
            singlehtml +='<span class="wpjelyy-stat">Import Template</span>';
            singlehtml +='<span class="wpjelly-load"><img src="'+wpjellyExportControl.loader+'"></span>';
            singlehtml+='</button>';
        } 
        
        singlehtml +='</div>';
        $("body").find("._2uvjKWnkHS3ThhMWs_FLxO_wpjelly").html(singlehtml);
        $("body").find("._3QBZLRa-A77ImXOKPt4QuF_wpjelly").css("opacity","1");
        
    }
    function reloadWpjellyFunc()
    {
        location.reload();
    }
    $("body").on('click','.wpjelly-import',function(e){
        e.preventDefault();
        
        var template=$(this).attr('data-template');
        var type=$(this).attr('data-type');
        if(type==undefined)
        {
            type='page';
        }

        var postId=getUrlParameter(window.location.search,'post');
        // console.log(template);
        
        if($(this).hasClass('wpjelly-direct'))
        {
            $(this).prop("disabled",true);
            $("body").find(".wpjelly-import .wpjelyy-stat").text('Setting The Template...');
            $("body").find(".wpjelly-import").addClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
            $.ajax({
                url:wpjellyExportControl.ajaxurl,
                method:'POST',
                data:{template:template,postId:postId,action:'wpjellyTemplateImportDirect'},
                success:function(res1)
                {   

                    let templateModel=JSON.parse(res1);
                    //console.log(templateModel);
                    if(templateModel.stat==0)
                    {
                        $("body").find(".wpjelly-import").removeClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
                        $("body").find(".wpjelly-import .wpjelyy-stat").text('Open Template in Library ');
                        $("body").find('._99v9ImSmsc9WaLo1JRKTv_wpjelly').html('<span style="color:red;">'+templateModel.error+'</span>');
                         $("body").find('._99v9ImSmsc9WaLo1JRKTv_wpjelly').fadeIn();
                         $("body").find(".wpjelly-import").prop("disabled",false);
                    }
                    else
                    {
                        var  wpjellyTemplate = Backbone.Model.extend();  
                        var template = new wpjellyTemplate();  
                        template.set({ template_id: templateModel.template_id, source:"local"});  
                        
                        elementor.channels.data.trigger('template:before:insert', template);
                        elementor.getPreviewView().addChildModel(templateModel.content, templateModel.options);
                        elementor.channels.data.trigger('template:after:insert', template);
                     

                       
                         

                        $("body").find(".wpjelly-import").prop("disabled",false);
                        $("body").find(".wpjelly-import").removeClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
                        $("body").find(".wpjelly-import .wpjelyy-stat").text('Open Template in Library ');
                        
                        $("#bsitemakr-elements-modal").fadeOut();
                    }
                    
                }

            });
        }
        if($(this).hasClass('wpjelly-process'))
        {
            $(this).prop("disabled",true);
            $("body").find(".wpjelly-import .wpjelyy-stat").text('Importing...');
            $("body").find(".wpjelly-import").addClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
            $.ajax({
                url:wpjellyExportControl.ajaxurl,
                method:'POST',
                data:{template:template,postId:postId,type:type,action:'wpjellyTemplateImportProcess'},
                success:function(res)
                {
                    //console.log(res);
                    let templateModel=JSON.parse(res);
                     if(templateModel.stat==0)
                      {
                         $("body").find(".wpjelly-import").removeClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
                         $("body").find(".wpjelly-import .wpjelyy-stat").text('Import Template');
                         $("body").find('._99v9ImSmsc9WaLo1JRKTv_wpjelly').html('<span style="color:red;">'+templateModel.error+'</span>');
                         $("body").find('._99v9ImSmsc9WaLo1JRKTv_wpjelly').fadeIn();
                         $("body").find(".wpjelly-import").prop("disabled",false);
                      }  
                      else
                      {

                        var  wpjellyTemplate = Backbone.Model.extend();  
                        var template = new wpjellyTemplate();  
                        template.set({ template_id: templateModel.template_id, source:"local"});  
                        
                        elementor.channels.data.trigger('template:before:insert', template);
                        elementor.getPreviewView().addChildModel(templateModel.content, templateModel.options);
                        elementor.channels.data.trigger('template:after:insert', template);
                        $("body").find(".wpjelly-import").prop("disabled",false);
                        $("body").find(".wpjelly-import").removeClass('wpjelly-process');
                        $("body").find(".wpjelly-import").addClass('wpjelly-direct');

                        $("body").find(".wpjelly-import").removeClass("_232V2hEXumw5qfQvYgC3_7_wpjelly");
                        $("body").find(".wpjelly-import .wpjelyy-stat").text('Open Template in Library ');
         
                        
                        $("#bsitemakr-elements-modal").fadeOut();
                      }
                    
                }
            });
        }
    });
    var timer = null;
    $("body").on('keyup','.wpjelly-search',function(e){
        e.preventDefault();
         clearTimeout(timer); 
         timer = setTimeout(wpjellySearch, 300);
        
    });
    function wpjellySearch() {
        var val=$("body").find(".wpjelly-search").val();
        var tags=['block','all'];
        var act = {'block':"wpjelly_block_search_editor",'all':'wpjelly_template_search_editor'};
        var pos=$("body").find("._wpjellyNavList .active").attr('href');
        if(val.length<1)
        {
            $("body").find("._wpjellyNavList .active").trigger('click');
            return false;
        }
        wpjellyLoader('show');
        if(jQuery.inArray(pos, tags) != -1) {
                $.ajax({
                    url:wpjellyExportControl.ajaxurl,
                    method:"POST",
                    // data:{action:act[pos],s:val},
                    data:{action:'editor_universal_template_search',s:val},
                    success:function(res)
                    {
                        $("body").find("._wpjellyNavList li a").removeClass('active');
                        $("body").find(".wpjellyFirstCat").addClass('active');
                        $("body").find(".wpjelly-listing-container").html(res);
                        wpjellyLoader('hide');

                    }
                });
            } 
            else 
            {
                $.ajax({
                    url:wpjellyExportControl.ajaxurl,
                    method:"POST",
                    // data:{action:'wpjelly_category_search_editor',s:val,cat:pos},
                    data:{action:'editor_universal_template_search',s:val},
                    success:function(res)
                    {
                        $("body").find("._wpjellyNavList li a").removeClass('active');
                        $("body").find(".wpjellyFirstCat").addClass('active');
                        $("body").find(".wpjelly-listing-container").html(res);
                        wpjellyLoader('hide');

                    }
                });
            } 
    }
    
});

