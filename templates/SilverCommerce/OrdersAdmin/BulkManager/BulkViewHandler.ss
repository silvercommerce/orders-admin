<!DOCTYPE html>
<% require css('silverstripe/admin: client/dist/styles/bundle.css') %>
<% require css('silvercommerce/orders-admin: client/dist/css/display.css') %>

<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>{$I18nLabel}</title>
    </head>

    <body>
        <div class="container">
            <% loop $List %>
                <div class="page">{$Me}</div>
            <% end_loop %>
        <div>
    </body>
</html>