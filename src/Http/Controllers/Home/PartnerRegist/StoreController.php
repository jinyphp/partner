<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class StoreController extends PartnerController
{

    /**
     * 파트너 신청서 제출 처리
     */
    public function __invoke(Request $request)
    {
        \Log::info('StoreController: Form submission started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'input_keys' => array_keys($request->all()),
            'has_resume' => $request->hasFile('resume'),
            'has_portfolio' => $request->hasFile('portfolio'),
            'has_other_docs' => $request->hasFile('other_documents')
        ]);

        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            \Log::warning('StoreController: User not authenticated');
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        \Log::info('StoreController: User authenticated', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'user_email' => $user->email,
            'user_name' => $user->name
        ]);

        // Step2. 파트너 등록 여부 확인 (UUID 기반)
        $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
            ->exists();

        if ($isPartnerUser) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '이미 파트너로 등록되어 있습니다.');
        }

        // Step3. 진행 중인 신청서가 있는지 확인 (UUID 기반)
        $existingApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->first();

        if ($existingApplication) {
            \Log::info('StoreController: Existing application found', [
                'application_id' => $existingApplication->id,
                'status' => $existingApplication->application_status,
                'user_uuid' => $user->uuid
            ]);

            // 'approved' 상태의 신청서가 있지만 파트너 사용자로 등록되지 않은 경우
            if ($existingApplication->application_status === 'approved' && !$isPartnerUser) {
                \Log::warning('StoreController: Approved application exists but partner user not created', [
                    'application_id' => $existingApplication->id,
                    'user_uuid' => $user->uuid
                ]);

                return redirect()->route('home.partner.regist.status')
                    ->with('info', '승인된 신청서가 있습니다. 파트너 등록이 완료되지 않은 경우 관리자에게 문의해주세요.');
            }

            // 다른 진행 중인 신청서가 있는 경우
            $statusMessages = [
                'submitted' => '제출된 신청서가 검토 중입니다.',
                'reviewing' => '신청서가 검토 중입니다.',
                'interview' => '면접이 예정되어 있습니다.',
                'approved' => '이미 승인된 신청서가 있습니다.'
            ];

            $message = $statusMessages[$existingApplication->application_status] ?? '이미 진행 중인 신청이 있습니다.';

            return redirect()->route('home.partner.regist.status')
                ->with('info', $message);
        }

        \Log::info('StoreController: Starting validation', [
            'validation_rules_count' => count($this->getValidationRules())
        ]);

        // 유효성 검사
        try {
            $validatedData = $request->validate($this->getValidationRules(), $this->getValidationMessages());
            \Log::info('StoreController: Validation passed', [
                'validated_keys' => array_keys($validatedData)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('StoreController: Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);

            // AJAX 요청인 경우 JSON 오류 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 데이터가 올바르지 않습니다.',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;
        }

        try {
            DB::beginTransaction();

            // 기존 draft 삭제 (UUID 기반) - 안전한 삭제
            $deletedCount = PartnerApplication::where('user_uuid', $user->uuid)
                ->where('application_status', 'draft')
                ->delete();

            if ($deletedCount > 0) {
                \Log::info('StoreController: Deleted existing draft applications', [
                    'deleted_count' => $deletedCount,
                    'user_uuid' => $user->uuid
                ]);
            }

            // 중복 방지를 위한 추가 체크 (UUID 기반으로 정확한 확인)
            $activeApplicationCount = PartnerApplication::where('user_uuid', $user->uuid)
                ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
                ->whereNull('deleted_at')
                ->count();

            if ($activeApplicationCount > 0) {
                \Log::warning('StoreController: Active application found during transaction', [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'active_count' => $activeApplicationCount
                ]);

                throw new \Exception('이미 처리 중인 신청서가 있습니다. 잠시 후 다시 시도해주세요.');
            }

            // 파트너 사용자 등록 여부도 UUID 기반으로 재확인
            $existingPartnerUser = PartnerUser::where('user_uuid', $user->uuid)->first();
            if ($existingPartnerUser) {
                \Log::warning('StoreController: Partner user found during transaction', [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'partner_id' => $existingPartnerUser->id,
                    'partner_code' => $existingPartnerUser->partner_code
                ]);

                throw new \Exception('이미 파트너로 등록되어 있습니다.');
            }

            // 파일 업로드 처리
            $documents = $this->handleFileUploads($request, $user->id);

            // 신청서 데이터 구성 (UpdateController와 일치하는 구조)
            $applicationData = [
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'shard_number' => $user->shard_number ?? null,
                'application_status' => $request->input('submit_type') === 'draft' ? 'draft' : 'submitted',
                'personal_info' => [
                    'name' => $validatedData['name'],
                    'email' => $user->email,
                    'phone' => $validatedData['phone'],
                    'country' => $validatedData['country'],
                    'address' => $validatedData['address']
                ],
                'experience_info' => [
                    'total_years' => $validatedData['total_years'] ?? null,
                    'career_summary' => $validatedData['career_summary'] ?? null,
                    'portfolio_url' => $validatedData['portfolio_url'] ?? null,
                    'bio' => $validatedData['bio'] ?? null
                ],
                'skills_info' => [
                    'skills' => array_filter($validatedData['skills'] ?? [], function($skill) {
                        return !empty(trim($skill));
                    }),
                    'skill_levels' => $this->parseSkillLevels($validatedData),
                    'certifications' => array_filter($validatedData['certifications'] ?? [], function($cert) {
                        return !empty(trim($cert));
                    }),
                    'languages' => array_filter($validatedData['languages'] ?? [], function($lang) {
                        return !empty(trim($lang));
                    })
                ],
                'expected_hourly_rate' => $validatedData['expected_hourly_rate'] ?? null,
                'preferred_work_areas' => [
                    'regions' => $validatedData['preferred_regions'] ?? [],
                    'max_distance_km' => $validatedData['max_distance_km'] ?? null
                ],
                'availability_schedule' => [
                    'weekdays' => $this->parseWeekdaySchedule($validatedData),
                    'weekend' => [
                        'saturday' => ['available' => $request->boolean('saturday_available')],
                        'sunday' => ['available' => $request->boolean('sunday_available')]
                    ],
                    'holiday_work' => $request->boolean('holiday_work')
                ],
                'documents' => $documents
            ];

            // 추천 파트너 정보 처리 (세션 기반)
            $referrerPartnerId = Session::get('referrer_partner_id');
            $referrerPartnerCode = Session::get('referrer_partner_code');
            $referrerInfo = Session::get('referrer_info');

            if ($referrerPartnerId) {
                // 세션에서 추천인 정보가 있는 경우 (ReferralController를 통한 접근)
                $referrerPartner = PartnerUser::find($referrerPartnerId);

                if ($referrerPartner && $referrerPartner->status === 'active') {
                    Log::info('StoreController: Processing referral from session', [
                        'referrer_partner_id' => $referrerPartnerId,
                        'referrer_partner_code' => $referrerPartnerCode,
                        'referrer_name' => $referrerInfo['name'] ?? 'Unknown',
                        'applicant_uuid' => $user->uuid
                    ]);

                    // 추천 파트너 정보 설정
                    $applicationData['referrer_partner_id'] = $referrerPartner->id;
                    $applicationData['referral_code'] = $referrerPartnerCode;
                    $applicationData['referral_source'] = 'online_link'; // 파트너 코드를 통한 온라인 링크 추천
                    $applicationData['referral_registered_at'] = now();
                    $applicationData['referral_bonus_eligible'] = true;

                    // 추천 상세 정보 JSON 구성 (세션에서)
                    $referralDetails = [
                        'referrer_name' => $referrerInfo['name'] ?? $referrerPartner->name,
                        'referrer_email' => $referrerInfo['email'] ?? $referrerPartner->email,
                        'referrer_tier' => $referrerInfo['tier'] ?? 'Unknown',
                        'referral_method' => 'partner_code_url',
                        'referral_timestamp' => now()->toISOString()
                    ];
                    $applicationData['referral_details'] = $referralDetails;

                    // 계층 구조 정보 계산
                    $applicationData['expected_tier_level'] = ($referrerPartner->level ?? 0) + 1;
                    $applicationData['expected_tier_path'] = ($referrerPartner->tree_path ?? '') . '/' . $referrerPartner->id;

                    // 커미션 계산 (예시: 상위 파트너의 80% 적용)
                    $personalCommissionRate = $referrerPartner->personal_commission_rate ?? 0.10; // 기본 10%
                    $applicationData['expected_commission_rate'] = $personalCommissionRate * 0.8;

                    Log::info('StoreController: Referral hierarchy calculated', [
                        'expected_tier_level' => $applicationData['expected_tier_level'],
                        'expected_tier_path' => $applicationData['expected_tier_path'],
                        'expected_commission_rate' => $applicationData['expected_commission_rate']
                    ]);
                }
            } else {
                // 폼 데이터에서 추천 정보 처리 (기존 로직 유지)
                if (!empty($validatedData['referral_code']) || ($validatedData['referral_source'] ?? 'self_application') !== 'self_application') {
                    // 추천 코드로 파트너 검색
                    $referrerPartner = null;
                    if (!empty($validatedData['referral_code'])) {
                        $referrerPartner = PartnerUser::where('partner_code', $validatedData['referral_code'])
                            ->where('status', 'active')
                            ->first();
                    }

                    // 추천 파트너 정보 설정
                    $applicationData['referrer_partner_id'] = $referrerPartner ? $referrerPartner->id : null;
                    $applicationData['referral_code'] = $validatedData['referral_code'] ?? null;
                    $applicationData['referral_source'] = $validatedData['referral_source'] ?? 'self_application';
                    $applicationData['referral_registered_at'] = now();
                    $applicationData['referral_bonus_eligible'] = $referrerPartner ? true : false;

                    // 추천 상세 정보 JSON 구성
                    $referralDetails = [];
                    if (!empty($validatedData['referrer_name'])) {
                        $referralDetails['referrer_name'] = $validatedData['referrer_name'];
                    }
                    if (!empty($validatedData['referrer_contact'])) {
                        $referralDetails['referrer_contact'] = $validatedData['referrer_contact'];
                    }
                    if (!empty($validatedData['referrer_relationship'])) {
                        $referralDetails['referrer_relationship'] = $validatedData['referrer_relationship'];
                    }
                    if (!empty($validatedData['meeting_date'])) {
                        $referralDetails['meeting_date'] = $validatedData['meeting_date'];
                    }
                    if (!empty($validatedData['meeting_location'])) {
                        $referralDetails['meeting_location'] = $validatedData['meeting_location'];
                    }
                    if (!empty($validatedData['introduction_method'])) {
                        $referralDetails['introduction_method'] = $validatedData['introduction_method'];
                    }
                    if (!empty($validatedData['motivation'])) {
                        $referralDetails['motivation'] = $validatedData['motivation'];
                    }

                    if (!empty($referralDetails)) {
                        $applicationData['referral_details'] = $referralDetails;
                    }

                    // 계층 구조 예상 정보 계산
                    if ($referrerPartner) {
                        $applicationData['expected_tier_level'] = ($referrerPartner->level ?? 0) + 1;
                        $applicationData['expected_tier_path'] = ($referrerPartner->tree_path ?? '') . '/' . $referrerPartner->id;
                        $applicationData['expected_commission_rate'] = ($referrerPartner->personal_commission_rate ?? 0.10) * 0.8; // 예시: 80% 적용
                    }
                }
            }

            // 신청서 생성
            \Log::info('StoreController: Creating application', [
                'application_data_keys' => array_keys($applicationData),
                'status' => $applicationData['application_status']
            ]);

            $application = PartnerApplication::create($applicationData);

            \Log::info('StoreController: Application created successfully', [
                'application_id' => $application->id,
                'status' => $application->application_status,
                'has_referrer' => isset($applicationData['referrer_partner_id'])
            ]);

            // 추천인 정보 세션 정리
            $referralMessage = '';
            if ($referrerPartnerId) {
                $referrerName = $referrerInfo['name'] ?? $referrerPartner->name ?? '추천인';
                $referralMessage = " ('{$referrerName}' 파트너의 추천)";

                // 추천 관련 세션 데이터 정리
                Session::forget(['referrer_partner_id', 'referrer_partner_code', 'referrer_info']);

                Log::info('StoreController: Referral session data cleared', [
                    'application_id' => $application->id,
                    'referrer_name' => $referrerName
                ]);
            }

            DB::commit();

            // AJAX 요청 확인
            if ($request->expectsJson() || $request->ajax()) {
                if ($application->application_status === 'draft') {
                    \Log::info('StoreController: JSON response (draft)');
                    return response()->json([
                        'success' => true,
                        'message' => '신청서가 임시저장되었습니다. 언제든지 이어서 작성하실 수 있습니다.' . $referralMessage,
                        'application_id' => $application->id,
                        'status' => 'draft',
                        'redirect_url' => route('home.partner.regist.index')
                    ]);
                } else {
                    \Log::info('StoreController: JSON response (submitted)', [
                        'application_id' => $application->id
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => '파트너 신청서가 성공적으로 제출되었습니다' . $referralMessage . '! 검토 후 연락드리겠습니다.',
                        'application_id' => $application->id,
                        'status' => 'submitted',
                        'redirect_url' => route('home.partner.regist.status')
                    ]);
                }
            }

            // 일반 HTTP 요청
            if ($application->application_status === 'draft') {
                \Log::info('StoreController: Redirecting to index (draft)');
                return redirect()->route('home.partner.regist.index')
                    ->with('success', '신청서가 임시저장되었습니다. 언제든지 이어서 작성하실 수 있습니다.' . $referralMessage);
            } else {
                \Log::info('StoreController: Redirecting to status page', [
                    'application_id' => $application->id,
                    'status_route' => route('home.partner.regist.status')
                ]);
                return redirect()->route('home.partner.regist.status')
                    ->with('success', '파트너 신청서가 성공적으로 제출되었습니다' . $referralMessage . '! 검토 후 연락드리겠습니다.');
            }

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // 무결성 제약 조건 위반인 경우 특별 처리
            if (str_contains($e->getMessage(), 'unique_active_application_per_user') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')) {

                \Log::warning('StoreController: Unique constraint violation detected', [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'error_message' => $e->getMessage()
                ]);

                $message = '이미 처리 중인 신청서가 있습니다. 기존 신청서 상태를 확인해주세요.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'error' => 'DUPLICATE_APPLICATION'
                    ], 422);
                }

                return redirect()->route('home.partner.regist.status')
                    ->with('error', $message);
            }

            // 다른 데이터베이스 오류
            \Log::error('StoreController: Database query exception', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);

            $message = '데이터베이스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => 'DATABASE_ERROR'
                ], 500);
            }

            return back()->withInput()->with('error', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('StoreController: Exception occurred', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            // AJAX 요청인 경우 JSON 오류 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '신청서 제출 중 오류가 발생했습니다. 다시 시도해주세요.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', '신청서 제출 중 오류가 발생했습니다. 다시 시도해주세요.');
        }
    }

    /**
     * 파일 업로드 처리 (UpdateController와 일치하는 구조)
     */
    private function handleFileUploads(Request $request, $userId)
    {
        $documents = [];
        $uploadPath = "partner-applications/{$userId}";

        // 이력서 업로드
        if ($request->hasFile('resume')) {
            $resumeFile = $request->file('resume');
            $resumePath = $resumeFile->store($uploadPath, 'public');

            $documents['resume'] = [
                'original_name' => $resumeFile->getClientOriginalName(),
                'stored_path' => $resumePath,
                'file_size' => $resumeFile->getSize(),
                'uploaded_at' => now()->toISOString()
            ];
        }

        // 포트폴리오 업로드
        if ($request->hasFile('portfolio')) {
            $portfolioFile = $request->file('portfolio');
            $portfolioPath = $portfolioFile->store($uploadPath, 'public');

            $documents['portfolio'] = [
                'original_name' => $portfolioFile->getClientOriginalName(),
                'stored_path' => $portfolioPath,
                'file_size' => $portfolioFile->getSize(),
                'uploaded_at' => now()->toISOString()
            ];
        }

        // 기타 서류 업로드
        if ($request->hasFile('other_documents')) {
            $otherFiles = $request->file('other_documents');
            $documents['other'] = [];

            foreach ($otherFiles as $index => $file) {
                $filePath = $file->store($uploadPath, 'public');

                $documents['other'][] = [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString()
                ];
            }
        }

        return $documents;
    }

    /**
     * 기술 수준 파싱 (UpdateController와 동일)
     */
    private function parseSkillLevels($validatedData)
    {
        $skillLevels = [];
        $skills = $validatedData['skills'] ?? [];
        $levels = $validatedData['skill_levels'] ?? [];

        foreach ($skills as $index => $skill) {
            $skill = trim($skill ?? '');
            if (!empty($skill)) {
                $level = trim($levels[$index] ?? '');
                $skillLevels[$skill] = !empty($level) ? $level : '기초';
            }
        }

        return $skillLevels;
    }

    /**
     * 평일 일정 파싱 (UpdateController와 동일)
     */
    private function parseWeekdaySchedule($validatedData)
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $schedule = [];

        foreach ($weekdays as $day) {
            $schedule[$day] = [
                'available' => $validatedData[$day . '_available'] ?? false,
                'start' => $validatedData[$day . '_start'] ?? '09:00',
                'end' => $validatedData[$day . '_end'] ?? '18:00'
            ];
        }

        return $schedule;
    }

    /**
     * 유효성 검사 규칙 (UpdateController와 일치)
     */
    private function getValidationRules()
    {
        return [
            // 개인정보
            'name' => 'required|string|max:100',
            'phone' => 'required|string|regex:/^010-\d{4}-\d{4}$/',
            'country' => 'required|string|in:KR,US,JP,CN,CA,AU,GB,DE,FR,SG,OTHER',
            'address' => 'required|string|max:255',

            // 경력정보
            'total_years' => 'nullable|integer|min:0|max:50',
            'career_summary' => 'nullable|string|max:1000',
            'portfolio_url' => 'nullable|url|max:255',
            'bio' => 'nullable|string|max:500',

            // 기술정보
            'skills' => 'sometimes|array',
            'skills.*' => 'sometimes|nullable|string|max:100',
            'skill_levels' => 'sometimes|array',
            'skill_levels.*' => 'sometimes|nullable|string|in:기초,중급,고급,전문가',
            'certifications' => 'sometimes|array',
            'certifications.*' => 'sometimes|nullable|string|max:100',
            'languages' => 'sometimes|array',
            'languages.*' => 'sometimes|nullable|string|max:100',

            // 근무조건
            'expected_hourly_rate' => 'nullable|numeric|min:10000|max:100000',
            'preferred_regions' => 'nullable|array',
            'max_distance_km' => 'nullable|integer|min:1|max:200',

            // 근무 가능 시간
            'monday_available' => 'nullable|boolean',
            'tuesday_available' => 'nullable|boolean',
            'wednesday_available' => 'nullable|boolean',
            'thursday_available' => 'nullable|boolean',
            'friday_available' => 'nullable|boolean',
            'saturday_available' => 'nullable|boolean',
            'sunday_available' => 'nullable|boolean',
            'holiday_work' => 'nullable|boolean',

            // 파일
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'portfolio' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:5120',

            // 추천 파트너 정보
            'referral_code' => 'nullable|string|max:50',
            'referral_source' => 'required|string|in:self_application,direct,online_link,offline_meeting,social_media,event,advertisement,word_of_mouth,other',
            'referrer_name' => 'nullable|string|max:100',
            'referrer_contact' => 'nullable|string|max:20',
            'referrer_relationship' => 'nullable|string|max:50',
            'meeting_date' => 'nullable|date|before_or_equal:today',
            'meeting_location' => 'nullable|string|max:200',
            'introduction_method' => 'nullable|string|max:100',
            'motivation' => 'nullable|string|max:500'
        ];
    }

    /**
     * 유효성 검사 메시지 (UpdateController와 일치)
     */
    private function getValidationMessages()
    {
        return [
            'name.required' => '이름을 입력해주세요.',
            'phone.required' => '휴대폰 번호를 입력해주세요.',
            'phone.regex' => '휴대폰 번호 형식이 올바르지 않습니다. (010-0000-0000)',
            'country.required' => '국가를 선택해주세요.',
            'country.in' => '유효한 국가를 선택해주세요.',
            'address.required' => '주소를 입력해주세요.',
            'education_level.required' => '학력을 선택해주세요.',
            'emergency_contact_name.required' => '비상연락처 이름을 입력해주세요.',
            'emergency_contact_phone.required' => '비상연락처 전화번호를 입력해주세요.',
            'emergency_contact_phone.regex' => '비상연락처 전화번호 형식이 올바르지 않습니다.',
            'emergency_contact_relationship.required' => '비상연락처 관계를 입력해주세요.',
            'total_years.required' => '총 경력년수를 입력해주세요.',
            'career_summary.required' => '경력 요약을 입력해주세요.',
            'bio.required' => '자기소개를 입력해주세요.',
            'expected_hourly_rate.required' => '희망 시급을 입력해주세요.',
            'expected_hourly_rate.min' => '최소 시급은 10,000원입니다.',
            'expected_hourly_rate.max' => '최대 시급은 100,000원입니다.',
            'max_distance_km.required' => '최대 이동거리를 입력해주세요.',
            'resume.required' => '이력서를 업로드해주세요.',
            'resume.mimes' => '이력서는 PDF, DOC, DOCX 파일만 업로드 가능합니다.',
            'resume.max' => '이력서 파일 크기는 5MB를 초과할 수 없습니다.',
            'portfolio.mimes' => '포트폴리오는 PDF, DOC, DOCX, ZIP 파일만 업로드 가능합니다.',
            'portfolio.max' => '포트폴리오 파일 크기는 10MB를 초과할 수 없습니다.',

            // 추천 파트너 정보 관련 메시지
            'referral_source.required' => '추천 경로를 선택해주세요.',
            'referral_source.in' => '올바른 추천 경로를 선택해주세요.',
            'referral_code.max' => '추천 코드는 50자를 초과할 수 없습니다.',
            'referrer_name.max' => '추천인 이름은 100자를 초과할 수 없습니다.',
            'referrer_contact.max' => '추천인 연락처는 20자를 초과할 수 없습니다.',
            'referrer_relationship.max' => '추천인과의 관계는 50자를 초과할 수 없습니다.',
            'meeting_date.date' => '올바른 날짜를 입력해주세요.',
            'meeting_date.before_or_equal' => '만남/추천일은 오늘 이전이어야 합니다.',
            'meeting_location.max' => '만남 장소는 200자를 초과할 수 없습니다.',
            'introduction_method.max' => '소개 방법은 100자를 초과할 수 없습니다.',
            'motivation.max' => '지원 동기는 500자를 초과할 수 없습니다.'
        ];
    }

}
