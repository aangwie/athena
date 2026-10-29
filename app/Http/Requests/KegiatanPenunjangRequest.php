<?php

namespace App\Http\Requests;

class KegiatanPenunjangRequest extends KegiatanBidangRequest
{
    protected const JENIS = 'JENIS_PENUNJANG';

    protected const SUB = 'SUB_PENUNJANG';

    protected const RINCIAN = 'RINCIAN_PENUNJANG';

    protected const BIDANG = 'bidang penunjang';
}
