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
        Schema::table('users', function (Blueprint $table) {
            $table->string('license_plate')->nullable()->after('phone');
            $table->string('restaurant_name')->nullable()->after('license_plate');
            $table->text('restaurant_address')->nullable()->after('restaurant_name');
            $table->string('photo')->nullable()->after('restaurant_address');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('license_plate');
            $table->dropColumn('restaurant_name');
            $table->dropColumn('restaurant_address');
            $table->dropColumn('photo');
        });
    }
};
