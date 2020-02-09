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
# Author         : Matrix                                               #
# Created Date   : 12-09-2019                                                #
# Purpose        : Predefine Theme and Plugin install and activate in one go #
/****************************************************************************/

/**
 * Import Wpjelly Plugin Setup
 */
class wpjellyExportPlugin  {
    public function __construct()
    {
       add_action('admin_menu', array($this, 'create_menu'));
       add_action('admin_enqueue_scripts',array($this,'wpjellyScripts'));
       if(isset($_GET['page']) && $_GET['page']=='wpjelly-importer')
       {
        add_action('admin_head', array($this,'wseadmin_theme_style'));
       }

    }
    public function create_menu()
    {  
      add_submenu_page( 'wpjelly-theme-pannel', 'Import Full Website', 'Import Full Website',
        'manage_options', 'wpjelly-importer', array($this, 'reqPluginFunCall'));
    }
    public function wpjellyScripts()
    {
        wp_register_style( 'wpJelly-main-style', WP_JELLY_CSS . '/main.css', array(), WP_JELLY_VERSION, 'all' );
        wp_register_script( 'wpJelly-main-script', WP_JELLY_JS . '/main.js', array(), WP_JELLY_VERSION, true );
        wp_register_script( 'wpJelly-decode-script', WP_JELLY_JS . '/jquery.base64.min.js', array(), WP_JELLY_VERSION, true );

        if(isset($_GET['page']) && $_GET['page']=='wpjelly-importer')
        {
            wp_enqueue_script('wpJelly-decode-script');
            wp_enqueue_script('wpJelly-main-script');
            wp_enqueue_style('wpJelly-main-style');
        }

        wp_localize_script('wpJelly-main-script', 'wpjellyControl', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            '_wpjelly_elem_nonce' => wp_create_nonce( "elementor_clear_cache" ),
            
        ));

    }
    public function reqPluginFunCall()
    {
    	$method=get_filesystem_method();
        $disable=($method=='ftpext'?'disabled':'');
        ?>
        <div class="wrap-wse">
            <div class="wse-export-body">
                <style>
                    #wpjelly-import-Progress {
                      width: 100%;
                      background-color: #ddd;
                    }

                    #wpjelly-import-Bar {
                      width: 0%;
                      height: 30px;
                      background-color: #4CAF50 !important;
                    }
                    #codtxt{text-align: center;}
                    /*23-09-2019*/
                    .wpjelly_wrapper_div{text-align: center; overflow: hidden;}
                    .wpjelly_wrapper_div h1{color: #060606; font-weight: 700; margin: 0; line-height: 40px; font-size: 32px; padding: 30px 0;}
                    .wpjelly_wrapper_div input{display: block; margin: 0 auto 15px; border:solid 1px #c7c7c7; height: 50px; line-height: 50px; padding: 10px; width: 28%;}
                    .wpjelly_wrapper_div button#keyrequestbtn{background: #3472a6; border:none; color: #fff; text-align: center; border-radius: 4px; box-shadow: none; text-transform: uppercase; font-size: 16px; padding: 10px 30px; height: auto; text-shadow: none; margin: 0 auto 30px;}
                    .wpjelly_wrapper_div button#keyrequestbtn:hover{background: #498ec8;}
                    .wpjelly_wrapper_div #wpjelly-import-Progress{width:30% !important; margin: 0 auto 20px; border-radius: 20px; height: 16px; position: relative;}
                    .wpjelly_wrapper_div #wpjelly-import-Progress #wpjelly-import-Bar{height:16px;background-color: #85cb81 !important;border-radius: 20px;}
                    .wpjelly_wrapper_div #wpjelly-import-Progress span.wpjelly-import-percentage{display: block; position: absolute; left: -55px; top: 0px; text-align: left; font-weight: 600; color: #000; font-size: 18px;height: 20px; line-height: 20px;}
                    .wpjelly_wrapper_div .temp-ajax-reply{font-size: 15px; line-height: 24px; margin: 0;}
                    .wpjelly_wrapper_div .temp-ajax-reply p{font-size: 15px; line-height: 24px; margin: 0px 0px 10px 0px;}
                    @media only screen and (max-width:1024px) {
                    .wpjelly_wrapper_div input{width: 70%;}
                    .wpjelly_wrapper_div #wpjelly-import-Progress{width: 70% !important;}
                    }
                    @media only screen and (max-width:480px) {
                      .wpjelly_wrapper_div h1{font-size: 25px; line-height: 30px;}
                      .wpjelly_wrapper_div{width: 95%;}
                      .wpjelly_wrapper_div input{width: 100%;}
                      .wpjelly_wrapper_div #wpjelly-import-Progress span.wpjelly-import-percentage{top: 20px; font-size: 16px; left: 0; right:0; text-align: center;}
                      .wpjelly_wrapper_div #wpjelly-import-Progress{margin-bottom: 30px;}
                    }
                    .wp-jelly-modal {
                        display: none; /* Hidden by default */
                        position: fixed; /* Stay in place */
                        z-index: 1; /* Sit on top */
                        padding-top: 100px; /* Location of the box */
                        left: 80px;
                        top: 0;
                        width: 100%; /* Full width */
                        height: 100%; /* Full height */
                        overflow: auto; /* Enable scroll if needed */
                        background-color: rgb(0,0,0); /* Fallback color */
                        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
                      }

                      /* Modal Content */
                      .wp-jelly-modal-content {
                        background-color: #fefefe;
                        margin: auto;
                        padding: 20px;
                        border: 1px solid #888;
                        width: 30%;
                      }

                      /* The Close Button */
                      .wp-jelly-close {
                        color: #aaaaaa;
                        float: right;
                        font-size: 28px;
                        font-weight: bold;
                      }

                      .wp-jelly-close:hover,
                      .wp-jelly-close:focus {
                        color: #000;
                        text-decoration: none;
                        cursor: pointer;
                      }
                      .wp-jelly-confirm-btn {
                        border: none;
                        color: white;
                        padding: 15px 32px;
                        text-align: center;
                        text-decoration: none;
                        display: inline-block;
                        font-size: 16px;
                        margin: 4px 2px;
                        cursor: pointer;
                      }
                      .wp-jelly-btn-no {background-color: #f44336;} /* Red */
                      .wp-jelly-btn-yes {background-color: #4CAF50; } /* Green */
                      #wpjelly-import-Progress {
                        display: block;
                        text-align: center;
                        width: 0;
                        height: 3px;
                        background: red;
                        transition: width .3s;
                    }
                    #wpjelly-import-Progress.hide {
                        opacity: 0;
                        transition: opacity 1.3s;
                    }

                    /*------------------------------------------------------------------*/
                      #wpjelly-import-Progress { 
                        width: 50%;
                        height: 20px;  /* Can be anything */
                        position: relative;
                        background: #555;
                        -moz-border-radius: 25px;
                        -webkit-border-radius: 25px;
                        border-radius: 25px;
                        padding: 2px;
                        box-shadow: inset 0 -1px 1px rgba(255,255,255,0.3);
                        margin: auto;
                      }
                      #wpjelly-import-Progress > #wpjelly-import-Bar {
                        display: block;
                        height: 100%;
                        border-top-right-radius: 8px;
                        border-bottom-right-radius: 8px;
                        border-top-left-radius: 20px;
                        border-bottom-left-radius: 20px;
                        background-color: rgb(43,194,83);
                        background-image: linear-gradient(
                          center bottom,
                          rgb(43,194,83) 37%,
                          rgb(84,240,84) 69%
                        );
                        box-shadow: 
                          inset 0 2px 9px  rgba(255,255,255,0.3),
                          inset 0 -2px 6px rgba(0,0,0,0.4);
                        position: relative;
                        overflow: hidden;
                      }
                      #wpjelly-import-Progress > #wpjelly-import-Bar-Complete {
                        display: none;
                        height: 100%;
                        border-top-right-radius: 8px;
                        border-bottom-right-radius: 8px;
                        border-top-left-radius: 20px;
                        border-bottom-left-radius: 20px;
                        background-color: #85CB7F;
                        background-image: linear-gradient(
                          center bottom,
                          rgb(43,194,83) 37%,
                          rgb(84,240,84) 69%
                        );
                        box-shadow: 
                          inset 0 2px 9px  rgba(255,255,255,0.3),
                          inset 0 -2px 6px rgba(0,0,0,0.4);
                        position: relative;
                        overflow: hidden;
                        border-radius: 25px;
                      }
                      #wpjelly-import-Progress > #wpjelly-import-Bar:after {
                      content: "";
                      position: absolute;
                      top: 0; left: 0; bottom: 0; right: 0;
                      background-image: linear-gradient(
                        -45deg, 
                        rgba(255, 255, 255, .2) 25%, 
                        transparent 25%, 
                        transparent 50%, 
                        rgba(255, 255, 255, .2) 50%, 
                        rgba(255, 255, 255, .2) 75%, 
                        transparent 75%, 
                        transparent
                      );
                      z-index: 1;
                      background-size: 50px 50px;
                      animation: move 2s linear infinite;
                      border-top-right-radius: 8px;
                      border-bottom-right-radius: 8px;
                      border-top-left-radius: 20px;
                      border-bottom-left-radius: 20px;
                      overflow: hidden;
                    }

                      @keyframes move {
                        0% {
                          background-position: 0 0;
                        }
                        100% {
                          background-position: 50px 50px;
                        }
                      }
                      #wpjelly-import-Progress > #wpjelly-import-Bar:after, .animate > #wpjelly-import-Bar > #wpjelly-import-Bar {
                        animation: move 2s linear infinite;
                      }
                      @keyframes expandWidth {
                         0% { width: 0; }
                         100% { width: auto; }
                      }
                      .progress {
    display: block;
    text-align: center;
    width: 0;
    height: 3px;
    background: red;
    transition: width .3s;
}
.progress.hide {
    opacity: 0;
    transition: opacity 1.3s;
}
                    /*------------------------------------------------------------------*/


                </style>
              
                <div class="wrap-wpjelly">
                  <div class="wpjelly_wrapper_div">
                  <?php
                  ?>
                     <h1>Import Your Website</h1>
                     <input type="text"  value="" class="regular-text" name="codetext" id="codtxt" <?php echo $disable;?> required style="width:45%;" placeholder="Place Your Website Key">
                     <button id="keyrequestbtn" class="button button-primary button-large wse-export-key-request" <?php echo $disable;?>>Import Website</button>
                     <div class="wp-jelly-container" id="wpjellycontainer"></div>
                        <div id="wpjelly-import-Progress" style="display: none;" class="animate">
                            <span class="wpjelly-import-percentage"></span>
                          <span id="wpjelly-import-Bar"></span>
                          <span id="wpjelly-import-Bar-Complete"></span>
                        </div>
                 
                     <div class="temp-ajax-reply"></div>
                     <div class="progress"></div>
                     <!-- <div class="meter animate">
                        
                     </div> -->
                    <?php if($method=='ftpext'):?>
                        <center>
                            <br>
                            <strong>Oops ! Looks like you site requires Ftp to upload plugins. Please place the following code in <i>wp-config.php</i></strong>
                            <br>
                            <input type="text" readonly value="define('FS_METHOD','direct');" style="width: 17em;padding: 21px;">
                        </center>
                    <?php endif;?>
                  </div>
                </div>
            </div>
        </div>
        <div class="wp-jelly-modal">

          <!-- Modal content -->
          <div class="wp-jelly-modal-content">
            <span class="wp-jelly-close">&times;</span>
            <center>
              <div class="wpjelly-msg">
                <p>Kindly note, this import will delete and reset all content data, including the theme and plugins from your website.<br> 
                Would like to proceed?</p>
              </div>
              <button class="wp-jelly-confirm-btn wp-jelly-btn-yes">Yes</button>
              <button class="wp-jelly-confirm-btn wp-jelly-btn-no">No</button>
            </center>
          </div>

        </div>

        <?php
    }
    public function wseadmin_theme_style()
    {
        echo '<style>.update-nag, .updated, .error, .is-dismissible { display: none; }</style>';
    }

}
new wpjellyExportPlugin();
