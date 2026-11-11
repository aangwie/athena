{{-- Blok identitas pada lembar validasi: label : nilai, dengan nilai TMT
     ditampilkan rata kanan pada baris yang sama. Parameter: $baris (array label/nilai/tmt). --}}
<table class="mt-1 w-full border-collapse text-[16px] leading-tight">
    <tbody>
        @foreach ($baris as [$label, $nilai, $tmt])
            <tr class="align-top">
                <td class="w-[45%] align-top">{{ $label }}</td>
                <td class="w-3 align-top">:</td>
                <td class="align-top font-semibold">{{ $nilai }}</td>
                @if ($tmt !== null && $tmt !== '' && $tmt !== '—')
                    <td class="w-[22%] text-right align-top whitespace-nowrap">TMT : {{ $tmt }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
