<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'bkd')
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'bkd']);
    }
};
