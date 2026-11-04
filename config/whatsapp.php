<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nama sesi / device WhatsApp gateway
    |--------------------------------------------------------------------------
    |
    | Gateway Baileys multi-sesi memisahkan tiap perangkat dengan NAMA SESI, yang
    | dipakai pada /status/{sesi}, /qr/{sesi}, /disconnect/{sesi}, dan dikirim
    | sebagai device_id saat mengirim pesan.
    |
    | Bila beberapa deployment (mis. server lokal dan server hosting) memakai
    | gateway yang SAMA, beri nama sesi berbeda pada tiap .env agar memutus
    | koneksi (logout) di satu server tidak ikut memutus server lainnya.
    | Contoh: WHATSAPP_SESSION=local di server lokal, WHATSAPP_SESSION=hosting
    | di server hosting. Samakan nilainya hanya bila memang ingin berbagi satu
    | perangkat WhatsApp.
    |
    | Nama hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.
    */

    'session' => env('WHATSAPP_SESSION', 'default'),
];
