<?php /* RAC sidebar persistence 2026-10-12 */ ?>
<!-- BOS24 original script load sequence -->
<script src="/administrace/vendor/jquery/jquery.js"></script>
<script src="/administrace/vendor/jquery-browser-mobile/jquery.browser.mobile.js"></script>
<script src="/administrace/vendor/jquery-cookie/jquery.cookie.js"></script>
<script src="/administrace/master/style-switcher/style.switcher.js"></script>
<script src="/administrace/vendor/popper/umd/popper.min.js"></script>
<script src="/administrace/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/administrace/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
<script src="/administrace/vendor/common/common.js"></script>
<script src="/administrace/vendor/nanoscroller/nanoscroller.js"></script>
<script src="/administrace/vendor/magnific-popup/jquery.magnific-popup.js"></script>
<script src="/administrace/vendor/jquery-placeholder/jquery.placeholder.js"></script>
<script src="/administrace/vendor/autosize/autosize.js"></script>
<script src="/administrace/js/theme.js"></script>
<script src="/administrace/js/custom.js"></script>
<script src="/administrace/js/theme.init.js"></script>

<!-- RAC: Zachování stavu levého menu mezi stránkami; výchozí stav otevřený. -->
<script>
(function () {
    'use strict';
    var key = 'rac-sidebar-left-state';
    var root = document.documentElement;
    var desktop = function () { return window.matchMedia('(min-width: 768px)').matches; };
    function getStored() {
        try { return window.localStorage.getItem(key); } catch (err) { return null; }
    }
    function save(state) {
        try { window.localStorage.setItem(key, state); } catch (err) {}
    }
    function applyStored() {
        if (!desktop()) return;
        // První návštěva: otevřeno. Při další návštěvě respektovat poslední volbu.
        root.classList.toggle('sidebar-left-collapsed', getStored() === 'collapsed');
    }
    applyStored();
    // Porto se stará o vlastní animaci a sbalení; stav uložit až po jeho klikací obsluze.
    document.addEventListener('click', function (event) {
        var target = event.target;
        var toggle = target && target.closest ? target.closest('#sidebar-left .sidebar-toggle') : null;
        if (!toggle || !desktop()) return;
        window.setTimeout(function () {
            save(root.classList.contains('sidebar-left-collapsed') ? 'collapsed' : 'expanded');
        }, 0);
    });
    document.addEventListener('keydown', function (event) {
        var target = event.target;
        if (!target || !target.closest || !target.closest('#sidebar-left .sidebar-toggle')) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            target.closest('#sidebar-left .sidebar-toggle').click();
        }
    });
    window.addEventListener('pageshow', applyStored);
})();
</script>
