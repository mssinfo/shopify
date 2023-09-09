<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id');
            $table->string('email');
            $table->string('subject');
            $table->string('category');
            $table->text('detail');
            $table->text('password')->nullable();
            $table->text('files')->nullable();
            $table->integer('priority')->default(1);
            $table->string('ip_address', 45)->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
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
        Schema::dropIfExists('tickets');
    }
}
