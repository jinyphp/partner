<?php

namespace Jiny\Partner\Tests\Feature\Admin\PartnerNetwork;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerNetworkRelationship;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(); // 테스트에서 미들웨어 제외
    }

    /** @test */
    public function it_can_display_recruitment_management_page()
    {
        // Given: 파트너 티어와 관계가 존재
        $tier = PartnerTier::factory()->create([
            'tier_name' => 'Bronze',
            'can_recruit' => true,
            'max_children' => 5
        ]);

        $parent = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true
        ]);

        $child = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        PartnerNetworkRelationship::factory()->create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'recruiter_id' => $parent->id,
            'is_active' => true
        ]);

        // When: 모집 관리 페이지에 접근
        $response = $this->get('/admin/partner/network/recruitment');

        // Then: 페이지가 성공적으로 로드됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-network.recruitment');
        $response->assertViewHas(['relationships', 'statistics', 'topRecruiters', 'availableRecruiters']);
    }

    /** @test */
    public function it_can_recruit_new_partner()
    {
        // Given: 상위 파트너와 신규 파트너가 존재
        $tier = PartnerTier::factory()->create([
            'can_recruit' => true,
            'max_children' => 5
        ]);

        $parent = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true,
            'children_count' => 0,
            'level' => 0
        ]);

        $child = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'parent_id' => null,
            'level' => 0
        ]);

        // When: 모집 요청을 보냄
        $response = $this->postJson('/admin/partner/network/recruitment/recruit', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'recruiter_id' => $parent->id,
            'recruitment_notes' => '우수한 파트너입니다.'
        ]);

        // Then: 모집이 성공적으로 처리됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '파트너 모집이 성공적으로 완료되었습니다.'
        ]);

        // And: 데이터베이스에 관계가 생성됨
        $this->assertDatabaseHas('partner_network_relationships', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'recruiter_id' => $parent->id,
            'is_active' => true
        ]);

        // And: 하위 파트너의 정보가 업데이트됨
        $child->refresh();
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals(1, $child->level);
    }

    /** @test */
    public function it_prevents_recruiting_when_max_children_reached()
    {
        // Given: 최대 하위 파트너 수에 도달한 상위 파트너
        $tier = PartnerTier::factory()->create([
            'can_recruit' => true,
            'max_children' => 2
        ]);

        $parent = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true,
            'children_count' => 2,
            'max_children' => 2
        ]);

        $child = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'parent_id' => null
        ]);

        // When: 모집 시도
        $response = $this->postJson('/admin/partner/network/recruitment/recruit', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'recruiter_id' => $parent->id
        ]);

        // Then: 모집이 거부됨
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonFragment(['관리 가능한 최대 하위 파트너 수에 도달했습니다']);
    }

    /** @test */
    public function it_prevents_self_recruitment()
    {
        // Given: 파트너
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $partner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true
        ]);

        // When: 자기 자신을 모집 시도
        $response = $this->postJson('/admin/partner/network/recruitment/recruit', [
            'parent_id' => $partner->id,
            'child_id' => $partner->id,
            'recruiter_id' => $partner->id
        ]);

        // Then: 모집이 거부됨
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonFragment(['자기 자신을 모집할 수 없습니다']);
    }

    /** @test */
    public function it_prevents_circular_relationships()
    {
        // Given: A -> B 관계가 이미 존재
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $partnerA = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true,
            'level' => 0
        ]);

        $partnerB = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true,
            'parent_id' => $partnerA->id,
            'level' => 1
        ]);

        // When: B -> A 순환 관계 생성 시도
        $response = $this->postJson('/admin/partner/network/recruitment/recruit', [
            'parent_id' => $partnerB->id,
            'child_id' => $partnerA->id,
            'recruiter_id' => $partnerB->id
        ]);

        // Then: 순환 관계가 차단됨
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
        $response->assertJsonFragment(['순환 관계가 생성됩니다']);
    }

    /** @test */
    public function it_can_bulk_recruit_partners()
    {
        // Given: 상위 파트너와 여러 신규 파트너들
        $tier = PartnerTier::factory()->create([
            'can_recruit' => true,
            'max_children' => 10
        ]);

        $parent = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'can_recruit' => true,
            'children_count' => 0
        ]);

        $children = PartnerUser::factory()->count(3)->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'parent_id' => null
        ]);

        // When: 대량 모집 요청
        $response = $this->postJson('/admin/partner/network/recruitment/bulk-recruit', [
            'parent_id' => $parent->id,
            'child_ids' => $children->pluck('id')->toArray(),
            'recruiter_id' => $parent->id
        ]);

        // Then: 대량 모집이 성공적으로 처리됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'successful' => 3,
            'failed' => 0
        ]);

        // And: 모든 관계가 생성됨
        foreach ($children as $child) {
            $this->assertDatabaseHas('partner_network_relationships', [
                'parent_id' => $parent->id,
                'child_id' => $child->id
            ]);
        }
    }

    /** @test */
    public function it_can_remove_recruitment_relationship()
    {
        // Given: 모집 관계가 존재
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $parent = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        $child = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active',
            'parent_id' => $parent->id
        ]);

        $relationship = PartnerNetworkRelationship::factory()->create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'is_active' => true
        ]);

        // When: 관계 해제 요청
        $response = $this->deleteJson("/admin/partner/network/recruitment/remove-relationship/{$relationship->id}", [
            'reason' => '성과 부진으로 인한 관계 해제'
        ]);

        // Then: 관계가 성공적으로 해제됨
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '파트너 관계가 성공적으로 해제되었습니다.'
        ]);

        // And: 관계가 비활성화됨
        $relationship->refresh();
        $this->assertFalse($relationship->is_active);
        $this->assertEquals('성과 부진으로 인한 관계 해제', $relationship->deactivation_reason);
    }

    /** @test */
    public function it_calculates_recruitment_statistics_correctly()
    {
        // Given: 다양한 상태의 모집 관계들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $recruiter = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        // 활성 관계 3개
        PartnerNetworkRelationship::factory()->count(3)->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
            'recruited_at' => now()
        ]);

        // 비활성 관계 2개
        PartnerNetworkRelationship::factory()->count(2)->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => false,
            'recruited_at' => now()->subDays(10)
        ]);

        // 이번 달 모집 2개
        PartnerNetworkRelationship::factory()->count(2)->create([
            'recruiter_id' => $recruiter->id,
            'is_active' => true,
            'recruited_at' => now()->startOfMonth()->addDays(5)
        ]);

        // When: 모집 관리 페이지 접근
        $response = $this->get('/admin/partner/network/recruitment');

        // Then: 통계가 올바르게 계산됨
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertEquals(7, $statistics['total_relationships']); // 3 + 2 + 2
        $this->assertEquals(5, $statistics['active_relationships']); // 3 + 2
        $this->assertGreaterThanOrEqual(2, $statistics['this_month_recruits']);
    }

    /** @test */
    public function it_can_filter_relationships_by_status()
    {
        // Given: 활성/비활성 관계들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $activeRelationships = PartnerNetworkRelationship::factory()->count(3)->create([
            'is_active' => true
        ]);

        $inactiveRelationships = PartnerNetworkRelationship::factory()->count(2)->create([
            'is_active' => false
        ]);

        // When: 활성 관계만 필터링
        $response = $this->get('/admin/partner/network/recruitment?status=active');

        // Then: 페이지가 로드되고 필터가 적용됨
        $response->assertStatus(200);
        $currentFilters = $response->viewData('currentFilters');
        $this->assertEquals('active', $currentFilters['status']);
    }

    /** @test */
    public function it_shows_top_recruiters()
    {
        // Given: 다양한 모집 실적을 가진 파트너들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $topRecruiter = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        $normalRecruiter = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        // 최고 모집자: 5명 모집
        PartnerNetworkRelationship::factory()->count(5)->create([
            'recruiter_id' => $topRecruiter->id,
            'is_active' => true
        ]);

        // 일반 모집자: 2명 모집
        PartnerNetworkRelationship::factory()->count(2)->create([
            'recruiter_id' => $normalRecruiter->id,
            'is_active' => true
        ]);

        // When: 모집 관리 페이지 접근
        $response = $this->get('/admin/partner/network/recruitment');

        // Then: 최고 모집자가 올바르게 표시됨
        $response->assertStatus(200);
        $topRecruiters = $response->viewData('topRecruiters');

        $this->assertNotEmpty($topRecruiters);
        $firstRecruiter = $topRecruiters->first();
        $this->assertEquals($topRecruiter->id, $firstRecruiter->id);
        $this->assertEquals(5, $firstRecruiter->total_recruits);
    }
}