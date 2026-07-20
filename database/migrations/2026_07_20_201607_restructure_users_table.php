<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key & kolom opd_id
            if (Schema::hasColumn('users', 'opd_id')) {
                $table->dropForeign(['opd_id']);
                $table->dropColumn('opd_id');
            }

            // Hapus email_verified_at
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }

            // Tambah NIP (nullable karena super admin tidak punya NIP)
            $table->string('nip')->nullable()->unique()->after('name');

            // Email jadi nullable
            $table->string('email')->nullable()->change();

            // Tambah is_active
            $table->boolean('is_active')->default(true)->after('role');

            // Ubah default role
            $table->string('role')->default('user')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'is_active']);

            // Kembalikan email jadi required
            $table->string('email')->nullable(false)->change();

            // Kembalikan role default
            $table->string('role')->default('admin_opd')->change();

            // Kembalikan email_verified_at
            $table->timestamp('email_verified_at')->nullable();

            // Kembalikan opd_id
            $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
        });
    }
};
