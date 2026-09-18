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
        Schema::table('traffic_logs', function (Blueprint $table) {
            $table->integer('car_in')->default(0)->after('car_count');
            $table->integer('car_out')->default(0)->after('car_in');
            $table->integer('motorcycle_in')->default(0)->after('motorcycle_count');
            $table->integer('motorcycle_out')->default(0)->after('motorcycle_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traffic_logs', function (Blueprint $table) {
            $table->dropColumn(['car_in', 'car_out', 'motorcycle_in', 'motorcycle_out']);
        });
    }
};
