jQuery(function(){

    let $form = jQuery("#frmpackage");
    $form.validate();
    function loadDetail(self)
    {
            var data = new FormData();	
            data.append('action', 'detail');
            data.append('filename', jQuery(self).data("filename"));					
            jQuery.ajax({
                url: ajaxurl,
                method: "POST",
                data: data,
                processData: false,
                enctype: 'multipart/form-data',
                contentType: false,						
                success: function(response) {				 					
                    jQuery("#frmpackage").trigger("reset");
                    if(response.success)
                    {
                        response.data.forEach(item=>{
                            jQuery(self).parent().after(`<div>${item}</div>`)
                        });
                    }	
                    else
                    {
                        jQuery("#zip_message").html(response.data.message).css("color", "red");
                    }				
                },
                error: function(response) {
                    console.log(response);
                }
            });
    }
    jQuery("[data-filename]").on("click", function(){
        loadDetail(this);
    });       

    jQuery("#btnPackageUpload").on("click", function(){			
        if($form.valid())
        {
            var data = new FormData();							
            data.append("wnp_file", jQuery("#wnp_file")[0].files[0]);	
            data.append("datatype", jQuery("#datatype").val());
            data.append('action', 'unzip');	
            jQuery.ajax({
                url: ajaxurl,
                method: "POST",
                data: data,
                processData: false,
                enctype: 'multipart/form-data',
                contentType: false,
                success: function(response) {									
                    jQuery("#frmpackage").trigger("reset");
                    if(response.success)
                    {
                        jQuery.ajax({url:ajaxurl, data: {"action":"list"}})
                            .success(function(data){
                                jQuery("#zip_message").html(response.data.message).addClass("success");
                                jQuery("#backups").html(data.replace("</ul>0", "</ul>"));

                                jQuery("[data-filename]").on("click", function(){
                                    loadDetail(this);
                            });     
                        });
                    }	
                    else
                    {
                        jQuery("#zip_message").html(response.data.message).addClass("danger");
                    }				
                },
                error: function(response) {
                    console.log(response);
                }
            });
        }
    });    
});