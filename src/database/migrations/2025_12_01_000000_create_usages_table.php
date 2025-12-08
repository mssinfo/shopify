<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usages', function (Blueprint $table) {
            $table->id();

            // Link to shops table from the package
            $table->unsignedBigInteger('shop_id');

            // Generic usage type: e.g. "whatsapp_message", "email", "api_call"
            $table->string('type')->default('default');

            // Units used in this record (e.g. number of messages)
            $table->integer('quantity')->default(1);

            // Cost of this usage batch (0 for free usage/credits)
            $table->decimal('cost', 10, 2)->default(0);

            // Shopify usage charge ID or any external reference
            $table->string('reference_id')->nullable();

            // Anything else you want to store per usage
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usages');
    }
}
