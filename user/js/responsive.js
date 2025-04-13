/**
 * HMAMAT - Holy Week Spiritual Tracker
 * Responsive navigation script
 */

$(document).ready(function() {
    // Mobile menu toggle
    $(".menu-toggle").click(function() {
        $(".nav").toggleClass("active");
    });

    // Close menu when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.header').length) {
            $(".nav").removeClass("active");
        }
    });

    // Prevent closing when clicking inside the menu
    $(".menu-toggle, .nav").click(function(e) {
        e.stopPropagation();
    });
    
    // Close menu when window is resized
    $(window).resize(function() {
        if ($(window).width() > 768) {
            $(".nav").removeClass("active");
        }
    });
}); 