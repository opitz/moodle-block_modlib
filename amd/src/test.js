define(['jquery', 'core/modal_factory', 'core/modal_events'], function($, ModalFactory, ModalEvents) {
    return {
        init: function() {

// ---------------------------------------------------------------------------------------------------------------------
            var test = function() {
                $('tr.module').on('click', function() {
                    console.log('test!');
                });
            };

// ---------------------------------------------------------------------------------------------------------------------
            var initFunctions = function() {
                // Load all required functions above

                test();
            };

// _____________________________________________________________________________________________________________________
            $(document).ready(function() {
                console.log('=================< modlib/test >=================');
                initFunctions();
                $('tr.module').css('cursor','pointer');
            });
        }
    };
});
