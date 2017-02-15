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
        if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
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
