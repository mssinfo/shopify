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
            $table->string('session_id')->nullable(false)->unique();
            $table->string('shop')->nullable(false);
            $table->boolean('is_online')->default(false);
            $table->string('scope')->nullable(false);
            $table->string('access_token')->nullable(false);
            $table->dateTime('expires_at')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('shop_url')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->boolean('shop_owner')->nullable();
            $table->boolean('timezone')->nullable();
            $table->string('phone')->nullable();
            $table->string('locale')->nullable();
            $table->boolean('collaborator')->nullable();
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
