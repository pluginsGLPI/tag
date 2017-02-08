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
