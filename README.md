# org.civicoop.singlecaserole
CiviCRM native extension to only allow one single occurence of a given case role on a given case type

This extension contains a JSON file with the configuration called **casetyperoles.json**. The file contains a case type **name** with the relationship type **name_a_b** of the associated relationshi type. So in the configuration here, it checks for the case type *Business* and for the role *Expert*.

There is a page as a callback for the url **civicrm/ajax/singlecaserole**. In this page you will find the function that is called from your button and pencil on the CaseView page. in core CiviCRM that function is CRM_Contact_Page_AJAX::relationship.
Because you can not use a buildForm hook on the CiviCRM CaseView form, this extension **WILL ONLY WORK** if you make a copy of the template CRM_Case_Form_CaseView.tpl in this extension (or in any other). In this template you have to track the two places where the url *civicrm/ajax/relation* is used and change it to use *civicrm/ajax/singlecaserole*:

# The first one#

This is the core bit
```smarty

          var postUrl = {/literal}"{crmURL p='civicrm/ajax/singlecaserole' h=0 }"{literal};
          cj.post( postUrl, { rel_contact: v1, rel_type: relType, contact_id: sourceContact,
            rel_id: relID, case_id: caseID, key: {/literal}"{crmKey name='civicrm/ajax/singlecaserole'}"{literal} },
            function( data ) {
              if ( data.status == 'process-relationship-success' ) {
                // reloading datatable
                var oTable = cj('#caseRoles-selector').dataTable();
                oTable.fnDraw();
              }
              else {
                if (data.status == 'singlecaserole-error') {
                  var relTypeName = cj("#role_type :selected").text();
                  var errorMsg = 'The case role ' + relTypeName + ' can only exist once on this case';
                } else {
                  // This is an awkward mix of smarty and javascript: the relTypeName variable is
                  // not available in smarty, could not find an i18n-correct way of doing this.
                  {/literal}
                    {capture assign=relTypeAdminLink}{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }{/capture}
                  {literal}
                  var errorMsg = relTypeName + ': ' + '{/literal}{ts escape="js" 1="$relTypeAdminLink"}The relationship type definition for the case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%1">Administer >> Option Lists >> Relationship Types</a>.{/ts}{literal}';
              }

                //display error message.
                cj().crmError(errorMsg);
              }
            }, 'json'
          );

```

Change it into:

```smarty

          var postUrl = {/literal}"{crmURL p='civicrm/ajax/singlecaserole' h=0 }"{literal};
          cj.post( postUrl, { rel_contact: v1, rel_type: relType, contact_id: sourceContact,
            rel_id: relID, case_id: caseID, key: {/literal}"{crmKey name='civicrm/ajax/singlecaserole'}"{literal} },
            function( data ) {
              if ( data.status == 'process-relationship-success' ) {
                // reloading datatable
                var oTable = cj('#caseRoles-selector').dataTable();
                oTable.fnDraw();
              }
              else {
                if (data.status == 'singlecaserole-error') {
                  var relTypeName = cj("#role_type :selected").text();
                  var errorMsg = 'The case role ' + relTypeName + ' can only exist once on this case';
                } else {
                  // This is an awkward mix of smarty and javascript: the relTypeName variable is
                  // not available in smarty, could not find an i18n-correct way of doing this.
                  {/literal}
                    {capture assign=relTypeAdminLink}{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }{/capture}
                  {literal}
                  var errorMsg = relTypeName + ': ' + '{/literal}{ts escape="js" 1="$relTypeAdminLink"}The relationship type definition for the case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%1">Administer >> Option Lists >> Relationship Types</a>.{/ts}{literal}';
              }

                //display error message.
                cj().crmError(errorMsg);
              }
            }, 'json'
          );

```

and the second one:

```smarty

        /* send synchronous request so that disabling any actions for slow servers*/
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/relation' h=0 }"{literal};
        var data = 'rel_contact='+ v1 + '&rel_type='+ v2 + '&contact_id='+sourceContact + '&rel_id='+ relID
          + '&case_id=' + caseID + "&key={/literal}{crmKey name='civicrm/ajax/relation'}{literal}";
        cj.ajax({
          type     : "POST",
          url      : postUrl,
          data     : data,
          async    : false,
          dataType : "json",
          success  : function(values) {
            if (values.status == 'process-relationship-success') {
              // reloading datatable
              var oTable = cj('#caseRoles-selector').dataTable();
              oTable.fnDraw();
            }
            else {
              var relTypeName = cj("#role_type :selected").text();
              var relTypeAdminLink = {/literal}"{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }"{literal};
              var errorMsg = '{/literal}{ts escape="js" 1="' + relTypeName + '" 2="' + relTypeAdminLink + '"}The relationship type definition for the %1 case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%2">Administer >> Option Lists >> Relationship Types</a>{/ts}{literal}.';

              //display error message.
              cj().crmError(errorMsg);
            }
          }
        });

```

change into:

```smarty

        /* send synchronous request so that disabling any actions for slow servers*/
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/singlecaserole' h=0 }"{literal};
        var data = 'rel_contact='+ v1 + '&rel_type='+ v2 + '&contact_id='+sourceContact + '&rel_id='+ relID
          + '&case_id=' + caseID + "&key={/literal}{crmKey name='civicrm/ajax/singlecaserole'}{literal}";
        cj.ajax({
          type     : "POST",
          url      : postUrl,
          data     : data,
          async    : false,
          dataType : "json",
          success  : function(values) {
            if (values.status == 'process-relationship-success') {
              // reloading datatable
              var oTable = cj('#caseRoles-selector').dataTable();
              oTable.fnDraw();
            }
            else {
              var relTypeName = cj("#role_type :selected").text();
              if (values.status == 'singlecaserole-error') {
                var errorMsg = 'The case role ' + relTypeName + ' can only exist once on this case';
              } else {
                var relTypeAdminLink = {/literal}"{crmURL p='civicrm/admin/reltype' q='reset=1' h=0 }"{literal};
                var errorMsg = '{/literal}{ts escape="js" 1="' + relTypeName + '" 2="' + relTypeAdminLink + '"}The relationship type definition for the %1 case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%2">Administer >> Option Lists >> Relationship Types</a>{/ts}{literal}.';
              }

              //display error message.
              cj().crmError(errorMsg);
            }
          }
        });
```




