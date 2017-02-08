function parseUrl(val) {
    var result = "Not found",
        tmp = [];
    location.search
    //.replace ( "?", "" )
    // this is better, there might be a question mark inside
    .substr(1)
        .split("&")
        .forEach(function (item) {
         tmp = item.split("=");
         if (tmp[0] === val) {
            result = decodeURIComponent(tmp[1]);
         }
        });
    return result;
}

// FAIL (with multiples entities) ?
function getIdFromHeader() {
   var splited = $("tr.headerRow:first th:first").text().split(" ");
   return splited[splited.length - 1];
}

function upperFirst(str) {
   return str.charAt(0).toUpperCase() + str.substring(1);
}

function isInteger(x) {
   return (typeof x === 'number') && (x % 1 === 0);
}

function idealTextColor(hexTripletColor) {
   var nThreshold = 105;
   hexTripletColor.replace(/^#/,'')
   var components = {
      R: parseInt(hexTripletColor.substring(0, 2), 16),
      G: parseInt(hexTripletColor.substring(2, 4), 16),
      B: parseInt(hexTripletColor.substring(4, 6), 16)
   };
   var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);
   return ((255 - bgDelta) < nThreshold) ? "#000000" : "#ffffff";
}

function formatOption(option) {
   var color = option.element[0].getAttribute("data-color-option");
   var template = "<span style='padding: 2px; border-radius: 3px; ";
   if (color !== "") {
      var invertedcolor = idealTextColor(color);

      template+= " background-color: " + color + "; ";
      template+= " color: " + invertedcolor + "; ";
   }
   template+= "'>" + option.text + "</span>";

   return template;
}

function showTags() {
   var str = document.location.href.substr(document.location.href.search('/front/') + 7);
   var itemtype = str.substr(0, str.search('.form.php'));

   if (location.pathname.indexOf('plugins') > 0) {
      // get plugin name :
      str = document.location.href.substr(document.location.href.search('/plugins/') + 9);
      var plugin_name = str.substr(0, str.search('/front/'));

      itemtype = 'Plugin' + upperFirst(plugin_name) + upperFirst(itemtype);

      urlAjax = "../../tag/ajax/tag.php";
   } else {
      urlAjax = "../plugins/tag/ajax/tag.php";
   }

   // Don't show in notification :
   if (itemtype == 'notification' || itemtype == 'crontask') { //Note : test crontask no is need on 0.90
      return;
   }

   var id = parseUrl('id');
   if (id == '' || id == 'Not found') {
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
      data: {"itemtype" : itemtype,
         "id"       : id,
         "action"   : "tag_values"},
      success: function(msg){
         if ($("#mainformtable").find("[name='plugin_tag_tag_itemtype']").length == 0) {
            $("#mainformtable tr:first").after(msg + hidden_fields);
            $("#mainformtable .chosen-select-no-results").select2({
               'formatResult': formatOption,
               'formatSelection': formatOption
            });
         }
      }
   });
}

$(document).ready(function() {
   $(".ui-tabs-panel:visible").find(".headerRow:visible").ready(function() {
      showTags();
   });

   $("#tabspanel + div.ui-tabs").on("tabsload", function( event, ui ) {
      //check if we're on the main tab...
      var current_glpi_tab = $('div.ui-tabs li.ui-tabs-active a')
        .attr('href')
        .match(/&_glpi_tab=.+\$(.*)&id=/)[1];

      if (current_glpi_tab === 'main') {
         showTags();
      }
   });
});
