{{-- Blok identitas pada lembar validasi: label : nilai, dengan nilai TMT
     ditampilkan rata kanan pada baris yang sama. Parameter: $baris (array label/nilai/tmt). --}}
<table class="mt-1 w-full border-collapse text-[11px]">
    <tbody>
        @foreach ($baris as [$label, $nilai, $tmt])
            <tr class="align-top">
                <td class="w-[45%] py-0.5 align-top">{{ $label }}</td>
                <td class="w-3 py-0.5 align-top">:</td>
                <td class="py-0.5 align-top font-semibold">{{ $nilai }}</td>
                @if ($tmt !== null && $tmt !== '' && $tmt !== '—')
                    <td class="w-[22%] py-0.5 text-right align-top whitespace-nowrap">TMT : {{ $tmt }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
