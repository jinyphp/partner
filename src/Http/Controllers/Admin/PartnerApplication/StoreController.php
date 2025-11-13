<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jiny\Partner\Models\PartnerApplication;

/**
 * 파트너 신청서 등록 처리 컨트롤러
 */
class StoreController extends Controller
{
    /**
     * 신청서 등록 처리
     */
    public function __invoke(Request $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'user_uuid' => 'nullable|string|max:100',
                'shard_number' => 'nullable|integer',
                'application_status' => 'required|in:submitted,reviewing,interview,approved,rejected',


                // 개인 정보
                'personal_info.name' => 'nullable|string|max:100',
                'personal_info.email' => 'nullable|email|max:150',
                'personal_info.phone' => 'nullable|string|max:20',
                'personal_info.address' => 'nullable|string|max:255',
                'personal_info.birth_date' => 'nullable|date',
                'personal_info.emergency_contact' => 'nullable|string|max:100',
                'personal_info.emergency_phone' => 'nullable|string|max:20',

                // 경력 정보
                'experience_info.total_years' => 'nullable|integer|min:0|max:50',
                'experience_info.previous_companies' => 'nullable|array',
                'experience_info.key_projects' => 'nullable|array',
                'experience_info.achievements' => 'nullable|string|max:2000',
                'experience_info.education' => 'nullable|string|max:500',
                'experience_info.certifications' => 'nullable|array',

                // 기술 정보
                'skills_info.primary_skills' => 'nullable|array',
                'skills_info.secondary_skills' => 'nullable|array',
                'skills_info.tools_experience' => 'nullable|array',
                'skills_info.industry_experience' => 'nullable|array',
                'skills_info.programming_languages' => 'nullable|array',

                // 추가 정보
                'expected_hourly_rate' => 'nullable|numeric|min:0|max:1000000',
                'preferred_work_areas' => 'nullable|array',
                'availability_schedule' => 'nullable|array',
                'motivation' => 'nullable|string|max:2000',
                'goals' => 'nullable|string|max:2000',
                'admin_notes' => 'nullable|string|max:2000',

                // 추천자 정보
                'referral_source' => 'nullable|string|max:100',
                'referral_code' => 'nullable|string|max:50',
                'referrer_name' => 'nullable|string|max:100',
                'referrer_contact' => 'nullable|string|max:100',
                'referrer_relationship' => 'nullable|string|max:100',
                'meeting_date' => 'nullable|date',
                'meeting_location' => 'nullable|string|max:255',
                'introduction_method' => 'nullable|string|max:255',

                // 파일 업로드
                'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120' // 5MB
            ]);

            // personal_info 유효성 확인 (name과 email은 필수)
            if (empty($validatedData['personal_info']['name']) || empty($validatedData['personal_info']['email'])) {
                throw new \Exception('이름과 이메일은 필수 입력 항목입니다.');
            }

            // 파일 업로드 처리
            $documents = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('partner-applications/temp', 'public');
                    $documents[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()->toISOString()
                    ];
                }
            }

            // 신청서 생성
            $application = PartnerApplication::create([
                'user_id' => $validatedData['user_id'],
                'user_uuid' => $validatedData['user_uuid'] ?? null,
                'shard_number' => $validatedData['shard_number'] ?? null,
                'application_status' => $validatedData['application_status'],
                'personal_info' => $validatedData['personal_info'] ?? [],
                'experience_info' => $validatedData['experience_info'] ?? [],
                'skills_info' => $validatedData['skills_info'] ?? [],
                'documents' => $documents,
                'expected_hourly_rate' => $validatedData['expected_hourly_rate'] ?? null,
                'preferred_work_areas' => $validatedData['preferred_work_areas'] ?? [],
                'availability_schedule' => $validatedData['availability_schedule'] ?? [],
                'motivation' => $validatedData['motivation'] ?? null,
                'goals' => $validatedData['goals'] ?? null,
                'admin_notes' => $validatedData['admin_notes'] ?? null,
                'referral_source' => $validatedData['referral_source'] ?? null,
                'referral_code' => $validatedData['referral_code'] ?? null,
                'referrer_name' => $validatedData['referrer_name'] ?? null,
                'referrer_contact' => $validatedData['referrer_contact'] ?? null,
                'referrer_relationship' => $validatedData['referrer_relationship'] ?? null,
                'meeting_date' => $validatedData['meeting_date'] ?? null,
                'meeting_location' => $validatedData['meeting_location'] ?? null,
                'introduction_method' => $validatedData['introduction_method'] ?? null,
                'submitted_at' => now()
            ]);

            // 파일 경로 업데이트 (신청서 ID를 포함한 경로로 이동)
            if (!empty($documents)) {
                $finalDocuments = [];
                foreach ($documents as $document) {
                    $oldPath = $document['path'];
                    $fileName = basename($oldPath);
                    $newPath = "partner-applications/{$application->id}/{$fileName}";

                    Storage::disk('public')->move($oldPath, $newPath);

                    $document['path'] = $newPath;
                    $finalDocuments[] = $document;
                }

                $application->update(['documents' => $finalDocuments]);
            }

            DB::commit();

            Log::info('Partner application created by admin', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'user_email' => $validatedData['personal_info']['email'],
                'user_name' => $validatedData['personal_info']['name'],
                'admin_user' => auth()->id(),
                'status' => $application->application_status
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '파트너 신청서가 성공적으로 등록되었습니다.',
                    'application_id' => $application->id,
                    'redirect' => route('admin.partner.applications.index')
                ]);
            }

            return redirect()
                ->route('admin.partner.applications.show', $application->id)
                ->with('success', '파트너 신청서가 성공적으로 등록되었습니다.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 정보를 확인해주세요.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner application creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_user' => auth()->id(),
                'request_data' => $request->except(['documents'])
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '신청서 등록 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', '신청서 등록 중 오류가 발생했습니다: ' . $e->getMessage())
                ->withInput();
        }
    }
}