import Swal from 'sweetalert2';

// Halaman yang dibiarkan terbuka lama menyimpan token CSRF yang bisa kedaluwarsa
// (sesi habis atau cookie terhapus). Modul ini menyegarkan token secara berkala dan
// menampilkan ajakan memuat ulang ketika server tetap menjawab 419 Page Expired.

const UMUR_SEGAR = 10 * 60 * 1000; // token disegarkan paling lama tiap 10 menit
const UMUR_TERLAMBAT = 2 * 60 * 1000; // tab dianggap "kembali" setelah 2 menit disembunyikan
const UMUR_AKTIF = 15 * 60 * 1000; // hanya menyegarkan selama pengguna memang aktif

let terakhirSegar = Date.now();
let terakhirAktif = Date.now();
let permintaanSegar = null;
let dialogTampil = false;

const metaToken = () => document.querySelector('meta[name="csrf-token"]');
const urlSegar = () => document.querySelector('meta[name="csrf-refresh-url"]')?.content ?? '';

const simpanToken = (token) => {
    const meta = metaToken();

    if (meta) {
        meta.content = token;
    }

    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
};

export const segarkanTokenCsrf = () => {
    if (permintaanSegar) {
        return permintaanSegar;
    }

    const url = urlSegar();

    if (!url) {
        return Promise.resolve();
    }

    permintaanSegar = fetch(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        credentials: 'same-origin',
    })
        .then((respons) => (respons.ok ? respons.json() : null))
        .then((data) => {
            if (data?.token) {
                simpanToken(data.token);
                terakhirSegar = Date.now();
            }
        })
        .catch(() => {
            // Offline atau server menolak: biarkan token lama, form tetap bisa dicoba.
        })
        .finally(() => {
            permintaanSegar = null;
        });

    return permintaanSegar;
};

export const tampilkanSesiBerakhir = () => {
    if (dialogTampil) {
        return;
    }

    dialogTampil = true;

    Swal.fire({
        title: 'Sesi Anda telah berakhir',
        text: 'Halaman ini sudah terlalu lama dibiarkan terbuka. Muat ulang halaman agar Anda dapat melanjutkan.',
        icon: 'warning',
        confirmButtonText: 'Muat ulang halaman',
        confirmButtonColor: '#0d9488',
        allowOutsideClick: false,
        allowEscapeKey: false,
    }).then(() => window.location.reload());
};

// Semua fetch (notifikasi, WhatsApp, dsb.) ikut terpantau: jawaban 419 langsung
// ditangani dengan dialog muat ulang, bukan gagal diam-diam.
const fetchAsli = window.fetch?.bind(window);

if (fetchAsli) {
    window.fetch = async (...args) => {
        const respons = await fetchAsli(...args);

        if (respons.status === 419) {
            tampilkanSesiBerakhir();
        }

        return respons;
    };
}

// Permintaan axios (unggah berkas, progres backup database, dsb.).
if (window.axios) {
    // Token selalu diambil dari meta saat permintaan dikirim, bukan saat halaman dimuat.
    window.axios.interceptors.request.use((config) => {
        const token = metaToken()?.content;

        if (token) {
            config.headers = config.headers ?? {};
            config.headers['X-CSRF-TOKEN'] = token;
        }

        return config;
    });

    window.axios.interceptors.response.use(null, (error) => {
        if (error?.response?.status === 419) {
            tampilkanSesiBerakhir();
        }

        return Promise.reject(error);
    });
}

const mulai = () => {
    if (!metaToken()) {
        return;
    }

    // Penyegaran hanya dilakukan saat pengguna aktif, supaya sesi yang ditinggal
    // tetap kedaluwarsa sesuai SESSION_LIFETIME (tidak "dihidupkan" oleh polling).
    ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((nama) => {
        document.addEventListener(nama, () => {
            terakhirAktif = Date.now();
        }, { passive: true });
    });

    window.setInterval(() => {
        const sekarang = Date.now();

        if (
            document.visibilityState === 'visible'
            && sekarang - terakhirSegar >= UMUR_SEGAR
            && sekarang - terakhirAktif <= UMUR_AKTIF
        ) {
            segarkanTokenCsrf();
        }
    }, 60 * 1000);

    let disembunyikanSejak = null;

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            disembunyikanSejak = Date.now();
            return;
        }

        const lamaTersembunyi = disembunyikanSejak ? Date.now() - disembunyikanSejak : 0;
        disembunyikanSejak = null;
        terakhirAktif = Date.now();

        // Pengguna kembali ke tab: segarkan token sebelum ia mengirim form lagi.
        if (lamaTersembunyi >= UMUR_TERLAMBAT) {
            segarkanTokenCsrf();
        }
    });

    // Halaman dipulihkan dari back/forward cache: token di DOM sudah usang.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            segarkanTokenCsrf();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mulai);
} else {
    mulai();
}