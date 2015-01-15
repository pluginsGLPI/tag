<?php
include ('../../../inc/includes.php');

Plugin::load('tag', true);

header('Content-Type: text/javascript');
?>
function insertAfter(newNode, referenceNode) {
   // For example : User with no right
   if (referenceNode !== undefined) {
      referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
   }
}

function getParamValue(param,url) {
   var u = url == undefined ? document.location.href : url;
   var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
   matches = u.match(reg);
   return matches != null && matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
}

function getIdFromHeader() {
   var headerRow = document.querySelectorAll("tr.headerRow")[0];
   var splited = headerRow.querySelectorAll("th")[0].textContent.split(" ");
   return splited[splited.length - 1];
}

function upperFirst(str) {
   return str.charAt(0).toUpperCase() + str.substring(1);
}

function isInteger(x) {
   return (typeof x === 'number') && (x % 1 === 0);
}

Ext.onReady(function() {
   var str = document.location.href.substr(document.location.href.search('/front/') + 7);
   var itemtype = str.substr(0, str.search('.form.php'));
   
   if (location.pathname.indexOf('plugins') > 0) {
      // get plugin name :
      str = document.location.href.substr(document.location.href.search('/plugins/') + 9);
      var plugin_name = str.substr(0, str.search('/front/'));
      
      itemtype = 'Plugin' + upperFirst(plugin_name) + upperFirst(itemtype);
      
      urlAjax = "../../tag/ajax/tags_values.php";
   } else {
      urlAjax = "../plugins/tag/ajax/tags_values.php";
   }
   
   // Don't show in notification :
   if (itemtype == 'notification' || itemtype == 'crontask') {
      return;
   }
   
   var id = getParamValue('id');
   
   if (id == '') {
      id = getIdFromHeader(); //For part of Mreporting plugin
      
      // Security :
      if (! isInteger(id)) {
         return;
      }
   }
   
   var hidden_fields = "<input type='hidden' name='plugin_tag_tag_id' value='"+id+"'>" +
      "<input type='hidden' name='plugin_tag_tag_itemtype' value='"+itemtype+"'>";
   
   Ext.Ajax.request({
      url: urlAjax+"?itemtype=" + itemtype + "&id=" + id,
      success: function(data) {
         
         Ext.select('#mainformtable tr:first').insertHtml('afterEnd', data.responseText + hidden_fields);
         
         var elements = document.querySelectorAll('.chosen-select-no-results');
         for (var i = 0; i < elements.length; i++) {
            new ChosenT(elements[i], {no_results_text: "<?php echo __("No tag found", 'tag'); ?>"});
         }
         
      }
   });
});
