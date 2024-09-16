<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем расширение uuid-ossp, если оно еще не существует
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // Проверяем существование типа bid_status перед его созданием
        if (!$this->enumExists('bid_status')) {
            DB::statement("CREATE TYPE bid_status AS ENUM ('Created', 'Published', 'Canceled')");
        }

        // Проверяем существование типа bid_author_type перед его созданием
        if (!$this->enumExists('bid_author_type')) {
            DB::statement("CREATE TYPE bid_author_type AS ENUM ('Organization', 'User')");
        }

        // Проверяем существование таблицы bids перед ее созданием
        if (!Schema::hasTable('bids')) {
            Schema::create('bids', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
                $table->string('name', 100);
                $table->string('description', 500);
                $table->enum('status', ['Created', 'Published', 'Canceled']);
                $table->uuid('tender_id');
                $table->enum('author_type', ['Organization', 'User']);
                $table->uuid('author_id');
                $table->integer('version')->default(1);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('tender_id')->references('id')->on('tenders')->onDelete('cascade');
                $table->foreign('author_id')->references('id')->on('employee')->onDelete('cascade');
                
                $table->index('tender_id');
                $table->index('author_id');
            });
        }

        // Добавляем проверку для поля version
        DB::statement('ALTER TABLE bids ADD CONSTRAINT check_version CHECK (version >= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
        
        // Удаляем ENUM типы при откате миграции
        DB::statement("DROP TYPE IF EXISTS bid_status");
        DB::statement("DROP TYPE IF EXISTS bid_author_type");
    }

    /**
     * Проверяет существование ENUM типа в PostgreSQL.
     */
    private function enumExists($enumName): bool
    {
        $result = DB::select("SELECT 1 FROM pg_type WHERE typname = ?", [$enumName]);
        return !empty($result);
    }
};
