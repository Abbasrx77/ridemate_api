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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('matricule');
            $table->string('email')->unique();
            $table->enum('fonction',['conducteur','passager','admin']);  
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('zone');
            $table->string('fcmToken');
            $table->string('uid');
            $table->float('note')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
