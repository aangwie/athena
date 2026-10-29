@extends('layouts.app')

@section('title', 'Data Dosen')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Data Dosen')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Administrasi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Data Dosen</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Kelola data dosen: nama, NIDN, password, dan pangkat.</p>
            </div>
            <a href="{{ route('data-dosen.create') }}" class="btn-primary w-auto px-5 whitespace-nowrap">Tambah Data</a>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="section-card">
            <div class="overflow-x-auto">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="w-20">ID</th>
                            <th scope="col">Nama Dosen</th>
                            <th scope="col">NIDN</th>
                            <th scope="col">Pangkat</th>
                            <th scope="col" class="no-sort text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-5 py-3 font-mono text-slate-500">#{{ $item->id }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $item->nidn }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $item->jabatan_fungsional }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('data-dosen.edit', $item->id) }}" class="btn-secondary px-3 py-1.5 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('data-dosen.destroy', $item->id) }}"
                                              data-confirm="Hapus data dosen {{ $item->name }}? Tindakan ini tidak dapat dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-secondary px-3 py-1.5 text-xs text-rose-600">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada data dosen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection