<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable()->unique();
            $table->string('shop')->nullable(false);
            $table->string('domain')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('scope')->nullable();
            $table->string('access_token')->nullable(false);
            $table->dateTime('expires_at')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->json('detail')->nullable();
            $table->boolean('is_uninstalled')->nullable();
            $table->date('uninstalled_at')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('shops');
    }
}
