<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 회원 테이블에 파트너 코드 컬럼 추가
     *
     * 파트너 코드는 하위 회원 가입을 위한 고유 식별 코드로 사용됩니다.
     * - 20자리 길이
     * - 유니크 제약
     * - 필수가 아님 (nullable)
     * - 하위 회원 가입 시 추천인 식별용
     */
    public function up(): void
    {
        Schema::table('partner_users', function (Blueprint $table) {
            // 파트너 코드 컬럼 추가
            $table->string('partner_code', 20)
                  ->nullable()
                  ->unique()
                  ->comment('파트너 고유 코드 (하위 회원 가입용)')
                  ->after('email'); // email 컬럼 다음에 추가

            // 파트너 코드 검색용 인덱스 추가
            $table->index(['partner_code'], 'idx_partner_users_partner_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_users', function (Blueprint $table) {
            // 인덱스 삭제
            $table->dropIndex('idx_partner_users_partner_code');

            // 컬럼 삭제
            $table->dropColumn('partner_code');
        });
    }
};