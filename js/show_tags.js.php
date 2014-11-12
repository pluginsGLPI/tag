Ext.onReady(function() {
   var str = document.location.href.substr(document.location.href.search('/front/') + 7);
   var id = str.substr(str.search('id=') + 3)
   var itemtype = str.substr(0, str.search('.form.php'));
   
   var hidden_fields = "<input type='hidden' name='plugin_tag_etiquette_id' value='"+id+"'>" +
      "<input type='hidden' name='plugin_tag_etiquette_itemtype' value='"+itemtype+"'>";
   
   Ext.Ajax.request({
      url: "../plugins/tag/ajax/tags_values.php?id=" + id + "&itemtype=" + itemtype,
      success: function(data) {
         //Ext.select('#mainformtable tr').insertHtml('afterEnd', data.responseText);
         $("#mainformtable tr").eq(0).after(data.responseText + hidden_fields);
      }
   });
});

/*
$(document).ready(function() {
   var resultat = $("#mainformtable tr").eq(0).after(html_code_ticket);
   if (resultat.length == 0) {
      console.log('FAIL to add tr');
   }
*/