function getNumColonne() {
   var header = $(".tab_cadrehov .tab_bg_2 th");
   for (var i=0; i<header.length; i++) {
      if (header[i].innerHTML.indexOf('Tag') > -1) {
         return i+1;
      }
   }
}
/*
function getUrlParameters()
{
   //Analyse de l'URL :
   var sPageURL = window.location.search.substring(1);
   var sURLVariables = sPageURL.split('&'); var tab = [];
   for (var i = 0; i < sURLVariables.length; i++) 
   {
      var sParameterName = sURLVariables[i].split('=');
      tab[i] = sParameterName[1];
   }
   return tab;
}

function getActiveFilters() {
   
   var parameters = getParameters(); //getUrlParameters();
   var tab = [];
   while (parameters.indexOf("10500") != -1) {
      index = parameters.indexOf("10500");
      tab.push( parameters[index+2]);
      parameters[index] = "";
   }
   return tab;
}*/

function getActiveFilters() {
   var tab = [];
   var selects = $("select[name^='field[']");
   var contains = $("input[type='text'][name^='contains[']");
   for (i=0; i<selects.length; i++) {
      if (selects[i].selectedOptions[0].value == "10500") {
         if (contains[i].value != "") {
            tab.push(contains[i].value);
         }
      }
   }
   return tab;
}

function arrayInArray(filters, str_tab) {
   for (i=0; i < filters.length; i++) {
      if (filters[i] != "" && $.inArray(filters[i], str_tab) == -1) {
         return false;
      }
   }
   return true;
}

function mask() {

   var haveMaskColonns = false;
   var num_colonne = getNumColonne() -1;
   var filters = decodeURIComponent(getActiveFilters()).split(',');
   filters = jQuery.unique(filters); //optimisation
   
   for (var i=0, l=$(".tab_cadrehov .tab_bg_1").length; i<l; i++) {
      var text = $(".tab_cadrehov .tab_bg_1:eq("+i+") td:eq("+num_colonne+")").text();
      var str_tab = text.split(",");
      if (! arrayInArray(filters, str_tab)) {
         //Mask the line i
         $(".tab_cadrehov .tab_bg_1:eq("+i+")").hide();
         haveMaskColonns = true;
      }
   }
   
   if (haveMaskColonns) {
      $(".tab_cadre_pager .b").text("");
   }
}

$(document).ready(function() {
   mask();
});
