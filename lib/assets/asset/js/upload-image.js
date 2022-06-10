$(document).ready(function () {

    $('[data-role="thumb-on-demand-upload-image"]').each(function () {
        var control = $(this);
        var altAttr = control.data('alt-attr-input');
        var file2name = {}, readers = {};

        function onLoad(event) {
            var template = $('.thumb', control);
            $('img', template).attr('src', event.target.result);
            $('.title .text', template).text(file2name[0]);

            if (altAttr) {
                $('#' + altAttr).val(file2name[0]);
            }

            $(template).removeClass('hidden disabled');
        }

        $('[data-role="remove"]', control).click(function () {
            var template = $('.thumb', control);
            $(template).addClass('disabled');

            if (altAttr) {
                $('#' + altAttr).val('');
            }

            $("input[type='hidden']", control).removeAttr('disabled');
        });

        $("input[type='file']", control).change(function () {
            if (!this.files) {
                return;
            }

            $("input[type='hidden']", control).attr('disabled', 'disabled');

            var name, type, index;
            for (var i = 0; i < this.files.length; i++) {
                type = this.files[i].type;
                if (!type) {
                    continue;
                }

                index = type.lastIndexOf('/');
                if (index !== -1) {
                    type = type.substr(0, index);
                }

                if ('image' != type) {
                    continue;
                }

                name = this.files[i].name;
                index = name.lastIndexOf('.');
                if (index !== -1) {
                    name = name.substr(0, index);
                }

                file2name[i] = name;

                readers[i] = new FileReader();
                readers[i].onload = onLoad;

                readers[i].readAsDataURL(this.files[i]);
            }
        });
    });
});