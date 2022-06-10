$(document).ready(function() {

    $('[data-role="widget-file-input"] [data-role="remove"]').click(function() {
        var confirm = $(this).data('confirm');
        if (confirm && !window.confirm(confirm)) {
            return;
        }

        var cont = $(this).parents('[data-role="widget-file-input"]')[0];
        $('[data-role="file"]', cont).val('');
        $('[data-role="drop"]', cont).removeAttr('disabled');
        $('a', cont).addClass('drop').removeAttr('href');

        if ($(this).data('fill')) {
            $('[data-role="file"]', cont).addClass('hidden');
        }

        $(this).addClass('hidden');
    });

    $('[data-role="widget-file-input"] [data-role="file"]').change(function() {
        var cont = $(this).parents('[data-role="widget-file-input"]')[0];
        if ($(this).val()) {
            $('[data-role="remove"]', cont).removeClass('hidden');
        }
    });
});