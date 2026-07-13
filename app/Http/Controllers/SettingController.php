<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function updateCompany(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_npwp' => ['nullable', 'string', 'max:30'],
            'company_nib' => ['nullable', 'string', 'max:50'],
            'company_izin' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_phone' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_pjt' => ['nullable', 'string', 'max:255'],
            'company_kbli' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::putMany($data);

        return back()->with('status', 'Profil perusahaan berhasil disimpan.');
    }

    /** Rekening pembayaran & penanda tangan dokumen. */
    public function updateBank(Request $request)
    {
        $data = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'director_name' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::putMany($data);

        return back()->with('status', 'Info pembayaran & tanda tangan berhasil disimpan.');
    }

    public function updateTax(Request $request)
    {
        $data = $request->validate([
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'fp_transaction_code' => ['required', 'string', 'max:5'],
            'fp_prefix' => ['nullable', 'string', 'max:20'],
        ]);

        Setting::putMany($data);

        return back()->with('status', 'Pengaturan pajak berhasil disimpan.');
    }
}
