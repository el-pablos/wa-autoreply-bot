<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageTypeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['message_type' => 'text', 'body' => 'Halo {{nama}}, terima kasih sudah menghubungi kami.'],
            ['message_type' => 'image', 'body' => 'Gambar kamu sudah masuk. Boleh kirim detail tambahan kalau perlu.'],
            ['message_type' => 'video', 'body' => 'Video diterima. Tim kami akan cek dan balas secepatnya.'],
            ['message_type' => 'audio', 'body' => 'Voice note diterima, sedang kami review.'],
            ['message_type' => 'document', 'body' => 'Dokumen diterima. Kami proses dan update segera.'],
            ['message_type' => 'sticker', 'body' => 'Sticker masuk. Kalau ada kebutuhan, kirim pesan teks juga ya.'],
            ['message_type' => 'location', 'body' => 'Lokasi diterima. Terima kasih infonya.'],
            ['message_type' => 'contact', 'body' => 'Kontak diterima. Tim kami akan lanjutkan follow-up.'],
            ['message_type' => 'unknown', 'body' => 'Pesan sudah kami terima. Mohon kirim detail tambahan dalam teks.'],
        ];

        $now = now();

        foreach ($templates as $template) {
            DB::table('message_type_templates')->updateOrInsert(
                ['message_type' => $template['message_type']],
                [
                    'body' => $template['body'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
