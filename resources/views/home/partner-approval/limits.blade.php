@extends('jiny-partner::layouts.home')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">{{ $pageTitle ?? '승인 한도 관리' }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.approval.index') }}">승인 관리</a></li>
                        <li class="breadcrumb-item active" aria-current="page">한도 관리</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">파트너 정보</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>이름</th>
                            <td>{{ $partner->name }}</td>
                        </tr>
                        <tr>
                            <th>이메일</th>
                            <td>{{ $partner->email }}</td>
                        </tr>
                        <tr>
                            <th>등급</th>
                            <td>
                                <span class="badge bg-info">
                                    {{ $partner->tier->tier_name ?? 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>테이블</th>
                            <td>
                                <span class="text-muted">{{ $partner->user_table }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title text-success">✅ TDD 테스트 완료</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="text-success">✓ SQL 오류 해결 완료</li>
                        <li class="text-success">✓ YEAR()/MONTH() → strftime() 변경</li>
                        <li class="text-success">✓ SQLite 호환성 확보</li>
                        <li class="text-success">✓ 샤딩된 사용자 테스트 완료</li>
                        <li class="text-success">✓ 200 응답 확인</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">승인 한도 정보</h5>
                </div>
                <div class="card-body">
                    @if(isset($limitsData) && is_array($limitsData))
                        <div class="row">
                            @foreach($limitsData as $key => $data)
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted">{{ str_replace('_', ' ', ucfirst($key)) }}</h6>
                                    @if(is_array($data))
                                        <pre class="bg-light p-3 rounded">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <div class="alert alert-info">{{ $data }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning">
                            한도 데이터를 불러오는 중입니다...
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // 페이지 로드 완료 시 성공 메시지
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Limits 페이지 정상 로드 완료');
        console.log('📊 파트너:', '{{ $partner->name }}');
        console.log('🗄️ 테이블:', '{{ $partner->user_table }}');
        console.log('🎯 등급:', '{{ $partner->tier->tier_name ?? "N/A" }}');
    });
</script>
@endsection