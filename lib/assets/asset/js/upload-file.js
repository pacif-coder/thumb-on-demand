$(document).ready(function() {

    $('[data-role="widget-upload-file"]').each(function() {
        var control = $(this);

        $('[data-role="remove"]', control).click(function() {
            if (control.hasClass('deleted')) {
                return;
            }

            var confirm = $(this).data('confirm');
            if (confirm && !window.confirm(confirm)) {
                return;
            }

            control.addClass('deleted');
            $('[data-role="drop"]', control).removeAttr('disabled');
            $('[data-role="file"]', control).attr('disabled', 'disabled').val('');
            $('a', control).removeAttr('href');
        });

        // Attach a 'change' event listener to file input elements within a specific control
        $('[data-role="file"]', control).change(function () {
            // Get the value of the input (path of the selected file)
            var val = $(this).val();
            if (!val) {
                return;
            }

            // Check if the 'nameAttrId' data attribute is not set on the control; if not, exit the function
            if (!control.data('nameAttrId')) {
                return;
            }

            // Retrieve the 'nameAttrId' data attribute value, which should be the ID of another element
            var nameAttrId = control.data('nameAttrId');

            // If the element with ID stored in 'nameAttrId' already has a value, exit the function
            if ($('#' + nameAttrId).val()) {
                return;
            }

            // Extract the file name from the full path, discard the path
            var file = val.split('\\').pop();
            if (!file) {
                return;
            }

            // Find the last dot in the filename to isolate the extension
            var index = file.lastIndexOf('.');

            // If an extension is found, remove it from the file name
            if (index !== -1) {
                file = file.substr(0, index);
            }

            // Set the value of the element with ID stored in 'nameAttrId' to the extracted file name (without extension)
            $('#' + nameAttrId).val(file);
        });

    });
});