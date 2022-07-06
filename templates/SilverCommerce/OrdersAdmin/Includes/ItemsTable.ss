<table class="table items-table">
    <thead>
        <tr>
            <th class="stock-id text-left"><%t OrdersAdmin.StockID "Stock ID" %></th>
            <th class="description text-left"><%t OrdersAdmin.Item "Item" %></th>
            <th class="qty text-center"><%t OrdersAdmin.Qty "Qty" %></th>
            <th class="unitprice text-right"><%t OrdersAdmin.UnitPrice "Unit Price" %></th>
            <th class="unittax text-right"><%t OrdersAdmin.UnitTax "Unit Tax" %></th>
            <th class="tax-type text-right"><%t OrdersAdmin.TaxType "Tax Type" %></th>
        </tr>
    </thead>
    <tbody><% loop $Items %>
        <tr>
            <td class="text-left">{$StockID}</td>
            <td class="text-left"><strong>{$Title}</strong>
                <% if $Customisations.exists %>
                    <br />
                    <em>$CustomisationHTML</em>
                <% end_if %>
            </td>
            <td class="text-center">{$Quantity}</td>
            <td class="text-right">{$UnitPrice.Nice}</td>
            <td class="text-right">{$UnitTax.Nice}</td>
            <td class="text-right">{$TaxRate.Title}</td>
        </tr>
    <% end_loop %></tbody>
</table>