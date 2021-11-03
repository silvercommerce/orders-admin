(function ($) {
    $.entwine('ss.ordersadmin', function ($) {
        $(".grid-field .orders-lineitem-search").entwine({
            onfocusin: function (event) {
                this.autocomplete({
                    source: function(request, response){
                        var search_field = $(this.element);
                        $.ajax({
                            headers: {
                                "X-Pjax" : 'Partial'
                            },
                            dataType: 'json',
                            type: "GET",
                            url: $(search_field).data('searchUrl'),
                            data: encodeURIComponent(search_field.attr('name')) + '=' + encodeURIComponent(search_field.val()),
                            success: response,
                            error: function(e) {
                                alert(i18n._t('Admin.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
                            }
                        });
                    },
                    select: function(event, ui) {
                        var addbutton = $(this)
                            .closest(".grid-field")
                            .find(".actin_gridfield_lineitemadd");

                        addbutton
                            .removeAttr('disabled')
                            .removeAttr('readonly');
                    }
                });
            },
            onkeyup: function(event) {
                var value = $(this).val();
                var addbutton = $(this)
                    .closest(".grid-field")
                    .find(".action_gridfield_lineitemadd");

                if (value.length > 0) {
                    addbutton
                        .removeAttr('disabled')
                        .removeAttr('readonly');
                } else {
                    addbutton
                        .attr('disabled', 'disabled')
                        .attr('readonly', 'reeadonly');
                }
            }
        });
    });
})(jQuery);