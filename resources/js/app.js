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

    const maritalInputs = document.querySelectorAll('[data-marital-status]');
    const marriedSections = document.querySelectorAll('[data-married-section]');
    const spouseOccupation = document.querySelector('[data-spouse-occupation]');
    const nipWrapper = document.querySelector('[data-nip-wrapper]');

    const syncMarriedSection = () => {
        const selected = document.querySelector('[data-marital-status]:checked');
        const isMarried = selected?.value === 'menikah';

        marriedSections.forEach((section) => {
            section.classList.toggle('hidden', !isMarried);
        });
    };

    if (maritalInputs.length > 0) {
        maritalInputs.forEach((input) => {
            input.addEventListener('change', syncMarriedSection);
        });
        syncMarriedSection();
    }

    const syncNipField = () => {
        if (!nipWrapper) {
            return;
        }

        nipWrapper.classList.toggle('hidden', spouseOccupation?.value !== 'asn');
    };

    if (spouseOccupation) {
        spouseOccupation.addEventListener('change', syncNipField);
        syncNipField();
    }

    const anakContainer = document.querySelector('[data-anak-container]');
    const anakTemplate = document.querySelector('[data-anak-template]');
    const anakEmptyState = document.querySelector('[data-anak-empty]');
    let nextAnakIndex = Number(anakContainer?.dataset.nextIndex ?? 0);

    const syncAnakState = () => {
        if (!anakContainer) {
            return;
        }

        const rows = anakContainer.querySelectorAll('[data-anak-row]');

        rows.forEach((row, index) => {
            const number = row.querySelector('[data-anak-number]');

            if (number) {
                number.textContent = index + 1;
            }
        });

        anakEmptyState?.classList.toggle('hidden', rows.length > 0);
    };

    document.querySelector('[data-add-anak]')?.addEventListener('click', () => {
        if (!anakContainer || !anakTemplate) {
            return;
        }

        const markup = anakTemplate.innerHTML.replaceAll('__INDEX__', String(nextAnakIndex));
        nextAnakIndex += 1;
        anakContainer.insertAdjacentHTML('beforeend', markup);
        syncAnakState();
        anakContainer.lastElementChild?.querySelector('input, select')?.focus();
    });

    anakContainer?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-anak]');

        if (!removeButton) {
            return;
        }

        removeButton.closest('[data-anak-row]')?.remove();
        syncAnakState();
    });

    if (anakContainer) {
        syncAnakState();
    }

    const jenjangSelect = document.querySelector('[data-jenjang-select]');
    const transkripField = document.querySelector('[data-transkrip-field]');
    const transkripInput = transkripField?.querySelector('input[type="file"]');
    const jenjangPerguruanTinggi = ['d3', 'd4', 's1', 's2', 's3'];

    const syncTranskripField = () => {
        const isPerguruanTinggi = jenjangPerguruanTinggi.includes(jenjangSelect?.value);

        transkripField?.classList.toggle('hidden', !isPerguruanTinggi);

        if (transkripInput) {
            transkripInput.disabled = !isPerguruanTinggi;

            if (!isPerguruanTinggi) {
                transkripInput.value = '';
                transkripInput.setCustomValidity('');
            }
        }
    };

    if (jenjangSelect) {
        jenjangSelect.addEventListener('change', syncTranskripField);
        syncTranskripField();
    }

    document.querySelectorAll('[data-pdf-input]').forEach((input) => {
        const field = input.closest('[data-file-field]');
        const errorBox = field?.querySelector('[data-file-error]');
        const maxKb = Number(input.dataset.maxKb ?? 500);

        const setFileError = (message) => {
            if (errorBox) {
                errorBox.textContent = message;
                errorBox.classList.toggle('hidden', message === '');
            }

            input.setCustomValidity(message);
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                setFileError('');

                return;
            }

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

            if (!isPdf) {
                setFileError('Berkas harus berformat PDF.');

                return;
            }

            if (file.size > maxKb * 1024) {
                setFileError(`Ukuran berkas maksimal ${maxKb} KB.`);

                return;
            }

            setFileError('');
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

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
