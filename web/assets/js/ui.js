/**
 * Copy buttons, tabs, lightweight UI helpers — vanilla JS only.
 */
(function (global) {
    'use strict';

    function initCopyButtons(root) {
        var scope = root || document;
        scope.querySelectorAll('[data-copy-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sel = btn.getAttribute('data-copy-target');
                var el = scope.querySelector(sel);
                if (!el) return;
                var text = el.innerText || el.textContent || '';
                navigator.clipboard.writeText(text.trim()).then(function () {
                    var prev = btn.getAttribute('data-label') || btn.textContent;
                    btn.textContent = 'Copied!';
                    btn.disabled = true;
                    setTimeout(function () {
                        btn.textContent = prev;
                        btn.disabled = false;
                    }, 2000);
                });
            });
        });
    }

    function initTabs(container) {
        if (!container) return;
        var strip = container.querySelector('.tab-strip');
        if (!strip) return;
        var buttons = strip.querySelectorAll('[data-tab]');
        var panels = container.querySelectorAll(':scope > [data-tab-panel]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-tab');
                buttons.forEach(function (b) {
                    b.classList.remove('border-isekai-accent', 'text-white', 'bg-isekai-card');
                    b.classList.add('border-transparent', 'text-isekai-muted');
                });
                btn.classList.add('border-isekai-accent', 'text-white', 'bg-isekai-card');
                btn.classList.remove('border-transparent', 'text-isekai-muted');
                panels.forEach(function (p) {
                    var show = p.getAttribute('data-tab-panel') === id;
                    p.classList.toggle('hidden', !show);
                });
            });
        });
    }

    global.IsekaiUI = {
        initCopyButtons: initCopyButtons,
        initTabs: initTabs,
    };
})(typeof window !== 'undefined' ? window : this);
