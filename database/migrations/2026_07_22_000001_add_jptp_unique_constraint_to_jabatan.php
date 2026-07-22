<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan unique constraint untuk mencegah duplikasi JPTP per OPD.
     *
     * Karena MySQL/MariaDB tidak mendukung partial unique index,
     * kita gunakan generated column yang bernilai NULL untuk non-JPTP.
     * NULL values tidak dianggap duplikat oleh unique index.
     *
     * ⚠️ Migration ini hanya berjalan di MySQL/MariaDB. SQLite (testing)
     *    dilewati karena tidak mendukung CONCAT() di generated column.
     *    Validasi di application layer (controller) sudah cukup untuk SQLite.
     *
     * ⚠️ Sebelum menjalankan migration ini di production, pastikan tidak ada
     *    data JPTP duplikat per OPD. Cek dengan:
     *    SELECT opd_id, COUNT(*) as cnt FROM jabatan
     *    WHERE jenis_jabatan='Struktural' AND jenjang='Pimpinan Tinggi Pratama'
     *    GROUP BY opd_id HAVING cnt > 1;
     */
    public function up(): void
    {
        // SQLite tidak mendukung CONCAT() di generated column — lewati
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE jabatan
            ADD COLUMN jptp_opd_unique VARCHAR(255)
            AS (
                CASE
                    WHEN jenis_jabatan = 'Struktural'
                     AND jenjang = 'Pimpinan Tinggi Pratama'
                    THEN CONCAT(opd_id, '-jptp')
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement('CREATE UNIQUE INDEX jabatan_jptp_opd_unique ON jabatan(jptp_opd_unique)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropIndex('jabatan_jptp_opd_unique');
            $table->dropColumn('jptp_opd_unique');
        });
    }
};
