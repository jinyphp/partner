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
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->string('country')->default('KR')->after('phone')->comment('국가 코드 (KR, US, JP, CN 등)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
