function pluginTagAddSubType(toupdate, toobserve, url) {
    //console.log($('#'+toobserve).val());
    $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        data: {"subtypes": $('#'+toobserve).val(),
               "action": "list_subtypes"},
        success: function (json) {
            $.each(json, function(index, value){
                $('#'+toupdate).append($('<option>', value));
            });
            deselectAll(toobserve);
            selectAll(toupdate);
        }
    });
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
   var template = "<span class='tag_choice' style='";
   if (color !== "") {
      var invertedcolor = idealTextColor(color);

      template+= " background-color: " + color + "; ";
      template+= " color: " + invertedcolor + "; ";
   } else {
      template+= " border: 1px solid #BBB; ";
   }
   template+= "'>" + option.text + "</span>";

   return template;
}
