import QRCode from 'qrcode';

(() => {
            const root = document.querySelector('[data-wa-page]');

            if (!root) {
                return;
            }

            const urlStatus = root.dataset.statusUrl;
            const urlQr = root.dataset.qrUrl;
            const urlKirim = root.dataset.kirimUrl;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const $ = (selector) => root.querySelector(selector);

            const gatewayBadge = $('[data-wa-gateway-badge]');
            const gatewayEndpoint = $('[data-wa-gateway-endpoint]');
            const gatewayLatency = $('[data-wa-gateway-latency]');
            const gatewayTime = $('[data-wa-gateway-time]');
            const gatewayMessage = $('[data-wa-gateway-message]');

            const connectionBadge = $('[data-wa-connection-badge]');
            const connectionNumber = $('[data-wa-connection-number]');
            const connectionName = $('[data-wa-connection-name]');
            const connectionTime = $('[data-wa-connection-time]');
            const connectionMessage = $('[data-wa-connection-message]');

            const qrImage = $('[data-wa-qr-image]');
            const qrPlaceholder = $('[data-wa-qr-placeholder]');
            const qrPlaceholderText = $('[data-wa-qr-placeholder-text]');
            const qrLoading = $('[data-wa-qr-loading]');
            const qrStatus = $('[data-wa-qr-status]');
            const qrEndpoint = $('[data-wa-qr-endpoint]');
            const qrTime = $('[data-wa-qr-time]');
            const qrLink = $('[data-wa-qr-link]');
            const raw = $('[data-wa-raw]');

            const testForm = $('[data-wa-test-form]');
            const testSubmit = $('[data-wa-test-submit]');
            const testIcon = $('[data-wa-test-icon]');
            const testSpinner = $('[data-wa-test-spinner]');
            const testLabel = $('[data-wa-test-label]');
            const testError = $('[data-wa-test-error]');
            const testResult = $('[data-wa-test-result]');
            const testBadge = $('[data-wa-test-badge]');
            const testHttp = $('[data-wa-test-http]');
            const testDuration = $('[data-wa-test-duration]');
            const testNumber = $('[data-wa-test-number]');
            const testMessage = $('[data-wa-test-message]');
            const testEndpoint = $('[data-wa-test-endpoint]');
            const testRaw = $('[data-wa-test-raw]');
            const counter = $('[data-wa-counter]');
            const pesanInput = $('#wa_pesan');

            const kelasBadge = {
                netral: 'inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500',
                ok: 'inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-bold text-teal-700 ring-1 ring-teal-200',
                gagal: 'inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600 ring-1 ring-rose-200',
                tunggu: 'inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200',
            };

            const setBadge = (element, teks, warna = 'netral') => {
                if (!element) {
                    return;
                }

                element.className = kelasBadge[warna] ?? kelasBadge.netral;
                element.textContent = teks;
            };

            const jam = (iso) => {
                if (!iso) {
                    return '—';
                }

                const tanggal = new Date(iso);

                return Number.isNaN(tanggal.getTime())
                    ? '—'
                    : tanggal.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            };

            const teksAtau = (nilai, fallback = '—') => {
                if (nilai === null || nilai === undefined || nilai === '') {
                    return fallback;
                }

                return String(nilai);
            };

            let statusTerakhirTerhubung = null;
            let sibukStatus = false;
            let sibukQr = false;
            let timerQr = null;

            const kosongkanQr = (pesan) => {
                if (qrImage) {
                    qrImage.classList.add('hidden');
                    qrImage.removeAttribute('src');
                }

                if (qrPlaceholder) {
                    qrPlaceholder.classList.remove('hidden');
                }

                if (qrPlaceholderText) {
                    qrPlaceholderText.textContent = pesan;
                }
            };

            const tampilkanQrTerhubung = () => {
                kosongkanQr('Perangkat WhatsApp sudah terhubung. QR code tidak diperlukan.');
                setBadge(connectionBadge, 'Terhubung', 'ok');

                if (qrStatus) {
                    qrStatus.textContent = 'Sesi WhatsApp aktif — tidak perlu memindai QR code.';
                }

                if (qrLink) {
                    qrLink.classList.add('hidden');
                    qrLink.classList.remove('inline-flex');
                }
            };

            const tampilkanGambarQr = (src, pesan) => {
                qrImage.src = src;
                qrImage.classList.remove('hidden');
                qrPlaceholder.classList.add('hidden');
                qrStatus.textContent = pesan;
            };

            const renderQr = async (data) => {
                if (!qrStatus) {
                    return;
                }

                if (qrEndpoint) {
                    qrEndpoint.textContent = teksAtau(data.endpoint);
                }

                if (qrTime) {
                    qrTime.textContent = jam(data.diperiksa_pada ?? new Date().toISOString());
                }

                if (qrLink) {
                    if (data.endpoint) {
                        qrLink.href = data.endpoint;
                        qrLink.classList.remove('hidden');
                        qrLink.classList.add('inline-flex');
                    } else {
                        qrLink.classList.add('hidden');
                        qrLink.classList.remove('inline-flex');
                    }
                }

                if (data.tersedia && data.qr) {
                    tampilkanGambarQr(data.qr, data.pesan || 'QR code siap dipindai.');

                    return;
                }

                // Gateway seperti Baileys mengirim QR sebagai teks, bukan gambar.
                if (data.qr_teks) {
                    try {
                        const dataUrl = await QRCode.toDataURL(data.qr_teks, {
                            width: 512,
                            margin: 2,
                            errorCorrectionLevel: 'M',
                        });

                        tampilkanGambarQr(dataUrl, data.pesan || 'QR code siap dipindai.');

                        return;
                    } catch (error) {
                        // Jatuh ke placeholder di bawah.
                    }
                }

                kosongkanQr(data.pesan || 'QR code belum tersedia.');
                qrStatus.textContent = data.pesan || 'QR code belum tersedia.';
            };

            const muatQr = async () => {
                if (sibukQr) {
                    return;
                }

                sibukQr = true;
                qrLoading?.classList.remove('hidden');

                try {
                    const respons = await fetch(urlQr, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    const data = await respons.json();
                    await renderQr(data);
                } catch (error) {
                    kosongkanQr('Gagal mengambil QR code dari gateway.');
                    qrStatus.textContent = 'Gagal mengambil QR code dari gateway.';
                } finally {
                    sibukQr = false;
                    qrLoading?.classList.add('hidden');
                }
            };

            const mulaiPollingQr = () => {
                if (timerQr !== null) {
                    return;
                }

                timerQr = window.setInterval(() => {
                    if (!document.hidden) {
                        muatQr();
                    }
                }, 20000);
            };

            const hentikanPollingQr = () => {
                if (timerQr === null) {
                    return;
                }

                window.clearInterval(timerQr);
                timerQr = null;
            };

            const renderStatus = (data) => {
                const gateway = data.gateway ?? {};
                const whatsapp = data.whatsapp ?? {};

                setBadge(
                    gatewayBadge,
                    gateway.online ? 'Terkoneksi' : 'Tidak terhubung',
                    gateway.online ? 'ok' : 'gagal',
                );

                gatewayEndpoint.textContent = teksAtau(gateway.endpoint);
                gatewayLatency.textContent = gateway.latency_ms === null || gateway.latency_ms === undefined
                    ? '—'
                    : `${gateway.latency_ms} ms`;
                gatewayTime.textContent = jam(data.diperiksa_pada);
                gatewayMessage.textContent = teksAtau(gateway.pesan, 'Tidak ada keterangan.');

                if (!data.terkonfigurasi) {
                    setBadge(connectionBadge, 'Belum diatur', 'tunggu');
                } else if (!whatsapp.diketahui) {
                    setBadge(connectionBadge, 'Tidak diketahui', 'netral');
                } else if (whatsapp.terhubung) {
                    setBadge(connectionBadge, 'Terhubung', 'ok');
                } else if (whatsapp.status === 'menunggu') {
                    setBadge(connectionBadge, 'Menunggu QR', 'tunggu');
                } else {
                    setBadge(connectionBadge, 'Terputus', 'gagal');
                }

                connectionNumber.textContent = teksAtau(whatsapp.nomor);
                connectionName.textContent = teksAtau(whatsapp.nama);
                connectionTime.textContent = jam(data.diperiksa_pada);
                connectionMessage.textContent = !data.terkonfigurasi
                    ? 'Simpan URL gateway pada bagian di atas untuk memulai pemeriksaan.'
                    : (whatsapp.terhubung
                        ? 'Gateway siap dipakai mengirim pesan.'
                        : 'Pindai QR code di bawah untuk menautkan perangkat WhatsApp.');

                raw.textContent = data.respon === null || data.respon === undefined
                    ? 'Tidak ada respons dari gateway.'
                    : (typeof data.respon === 'string' ? data.respon : JSON.stringify(data.respon, null, 2));

                if (whatsapp.terhubung) {
                    statusTerakhirTerhubung = true;
                    hentikanPollingQr();
                    tampilkanQrTerhubung();
                } else {
                    if (statusTerakhirTerhubung !== false) {
                        statusTerakhirTerhubung = false;
                        muatQr();
                    }

                    mulaiPollingQr();
                }
            };

            const cekStatus = async () => {
                if (sibukStatus) {
                    return;
                }

                sibukStatus = true;

                try {
                    const respons = await fetch(urlStatus, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    const data = await respons.json();
                    renderStatus(data);
                } catch (error) {
                    setBadge(gatewayBadge, 'Tidak terhubung', 'gagal');
                    setBadge(connectionBadge, 'Tidak diketahui', 'netral');
                    gatewayMessage.textContent = 'Gagal menghubungi server aplikasi.';
                    connectionMessage.textContent = 'Gagal menghubungi server aplikasi.';
                    gatewayTime.textContent = jam(new Date().toISOString());
                    connectionTime.textContent = jam(new Date().toISOString());
                } finally {
                    sibukStatus = false;
                }
            };

            const renderHitungPesan = () => {
                if (!counter || !pesanInput) {
                    return;
                }

                const panjang = pesanInput.value.length;
                counter.textContent = `${panjang}/4096`;
                counter.classList.toggle('text-rose-500', panjang > 3900);
                counter.classList.toggle('text-slate-400', panjang <= 3900);
            };

            const setLoadingTest = (aktif) => {
                testSubmit.disabled = aktif;
                testIcon?.classList.toggle('hidden', aktif);
                testSpinner?.classList.toggle('hidden', !aktif);
                testLabel.textContent = aktif ? 'Mengirim…' : 'Kirim Pesan Uji';
            };

            const renderHasilUji = (data, httpStatus) => {
                const sukses = data.sukses === true;

                setBadge(testBadge, sukses ? 'Berhasil' : 'Gagal', sukses ? 'ok' : 'gagal');
                testHttp.textContent = data.http_status ?? (httpStatus || '—');
                testDuration.textContent = data.durasi_ms === null || data.durasi_ms === undefined
                    ? '—'
                    : `${data.durasi_ms} ms`;
                testNumber.textContent = teksAtau(data.nomor);
                testMessage.textContent = teksAtau(data.pesan, sukses ? 'Pesan diterima gateway.' : 'Pengiriman gagal.');
                testEndpoint.textContent = teksAtau(data.endpoint);

                testRaw.textContent = data.respon === null || data.respon === undefined
                    ? 'Tidak ada respons dari gateway.'
                    : (typeof data.respon === 'string' ? data.respon : JSON.stringify(data.respon, null, 2));

                testResult.classList.remove('border-rose-200');
                testResult.classList.toggle('border-teal-200', sukses);
                testResult.classList.toggle('border-rose-200', !sukses);
            };

            testForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const nomor = testForm.elements.nomor.value.trim();
                const pesan = testForm.elements.pesan.value.trim();

                if (nomor === '' || pesan === '') {
                    testForm.reportValidity();

                    return;
                }

                testError.classList.add('hidden');
                testError.textContent = '';
                setLoadingTest(true);

                try {
                    const respons = await fetch(urlKirim, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ nomor, pesan }),
                    });
                    const data = await respons.json();

                    if (data.errors) {
                        const pesanValidasi = Object.values(data.errors).flat()[0] ?? data.message ?? 'Data tidak valid.';
                        throw new Error(pesanValidasi);
                    }

                    renderHasilUji(data, respons.status);
                } catch (error) {
                    testError.textContent = error.message || 'Gagal mengirim pesan uji.';
                    testError.classList.remove('hidden');
                    setBadge(testBadge, 'Gagal', 'gagal');
                } finally {
                    setLoadingTest(false);
                }
            });

            pesanInput?.addEventListener('input', renderHitungPesan);
            renderHitungPesan();

            root.querySelector('[data-wa-qr-refresh]')?.addEventListener('click', () => {
                if (statusTerakhirTerhubung === true) {
                    tampilkanQrTerhubung();

                    return;
                }

                muatQr();
            });

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    cekStatus();
                }
            });

            cekStatus();
            window.setInterval(() => {
                if (!document.hidden) {
                    cekStatus();
                }
            }, 10000);
        })();