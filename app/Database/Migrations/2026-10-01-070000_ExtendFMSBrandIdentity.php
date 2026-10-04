<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class ExtendFMSBrandIdentity extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('c_brands', [
            'tagline' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
                'after'      => 'name',
            ],
            'description' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'tagline',
            ],
            'website_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'phone',
            ],
            'whatsapp' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'after'      => 'website_url',
            ],
            'social_links' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'whatsapp',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('c_brands', [
            'social_links',
            'whatsapp',
            'website_url',
            'description',
            'tagline',
        ]);
    }
}
