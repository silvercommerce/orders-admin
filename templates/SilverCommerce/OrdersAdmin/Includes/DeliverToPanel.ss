<div class="panel">
    <div class="panel-heading">
        <%t OrdersAdmin.DeliverTo "Deliver To" %>
    </div>
    <div class="panel-body">
        $DeliveryFirstName $DeliverySurname<br/>
        <% if $DeliveryCompany %>$DeliveryCompany<br/><% end_if %>
        $DeliveryAddress1<br/>
        <% if $DeliveryAddress2 %>$DeliveryAddress2<br/><% end_if %>
        $DeliveryCity<br/>
        $DeliveryPostCode<br/>
        $DeliveryCountry
    </div>
</div>