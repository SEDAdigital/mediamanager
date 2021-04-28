$(document).ready(function() {
    var modalTrigger = '.mediamanager-input',
        tvId         = null,
        input        = '.mediamanager-input-wrapper .textfield',
        typeInterval = 3000,
        typingTimer  = null,
        currentInput = null;

    $(input).keyup(function() {
        clearTimeout(typingTimer);

        currentInput = $(this);

        var inputId         = $(currentInput).parent().attr('data-tvid'),
            $imageContainer = $('#tv-image-preview-' + inputId),
            $imagePreview   = $imageContainer.find('img');

        $imagePreview.hide();

        if ($(this).val()) {
            typingTimer = setTimeout(keyupFunction, typeInterval);
        }
    });

    function keyupFunction() {
        $.ajax({
            method  : 'GET',
            url     : MODx.config['mediamanager.assets_path'] + 'connector.php?action=mgr/migx/getfile&src=' + $(currentInput).val() + '&return_url=1'
        })
        .done(function(data) {
            if (data !== '') {
                var inputId         = $(currentInput).parent().attr('data-tvid'),
                    $imageContainer = $('#tv-image-preview-' + inputId),
                    $imagePreview   = $imageContainer.find('img');

                if ($imageContainer.length) {
                    $imagePreview.show();
                    $imagePreview.css('max-width', 400).css('max-height', 300).attr('src', data);
                }
            }
        });
    }

    $(document).on('click', modalTrigger, function(e) {
        tvId = $(this).parent().attr('data-tvid');

        var $imageContainer = $('#tv-image-preview-' + tvId),
            $imagePreview   = $imageContainer.find('img');

        e.preventDefault();

        var mediaManager = new $.MediaManagerModal({
            onSelect: function(file) {
                if (!$imagePreview.length) {
                    $imagePreview = $('<img />').appendTo($imageContainer);
                }

                $imagePreview.css('max-width', 400).css('max-height', 300).attr('src', file.preview);

                $('input#tv' + tvId).attr('value', file.id);
            }
        });

        mediaManager.open();
    });

});
