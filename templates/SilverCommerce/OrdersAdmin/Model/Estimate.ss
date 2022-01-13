<div class="estimate pb-5">
    <header class="header">
        <div class="row">
            <div class="col-sm-8">
                <div class="panel logopanel">
                    <div class="panel-body">
                        <div class="w-75">
                            <% if $SiteConfig.Logo.exists %>
                                <img
                                    class="img-fluid logoimg"
                                    src="{$SiteConfig.Logo.Base64DataURL}"
                                />
                            <% end_if %>
                        </div>

                        <h1 class="title mt-4">
                            <%t OrdersAdmin.EstimateTitle "Estimate {ref}" ref=$FullRef %>
                        </h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 text-right">
                <div class="panel contentpanel">
                    <div class="panel-body">
                        {$SiteConfig.EstimateHeaderContent}
                    </div>
                </div>
            </div>
        </div>

        <hr/>

        <div class="row">
            <div class="issuedtopanel <% if $isDeliverable %>col-sm-3<% else %>col-sm-4<% end_if %>">
                <% include SilverCommerce\OrdersAdmin\Includes\IssuedToPanel %>
            </div>

            <% if $isDeliverable %>
                <div class="delivertopanel col-sm-3">
                    <% include SilverCommerce\OrdersAdmin\Includes\DeliverToPanel %>
                </div>
            <% end_if %>

            <div class="infopanel <% if $isDeliverable %>col-sm-6<% else %>col-sm-8<% end_if %>">
                <table style="width: 100%;" class="table text-left">
                    <tbody>
                        <tr>
                            <th><%t OrdersAdmin.RefNo "Ref No." %></th>
                            <td>$FullRef</td>
                        </tr>
                        <tr>
                            <th><%t OrdersAdmin.IssueDate "Issue Date" %></th>
                            <td>$StartDate.Format('d/M/Y')</td>
                        </tr>
                        <tr>
                            <th><%t OrdersAdmin.ValidUntil "Valid Until" %></th>
                            <td>$EndDate.Format('d/M/Y')</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </header>

    <hr/>

    <main class="itemspanel col-sm-12">
        <% include SilverCommerce\OrdersAdmin\Includes\ItemsTable %>
    </main>

    <hr/>

    <footer class="row">
        <div class="col-sm-8 d-none d-md-block">
            {$SiteConfig.EstimateFooterContent}
        </div>

        <div class="col-sm-4">
            <% include SilverCommerce\OrdersAdmin\Includes\SummaryTable %>
        </div>

        <div class="col-sm-8 hide-pdf d-block d-sm-none">
            {$SiteConfig.EstimateFooterContent}
        </div>

        <div class="col-sm-12 text-center">
            <a
                class="btn btn-lg btn-primary font-icon-down-circled"
                href="{$PDFLink}"
            >
                Download
            </a>
        </div>
    </footer>
</div>