(function ($) {
    $(document).ready(function () {
        $(".ibenic_file_delete").on("click", function (e) {
            e.preventDefault();
            if (confirm(wma.delete_question) === false)
                return;
            var link_id = $(this).attr("data-link-id");
            var id = $(this).attr("data-attachment-id");
            var data = new FormData();
            data.append("action", "ibenic_file_delete");
            data.append("id", id);
            data.append("anchor_image_once", $("#anchor_image_once").val());
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                cache: false,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function(data, textStatus, jqXHR) {
                    if( data.response == "SUCCESS" ){
                        $("#ibenic_file_upload"+link_id+"_preview").hide();
                        $("#ibenic_file_upload"+link_id).show();
                    }
                    if( data.response == "ERROR" ){
                        alert( data.error );
                    }
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    alert( textStatus );
                }
            });
        });

        $(".ibenic_file_upload").on("click", function () {
            $(this).parent().find(".ibenic_file_input").click(function (event) {
                event.stopPropagation();
            });
        });

        $(".ibenic_file_input").on('change', prepareUpload);

        function prepareUpload(event) {
            var file = event.target.files;
            var parent = $("#" + event.target.id).parent();
            var link_id = parent.attr("data-link-id");
            var data = new FormData();
            data.append("action", "ibenic_file_upload");
            data.append("link-id", link_id);
            data.append("anchor_image_once", $("#anchor_image_once").val());
            $.each(file, function (key, value) {
                data.append("ibenic_file_upload", value);
            });
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                cache: false,
                dataType: 'json',
                processData: false,
                contentType: false,
                success: function (data, textStatus, jqXHR) {
                    if (data.response == "SUCCESS") {
                        var preview = "";
                        if (data.type === "image/jpg"
                            || data.type === "image/png"
                            || data.type === "image/gif"
                            || data.type === "image/jpeg"
                        ) {
                            preview = "<img src='" + data.url + "' height='100'/>";
                        } else {
                            preview = data.filename;
                        }
                        var previewID = parent.attr("id") + "_preview";
                        var previewParent = $("#" + previewID);
                        previewParent.show();
                        previewParent.children(".ibenic_file_preview" + link_id).empty().append(preview);
                        previewParent.children("button").attr("data-fileurl", data.url);
                        parent.children("input").val("");
                        parent.hide();
                        var deletebutton = $("#ibenic_file_delete" + link_id);
                        deletebutton.attr("data-attachment-id", data.id)
                    } else {
                        alert(data.error);
                    }
                }
            });
        }
    });
})(jQuery);