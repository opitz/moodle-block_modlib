define(['jquery', 'core/config', 'core/str', 'core/modal_factory', 'core/modal_events'], function ($, config, str, ModalFactory) {
    return {
        init: function () {

// ---------------------------------------------------------------------------------------------------------------------
            var execute = function () {
                $(".modlib-sections .dropdown-item").on('click', function () {
                    // Get the selected modules
                    if ($('input[class="module"]:checked').length === 0) {
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
                        var count = $('input[class="module"]:checked').length;
                        $('input[class="module"]:checked').each(function () {
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
                                    alert(e);
                                }
                            });
                        });
                    }
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function () {
                // Load all required functions above

                execute();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function () {
//                console.log('=================< modlib/install_module >=================');
                initFunctions();
                $('.module_row').on('click', function () {
                    if ($('input[class="module"]:checked').length !== 0) {
                        $('#target_topic_btn').removeClass('disabled');
                    } else {
                        $('#target_topic_btn').addClass('disabled');
                    }
                });

                $('#modlib-spinner-modal').hide();
                $('tr.module').css('cursor', 'pointer');
            });
        }
    };
})
