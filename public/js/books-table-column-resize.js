/**
 * Drag-to-resize columns for .books-looker-table (e.g. /books, /booths).
 * Always normalizes column widths so the table fits 100% inside .table-view.
 * Persists relative widths in localStorage. Default key: booksTableColumnWidths_v3.
 * Set data-books-column-resize-key="booths" (or any id) on the table for a separate key.
 * Double-click any resize handle to reset column widths to default.
 */
(function () {
    'use strict';

    var DEFAULT_STORAGE_KEY = 'booksTableColumnWidths_v3';
    var MIN_W = 32;

    function getStorageKey(table) {
        if (!table || !table.getAttribute) {
            return DEFAULT_STORAGE_KEY;
        }
        var k = table.getAttribute('data-books-column-resize-key');
        if (k) {
            return 'booksLookerTableColumnWidths_' + k + '_v3';
        }
        return DEFAULT_STORAGE_KEY;
    }

    function clamp(n, a, b) {
        return Math.max(a, Math.min(b, n));
    }

    function applyColumnWidth(table, colIndex, widthPx) {
        var tableWidth = table.getBoundingClientRect().width || table.clientWidth || 1000;
        var w = clamp(Math.round(widthPx), MIN_W, tableWidth * 0.45);
        var ths = table.querySelectorAll('thead tr th');
        var th = ths[colIndex];
        if (!th) return;
        var pct = ((w / tableWidth) * 100).toFixed(2) + '%';
        th.style.width = pct;
        th.style.minWidth = '0';
    }

    function applySavedWidths(table) {
        try {
            var raw = localStorage.getItem(getStorageKey(table));
            if (!raw) return;
            var widths = JSON.parse(raw);
            if (!Array.isArray(widths)) return;
            var ths = table.querySelectorAll('thead tr th');
            if (widths.length !== ths.length) return;

            var sum = 0;
            for (var k = 0; k < widths.length; k++) {
                sum += (typeof widths[k] === 'number' && widths[k] > 0 ? widths[k] : 100);
            }
            if (sum <= 0) return;

            for (var i = 0; i < ths.length; i++) {
                if (typeof widths[i] === 'number' && widths[i] > 0) {
                    var pct = ((widths[i] / sum) * 100).toFixed(2) + '%';
                    ths[i].style.width = pct;
                    ths[i].style.maxWidth = pct;
                    ths[i].style.minWidth = '0';
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
            applySavedWidths(table);
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
            grip.title = 'Drag to resize · Double-click to reset';

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

            grip.addEventListener('dblclick', function (e) {
                e.preventDefault();
                e.stopPropagation();
                try {
                    localStorage.removeItem(getStorageKey(table));
                } catch (err) {}
                ths.forEach(function (cell) {
                    cell.style.width = '';
                    cell.style.minWidth = '';
                    cell.style.maxWidth = '';
                });
            });

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
