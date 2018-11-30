$(function() {
   var items_id = getUrlParameter('id');

   //only in edit form
   if (items_id == undefined) {
      return;
   }

   $(".ui-tabs-panel:visible").ready(function() {
      setEntityTag();
   })

   $("#tabspanel + div.ui-tabs").on("tabsload", function() {
      setTimeout(function() {
         setEntityTag();
      }, 300);
   });
});

var setEntityTag = function() {

   // find entity title for ticket
   var regex = /.+(-\s.+\s[0-9]+)*\s\((.+)\)/;
   var entity_title = $('#page .tab_cadre_pager tr.tab_bg_2:first-child td.b.big, \
                         #mainformtable tr:first-child th').filter(function() {
      return regex.test($(this).text());
   });

   if (entity_title.length > 0) {
      entity_title    = entity_title.first();
      var matches     = entity_title.text().match(regex);
      var entity_name = matches[2];
      replaceEntity(entity_title, entity_name)
   } else {
      // find entity title for all objects except tickets
      // check first the th with recursive select
      var entity_title =
         $('#mainformtable tr.headerRow > th:nth-child(2) > .tab_format tr')
            .has('select[name=is_recursive]');

      // next find the entity th
      if (entity_title.length > 0) {
         entity_title = entity_title.children("th:first-child");
      }

      // then replace it
      if (entity_title.length > 0) {
         replaceEntity(entity_title, entity_title.text());
      }
   }
};

var replaceEntity = function(entity_title, entity_name) {
   if (entity_title.length > 0) {
      $.ajax({
         url: '../plugins/tag/ajax/get_entity_tags.php',
         data: {
            'name': entity_name,
         },
         success: function(response) {
            entity_title.html(function() {
               if ($(this).html().indexOf(')') > 0) {
                  return $(this).html().replace(/\)$/, response+")");
               } else {
                  return $(this).html()+response;
               }
            });
         }
      });
   }
};

var getUrlParameter = function(val) {
   var result = undefined,
       tmp = [];

   location.search
      .substr(1) // remove '?'
      .split("&")
      .forEach(function (item) {
         tmp = item.split("=");
         if (tmp[0] === val) {
            result = decodeURIComponent(tmp[1]);
         }
      });
   return result;
};
