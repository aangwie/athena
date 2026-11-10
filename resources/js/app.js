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
    const jabatanSection = document.querySelector('[data-jabatan-section]');
    const jabatanSelects = [document.querySelector('#jabatan_fungsional'), document.querySelector('#pangkat')];

    const syncPangkatSection = () => {
        if (!jabatanSection) {
            return;
        }

        const punyaJabatan = document.querySelector('[data-jabatan-akademik]:checked')?.value === 'ya';

        jabatanSection.classList.toggle('hidden', !punyaJabatan);

        jabatanSelects.forEach((select) => {
            if (select) {
                select.required = punyaJabatan;
                select.disabled = !punyaJabatan;
            }
        });
    };

    if (jabatanInputs.length > 0) {
        jabatanInputs.forEach((input) => {
            input.addEventListener('change', syncPangkatSection);
        });
        syncPangkatSection();
    }

    // Surat pengantar: pilih bentuk Perorangan (dropdown satu dosen) atau Kolektif (multi select).
    const pengantarJenisInputs = document.querySelectorAll('[data-pengantar-jenis]');
    const pengantarPerorangan = document.querySelectorAll('[data-pengantar-perorangan]');
    const pengantarKolektif = document.querySelectorAll('[data-pengantar-kolektif]');
    const pengantarSelectPerorangan = document.querySelector('#pengantar_dosen_perorangan');
    const pengantarSelectKolektif = document.querySelector('#pengantar_dosen');
    const pengantarSelectDiusulkan = document.querySelector('#pengantar_jabatan_diusulkan');

    const syncPengantarJenis = () => {
        const kolektif = document.querySelector('[data-pengantar-jenis]:checked')?.value === '1';

        pengantarPerorangan.forEach((el) => el.classList.toggle('hidden', kolektif));
        pengantarKolektif.forEach((el) => el.classList.toggle('hidden', !kolektif));

        // Disabled agar isian bentuk yang tidak dipakai tidak ikut dikirim / divalidasi browser.
        if (pengantarSelectPerorangan) {
            pengantarSelectPerorangan.required = !kolektif;
            pengantarSelectPerorangan.disabled = kolektif;
        }

        if (pengantarSelectKolektif) {
            pengantarSelectKolektif.required = kolektif;
            pengantarSelectKolektif.disabled = !kolektif;
        }

        // Jabatan yang diusulkan hanya untuk bentuk perorangan.
        if (pengantarSelectDiusulkan) {
            pengantarSelectDiusulkan.disabled = kolektif;
        }
    };

    if (pengantarJenisInputs.length > 0) {
        pengantarJenisInputs.forEach((input) => {
            input.addEventListener('change', syncPengantarJenis);
        });
        syncPengantarJenis();
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

    // Pakta integritas: pilih jenis cetak (Pernyataan atau Validasi) lewat dialog SweetAlert2.
    document.querySelectorAll('[data-cetak-pakta]').forEach((button) => {
        button.addEventListener('click', () => {
            const pernyataan = button.dataset.cetakPernyataan;
            const validasi = button.dataset.cetakValidasi;

            Swal.fire({
                title: 'Pilih jenis cetak',
                text: 'Pilih dokumen yang ingin dicetak untuk pakta integritas ini.',
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Pernyataan',
                denyButtonText: 'Validasi',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#0d9488',
                denyButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed && pernyataan) {
                    window.open(pernyataan, '_blank', 'noopener');
                } else if (result.isDenied && validasi) {
                    window.open(validasi, '_blank', 'noopener');
                }
            });
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

    // Dropdown pencarian (vanilla JS): lihat siapkanDropdownCari() di akhir berkas.
    document.querySelectorAll('select[data-select-search]').forEach((select, indeks) => siapkanDropdownCari(select, indeks));

    // Masker tanggal DD-MM-YYYY: hanya angka, tanda hubung ditambahkan otomatis.
    document.querySelectorAll('[data-date-mask]').forEach((input) => {
        input.addEventListener('input', () => {
            const digit = input.value.replace(/\D/g, '').slice(0, 8);

            input.value = [digit.slice(0, 2), digit.slice(2, 4), digit.slice(4, 8)].filter(Boolean).join('-');
        });
    });

    // Date picker DD-MM-YYYY (vanilla JS): lihat siapkanDatePicker() di akhir berkas.
    document.querySelectorAll('[data-date-picker]').forEach((input, indeks) => siapkanDatePicker(input, indeks));

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

    // Riwayat pekerjaan: masa kerja (tahun & bulan) dihitung dari Tanggal SK,
    // yaitu selisih hari ini dengan tanggal SK pada grup [data-sk-group].
    document.querySelectorAll('[data-sk-tanggal]').forEach((input) => {
        const group = input.closest('[data-sk-group]');
        const tahun = group?.querySelector('[data-sk-tahun]');
        const bulan = group?.querySelector('[data-sk-bulan]');

        if (!tahun || !bulan) {
            return;
        }

        const sync = () => {
            const tanggal = input.value ? new Date(`${input.value}T00:00:00`) : null;

            if (!tanggal || Number.isNaN(tanggal.getTime())) {
                tahun.value = '';
                bulan.value = '';

                return;
            }

            const hariIni = new Date();
            let totalBulan =
                (hariIni.getFullYear() - tanggal.getFullYear()) * 12 + (hariIni.getMonth() - tanggal.getMonth());

            if (hariIni.getDate() < tanggal.getDate()) {
                totalBulan -= 1;
            }

            totalBulan = Math.max(0, totalBulan);
            tahun.value = Math.floor(totalBulan / 12);
            bulan.value = totalBulan % 12;
        };

        input.addEventListener('change', sync);
        input.addEventListener('input', sync);
        sync();
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

    // Riwayat karya tulis: bidang jurnal/prosiding tampil mengikuti jenis karya ilmiah terpilih.
    const karyaJenis = document.querySelector('#karya_ilmiah');

    if (karyaJenis) {
        const jurnalBox = document.querySelector('[data-grup-jurnal]');
        const prosidingBox = document.querySelector('[data-grup-prosiding]');
        const jenisJurnal = JSON.parse(karyaJenis.dataset.karyaJurnal || '[]');
        const jenisProsiding = karyaJenis.dataset.karyaProsiding || '';

        // Input pada grup tersembunyi dinonaktifkan (dan tidak wajib) agar tidak
        // ikut tervalidasi/terkirim; grup yang tampil dikembalikan aktif & wajib.
        const setGrup = (box, tampil) => {
            if (!box) {
                return;
            }

            box.classList.toggle('hidden', !tampil);

            box.querySelectorAll('input, select, textarea').forEach((el) => {
                el.disabled = !tampil;
                el.required = tampil && el.dataset.wajib !== undefined;
            });
        };

        const syncKaryaJenis = () => {
            const jenis = karyaJenis.value;

            setGrup(jurnalBox, jenisJurnal.includes(jenis));
            setGrup(prosidingBox, jenis === jenisProsiding);
        };

        karyaJenis.addEventListener('change', syncKaryaJenis);
        syncKaryaJenis();
    }

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

    // Lonceng notifikasi: buka panel, lalu tandai semua notifikasi sudah dibaca.
    document.querySelectorAll('[data-notif-button]').forEach((button) => {
        const menu = button.parentElement?.querySelector('[data-notif-menu]');

        if (!menu) {
            return;
        }

        const badge = button.querySelector('[data-notif-badge]');
        const summary = menu.querySelector('[data-notif-summary]');
        let sudahDibaca = badge?.classList.contains('hidden') ?? true;

        const closeMenu = () => {
            menu.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        };

        const tandaiDibaca = () => {
            if (sudahDibaca) {
                return;
            }

            sudahDibaca = true;
            badge?.classList.add('hidden');

            if (summary) {
                summary.textContent = '0 baru';
            }

            fetch(button.dataset.notifUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });
        };

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const isHidden = menu.classList.contains('hidden');

            menu.classList.toggle('hidden', !isHidden);
            button.setAttribute('aria-expanded', String(isHidden));

            if (isHidden) {
                tandaiDibaca();
            }
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

    // Alert hasil pengiriman notifikasi WhatsApp (dari session('wa_notif')).
    const waNotifAlert = document.querySelector('[data-wa-notif-alert]');

    if (waNotifAlert) {
        Swal.fire({
            icon: waNotifAlert.dataset.waIcon || 'info',
            title: waNotifAlert.dataset.waTitle || 'Notifikasi WhatsApp',
            text: waNotifAlert.dataset.waText || '',
            confirmButtonColor: '#0d9488',
            confirmButtonText: 'Mengerti',
        });
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

/**
 * Komponen dropdown pencarian (vanilla JS, tanpa dependensi).
 *
 * <select> asli disembunyikan (sr-only) lalu diganti tampilan tombol + panel daftar + kotak cari.
 * Nilai tetap disimpan pada <select> asli sehingga name/id/required/disabled dan validasi server
 * tetap berjalan, sekaligus skrip lain yang mencari element lewat id tidak terganggu.
 *
 * @param {HTMLSelectElement} select
 * @param {number} indeks
 */
function siapkanDropdownCari(select, indeks) {
    const multi = select.multiple;
    const teksCari = select.dataset.selectSearch || 'Cari…';
    const opsiKosong = select.querySelector('option[value=""]');
    const teksPlaceholder = (opsiKosong?.textContent || 'Pilih…').trim();
    const idPanel = `sd-panel-${indeks}-${Math.random().toString(36).slice(2, 8)}`;

    // Select asli dipertahankan (sr-only) agar tetap tersubmit & tervalidasi; id tidak diubah
    // karena skrip lain (mis. data-dependent-select) mencarinya lewat id.
    const wrap = document.createElement('div');
    wrap.className = 'sd';
    select.before(wrap);
    wrap.append(select);
    select.classList.add('sr-only');
    select.setAttribute('tabindex', '-1');

    const trigger = document.createElement('div');
    trigger.className = 'sd-trigger';
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', idPanel);
    trigger.innerHTML = '<span class="sd-value"></span>'
        + '<svg class="sd-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
        + '<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>';

    const nilai = trigger.querySelector('.sd-value');

    const chips = document.createElement('div');
    chips.className = 'sd-chips';
    chips.hidden = true;

    wrap.append(trigger, chips);

    const panel = document.createElement('div');
    panel.className = 'sd-panel';
    panel.id = idPanel;
    panel.hidden = true;
    panel.setAttribute('role', 'listbox');

    if (multi) {
        panel.setAttribute('aria-multiselectable', 'true');
    }

    const cari = document.createElement('input');
    cari.type = 'search';
    cari.className = 'sd-search';
    cari.placeholder = teksCari;
    cari.setAttribute('aria-label', teksCari);
    cari.autocomplete = 'off';

    const list = document.createElement('div');
    list.className = 'sd-list';

    const kosong = document.createElement('p');
    kosong.className = 'sd-empty';
    kosong.textContent = select.dataset.selectEmpty || 'Tidak ada pilihan yang cocok.';
    kosong.hidden = true;

    panel.append(cari, list, kosong);

    // Panel ditempel di <body> memakai posisi fixed agar tidak terpotong overflow induk.
    document.body.append(panel);

    const terpilih = () => Array.from(select.options).filter((option) => option.selected && option.value !== '');

    const gambarNilai = () => {
        const dipilih = terpilih();

        nilai.classList.toggle('sd-placeholder', dipilih.length === 0);
        nilai.textContent = dipilih.length === 0
            ? teksPlaceholder
            : (multi ? `${dipilih.length} dipilih` : dipilih[0].textContent.trim());
    };

    const gambarChips = () => {
        if (!multi) {
            chips.hidden = true;

            return;
        }

        const dipilih = terpilih();
        chips.hidden = dipilih.length === 0;

        chips.replaceChildren(...dipilih.map((option) => {
            const chip = document.createElement('span');
            chip.className = 'sd-chip';

            const teks = document.createElement('span');
            teks.className = 'truncate';
            teks.textContent = option.textContent.trim();

            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'sd-chip-remove';
            hapus.setAttribute('aria-label', `Hapus ${option.textContent.trim()}`);
            hapus.innerHTML = '&times;';
            hapus.addEventListener('click', (event) => {
                event.stopPropagation();
                option.selected = false;
                ubah();
            });

            chip.append(teks, hapus);

            return chip;
        }));
    };

    // Kelompok <optgroup> asli ditampilkan sebagai judul di atas opsinya (dropdown bertingkat).
    const grupOpsi = (option) => (option.parentElement instanceof HTMLOptGroupElement ? option.parentElement : null);

    // Opsi cocok bila labelnya atau nama kelompoknya memuat kata kunci.
    const cocok = (option, kata) => kata === ''
        || option.textContent.toLowerCase().includes(kata)
        || (grupOpsi(option)?.label.toLowerCase().includes(kata) ?? false);

    const barisOpsi = (option) => {
        const baris = document.createElement('div');
        baris.className = 'sd-option' + (option.selected ? ' sd-option-active' : '');
        baris.setAttribute('role', 'option');
        baris.setAttribute('aria-selected', String(option.selected));

        const label = document.createElement('span');
        label.className = 'sd-option-label';
        label.textContent = option.textContent.trim();

        const tick = document.createElement('svg');
        tick.className = 'sd-option-tick' + (option.selected ? '' : ' sd-option-tick-off');
        tick.setAttribute('viewBox', '0 0 20 20');
        tick.setAttribute('fill', 'currentColor');
        tick.setAttribute('aria-hidden', 'true');
        tick.innerHTML = '<path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.2 7.3a1 1 0 0 1-1.4 0L4.3 10.3a1 1 0 0 1 1.4-1.4l3.1 3 6.5-6.6a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />';

        baris.append(label, tick);
        baris.addEventListener('click', () => pilih(option));

        return baris;
    };

    const gambarList = () => {
        const kata = cari.value.trim().toLowerCase();
        // Opsi kosong & opsi dinonaktifkan (mis. kuota jabatan sudah terisi) tidak ikut ditampilkan.
        const tampil = Array.from(select.options)
            .filter((option) => option.value !== '' && !option.disabled && cocok(option, kata));

        const baris = [];
        let grupTerakhir = null;

        tampil.forEach((option) => {
            const grup = grupOpsi(option)?.label ?? '';

            // Judul kelompok hanya dicetak sekali, tepat sebelum opsi pertamanya.
            if (grup !== grupTerakhir) {
                grupTerakhir = grup;

                if (grup !== '') {
                    const judul = document.createElement('p');
                    judul.className = 'sd-group';
                    judul.textContent = grup;
                    baris.push(judul);
                }
            }

            baris.push(barisOpsi(option));
        });

        list.replaceChildren(...baris);

        kosong.hidden = tampil.length > 0;
    };

    // pilih(): single -> set nilai & tutup panel; multi -> toggle & biarkan panel terbuka.
    const pilih = (option) => {
        if (select.disabled) {
            return;
        }

        if (multi) {
            option.selected = !option.selected;
            ubah();
        } else {
            select.value = option.value;
            ubah();
            tutup();
        }
    };

    // ubah(): beri tahu skrip lain via event change, lalu gambar ulang tampilan.
    const ubah = () => {
        select.dispatchEvent(new Event('change', { bubbles: true }));
        gambarNilai();
        gambarChips();
        gambarList();
    };

    // Panel fixed: posisinya dihitung dari tombol; bila ruang bawah sempit dibuka ke atas.
    const posisi = () => {
        if (panel.hidden) {
            return;
        }

        const kotak = trigger.getBoundingClientRect();
        const lebar = kotak.width;
        const ruangBawah = window.innerHeight - kotak.bottom;
        const tinggiMaks = Math.max(160, Math.min(300, window.innerHeight - 24));

        panel.style.width = `${lebar}px`;
        panel.style.left = `${Math.max(8, Math.min(kotak.left, window.innerWidth - lebar - 8))}px`;

        if (ruangBawah < 240 && kotak.top > ruangBawah) {
            panel.style.maxHeight = `${Math.min(tinggiMaks, Math.max(160, kotak.top - 12))}px`;
            panel.style.top = `${Math.max(8, kotak.top - panel.offsetHeight - 4)}px`;
        } else {
            panel.style.maxHeight = `${Math.min(tinggiMaks, Math.max(160, ruangBawah - 12))}px`;
            panel.style.top = `${kotak.bottom + 4}px`;
        }
    };

    const buka = () => {
        if (select.disabled || !panel.hidden) {
            return;
        }

        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        cari.value = '';
        gambarList();
        posisi();
        cari.focus();
        window.addEventListener('scroll', posisi, true);
        window.addEventListener('resize', posisi);
    };

    const tutup = () => {
        if (panel.hidden) {
            return;
        }

        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        window.removeEventListener('scroll', posisi, true);
        window.removeEventListener('resize', posisi);
    };

    const syncDisabled = () => {
        const mati = select.disabled;

        trigger.classList.toggle('sd-disabled', mati);
        trigger.setAttribute('aria-disabled', String(mati));
        trigger.tabIndex = mati ? -1 : 0;

        if (mati) {
            tutup();
        }
    };

    trigger.addEventListener('click', () => (panel.hidden ? buka() : tutup()));
    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
            event.preventDefault();
            buka();
        } else if (event.key === 'Escape') {
            tutup();
        }
    });

    cari.addEventListener('input', gambarList);
    cari.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            tutup();
            trigger.focus();
        }
    });

    // Klik di luar komponen menutup panel. composedPath() dipakai karena opsi bisa
    // sudah terlepas dari DOM (daftar digambar ulang) saat event sampai ke document.
    document.addEventListener('click', (event) => {
        const jalur = typeof event.composedPath === 'function' ? event.composedPath() : [];

        if (jalur.includes(panel) || jalur.includes(wrap) || wrap.contains(event.target) || panel.contains(event.target)) {
            return;
        }

        tutup();
    });

    // Perubahan opsi/status oleh skrip lain (mis. data-dependent-select) ikut disinkronkan.
    new MutationObserver(() => {
        syncDisabled();
        gambarNilai();
        gambarChips();
        gambarList();
    }).observe(select, { attributes: true, attributeFilter: ['disabled', 'required'], childList: true, subtree: true });

    // Isian wajib yang kosong: buka panel sebagai umpan balik, karena kontrol asli tersembunyi
    // sehingga pesan validasi bawaan browser tidak terlihat pengguna.
    const form = select.closest('form');

    if (form) {
        form.addEventListener('submit', (event) => {
            if (select.required && !select.disabled && select.value === '' && !event.defaultPrevented) {
                event.preventDefault();
                buka();
            }
        });
    }

    syncDisabled();
    gambarNilai();
    gambarChips();
    gambarList();
}

/**
 * Date picker (vanilla JS, tanpa dependensi) dengan keluaran DD-MM-YYYY.
 *
 * Input teks asli tetap dipakai (name/id/required/disabled dan validasi server date_format:d-m-Y
 * tidak berubah), lalu ditambah tombol kalender dan panel pemilih tanggal. Nilai yang dikirim ke
 * server selalu DD-MM-YYYY, termasuk saat tanggal dipilih lewat kalender.
 *
 * @param {HTMLInputElement} input
 * @param {number} indeks
 */
function siapkanDatePicker(input, indeks) {
    const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const NAMA_HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    const idPanel = `dp-panel-${indeks}-${Math.random().toString(36).slice(2, 8)}`;
    const dua = (angka) => String(angka).padStart(2, '0');

    // Date -> "DD-MM-YYYY", format yang dipakai input sekaligus validasi server.
    const formatNilai = (tanggal) => `${dua(tanggal.getDate())}-${dua(tanggal.getMonth() + 1)}-${tanggal.getFullYear()}`;

    // "DD-MM-YYYY" -> Date, atau null bila belum lengkap / tanggalnya tidak ada (mis. 31-02-2026).
    const bacaNilai = (teks) => {
        const cocok = /^(\d{2})-(\d{2})-(\d{4})$/.exec(String(teks).trim());

        if (!cocok) {
            return null;
        }

        const hari = Number(cocok[1]);
        const bulan = Number(cocok[2]) - 1;
        const tanggal = new Date(Number(cocok[3]), bulan, hari);

        return tanggal.getDate() === hari && tanggal.getMonth() === bulan ? tanggal : null;
    };

    const samaHari = (a, b) => b !== null && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

    const hariIni = () => {
        const sekarang = new Date();

        return new Date(sekarang.getFullYear(), sekarang.getMonth(), sekarang.getDate());
    };

    // Input dibungkus agar tombol kalender bisa ditempatkan di dalam sisi kanan input.
    const wrap = document.createElement('div');
    wrap.className = 'dp';
    input.parentNode.insertBefore(wrap, input);
    wrap.append(input);

    input.classList.add('dp-input');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('aria-haspopup', 'dialog');
    input.setAttribute('aria-controls', idPanel);

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'dp-trigger';
    trigger.setAttribute('aria-label', 'Buka kalender');
    trigger.setAttribute('aria-controls', idPanel);
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML = '<svg class="dp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        + 'stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />'
        + '<path d="M3.5 9.5h17M8.5 3v4M15.5 3v4" stroke-linecap="round" /></svg>';
    wrap.append(trigger);

    const panel = document.createElement('div');
    panel.className = 'dp-panel';
    panel.id = idPanel;
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Pilih tanggal');

    const head = document.createElement('div');
    head.className = 'dp-head';

    const tombolNav = (arah, label, simbol) => {
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'dp-nav';
        tombol.dataset.arah = arah;
        tombol.setAttribute('aria-label', label);
        tombol.innerHTML = simbol;

        return tombol;
    };

    const prevTahun = tombolNav('-12', 'Tahun sebelumnya', '&laquo;');
    const prevBulan = tombolNav('-1', 'Bulan sebelumnya', '&lsaquo;');
    const nextBulan = tombolNav('1', 'Bulan berikutnya', '&rsaquo;');
    const nextTahun = tombolNav('12', 'Tahun berikutnya', '&raquo;');

    const label = document.createElement('span');
    label.className = 'dp-label';
    label.setAttribute('aria-live', 'polite');

    head.append(prevTahun, prevBulan, label, nextBulan, nextTahun);

    const minggu = document.createElement('div');
    minggu.className = 'dp-week';
    minggu.append(...NAMA_HARI.map((hari) => {
        const sel = document.createElement('span');
        sel.textContent = hari;

        return sel;
    }));

    const grid = document.createElement('div');
    grid.className = 'dp-grid';

    const kaki = document.createElement('div');
    kaki.className = 'dp-foot';

    const tombolIni = document.createElement('button');
    tombolIni.type = 'button';
    tombolIni.className = 'dp-today';
    tombolIni.textContent = 'Hari ini';
    kaki.append(tombolIni);

    panel.append(head, minggu, grid, kaki);

    // Panel ditempel di <body> memakai posisi fixed agar tidak terpotong overflow induk.
    document.body.append(panel);

    // Bulan yang ditampilkan (selalu tanggal 1) dan tanggal yang disorot navigasi keyboard.
    let tampil = new Date(hariIni().getFullYear(), hariIni().getMonth(), 1);
    let sorot = hariIni();

    const gambar = () => {
        label.textContent = `${NAMA_BULAN[tampil.getMonth()]} ${tampil.getFullYear()}`;

        const terpilih = bacaNilai(input.value);
        // Senin sebagai awal pekan: getDay() 0 = Minggu, jadi digeser 6.
        const offset = (new Date(tampil.getFullYear(), tampil.getMonth(), 1).getDay() + 6) % 7;
        const mulai = new Date(tampil.getFullYear(), tampil.getMonth(), 1 - offset);
        const tombol = [];

        // 6 baris × 7 hari agar tinggi panel tetap saat berpindah bulan.
        for (let i = 0; i < 42; i += 1) {
            const tanggal = new Date(mulai.getFullYear(), mulai.getMonth(), mulai.getDate() + i);
            const sel = document.createElement('button');
            sel.type = 'button';
            sel.className = 'dp-day';
            sel.textContent = tanggal.getDate();
            sel.dataset.tanggal = formatNilai(tanggal);
            sel.tabIndex = -1;
            sel.setAttribute('aria-pressed', String(samaHari(tanggal, terpilih)));
            sel.setAttribute('aria-label', tanggal.toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            }));

            if (tanggal.getMonth() !== tampil.getMonth()) {
                sel.classList.add('dp-day-luar');
            }

            if (samaHari(tanggal, terpilih)) {
                sel.classList.add('dp-day-terpilih');
            }

            if (samaHari(tanggal, hariIni())) {
                sel.classList.add('dp-day-ini');
            }

            if (samaHari(tanggal, sorot)) {
                sel.classList.add('dp-day-sorot');
            }

            sel.addEventListener('click', () => pilih(tanggal));
            tombol.push(sel);
        }

        grid.replaceChildren(...tombol);
    };

    // Panel fixed: posisinya dihitung dari input; bila ruang bawah sempit dibuka ke atas.
    const posisi = () => {
        if (panel.hidden) {
            return;
        }

        const kotak = wrap.getBoundingClientRect();
        const lebar = panel.offsetWidth;
        const tinggi = panel.offsetHeight;
        const ruangBawah = window.innerHeight - kotak.bottom;

        panel.style.left = `${Math.max(8, Math.min(kotak.left, window.innerWidth - lebar - 8))}px`;
        panel.style.top = ruangBawah < tinggi + 12 && kotak.top > ruangBawah
            ? `${Math.max(8, kotak.top - tinggi - 6)}px`
            : `${Math.min(kotak.bottom + 6, Math.max(8, window.innerHeight - tinggi - 8))}px`;
    };

    // Nilai input ditulis DD-MM-YYYY lalu event input/change dipicu agar masker dan skrip lain ikut.
    const pilih = (tanggal) => {
        input.value = formatNilai(tanggal);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        tutup(true);
    };

    const buka = () => {
        if (input.disabled || input.readOnly || !panel.hidden) {
            return;
        }

        const terpilih = bacaNilai(input.value);
        const acuan = terpilih ?? hariIni();

        tampil = new Date(acuan.getFullYear(), acuan.getMonth(), 1);
        sorot = acuan;

        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        gambar();
        posisi();

        // Fokus ke tanggal tersorot agar navigasi panah keyboard langsung bekerja.
        grid.querySelector('.dp-day-sorot')?.focus({ preventScroll: true });

        window.addEventListener('scroll', posisi, true);
        window.addEventListener('resize', posisi);
    };

    const tutup = (kembaliKeInput = false) => {
        if (panel.hidden) {
            return;
        }

        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        window.removeEventListener('scroll', posisi, true);
        window.removeEventListener('resize', posisi);

        if (kembaliKeInput) {
            input.focus({ preventScroll: true });
        }
    };

    // Pindah bulan/tahun: sorotan tetap pada tanggal yang sama, dibatasi akhir bulan tujuan.
    const geser = (langkah) => {
        tampil = new Date(tampil.getFullYear(), tampil.getMonth() + langkah, 1);

        const hariMaks = new Date(tampil.getFullYear(), tampil.getMonth() + 1, 0).getDate();
        sorot = new Date(tampil.getFullYear(), tampil.getMonth(), Math.min(sorot.getDate(), hariMaks));

        gambar();
        grid.querySelector('.dp-day-sorot')?.focus({ preventScroll: true });
        posisi();
    };

    [prevTahun, prevBulan, nextBulan, nextTahun].forEach((tombol) => {
        tombol.addEventListener('click', () => geser(Number(tombol.dataset.arah)));
    });

    tombolIni.addEventListener('click', () => pilih(hariIni()));

    trigger.addEventListener('click', () => (panel.hidden ? buka() : tutup(true)));
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            buka();
        } else if (event.key === 'Escape') {
            tutup();
        }
    });

    // Navigasi keyboard di panel: panah pindah hari/pekan, PageUp/PageDown pindah bulan,
    // Escape menutup. Enter/Spasi memilih lewat aktivasi tombol bawaan.
    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            tutup(true);

            return;
        }

        if (event.key === 'PageUp' || event.key === 'PageDown') {
            event.preventDefault();
            geser(event.key === 'PageUp' ? -1 : 1);

            return;
        }

        const langkah = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[event.key];

        if (langkah === undefined) {
            return;
        }

        event.preventDefault();

        const berikut = new Date(sorot.getFullYear(), sorot.getMonth(), sorot.getDate() + langkah);
        sorot = berikut;

        if (berikut.getMonth() !== tampil.getMonth() || berikut.getFullYear() !== tampil.getFullYear()) {
            tampil = new Date(berikut.getFullYear(), berikut.getMonth(), 1);
        }

        gambar();
        grid.querySelector('.dp-day-sorot')?.focus({ preventScroll: true });
        posisi();
    });

    // Klik di luar komponen menutup panel. composedPath() dipakai karena tombol tanggal
    // sudah terlepas dari DOM (grid digambar ulang) saat event sampai ke document.
    document.addEventListener('click', (event) => {
        const jalur = typeof event.composedPath === 'function' ? event.composedPath() : [];

        if (jalur.includes(panel) || jalur.includes(wrap) || wrap.contains(event.target) || panel.contains(event.target)) {
            return;
        }

        tutup();
    });

    // Isian wajib yang kosong: buka kalender sebagai umpan balik, seperti dropdown pencarian.
    const form = input.closest('form');

    if (form) {
        form.addEventListener('submit', (event) => {
            if (input.required && !input.disabled && input.value.trim() === '' && !event.defaultPrevented) {
                event.preventDefault();
                buka();
            }
        });
    }

    trigger.disabled = input.disabled;
}

