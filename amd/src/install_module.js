define(['jquery', 'core/config', 'core/str', 'core/modal_factory', 'core/modal_events'], function ($, config, str, ModalFactory) {
    return {
        init: function () {

// ---------------------------------------------------------------------------------------------------------------------
            var sectionModules = function() {
                $('.template_section').change(function() {
                    var status = $(this).find('input').is(':checked');
                    var sectionId = $(this).attr('sectionid');
                    $('.template_module[sid = "' + sectionId + '"]').prop('checked', status);

                    if ($('input:checked').length !== 0) {
                        $('input.template_module').attr('disabled',''); // Disable module selection as they are part of a section
                        $('#target_topic_btn').removeClass('disabled');
                        // Change button to section text
                        var getTheString = str.get_string('select_section_after', 'block_modlib');
                        $.when(getTheString).done(function(theString) {
                            $('#target_topic_btn').html(theString);
                        });
                        // Change button title to section text
                        var getTheString = str.get_string('select_section_after_mouseover', 'block_modlib');
                        $.when(getTheString).done(function(theString) {
                            $('#target_topic_btn').attr('title', theString);
                        });
                    } else {
                        $('input.template_module').removeAttr('disabled'); // Activate module selection
                        $('#target_topic_btn').addClass('disabled');
                        // Change button to module text
                        var getTheString = str.get_string('select_section', 'block_modlib');
                        $.when(getTheString).done(function(theString) {
                            $('#target_topic_btn').html(theString);
                        });
                        // Change button mouseover to module text
                        var getTheString = str.get_string('select_section_mouseover', 'block_modlib');
                        $.when(getTheString).done(function(theString) {
                            $('#target_topic_btn').attr('title', theString);
                        });
                    }
                });

                $('.module_row').on('click', function () {
                    if ($('input:checked').length !== 0) {
                        $('#target_topic_btn').removeClass('disabled');
                    } else {
                        $('#target_topic_btn').addClass('disabled');
                    }
                });
            };
// ---------------------------------------------------------------------------------------------------------------------
            var executeModules = function (sectionId) {
                var count = $('input[class="template_module"]:checked').length;
                // Get the selected modules
                $('input[class="template_module"]:checked').each(function () {
                    var module = {};
                    module.sectionid = sectionId;
                    module.cmid = $(this).attr('cmid');
                    module.name = $(this).attr('name');
                    module.type = $(this).attr('module_type');

                    callAjax(module);
                    if (!--count) { // once all modules have been installed reload the page
                        location.reload();
                    }
                });
            };
// ---------------------------------------------------------------------------------------------------------------------
            var executeSections = function(sectionId) {
                // Get the selected sections
                var count = $('input[class="template_section"]:checked').length;
                $('input[class="template_section"]:checked').each(function () {
                    var section = {};
                    section.id = $(this).attr('sid');
                    section.name = $(this).attr('name');

                    // Now install the module
                    var data = {};
                    data.sectionid = sectionId;
                    data.cmid = section.id;
                    data.type = 'section';

                    callAjax(data);
                    if (!--count) { // once all sections have been installed reload the page
                        location.reload();
                    }
                });
            }
// ---------------------------------------------------------------------------------------------------------------------
            var execute = function () {
                $(".modlib-sections .dropdown-item").on('click', function () {

                    $('.modlib-modal').show(); // Show the graphical interlude...

                    // Get the selected sections
                    if ($('input[class="template_section"]:checked').length === 0) {
                        // No sections have been selected - on to single modules
                        executeModules($(this).attr('value'));
                    } else {
                        executeSections($(this).attr('value'));
                    }
                });
            };
// ---------------------------------------------------------------------------------------------------------------------
            var callAjax = function(data) {
                var execUrl = config.wwwroot + '/blocks/modlib/execute.php';
                $.ajax({
                    url: execUrl,
                    type: "POST",
                    data: {
                        'sectionid': data.sectionid,
                        'cmid': data.cmid,
                        'type': data.type
                    },
                    success: function (result) {
                        $('#modlib-modal-msg').html(result);
                    },
                    error: function (e) {
                        $('.modlib-modal').hide();
                        console.error(e);
                    }
                });
            }
// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function () {
                // Load all required functions above
                sectionModules();
                execute();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function () {
                initFunctions();

                $('#modlib-spinner-modal').hide();
                $('tr.module').css('cursor', 'pointer');
            });
        }
    };
})
