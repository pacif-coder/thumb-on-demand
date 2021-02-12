$(document).ready(function () {
    
    $('[data-role="thumb-on-demand-upload-image"]').each(function () {
        var control = $(this);
        var file2name = {}, readers = {};        
        
        function onLoad(event) {
            var template = $('.thumb', control);
            $('img', template).attr('src', event.target.result);
            $('.title .text', template).text(file2name[0]);
            
            $('[data-role="alt"]', control).val(file2name[0]);

            $(template).removeClass('hidden disabled');
        }

        $('[data-role="remove"]', control).click(function () {
            var template = $('.thumb', control);
            $(template).addClass('disabled');
            
            $('[data-role="alt"]', control).val('');            
            
            $("input[type='hidden']", control).removeAttr('disabled');
        });

        $("input[type='file']", control).change(function () {
            if (!this.files) {
                return;
            }
            
            $("input[type='hidden']", control).attr('disabled', 'disabled');

            var name;
            for (var i = 0; i < this.files.length; i++) {
                name = this.files[i].name;
                var index = name.lastIndexOf('.');
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