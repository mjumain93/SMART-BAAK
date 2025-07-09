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
        Schema::create('rekap_nilai_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->string('nama_mahasiswa');
            $table->string('npm', 20);
            $table->decimal('sikap', 5, 2)->nullable();                // 15%
            $table->decimal('quis', 5, 2)->nullable();                 // 10%
            $table->decimal('uts', 5, 2)->nullable();                  // 10%
            $table->decimal('uas', 5, 2)->nullable();                  // 20%
            $table->decimal('umum', 5, 2)->nullable();   // 20%
            $table->decimal('khusus', 5, 2)->nullable(); // 25%
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_huruf', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_nilai_mahasiswa');
    }
};
