var importBtn = document.getElementById("keyrequestbtn");
var wpjellycontainer = document.getElementById("wpjellycontainer");
var total = 100;
var loading = 0;
var finish = 0;
var items_per_request = 5;
var plugins_per_request = 1;
var progress_interval_id = 0;

if (importBtn) {
	importBtn.addEventListener("click", function () {
		var codetext = document.getElementById("codtxt").value;
		if (codetext.length < 1) {
			jQuery(".temp-ajax-reply").html('<span style="color:red;">Please Enter A Valid Key</span>');
			return false;
		}
		jQuery(".wp-jelly-modal").fadeIn();
	});

	jQuery('.wp-jelly-btn-yes').on('click', function(e) {
	    jQuery("#wpjelly-import-Bar-Complete").css("display","none");
	    jQuery("#wpjelly-import-Bar").css("display","block");
		jQuery(".wse-export-key-request").prop('disabled', true);

		var codetext = document.getElementById("codtxt").value;

		if (codetext.length < 1) {
			jQuery(".wse-export-key-request").prop('disabled', false);
			jQuery(".temp-ajax-reply").html('<span style="color:red;">Please Enter A Valid Key</span>');
			return false;
		}

		var base64regex = /^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$/;

		if (base64regex.test(codetext)===false) {
		    jQuery(".wp-jelly-modal").fadeOut();
		    jQuery(".temp-ajax-reply").html('<span style="color:red;">Not a valid key</span>');
			jQuery("#wpjelly-import-Progress").fadeOut();
			jQuery(".wse-export-key-request").prop('disabled', false);
			return false;
		}
		
		var data = jQuery.base64.decode(codetext);
		var info = data.split('|');

		if (info['2']=='live') {
		    mainurl='https://wpjelly.com/wp-admin/admin-ajax.php';
		} else {
		    mainurl='https://dev.wpjelly.com/wp-admin/admin-ajax.php';
		}

		var newcode=info['0']+'|'+info['1'];
		newcode=jQuery.base64.encode(newcode);
		jQuery(".wp-jelly-modal").fadeOut();

		wpjellyImportProgressBar(0, 5);

		jQuery(".temp-ajax-reply").html('Getting all elements, please wait...');
		jQuery("#wpjelly-import-Progress").fadeIn();
		jQuery.ajax({
			type: "POST",
			url: mainurl,
			data: {
				codetext: codetext,
				action: 'wpjelly_export_ajax'
			},
			success: wpjellyExportAjaxSuccess,
			error: wpjellyFailedToProcessRequest
		});
	});

	jQuery(".wp-jelly-btn-no").on("click", function() {
		jQuery(".wp-jelly-modal").fadeOut();
	});

	jQuery(".wp-jelly-close").on("click", function() {
		jQuery(".wp-jelly-modal").fadeOut();
	});

	function wpjellyErrorOccured(message) {
		jQuery(".wp-jelly-modal").fadeOut();
		jQuery(".temp-ajax-reply").html('<span style="color:red;">' + message + '</span>');
		jQuery("#wpjelly-import-Progress").fadeOut();
		jQuery(".wse-export-key-request").prop('disabled', false);
	}

	function wpjellyFailedToProcessRequest(jqXHR, textStatus, errorThrown) {
		console.log("Failed to process this request")
		console.log(jqXHR);
		console.log(textStatus);
		console.log(errorThrown);
		wpjellyErrorOccured("Failed to process this request");
	}

	function wpjellyExportAjaxSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response)
		{
			if (response.stat == 1) {
				wpjellyImportProgressBar(5, 10);

				jQuery(".temp-ajax-reply").html('Resetting site, please wait...');
				jQuery("#wpjelly-import-Progress").fadeIn();
				jQuery.ajax({
					url:wpjellyControl.ajaxurl,
					method:'POST',
					data: {
						seed: response.seed,
						action: 'wpjellyResetSiteStart'
					},
					themeInfo: response,
					success: wpjellyResetSiteStartSuccess,
					error: wpjellyFailedToProcessRequest
				});
			} else {
				wpjellyErrorOccured(response.error);
			}
		} else {
			wpjellyErrorOccured("Failed to retreive initial data");
		}
	}

	function wpjellyResetSiteStartSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			wpjellyImportProgressBar(10, 15);

			jQuery(".temp-ajax-reply").html('Resetting site, please wait...');
			jQuery.ajax({
				url:wpjellyControl.ajaxurl,
				method:'POST',
				data: {
					action: 'wpjellyResetSiteProcess'
				},
				themeInfo: this.themeInfo,
				success: wpjellyResetSiteProcessSuccess,
				error: wpjellyFailedToProcessRequest
			});
		} else {
			wpjellyErrorOccured("Failed to reset initial state");
		}
	}

	function wpjellyResetSiteProcessSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			wpjellyImportProgressBar(15, 20);

			jQuery(".temp-ajax-reply").html('Installing theme, please wait...');
			jQuery.ajax({
				url: wpjellyControl.ajaxurl,
				method: 'POST',
				data: {
					fileName: this.themeInfo.fileName,
					fileDownloadUrl: this.themeInfo.fileDownloadUrl,
					elementorUrl:this.themeInfo.elementorUrl,
					action: 'wpjellyImportThemeData'
				},
				themeInfo: this.themeInfo,
				success: wpjellyImportThemeDataSuccess,
				error: wpjellyFailedToProcessRequest
			});
		} else {
			wpjellyErrorOccured("Failed to reset initial state");
		}
	}

	function wpjellyInitImportXMLStart(themeInfo) {
		jQuery(".temp-ajax-reply").html('Importing data, please wait...');
		jQuery.ajax({
			url: wpjellyControl.ajaxurl,
			method: 'POST',
			data: {
				xmlUrl: themeInfo.fileDownloadUrl,
				checklistPoints: themeInfo.wpjelly_checklist_points,
				checkedPoints: themeInfo.wpjelly_checked_points,
				mainUrl: themeInfo.mainUrl,
				blogUrl: themeInfo.blogUrl,
				mainId: themeInfo.mainId,
				elementorUrl: themeInfo.elementorUrl,
				action: 'wpjellyImportXmlStart'
			},
			themeInfo: themeInfo,
			success: wpjellyImportXmlStartSuccess,
			error: wpjellyFailedToProcessRequest
		});
	}

	function wpjellyImportThemeDataSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			if ( ( response.pluginsCount > 0 ) && ( plugins_per_request > 0 ) ) {
				var progressMin = 20;
				var progressMax = 25;
	
				var totalRequests = Math.floor( response.pluginsCount / plugins_per_request );
				if ( response.pluginsCount % plugins_per_request ) {
					totalRequests++;
				}

				var progressStep = ( progressMax - progressMin ) / totalRequests;
				wpjellyImportProgressBar( progressMin, progressMin + progressStep );
	
				jQuery(".temp-ajax-reply").html('Installing plugins, please wait...');
				jQuery.ajax({
					url: wpjellyControl.ajaxurl,
					method: 'POST',
					data: {
						pluginsStart: 0,
						pluginsCount: plugins_per_request,
						action: 'wpjellyImportPlugin'
					},
					themeInfo: this.themeInfo,
					clientPluginsStart: plugins_per_request,
					clientPluginsCount: response.pluginsCount,
					nextProgress: progressMin + progressStep,
					progressStep: progressStep,
					success: wpjellyImportPluginSuccess,
					error: wpjellyFailedToProcessRequest
				});
			} else {
				wpjellyImportProgressBar(20, 30);
				wpjellyInitImportXMLStart(this.themeInfo);
			}
		} else {
			wpjellyErrorOccured("Failed to import theme data");
		}
	}

	function wpjellyImportPluginSuccess(result) {
		if (this.clientPluginsStart < this.clientPluginsCount) {
			wpjellyImportProgressBar(this.nextProgress, this.nextProgress + this.progressStep);
			jQuery(".temp-ajax-reply").html('Installing plugins, please wait...');
			jQuery.ajax({
				url: wpjellyControl.ajaxurl,
				method: 'POST',
				data: {
					pluginsStart: this.clientPluginsStart,
					pluginsCount: plugins_per_request,
					action: 'wpjellyImportPlugin'
				},
				themeInfo: this.themeInfo,
				clientPluginsStart: this.clientPluginsStart + plugins_per_request,
				clientPluginsCount: this.clientPluginsCount,
				nextProgress: this.nextProgress + this.progressStep,
				progressStep: this.progressStep,
				success: wpjellyImportPluginSuccess,
				error: wpjellyFailedToProcessRequest
			});
		} else {
			wpjellyImportProgressBar(25, 30);
			wpjellyInitImportXMLStart(this.themeInfo);
		}
	}

	function wpjellyInitImportXMLFinish(themeInfo) {
		jQuery(".temp-ajax-reply").html('Importing media files, please wait...');
		jQuery.ajax({
			url: wpjellyControl.ajaxurl,
			method: 'POST',
			data: {
				xmlUrl: themeInfo.fileDownloadUrl,
				checklistPoints: themeInfo.wpjelly_checklist_points,
				checkedPoints: themeInfo.wpjelly_checked_points,
				mainUrl: themeInfo.mainUrl,
				blogUrl: themeInfo.blogUrl,
				mainId: themeInfo.mainId,
				elementorUrl: themeInfo.elementorUrl,
				action: 'wpjellyImportXmlFinish'
			},
			themeInfo: themeInfo,
			success: wpjellyImportXmlFinishSuccess,
			error: wpjellyFailedToProcessRequest
		});
	}

	function wpjellyImportXmlStartSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			if ( response.mediaCount > 0 ) {
				var progressMin = 30;
				var progressMax = 70;

				var minRequests = Math.floor( response.mediaCount / response.mediaMax );
				if ( response.mediaCount % response.mediaMax ) {
					minRequests++;
				}

				var progressStep = ( progressMax - progressMin ) / minRequests;
				wpjellyImportProgressBar( progressMin, progressMin + progressStep );

				jQuery(".temp-ajax-reply").html('Importing media files, please wait...');
				jQuery.ajax({
					url: wpjellyControl.ajaxurl,
					method: 'POST',
					data: {
						xmlUrl: this.themeInfo.fileDownloadUrl,
						checklistPoints: this.themeInfo.wpjelly_checklist_points,
						checkedPoints: this.themeInfo.wpjelly_checked_points,
						mainUrl: this.themeInfo.mainUrl,
						blogUrl: this.themeInfo.blogUrl,
						mainId: this.themeInfo.mainId,
						elementorUrl: this.themeInfo.elementorUrl,
						mediaStart: 0,
						action: 'wpjellyImportXmlMedia'
					},
					themeInfo: this.themeInfo,
					totalMediaProcessed: 0,
					totalMediaCount: response.mediaCount,
					nextProgress: progressMin + progressStep,
					progressStep: progressStep,
					progressMin: progressMin,
					progressMax: progressMax,
					success: wpjellyImportXmlMediaSuccess,
					error: wpjellyFailedToProcessRequest
				});
			} else {
				wpjellyImportProgressBar(30, 75);
				wpjellyInitImportXMLFinish(this.themeInfo);
			}
		} else {
			wpjellyErrorOccured("Failed to import XML file");
		}
	}

	function wpjellyImportXmlMediaSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success) {
			if ( response.mediaProcessed > 0 ) {
				this.totalMediaProcessed += response.mediaProcessed;

				if (this.totalMediaProcessed < this.totalMediaCount) {
					if ( ( this.totalMediaProcessed / (this.totalMediaCount / 100) ) >= ( ( this.nextProgress - this.progressMin ) / ( ( this.progressMax - this.progressMin ) / 100 ) ) ) {
						wpjellyImportProgressBar(this.nextProgress, this.nextProgress + this.progressStep);
						this.nextProgress += this.progressStep;
					}

					jQuery(".temp-ajax-reply").html('Importing media files, please wait...');
					jQuery.ajax({
						url: wpjellyControl.ajaxurl,
						method: 'POST',
						data: {
							xmlUrl: this.themeInfo.fileDownloadUrl,
							checklistPoints: this.themeInfo.wpjelly_checklist_points,
							checkedPoints: this.themeInfo.wpjelly_checked_points,
							mainUrl: this.themeInfo.mainUrl,
							blogUrl: this.themeInfo.blogUrl,
							mainId: this.themeInfo.mainId,
							elementorUrl: this.themeInfo.elementorUrl,
							mediaStart: this.totalMediaProcessed,
							action: 'wpjellyImportXmlMedia'
						},
						themeInfo: this.themeInfo,
						totalMediaProcessed: this.totalMediaProcessed,
						totalMediaCount: this.totalMediaCount,
						nextProgress: this.nextProgress,
						progressStep: this.progressStep,
						progressMin: this.progressMin,
						progressMax: this.progressMax,
						success: wpjellyImportXmlMediaSuccess,
						error: wpjellyFailedToProcessRequest
					});
				} else {
					wpjellyImportProgressBar(70, 75);
					wpjellyInitImportXMLFinish(this.themeInfo);
				}
			} else {
				if (this.totalMediaProcessed < this.totalMediaCount) {
					wpjellyErrorOccured("Failed to import media files");
				} else {
					wpjellyImportProgressBar(70, 75);
					wpjellyInitImportXMLFinish(this.themeInfo);
				}
 			}
		} else {
			wpjellyErrorOccured("Failed to import media files");
		}
	}

	function wpjellyInitMigrateSettings(themeInfo) {
		jQuery(".temp-ajax-reply").html('Configuring the Settings...');
		jQuery.ajax({
			url: wpjellyControl.ajaxurl,
			method: 'POST',
			data: {
				action: 'wpjellyMigrateFinish',
				mainId: themeInfo.mainId,
				// oceanwpOptions: themeInfo.oceanwp_options,
				// widgets: themeInfo.widgets
			},
			success: wpjellyMigrateFinishSuccess,
			error: wpjellyFailedToProcessRequest
		});
	}

	function wpjellyImportXmlFinishSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			if ( response.resourcesCount > 0 ) {
				var progressMin = 75;
				var progressMax = 90;

				var minRequests = Math.floor( response.resourcesCount / response.resourcesMax );
				if ( response.resourcesCount % response.resourcesMax ) {
					minRequests++;
				}

				var progressStep = ( progressMax - progressMin ) / minRequests;
				wpjellyImportProgressBar( progressMin, progressMin + progressStep );

				jQuery(".temp-ajax-reply").html('Reinstating files, images and other resources...');
				jQuery.ajax({
					url: wpjellyControl.ajaxurl,
					method: 'POST',
					data: {
						mainUrl: this.themeInfo.mainUrl,
						mainId: this.themeInfo.mainId,
						resourcesStart: 0,
						action: 'wpjellyMigrateResources'
					},
					themeInfo: this.themeInfo,
					totalResourcesProcessed: 0,
					totalResourcesCount: response.resourcesCount,
					nextProgress: progressMin + progressStep,
					progressStep: progressStep,
					progressMin: progressMin,
					progressMax: progressMax,
					success: wpjellyMigrateResourcesSuccess,
					error: wpjellyFailedToProcessRequest
				});
			} else {
				wpjellyImportProgressBar(75, 95);
				wpjellyInitMigrateSettings(this.themeInfo);
			}
		} else {
			wpjellyErrorOccured("Failed to finish importing XML file");
		}
	}

	function wpjellyMigrateResourcesSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success) {
			if ( response.resourcesProcessed > 0 ) {
				this.totalResourcesProcessed += response.resourcesProcessed;

				if (this.totalResourcesProcessed < this.totalResourcesCount) {
					if ( ( this.totalMediaProcessed / (this.totalMediaCount / 100) ) >= ( ( this.nextProgress - this.progressMin ) / ( ( this.progressMax - this.progressMin ) / 100 ) ) ) {
						wpjellyImportProgressBar(this.nextProgress, this.nextProgress + this.progressStep);
						this.nextProgress += this.progressStep;
					}

					jQuery(".temp-ajax-reply").html('Reinstating files, images and other resources...');
					jQuery.ajax({
						url: wpjellyControl.ajaxurl,
						method: 'POST',
						data: {
							mainUrl: this.themeInfo.mainUrl,
							mainId: this.themeInfo.mainId,
							resourcesStart: this.totalResourcesProcessed,
							action: 'wpjellyMigrateResources'
						},
						themeInfo: this.themeInfo,
						totalResourcesProcessed: this.totalResourcesProcessed,
						totalResourcesCount: this.totalResourcesCount,
						nextProgress: this.nextProgress,
						progressStep: this.progressStep,
						progressMin: this.progressMin,
						progressMax: this.progressMax,
						success: wpjellyMigrateResourcesSuccess,
						error: wpjellyFailedToProcessRequest
					});
				} else {
					wpjellyImportProgressBar(90, 95);
					wpjellyInitMigrateSettings(this.themeInfo);
				}	
			} else {
				if (this.totalResourcesProcessed < this.totalResourcesCount) {
					wpjellyErrorOccured("Failed to import template media files");
				} else {
					wpjellyImportProgressBar(90, 95);
					wpjellyInitMigrateSettings(this.themeInfo);
				}
			}
		} else {
			wpjellyErrorOccured("Failed to import template media files");
		}
	}

	function wpjellyMigrateFinishSuccess(result) {
		var response = wpjellyGetJSON(result);

		if (response && response.success)
		{
			wpjellyImportProgressBar(95, 100);
			jQuery(".temp-ajax-reply").html('Generating CSS configurations please wait...');
			
			jQuery.ajax({
				url: wpjellyControl.ajaxurl,
				method: 'POST',
				data: {
					action: 'elementor_clear_cache',
					_nonce: wpjellyControl._wpjelly_elem_nonce
				},
				success: function (final_result) {
					jQuery("#wpjelly-import-Bar").hide();
					jQuery("#wpjelly-import-Bar-Complete").css("display","block");
					wpjellyImportProgressBar(100, 100);
					jQuery(".temp-ajax-reply").html(response.message);
					jQuery(".wse-export-key-request").prop('disabled', false);
				},
				error: wpjellyFailedToProcessRequest
			});
		} else {
			wpjellyErrorOccured("Failed to finish importing template media files");
		}
	}

	function wpjellyGetJSON(str) {
	    try {
			if (str) {
				return JSON.parse(str);
			} else {
				return false;
			}
	    } catch (e) {
	        return false;
	    }
	}

	function wpjellyImportProgressBar(start, end) {
		start = Math.floor(start);
		end = Math.floor(end);

		jQuery(".wpjelly-import-percentage").html(start + '%');
		var elem = document.getElementById("wpjelly-import-Bar");
		if ( elem ) {
			elem.style.width = start + '%';
		}

		if ( progress_interval_id ) {
			clearInterval( progress_interval_id );
			progress_interval_id = 0;
		}

		if (start < end) {
			progress_interval_id = setInterval( function() {
				if (start < end) {
					start++;

					jQuery(".wpjelly-import-percentage").html(start + '%');
					var elem = document.getElementById("wpjelly-import-Bar");
					if ( elem ) {
						elem.style.width = start + '%';
					}
				}
			}, 3000);
		}
	}

	(function ($) {
		$.fn.blink = function (options) {
			var defaults = {
				delay: 500
			};
			var options = $.extend(defaults, options);

			return this.each(function () {
				var obj = $(this);
				setInterval(function () {
					if ($(obj).css("visibility") == "visible") {
						$(obj).css('visibility', 'hidden');
					} else {
						$(obj).css('visibility', 'visible');
					}
				}, options.delay);
			});
		}
	}(jQuery))
}