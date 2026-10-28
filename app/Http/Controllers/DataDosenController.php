<?php

namespace App\Http\Controllers;

use App\Models\Kepangkatan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataDosenController extends Controller
{
    public function index(): View
    {
        return view('data-dosen.index', [
            'items' => User::query()
                ->where('role', 'dosen')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('data-dosen.form', [
            'item' => null,
            'pangkatOptions' => Kepangkatan::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create([
            'role' => 'dosen',
            'is_active' => true,
            'name' => $data['name'],
            'nidn' => $data['nidn'],
            'jabatan_fungsional' => $data['pangkat'],
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('data-dosen.form', [
            'item' => $this->find($id),
            'pangkatOptions' => Kepangkatan::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);
        $data = $this->validated($request, $item);

        $update = [
            'name' => $data['name'],
            'nidn' => $data['nidn'],
            'jabatan_fungsional' => $data['pangkat'],
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $item->update($update);

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->find($id)->delete();

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen berhasil dihapus.');
    }

    private function find(string $id): User
    {
        return User::query()->where('role', 'dosen')->findOrFail($id);
    }

    private function validated(Request $request, ?User $item = null): array
    {
        $uniqueNidn = Rule::unique('users', 'nidn');
        if ($item !== null) {
            $uniqueNidn->ignore($item->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nidn' => ['required', 'string', 'max:20', $uniqueNidn],
            'pangkat' => ['required', 'string', 'max:100'],
            'password' => [$item === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama Dosen wajib diisi.',
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN tersebut sudah terdaftar.',
            'pangkat.required' => 'Pangkat wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);
    }
}
