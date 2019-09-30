define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events'], function($, str, ModalFactory, ModalEvents) {
    return {
        init: function() {

// ---------------------------------------------------------------------------------------------------------------------
            var execute = function() {
                $(".modlib-sections .dropdown-item").on('click', function() {
                    // Get the selected modules
                    if ($('input[class="module"]:checked').length === 0) {
                        ModalFactory.create({
                            //type: ModalFactory.types.SAVE_CANCEL,
                            type: ModalFactory.types.CANCEL,
                            title: 'No Module selected',
                            body: 'Please select at least one module to install.',
                        })
                            .then(function(modal) {
                                modal.show();
                            });
                    } else {
                        $('.modlib-modal').show();
                        var sectionId =  $(this).attr('value');
                        var count = $('input[class="module"]:checked').length;
                        $('input[class="module"]:checked').each(function() {
                            var module = {};
                            module.id = $(this).val();
                            module.cmid = $(this).attr('cmid');
                            module.name = $(this).attr('name');
                            module.type = $(this).attr('module_type');

                            // Now install the module
                            var pathUrl = window.location.pathname;
                            var baseUrl = pathUrl.split('/');
                            baseUrl.shift();
                            var execUrl = window.location.protocol + "//" + window.location.host + "/" + baseUrl.shift() +
                                '/blocks/modlib/execute.php';

                            baseUrl =
                                $.ajax({
                                    url: execUrl,
                                    type: "POST",
                                    data: {'sectionid': sectionId, 'cmid': module.cmid, 'moduleid': module.id, 'type': module.type},
                                    success: function(result) {
                                        if(result !== '') {
                                            $('#modlib-modal-msg').html(result);
                                        } else {
                                            console.warn('Unsupported module type for installation: ' + module.type + '!\n');
                                        }
                                        if (! --count) { // once all
                                            $('.modlib-modal').hide();
                                            location.reload();
                                        }
//                                    window.location = returnurl;
                                    },
                                    error: function(e) {
                                        $('.modlib-modal').hide();
                                        console.error(e);
                                    }
                                });
                        });
                    }
                });
            };

            // the same command but with console logorrhea
            var execute_w_console = function() {
                $(".modlib-sections .dropdown-item").on('click', function() {

                    // Get the course ID
                    var courseid = $('#courseid').val();
//                    var returnurl = window.location.href;
                    var command = $(this).attr('value');
//                    var confirm = $(this).attr('confirm_txt');

                    console.log('course ID = ' + courseid);
                    console.log('command = ' + command);
//                    console.log('confirmation = ' + confirm);

                    // Get the selected modules
                    if ($('input[class="module"]:checked').length === 0) {
                        ModalFactory.create({
                            //type: ModalFactory.types.SAVE_CANCEL,
                            type: ModalFactory.types.CANCEL,
                            title: 'No Module selected',
                            body: 'Please select at least one module to install.',
                        })
                            .then(function(modal) {
                                modal.show();
                            });
                    } else {
                        $('.modlib-modal').show();
//                        $('#test_area').show();
//                        alert('now showing spinner');
//                        debugger;
                        var sectionId =  $(this).attr('value');
                        var sectionName = $(this).html();
                        console.log('Install into section ' + sectionId + ' -> ' + sectionName);
                        var count = $('input[class="module"]:checked').length;
                        $('input[class="module"]:checked').each(function() {
                            var module = {};
                            module.id = $(this).val();
                            module.cmid = $(this).attr('cmid');
                            module.name = $(this).attr('name');
                            module.type = $(this).attr('module_type');

                            console.log('=> checked module "' + module.name + '" (ID ' + module.id + ') type = ' + module.type);

                            console.log(window.location.host);
                            console.log(window.location.pathname);

                            // Now install the module
                            var pathUrl = window.location.pathname;
                            var baseUrl = pathUrl.split('/');
                            console.log('-----');
                            baseUrl.shift();
                            var execUrl = window.location.protocol + "//" + window.location.host + "/" + baseUrl.shift() +
                                '/blocks/modlib/execute.php';
                            console.log(execUrl);
                            console.log('-----');

                            baseUrl =
                                $.ajax({
                                    url: execUrl,
                                    type: "POST",
                                    data: {'sectionid': sectionId, 'cmid': module.cmid, 'moduleid': module.id, 'type': module.type},
                                    success: function(result) {
                                        if(result !== '') {
                                            console.log('Execution result:\n' + result);
                                            $('#modlib-modal-msg').html(result);
                                        } else {
                                            console.warn('Unsupported module type for installation: ' + module.type + '!\n');
                                        }
                                        if (! --count) { // once all
                                            $('.modlib-modal').hide();
                                            location.reload();
                                        }
//                                    window.location = returnurl;
                                    },
                                    error: function(e) {
                                        $('.modlib-modal').hide();
                                        console.error(e);
                                    }
                                });
                        });
//                        alert('hiding spinner');
//                        debugger;
//                        $('#test_area').hide();
//                        $('.modlib-modal').hide();
                    }
                });
            };


// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above

                execute();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function() {
                console.log('=================< modlib/install_module >=================');
                initFunctions();

                $('.module_row').on('click', function() {
                    if ($('input[class="module"]:checked').length !== 0) {
                        $('#target_topic_btn').removeClass('disabled');
                    } else {
                        $('#target_topic_btn').addClass('disabled');
                    }
                });

                $('#modlib-spinner-modal').hide();
                $('tr.module').css('cursor','pointer');
            });
        }
    };
});
