define(['jquery', 'core/config', 'core/str', 'core/modal_factory', 'core/modal_events'], function ($, config, str, ModalFactory) {
    return {
        init: function () {

// ---------------------------------------------------------------------------------------------------------------------
            var sectionModules = function() {

                $('.template_section').change(function() {
                    var status = $(this).find('input').is(':checked');
                    var sectionId = $(this).attr('sectionid');
                    $('.template_module[sid = "' + sectionId + '"]').prop('checked', status);
//                    $('.template_module[sid = "' + sectionId + '"]').click();

                    if ($('input:checked').length !== 0) {
                        $('#target_topic_btn').hide();
                        $('#after_topic_btn').show();
                        $('#after_topic_btn').removeClass('disabled');
                        $('input.template_module').attr('disabled','');
                    } else {
                        $('#target_topic_btn').show();
                        $('#after_topic_btn').hide();
                        $('#after_topic_btn').addClass('disabled');
                        $('input.template_module').removeAttr('disabled');
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
            var executeModules = function () {
                $(".modlib-sections .dropdown-item").on('click', function () {
//                    alert('heda!');
                    // Get the selected modules
                    if ($('input[class="template_module"]:checked').length === 0) {
                        ModalFactory.create({
                            //type: ModalFactory.types.SAVE_CANCEL,
                            type: ModalFactory.types.CANCEL,
                            title: 'No Module selected',
                            body: 'Please select at least one module to install.',
                        })
                            .then(function (modal) {
                                modal.show();
                            });
                    } else {
                        $('.modlib-modal').show();
                        var sectionId = $(this).attr('value');
                        var count = $('input[class="template_module"]:checked').length;
                        $('input[class="template_module"]:checked').each(function () {
                            var module = {};
                            module.id = $(this).val();
                            module.cmid = $(this).attr('cmid');
                            module.name = $(this).attr('name');
                            module.type = $(this).attr('module_type');

                            // Now install the module
                            var execUrl = config.wwwroot + '/blocks/modlib/execute.php';
                            $.ajax({
                                url: execUrl,
                                type: "POST",
                                data: {
                                    'sectionid': sectionId,
                                    'cmid': module.cmid,
                                    'moduleid': module.id,
                                    'type': module.type
                                },
                                success: function (result) {
                                    $('#modlib-modal-msg').html(result);
                                    if (!--count) { // once all
//                                            $('.modlib-modal').hide();
                                        location.reload();
                                    }
                                },
                                error: function (e) {
                                    $('.modlib-modal').hide();
                                    console.error(e);
                                }
                            });
                        });
                    }
                });
            };

            var execute = function () {
                $(".modlib-sections .dropdown-item").on('click', function () {
                    // get any selected sections


                    // Get the selected sections
                    if ($('input[class="template_section"]:checked').length === 0) {
                        // No sections have been selected - on to single modules
                        executeModules();
                    } else {
                        $('.modlib-modal').show(); // Show the graphical interlude...
                        var targetSectionId = $(this).attr('value');
                        var count = $('input[class="template_section"]:checked').length;
                        $('input[class="template_section"]:checked').each(function () {
                            var section = {};
                            section.id = $(this).attr('sid');
                            section.name = $(this).attr('name');

                            // Now install the module
                            var execUrl = config.wwwroot + '/blocks/modlib/execute.php';
                            $.ajax({
                                url: execUrl,
                                type: "POST",
                                data: {
                                    'sectionid': targetSectionId,
                                    'cmid': section.id,
                                    'type': 'section'
                                },
                                success: function (result) {
                                    $('#modlib-modal-msg').html(result);
                                    if (!--count) { // once all
//                                            $('.modlib-modal').hide();
                                        location.reload();
                                    }
                                },
                                error: function (e) {
                                    $('.modlib-modal').hide();
                                    console.error(e);
                                }
                            });
                        });
                    }
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function () {
                // Load all required functions above

                sectionModules();
                execute();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function () {
//                console.log('=================< modlib/install_module >=================');
                initFunctions();

                $('#modlib-spinner-modal').hide();
                $('tr.module').css('cursor', 'pointer');
            });
        }
    };
})
