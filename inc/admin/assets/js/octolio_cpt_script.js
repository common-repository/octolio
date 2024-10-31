jQuery(document).ready(function ($) {
    //import il8n
    const {__, _x, _n, _nx} = wp.i18n;

    /*
    Events
    */

    //Display datepicker
    $('body').on('focus', '.datepicker', function () {
        $(this).datepicker();
    });

    //Reindex elements when cpt action is changed
    $(document).on('change', '#' + octolio_global_var.cpt_type + '_type', function () {
        switch_integration_type(this.value);
        reindex_elements();
    });

    //Reindex elements when cpt action type is changed
    $(document).on('change', '.' + octolio_global_var.cpt_type + '_action_type', function () {
        display_params('action', this.value, $(this).parent());
        reindex_elements();
    });

    //Reindex elements when cpt action filter type is changed
    $(document).on('change', '.' + octolio_global_var.cpt_type + '_filter_type', function () {
        display_params('and_condition', this.value, $(this).parent());
        reindex_elements();
    });

    //Add a section (And condition / Or condition / Action)
    $('body').on('click', '.octolio_add_section', function () {
        var content_to_add = $(this).data('type');
        add_content(content_to_add, $(this).parent());
    });

    //Click to delete a section (and/or/action)
    $('body').on('click', '.or_delete, .and_delete, .action_delete', function () {

        if ('or_delete' == $(this).attr('class')) {
            section_class = '.or_condition';
            link_class = '.or_link';
        } else if ('and_delete' == $(this).attr('class')) {
            section_class = '.and_condition';
            link_class = '.and_link';
        } else {
            section_class = '.one_action';
            link_class = '.action_link';
        }

        $(this).closest(section_class).prev(link_class).remove();
        $(this).closest(section_class).remove();

        reindex_elements();

    });

    //Unlock elements (when we should be careful with some options)
    $('body').on('click', '.unlock_elements', function () {

        var checkbox = $(this);

        $(this).closest('.octolio_filter_params').find('.unlock').each(function () {
            if (checkbox.is(':checked')) $(this).removeAttr('disabled'); else $(this).prop('disabled', true);
        });
    });

    //Unlock disabled elements before form submit
    $('form').submit(function () {
        $('.unlock').removeAttr('disabled');
    });

    //Display action or filter params
    function display_params(params_type, filter_type, current_element) {

        var current_cpt_type = $('#' + octolio_global_var.cpt_type + '_type').val();

        if ('action' == params_type) {

            var id_lib_element = '#lib_actions_params_list';
            var params_element_class = '.octolio_action_params';

        } else if ('and_condition' == params_type) {
            var id_lib_element = '#lib_filters_params_list';
            var params_element_class = '.octolio_filter_params';

        } else if ('or_condition' == params_type) {
            var id_lib_element = '#lib_filters_params_list';
            var params_element_class = '.octolio_filter_params';
        }

        //Collect the HTML
        var html = JSON.parse($(id_lib_element).val());

        //No filter type set? Let's display the first filter then
        if (null == filter_type) filter_type = Object.keys(html[current_cpt_type])[0];

        current_element.find(params_element_class).html(html[current_cpt_type][filter_type]['params']);

        //initialise select2
        select2_initialise();

    }

    //Reindex elements
    function reindex_elements() {
        attributes_to_update = [
            'id',
            'name',
        ];

        action_container = $('.actions_container').not('.and_link, .octolio_add_section');

        $.each(action_container, function (key, value) {

            $(value).find('[name*=\'nb_action\']').each(function () {

                element_to_update = $(this);

                $.each(attributes_to_update, function (key, value) {

                    if (null != element_to_update.attr(value)) {
                        element_to_update.attr(value, element_to_update.attr(value).replace(new RegExp('__nb_action__', 'g'), key));
                    }
                });
            });
        });

        and_container = $('.and_conditions_container');
        or_container = $('.or_conditions_container');

        $.each(and_container, function (key, value) {

            var index = or_container.find('.or_condition').index($(this).closest('.or_condition'));

            var and_childrens = ($(this).children().not('.and_link, .octolio_add_section'));

            $.each(and_childrens, function (key, value) {

                and_number = key;
                $(value).find('[name*=\'nb_and\']').each(function () {
                    element_to_update = $(this);

                    $.each(attributes_to_update, function (key, value) {

                        if (null != element_to_update.attr(value)) {
                            //Set the filters AND dimension [XX][key]
                            element_to_update.attr(value, element_to_update.attr(value).replace(new RegExp('__nb_and__', 'g'), and_number));
                            //Set the filters OR dimension [index][XX]
                            element_to_update.attr(value, element_to_update.attr(value).replace(new RegExp('__nb_or__', 'g'), index));
                        }
                    });
                });
            });
        });
    }

    //Swith cpt action type
    function switch_integration_type(value) {

        $('.or_conditions_container').children().not('.or_condition:eq(0), .octolio_add_section').remove();
        $('.and_conditions_container').children().not('.octolio_add_section').remove();
        $('.actions_container').children().not('.octolio_add_section').remove();

        if ('workflow' == octolio_global_var.cpt_type) {
            var current_cpt_type = $('#' + octolio_global_var.cpt_type + '_type').val();
            $('#workflow_hook').html(JSON.parse($('#lib_hook_dropdowns').val())[current_cpt_type]);
        }

        add_content([
            'and_condition',
        ], $('.and_conditions_container'));

        add_content([
            'action',
        ], $('.actions_container'));


    }

    //Add a section
    function add_content(section_to_add = '', container = {}) {

        var current_cpt_type = $('#' + octolio_global_var.cpt_type + '_type').val();
        var content_to_add, and_condition_container = {};


        if (section_to_add == 'or_condition') {


            //Create the AND container which will be inside the OR one.
            and_condition_container = $('<div>', {'class': 'and_conditions_container'});
            and_condition_container.append($('.add_and_section').clone()[0]);
            //Add the AND section in the AND section container
            add_content([
                'and_condition',
            ], and_condition_container);

            delete_div = $('<div>', {'class': 'or_delete'});
            if (0 < container.find('.or_condition').length) delete_div.append($('<i>', {'class': 'octolio-trash-o'}));

            and_wrapper = $('<div>', {'class': 'or_condition_wrapper'});
            and_wrapper.append(delete_div);
            and_wrapper.append(and_condition_container);


            //Create OR section and add the AND section container
            or_section = $('<div>', {'class': 'or_condition'});
            or_section.prepend(and_wrapper);

            content_to_add = ($('<div>', {
                'class': 'or_link',
                'text': __('OR'),
            })).add(or_section);

        } else if (section_to_add == 'and_condition') {

            and_params = $('<div>', {'class': 'and_params'});
            and_params.append(JSON.parse($('#lib_filter_dropdowns').val())[current_cpt_type]);
            and_params.append($('<div>', {'class': 'octolio_filter_params'}));


            delete_div = $('<div>', {'class': 'and_delete'});
            if (0 < container.find('.and_condition').length) delete_div.append($('<i>', {'class': 'octolio-trash-o'}));

            and_wrapper = $('<div>', {'class': 'and_condition_wrapper'});
            and_wrapper.append(delete_div);
            and_wrapper.append(and_params);

            and_section = $('<div>', {'class': 'and_condition'});
            and_section.append(and_wrapper);

            if (1 > container.find('.and_condition').length) {
                content_to_add = and_section;
            } else {
                content_to_add = ($('<div>', {
                    'class': 'and_link',
                    'text': __('AND'),
                })).add(and_section);
            }

        } else if (section_to_add == 'action') {

            var action_params = $('<div>', {'class': 'action_params'});
            action_params.append(JSON.parse($('#lib_action_dropdowns').val())[current_cpt_type]);
            action_params.append($('<div>', {'class': 'octolio_action_params'}));

            delete_div = $('<div>', {'class': 'action_delete'});
            if (0 < container.find('.one_action').length) delete_div.append($('<i>', {'class': 'octolio-trash-o'}));

            action_wrapper = $('<div>', {'class': 'one_action_wrapper'});
            action_wrapper.append(delete_div);
            action_wrapper.append(action_params);

            action_section = $('<div>', {'class': 'one_action'});
            action_section.append(action_wrapper);

            if (1 > container.find('.one_action').length) {
                content_to_add = action_section;
            } else {
                content_to_add = ($('<div>', {
                    'class': 'action_link',
                    'text': __('AND'),
                })).add(action_section);
            }

        }

        container.children().last().before(content_to_add);
        if (section_to_add != 'or_condition') display_params(section_to_add, null, content_to_add);

        reindex_elements();
    }


    $('body').on('click', 'input[name ="save_db_button"]', function (event) {
        event.preventDefault();

        $('#save_db_result')
            .html($('<img>', {
                'class': 'octolio_loader',
                'src': octolio_global_var.plugin_url + '/admin/assets/img/ajax-loader.gif',
            }));
        $.ajax({
            url: 'admin-ajax.php',
            method: 'POST',
            data: {
                plugin: 'octolio',
                action: 'save_db',
            },
            success: function (data) {
                var answer = jQuery.parseJSON(data);
                if (true == answer.status) {

                    $('#save_db_result').html($('<i>', {
                        'class': 'octolio-check-circle-o',
                    }).add($('<span>', {
                        'text': __('Success'),
                    })));
                    $('.octolio_save_db')
                        .append($('<a>', {
                            'class': 'download_db_backup',
                            'text': __('How to download backup'),
                            'href': 'https://octolio.gitbook.io/documentation/database-backup-save-and-restore',
                            'target': '_blank',
                        }).append($('<i>', {'class': 'fas fa-download'})));

                } else {
                    $('#save_db_result').html($('<i>', {
                        'class': 'octolio-exclamation-triangle',
                    }).add($('<span>', {
                        'text': __('Error while saving. Check your server logs'),
                    })));
                }
            },
            error: function (data) { // en cas d'échec
                console.log(data);
            },
        });
    });


    function select2_initialise() {
        $('.octolio_select2').each(function (index, element) { // do not forget that "index" is just auto incremented value
            $(element).select2();
        });


    }

    select2_initialise();

});
