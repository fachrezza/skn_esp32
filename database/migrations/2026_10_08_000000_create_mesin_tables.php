<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salinan struktur tabel mesin yang sudah ada di database produksi (sanwin_temp).
 * Setiap tabel hanya dibuat bila belum ada, sehingga aman dijalankan di database asli;
 * kegunaan utamanya untuk database test (sqlite :memory:).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mesin_qr')) {
            Schema::create('mesin_qr', function (Blueprint $table) {
                $table->string('no_mesin', 211)->primary();
                $table->string('nama_mesin', 211)->nullable();
                $table->string('image_qr', 211)->nullable();
                $table->string('note_mesin', 111)->nullable();
                $table->string('pin_mesin', 123)->nullable()->default('123');
                $table->enum('status_mesin', ['READY', 'RUNNING', 'MAINTANCE', 'OFF', 'RUSAK'])->nullable()->default('READY');
                $table->dateTime('created_at')->nullable();
                $table->string('user_at', 111)->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->string('running_jo', 211)->nullable();
                $table->string('uid_esp', 117)->nullable();
            });
        }

        if (! Schema::hasTable('mesin_history')) {
            Schema::create('mesin_history', function (Blueprint $table) {
                $table->increments('id_history');
                $table->string('no_mesin', 117)->nullable();
                $table->date('tgl_running')->nullable();
                $table->enum('jenis_operator', ['OPERATOR', 'TEKNISI', 'SETUP'])->nullable();
                $table->string('shift', 5)->nullable();
                $table->string('operator1', 117)->nullable();
                $table->string('operator2', 117)->nullable();
                $table->string('nama_teksini', 117)->nullable();
                $table->string('nama_setup', 117)->nullable();
                $table->string('no_jo', 117)->nullable();
                $table->dateTime('mesin_start')->nullable();
                $table->dateTime('mesin_stop')->nullable();
                $table->string('mesin_durasi', 117)->nullable();
                foreach (['produksi', 'mold', 'setup'] as $tahap) {
                    $table->time("start_$tahap")->nullable();
                    $table->time("stop_$tahap")->nullable();
                }
                $table->float('good')->default(0);
                $table->float('ng')->default(0);
                $table->float('sisa_jo')->default(0);
                $table->float('runner')->nullable();
                for ($i = 1; $i <= 10; $i++) {
                    $table->float("ITEM$i")->nullable();
                }
                $table->float('actual_cycletime')->nullable();
                $table->float('actual_berat')->nullable();
                $table->mediumText('remark')->nullable();
                $table->enum('status_proses', ['OPEN', 'RUNNING', 'FINISH', 'PENDING', 'HOLD'])->nullable();
                $table->dateTime('created_at')->nullable();
                $table->string('user_at', 117)->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('mesin_history_jo')) {
            Schema::create('mesin_history_jo', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_history');
                $table->string('no_mesin', 117);
                $table->string('no_jo', 117);
                $table->string('produk')->nullable();
                $table->decimal('qtyjo', 18, 2)->default(0);
                $table->decimal('good', 18, 2)->default(0);
                $table->decimal('ng', 18, 2)->default(0);
                $table->decimal('sisa_jo', 18, 2)->default(0);
                $table->decimal('runner', 18, 2)->default(0);
                $table->string('no_pr', 32)->nullable()->index();
                $table->decimal('converted_good', 18, 4)->default(0);
                $table->decimal('converted_ng', 18, 4)->default(0);
                $table->timestamps();
                $table->index(['id_history', 'no_jo']);
                $table->index(['no_mesin', 'no_jo']);
            });
        }

        if (! Schema::hasTable('mesin_mapping_jo')) {
            Schema::create('mesin_mapping_jo', function (Blueprint $table) {
                $table->string('no_jo', 71);
                $table->integer('prioritas')->nullable();
                $table->string('no_mesin', 71);
                $table->date('tgl_jo')->nullable();
                $table->string('boms', 111)->nullable();
                $table->string('produk', 111)->nullable();
                $table->float('qtyjo')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->string('user_at', 71)->nullable();
                $table->enum('status_jo', ['OPEN', 'RUNNING', 'FINISH', 'HOLD', 'PENDING'])->nullable()->default('OPEN');
                $table->decimal('qty_good', 10, 0)->nullable()->default(0);
                $table->decimal('qty_ng', 10, 0)->nullable()->default(0);
                $table->decimal('sisa_jo', 10, 0)->nullable()->default(0);
                $table->float('runner')->nullable()->default(0);
                $table->string('user_updated', 71)->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->primary(['no_jo', 'no_mesin']);
            });
        }

        if (! Schema::hasTable('mesin_troublle_item')) {
            Schema::create('mesin_troublle_item', function (Blueprint $table) {
                $table->string('kditem', 117)->primary();
                $table->string('nmitem', 117);
                $table->string('jenis', 117);
                $table->enum('stat', ['ENABLE', 'DISABLE'])->default('ENABLE');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deteled_at')->nullable();
            });
        }

        if (! Schema::hasTable('mesin_troublle_log')) {
            Schema::create('mesin_troublle_log', function (Blueprint $table) {
                $table->increments('id_log');
                $table->integer('id_history')->nullable();
                $table->string('kduser', 117)->nullable();
                $table->string('kditem', 117)->nullable();
                $table->string('nmitem', 117)->nullable();
                $table->string('jenis', 117)->nullable();
                $table->mediumText('keterangan')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }
    }

    /**
     * Sengaja kosong: tabel-tabel ini milik database produksi dan tidak boleh dihapus lewat rollback.
     */
    public function down(): void
    {
        //
    }
};
