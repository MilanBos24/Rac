<?php /* RAC sidebar persistence 2026-10-12 */ ?>
<script>
(function () {
    document.documentElement.classList.add('modern', 'has-top-menu', 'has-left-sidebar-half');
    if (window.matchMedia('(min-width: 768px)').matches) {
        var sidebarState = null;
        try { sidebarState = localStorage.getItem('rac-sidebar-left-state'); } catch (error) {}
        if (sidebarState === 'collapsed') document.documentElement.classList.add('sidebar-left-collapsed');
        else document.documentElement.classList.remove('sidebar-left-collapsed');
    }
})();
</script>
<script src="/administrace/vendor/modernizr/modernizr.js"></script>
<script src="/administrace/master/style-switcher/style.switcher.localstorage.js"></script>
