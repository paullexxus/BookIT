// sidebar-toggle.js
// Injects a small toggle button and adds handlers to toggle collapsed state
(function(){
    function createToggle() {
        var btn = document.createElement('button');
        btn.className = 'sidebar-toggle-btn';
        btn.setAttribute('aria-label','Toggle sidebar');
        // Prefer a local asset in assets/images/menu.svg so icons stay in-project
        // Compute base URL from the current script src so it works when app is in a subfolder
        var script = document.currentScript || (function(){
            var s = document.getElementsByTagName('script');
            return s[s.length - 1];
        })();
        var scriptSrc = script && script.src ? script.src : '';
        var base = scriptSrc.replace(/\/assets\/js\/sidebar-toggle(\.min)?\.js(\?.*)?$/i, '').replace(/\/+$/,'');
        var localPath = (base ? base : '') + '/assets/images/menu.svg';
        var img = document.createElement('img');
        img.src = localPath;
        img.alt = 'menu';
        img.style.width = '20px';
        img.style.height = '20px';

        // Use image if it loads; otherwise fallback to inline svg
        img.addEventListener('error', function(){
            btn.innerHTML = '<svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="18" height="2" rx="1" fill="currentColor"/><rect y="6" width="18" height="2" rx="1" fill="currentColor"/><rect y="12" width="18" height="2" rx="1" fill="currentColor"/></svg>';
        });

        btn.appendChild(img);
        return btn;
    }

    function init() {
        if (!document.body) return;

        var sidebars = document.querySelectorAll('.sidebar');
        if (!sidebars || sidebars.length === 0) return;

        var btn = createToggle();
        document.body.appendChild(btn);

        // Default collapsed on medium screens (match CSS breakpoint)
        var collapsed = window.matchMedia('(max-width: 1200px)').matches;

        sidebars.forEach(function(sb){
            if (collapsed) {
                sb.classList.add('sidebar-collapsed');
            }
        });

        function toggleAll() {
            // On small screens, we prefer sliding open; on larger, toggle collapsed
            if (window.matchMedia('(max-width: 768px)').matches) {
                // toggle body class to slide
                document.body.classList.toggle('sidebar-open');
            } else {
                sidebars.forEach(function(sb){
                    sb.classList.toggle('sidebar-collapsed');
                });
            }
        }

        btn.addEventListener('click', function(e){
            e.preventDefault();
            toggleAll();
        });

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(e){
            if (!window.matchMedia('(max-width: 768px)').matches) return;
            if (!document.body.classList.contains('sidebar-open')) return;
            var target = e.target;
            var inside = false;
            sidebars.forEach(function(sb){ if (sb.contains(target)) inside = true; });
            if (!inside && !btn.contains(target)) {
                document.body.classList.remove('sidebar-open');
            }
        });

        // Keep behavior responsive when resizing
        var mm = window.matchMedia('(max-width: 1200px)');
        mm.addListener(function(e){
            if (e.matches) {
                sidebars.forEach(function(sb){ sb.classList.add('sidebar-collapsed'); });
            } else {
                sidebars.forEach(function(sb){ sb.classList.remove('sidebar-collapsed'); });
                document.body.classList.remove('sidebar-open');
            }
        });
    }

    // Init on DOMContentLoaded or immediately if ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
