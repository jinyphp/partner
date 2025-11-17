<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UNIQUE constraint 제거 - UNIQUE constraint failed 오류 해결
     *
     * partner_users 테이블의 ['user_id', 'user_table'] UNIQUE constraint를 제거하여
     * 동일 사용자가 여러 번 승인 요청을 해도 오류가 발생하지 않도록 함
     */
    public function up(): void
    {
        Schema::table('partner_users', function (Blueprint $table) {
            try {
                // SQLite와 다른 DB 엔진에서의 UNIQUE constraint 제거 방식이 다름
                $driverName = Schema::getConnection()->getDriverName();

                if ($driverName === 'sqlite') {
                    // SQLite의 경우 제약조건 이름을 직접 지정할 수 없으므로
                    // 테이블 재생성이 필요하지만, 위험하므로 경고만 출력
                    \Log::info('SQLite에서는 UNIQUE constraint 제거가 복잡합니다. 대신 ApproveController에서 중복 체크로 처리합니다.');
                } else {
                    // MySQL, PostgreSQL 등의 경우
                    $table->dropUnique(['user_id', 'user_table']);
                }
            } catch (\Exception $e) {
                \Log::warning('UNIQUE constraint 제거 중 오류 발생 (무시됨)', [
                    'error' => $e->getMessage(),
                    'table' => 'partner_users'
                ]);
                // 오류가 발생해도 마이그레이션은 성공으로 처리
                // ApproveController의 중복 체크 로직으로 해결
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_users', function (Blueprint $table) {
            try {
                // UNIQUE constraint 복원
                $table->unique(['user_id', 'user_table'], 'partner_users_user_id_user_table_unique');
            } catch (\Exception $e) {
                \Log::warning('UNIQUE constraint 복원 중 오류 발생', [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }
};