<?php

namespace Jiny\Partner\Tests\Feature\Admin\PartnerNetwork;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;

class TreeViewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(); // 테스트에서 미들웨어 제외
    }

    /** @test */
    public function it_can_display_network_tree_page()
    {
        // Given: 파트너 티어가 존재
        $tier = PartnerTier::factory()->create([
            'tier_name' => 'Bronze',
            'priority_level' => 1,
            'can_recruit' => true,
            'max_children' => 5
        ]);

        // And: 루트 파트너가 존재
        $rootPartner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'parent_id' => null,
            'can_recruit' => true,
            'status' => 'active'
        ]);

        // When: 네트워크 트리 페이지에 접근
        $response = $this->get('/admin/partner/network/tree');

        // Then: 성공적으로 페이지가 로드됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-network.tree');
        $response->assertViewHas(['tree', 'statistics', 'availableTiers']);
    }

    /** @test */
    public function it_displays_network_statistics_correctly()
    {
        // Given: 다양한 레벨의 파트너들이 존재
        $tier = PartnerTier::factory()->create([
            'tier_name' => 'Bronze',
            'can_recruit' => true,
            'max_children' => 5
        ]);

        $rootPartner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'parent_id' => null,
            'status' => 'active',
            'monthly_sales' => 1000000
        ]);

        $childPartner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 1,
            'parent_id' => $rootPartner->id,
            'status' => 'active',
            'monthly_sales' => 500000
        ]);

        // When: 네트워크 트리 페이지 접근
        $response = $this->get('/admin/partner/network/tree');

        // Then: 통계가 올바르게 표시됨
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertEquals(2, $statistics['total_partners']);
        $this->assertEquals(2, $statistics['active_partners']);
        $this->assertEquals(1500000, $statistics['total_sales']);
    }

    /** @test */
    public function it_can_filter_tree_by_root_partner()
    {
        // Given: 계층 구조가 있는 파트너들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $root1 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'status' => 'active'
        ]);

        $root2 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'status' => 'active'
        ]);

        $child1 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 1,
            'parent_id' => $root1->id,
            'status' => 'active'
        ]);

        // When: 특정 루트 파트너로 필터링
        $response = $this->get("/admin/partner/network/tree?root_id={$root1->id}");

        // Then: 해당 루트의 트리만 표시됨
        $response->assertStatus(200);
        $tree = $response->viewData('tree');

        // 트리에 해당 루트 파트너가 포함되어야 함
        $this->assertNotNull($tree);
    }

    /** @test */
    public function it_can_handle_max_depth_parameter()
    {
        // Given: 깊은 계층 구조
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $level0 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'status' => 'active'
        ]);

        $level1 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 1,
            'parent_id' => $level0->id,
            'status' => 'active'
        ]);

        $level2 = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'level' => 2,
            'parent_id' => $level1->id,
            'status' => 'active'
        ]);

        // When: 최대 깊이를 2로 제한
        $response = $this->get('/admin/partner/network/tree?max_depth=2');

        // Then: 페이지가 정상적으로 로드됨
        $response->assertStatus(200);
        $this->assertEquals(2, $response->viewData('maxDepth'));
    }

    /** @test */
    public function it_can_include_inactive_partners_when_requested()
    {
        // Given: 활성/비활성 파트너들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        $activePartner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'active'
        ]);

        $inactivePartner = PartnerUser::factory()->create([
            'partner_tier_id' => $tier->id,
            'status' => 'inactive'
        ]);

        // When: 비활성 파트너 포함 옵션 활성화
        $response = $this->get('/admin/partner/network/tree?show_inactive=1');

        // Then: 페이지가 로드되고 옵션이 설정됨
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('showInactive'));
    }

    /** @test */
    public function it_handles_empty_network_gracefully()
    {
        // Given: 파트너가 없는 상태

        // When: 네트워크 트리 페이지 접근
        $response = $this->get('/admin/partner/network/tree');

        // Then: 빈 상태를 올바르게 처리
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertEquals(0, $statistics['total_partners']);
        $this->assertEquals(0, $statistics['active_partners']);
    }

    /** @test */
    public function it_calculates_level_distribution_correctly()
    {
        // Given: 다양한 레벨의 파트너들
        $tier = PartnerTier::factory()->create(['can_recruit' => true]);

        // 레벨 0: 2명
        PartnerUser::factory()->count(2)->create([
            'partner_tier_id' => $tier->id,
            'level' => 0,
            'status' => 'active'
        ]);

        // 레벨 1: 3명
        PartnerUser::factory()->count(3)->create([
            'partner_tier_id' => $tier->id,
            'level' => 1,
            'status' => 'active'
        ]);

        // When: 네트워크 트리 페이지 접근
        $response = $this->get('/admin/partner/network/tree');

        // Then: 레벨별 분포가 올바르게 계산됨
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('level_distribution', $statistics);
        $this->assertEquals(2, $statistics['level_distribution'][0] ?? 0);
        $this->assertEquals(3, $statistics['level_distribution'][1] ?? 0);
    }

    /** @test */
    public function it_shows_tier_distribution()
    {
        // Given: 다른 티어의 파트너들
        $bronzeTier = PartnerTier::factory()->create([
            'tier_name' => 'Bronze',
            'can_recruit' => true
        ]);

        $silverTier = PartnerTier::factory()->create([
            'tier_name' => 'Silver',
            'can_recruit' => true
        ]);

        PartnerUser::factory()->count(2)->create([
            'partner_tier_id' => $bronzeTier->id,
            'status' => 'active'
        ]);

        PartnerUser::factory()->count(3)->create([
            'partner_tier_id' => $silverTier->id,
            'status' => 'active'
        ]);

        // When: 네트워크 트리 페이지 접근
        $response = $this->get('/admin/partner/network/tree');

        // Then: 티어별 분포가 표시됨
        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('tier_distribution', $statistics);
        $this->assertEquals(2, $statistics['tier_distribution']['Bronze'] ?? 0);
        $this->assertEquals(3, $statistics['tier_distribution']['Silver'] ?? 0);
    }
}