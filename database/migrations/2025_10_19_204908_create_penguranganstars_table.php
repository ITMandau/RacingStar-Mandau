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
        Schema::create('penguranganstars', function (Blueprint $table) {
            $table->id('id_penguranganstar');;
            $table->unsignedBigInteger('id_serpo');
            $table->integer('jumlah_pengurangan')->default(0);
            $table->string('alasan')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
            $table->foreign('id_serpo')->references('id_serpo')->on('serpos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penguranganstars');
    }
};
