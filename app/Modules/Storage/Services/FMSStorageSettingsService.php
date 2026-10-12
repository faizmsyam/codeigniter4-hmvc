<?php
namespace App\Modules\Storage\Services;

use App\Config\FMSUploads;

/**
 * Pengaturan Storage yang bisa diubah dari UI (single source untuk allow-list MIME).
 * Nilai tersimpan di tabel storage_settings; .env hanya jadi fallback awal.
 */
final class FMSStorageSettingsService
{
    private const KEY_ALLOWED_MIME = 'allowed_mime_types';

    /** @return array<string, array{key:string, mime:string, extension:string, maxBytes:int, label:string}> */
    public function availableMimeTypes(): array
    {
        $available = [];
        foreach (config(FMSUploads::class)->allowedFileTypes as $key => $type) {
            $available[$type['mime']] = [
                'key'       => (string) $key,
                'mime'      => (string) $type['mime'],
                'extension' => (string) $type['extension'],
                'maxBytes'  => (int) $type['maxBytes'],
                'label'     => strtoupper((string) $key),
            ];
        }

        return $available;
    }

    public function hasAllowedMimeSetting(): bool
    {
        return db_connect()->table('storage_settings')
            ->where('setting_key', self::KEY_ALLOWED_MIME)
            ->countAllResults() > 0;
    }

    /** @return list<string> daftar MIME aktif (array kosong valid sebagai deny-all) */
    public function allowedMimeTypes(): array
    {
        $row = db_connect()->table('storage_settings')
            ->where('setting_key', self::KEY_ALLOWED_MIME)
            ->get(1)->getRowArray();

        if (! is_array($row)) {
            return [];
        }

        $decoded = json_decode((string) ($row['setting_value'] ?? ''), true);

        return $this->normalize(is_array($decoded) ? $decoded : []);
    }

    /** @param array<int, mixed> $mimeTypes */
    public function saveAllowedMimeTypes(array $mimeTypes): array
    {
        $clean = $this->normalize($mimeTypes);
        $payload = json_encode(array_values($clean), JSON_UNESCAPED_SLASHES);
        $now = date('Y-m-d H:i:s');

        $table = db_connect()->table('storage_settings');
        $existing = $table->where('setting_key', self::KEY_ALLOWED_MIME)->get(1)->getRowArray();

        if (is_array($existing)) {
            $table->where('setting_key', self::KEY_ALLOWED_MIME)->update([
                'setting_value' => $payload,
                'updated_at'    => $now,
            ]);
        } else {
            $table->insert([
                'setting_key'   => self::KEY_ALLOWED_MIME,
                'setting_value' => $payload,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        return $clean;
    }

    /**
     * Buang MIME yang tidak dikenal / duplikat.
     *
     * @param array<int, mixed> $mimeTypes
     * @return list<string>
     */
    private function normalize(array $mimeTypes): array
    {
        $available = array_keys($this->availableMimeTypes());
        $clean = [];
        foreach ($mimeTypes as $mime) {
            $mime = strtolower(trim((string) $mime));
            if ($mime !== '' && in_array($mime, $available, true) && ! in_array($mime, $clean, true)) {
                $clean[] = $mime;
            }
        }

        return $clean;
    }
}
