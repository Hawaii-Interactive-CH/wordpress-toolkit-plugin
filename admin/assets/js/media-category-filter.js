/**
 * Media Category Filter
 * 
 * Adds category filtering to the WordPress media library grid view
 */
(function($) {
    'use strict';
    
    // Main initialization function
    $(function() {
        // Make sure we have the required WordPress objects
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            return;
        }
        
        // Custom event binding for when the media modal loads
        $(document).on('DOMNodeInserted', '.media-modal', function() {
            setTimeout(function() {
                // Refresh the filter when switching views
                if (wp.media.frame && wp.media.frame.content && wp.media.frame.content.get()) {
                    wp.media.frame.content.get().collection.props.on('change:type', function() {
                        wp.media.frame.trigger('content:activate:browse');
                    });
                }
            }, 100);
        });
    });
})(jQuery); 