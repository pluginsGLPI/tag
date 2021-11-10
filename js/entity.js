$(function() {
    // only where main tabs exist (probably in create/edit form of items)
    if ($('#tabspanel').length == 0) {
        return;
    }

    $(document).on('glpi.tab.loaded', function() {
        setTimeout(function() {
            setEntityTag();
        }, 100);
    });
});

var setEntityTag = function() {
    $('.entity-name, .entity-badge')
        .each(function() {
            var entity_element = $(this);
            entity_name = entity_element.attr('title');
            if (entity_element.hasClass('tags_already_set')) {
                return; // consider this return as a continue in a jquery each
            }
            entity_element.addClass('tags_already_set');

            $.ajax({
                url: CFG_GLPI.root_doc + '/' + GLPI_PLUGINS_PATH.tag + '/ajax/get_entity_tags.php',
                data: {
                'name': entity_name,
                },
                success: function(response) {
                entity_element.html(function() {
                    if ($(this).html().indexOf(')') > 0) {
                        return $(this).html().replace(/\)$/, response + ')');
                    } else {
                        return $(this).html() + response;
                    }
                });
                }
            });
        });
};
