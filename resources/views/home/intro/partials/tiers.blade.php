<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
</style>

<!-- Partner Tiers Information -->
@if (isset($partnerTiers) && $partnerTiers->count() > 0)
    <section class="mt-5">
        <div class="mb-5">
            <div class="d-flex align-items-center mb-4">
                <i class="bi bi-award text-primary fs-3 me-3"></i>
                <h3 class="h4 mb-0 fw-bold">파트너 등급 안내</h3>
            </div>

            <div class="row g-4">
                @foreach ($partnerTiers as $tier)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-gradient bg-primary text-white border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0 fw-bold">{{ $tier->tier_name }}</h5>
                                    <span class="badge bg-light text-primary fw-bold">
                                        {{ $tier->commission_rate }}% 커미션
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-4">{{ $tier->description }}</p>

                                <!-- Requirements -->
                                @if (isset($tier->requirements) && is_array($tier->requirements))
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-success mb-3">
                                            <i class="bi bi-check-circle me-1"></i>가입 요건
                                        </h6>
                                        <ul class="list-unstyled">
                                            @foreach ($tier->requirements as $key => $value)
                                                <li class="d-flex align-items-start mb-2">
                                                    <i class="bi bi-check text-success me-2 mt-1"></i>
                                                    <small class="text-muted">
                                                        @if ($key === 'min_experience_months')
                                                            최소 {{ $value }}개월 경력
                                                        @elseif($key === 'min_completed_jobs')
                                                            최소 {{ number_format($value) }}건 완료
                                                        @elseif($key === 'min_rating')
                                                            최소 {{ $value }}점 평점
                                                        @else
                                                            {{ $key }}:
                                                            {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        @endif
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Benefits -->
                                @if (isset($tier->benefits) && is_array($tier->benefits))
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-info mb-3">
                                            <i class="bi bi-gift me-1"></i>혜택
                                        </h6>
                                        <ul class="list-unstyled">
                                            @foreach ($tier->benefits as $key => $value)
                                                <li class="d-flex align-items-start mb-2">
                                                    <i class="bi bi-star text-warning me-2 mt-1"></i>
                                                    <small class="text-muted">
                                                        @if ($key === 'job_assignment_priority')
                                                            {{ $value === 'high' ? '높은' : '일반' }} 우선순위 업무 배정
                                                        @elseif($key === 'maximum_concurrent_jobs')
                                                            최대 {{ $value }}개 동시 업무
                                                        @elseif($key === 'support_response_time')
                                                            {{ $value }} 내 지원 응답
                                                        @else
                                                            {{ $key }}:
                                                            {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        @endif
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
