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