<?php
include ('../../../inc/includes.php');

Plugin::load('tag', true);

header('Content-Type: text/javascript');
?>
function getParamValue(param,url) {
   var u = url == undefined ? document.location.href : url;
   var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
   matches = u.match(reg);
   return matches != null && matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
}

// FAIL (with multiples entities) ?
function getIdFromHeader() {
   var headerRow = document.querySelectorAll("tr.headerRow")[0]; //or $("tr.headerRow:first");
   var splited = headerRow.querySelectorAll("th")[0].textContent.split(" ");
   return splited[splited.length - 1];
}

function upperFirst(str) {
   return str.charAt(0).toUpperCase() + str.substring(1);
}

function isInteger(x) {
   return (typeof x === 'number') && (x % 1 === 0);
}

function showTags() {
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
      id = parseInt(getIdFromHeader()); //For part of Mreporting plugin
      
      // Security :
      if (! isInteger(id)) {
         return;
      }
   }
   
   var hidden_fields = "<input type='hidden' name='plugin_tag_tag_id' value='"+id+"'>" +
      "<input type='hidden' name='plugin_tag_tag_itemtype' value='"+itemtype+"'>";
   $.ajax({
      type: "POST",
      url: urlAjax,
      data: "itemtype=" + itemtype + "&id=" + id,
      success: function(msg){
            if ($(".ui-tabs-panel:visible").find("[name='plugin_tag_tag_itemtype']").length == 0) {
               $(".ui-tabs-panel:visible").find("#mainformtable tr:first").after(msg + hidden_fields);            
               $(".ui-tabs-panel:visible").find('.chosen-select-no-results').select2();
            }
         }
   });
}

$(document).ready(function() {
   $(".ui-tabs-panel:visible").find(".headerRow:visible").ready(function() {
      showTags();
   });

   $("#tabspanel + div.ui-tabs").on("tabsload", function( event, ui ) {
      showTags();
   });
});
