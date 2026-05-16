/*
 * Application
 */
(function($) {
    "use strict";

    /*-------------------------------
    COLOUR MODE SWITCHER
    ---------------------------------*/
    var el = document.documentElement;
    var baseTheme = el.dataset.colorTheme || 'default';

    function applyColorMode(pref) {
        var isDark;
        if (pref === 'dark')       isDark = true;
        else if (pref === 'light') isDark = false;
        else                       isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (baseTheme && baseTheme !== 'default') {
            el.setAttribute('data-theme', isDark ? baseTheme + '-dark' : baseTheme);
        } else {
            if (pref === 'auto') el.removeAttribute('data-theme');
            else el.setAttribute('data-theme', pref);
        }

        localStorage.setItem('saga-color-mode', pref);
        updateSwitcherState(pref);
    }

    function updateSwitcherState(pref) {
        document.querySelectorAll('.color-mode-switcher__btn').forEach(function (btn) {
            var active = btn.dataset.mode === pref;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    // Set initial active state on page load
    updateSwitcherState(localStorage.getItem('saga-color-mode') || 'auto');

    // Handle switcher button clicks
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.color-mode-switcher__btn');
        if (btn) applyColorMode(btn.dataset.mode);
    });

    jQuery(document).ready(function($) {
        /*-------------------------------
        WINTER CMS FLASH MESSAGE HANDLING
        ---------------------------------*/
        $(document).on('ajaxSetup', function(event, context) {
            // Enable AJAX handling of Flash messages on all AJAX requests
            context.options.flash = true;

            // Enable the StripeLoadIndicator on all AJAX requests
            context.options.loading = $.oc.stripeLoadIndicator;

            // Handle Flash Messages
            context.options.handleFlashMessage = function(message, type) {
                $.oc.flashMsg({ text: message, class: type });
            };

            // Handle Error Messages
            context.options.handleErrorMessage = function(message) {
                $.oc.flashMsg({ text: message, class: 'error' });
            };
        });
    });
}(jQuery));

if (typeof(gtag) !== 'function') {
    gtag = function() { console.log('GoogleAnalytics not present.'); }
}
