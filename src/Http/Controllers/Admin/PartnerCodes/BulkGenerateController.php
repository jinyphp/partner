<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerCodes;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkGenerateController extends Controller
{
    /**
     * 대량 파트너 코드 생성
     */
    public function generate(Request $request)
    {
        try {
            $request->validate([
                'partner_ids' => 'required|array',
                'partner_ids.*' => 'integer|exists:partner_users,id',
                'prefix' => 'nullable|string|max:10'
            ]);

            $partnerIds = $request->input('partner_ids');
            $prefix = $request->input('prefix');
            $successCount = 0;
            $failCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($partnerIds as $partnerId) {
                try {
                    $partnerUser = PartnerUser::find($partnerId);

                    if (!$partnerUser) {
                        $failCount++;
                        $errors[] = "파트너 ID {$partnerId}를 찾을 수 없습니다.";
                        continue;
                    }

                    if ($partnerUser->partner_code) {
                        $failCount++;
                        $errors[] = "파트너 {$partnerUser->name}은 이미 코드가 있습니다.";
                        continue;
                    }

                    $partnerCode = $this->generateUniquePartnerCode($partnerUser->email, $prefix);

                    $partnerUser->update([
                        'partner_code' => $partnerCode,
                        'updated_by' => auth()->id()
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "파트너 ID {$partnerId}: " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Bulk partner code generation completed', [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total_requested' => count($partnerIds),
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "대량 코드 생성 완료: 성공 {$successCount}개, 실패 {$failCount}개",
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'total' => count($partnerIds),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk partner code generation failed', [
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '대량 코드 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 이메일 기반으로 고유한 파트너 코드 생성
     */
    private function generateUniquePartnerCode(string $email, string $prefix = null): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $attempt++;

            if ($prefix) {
                // 접두어가 있는 경우
                $codePrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix));
                $codePrefix = substr($codePrefix, 0, 8); // 최대 8자리

                // 나머지 자리는 랜덤 생성 (전체 20자리가 되도록)
                $remainingLength = 20 - strlen($codePrefix);
                $randomSuffix = '';
                for ($i = 0; $i < $remainingLength; $i++) {
                    $randomSuffix .= $this->getRandomChar();
                }

                $partnerCode = $codePrefix . $randomSuffix;
            } else {
                // 이메일의 사용자명 부분 추출
                $emailUsername = explode('@', $email)[0];

                // 이메일 사용자명의 처음 4자리 (영문, 숫자만)
                $emailPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $emailUsername));
                $emailPrefix = substr($emailPrefix, 0, 4);

                // 부족한 자리는 랜덤으로 채움
                if (strlen($emailPrefix) < 4) {
                    $emailPrefix = str_pad($emailPrefix, 4, $this->getRandomChar(), STR_PAD_RIGHT);
                }

                // 나머지 16자리는 랜덤 생성
                $randomSuffix = '';
                for ($i = 0; $i < 16; $i++) {
                    $randomSuffix .= $this->getRandomChar();
                }

                $partnerCode = $emailPrefix . $randomSuffix;
            }

            // 중복 확인
            $exists = PartnerUser::where('partner_code', $partnerCode)->exists();

            if (!$exists) {
                return $partnerCode;
            }

        } while ($attempt < $maxAttempts);

        // 최대 시도 후에도 중복되면 완전 랜덤 생성
        do {
            $partnerCode = '';
            for ($i = 0; $i < 20; $i++) {
                $partnerCode .= $this->getRandomChar();
            }

            $exists = PartnerUser::where('partner_code', $partnerCode)->exists();

        } while ($exists);

        return $partnerCode;
    }

    /**
     * A-Z, 0-9 범위에서 랜덤 문자 반환
     */
    private function getRandomChar(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return $chars[random_int(0, strlen($chars) - 1)];
    }
}