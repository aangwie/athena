<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleSelectionController extends Controller
{
    /**
     * Tampilkan pop-up pemilihan peran untuk user multi-role.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $roles = $request->user()->roleList();

        if (count($roles) < 2) {
            return redirect()->route('dashboard');
        }

        return view('auth.pilih-role', ['roles' => $roles]);
    }

    /**
     * Simpan peran yang dipilih sebagai peran aktif pada sesi ini.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in($request->user()->roleList())],
        ], [
            'role.required' => 'Pilih salah satu peran untuk melanjutkan.',
            'role.in' => 'Peran yang dipilih tidak tersedia untuk akun Anda.',
        ]);

        $request->session()->put('active_role', $validated['role']);

        return redirect()->route('dashboard')
            ->with('status', 'Anda masuk sebagai '.User::ROLES[$validated['role']].'.');
    }
}
