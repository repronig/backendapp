<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('associations')
            ->where('code', 'ANFAAN')
            ->update([
                'code' => 'AANFAN',
                'name' => 'Association of Academic and Non-Fiction Authors of Nigeria',
                'description' => 'Association for academic and non-fiction authors in Nigeria.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('associations')
            ->where('code', 'AANFAN')
            ->update([
                'code' => 'ANFAAN',
                'name' => 'Association of Non Fiction Authors of Nigeria',
                'description' => 'Association for non-fiction authors in Nigeria.',
                'updated_at' => now(),
            ]);
    }
};
