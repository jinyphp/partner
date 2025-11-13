<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class ShowController extends PartnerController
{
    /**
     * 파트너 매출 상세 조회 (StoreController 방식으로 간소화)
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // Step 1: 사용자 인증 확인 (StoreController 방식)
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // Step 2: 파트너 등록 여부 확인 (StoreController 방식)
            $partner = $this->isPartner($user);
            if (!$partner) {
                return redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
            }

            // Step 3: 매출 정보 조회
            $sale = PartnerSales::find($id);

            if (!$sale) {
                return redirect()->route('home.partner.sales.index')
                    ->with('error', '매출 정보를 찾을 수 없습니다.');
            }

            // Step 4: 권한 확인 (본인 매출만 조회 가능)
            if ($sale->partner_id !== $partner->id) {
                return redirect()->route('home.partner.sales.index')
                    ->with('error', '본인의 매출만 조회할 수 있습니다.');
            }

            // Step 5: 관련 파트너 정보 조회
            $salePartner = PartnerUser::find($sale->partner_id); // 매출 소유 파트너
            $registeredByUser = PartnerUser::find($sale->created_by); // 등록자 파트너

            // Step 5-1: 파트너 객체로 실제 회원 정보 조회 (PartnerController 메서드 활용)
            $salePartnerUser = $salePartner ? $this->getUserByPartner($salePartner) : null;
            $registeredUser = null;

            // 등록자와 매출 소유자가 다른 경우만 별도 조회
            if ($registeredByUser && $registeredByUser->id !== $salePartner->id) {
                $registeredUser = $this->getUserByPartner($registeredByUser);
            } elseif ($registeredByUser && $registeredByUser->id === $salePartner->id) {
                $registeredUser = $salePartnerUser; // 동일인이면 중복 조회 방지
            }

            // 현재 조회자의 실제 회원 정보 조회
            $currentUser = $this->getUserByPartner($partner);

            // Step 6: 커미션 정보 조회 (있는 경우)
            $commissionInfo = null;
            if ($sale->commission_calculated) {
                $commissionInfo = [
                    'total_amount' => $sale->total_commission_amount,
                    'recipients_count' => $sale->commission_recipients_count,
                    'calculated_at' => $sale->commission_calculated_at,
                    'distribution' => $sale->commission_distribution
                ];
            }

            // Step 7: 고객 정보 조회 (이메일 형식인 경우 실제 회원 정보 검색)
            $customerInfo = null;
            if ($sale->customer_name && filter_var($sale->customer_name, FILTER_VALIDATE_EMAIL)) {
                // 이메일 형식이면 실제 회원 정보 검색 시도
                $customerInfo = $this->searchCustomerByEmail($sale->customer_name);
            }

            // Step 8: 승인 권한 확인 (관리자만 가능)
            $hasApprovalAccess = $partner->is_admin ?? false;

            $viewData = [
                'user' => $user,
                'partnerUser' => $partner,
                'sale' => $sale,
                'salePartner' => $salePartner,
                'registeredByUser' => $registeredByUser,
                'salePartnerUser' => $salePartnerUser, // 매출 소유자의 실제 회원 정보
                'registeredUser' => $registeredUser, // 등록자의 실제 회원 정보
                'currentUser' => $currentUser, // 현재 조회자의 실제 회원 정보
                'customerInfo' => $customerInfo, // 고객의 실제 회원 정보 (있는 경우)
                'commissionInfo' => $commissionInfo,
                'hasApprovalAccess' => $hasApprovalAccess,
                'pageTitle' => '매출 상세 정보'
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'data' => $viewData
                ]);
            }

            return view('jiny-partner::home.sales.show', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner sales show error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.index')
                ->with('error', '매출 상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 이메일로 고객의 실제 회원 정보 검색
     *
     * UserSearchController와 유사한 로직 사용
     *
     * @param string $email
     * @return object|null
     */
    private function searchCustomerByEmail($email)
    {
        try {
            $tableNames = $this->getShardedUserTables();

            foreach ($tableNames as $tableName) {
                $user = \DB::table($tableName)
                    ->select([
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at'
                    ])
                    ->where('email', $email)
                    ->whereNull('deleted_at')
                    ->first();

                if ($user) {
                    // 회원 정보를 객체로 변환하여 반환
                    return (object) [
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'table_source' => $tableName
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            \Log::error('Customer search by email failed: ' . $e->getMessage(), [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 샤딩된 사용자 테이블 목록 조회
     */
    private function getShardedUserTables()
    {
        try {
            $tableNames = [];
            $databaseDriver = \DB::getDriverName();

            if ($databaseDriver === 'sqlite') {
                $tables = \DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            } else {
                $tables = \DB::select("SHOW TABLES LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            }

            sort($tableNames);

            if (empty($tableNames)) {
                return ['users_001', 'users_002', 'users_003']; // fallback
            }

            return $tableNames;

        } catch (\Exception $e) {
            \Log::error('Failed to get sharded user tables: ' . $e->getMessage());
            return ['users_001', 'users_002', 'users_003']; // fallback
        }
    }
}
