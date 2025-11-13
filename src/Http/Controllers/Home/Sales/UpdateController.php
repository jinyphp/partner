<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class UpdateController extends PartnerController
{
    /**
     * 파트너 매출 수정 처리 (대기 상태만 수정 가능)
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // Step 1: 사용자 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 401,
                    'message' => '회원로그인이 필요합니다.'
                ], 401);
            }

            // Step 2: 파트너 등록 여부 확인
            $partner = $this->isPartner($user);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'code' => 403,
                    'message' => '파트너 등록이 필요합니다.'
                ], 403);
            }

            // Step 3: 매출 정보 조회
            $sale = PartnerSales::find($id);

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'code' => 404,
                    'message' => '매출 정보를 찾을 수 없습니다.'
                ], 404);
            }

            // Step 4: 권한 확인 (본인 매출만 수정 가능)
            if ($sale->partner_id !== $partner->id) {
                return response()->json([
                    'success' => false,
                    'code' => 403,
                    'message' => '본인의 매출만 수정할 수 있습니다.'
                ], 403);
            }

            // Step 5: 수정 가능한 상태인지 확인 (pending 상태만 수정 가능)
            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'code' => 400,
                    'message' => '대기 상태의 매출만 수정할 수 있습니다.'
                ], 400);
            }

            // Step 6: 유효성 검사 (StoreController와 동일)
            $validatedData = $request->validate([
                'product_name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'sales_date' => 'required|date_format:Y-m-d\TH:i', // datetime-local 형식
                'category' => 'nullable|string|max:50',
                'customer_name' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:pending,confirmed,cancelled'
            ]);

            // Step 7: 매출 데이터 업데이트 (본인 정보 유지)
            $updateData = [
                'title' => $validatedData['product_name'], // product_name -> title로 매핑
                'amount' => $validatedData['amount'],
                'sales_date' => $validatedData['sales_date'],
                'category' => $validatedData['category'] ?? 'general',
                'customer_name' => $validatedData['customer_name'],
                'description' => $validatedData['description'],
                'status' => $validatedData['status'] ?? 'pending', // 수정 후에도 대기 상태 유지
                'updated_by' => $partner->id, // partner_users 테이블 ID 사용
                'updated_at' => now()
            ];

            // Step 8: 매출 업데이트
            $sale->update($updateData);

            // Step 9: 성공 응답
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => '매출이 성공적으로 수정되었습니다.',
                'data' => [
                    'sale_id' => $sale->id,
                    'partner_name' => $partner->name,
                    'product_name' => $sale->title,
                    'amount' => $sale->amount,
                    'sales_date' => $sale->sales_date,
                    'status' => $sale->status
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => '입력 데이터가 올바르지 않습니다.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // 상세한 에러 로그 기록
            $errorDetails = [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            \Log::error('Partner sales update error: ' . $e->getMessage(), $errorDetails);

            // 개발 환경에서는 상세한 오류 정보를 JSON 응답에 포함
            $response = [
                'success' => false,
                'code' => 500,
                'message' => '매출 수정 중 오류가 발생했습니다.'
            ];

            // 개발 환경이거나 디버그 모드일 때 상세 정보 추가
            if (config('app.debug') || app()->environment('local', 'development')) {
                $response['debug'] = [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ];
            }

            return response()->json($response, 500);
        }
    }
}