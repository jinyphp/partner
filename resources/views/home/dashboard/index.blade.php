@extends($layout ?? 'jiny-auth::layouts.home')

@section('title', '파트너 대시보드')

@section('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- 파트너 기본 정보 카드 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6" id="partner-info-card">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-800">파트너 대시보드</h1>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium" id="partner-tier">
                    {{ $partner->partnerTier->tier_name ?? '미설정' }}
                </span>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium" id="partner-type">
                    {{ $partner->partnerType->type_name ?? '미설정' }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- 파트너 기본 정보 -->
            <div class="bg-gray-50 rounded-lg p-4" id="partner-basic-info">
                <h3 class="text-sm font-medium text-gray-500 mb-2">파트너 정보</h3>
                <div class="space-y-1">
                    <p class="text-sm"><span class="font-medium">이름:</span> <span id="partner-name">{{ $partner->name }}</span></p>
                    <p class="text-sm"><span class="font-medium">이메일:</span> <span id="partner-email">{{ $partner->email }}</span></p>
                    <p class="text-sm"><span class="font-medium">레벨:</span> <span id="partner-level">{{ $networkInfo['level'] }}</span></p>
                    <p class="text-sm"><span class="font-medium">경로:</span> <span id="partner-path">{{ $networkInfo['path'] }}</span></p>
                </div>
            </div>

            <!-- 네트워크 정보 -->
            <div class="bg-blue-50 rounded-lg p-4" id="network-info">
                <h3 class="text-sm font-medium text-blue-700 mb-2">네트워크</h3>
                <div class="space-y-1">
                    <p class="text-sm">
                        <span class="font-medium">상위 파트너:</span>
                        <span id="parent-partner">
                            {{ $networkInfo['parent_partner']->name ?? '없음' }}
                        </span>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">하위 파트너:</span>
                        <span id="children-count">{{ $networkInfo['children_count'] }}명</span>
                    </p>
                </div>
            </div>

            <!-- 매출 정보 -->
            <div class="bg-green-50 rounded-lg p-4" id="sales-info">
                <h3 class="text-sm font-medium text-green-700 mb-2">매출 정보</h3>
                <div class="space-y-1">
                    <p class="text-sm">
                        <span class="font-medium">이번 달:</span>
                        <span id="monthly-sales" class="font-bold text-green-600">
                            {{ number_format($salesStats['current_month_sales']) }}원
                        </span>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">총 매출:</span>
                        <span id="total-sales" class="font-bold">
                            {{ number_format($salesStats['total_sales']) }}원
                        </span>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">팀 매출:</span>
                        <span id="team-sales" class="font-bold">
                            {{ number_format($salesStats['team_sales']) }}원
                        </span>
                    </p>
                </div>
            </div>

            <!-- 커미션 정보 -->
            <div class="bg-yellow-50 rounded-lg p-4" id="commission-info">
                <h3 class="text-sm font-medium text-yellow-700 mb-2">커미션 정보</h3>
                <div class="space-y-1">
                    <p class="text-sm">
                        <span class="font-medium">이번 달:</span>
                        <span id="monthly-commission" class="font-bold text-yellow-600">
                            {{ number_format($commissionStats['this_month_commission']) }}원
                        </span>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">총 커미션:</span>
                        <span id="total-commission" class="font-bold">
                            {{ number_format($commissionStats['total_commission']) }}원
                        </span>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">대기중:</span>
                        <span id="pending-commission" class="font-bold text-orange-600">
                            {{ number_format($commissionStats['pending_commission']) }}원
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 최근 매출 기록 -->
        <div class="bg-white rounded-lg shadow-md" id="recent-sales-section">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">최근 매출 기록</h2>
            </div>
            <div class="p-6">
                @if($recentSales->count() > 0)
                    <div class="space-y-4" id="sales-records">
                        @foreach($recentSales as $sale)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg sales-record"
                             data-sale-id="{{ $sale->id }}">
                            <div>
                                <p class="font-medium text-gray-800 sales-title">{{ $sale->title }}</p>
                                <p class="text-sm text-gray-500 sales-date">{{ $sale->sales_date }}</p>
                                <p class="text-sm sales-category">{{ $sale->category }} - {{ $sale->product_type }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600 sales-amount">
                                    {{ number_format($sale->amount) }}원
                                </p>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full sales-status
                                    @if($sale->status === 'confirmed') bg-green-100 text-green-800
                                    @elseif($sale->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $sale->status }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8" id="no-sales-message">아직 매출 기록이 없습니다.</p>
                @endif
            </div>
        </div>

        <!-- 하위 파트너 정보 -->
        <div class="bg-white rounded-lg shadow-md" id="sub-partners-section">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">하위 파트너</h2>
            </div>
            <div class="p-6">
                @if($subPartners->count() > 0)
                    <div class="space-y-4" id="sub-partners-list">
                        @foreach($subPartners as $subPartner)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg sub-partner"
                             data-partner-id="{{ $subPartner->id }}">
                            <div>
                                <p class="font-medium text-gray-800 sub-partner-name">{{ $subPartner->name }}</p>
                                <p class="text-sm text-gray-500 sub-partner-email">{{ $subPartner->email }}</p>
                                <div class="flex space-x-2 mt-1">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded sub-partner-tier">
                                        {{ $subPartner->partnerTier->tier_name ?? '미설정' }}
                                    </span>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded sub-partner-type">
                                        {{ $subPartner->partnerType->type_name ?? '미설정' }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600 sub-partner-monthly-sales">
                                    월 매출: {{ number_format($subPartner->monthly_sales ?? 0) }}원
                                </p>
                                <p class="text-sm text-gray-600 sub-partner-total-sales">
                                    총 매출: {{ number_format($subPartner->total_sales ?? 0) }}원
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8" id="no-sub-partners-message">아직 하위 파트너가 없습니다.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- 추가 통계 정보 -->
    <div class="bg-white rounded-lg shadow-md mt-6 p-6" id="additional-stats">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">상세 통계</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600" id="total-sales-count">{{ $salesStats['total_sales_count'] }}</p>
                <p class="text-sm text-blue-700">총 거래 건수</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600" id="current-year-sales">
                    {{ number_format($salesStats['current_year_sales']) }}원
                </p>
                <p class="text-sm text-green-700">올해 매출</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600" id="commission-count">{{ $commissionStats['commission_count'] }}</p>
                <p class="text-sm text-yellow-700">커미션 건수</p>
            </div>
        </div>
    </div>
</div>
@endsection