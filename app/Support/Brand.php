<?php

namespace App\Support;

use App\Models\Setting;

class Brand
{
    /** Logo sebagai data URI (aman untuk dompdf). */
    public static function logoDataUri(): ?string
    {
        $path = public_path('img/logo.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    /** Data profil perusahaan untuk kop dokumen. */
    public static function company(): array
    {
        return [
            'name' => Setting::get('company_name', 'PT Dimas Love Medika'),
            'npwp' => Setting::get('company_npwp'),
            'nib' => Setting::get('company_nib'),
            'izin' => Setting::get('company_izin'),
            'kbli' => Setting::get('company_kbli'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::get('company_email'),
            'pjt' => Setting::get('company_pjt'),
        ];
    }
}
