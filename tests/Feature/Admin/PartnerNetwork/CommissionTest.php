<?php

namespace Jiny\Partner\Tests\Feature\Admin\PartnerNetwork;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\CommissionRecord;
use Jiny\Partner\Models\PartnerNetworkRelationship;

class CommissionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(); // 테스트에서 미들웨어 제외
    }

    /** @test */
    public function it_can_display_commission_management_page()
    {
        // Given: 커미션 기록이 존재
        $tier = PartnerTier::factory()->create([
            'tier_name' => 'Bronze',
            'commission_rate' => 0.05
        ]);

        $partner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        CommissionRecord::factory()->create([
            'partner_id' => $partner->id,
            'commission_type' => 'direct_sales',
            'amount' => 50000,
            'status' => 'paid'
        ]);

        // When: 커미션 관리 페이지에 접근
        $response = $this->get('/admin/partner/network/commission');

        // Then: 페이지가 성공적으로 로드됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-network.commission');
        $response->assertViewHas(['commissions', 'statistics', 'monthlyData', 'tierSummary']);
    }

    /** @test */
    public function it_can_show_commission_detail()
    {
        // Given: 커미션 기록이 존재
        $tier = PartnerTier::factory()->create(['commission_rate' => 0.05]);
        $partner = PartnerUser::factory()->create(['partner_tier_id' => $tier->id]);

        $commission = CommissionRecord::factory()->create([
            'partner_id' => $partner->id,
            'commission_type' => 'direct_sales',
            'amount' => 100000,
            'status' => 'paid',
            'source_type' => 'order',
            'source_id' => 1,
            'description' => '주문 #1에 대한 직접 판매 커미션'
        ]);

        // When: 커미션 상세 페이지 접근
        $response = $this->get("/admin/partner/network/commission/{$commission->id}");

        // Then: 상세 정보가 표시됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-network.commission-detail');
        $response->assertViewHas('commission');
        $response->assertSee('100,000');
        $response->assertSee('직접 판매 커미션');
    }

    /** @test */
    public function it_can_create_manual_commission()
    {
        // Given: 파트너가 존재
        $tier = PartnerTier::factory()->create(['commission_rate' => 0.05]);
        $partner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        // When: 수동 커미션 생성 요청
        $response = $this->postJson('/admin/partner/network/commission/create', [
            'partner_id' => $partner->id,
            'commission_type' => 'manual_bonus',
            'amount' => 200000,
            'description' => '특별 성과 보너스',
            'notes' => '월 매출 목표 달성 보상'
        ]);

        // Then: 커미션이 성공적으로 생성됨
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => '커미션이 성공적으로 생성되었습니다.'
        ]);

        // And: 데이터베이스에 기록됨
        $this->assertDatabaseHas('commission_records', [
            'partner_id' => $partner->id,
            'commission_type' => 'manual_bonus',
            'amount' => 200000,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_edit_commission_record()
    {
        // Given: 커미션 기록이 존재
        $commission = CommissionRecord::factory()->create([
            'commission_type' => 'direct_sales',
            'amount' => 100000,
            'status' => 'pending',
            'description' => '원래 설명'
        ]);

        // When: 커미션 수정 요청
        $response = $this->putJson("/admin/partner/network/commission/{$commission->id}", [
            'commission_type' => 'team_sales',
            'amount' => 150000,
            'description' => '수정된 설명',
            'notes' => '관리자에 의해 수정됨'
        ]);

        // Then: 커미션이 성공적으로 수정됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '커미션이 성공적으로 수정되었습니다.'
        ]);

        // And: 데이터베이스가 업데이트됨
        $commission->refresh();
        $this->assertEquals('team_sales', $commission->commission_type);
        $this->assertEquals(150000, $commission->amount);
        $this->assertEquals('수정된 설명', $commission->description);
    }

    /** @test */
    public function it_can_delete_commission_record()
    {
        // Given: 커미션 기록이 존재
        $commission = CommissionRecord::factory()->create([
            'status' => 'pending'
        ]);

        // When: 커미션 삭제 요청
        $response = $this->deleteJson("/admin/partner/network/commission/{$commission->id}", [
            'reason' => '잘못 생성된 커미션'
        ]);

        // Then: 커미션이 성공적으로 삭제됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '커미션이 성공적으로 삭제되었습니다.'
        ]);

        // And: 데이터베이스에서 제거됨
        $this->assertSoftDeleted('commission_records', [
            'id' => $commission->id
        ]);
    }

    /** @test */
    public function it_prevents_editing_paid_commissions()
    {
        // Given: 이미 지급된 커미션
        $commission = CommissionRecord::factory()->create([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        // When: 지급된 커미션 수정 시도
        $response = $this->putJson("/admin/partner/network/commission/{$commission->id}", [
            'amount' => 999999
        ]);

        // Then: 수정이 거부됨
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonFragment(['이미 지급된 커미션은 수정할 수 없습니다']);
    }

    /** @test */
    public function it_can_approve_pending_commissions()
    {
        // Given: 대기 중인 커미션들
        $commissions = CommissionRecord::factory()->count(3)->create([
            'status' => 'pending'
        ]);

        // When: 커미션 승인 요청
        $response = $this->postJson('/admin/partner/network/commission/approve', [
            'commission_ids' => $commissions->pluck('id')->toArray(),
            'notes' => '관리자 승인'
        ]);

        // Then: 커미션들이 승인됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'approved' => 3
        ]);

        // And: 상태가 업데이트됨
        foreach ($commissions as $commission) {
            $commission->refresh();
            $this->assertEquals('approved', $commission->status);
        }
    }

    /** @test */
    public function it_can_process_commission_payments()
    {
        // Given: 승인된 커미션들
        $commissions = CommissionRecord::factory()->count(2)->create([
            'status' => 'approved',
            'amount' => 100000
        ]);

        // When: 커미션 지급 처리
        $response = $this->postJson('/admin/partner/network/commission/pay', [
            'commission_ids' => $commissions->pluck('id')->toArray(),
            'payment_method' => 'bank_transfer',
            'notes' => '일괄 지급 처리'
        ]);

        // Then: 지급이 성공적으로 처리됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'paid' => 2,
            'total_amount' => 200000
        ]);

        // And: 지급 상태로 변경됨
        foreach ($commissions as $commission) {
            $commission->refresh();
            $this->assertEquals('paid', $commission->status);
            $this->assertNotNull($commission->paid_at);
        }
    }

    /** @test */
    public function it_calculates_commission_statistics_correctly()
    {
        // Given: 다양한 상태의 커미션들
        $tier = PartnerTier::factory()->create(['commission_rate' => 0.05]);
        $partner = PartnerUser::factory()->create(['partner_tier_id' => $tier->id]);

        // 대기 중인 커미션: 3개, 총 300,000원
        CommissionRecord::factory()->count(3)->create([
            'partner_id' => $partner->id,
            'status' => 'pending',
            'amount' => 100000
        ]);

        // 지급된 커미션: 2개, 총 400,000원
        CommissionRecord::factory()->count(2)->create([
            'partner_id' => $partner->id,
            'status' => 'paid',
            'amount' => 200000,
            'paid_at' => now()
        ]);

        // 이번 달 커미션: 1개, 150,000원
        CommissionRecord::factory()->create([
            'partner_id' => $partner->id,
            'status' => 'paid',
            'amount' => 150000,
            'created_at' => now()->startOfMonth()->addDays(5),
            'paid_at' => now()
        ]);

        // When: 커미션 관리 페이지 접근
        $response = $this->get('/admin/partner/network/commission');

        // Then: 통계가 올바르게 계산됨
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertEquals(6, $statistics['total_commissions']); // 3 + 2 + 1
        $this->assertEquals(300000, $statistics['pending_amount']); // 3 * 100,000
        $this->assertEquals(550000, $statistics['paid_amount']); // 2 * 200,000 + 150,000
        $this->assertGreaterThanOrEqual(150000, $statistics['this_month_amount']);
    }

    /** @test */
    public function it_can_filter_commissions_by_status()
    {
        // Given: 다양한 상태의 커미션들
        CommissionRecord::factory()->count(3)->create(['status' => 'pending']);
        CommissionRecord::factory()->count(2)->create(['status' => 'paid']);

        // When: 대기 중인 커미션만 필터링
        $response = $this->get('/admin/partner/network/commission?status=pending');

        // Then: 페이지가 로드되고 필터가 적용됨
        $response->assertStatus(200);
        $currentFilters = $response->viewData('currentFilters');
        $this->assertEquals('pending', $currentFilters['status']);
    }

    /** @test */
    public function it_can_filter_commissions_by_partner()
    {
        // Given: 다른 파트너들의 커미션들
        $partner1 = PartnerUser::factory()->create();
        $partner2 = PartnerUser::factory()->create();

        CommissionRecord::factory()->count(3)->create(['partner_id' => $partner1->id]);
        CommissionRecord::factory()->count(2)->create(['partner_id' => $partner2->id]);

        // When: 특정 파트너로 필터링
        $response = $this->get("/admin/partner/network/commission?partner_id={$partner1->id}");

        // Then: 페이지가 로드되고 필터가 적용됨
        $response->assertStatus(200);
        $currentFilters = $response->viewData('currentFilters');
        $this->assertEquals($partner1->id, $currentFilters['partner_id']);
    }

    /** @test */
    public function it_shows_monthly_commission_trends()
    {
        // Given: 다양한 월의 커미션 데이터
        $tier = PartnerTier::factory()->create(['commission_rate' => 0.05]);
        $partner = PartnerUser::factory()->create(['partner_tier_id' => $tier->id]);

        // 이번 달
        CommissionRecord::factory()->count(3)->create([
            'partner_id' => $partner->id,
            'amount' => 100000,
            'status' => 'paid',
            'created_at' => now(),
            'paid_at' => now()
        ]);

        // 지난 달
        CommissionRecord::factory()->count(2)->create([
            'partner_id' => $partner->id,
            'amount' => 150000,
            'status' => 'paid',
            'created_at' => now()->subMonth(),
            'paid_at' => now()->subMonth()
        ]);

        // When: 커미션 관리 페이지 접근
        $response = $this->get('/admin/partner/network/commission');

        // Then: 월별 데이터가 표시됨
        $response->assertStatus(200);
        $monthlyData = $response->viewData('monthlyData');

        $this->assertNotEmpty($monthlyData);
        $this->assertArrayHasKey(now()->format('Y-m'), $monthlyData);
    }
}