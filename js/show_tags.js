function insertAfter(newNode, referenceNode) {
   referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function getParamValue(param,url) {
   var u = url == undefined ? document.location.href : url;
   var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
   matches = u.match(reg);
   return matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
}

Ext.onReady(function() {
   var str = document.location.href.substr(document.location.href.search('/front/') + 7);
   var itemtype = str.substr(0, str.search('.form.php'));
   
   // Don't show in notification :
   if (itemtype == 'notification') {
      return;
   }
   
   var id = getParamValue('id');
   
   var hidden_fields = "<input type='hidden' name='plugin_tag_tag_id' value='"+id+"'>" +
      "<input type='hidden' name='plugin_tag_tag_itemtype' value='"+itemtype+"'>";
   
   Ext.Ajax.request({
      url: "../plugins/tag/ajax/tags_values.php?itemtype=" + itemtype + "&id=" + id,
      success: function(data) {
         //Ext.select('#mainformtable tr').insertHtml('afterEnd', data.responseText);
         //$("#mainformtable tr").eq(0).after(data.responseText + hidden_fields);
         
         var tr = document.createElement('tr');
         tr.innerHTML = data.responseText + hidden_fields;
         insertAfter(tr, document.querySelectorAll("tr.headerRow")[0]);
         
         var elements = document.querySelectorAll('.chosen-select-no-results');
         for (var i = 0; i < elements.length; i++) {
            new Chosen(elements[i], {no_results_text: "Aucun tag trouvé."});
         }
         
      }
   });
});
