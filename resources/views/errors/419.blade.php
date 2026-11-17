@php
    $namaWeb = config('app.name', 'Athena');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0f766e">
    <title>Sesi Berakhir · {{ $namaWeb }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: "Plus Jakarta Sans", "Segoe UI", system-ui, -apple-system, sans-serif;
            color: #0f172a;
            background: linear-gradient(140deg, #0f766e 0%, #115e59 55%, #022c22 100%);
        }

        .kartu {
            width: 100%;
            max-width: 27rem;
            padding: 32px 28px;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 28px 60px -20px rgba(2, 44, 34, .55);
            text-align: center;
        }

        .ikon {
            width: 60px;
            height: 60px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
        }

        .ikon svg {
            width: 30px;
            height: 30px;
        }

        .kode {
            margin: 0 0 6px;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #0d9488;
        }

        h1 {
            margin: 0;
            font-size: 1.375rem;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        p {
            margin: 12px 0 0;
            font-size: .875rem;
            line-height: 1.65;
            color: #475569;
        }

        .catatan {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: .75rem;
            line-height: 1.6;
            color: #64748b;
            text-align: left;
        }

        .tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 22px;
            padding: 13px 18px;
            border: 0;
            border-radius: 14px;
            font: inherit;
            font-size: .875rem;
            font-weight: 700;
            color: #ffffff;
            background: #0d9488;
            cursor: pointer;
            transition: background-color .15s ease;
        }

        .tombol:hover {
            background: #0f766e;
        }

        .tautan {
            display: inline-block;
            margin-top: 14px;
            font-size: .8125rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
        }

        .tautan:hover {
            color: #0f766e;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="kartu">
        <div class="ikon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7.5V12l3 1.8" />
            </svg>
        </div>

        <p class="kode">419 · Page Expired</p>
        <h1>Sesi Anda telah berakhir</h1>
        <p>
            Halaman ini dibiarkan terbuka terlalu lama sehingga token keamanan (CSRF) sudah tidak berlaku.
            Data yang Anda kirim belum tersimpan.
        </p>

        <div class="catatan">
            Klik tombol di bawah untuk memuat ulang halaman, lalu ulangi pengisian form. Bila pesan ini
            sering muncul, pastikan cookie browser diaktifkan dan waktu perangkat Anda sudah benar.
        </div>

        <button type="button" class="tombol" onclick="window.location.reload()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 11a8 8 0 1 0-2.3 5.7" />
                <path d="M20 5v6h-6" />
            </svg>
            Muat ulang halaman
        </button>

        <a class="tautan" href="{{ url('/') }}">Kembali ke halaman awal</a>
    </main>
</body>
</html>