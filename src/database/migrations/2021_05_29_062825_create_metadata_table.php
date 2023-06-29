<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Thanks to @ncpope of Github.com
        Schema::create('metadata', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->text('value');
            // Provides created_at && updated_at columns
            $table->timestamps();
            $table->foreignId('shop_id');
            // Linking
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('charges');
    }
}
