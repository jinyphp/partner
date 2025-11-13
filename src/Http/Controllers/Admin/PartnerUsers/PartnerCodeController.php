<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Support\Str;

/**
 * 파트너 코드 관리 AJAX API 컨트롤러
 *
 * 파트너 코드 생성, 삭제 처리
 */
class PartnerCodeController extends Controller
{
    /**
     * 파트너 코드 생성 (AJAX)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request, $id)
    {
        try {
            Log::info('Partner code generation request received', [
                'partner_user_id' => $id,
                'request_method' => $request->method(),
                'is_authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            // 인증 확인
            if (!auth()->check()) {
                Log::warning('Unauthenticated partner code generation request', ['partner_user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], 401);
            }

            // 입력 유효성 검사 (email은 데이터베이스에서 가져오므로 불필요)
            // $request->validate([
            //     'email' => 'required|email'
            // ]);

            // 파트너 사용자 조회
            $partnerUser = PartnerUser::findOrFail($id);

            Log::info('Partner code generation request', [
                'partner_user_id' => $id,
                'partner_email' => $partnerUser->email,
                'current_partner_code' => $partnerUser->partner_code,
                'admin_user' => auth()->id()
            ]);

            // 이미 파트너 코드가 있는지 확인
            if ($partnerUser->partner_code) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 파트너 코드가 존재합니다. 기존 코드를 삭제한 후 새로 생성해주세요.',
                    'existing_code' => $partnerUser->partner_code
                ], 400);
            }

            DB::beginTransaction();

            // 파트너 코드 생성 (이메일 기반 + 랜덤)
            $partnerCode = $this->generateUniquePartnerCode($partnerUser->email);

            // 파트너 코드 업데이트
            $partnerUser->update([
                'partner_code' => $partnerCode,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            Log::info('Partner code generated successfully', [
                'partner_user_id' => $id,
                'partner_code' => $partnerCode,
                'admin_user' => auth()->id()
            ]);

            // 요청이 AJAX인지 확인
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "파트너 코드가 성공적으로 생성되었습니다. 생성된 코드: {$partnerCode}",
                    'data' => [
                        'id' => $partnerUser->id,
                        'partner_code' => $partnerCode,
                        'updated_at' => $partnerUser->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            // 일반 웹 요청인 경우 리디렉션
            return redirect()->route('admin.partner.users.show', $id)
                            ->with('success', "파트너 코드가 성공적으로 생성되었습니다. 생성된 코드: {$partnerCode}")
                            ->with('partner_code', $partnerCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '입력 데이터가 올바르지 않습니다.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '파트너 사용자를 찾을 수 없습니다.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner code generation failed', [
                'partner_user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '파트너 코드 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 파트너 코드 삭제 (AJAX)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        try {
            Log::info('Partner code deletion request received', [
                'partner_user_id' => $id,
                'request_method' => $request->method(),
                'is_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // 인증 확인
            if (!auth()->check()) {
                Log::warning('Unauthenticated partner code deletion request', ['partner_user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], 401);
            }

            // 파트너 사용자 조회
            $partnerUser = PartnerUser::findOrFail($id);

            Log::info('Partner code deletion request', [
                'partner_user_id' => $id,
                'partner_email' => $partnerUser->email,
                'current_partner_code' => $partnerUser->partner_code,
                'admin_user' => auth()->id()
            ]);

            // 파트너 코드가 없는지 확인
            if (!$partnerUser->partner_code) {
                return response()->json([
                    'success' => false,
                    'message' => '삭제할 파트너 코드가 존재하지 않습니다.'
                ], 400);
            }

            DB::beginTransaction();

            $oldCode = $partnerUser->partner_code;

            // 파트너 코드 삭제
            $partnerUser->update([
                'partner_code' => null,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            Log::info('Partner code deleted successfully', [
                'partner_user_id' => $id,
                'deleted_code' => $oldCode,
                'admin_user' => auth()->id()
            ]);

            // 요청이 AJAX인지 확인
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '파트너 코드가 성공적으로 삭제되었습니다.',
                    'data' => [
                        'id' => $partnerUser->id,
                        'partner_code' => null,
                        'updated_at' => $partnerUser->updated_at->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            // 일반 웹 요청인 경우 리디렉션
            return redirect()->route('admin.partner.users.show', $id)
                            ->with('success', '파트너 코드가 성공적으로 삭제되었습니다.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '파트너 사용자를 찾을 수 없습니다.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner code deletion failed', [
                'partner_user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '파트너 코드 삭제 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 이메일 기반으로 고유한 파트너 코드 생성
     *
     * @param string $email
     * @return string
     */
    private function generateUniquePartnerCode(string $email): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $attempt++;

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
     *
     * @return string
     */
    private function getRandomChar(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return $chars[random_int(0, strlen($chars) - 1)];
    }

    /**
     * 테스트용 파트너 코드 생성 (GET 방식)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function testGenerate(Request $request, $id)
    {
        try {
            Log::info('Test partner code generation request received', [
                'partner_user_id' => $id,
                'is_authenticated' => auth()->check(),
                'user_id' => auth()->id()
            ]);

            // 인증 확인
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], 401);
            }

            // 파트너 사용자 조회
            $partnerUser = PartnerUser::findOrFail($id);

            Log::info('Test partner code generation for user', [
                'partner_user_id' => $id,
                'partner_email' => $partnerUser->email,
                'current_partner_code' => $partnerUser->partner_code,
                'admin_user' => auth()->id()
            ]);

            DB::beginTransaction();

            // 파트너 코드 생성 (이메일 기반 + 랜덤)
            $partnerCode = $this->generateUniquePartnerCode($partnerUser->email);

            // 파트너 코드 업데이트
            $partnerUser->update([
                'partner_code' => $partnerCode,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            Log::info('Test partner code generated successfully', [
                'partner_user_id' => $id,
                'partner_code' => $partnerCode,
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => '테스트: 파트너 코드가 성공적으로 생성되었습니다.',
                'data' => [
                    'id' => $partnerUser->id,
                    'partner_code' => $partnerCode,
                    'email' => $partnerUser->email,
                    'updated_at' => $partnerUser->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Test partner code generation failed', [
                'partner_user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '테스트: 파트너 코드 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}