(function () {
    document.querySelectorAll('[data-catalog-page]').forEach(function (page) {
        var grid = page.querySelector('[data-catalog-grid]');
        if (!grid) return;

        var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-catalog-card]'));
        var search = page.querySelector('[data-catalog-search]');
        var serverForm = page.querySelector('[data-catalog-server-form]');
        var tabs = page.querySelectorAll('[data-catalog-filter]');
        var extraFilters = page.querySelectorAll('[data-catalog-field-filter]');
        var emptyState = page.querySelector('[data-catalog-filter-empty]');
        var activeFilter = 'all';
        var filters = {};

        function applyFilters() {
            var query = search ? (search.value || '').trim().toLocaleLowerCase('es') : '';
            var visibleCount = 0;

            cards.forEach(function (card) {
                var stateMatches = activeFilter === 'all' || card.dataset.catalogStatus === activeFilter;
                var searchMatches = !query || (card.dataset.catalogSearch || '').toLocaleLowerCase('es').includes(query);
                var fieldsMatch = Array.prototype.every.call(extraFilters, function (control) {
                    var field = control.dataset.catalogFieldFilter;
                    return !control.value || card.dataset['catalog' + field.charAt(0).toUpperCase() + field.slice(1)] === control.value;
                });
                var visible = stateMatches && searchMatches && fieldsMatch;

                card.hidden = !visible;
                if (visible) visibleCount++;
            });

            if (emptyState) emptyState.hidden = visibleCount > 0;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeFilter = tab.dataset.catalogFilter;
                tabs.forEach(function (item) {
                    var selected = item === tab;
                    item.classList.toggle('is-active', selected);
                    item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });

                if (serverForm) {
                    var stateInput = serverForm.querySelector('[data-catalog-state]');
                    if (stateInput) stateInput.value = tab.dataset.catalogQueryValue || '';
                    serverForm.submit();
                    return;
                }

                applyFilters();
            });
        });

        if (search && serverForm) {
            var searchTimeout;
            search.addEventListener('input', function () {
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(function () {
                    serverForm.submit();
                }, 350);
            });
        } else if (search) {
            search.addEventListener('input', applyFilters);
        }
        extraFilters.forEach(function (control) {
            control.addEventListener('change', applyFilters);
        });

        page.querySelectorAll('[data-catalog-view]').forEach(function (button) {
            button.addEventListener('click', function () {
                var selectedView = button.dataset.catalogView;
                grid.classList.toggle('is-list', selectedView === 'list');
                page.querySelectorAll('[data-catalog-view]').forEach(function (item) {
                    var selected = item === button;
                    item.classList.toggle('is-active', selected);
                    item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            });
        });

        var exportToggle = page.querySelector('[data-catalog-export-toggle]');
        var exportMenu = page.querySelector('[data-catalog-export-menu]');

        function closeExportMenu() {
            if (!exportMenu || !exportToggle) return;
            exportMenu.hidden = true;
            exportToggle.setAttribute('aria-expanded', 'false');
        }

        if (exportToggle && exportMenu) {
            exportToggle.addEventListener('click', function () {
                exportMenu.hidden = !exportMenu.hidden;
                exportToggle.setAttribute('aria-expanded', exportMenu.hidden ? 'false' : 'true');
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-catalog-export-toggle]') && !event.target.closest('[data-catalog-export-menu]')) {
                    closeExportMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeExportMenu();
            });
        }

        function getVisibleRows() {
            return cards.filter(function (card) {
                return !card.hidden;
            }).map(function (card) {
                return JSON.parse(card.dataset.catalogRecord);
            });
        }

        function downloadFile(content, type, filename) {
            var blob = new Blob([content], { type: type });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(function () {
                URL.revokeObjectURL(url);
            }, 1000);
        }

        function escapeHtml(value) {
            var element = document.createElement('span');
            element.textContent = value;
            return element.innerHTML;
        }

        function spreadsheetValue(value) {
            var text = String(value || '');
            return /^[\t\r\n ]*[=+\-@]/.test(text) ? "'" + text : text;
        }

        function buildTable(headers, rows, forSpreadsheet) {
            var headerHtml = '<tr>' + headers.map(function (header) {
                return '<th>' + escapeHtml(header) + '</th>';
            }).join('') + '</tr>';
            var bodyHtml = rows.map(function (row) {
                return '<tr>' + row.map(function (value) {
                    return '<td>' + escapeHtml(forSpreadsheet ? spreadsheetValue(value) : value) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            return '<table><thead>' + headerHtml + '</thead><tbody>' + bodyHtml + '</tbody></table>';
        }

        function printRows(headers, rows) {
            var printWindow = window.open('', '_blank');
            if (!printWindow) {
                Swal.fire({
                    title: 'Ventana bloqueada',
                    text: 'Permite las ventanas emergentes para imprimir o guardar el listado como PDF.',
                    icon: 'warning',
                    confirmButtonColor: '#a6262e'
                });
                return;
            }

            printWindow.document.open();
            printWindow.onload = function () {
                printWindow.focus();
                printWindow.print();
            };
            printWindow.document.write(
                '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Listado S.R.C</title>' +
                '<style>body{font:13px Arial,sans-serif;color:#202b3b;padding:24px}h1{font-size:20px;margin:0 0 16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d5dce5;padding:7px;text-align:left}th{background:#7b1e24;color:#fff}tr:nth-child(even){background:#f5f7f9}@media print{body{padding:0}}</style>' +
                '</head><body><h1>' + escapeHtml(page.dataset.catalogTitle || 'Listado S.R.C') + '</h1>' +
                buildTable(headers, rows, false) + '</body></html>'
            );
            printWindow.document.close();
        }

        page.querySelectorAll('[data-catalog-export]').forEach(function (button) {
            button.addEventListener('click', function () {
                var rows = getVisibleRows();
                closeExportMenu();
                if (!rows.length) {
                    Swal.fire({
                        title: 'Sin registros para exportar',
                        text: 'No hay resultados que coincidan con los filtros actuales.',
                        icon: 'info',
                        confirmButtonColor: '#a6262e'
                    });
                    return;
                }

                var headers = JSON.parse(grid.dataset.catalogHeaders);
                var filename = (page.dataset.catalogFilename || 'listado-src');
                var format = button.dataset.catalogExport;

                if (format === 'csv') {
                    var csv = [headers].concat(rows).map(function (row) {
                        return row.map(function (value) {
                            return '"' + spreadsheetValue(value).replace(/"/g, '""') + '"';
                        }).join(',');
                    }).join('\r\n');
                    downloadFile('\uFEFF' + csv, 'text/csv;charset=utf-8', filename + '.csv');
                } else if (format === 'excel') {
                    downloadFile(
                        '<!doctype html><html><head><meta charset="utf-8"></head><body>' + buildTable(headers, rows, true) + '</body></html>',
                        'application/vnd.ms-excel;charset=utf-8',
                        filename + '.xls'
                    );
                } else {
                    printRows(headers, rows);
                }
            });
        });

        page.querySelectorAll('[data-catalog-delete]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = button.closest('form');
                Swal.fire({
                    title: button.dataset.confirmTitle || '¿Confirmar eliminación?',
                    text: button.dataset.confirmText || 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#a6262e',
                    cancelButtonColor: '#778087',
                    confirmButtonText: button.dataset.confirmButton || 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    });
})();
