$(document).on('click', "form#auth a", function () {
    $.ajax({
        url: '/reg',
        dataType: 'html',
        success: function (html) {
            $('form').replaceWith(html);
            $('#common-errors').remove();
        }
    });
    return false;
});
$(document).on('click', "form#reg a", function () {
    $.ajax({
        url: '/auth',
        dataType: 'html',
        success: function (html) {
            $('form').replaceWith(html);
            $('#common-errors').remove();
        }
    });
    return false;
});