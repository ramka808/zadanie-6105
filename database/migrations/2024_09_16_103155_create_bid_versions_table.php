<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bid_versions', function (Blueprint $table) {
            $table->uuid('version_id')->primary();
            $table->uuid('bid_id');
            $table->string('name', 100);
            $table->string('description', 500);
            $table->string('status', 50);
            $table->uuid('tender_id');
            $table->string('author_type', 50);
            $table->uuid('author_id');
            $table->integer('version');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('bid_id')->references('id')->on('bids')->onDelete('cascade');
        });

        // Добавляем функцию uuid_generate_v4(), если её нет
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        DB::statement('ALTER TABLE bid_versions ALTER COLUMN version_id SET DEFAULT uuid_generate_v4();');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_versions');
    }
};
