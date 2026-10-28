import './bootstrap';
import $ from 'jquery';
import DataTable from 'datatables.net';
import 'datatables.net-responsive';

window.$ = window.jQuery = $;

const boot = () => {
    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        const card = table.closest('[data-table-card]') ?? document;
        const searchInput = card.querySelector('[data-table-search]');
        const emptyRow = table.querySelector('tbody tr td[colspan]');

        if (emptyRow) {
            return;
        }

        const dataTable = new DataTable(table, {
            responsive: true,
            autoWidth: false,
            pageLength: Number(table.dataset.pageLength ?? 10),
            lengthMenu: [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, 'Semua'],
            ],
            order: [],
            columnDefs: [
                {
                    targets: 'no-sort',
                    orderable: false,
                    searchable: false,
                },
            ],
            language: {
                emptyTable: 'Belum ada data yang tersedia.',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                lengthMenu: '_MENU_ data per halaman',
                loadingRecords: 'Memuat data…',
                processing: 'Memproses…',
                zeroRecords: 'Data yang Anda cari tidak ditemukan.',
                search: 'Cari:',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Berikutnya',
                    previous: 'Sebelumnya',
                },
                aria: {
                    sortAscending: ': aktifkan untuk mengurutkan naik',
                    sortDescending: ': aktifkan untuk mengurutkan turun',
                },
            },
        });

        const container = table.closest('.dt-container');

        const syncResponsiveControl = () => {
            const hasHidden = dataTable.responsive?.hasHidden?.() ?? true;
            container?.classList.toggle('dt-has-hidden', Boolean(hasHidden));
        };

        dataTable.on('responsive-resize', syncResponsiveControl);
        syncResponsiveControl();

        if (searchInput) {
            searchInput.addEventListener('input', (event) => {
                dataTable.search(event.target.value).draw();
            });

            const clearButton = card.querySelector('[data-table-search-clear]');

            const syncClearButton = () => {
                if (!clearButton) {
                    return;
                }

                clearButton.classList.toggle('hidden', searchInput.value.length === 0);
            };

            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    searchInput.value = '';
                    dataTable.search('').draw();
                    searchInput.focus();
                    syncClearButton();
                });
            }

            searchInput.addEventListener('input', syncClearButton);
            syncClearButton();
        }

        const statusFilter = card.querySelector('[data-table-filter]');

        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                const columnIndex = Number(statusFilter.dataset.column);

                if (Number.isNaN(columnIndex)) {
                    return;
                }

                dataTable.column(columnIndex).search(statusFilter.value).draw();
            });
        }
    });

    const passwordToggles = document.querySelectorAll('[data-password-toggle]');

    passwordToggles.forEach((button) => {
        const target = document.querySelector(button.dataset.passwordToggle);

        if (!target) {
            return;
        }

        const eyeOpen = button.querySelector('[data-eye-open]');
        const eyeClosed = button.querySelector('[data-eye-off]');

        button.addEventListener('click', () => {
            const show = target.type === 'password';

            target.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute(
                'aria-label',
                show ? 'Sembunyikan password' : 'Tampilkan password',
            );
            eyeOpen?.classList.toggle('hidden', show);
            eyeClosed?.classList.toggle('hidden', !show);
            target.focus({ preventScroll: true });
        });
    });

    document.querySelectorAll('[data-fill-nidn]').forEach((button) => {
        button.addEventListener('click', () => {
            const nidnInput = document.querySelector('#nidn');
            const passwordInput = document.querySelector('#password');

            if (nidnInput) {
                nidnInput.value = button.dataset.fillNidn ?? '';
                nidnInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (passwordInput) {
                passwordInput.value = button.dataset.fillPassword ?? 'password';
                passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            nidnInput?.focus();
        });
    });

    const capsWarning = document.querySelector('[data-caps-warning]');
    const passwordField = document.querySelector('#password');

    if (capsWarning && passwordField) {
        const updateCapsWarning = (event) => {
            const isCapsOn = event.getModifierState?.('CapsLock') ?? false;
            capsWarning.classList.toggle('hidden', !isCapsOn);
        };

        passwordField.addEventListener('keydown', updateCapsWarning);
        passwordField.addEventListener('keyup', updateCapsWarning);
        passwordField.addEventListener('blur', () => capsWarning.classList.add('hidden'));
    }

    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        sidebarOverlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const isClosed = sidebar?.classList.contains('-translate-x-full');

            sidebar?.classList.toggle('-translate-x-full', !isClosed);
            sidebarOverlay?.classList.toggle('hidden', !isClosed);
            document.body.classList.toggle('overflow-hidden', Boolean(isClosed));
        });
    });

    sidebarOverlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-user-menu-button]').forEach((button) => {
        const menu = button.parentElement?.querySelector('[data-user-menu]');

        if (!menu) {
            return;
        }

        const closeMenu = () => {
            menu.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        };

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const isHidden = menu.classList.contains('hidden');

            menu.classList.toggle('hidden', !isHidden);
            button.setAttribute('aria-expanded', String(isHidden));
        });

        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target) && !button.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
