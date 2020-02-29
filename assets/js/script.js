(function($){
    $(document).ready(function(){
        $("#zepto_token").on("keyup", function(){
            if($("#zepto_id").val() != ""){
                $("#zepto_submit").removeAttr("disabled");
            }
        });

        $("#zepto_id").on("keyup", function(){
            if($("#zepto_token").val() != ""){
                $("#zepto_submit").removeAttr("disabled");
            }
        });
    });
})(jQuery)