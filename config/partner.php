<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Partner Package Configuration
    |--------------------------------------------------------------------------
    |
    | 파트너 패키지의 기본 설정값들을 정의합니다.
    |
    */

    'default_tier' => 'bronze',

    'tier_requirements' => [
        'bronze' => [
            'min_rating' => 0,
            'min_jobs' => 0,
            'min_earnings' => 0,
        ],
        'silver' => [
            'min_rating' => 4.0,
            'min_jobs' => 50,
            'min_earnings' => 500000,
        ],
        'gold' => [
            'min_rating' => 4.3,
            'min_jobs' => 100,
            'min_earnings' => 1000000,
        ],
        'platinum' => [
            'min_rating' => 4.5,
            'min_jobs' => 200,
            'min_earnings' => 2000000,
        ],
        'diamond' => [
            'min_rating' => 4.7,
            'min_jobs' => 500,
            'min_earnings' => 5000000,
        ],
    ],

    'commission_rates' => [
        'bronze' => 15.0,
        'silver' => 18.0,
        'gold' => 20.0,
        'platinum' => 22.0,
        'diamond' => 25.0,
    ],

    'service_types' => [
        'air_conditioning' => '에어컨',
        'plumbing' => '배관',
        'electrical' => '전기',
        'cleaning' => '청소',
        'maintenance' => '수리',
        'general' => '일반',
    ],

    'proficiency_levels' => [
        'beginner' => '초급',
        'intermediate' => '중급',
        'advanced' => '고급',
        'expert' => '전문가',
    ],

    'application_statuses' => [
        'submitted' => '지원완료',
        'reviewing' => '검토중',
        'interview' => '면접진행',
        'approved' => '승인',
        'rejected' => '거절',
    ],

    'engineer_statuses' => [
        'pending' => '대기',
        'active' => '활성',
        'inactive' => '비활성',
        'suspended' => '정지',
    ],
];