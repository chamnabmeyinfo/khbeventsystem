/**
 * Drag-to-resize columns for .books-looker-table (e.g. /books, /booths).
 * Persists widths in localStorage. Default key: booksTableColumnWidths_v1.
 * Set data-books-column-resize-key="booths" (or any id) on the table for a separate key.
 */
(function () {
    'use strict';

    var DEFAULT_STORAGE_KEY = 'booksTableColumnWidths_v2';
    var MIN_W = 48;
    var MAX_W = 640;

    function getStorageKey(table) {
        if (!table || !table.getAttribute) {
            return DEFAULT_STORAGE_KEY;
        }
        var k = table.getAttribute('data-books-column-resize-key');
        if (k) {
            return 'booksLookerTableColumnWidths_' + k + '_v2';
        }
        return DEFAULT_STORAGE_KEY;
    }

    function clamp(n, a, b) {
        return Math.max(a, Math.min(b, n));
    }

    function applyColumnWidth(table, colIndex, widthPx) {
        var w = clamp(Math.round(widthPx), MIN_W, MAX_W) + 'px';
        var ths = table.querySelectorAll('thead tr th');
        var th = ths[colIndex];
        if (!th) return;
        th.style.width = w;
        th.style.minWidth = w;
        th.style.maxWidth = w;
        /* table-layout: fixed — column width applies to all rows including lazy-loaded tbody */
    }

    function applySavedWidths(table) {
        try {
            var raw = localStorage.getItem(getStorageKey(table));
            if (!raw) return;
            var widths = JSON.parse(raw);
            if (!Array.isArray(widths)) return;
            var n = table.querySelectorAll('thead tr th').length;
            if (widths.length !== n) return;
            for (var i = 0; i < n && i < widths.length; i++) {
                if (typeof widths[i] === 'number' && widths[i] > 0) {
                    applyColumnWidth(table, i, widths[i]);
                }
            }
        } catch (e) {}
    }

    function saveWidths(table) {
        var ths = table.querySelectorAll('thead tr th');
        var out = [];
        for (var i = 0; i < ths.length; i++) {
            out.push(ths[i].getBoundingClientRect().width);
        }
        try {
            localStorage.setItem(getStorageKey(table), JSON.stringify(out));
        } catch (e) {}
    }

    function pageXFromEvent(e) {
        if (e.pageX != null) return e.pageX;
        if (e.touches && e.touches[0]) return e.touches[0].pageX;
        if (e.changedTouches && e.changedTouches[0]) return e.changedTouches[0].pageX;
        return 0;
    }

    function beginResize(table, colIndex, th, startPageX) {
        var startW = th.getBoundingClientRect().width;

        function onMove(e) {
            var x = pageXFromEvent(e);
            var dx = x - startPageX;
            applyColumnWidth(table, colIndex, startW + dx);
        }

        function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onUp);
            document.removeEventListener('touchcancel', onUp);
            document.body.classList.remove('books-table-resizing');
            saveWidths(table);
        }

        document.body.classList.add('books-table-resizing');
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
        document.addEventListener('touchmove', onMove, { passive: false });
        document.addEventListener('touchend', onUp);
        document.addEventListener('touchcancel', onUp);
    }

    function initBooksTableColumnResize(table) {
        if (!table || table.tagName !== 'TABLE' || table.dataset.booksColumnResizeInit === '1') {
            return;
        }
        table.dataset.booksColumnResizeInit = '1';

        var theadRow = table.querySelector('thead tr');
        if (!theadRow) return;

        var ths = theadRow.querySelectorAll('th');
        if (!ths.length) return;

        applySavedWidths(table);

        ths.forEach(function (th, colIndex) {
            if (th.querySelector('.books-col-resize-handle')) return;

            th.style.position = 'relative';

            var grip = document.createElement('span');
            grip.className = 'books-col-resize-handle';
            grip.setAttribute('aria-hidden', 'true');
            grip.title = 'Drag to resize column';

            grip.addEventListener('mousedown', function (e) {
                e.preventDefault();
                e.stopPropagation();
                beginResize(table, colIndex, th, e.pageX);
            });

            grip.addEventListener('touchstart', function (e) {
                e.stopPropagation();
                if (!e.touches || !e.touches[0]) return;
                e.preventDefault();
                beginResize(table, colIndex, th, e.touches[0].pageX);
            }, { passive: false });

            th.appendChild(grip);
        });
    }

    function boot() {
        document.querySelectorAll('table.books-looker-table').forEach(initBooksTableColumnResize);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.initBooksTableColumnResize = initBooksTableColumnResize;
})();
