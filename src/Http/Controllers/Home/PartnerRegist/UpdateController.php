<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class UpdateController extends PartnerController
{

    /**
     * 파트너 신청서 수정 처리
     */
    public function __invoke(Request $request, $id)
    {
        \Log::info('UpdateController: Starting update process', [
            'application_id' => $id,
            'method' => $request->method(),
            'input_keys' => array_keys($request->all())
        ]);

        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        \Log::info('UpdateController: User authenticated', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid
        ]);

        // Step2. 신청서 조회 (본인의 신청서만, UUID 기반)
        $application = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->firstOrFail();

        \Log::info('UpdateController: Application found', [
            'application_id' => $application->id,
            'application_status' => $application->application_status,
            'user_uuid' => $application->user_uuid
        ]);

        // 수정 가능한 상태인지 확인
        if (!in_array($application->application_status, ['draft', 'submitted', 'rejected'])) {
            \Log::warning('UpdateController: Invalid status for editing', [
                'application_status' => $application->application_status,
                'allowed_statuses' => ['draft', 'submitted', 'rejected']
            ]);
            return redirect()->route('home.partner.regist.status')
                ->with('error', '현재 상태에서는 신청서를 수정할 수 없습니다.');
        }

        \Log::info('UpdateController: Starting validation', [
            'validation_rules_count' => count($this->getValidationRules($application))
        ]);

        // skills 배열에서 빈 값들 제거
        $skills = $request->input('skills', []);
        $filteredSkills = array_filter($skills, function($skill) {
            return !empty(trim($skill));
        });
        $request->merge(['skills' => array_values($filteredSkills)]);

        // certifications와 languages도 동일하게 처리
        $certifications = $request->input('certifications', []);
        $filteredCertifications = array_filter($certifications, function($cert) {
            return !empty(trim($cert));
        });
        $request->merge(['certifications' => array_values($filteredCertifications)]);

        $languages = $request->input('languages', []);
        $filteredLanguages = array_filter($languages, function($lang) {
            return !empty(trim($lang));
        });
        $request->merge(['languages' => array_values($filteredLanguages)]);

        \Log::info('UpdateController: Filtered input arrays', [
            'original_skills' => $skills,
            'filtered_skills' => $filteredSkills,
            'original_certifications' => $certifications,
            'filtered_certifications' => $filteredCertifications,
            'original_languages' => $languages,
            'filtered_languages' => $filteredLanguages
        ]);

        // 유효성 검사 (파일 업로드는 선택적으로 변경)
        $validatedData = $request->validate($this->getValidationRules($application), $this->getValidationMessages());

        \Log::info('UpdateController: Validation completed successfully', [
            'validated_data_keys' => array_keys($validatedData)
        ]);

        try {
            DB::beginTransaction();

            // 파일 업로드 처리 (기존 파일 유지하면서 새 파일 업로드)
            $documents = $this->handleFileUploads($request, $application);

            // 재신청인 경우 이전 신청서 ID 설정
            $previousApplicationId = null;
            if ($application->application_status === 'rejected') {
                $previousApplicationId = $application->id;
            }

            // 신청서 데이터 구성
            $newStatus = $application->application_status === 'rejected' ? 'reapplied' : 'submitted';

            $applicationData = [
                'application_status' => $newStatus,
                'personal_info' => [
                    'name' => $validatedData['name'],
                    'email' => $user->email,
                    'phone' => $validatedData['phone'],
                    'address' => $validatedData['address'],
                    'country' => $validatedData['country']
                ],
                'experience_info' => [
                    'total_years' => $validatedData['total_years'] ?? null,
                    'career_summary' => $validatedData['career_summary'] ?? null,
                    'previous_companies' => $this->parsePreviousCompanies($request),
                    'portfolio_url' => $validatedData['portfolio_url'] ?? null,
                    'bio' => $validatedData['bio'] ?? null
                ],
                'skills_info' => [
                    'skills' => $validatedData['skills'] ?? [],
                    'skill_levels' => $this->parseSkillLevels($request),
                    'certifications' => array_filter($validatedData['certifications'] ?? []),
                    'languages' => array_filter($validatedData['languages'] ?? [])
                ],
                'documents' => $documents,
                'preferred_work_areas' => [
                    'regions' => $validatedData['preferred_regions'] ?? [],
                    'districts' => $validatedData['preferred_districts'] ?? [],
                    'transport_preference' => $validatedData['transport_preference'] ?? []
                ],
                'availability_schedule' => [
                    'weekdays' => $this->parseWeekdaySchedule($request),
                    'weekend' => [
                        'saturday' => ['available' => $request->boolean('saturday_available')],
                        'sunday' => ['available' => $request->boolean('sunday_available')]
                    ],
                    'holiday_work' => $request->boolean('holiday_work'),
                    'overtime_available' => $request->boolean('overtime_available')
                ]
            ];

            // 재신청인 경우 추가 필드 설정
            if ($application->application_status === 'rejected') {
                $applicationData['previous_application_id'] = $previousApplicationId;
                $applicationData['reapplication_reason'] = $request->input('reapplication_reason');

                // 기존 거부 관련 필드 초기화
                $applicationData['rejection_date'] = null;
                $applicationData['rejection_reason'] = null;
                $applicationData['rejected_by'] = null;
            }

            // 제출 시간 갱신 (항상 제출이므로)
            $applicationData['submitted_at'] = now();

            \Log::info('UpdateController: About to update application', [
                'application_id' => $application->id,
                'application_data_keys' => array_keys($applicationData),
                'new_status' => $applicationData['application_status']
            ]);

            // 신청서 업데이트
            $updateResult = $application->update($applicationData);

            \Log::info('UpdateController: Update result', [
                'application_id' => $application->id,
                'update_result' => $updateResult,
                'current_status' => $application->fresh()->application_status
            ]);

            DB::commit();

            \Log::info('UpdateController: Application updated successfully', [
                'application_id' => $application->id,
                'new_status' => $application->application_status,
                'submitted_at' => $application->submitted_at
            ]);

            // AJAX 요청인지 확인
            if ($request->expectsJson() || $request->ajax()) {
                \Log::info('UpdateController: Returning JSON response for AJAX request');

                $message = $application->application_status === 'reapplied'
                    ? '재신청이 성공적으로 제출되었습니다! 재검토 후 연락드리겠습니다.'
                    : '신청서가 성공적으로 수정되었습니다!';

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('home.partner.regist.status'),
                    'application_status' => $application->application_status,
                    'application_id' => $application->id
                ]);
            }

            // 일반 폼 제출 응답 (기존 방식)
            if ($application->application_status === 'reapplied') {
                return redirect()->route('home.partner.regist.status')
                    ->with('success', '재신청이 성공적으로 제출되었습니다! 재검토 후 연락드리겠습니다.');
            } else {
                return redirect()->route('home.partner.regist.status')
                    ->with('success', '신청서가 성공적으로 수정되었습니다!');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Partner application validation failed', [
                'errors' => $e->errors(),
                'application_id' => $id
            ]);

            // AJAX 요청에 대한 유효성 검사 오류 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 데이터에 오류가 있습니다.',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner application update failed: ' . $e->getMessage(), [
                'application_id' => $id,
                'exception' => $e->getTraceAsString()
            ]);

            // AJAX 요청에 대한 에러 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '신청서 수정 중 오류가 발생했습니다. 다시 시도해주세요.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', '신청서 수정 중 오류가 발생했습니다. 다시 시도해주세요.');
        }
    }

    /**
     * 파일 업로드 처리 (기존 파일 유지하면서 새 파일 업로드)
     */
    private function handleFileUploads(Request $request, $application)
    {
        $documents = $application->documents ?? [];
        $uploadPath = "partner-applications/{$application->user_id}";

        // 이력서 업로드
        if ($request->hasFile('resume')) {
            // 기존 파일 삭제
            if (isset($documents['resume']['stored_path'])) {
                Storage::disk('public')->delete($documents['resume']['stored_path']);
            }

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
            // 기존 파일 삭제
            if (isset($documents['portfolio']['stored_path'])) {
                Storage::disk('public')->delete($documents['portfolio']['stored_path']);
            }

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
            // 기존 파일들 삭제
            if (isset($documents['other'])) {
                foreach ($documents['other'] as $file) {
                    Storage::disk('public')->delete($file['stored_path']);
                }
            }

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
     * 이전 직장 정보 파싱 (StoreController와 동일)
     */
    private function parsePreviousCompanies(Request $request)
    {
        $companies = [];
        $companyData = $request->input('companies', []);

        foreach ($companyData as $company) {
            if (!empty($company['company']) && !empty($company['position'])) {
                $companies[] = [
                    'company' => $company['company'],
                    'position' => $company['position'],
                    'period' => $company['period'] ?? '',
                    'description' => $company['description'] ?? ''
                ];
            }
        }

        return $companies;
    }

    /**
     * 기술 수준 파싱 (StoreController와 동일)
     */
    private function parseSkillLevels(Request $request)
    {
        $skillLevels = [];
        $skills = $request->input('skills', []);
        $levels = $request->input('skill_levels', []);

        foreach ($skills as $skill) {
            $skillLevels[$skill] = $levels[$skill] ?? '기초';
        }

        return $skillLevels;
    }

    /**
     * 평일 일정 파싱 (StoreController와 동일)
     */
    private function parseWeekdaySchedule(Request $request)
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $schedule = [];

        foreach ($weekdays as $day) {
            $schedule[$day] = [
                'available' => $request->boolean("{$day}_available"),
                'start' => $request->input("{$day}_start", '09:00'),
                'end' => $request->input("{$day}_end", '18:00')
            ];
        }

        return $schedule;
    }

    /**
     * 유효성 검사 규칙 (수정시에는 파일이 선택적)
     */
    private function getValidationRules($application)
    {
        $rules = [
            // 개인정보
            'name' => 'required|string|max:100',
            'phone' => 'required|string|regex:/^010-\d{4}-\d{4}$/',
            'address' => 'required|string|max:255',
            'country' => 'required|string|in:KR,US,JP,CN,CA,AU,GB,DE,FR,SG,OTHER',

            // 경력정보
            'total_years' => 'nullable|integer|min:0|max:50',
            'career_summary' => 'nullable|string|max:1000',
            'portfolio_url' => 'nullable|url|max:255',
            'bio' => 'nullable|string|max:500',

            // 기술정보
            'skills' => 'nullable|array',
            'skills.*' => 'nullable|string|max:100',
            'certifications' => 'nullable|array',
            'certifications.*' => 'nullable|string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'nullable|string|max:100',

            // 근무조건
            'preferred_regions' => 'nullable|array',
            'preferred_districts' => 'nullable|array',

            // 파일 (수정시에는 선택적)
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'portfolio' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:5120'
        ];


        // 재신청인 경우 재신청 사유 필수
        if ($application->application_status === 'rejected') {
            $rules['reapplication_reason'] = 'required|string|max:1000';
        }

        return $rules;
    }

    /**
     * 유효성 검사 메시지 (StoreController와 동일)
     */
    private function getValidationMessages()
    {
        return [
            'name.required' => '이름을 입력해주세요.',
            'phone.required' => '휴대폰 번호를 입력해주세요.',
            'phone.regex' => '휴대폰 번호 형식이 올바르지 않습니다. (010-0000-0000)',
            'address.required' => '주소를 입력해주세요.',
            'birth_year.required' => '출생년도를 입력해주세요.',
            'birth_year.max' => '만 18세 이상만 신청 가능합니다.',
            'education_level.required' => '학력을 선택해주세요.',
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
            'reapplication_reason.required' => '재신청 사유를 입력해주세요.',
            'reapplication_reason.max' => '재신청 사유는 1000자를 초과할 수 없습니다.'
        ];
    }
}
