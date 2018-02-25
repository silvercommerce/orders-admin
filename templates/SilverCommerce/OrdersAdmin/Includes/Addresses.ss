<% with $Contact %>
    <% if $Locations.exists %>
        <ul>
            <% loop $Locations %>
                <li class="<% if $Default %>default<% end_if %>">
                    <% if $Default %><em>(<%t ContactAdmin.DefaultAddress "Default Address" %>)</em><br/><% end_if %>
                    $Title
                    <a href="{$Top.Link('editaddress')}/{$ID}">
                        <%t ContactAdmin.Edit "Edit" %>
                    </a>
                    |
                    <a href="{$Top.Link('removeaddress')}/{$ID}">
                        <%t ContactAdmin.Remove "Remove" %>
                    </a>
                </li>
            <% end_loop %>
        </ul>

        <hr/>
    <% else %>
        <p>
            <%t ContactAdmin.NoSavedAddresses "You have no saved addresses." %>
        </p>
    <% end_if %>
<% end_with %>

<p>
    <a href="{$Link('addaddress')}" class="btn btn-green btn-success">
        <%t ContactAdmin.AddAddress "Add Address" %>
    </a>
</p>