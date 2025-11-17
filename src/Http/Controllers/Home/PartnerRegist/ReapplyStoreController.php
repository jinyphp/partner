<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class ReapplyStoreController extends PartnerController
{

    /**
     * 파트너 재신청 처리
     */
    public function __invoke(Request $request, $id)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // Step2. 기존 반려된 신청서 조회 (본인의 신청서만, UUID 기반)
        $rejectedApplication = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->where('application_status', 'rejected')
            ->firstOrFail();

        // Step3. 이미 재신청을 했는지 확인 (UUID 기반)
        $existingReapplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->where('previous_application_id', $rejectedApplication->id)
            ->first();

        if ($existingReapplication) {
            return redirect()->route('home.partner.regist.status')
                ->with('info', '이미 재신청을 진행하셨습니다.');
        }

        // Step4. 진행 중인 다른 신청서가 있는지 확인 (UUID 기반)
        $activeApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->exists();

        if ($activeApplication) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '현재 진행 중인 신청이 있어 재신청할 수 없습니다.');
        }

        // Step5. 유효성 검사
        try {
            $validatedData = $request->validate($this->getValidationRules(), $this->getValidationMessages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('ReapplyStoreController: Validation failed', [
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

            // Step6. 새로운 재신청 데이터 생성
            $submitType = $request->input('submit_type');
            $isDraft = ($submitType === 'draft');

            // 파일 업로드 처리
            $documents = $this->handleFileUploads($request, $user);

            // 기존 문서 정보 복사 (새 파일이 업로드되지 않은 경우)
            if (empty($documents['portfolio_url']) && isset($rejectedApplication->documents['portfolio_url'])) {
                $documents['portfolio_url'] = $rejectedApplication->documents['portfolio_url'];
            }

            // 새 재신청 데이터 구성
            $applicationData = [
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'application_status' => $isDraft ? 'draft' : 'submitted',
                'previous_application_id' => $rejectedApplication->id,
                'personal_info' => [
                    'name' => $validatedData['name'],
                    'phone' => $validatedData['phone'],
                    'birth_year' => $validatedData['birth_year'],
                    'region' => $validatedData['region'],
                    'district' => $validatedData['district'] ?? null,
                    'address' => $validatedData['address'],
                    'preferred_tier_id' => $validatedData['preferred_tier_id'] ?? null,
                ],
                'experience_info' => [
                    'years' => $validatedData['experience_years'],
                ],
                'skills_info' => [
                    'languages' => $validatedData['languages'] ?? [],
                    'frameworks' => $validatedData['frameworks'] ?? [],
                    'skills' => $validatedData['skills'] ?? [],
                ],
                'documents' => $documents,
                'motivation' => $validatedData['motivation'],
                'improvement_plan' => $validatedData['improvement_plan'],
                'project_experience' => $validatedData['project_experience'] ?? null,
                'goals' => $validatedData['goals'] ?? null,

                // 추천자 정보 (StoreController와 동일한 필드명으로 저장)
                'referral_source' => $validatedData['referral_source'],
                'referral_code' => $validatedData['referral_code'] ?? null,
                'referrer_name' => $validatedData['referrer_name'] ?? null,
                'referrer_contact' => $validatedData['referrer_contact'] ?? null,
                'referrer_relationship' => $validatedData['referrer_relationship'] ?? null,
                'meeting_date' => $validatedData['meeting_date'] ?? null,
                'meeting_location' => $validatedData['meeting_location'] ?? null,
                'introduction_method' => $validatedData['introduction_method'] ?? null,

                'submitted_at' => $isDraft ? null : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Step7. 새 재신청서 생성
            $newApplication = PartnerApplication::create($applicationData);

            DB::commit();

            // AJAX 요청 확인 (StoreController와 동일한 처리)
            if ($request->expectsJson() || $request->ajax()) {
                if ($isDraft) {
                    return response()->json([
                        'success' => true,
                        'message' => '재신청서가 임시저장되었습니다. 언제든지 이어서 작성하실 수 있습니다.',
                        'application_id' => $newApplication->id,
                        'status' => 'draft',
                        'redirect_url' => route('home.partner.regist.status')
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => '재신청이 성공적으로 제출되었습니다! 재검토 후 연락드리겠습니다.',
                        'application_id' => $newApplication->id,
                        'status' => 'submitted',
                        'redirect_url' => route('home.partner.regist.status')
                    ]);
                }
            }

            // 일반 HTTP 요청 (기존 처리 방식)
            if ($isDraft) {
                return redirect()->route('home.partner.regist.status')
                    ->with('success', '재신청서가 임시저장되었습니다.');
            } else {
                return redirect()->route('home.partner.regist.status')
                    ->with('success', '재신청이 성공적으로 제출되었습니다! 재검토 후 연락드리겠습니다.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner reapplication failed: ' . $e->getMessage());

            // AJAX 요청인 경우 JSON 오류 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '재신청 처리 중 오류가 발생했습니다. 다시 시도해주세요.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', '재신청 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
        }
    }

    /**
     * 파일 업로드 처리
     */
    private function handleFileUploads(Request $request, $user)
    {
        $documents = [];
        $uploadPath = "partner-applications/{$user->uuid}";

        // 포트폴리오 URL
        if ($request->filled('portfolio_url')) {
            $documents['portfolio_url'] = $request->input('portfolio_url');
        }

        // 추가 첨부파일
        if ($request->hasFile('additional_attachments')) {
            $additionalFiles = $request->file('additional_attachments');
            $documents['additional'] = [];

            foreach ($additionalFiles as $index => $file) {
                $filePath = $file->store($uploadPath, 'public');

                $documents['additional'][] = [
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
     * 유효성 검사 규칙
     */
    private function getValidationRules()
    {
        return [
            // 개인정보
            'name' => 'required|string|max:100',
            'phone' => 'required|string|regex:/^010-\d{4}-\d{4}$/',
            'birth_year' => 'required|integer|min:1950|max:' . (date('Y') - 18),
            'region' => 'required|string|max:50',
            'district' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',

            // 추천자 정보
            'referral_source' => 'required|string|in:self_application,direct,online_link,offline_meeting,social_media,event,advertisement,word_of_mouth,other',
            'referral_code' => 'nullable|string|max:50',
            'referrer_name' => 'nullable|string|max:100',
            'referrer_contact' => 'nullable|string|max:20',
            'referrer_relationship' => 'nullable|string|max:50',
            'meeting_date' => 'nullable|date|before_or_equal:today',
            'meeting_location' => 'nullable|string|max:200',
            'introduction_method' => 'nullable|string|max:100',

            // 경력정보
            'experience_years' => 'required|integer|min:0|max:50',
            'preferred_tier_id' => 'nullable|integer',

            // 기술정보
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'frameworks' => 'nullable|array',
            'frameworks.*' => 'string|max:50',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',

            // 포트폴리오
            'portfolio_url' => 'nullable|url|max:255',

            // 재신청 내용
            'motivation' => 'required|string|max:2000',
            'improvement_plan' => 'required|string|max:2000',
            'project_experience' => 'nullable|string|max:2000',
            'goals' => 'nullable|string|max:1000',

            // 추가 파일
            'additional_attachments' => 'nullable|array|max:5',
            'additional_attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',

            // 동의사항
            'improvement_confirmed' => 'required|accepted',
            'terms_agreed' => 'required|accepted',

            // 액션
            'submit_type' => 'required|in:draft,submit'
        ];
    }

    /**
     * 유효성 검사 메시지
     */
    private function getValidationMessages()
    {
        return [
            // 개인정보
            'name.required' => '이름을 입력해주세요.',
            'phone.required' => '전화번호를 입력해주세요.',
            'phone.regex' => '전화번호 형식이 올바르지 않습니다. (010-0000-0000)',
            'birth_year.required' => '출생연도를 선택해주세요.',
            'birth_year.min' => '출생연도를 올바르게 선택해주세요.',
            'birth_year.max' => '만 18세 이상만 신청 가능합니다.',
            'region.required' => '지역을 선택해주세요.',
            'address.required' => '상세 주소를 입력해주세요.',

            // 추천자 정보
            'referral_source.required' => '신청 경로를 선택해주세요.',
            'referral_source.in' => '올바른 신청 경로를 선택해주세요.',
            'referral_code.max' => '추천 코드는 50자를 초과할 수 없습니다.',
            'referrer_name.max' => '추천인 이름은 100자를 초과할 수 없습니다.',
            'referrer_contact.max' => '추천인 연락처는 20자를 초과할 수 없습니다.',
            'referrer_relationship.max' => '추천인과의 관계는 50자를 초과할 수 없습니다.',
            'meeting_date.date' => '올바른 날짜 형식을 입력해주세요.',
            'meeting_date.before_or_equal' => '만남 일자는 오늘 이전이어야 합니다.',
            'meeting_location.max' => '만남 장소는 200자를 초과할 수 없습니다.',
            'introduction_method.max' => '소개 방법은 100자를 초과할 수 없습니다.',

            // 경력정보
            'experience_years.required' => '경력년수를 선택해주세요.',
            'experience_years.min' => '경력년수는 0년 이상이어야 합니다.',
            'experience_years.max' => '경력년수는 50년을 초과할 수 없습니다.',

            // 재신청 내용
            'motivation.required' => '신청 동기 및 개선사항을 입력해주세요.',
            'motivation.max' => '신청 동기 및 개선사항은 2000자를 초과할 수 없습니다.',
            'improvement_plan.required' => '개선 계획을 입력해주세요.',
            'improvement_plan.max' => '개선 계획은 2000자를 초과할 수 없습니다.',
            'project_experience.max' => '추가 프로젝트 경험은 2000자를 초과할 수 없습니다.',
            'goals.max' => '수정된 목표 및 계획은 1000자를 초과할 수 없습니다.',

            // 파일 업로드
            'additional_attachments.max' => '추가 파일은 최대 5개까지만 업로드 가능합니다.',
            'additional_attachments.*.mimes' => '허용되지 않는 파일 형식입니다. (PDF, DOC, DOCX, JPG, JPEG, PNG만 가능)',
            'additional_attachments.*.max' => '파일 크기는 10MB를 초과할 수 없습니다.',

            // 동의사항
            'improvement_confirmed.required' => '반려 사유를 확인하고 개선했음을 확인해주세요.',
            'improvement_confirmed.accepted' => '반려 사유를 확인하고 개선했음을 확인해주세요.',
            'terms_agreed.required' => '개인정보 수집 및 이용에 동의해주세요.',
            'terms_agreed.accepted' => '개인정보 수집 및 이용에 동의해주세요.',

            // 액션
            'submit_type.required' => '처리할 액션을 선택해주세요.',
            'submit_type.in' => '올바르지 않은 액션입니다.',
        ];
    }
}
