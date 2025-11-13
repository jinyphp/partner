<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class StoreController extends PartnerController
{
    /**
     * 파트너 매출 등록 처리
     */
    public function __invoke(Request $request)
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

            // Step 3: 유효성 검사 (partner_id 제거 - 본인 매출만 등록)
            $validatedData = $request->validate([
                'product_name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'sales_date' => 'required|date_format:Y-m-d\TH:i', // datetime-local 형식 (시분초 포함)
                'category' => 'nullable|string|max:50',
                'customer_name' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:pending,confirmed,cancelled'
            ]);

            // Step 4: 매출 데이터 생성 (본인 정보 사용)
            $salesData = [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'partner_email' => $partner->email,
                'title' => $validatedData['product_name'], // product_name -> title로 매핑
                'amount' => $validatedData['amount'],
                'sales_date' => $validatedData['sales_date'],
                'category' => $validatedData['category'] ?? 'general',
                'customer_name' => $validatedData['customer_name'], // 고객명 추가
                'description' => $validatedData['description'],
                'status' => $validatedData['status'] ?? 'pending',
                'created_by' => $partner->id, // partner_users 테이블 ID 사용 (외래키 제약조건)
                'updated_by' => $partner->id, // partner_users 테이블 ID 사용
                'currency' => 'KRW',
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Step 5: 매출 등록
            $sale = PartnerSales::create($salesData);

            // Step 6: 성공 응답
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => '매출이 성공적으로 등록되었습니다.',
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
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            \Log::error('Partner sales store error: ' . $e->getMessage(), $errorDetails);

            // 개발 환경에서는 상세한 오류 정보를 JSON 응답에 포함
            $response = [
                'success' => false,
                'code' => 500,
                'message' => '매출 등록 중 오류가 발생했습니다.'
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
