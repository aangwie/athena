{{-- Blok identitas pada lembar validasi: label : nilai. TMT sudah menyatu pada
     nilai labelnya (mis. "Tenaga Pengajar, 25 September 2023"), bukan kolom
     terpisah di kanan. Parameter: $baris (array pasangan [label, nilai]). --}}
<table class="mt-1 w-full border-collapse text-[16px] leading-tight">
    <tbody>
        @foreach ($baris as [$label, $nilai])
            <tr class="align-top">
                <td class="w-[45%] align-top">{{ $label }}</td>
                <td class="w-3 align-top">:</td>
                <td class="align-top font-semibold">{{ $nilai }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

