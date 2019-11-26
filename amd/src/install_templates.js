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
                // Get the selected modules
                var modules = [];
                $('input[class="template_module"]:checked').each(function () {
                    var module = {};
                    module.sectionid = sectionId;
                    module.cmid = $(this).attr('cmid');
                    module.name = $(this).attr('name');
                    module.type = $(this).attr('module_type');
                    modules.push(module);
                });
                // Now install the modules
                var data = {};
                data.sectionid = sectionId; // The section into which..
                data.type = 'modules';
                data.payload = modules;

                callAjax(data);
            };
// ---------------------------------------------------------------------------------------------------------------------
            var executeSections = function(sectionId) {
                // Get the selected sections
                var sections = [];
                $('input[class="template_section"]:checked').each(function () {
                    var section = {};
                    section.id = $(this).attr('sid');
                    section.name = $(this).attr('name');
                    sections.push(section);
                });
                // Now install the sections
                var data = {};
                data.sectionid = sectionId; // The section after which...
                data.type = 'sections';
                data.payload = sections;

                callAjax(data);
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

                var execUrl = config.wwwroot + '/blocks/modlib/ajax/install_templates.php';
                $.ajax({
                    url: execUrl,
                    type: "POST",
                    data: {
                        'sectionid': data.sectionid,
                        'type': data.type,
                        'payload': data.payload
                    },
                    success: function (result) {
                        $('#modlib-modal-msg').html(result);
                        // reload the page
//                        location.reload();
                        window.location = window.location;
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
