/**
 * Pengambil tangkapan layar buku manual Athena.
 *
 * Memakai Chrome headless melalui Chrome DevTools Protocol sehingga tidak
 * memerlukan dependensi tambahan (tanpa Puppeteer/Playwright).
 *
 * Pemakaian:
 *   node docs/manual/tools/capture.mjs phase1
 *   node docs/manual/tools/capture.mjs phase2
 *
 * Prasyarat: aplikasi berjalan di http://127.0.0.1:8123 dan password akun demo
 * sudah diganti sementara oleh `php docs/manual/tools/helper.php passwords-set`.
 * Setelah selesai, kembalikan dengan `php docs/manual/tools/helper.php passwords-restore`
 * dan hapus ajuan contoh memakai `php docs/manual/tools/helper.php demo-delete`.
 */

import { spawn } from 'node:child_process';
import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const BASE = process.env.MANUAL_BASE_URL ?? 'http://127.0.0.1:8123';
const OUT = path.resolve('docs/manual/images');
const CHROME = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_PORT = Number(process.env.MANUAL_CDP_PORT ?? 9333);
let portCounter = 0;
const PASSWORD = 'AthenaManual2026!';
const PHASE = process.argv[2] ?? 'phase1';
const DEMO_FILE = path.resolve('storage/app/manual-shots/demo-ids.json');

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

class Cdp {
    constructor(url) {
        this.url = url;
        this.id = 0;
        this.pending = new Map();
    }

    async connect() {
        this.ws = new WebSocket(this.url);
        await new Promise((resolve, reject) => {
            this.ws.addEventListener('open', resolve, { once: true });
            this.ws.addEventListener('error', reject, { once: true });
        });

        this.ws.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);
            const waiter = this.pending.get(message.id);

            if (!waiter) {
                return;
            }

            this.pending.delete(message.id);

            if (message.error) {
                waiter.reject(new Error(`${waiter.method}: ${message.error.message}`));
            } else {
                waiter.resolve(message.result ?? {});
            }
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++this.id;
            this.pending.set(id, { resolve, reject, method });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    close() {
        try {
            this.ws.close();
        } catch {
            // abaikan
        }
    }
}

async function waitForHttp(url, timeout = 20000) {
    const start = Date.now();

    while (Date.now() - start < timeout) {
        try {
            const response = await fetch(url);

            if (response.ok) {
                return await response.json();
            }
        } catch {
            // Chrome belum siap, ulangi.
        }

        await sleep(200);
    }

    throw new Error(`Chrome tidak merespons di ${url}`);
}

async function openTarget(port) {
    const response = await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' });

    if (!response.ok) {
        throw new Error(`Gagal membuka tab Chrome (${response.status})`);
    }

    return response.json();
}

async function launchChrome(role, port) {
    if (!existsSync(CHROME)) {
        throw new Error(`Chrome tidak ditemukan di ${CHROME}`);
    }

    const profile = path.join(os.tmpdir(), `athena-manual-${role}-${Date.now()}`);

    const process = spawn(
        CHROME,
        [
            '--headless=new',
            `--remote-debugging-port=${port}`,
            `--user-data-dir=${profile}`,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--hide-scrollbars',
            '--force-device-scale-factor=1',
            '--window-size=1440,960',
            'about:blank',
        ],
        { stdio: 'ignore' },
    );

    await waitForHttp(`http://127.0.0.1:${port}/json/version`);

    return { process, profile };
}


class Page {
    constructor(cdp) {
        this.cdp = cdp;
    }

    async evaluate(expression) {
        const result = await this.cdp.send('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true,
            userGesture: true,
        });

        if (result.exceptionDetails) {
            const detail = result.exceptionDetails.exception?.description ?? result.exceptionDetails.text;
            throw new Error(`Evaluasi gagal: ${detail}`);
        }

        return result.result?.value;
    }

    async waitUntil(check, timeout = 20000, label = 'kondisi halaman') {
        const start = Date.now();

        while (Date.now() - start < timeout) {
            try {
                if (await check()) {
                    return;
                }
            } catch {
                // halaman sedang berpindah, ulangi.
            }

            await sleep(150);
        }

        throw new Error(`Waktu habis menunggu ${label}`);
    }

    async navigate(target) {
        const url = target.startsWith('http') ? target : BASE + target;

        await this.cdp.send('Page.navigate', { url });
        await this.waitUntil(async () => (await this.evaluate('document.readyState')) === 'complete', 25000, url);

        await this.evaluate(`(() => {
            const style = document.createElement('style');
            style.textContent = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';
            document.head.appendChild(style);
            return document.fonts.ready.then(() => true);
        })()`);

        await sleep(1200);
    }

    async login(nidn, role = null) {
        await this.navigate('/login');

        await this.evaluate(`(() => {
            document.querySelector('input[name="nidn"]').value = ${JSON.stringify(nidn)};
            document.querySelector('input[name="password"]').value = ${JSON.stringify(PASSWORD)};
            document.querySelector('form').submit();
            return true;
        })()`);

        await this.waitUntil(
            async () => ['/dashboard', '/pilih-role'].includes(await this.evaluate('location.pathname')),
            25000,
            'halaman setelah login',
        );

        await sleep(800);

        if (role) {
            await this.evaluate(`(() => {
                const form = [...document.querySelectorAll('form')]
                    .find((item) => item.querySelector('input[name="role"][value="${role}"]'));

                if (!form) {
                    throw new Error('Form peran ${role} tidak ditemukan');
                }

                form.submit();
                return true;
            })()`);

            await this.waitUntil(async () => (await this.evaluate('location.pathname')) === '/dashboard', 25000, 'dashboard peran');
            await sleep(900);
        }
    }

    async screenshot(file) {
        const { data } = await this.cdp.send('Page.captureScreenshot', {
            format: 'png',
            fromSurface: true,
            captureBeyondViewport: false,
        });

        await writeFile(path.join(OUT, file), Buffer.from(data, 'base64'));
    }
}

const tabClick = (tab) => async (page) => {
    await page.evaluate(`(() => { document.querySelector('#tab-${tab}')?.click(); return true; })()`);
    await sleep(600);
};

const jobs = {
    public: [
        { file: '01-halaman-masuk.png', url: '/login' },
    ],

    dosen: [
        { file: '02-dashboard-dosen.png', url: '/dashboard' },
        { file: '03-profil-portofolio.png', url: '/profil' },
        { file: '04-data-pribadi.png', url: '/profil/data-pribadi' },
        { file: '05-riwayat-pendidikan.png', url: '/profil/riwayat-pendidikan' },
        { file: '06-tambah-pendidikan.png', url: '/profil/riwayat-pendidikan/create' },
        { file: '07-riwayat-pekerjaan.png', url: '/profil/riwayat-pekerjaan' },
        { file: '08-status-jabatan.png', url: '/profil/pengajuan' },
        { file: '09-pilih-periode.png', url: '/profil/pengajuan/periode' },
        { file: '10-masa-penilaian.png', url: '/profil/pengajuan/masa-penilaian?tahun=2026&semester=ganjil' },
        { file: '11-pengajuan-baru.png', url: '/profil/pengajuan/create?tahun=2026&semester=ganjil' },
        { file: '12-form-penelitian.png', url: '/profil/pengajuan/create?tahun=2026&semester=ganjil', prepare: tabClick('penelitian') },
        { file: '13-form-review.png', url: '/profil/pengajuan/create?tahun=2026&semester=ganjil', prepare: tabClick('review') },
        { file: '14-riwayat-pengajuan.png', url: '/profil/pengajuan/riwayat' },
        { file: '15-riwayat-ikd.png', url: '/profil/pengajuan/riwayat-ikd' },
        { file: '16-panduan-pengajuan.png', url: '/panduan-pengajuan' },
    ],

    dosen2: [
        { file: '17-karya-tulis.png', url: '/profil/riwayat-karya-tulis' },
        { file: '18-syarat-khusus.png', url: '/profil/pengajuan/syarat-khusus?tahun=2026&semester=ganjil' },
    ],

    admin: [
        { file: '21-data-dosen.png', url: '/data-dosen' },
        { file: '22-tambah-data-dosen.png', url: '/data-dosen/create' },
        { file: '23-import-data-dosen.png', url: '/data-dosen/import' },
        { file: '24-riwayat-dosen.png', url: '/riwayat-dosen' },
        { file: '25-detail-riwayat-dosen.png', url: '/riwayat-dosen/34' },
        { file: '26-verifikasi-berkas.png', url: '/verifikasi-berkas' },
        { file: '29-surat.png', url: '/surat' },
        { file: '30-skp.png', url: '/skp' },
        { file: '31-jabatan-fungsional.png', url: '/kepegawaian/jabatan-fungsional' },
        { file: '32-jabatan-struktural.png', url: '/kepegawaian/jabatan-struktural' },
        { file: '33-kepangkatan.png', url: '/kepegawaian/kepangkatan' },
        { file: '34-data-instansi.png', url: '/instansi' },
        { file: '35-pejabat-struktural.png', url: '/instansi/struktur' },
        { file: '36-senat.png', url: '/instansi/senat' },
        { file: '37-tim-komite.png', url: '/instansi/tim-komite' },
        { file: '38-prodi.png', url: '/instansi/prodi' },
        { file: '39-panduan-penilaian.png', url: '/panduan-penilaian' },
        { file: '40-pengaturan.png', url: '/pengaturan' },
        { file: '41-whatsapp-api.png', url: '/whatsapp-api' },
    ],

    timpak: [
        { file: '46-panduan-penilaian-tim-pak.png', url: '/panduan-penilaian' },
    ],

    skpDosen: [
        { file: '47-skp-dosen.png', url: '/skp-dosen' },
        { file: '48-keberatan-skp.png', url: '/skp-dosen/1/keberatan' },
    ],

    skpUsulan: [
        { file: '49-usulan-skp.png', url: '/skp-usulan' },
    ],
};

const akun = {
    dosen: { nidn: '0711068304', role: null },
    dosen2: { nidn: '0721048704', role: null },
    admin: { nidn: '0000000001', role: null },
    timpak: { nidn: '0708048703', role: 'tim_pak' },
    skpDosen: { nidn: '0713019003', role: null },
    skpUsulan: { nidn: '0708098001', role: null },
};

async function readDemoIds() {
    if (!existsSync(DEMO_FILE)) {
        throw new Error(`Berkas ${DEMO_FILE} belum ada. Jalankan demo-create terlebih dahulu.`);
    }

    return JSON.parse(await readFile(DEMO_FILE, 'utf8'));
}

async function main() {
    await mkdir(OUT, { recursive: true });

    let failures = 0;
    let total = 0;

    const run = async (role, daftar) => {
        const port = BASE_PORT + ++portCounter;
        const { process, profile } = await launchChrome(role, port);

        try {
            const target = await openTarget(port);
            const cdp = new Cdp(target.webSocketDebuggerUrl);

            await cdp.connect();
            await cdp.send('Page.enable');
            await cdp.send('Runtime.enable');
            await cdp.send('Emulation.setDeviceMetricsOverride', {
                width: 1440,
                height: 960,
                deviceScaleFactor: 1,
                mobile: false,
            });

            const page = new Page(cdp);

            if (role !== 'public') {
                const data = akun[role];
                await page.login(data.nidn, data.role);

                if (role === 'timpak') {
                    // Halaman pilih peran hanya tampil sekali saat login multi-peran.
                    await page.navigate('/pilih-role');
                    await page.screenshot('42-pilih-peran.png');
                }
            }

            for (const job of daftar) {
                total += 1;

                try {
                    await page.navigate(job.url);

                    if (job.prepare) {
                        await job.prepare(page);
                    }

                    await page.screenshot(job.file);
                    console.log(`  OK   ${job.file}  ${job.url}`);
                } catch (error) {
                    failures += 1;
                    console.error(`  GAGAL ${job.file}  ${job.url}  -> ${error.message}`);
                }
            }

            cdp.close();
        } finally {
            spawn('taskkill', ['/PID', String(process.pid), '/T', '/F'], { stdio: 'ignore' });
            await sleep(1200);
            await rm(profile, { recursive: true, force: true }).catch(() => {});
        }
    };

    const daftarPhase1 = [
        ['public', jobs.public],
        ['dosen', jobs.dosen],
        ['dosen2', jobs.dosen2],
        ['admin', jobs.admin],
        ['timpak', jobs.timpak],
        ['skpDosen', jobs.skpDosen],
        ['skpUsulan', jobs.skpUsulan],
    ];

    if (PHASE === 'phase1') {
        for (const [role, daftar] of daftarPhase1) {
            console.log(`\n== Peran ${role} ==`);
            await run(role, daftar);
        }
    } else if (PHASE === 'phase2') {
        const demo = await readDemoIds();
        const idDiajukan = demo.pengajuan[0];
        const idDiajukanDupak = demo.pengajuan[2];

        console.log('\n== Peran admin (data contoh) ==');
        await run('admin', [
            { file: '20-dashboard-admin.png', url: '/dashboard' },
            { file: '27-ajuan-pak.png', url: '/ajuan-pak' },
            { file: '28-verifikasi-dupak.png', url: `/verifikasi-dupak/${idDiajukanDupak}` },
        ]);

        console.log('\n== Peran timpak (data contoh) ==');
        await run('timpak', [
            { file: '43-dashboard-tim-pak.png', url: '/dashboard' },
            { file: '44-pengajuan-masuk.png', url: '/tim-pak/pengajuan' },
            { file: '45-nilai-pengajuan.png', url: `/tim-pak/pengajuan/${idDiajukan}` },
        ]);
    } else {
        throw new Error('Fase tidak dikenal. Gunakan phase1 atau phase2.');
    }

    console.log(`\nSelesai: ${total} tangkapan layar, ${failures} gagal.`);

    if (failures > 0) {
        process.exitCode = 1;
    }
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});


