<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>{$Top.Type}: {$Object.OrderNumber}</title>
    </head>

    <body>
        <div class="container">
            <header class="header">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="panel">
                            <div class="panel-body">
                                <br/>
                                <% if $Logo.exists %>
                                    <img class="img-fluid" src="{$Logo.Fit(400,240).URL}" />
                                <% end_if %>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 text-right">
                        <div class="panel">
                            <h1 class="panel-heading">
                                {$Title}
                            </h1>
                            <div class="panel-body">
                                {$HeaderContent}
                            </div>
                        </div>
                    </div>
                </div>

                <hr/>

                <div class="row">
                    <% with $Object %>
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

                        <div class="col-sm-8">
                            <table style="width: 100%;" class="table">
                                <tbody>
                                    <tr>
                                        <th><%t OrdersAdmin.RefNo "Ref No." %></th>
                                        <td>$OrderNumber</td>
                                    </tr>
                                    <tr>
                                        <th><%t OrdersAdmin.IssueDate "Issue Date" %></th>
                                        <td>$StartDate.Format('d/M/Y')</td>
                                    </tr>
                                    <tr>
                                        <th><% if $Top.Type == "Estimate" %>
                                            <%t OrdersAdmin.ValidUntil "Valid Until" %>
                                        <% else %>
                                            <%t OrdersAdmin.DueOn "Due On" %>
                                        <% end_if %></th>
                                        <td>$EndDate.Format('d/M/Y')</td>
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
                    <% end_with %>
                </div>
            </header>

            <hr/>

            <main>
                <% with $Object %>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="description text-left"><%t OrdersAdmin.Item "Item" %></th>
                                <th class="qty text-center"><%t OrdersAdmin.Qty "Qty" %></th>
                                <th class="unitprice text-right"><%t OrdersAdmin.UnitPrice "Unit Price" %></th>
                                <th class="unittax text-right"><%t OrdersAdmin.UnitTax "Unit Tax" %></th>
                                <th class="tax-type text-right"><%t OrdersAdmin.TaxType "Tax Type" %></th>
                            </tr>
                        </thead>
                        <tbody><% loop $Items %>
                            <tr>
                                <td class="text-left">
                                    <strong>{$Title}</strong><br/>
                                    $Content
                                </td>
                                <td class="text-center">{$Quantity}</td>
                                <td class="text-right">{$UnitPrice.Nice}</td>
                                <td class="text-right">{$UnitTax.Nice}</td>
                                <td class="text-right">{$Tax.Title}</td>
                            </tr>
                        <% end_loop %></tbody>
                    </table>
                <% end_with %>
            </main>

            <hr/>

            <footer class="row">
                <div class="col-sm-8 d-none d-md-block">
                    {$FooterContent}
                </div>

                <div class="col-sm-4">
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
                                
                                <% loop $TaxList %>
                                    <tr>
                                        <th class="text-right">{$Rate.Title}</th>
                                        <td class="text-right">$Total.Nice</td>
                                    </tr>
                                <% end_loop %>
        
                                <tr>
                                    <th class="text-right"><%t OrdersAdmin.GrandTotal "Grand Total" %></th>
                                    <td class="text-right">$Total.Nice</td>
                                </tr>
                            </tbody>
                        </table>
                    <% end_with %>
                </div>

                <div class="col-sm-8 hide-pdf d-block d-sm-none">
                    {$FooterContent}
                </div>

                <div class="col-sm-12 text-center">
                    <a class="btn btn-lg btn-primary font-icon-down-circled" href="{$Object.PDFLink()}">Download</a>
                </div>
            </footer> 
        <div>
    </body>
</html>