var WebDeploy;
(function (WebDeploy, $) {
    var Setting = /** @class */ (function () {
        function Setting(ajaxUrl) {
            this.ajaxUrl = ajaxUrl;
        }
        return Setting;
    }());
    WebDeploy.Setting = Setting;
    var Data = /** @class */ (function () {
        function Data() {
        }
        Data.init = function () {
            $("#btnCleanup").on("click", function(){			
                $.ajax({
                    url: ajaxurl,
                    method: "GET",
                    data: {"action":"cleanup"},
                    success: function(response) {									
                        if(response.success)
                        {
                            $("#zip_message").html("Cleaned up").addClass("success");
                        }	
                        else
                        {
                            $("#zip_message").html(response.errors.map(function(item){return jQuery(`<div>${item}</div>`);})).addClass("danger");
                        }				
                    },
                    error: function(response) {
                        console.log(response);
                    }
                });
            });

            $("#btnPackageUpload").on("click", function(){
                Data.Upload();
            });

            $("[data-filename]").on("click", function(){
                var self = this;
                Data.ajaxCall({action: $(this).attr("class"), filename = $(self).data("filename")}).success(function(response){
                    $(self).siblings(".detail").html("");
                    response.data.forEach(item=>{
                        $(self).siblings(".detail").append(`<li>${item}</li>`)
                    });
                });
            
            });            
        };
        Data.ajaxCall = function (data, method) {
            return $.ajax({ url: ajaxurl, data: data, method: method });
        };
        Data.Load = function () {
            this.ajaxCall({ action: "list" }, "POST").success(function(response){
                if (response.success) {
                    $("#backups").html(response.html);
                }
            });
        };

        Data.Upload = function(){
            let $form = $("#frmpackage");
            $form.validate();               
            if($form.valid())
            {
                var data = new FormData();							
                data.append("wnp_file", $("#wnp_file")[0].files[0]);	
                data.append("datatype", $("#datatype").val());
                data.append('action', 'unzip');	
                
                $.ajax({url: ajaxurl, data:data, method: "POST",
                processData: false,
                enctype: 'multipart/form-data',
                contentType: false
                }).success(function(response){
                        if (response.success) {
                            $("#zip_message").html(response.data.message).addClass("success");
                            $("#backups").html(response.html);
                        }else{
                            $("#zip_message").html(response.data.message).addClass("danger");
                        }
                }).error(function(response){
                    console.log(response);
                });
            }
        }

        return Data;
    }(jQuery));
    WebDeploy.Data = Data;
})(WebDeploy || (WebDeploy = {}), jQuery);
WebDeploy.Data.init();
