@extends('jiny-partner::layouts.home')

@section('title', 'Customer Search Test')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Search Component Test</h5>
                </div>
                <div class="card-body">
                    {{-- Livewire Customer Component --}}
                    @livewire('jiny-partner::customer', [
                        'placeholder' => '고객 이름 또는 이메일을 입력하세요'
                    ])

                    <div class="mt-4">
                        <h6>Debug Info:</h6>
                        <div id="debug-info" class="alert alert-info">
                            Livewire component should appear above. Check browser console for debug logs.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', function () {
    console.log('Livewire initialized for test page');

    // Debug events
    document.addEventListener('livewire-customer-selected', function(event) {
        document.getElementById('debug-info').innerHTML = 'Customer selected: ' + JSON.stringify(event.detail.customer);
    });

    document.addEventListener('livewire-customer-cleared', function(event) {
        document.getElementById('debug-info').innerHTML = 'Customer selection cleared';
    });

    // 자동으로 test 검색 실행
    setTimeout(function() {
        console.log('Auto-searching for "test"...');
        Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('searchQuery', 'test');
    }, 2000);
});
</script>
@endsection