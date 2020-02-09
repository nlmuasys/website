jQuery(function($){
	function moveWpjellySubsiteProgress() {
	  var count=$("#wpjelly-subsite-checklit-progress").attr('data-count');

	  if(count!=undefined)
	  {
	  	var elem = document.getElementById("wpjelly-subsite-checklit-progress-Bar");   
		  var width = 1;
		  var id = setInterval(frame, 10);
		  function frame() {
		    if (width >= count) {
		      clearInterval(id);
		    } else {
		      width++; 
		      elem.style.width = width + '%'; 
		    }

		  }
	  }
	}


	$("body").on('change','.wpcd-subsite-checklist-control',function(event){
	    $(".wpjelly-subsite-admin-loader-wrapper").fadeIn();
	    var data=$(".wpcd-chceklist-subadmin-form").serialize();
	    $.ajax({
	    	url:wpcdControl.ajaxUrl,
	    	method:'POST',
	    	data:{data:data,action:'wpcd_checklist_admin_form'},
	    	success:function(res)
	    	{
	    		//console.log(res);
	    		$(".wpjelly-subsite-admin-loader-wrapper").fadeOut();
	    		$("#wpjelly-subsite-checklit-progress").attr('data-count',res);
	    		$(".wpjelly-checklist-percentage").html(res+'%');
	    		moveWpjellySubsiteProgress();
	    	}
	    });
	});
	$(document).ready(function(){
		moveWpjellySubsiteProgress();
	});
});