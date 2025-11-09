<?php

namespace Jiny\Partner\Tests\Feature\Home;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use App\Models\User;

class PartnerDashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $partner;
    protected $partnerTier;
    protected $partnerType;

    protected function setUp(): void
    {
        parent::setUp();

        // 테스트용 사용자 생성
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => '테스트 사용자'
        ]);

        // 파트너 타입 생성
        $this->partnerType = PartnerType::create([
            'type_code' => 'SALES_' . time(),
            'type_name' => '영업 파트너',
            'description' => '영업 전문 파트너',
            'is_active' => true
        ]);

        // 파트너 티어 생성
        $this->partnerTier = PartnerTier::create([
            'tier_code' => 'gold_' . time(),
            'tier_name' => '골드',
            'description' => '골드 등급 파트너',
            'commission_rate' => 8.0,
            'priority_level' => 2,
            'requirements' => [
                'min_experience_months' => 12,
                'min_completed_jobs' => 100,
                'min_rating' => 4.0
            ],
            'benefits' => [
                'job_assignment_priority' => 'high',
                'maximum_concurrent_jobs' => 5,
                'support_response_time' => '6시간'
            ]
        ]);

        // 파트너 사용자 생성
        $this->partner = PartnerUser::create([
            'user_id' => $this->user->id,
            'partner_type_id' => $this->partnerType->id,
            'partner_tier_id' => $this->partnerTier->id,
            'name' => '테스트 파트너',
            'email' => 'partner@example.com',
            'monthly_sales' => 1500000,
            'total_sales' => 5000000,
            'team_sales' => 2000000,
            'level' => 1,
            'path' => '/1',
            'children_count' => 2,
            'is_active' => true,
            'partner_joined_at' => now()->format('Y-m-d'),
            'tier_assigned_at' => now()->format('Y-m-d'),
            'status' => 'active'
        ]);
    }

    /** @test */
    public function authenticated_user_can_access_partner_dashboard()
    {
        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::home.dashboard.index');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_partner_dashboard()
    {
        $response = $this->get('/home/partner');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function partner_registration_redirect_works()
    {
        // 파트너가 아닌 사용자로 테스트
        $userWithoutPartner = User::factory()->create();

        $response = $this->actingAs($userWithoutPartner)
            ->get('/home/partner');

        $response->assertRedirect(route('home.partner.regist.index'));
        $response->assertSessionHas('info', '파트너 등록이 필요합니다.');
    }

    /** @test */
    public function user_without_partner_record_is_redirected_to_registration()
    {
        // 파트너 기록이 없는 사용자 생성
        $userWithoutPartner = User::factory()->create();

        $response = $this->actingAs($userWithoutPartner)
            ->get('/home/partner');

        $response->assertRedirect();
        $response->assertSessionHas('info', '파트너 등록이 필요합니다.');
    }

    /** @test */
    public function dashboard_displays_partner_basic_information()
    {
        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 파트너 기본 정보 확인
        $response->assertSee('테스트 파트너'); // partner name
        $response->assertSee('partner@example.com'); // partner email
        $response->assertSee('골드'); // partner tier
        $response->assertSee('영업 파트너'); // partner type

        // ID 속성을 가진 요소들 확인
        $response->assertSee('id="partner-name"', false);
        $response->assertSee('id="partner-email"', false);
        $response->assertSee('id="partner-tier"', false);
        $response->assertSee('id="partner-type"', false);
        $response->assertSee('id="partner-level"', false);
        $response->assertSee('id="partner-path"', false);
    }

    /** @test */
    public function dashboard_displays_sales_statistics()
    {
        // 테스트용 매출 데이터 생성
        PartnerSales::create([
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'partner_email' => $this->partner->email,
            'title' => '테스트 매출 1',
            'amount' => 500000,
            'currency' => 'KRW',
            'sales_date' => now()->format('Y-m-d'),
            'category' => 'service',
            'product_type' => 'consulting',
            'status' => 'confirmed'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 매출 정보 확인
        $response->assertSee('5,000,000'); // total_sales
        $response->assertSee('1,500,000'); // monthly_sales
        $response->assertSee('2,000,000'); // team_sales

        // 매출 관련 ID 속성 확인
        $response->assertSee('id="total-sales"', false);
        $response->assertSee('id="monthly-sales"', false);
        $response->assertSee('id="team-sales"', false);
        $response->assertSee('id="total-sales-count"', false);
    }

    /** @test */
    public function dashboard_displays_commission_information()
    {
        // 테스트용 커미션 데이터 생성
        PartnerCommission::create([
            'partner_id' => $this->partner->id,
            'source_partner_id' => $this->partner->id,
            'amount' => 50000,
            'commission_type' => 'direct',
            'commission_rate' => 8.0,
            'source_amount' => 625000,
            'level' => 0,
            'status' => 'paid',
            'description' => '직접 커미션'
        ]);

        PartnerCommission::create([
            'partner_id' => $this->partner->id,
            'source_partner_id' => $this->partner->id,
            'amount' => 30000,
            'commission_type' => 'team',
            'commission_rate' => 3.0,
            'source_amount' => 1000000,
            'level' => 1,
            'status' => 'pending',
            'description' => '팀 커미션'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 커미션 관련 ID 속성 확인
        $response->assertSee('id="total-commission"', false);
        $response->assertSee('id="pending-commission"', false);
        $response->assertSee('id="monthly-commission"', false);
        $response->assertSee('id="commission-count"', false);
    }

    /** @test */
    public function dashboard_displays_recent_sales_records()
    {
        // 최근 매출 기록 생성
        $sale1 = PartnerSales::create([
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'partner_email' => $this->partner->email,
            'title' => '웹사이트 개발 프로젝트',
            'amount' => 1000000,
            'currency' => 'KRW',
            'sales_date' => now()->format('Y-m-d'),
            'category' => 'service',
            'product_type' => 'development',
            'status' => 'confirmed'
        ]);

        $sale2 = PartnerSales::create([
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'partner_email' => $this->partner->email,
            'title' => '마케팅 컨설팅',
            'amount' => 750000,
            'currency' => 'KRW',
            'sales_date' => now()->subDays(1)->format('Y-m-d'),
            'category' => 'service',
            'product_type' => 'consulting',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 매출 기록 표시 확인
        $response->assertSee('웹사이트 개발 프로젝트');
        $response->assertSee('마케팅 컨설팅');
        $response->assertSee('1,000,000원');
        $response->assertSee('750,000원');

        // 매출 기록 관련 ID와 클래스 확인
        $response->assertSee('id="sales-records"', false);
        $response->assertSee('class="sales-record"', false);
        $response->assertSee('class="sales-title"', false);
        $response->assertSee('class="sales-amount"', false);
        $response->assertSee('class="sales-status"', false);
        $response->assertSee('data-sale-id="' . $sale1->id . '"', false);
    }

    /** @test */
    public function dashboard_displays_no_sales_message_when_no_records()
    {
        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);
        $response->assertSee('아직 매출 기록이 없습니다');
        $response->assertSee('id="no-sales-message"', false);
    }

    /** @test */
    public function dashboard_displays_sub_partners_information()
    {
        // 하위 파트너 생성
        $subPartnerUser = User::factory()->create(['name' => '하위 파트너 1']);
        $subPartner = PartnerUser::create([
            'user_id' => $subPartnerUser->id,
            'partner_type_id' => $this->partnerType->id,
            'partner_tier_id' => $this->partnerTier->id,
            'name' => '하위 파트너 1',
            'email' => 'sub1@example.com',
            'phone' => '010-1111-2222',
            'monthly_sales' => 800000,
            'total_sales' => 2000000,
            'level' => 2,
            'path' => '/1/2',
            'is_active' => true,
            'partner_joined_at' => now()->format('Y-m-d'),
            'tier_assigned_at' => now()->format('Y-m-d'),
            'status' => 'active'
        ]);

        // 네트워크 관계 생성
        PartnerNetworkRelationship::create([
            'parent_id' => $this->partner->id,
            'child_id' => $subPartner->id,
            'recruiter_id' => $this->partner->id,
            'relationship_type' => 'direct',
            'depth' => 1,
            'relationship_path' => '/1/2',
            'recruited_at' => now(),
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 하위 파트너 정보 확인
        $response->assertSee('하위 파트너 1');
        $response->assertSee('sub1@example.com');
        $response->assertSee('800,000'); // monthly sales
        $response->assertSee('2,000,000'); // total sales

        // 하위 파트너 관련 ID와 클래스 확인
        $response->assertSee('id="sub-partners-list"', false);
        $response->assertSee('class="sub-partner"', false);
        $response->assertSee('class="sub-partner-name"', false);
        $response->assertSee('class="sub-partner-email"', false);
        $response->assertSee('data-partner-id="' . $subPartner->id . '"', false);
    }

    /** @test */
    public function dashboard_displays_no_sub_partners_message_when_none_exist()
    {
        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);
        $response->assertSee('아직 하위 파트너가 없습니다');
        $response->assertSee('id="no-sub-partners-message"', false);
    }

    /** @test */
    public function dashboard_displays_network_information()
    {
        // 상위 파트너 생성
        $parentUser = User::factory()->create(['name' => '상위 파트너']);
        $parentPartner = PartnerUser::create([
            'user_id' => $parentUser->id,
            'partner_type_id' => $this->partnerType->id,
            'partner_tier_id' => $this->partnerTier->id,
            'name' => '상위 파트너',
            'email' => 'parent@example.com',
            'phone' => '010-0000-1111',
            'level' => 0,
            'path' => '/',
            'is_active' => true,
            'partner_joined_at' => now()->format('Y-m-d'),
            'tier_assigned_at' => now()->format('Y-m-d'),
            'status' => 'active'
        ]);

        // 네트워크 관계 생성 (현재 파트너가 하위)
        PartnerNetworkRelationship::create([
            'parent_id' => $parentPartner->id,
            'child_id' => $this->partner->id,
            'recruiter_id' => $parentPartner->id,
            'relationship_type' => 'direct',
            'depth' => 1,
            'relationship_path' => '/0/1',
            'recruited_at' => now()
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 네트워크 정보 확인
        $response->assertSee('상위 파트너'); // parent partner name
        $response->assertSee('2명'); // children count (from partner setup)

        // 네트워크 관련 ID 확인
        $response->assertSee('id="parent-partner"', false);
        $response->assertSee('id="children-count"', false);
    }

    /** @test */
    public function dashboard_displays_additional_statistics()
    {
        // 여러 매출 기록 생성
        PartnerSales::factory()->count(3)->create([
            'partner_id' => $this->partner->id,
            'status' => 'confirmed'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/home/partner');

        $response->assertStatus(200);

        // 추가 통계 ID 확인
        $response->assertSee('id="additional-stats"', false);
        $response->assertSee('id="current-year-sales"', false);
    }
}