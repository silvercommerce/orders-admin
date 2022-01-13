<table class="table total-table">
    <tbody>
        <tr>
            <th class="text-right"><%t OrdersAdmin.SubTotal "SubTotal" %></th>
            <td class="text-right">$SubTotal.Nice</td>
        </tr>

        <% if $DiscountTotal.RAW > 0 %>
            <tr>
                <th class="text-right"><%t OrdersAdmin.Discount "Discount" %></th>
                <td class="text-right">$DiscountTotal.Nice</td>
            </tr>
        <% end_if %>

        <% if $PostagePrice.RAW > 0 %>
            <tr>
                <th class="text-right"><%t OrdersAdmin.Postage "Postage" %></th>
                <td class="text-right">$PostagePrice.Nice</td>
            </tr>
        <% end_if %>

        <tr>
            <th class="text-right"><%t OrdersAdmin.Tax 'Tax' %></th>
            <td class="text-right">{$TaxTotal.Nice}</td>
        </tr>

        <tr>
            <th class="text-right"><%t OrdersAdmin.GrandTotal "Grand Total" %></th>
            <td class="text-right">$Total.Nice</td>
        </tr>
    </tbody>
</table>