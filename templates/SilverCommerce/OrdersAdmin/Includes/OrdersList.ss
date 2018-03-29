<% if $List.exists %>
    <table class="width-100 table">
        <thead>
            <tr>
                <th><%t Orders.Order "Order" %></th>
                <th><%t Orders.Date "Date" %></th>
                <th><%t Orders.Price "Price" %></th>
                <th><%t Orders.Status "Status" %></th>
            </tr>
        </thead>
        <tbody>
            <% loop $List %>
                <tr>
                    <td><a href="{$DisplayLink}">{$Number}</a></td>
                    <td><a href="{$DisplayLink}">{$Created.Nice}</a></td>
                    <td><a href="{$DisplayLink}">{$Total.Nice}</a></td>
                    <td><a href="{$DisplayLink}">{$TranslatedStatus}</a></td>
                </tr>
            <% end_loop %>
        </tbody>
    </table>

    <% if $List.MoreThanOnePage %>
        <p class="pagination">
            <% if $List.NotFirstPage %>
                <span class="page-item">
                    <a class="prev page-link" href="$List.PrevLink">
                        <%t Orders.Prev "Prev" %>
                    </a>
                </span>
            <% end_if %>
            <% loop $List.Pages %>
                <% if $CurrentBool %>
                    <span class="page-item disabled">$PageNum</span>
                <% else_if $Link %>
                    <span class="page-item">
                        <a class="page-link" href="$Link">$PageNum</a>
                    </span>
                <% else %>
                    <span class="page-item disabled">$PageNum</span>
                <% end_if %>
                <% end_loop %>
            <% if $List.NotLastPage %>
                <span class="page-item">
                    <a class="next" href="$List.NextLink">
                        <%t Orders.Next "Next" %>
                    </a>
                </span>
            <% end_if %>
        </p>
    <% end_if %>
<% else %>
    <p class="message info message-info">
        <%t Orders.NoOrders "There are currently no items" %>
    </p>
<% end_if %>