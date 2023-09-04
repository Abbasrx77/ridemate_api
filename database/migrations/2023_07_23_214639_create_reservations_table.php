<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conducteur_id')->constrained('conducteurs');
            $table->foreignId('passager_id')->constrained('passagers');
            $table->string('offre_id')->constrained('offre_trajets');
            $table->enum('conducteur_notified',['yes','waiting'])->default('waiting');
            $table->enum('statut',['confirme','decline','en_attente'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
