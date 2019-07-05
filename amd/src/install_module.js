define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events'], function($, str, ModalFactory, ModalEvents) {
    return {
        init: function() {

// ---------------------------------------------------------------------------------------------------------------------
            var execute = function() {
                $(".modlib-sections .dropdown-item").on('click', function() {

                    // Get the course ID
                    var courseid = $('#courseid').val();
                    var returnurl = window.location.href;
                    var command = $(this).attr('value');
//                    var confirm = $(this).attr('confirm_txt');

                    console.log('course ID = ' + courseid);
                    console.log('return URL = ' + returnurl);
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
                        var sectionId =  $(this).attr('value');
                        var sectionName = $(this).html();
                        console.log('Install into section ' + sectionId + ' -> ' + sectionName);
                        $('input[class="module"]:checked').each(function() {
                            var module = {};
                            module.id = $(this).val();
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
                            execUrl = window.location.protocol + "//" + window.location.host + "/" + baseUrl.shift() +
                                '/blocks/modlib/execute.php';
                            console.log(execUrl);
                            console.log('-----');

                            baseUrl =
                            $.ajax({
                                url: execUrl,
                                type: "POST",
                                data: {'sectionid': sectionId, 'moduleid': module.id, 'type': module.type},
                                success: function(result) {
                                    if(result !== '') {
                                        console.log('Execution result:\n' + result);
                                    } else {
                                        console.warn('Unsupported module type for installation: ' + module.type + '!\n');
                                    }
                                    window.location = returnurl;
                                },
                                error: function(e) {
                                    console.error(e);
                                }
                            });

                        });
                    }
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var test = function() {
                $('.test').on('click', function() {
                    console.log('test!');
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above

                execute();
                test();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function() {
                console.log('=================< modlib/install_module >=================');
                initFunctions();
                $('tr.module').css('cursor','pointer');
            });
        }
    };
});
