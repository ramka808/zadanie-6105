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
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_save_bid_version ON bids;


DROP FUNCTION IF EXISTS save_bid_version();

CREATE OR REPLACE FUNCTION save_bid_version() RETURNS TRIGGER AS $$
BEGIN
  
    INSERT INTO bid_versions (bid_id, name, description, status, tender_id, author_type, author_id, version, created_at)
    SELECT OLD.id, OLD.name, OLD.description, OLD.status, OLD.tender_id, OLD.author_type, OLD.author_id, OLD.version, OLD.created_at;
   
    NEW.version := OLD.version + 1;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_save_bid_version
BEFORE UPDATE ON bids
FOR EACH ROW
EXECUTE FUNCTION save_bid_version();');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('save_version_bid');
    }
};
