<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice</title>
    </head>

    <body>
        <div class="container">
            <header class="header">
                <h1>
                    <% if $Top.Type == "Invoice" %>
                        <%t OrdersAdmin.Invoice "Invoice" %>
                    <% else %>
                        <%t OrdersAdmin.Estimate "Estimate" %>
                    <% end_if %>
                </h1>

                <hr/>

                <div class="row">
                    <% with $Object %>
                        <div class="col-sm-4">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th><%t OrdersAdmin.RefNo "Ref No." %></th>
                                        <td>$OrderNumber</td>
                                    </tr>
                                    <tr>
                                        <th><%t OrdersAdmin.Date "Date" %></th>
                                        <td>$Date.Format('d/M/Y')</td>
                                    </tr>
                                    <% if $Top.Type == "Invoice" %>
                                        <tr>
                                            <th><%t OrdersAdmin.Status "Status" %></th>
                                            <td>$Status</td>
                                        </tr>
                                        <tr>
                                            <th><%t OrdersAdmin.Action "Action" %></th>
                                            <td>$Action</td>
                                        </tr>
                                    <% end_if %>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-sm-4">
                            <div class="panel">
                                <div class="panel-heading">
                                    <%t OrdersAdmin.IssuedTo "Issued To" %>
                                </div>
                                <div class="panel-body">
                                    $FirstName $Surname<br/>
                                    <% if $Company %>$Company<br/><% end_if %>
                                    $Address1<br/>
                                    <% if $Address2 %>$Address2<br/><% end_if %>
                                    $City<br/>
                                    $PostCode<br/>
                                    $Country
                                </div>
                            </div>
                        </div>
                    <% end_with %>

                    <div class="col-sm-4">
                        <div class="panel">
                            <div class="panel-body">
                                <% if $Type == "Invoice" %>
                                    {$SiteConfig.InvoiceHeaderContent}
                                <% else %>
                                    {$SiteConfig.EstimateHeaderContent}
                                <% end_if %>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <hr/>

            <main>
                <% with $Object %>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="description"><%t OrdersAdmin.Item "Item" %></th>
                                <th class="qty text-centered"><%t OrdersAdmin.Qty "Qty" %></th>
                                <th class="unitprice text-right"><%t OrdersAdmin.UnitPrice "Unit Price" %></th>
                                <th class="tax text-right"><%t OrdersAdmin.Tax "Tax" %></th>
                            </tr>
                        </thead>
                        <tbody><% loop $Items %>
                            <tr>
                                <td>
                                    <strong>{$Title}</strong><br/>
                                    $Content
                                </td>
                                <td class="text-centered">{$Quantity}</td>
                                <td class="text-right">{$UnitPrice.Nice}</td>
                                <td class="text-right">{$UnitTax.Nice}</td>
                            </tr>
                        <% end_loop %></tbody>
                    </table>
                <% end_with %>
            </main>

            <hr/>

            <footer class="row">
                <div class="col-sm-3 col-sm-push-9">
                    <% with $Object %>
                        <table class="table total-table">
                            <tbody>
                                <tr>
                                    <th class="text-right"><%t OrdersAdmin.SubTotal "SubTotal" %></th>
                                    <td class="text-right">$SubTotal.Nice</td>
                                </tr>
        
                                <% if $DiscountAmount.RAW > 0 %>
                                    <tr>
                                        <th class="text-right"><%t OrdersAdmin.Discount "Discount" %></th>
                                        <td class="text-right">$DiscountAmount.Nice</td>
                                    </tr>
                                <% end_if %>
                                
                                <tr>
                                    <th class="text-right"><%t OrdersAdmin.Postage "Postage" %></th>
                                    <td class="text-right">$PostageCost.Nice</td>
                                </tr>
        
                                <tr>
                                    <th class="text-right"><%t OrdersAdmin.TotalTax "Total Tax" %></th>
                                    <td class="text-right">$TaxTotal.Nice</td>
                                </tr>
        
                                <tr>
                                    <th class="text-right"><%t OrdersAdmin.GrandTotal "Grand Total" %></th>
                                    <td class="text-right">$Total.Nice</td>
                                </tr>
                            </tbody>
                        </table>
                    <% end_with %>
                </div>

                <div class="col-sm-9 col-sm-pull-3">
                    <% if {$Type} == "Invoice" %>
                        {$SiteConfig.InvoiceFooterContent}
                    <% else %>
                        {$SiteConfig.EstimateFooterContent}
                    <% end_if %>
                </div>
            </footer> 
        <div>
    </body>
</html>