<?php

namespace App\Http\Requests;

class KegiatanPengabdianRequest extends KegiatanBidangRequest
{
    protected const JENIS = 'JENIS_PENGABDIAN';

    protected const SUB = 'SUB_PENGABDIAN';

    protected const RINCIAN = 'RINCIAN_PENGABDIAN';

    protected const BIDANG = 'bidang pengabdian kepada masyarakat';
}
