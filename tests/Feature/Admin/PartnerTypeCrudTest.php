<?php

namespace Jiny\Partner\Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Jiny\Partner\Models\PartnerType;
use App\Models\User;

class PartnerTypeCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin 사용자 생성
        $this->adminUser = User::factory()->create([
            'isAdmin' => true,
            'utype' => 'admin'
        ]);
    }

    /** @test */
    public function admin_can_view_partner_types_index()
    {
        // Given: 파트너 타입이 존재함
        $partnerTypes = PartnerType::factory()->count(3)->create();

        // When: 관리자가 파트너 타입 목록을 조회함
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/partner/type');

        // Then: 성공적으로 목록이 표시됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-type.index');

        foreach ($partnerTypes as $partnerType) {
            $response->assertSee($partnerType->type_name);
            $response->assertSee($partnerType->type_code);
        }
    }

    /** @test */
    public function admin_can_create_partner_type()
    {
        // Given: 파트너 타입 생성 데이터
        $partnerTypeData = [
            'type_code' => 'TEST_TYPE',
            'type_name' => '테스트 파트너 타입',
            'description' => '테스트용 파트너 타입입니다.',
            'icon' => 'fe-test',
            'color' => '#ff6b35',
            'sort_order' => 10,
            'specialties' => ['test_specialty', 'another_specialty'],
            'required_skills' => ['skill_1', 'skill_2'],
            'min_baseline_sales' => 1000000,
            'min_baseline_cases' => 50,
            'min_baseline_revenue' => 500000,
            'min_baseline_clients' => 10,
            'baseline_quality_score' => 85.5,
            'default_commission_type' => 'percentage',
            'default_commission_rate' => 10.0,
            'commission_notes' => '테스트 수수료 관련 내용',
            'registration_fee' => 100000,
            'monthly_maintenance_fee' => 50000,
            'annual_maintenance_fee' => 500000,
            'fee_waiver_available' => true,
            'fee_structure_notes' => '테스트 비용 구조 관련 내용',
            'is_active' => true,
            'admin_notes' => '테스트용 관리자 메모'
        ];

        // When: 관리자가 파트너 타입을 생성함
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/partner/type', $partnerTypeData);

        // Then: 파트너 타입이 생성되고 상세 페이지로 리다이렉트됨
        $this->assertDatabaseHas('partner_types', [
            'type_code' => 'TEST_TYPE',
            'type_name' => '테스트 파트너 타입',
            'description' => '테스트용 파트너 타입입니다.',
            'min_baseline_sales' => 1000000,
            'default_commission_type' => 'percentage',
            'default_commission_rate' => 10.0,
            'created_by' => $this->adminUser->id
        ]);

        $partnerType = PartnerType::where('type_code', 'TEST_TYPE')->first();
        $response->assertRedirect('/admin/partner/type/' . $partnerType->id);
    }

    /** @test */
    public function admin_can_view_partner_type_details()
    {
        // Given: 파트너 타입이 존재함
        $partnerType = PartnerType::factory()->create([
            'type_code' => 'DETAIL_TEST',
            'type_name' => '상세보기 테스트',
            'specialties' => ['specialty1', 'specialty2'],
            'required_skills' => ['skill1', 'skill2']
        ]);

        // When: 관리자가 파트너 타입 상세를 조회함
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/partner/type/{$partnerType->id}");

        // Then: 성공적으로 상세 정보가 표시됨
        $response->assertStatus(200);
        $response->assertViewIs('jiny-partner::admin.partner-type.show');
        $response->assertSee($partnerType->type_name);
        $response->assertSee($partnerType->type_code);
    }

    /** @test */
    public function admin_can_update_partner_type()
    {
        // Given: 기존 파트너 타입이 존재함
        $partnerType = PartnerType::factory()->create([
            'type_code' => 'UPDATE_TEST',
            'type_name' => '업데이트 테스트'
        ]);

        $updateData = [
            'type_code' => 'UPDATE_TEST',
            'type_name' => '업데이트된 파트너 타입',
            'description' => '업데이트된 설명',
            'min_baseline_sales' => 2000000,
            'default_commission_type' => 'fixed_amount',
            'default_commission_amount' => 50000,
            'is_active' => true,
            'fee_waiver_available' => false
        ];

        // When: 관리자가 파트너 타입을 수정함
        $response = $this->actingAs($this->adminUser)
            ->put("/admin/partner/type/{$partnerType->id}", $updateData);

        // Then: 파트너 타입이 수정되고 상세 페이지로 리다이렉트됨
        $this->assertDatabaseHas('partner_types', [
            'id' => $partnerType->id,
            'type_name' => '업데이트된 파트너 타입',
            'description' => '업데이트된 설명',
            'min_baseline_sales' => 2000000,
            'default_commission_type' => 'fixed_amount',
            'default_commission_amount' => 50000,
            'updated_by' => $this->adminUser->id
        ]);

        $response->assertRedirect("/admin/partner/type/{$partnerType->id}");
    }

    /** @test */
    public function admin_can_delete_partner_type()
    {
        // Given: 파트너 타입이 존재함
        $partnerType = PartnerType::factory()->create([
            'type_code' => 'DELETE_TEST',
            'type_name' => '삭제 테스트'
        ]);

        // When: 관리자가 파트너 타입을 삭제함
        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/partner/type/{$partnerType->id}");

        // Then: 파트너 타입이 소프트 삭제됨
        $this->assertSoftDeleted('partner_types', [
            'id' => $partnerType->id
        ]);

        $response->assertRedirect('/admin/partner/type');
    }

    /** @test */
    public function partner_type_creation_validates_required_fields()
    {
        // Given: 필수 필드가 누락된 데이터
        $invalidData = [
            'description' => '설명만 있음'
            // type_code와 type_name이 누락됨
        ];

        // When: 관리자가 잘못된 데이터로 파트너 타입 생성을 시도함
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/partner/type', $invalidData);

        // Then: 유효성 검사 오류가 발생함
        $response->assertSessionHasErrors(['type_code', 'type_name']);
    }

    /** @test */
    public function partner_type_code_must_be_unique()
    {
        // Given: 기존 파트너 타입이 존재함
        $existingPartnerType = PartnerType::factory()->create([
            'type_code' => 'UNIQUE_TEST'
        ]);

        $duplicateData = [
            'type_code' => 'UNIQUE_TEST', // 중복된 코드
            'type_name' => '중복 테스트',
            'default_commission_type' => 'percentage',
            'is_active' => true
        ];

        // When: 관리자가 중복된 코드로 파트너 타입 생성을 시도함
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/partner/type', $duplicateData);

        // Then: 유효성 검사 오류가 발생함
        $response->assertSessionHasErrors(['type_code']);
    }

    /** @test */
    public function partner_type_index_can_be_filtered_by_active_status()
    {
        // Given: 활성/비활성 파트너 타입들이 존재함
        $activeType = PartnerType::factory()->create(['is_active' => true]);
        $inactiveType = PartnerType::factory()->create(['is_active' => false]);

        // When: 활성 상태 필터로 조회함
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/partner/type?is_active=1');

        // Then: 활성 타입만 표시됨
        $response->assertStatus(200);
        $response->assertSee($activeType->type_name);
        $response->assertDontSee($inactiveType->type_name);
    }

    /** @test */
    public function partner_type_index_can_be_searched()
    {
        // Given: 검색 가능한 파트너 타입들이 존재함
        $searchableType = PartnerType::factory()->create([
            'type_name' => '검색 테스트 타입'
        ]);
        $otherType = PartnerType::factory()->create([
            'type_name' => '다른 타입'
        ]);

        // When: 검색어로 조회함
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/partner/type?search=검색');

        // Then: 검색 결과만 표시됨
        $response->assertStatus(200);
        $response->assertSee($searchableType->type_name);
        $response->assertDontSee($otherType->type_name);
    }
}