<?php

declare(strict_types=1);

/**
 * Terjemahan Bahasa Indonesia untuk Pesan Validasi CodeIgniter 4
 */
return [
    // Core Messages
    'noRuleSets'            => 'Tidak ada kumpulan aturan yang ditentukan dalam konfigurasi Validasi.',
    'ruleNotFound'          => '"{0}" bukan aturan yang valid.',
    'groupNotFound'         => '"{0}" bukan grup aturan validasi.',
    'groupNotArray'         => 'Grup aturan "{0}" harus berupa array.',
    'invalidTemplate'       => '"{0}" bukan template Validasi yang valid.',

    // Rule Messages
    'alpha'                 => 'Kolom {field} hanya boleh berisi karakter alfabet.',
    'alpha_dash'            => 'Kolom {field} hanya boleh berisi karakter alfanumerik, garis bawah, dan tanda hubung.',
    'alpha_numeric'         => 'Kolom {field} hanya boleh berisi karakter alfanumerik.',
    'alpha_numeric_punct'   => 'Kolom {field} hanya boleh berisi karakter alfanumerik, spasi, dan karakter ~ ! # $ % & * - _ + = | : .',
    'alpha_numeric_space'   => 'Kolom {field} hanya boleh berisi karakter alfanumerik dan spasi.',
    'alpha_space'           => 'Kolom {field} hanya boleh berisi karakter alfabet dan spasi.',
    'decimal'               => 'Kolom {field} harus berisi angka desimal.',
    'differs'               => 'Kolom {field} harus berbeda dari kolom {param}.',
    'equals'                => 'Kolom {field} harus sama persis dengan: {param}.',
    'exact_length'          => 'Kolom {field} harus tepat sepanjang {param} karakter.',
    'field_exists'          => 'Kolom {field} harus ada.',
    'greater_than'          => 'Kolom {field} harus berisi angka lebih besar dari {param}.',
    'greater_than_equal_to' => 'Kolom {field} harus berisi angka lebih besar dari atau sama dengan {param}.',
    'hex'                   => 'Kolom {field} hanya boleh berisi karakter heksadesimal.',
    'in_list'               => 'Kolom {field} harus salah satu dari: {param}.',
    'integer'               => 'Kolom {field} harus berupa angka bulat (integer).',
    'is_natural'            => 'Kolom {field} hanya boleh berisi angka.',
    'is_natural_no_zero'    => 'Kolom {field} hanya boleh berisi angka dan harus lebih besar dari nol.',
    'is_not_unique'         => 'Kolom {field} harus berisi nilai yang sudah ada sebelumnya di database.',
    'is_unique'             => 'Kolom {field} harus berisi nilai unik (belum pernah digunakan).',
    'less_than'             => 'Kolom {field} harus berisi angka kurang dari {param}.',
    'less_than_equal_to'    => 'Kolom {field} harus berisi angka kurang dari atau sama dengan {param}.',
    'matches'               => 'Kolom {field} tidak cocok dengan kolom {param}.',
    'max_length'            => 'Kolom {field} tidak boleh melebihi {param} karakter.',
    'min_length'            => 'Kolom {field} harus memiliki panjang minimal {param} karakter.',
    'not_equals'            => 'Kolom {field} tidak boleh sama dengan: {param}.',
    'not_in_list'           => 'Kolom {field} tidak boleh salah satu dari: {param}.',
    'numeric'               => 'Kolom {field} hanya boleh berisi angka.',
    'regex_match'           => 'Format kolom {field} tidak benar.',
    'required'              => 'Kolom {field} wajib diisi.',
    'required_with'         => 'Kolom {field} wajib diisi bila {param} terisi.',
    'required_without'      => 'Kolom {field} wajib diisi bila {param} tidak terisi.',
    'string'                => 'Kolom {field} harus berupa string yang valid.',
    'timezone'              => 'Kolom {field} harus berupa zona waktu yang valid.',
    'valid_base64'          => 'Kolom {field} harus berupa string base64 yang valid.',
    'valid_email'           => 'Kolom {field} harus berisi alamat email yang valid.',
    'valid_emails'          => 'Kolom {field} harus berisi semua alamat email yang valid.',
    'valid_ip'              => 'Kolom {field} harus berisi alamat IP yang valid.',
    'valid_url'             => 'Kolom {field} harus berisi URL yang valid.',
    'valid_url_strict'      => 'Kolom {field} harus berisi URL yang valid.',
    'valid_date'            => 'Kolom {field} harus berisi tanggal yang valid.',
    'valid_json'            => 'Kolom {field} harus berisi format JSON yang valid.',

    // Credit Cards
    'valid_cc_number'       => '{field} bukan nomor kartu kredit yang valid.',

    // Files
    'uploaded'              => 'Berkas {field} tidak terunggah dengan benar.',
    'max_size'              => 'Ukuran berkas {field} terlalu besar.',
    'is_image'              => 'Berkas {field} bukan gambar yang valid.',
    'mime_in'               => 'Tipe MIME berkas {field} tidak diizinkan.',
    'ext_in'                => 'Ekstensi berkas {field} tidak valid.',
    'max_dims'              => 'Dimensi gambar {field} terlalu lebar atau terlalu tinggi.',
    'min_dims'              => 'Dimensi gambar {field} tidak cukup lebar atau cukup tinggi.',
];
