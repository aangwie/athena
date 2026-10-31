import './bootstrap';
import 'sweetalert2/dist/sweetalert2.min.css';
import Swal from 'sweetalert2';
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
            // Nomor baris mengikuti halaman aktif: tambahkan atribut data-row-numbers pada tabel.
            drawCallback() {
                if (!table.hasAttribute('data-row-numbers')) {
                    return;
                }

                const api = this.api();
                const start = api.page.info().start;

                api.column(0, { page: 'current' })
                    .nodes()
                    .each((cell, index) => {
                        cell.textContent = start + index + 1;
                    });
            },
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

    const jabatanInputs = document.querySelectorAll('[data-jabatan-akademik]');
    const pangkatSection = document.querySelector('[data-pangkat-section]');
    const pangkatSelect = document.querySelector('#pangkat');

    const syncPangkatSection = () => {
        if (!pangkatSection || !pangkatSelect) {
            return;
        }

        const punyaJabatan = document.querySelector('[data-jabatan-akademik]:checked')?.value === 'ya';

        pangkatSection.classList.toggle('hidden', !punyaJabatan);
        pangkatSelect.required = punyaJabatan;
        pangkatSelect.disabled = !punyaJabatan;
    };

    if (jabatanInputs.length > 0) {
        jabatanInputs.forEach((input) => {
            input.addEventListener('change', syncPangkatSection);
        });
        syncPangkatSection();
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

    document.querySelectorAll('[data-image-input]').forEach((input) => {
        const field = input.closest('[data-image-field]');
        const errorBox = field?.querySelector('[data-image-error]');
        const preview = field?.querySelector('[data-image-preview]');
        const placeholder = field?.querySelector('[data-image-placeholder]');
        const maxKb = Number(input.dataset.maxKb ?? 500);

        const setImageError = (message) => {
            if (errorBox) {
                errorBox.textContent = message;
                errorBox.classList.toggle('hidden', message === '');
            }

            input.setCustomValidity(message);
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                setImageError('');

                return;
            }

            if (!file.type.startsWith('image/')) {
                setImageError('Berkas harus berupa gambar.');

                return;
            }

            if (file.size > maxKb * 1024) {
                setImageError(`Ukuran gambar maksimal ${maxKb} KB.`);

                return;
            }

            setImageError('');

            if (preview) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            }

            placeholder?.classList.add('hidden');
        });
    });

    // Pengaturan: backup & import database dikerjakan bertahap, bilah progres mengikuti
    // jumlah tabel (backup) atau byte berkas (import) yang sudah diproses server.
    document.querySelectorAll('form[data-db-job]').forEach((form) => {
        const panel = form.closest('[data-db-panel]');
        const progres = panel?.querySelector('[data-db-progress]');
        const bar = panel?.querySelector('[data-db-bar]');
        const info = panel?.querySelector('[data-db-info]');
        const unduh = panel?.querySelector('[data-db-unduh]');
        const tombol = form.querySelector('button[type="submit"]');
        const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        const tampilProgres = (persen, pesan) => {
            progres?.classList.remove('hidden');

            if (bar) {
                bar.style.width = `${Math.min(100, Math.max(0, Number(persen) || 0))}%`;
            }

            if (info) {
                info.textContent = pesan ?? '';
            }
        };

        const kirim = (url, data) => window.axios.post(url, data, {
            headers: { 'X-CSRF-TOKEN': token },
        });

        // data-db-langkah → dataset.dbLangkah (camelCase), bukan dataset.langkah.
        const langkahUrl = form.dataset.dbLangkah;

        form.addEventListener('submit', async (event) => {
            // Konfirmasi global SweetAlert2 (form[data-confirm]) dijalankan lebih dulu.
            if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
                return;
            }

            event.preventDefault();
            unduh?.classList.add('hidden');

            if (tombol) {
                tombol.disabled = true;
            }

            tampilProgres(0, 'Menyiapkan proses…');

            try {
                if (!langkahUrl) {
                    throw new Error('Atribut data-db-langkah tidak ada pada form.');
                }

                const mulai = await kirim(form.action, new FormData(form));
                let hasil = mulai.data;

                tampilProgres(hasil.persen, hasil.pesan);

                while (!hasil.selesai) {
                    const langkah = await kirim(langkahUrl, new FormData());
                    hasil = langkah.data;

                    tampilProgres(hasil.persen, hasil.pesan);
                }

                if (hasil.unduh) {
                    if (unduh) {
                        unduh.href = hasil.unduh;
                        unduh.classList.remove('hidden');
                    }

                    window.location.href = hasil.unduh;
                }
            } catch (error) {
                const data = error.response?.data ?? {};
                const pesan = data.pesan
                    || Object.values(data.errors ?? {}).flat().join(' ')
                    || error.message;

                tampilProgres(0, pesan);
            } finally {
                if (tombol) {
                    tombol.disabled = false;
                }

                form.dataset.confirmed = 'false';
            }
        });
    });

    // Konfirmasi global: setiap <form data-confirm="..."> dicek lewat SweetAlert2.
    // Delegasi ke document supaya form di dalam tabel (yang bisa di-render ulang
    // DataTables) tetap ikut terkonfirmasi.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (!form || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();

        const isDelete = (form.querySelector('input[name="_method"]')?.value ?? '').toUpperCase() === 'DELETE';

        Swal.fire({
            title: form.dataset.confirmTitle || (isDelete ? 'Hapus data ini?' : 'Konfirmasi'),
            text: form.dataset.confirm,
            icon: isDelete ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: isDelete ? 'Ya, hapus' : 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: isDelete ? '#e11d48' : '#0d9488',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            form.dataset.confirmed = 'true';
            form.requestSubmit();
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

    const sidebarRoot = document.documentElement;
    const collapseButtons = document.querySelectorAll('[data-sidebar-collapse]');
    const sidebarDesktop = window.matchMedia('(min-width: 1024px)');
    const forcedOpenDetails = new Set();

    const syncSidebarDetails = () => {
        if (!sidebar) {
            return;
        }

        const revealAll = sidebarRoot.classList.contains('sidebar-collapsed') && sidebarDesktop.matches;

        sidebar.querySelectorAll('details').forEach((details) => {
            if (revealAll) {
                if (!details.open) {
                    details.open = true;
                    forcedOpenDetails.add(details);
                }

                return;
            }

            if (forcedOpenDetails.has(details)) {
                details.open = false;
                forcedOpenDetails.delete(details);
            }
        });
    };

    const syncSidebarCollapse = () => {
        const isCollapsed = sidebarRoot.classList.contains('sidebar-collapsed');
        const label = isCollapsed ? 'Perluas sidebar' : 'Minimalkan sidebar';

        collapseButtons.forEach((button) => {
            button.setAttribute('aria-expanded', String(!isCollapsed));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.querySelector('[data-collapse-icon-open]')?.classList.toggle('hidden', isCollapsed);
            button.querySelector('[data-collapse-icon-closed]')?.classList.toggle('hidden', !isCollapsed);
        });

        syncSidebarDetails();
    };

    collapseButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const isCollapsed = sidebarRoot.classList.toggle('sidebar-collapsed');

            try {
                localStorage.setItem('athena-sidebar', isCollapsed ? 'collapsed' : 'expanded');
            } catch (error) {
                // localStorage tidak tersedia, abaikan.
            }

            syncSidebarCollapse();
        });
    });

    if (typeof sidebarDesktop.addEventListener === 'function') {
        sidebarDesktop.addEventListener('change', syncSidebarDetails);
    }

    syncSidebarCollapse();

    document.querySelectorAll('[data-tabs]').forEach((group) => {
        const tabs = Array.from(group.querySelectorAll('[data-tab-target]'));
        const panels = Array.from(group.querySelectorAll('[data-tab-panel]'));

        const activate = (name, remember) => {
            if (!panels.some((panel) => panel.dataset.tabPanel === name)) {
                return;
            }

            tabs.forEach((tab) => {
                const isActive = tab.dataset.tabTarget === name;
                tab.classList.toggle('tab-button-active', isActive);
                tab.setAttribute('aria-selected', String(isActive));
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
            });

            // DataTables perlu hitung ulang lebar kolom saat panel tersembunyi ditampilkan.
            window.dispatchEvent(new Event('resize'));

            if (remember) {
                history.replaceState(null, '', `#${name}`);
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab.dataset.tabTarget, true));

            tab.addEventListener('keydown', (event) => {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
                    return;
                }

                event.preventDefault();
                const step = event.key === 'ArrowRight' ? 1 : tabs.length - 1;
                const next = tabs[(index + step) % tabs.length];

                next.focus();
                activate(next.dataset.tabTarget, true);
            });
        });

        activate(location.hash.slice(1) || tabs[0]?.dataset.tabTarget, false);
    });

    // Select dengan kotak pencarian: opsi disaring tiap ketikan, opsi terpilih selalu dipertahankan.
    document.querySelectorAll('select[data-select-search]').forEach((select) => {
        const opsi = Array.from(select.options).map((option) => ({
            value: option.value,
            label: option.textContent.trim(),
        }));

        const cari = document.createElement('input');
        cari.type = 'search';
        cari.className = 'form-input mb-2';
        cari.placeholder = select.dataset.selectSearch || 'Cari…';
        cari.setAttribute('aria-label', cari.placeholder);
        select.before(cari);

        cari.addEventListener('input', () => {
            const kata = cari.value.toLowerCase();
            const terpilih = select.value;

            select.replaceChildren(
                ...opsi
                    .filter((option) => option.value === '' || option.value === terpilih || option.label.toLowerCase().includes(kata))
                    .map((option) => new Option(option.label, option.value, false, option.value === terpilih)),
            );

            select.value = terpilih;
        });
    });

    // Masker tanggal DD-MM-YYYY: hanya angka, tanda hubung ditambahkan otomatis.
    document.querySelectorAll('[data-date-mask]').forEach((input) => {
        input.addEventListener('input', () => {
            const digit = input.value.replace(/\D/g, '').slice(0, 8);

            input.value = [digit.slice(0, 2), digit.slice(2, 4), digit.slice(4, 8)].filter(Boolean).join('-');
        });
    });

    // Pakta integritas: centang karya ilmiah milik dosen yang dipilih (data dari <script data-karya-data>).
    document.querySelectorAll('[data-karya-picker]').forEach((picker) => {
        const dosen = document.querySelector(picker.dataset.dosenTarget);
        const list = picker.querySelector('[data-karya-list]');
        const info = picker.querySelector('[data-karya-info]');
        const kosong = picker.querySelector('[data-karya-empty]');
        const semua = picker.querySelector('[data-karya-all]');
        const data = JSON.parse(picker.querySelector('[data-karya-data]')?.textContent || '{}');
        const terpilih = new Set((picker.dataset.selected || '').split(',').filter(Boolean));

        const kotak = () => [...(list?.querySelectorAll('input[type="checkbox"]') ?? [])];

        const sinkron = () => {
            const jumlah = kotak().length;
            const dicentang = kotak().filter((item) => item.checked).length;

            if (semua) {
                semua.checked = jumlah > 0 && dicentang === jumlah;
                semua.disabled = jumlah === 0;
            }

            // Jumlah karya dosen yang dipilih ditampilkan di samping judul bagian.
            if (info) {
                info.textContent = dosen?.value
                    ? `${jumlah} karya ilmiah terdaftar · ${dicentang} dicetak`
                    : 'Pilih dosen terlebih dahulu.';
            }
        };

        const gambar = () => {
            if (!list) return;

            const items = data[dosen?.value] ?? [];

            list.replaceChildren(...items.map((karya) => {
                const label = document.createElement('label');
                label.className = 'flex items-start gap-2 rounded-lg border border-slate-200 bg-white p-2.5 text-xs text-slate-600 transition hover:border-teal-300';

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.name = 'karya_tulis_ids[]';
                input.value = karya.id;
                input.checked = terpilih.has(String(karya.id));
                input.className = 'mt-0.5 size-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30';
                input.addEventListener('change', sinkron);

                const jenis = document.createElement('span');
                jenis.className = 'block font-bold text-slate-700';
                jenis.textContent = karya.jenis;

                const judul = document.createElement('span');
                judul.className = 'mt-0.5 block text-[11px] leading-snug text-slate-500';
                judul.textContent = karya.judul;

                const teks = document.createElement('span');
                teks.append(jenis, judul);
                label.append(input, teks);

                return label;
            }));

            kosong?.classList.toggle('hidden', items.length > 0);
            sinkron();
        };

        // Ganti dosen = mulai dari tanpa centang (semua karya dosen baru ikut tercetak).
        dosen?.addEventListener('change', () => {
            terpilih.clear();
            gambar();
        });

        semua?.addEventListener('change', () => {
            kotak().forEach((item) => {
                item.checked = semua.checked;
            });
            sinkron();
        });

        gambar();
    });

    document.querySelectorAll('[data-reveal-select]').forEach((select) => {
        const target = document.querySelector(select.dataset.revealSelect);

        if (!target) {
            return;
        }

        const sync = () => target.classList.toggle('hidden', select.value === '');

        select.addEventListener('change', sync);
        sync();
    });

    // Select dengan pencarian: input [data-select-filter="#id-select"] menyaring opsi select tujuannya.
    document.querySelectorAll('[data-select-filter]').forEach((input) => {
        const select = document.querySelector(input.dataset.selectFilter);

        if (!select) {
            return;
        }

        const options = [...select.options];

        input.addEventListener('input', () => {
            const keyword = input.value.trim().toLowerCase();
            const selected = select.value;

            select.replaceChildren(
                ...options.filter((option) => {
                    return option.value === ''
                        || option.value === selected
                        || option.text.toLowerCase().includes(keyword);
                }),
            );

            select.value = selected;
        });
    });

    document.querySelectorAll('[data-dependent-select]').forEach((select) => {
        const target = document.querySelector(select.dataset.dependentSelect);
        const field = target?.closest('[data-dependent-field]');

        if (!target || !field) {
            return;
        }

        const options = JSON.parse(select.dataset.subOptions || '{}');
        const placeholder = target.dataset.placeholder || 'Pilih rincian';

        const sync = () => {
            const subs = options[select.value] || {};
            const keys = Object.keys(subs);
            const previous = target.dataset.selected || target.value || '';

            target.innerHTML = '';
            target.add(new Option(keys.length ? placeholder : 'Tidak ada rincian', ''));
            keys.forEach((key) => target.add(new Option(subs[key], key)));

            target.value = keys.includes(previous) ? previous : '';
            target.dataset.selected = '';
            target.disabled = keys.length === 0;
            field.classList.toggle('hidden', keys.length === 0);

            // Teruskan ke select tingkat berikutnya (komponen → rincian → rincian kegiatan).
            target.dispatchEvent(new Event('change'));
        };

        select.addEventListener('change', sync);
        sync();
    });

    document.querySelectorAll('[data-link-input]').forEach((input) => {
        const field = input.closest('div');
        const preview = field?.querySelector('[data-link-preview]');
        const host = field?.querySelector('[data-link-host]');
        const url = field?.querySelector('[data-link-url]');
        const invalid = field?.querySelector('[data-link-invalid]');

        if (!preview || !host || !url || !invalid) {
            return;
        }

        const sync = () => {
            const value = input.value.trim();
            let parsed = null;

            try {
                parsed = new URL(value);
            } catch (error) {
                // URL belum lengkap, tampilkan sebagai peringatan.
            }

            const isValid = parsed !== null && ['http:', 'https:'].includes(parsed.protocol);

            preview.classList.toggle('hidden', !isValid);
            preview.classList.toggle('flex', isValid);
            invalid.classList.toggle('hidden', value === '' || isValid);

            if (isValid) {
                host.textContent = parsed.host;
                url.textContent = value;
                url.href = value;
            }
        };

        input.addEventListener('input', sync);
        sync();
    });

    // TIM PAK: baris "Jumlah" tiap bidang mengikuti nilai yang sedang diketik.
    const nilaiInputs = document.querySelectorAll('[data-nilai]');

    if (nilaiInputs.length > 0) {
        const formatKredit = (nilai) => String(Math.round(nilai * 100) / 100).replace('.', ',');

        const syncJumlahBidang = () => {
            const total = new Map();

            nilaiInputs.forEach((input) => {
                const grup = input.dataset.nilaiGrup;
                const nilai = Number.parseFloat(input.value);

                total.set(grup, (total.get(grup) ?? 0) + (Number.isFinite(nilai) ? nilai : 0));
            });

            total.forEach((nilai, grup) => {
                document.querySelectorAll(`[data-jumlah-total="${grup}"]`).forEach((sel) => {
                    sel.textContent = formatKredit(nilai);
                });
            });

            // Baris "Jumlah Total": akumulasi seluruh bidang.
            const grandTotal = [...total.values()].reduce((jumlah, nilai) => jumlah + nilai, 0);

            document.querySelectorAll('[data-jumlah-grand-total]').forEach((sel) => {
                sel.textContent = formatKredit(grandTotal);
            });
        };

        nilaiInputs.forEach((input) => input.addEventListener('input', syncJumlahBidang));
        syncJumlahBidang();
    }

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
