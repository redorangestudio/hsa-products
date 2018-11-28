jQuery(document).ready(function (e) {
    jQuery('#fund-content-button').on('click', function () {
        var instance = window.parent.tinyMCE;
        var editor = instance.editors.funds_content;
        jQuery.ajax({
            method: "POST",
            type: "json",
            url: ajaxurl,
            data: {
                action: 'get_funds_content',
                add_funds_content_nonce: hsa_admin.add_funds_content_nonce
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    editor.setContent(response.success);
                }
            }
        });
        return false;
    });
});