/**
 * AgentPress Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Clipboard Copy Button Handler
        $('.agentpress-copy-btn').on('click', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var $target = $('#' + targetId);

            if ($target.length) {
                $target.select();
                navigator.clipboard.writeText($target.val()).then(function() {
                    var $btn = $(e.currentTarget);
                    var originalText = $btn.text();
                    $btn.text('Copied! ✓').addClass('button-primary');

                    setTimeout(function() {
                        $btn.text(originalText).removeClass('button-primary');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Failed to copy text: ', err);
                });
            }
        });
    });
})(jQuery);
