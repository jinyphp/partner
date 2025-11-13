<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;

/**
 * 파트너 신청서 관리 컨트롤러
 *
 * 관리자가 파트너 신청서를 직접 등록하고 관리할 수 있는 기능 제공
 */
class PartnerApplicationController extends Controller
{
    /**
     * 신청서 목록 조회
     */
    public function index(Request $request)
    {
        $query = PartnerApplication::with(['user', 'approver', 'rejector']);

        // 상태 필터링
        $selectedStatus = $request->get('status', '');
        if ($request->filled('status')) {
            $query->where('application_status', $request->status);
        }

        // 검색 기능
        $searchValue = $request->get('search', '');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereJsonContains('personal_info->name', $search)
                  ->orWhereJsonContains('personal_info->email', $search)
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('admin_notes', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        // 통계 데이터
        $stats = [
            'total' => PartnerApplication::count(),
            'pending' => PartnerApplication::whereIn('application_status', ['submitted', 'reviewing'])->count(),
            'approved' => PartnerApplication::where('application_status', 'approved')->count(),
            'rejected' => PartnerApplication::where('application_status', 'rejected')->count(),
        ];

        return view('jiny-partner::admin.partner-applications.index', [
            'title' => '파트너 신청서 관리',
            'routePrefix' => 'partner.applications',
            'items' => $items,
            'stats' => $stats,
            'statusOptions' => $this->getStatusOptions(),
            'searchValue' => $searchValue,
            'selectedStatus' => $selectedStatus
        ]);
    }

    /**
     * 신청서 등록 폼 표시
     */
    public function create()
    {
        $partnerTiers = PartnerTier::where('is_active', true)->orderBy('priority_level')->get();

        return view('jiny-partner::admin.partner-applications.create', [
            'title' => '파트너 신청서 등록',
            'routePrefix' => 'partner.applications',
            'partnerTiers' => $partnerTiers,
            'statusOptions' => $this->getStatusOptions()
        ]);
    }

    /**
     * 신청서 등록 처리
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'user_uuid' => 'nullable|string|max:100',
                'shard_number' => 'nullable|integer',
                'application_status' => 'required|in:submitted,reviewing,interview,approved,rejected',

                // 개인 정보
                'personal_info.name' => 'required|string|max:100',
                'personal_info.email' => 'required|email|max:150',
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
                'user_uuid' => $validatedData['user_uuid'],
                'shard_number' => $validatedData['shard_number'],
                'application_status' => $validatedData['application_status'],
                'personal_info' => $validatedData['personal_info'] ?? [],
                'experience_info' => $validatedData['experience_info'] ?? [],
                'skills_info' => $validatedData['skills_info'] ?? [],
                'documents' => $documents,
                'expected_hourly_rate' => $validatedData['expected_hourly_rate'],
                'preferred_work_areas' => $validatedData['preferred_work_areas'] ?? [],
                'availability_schedule' => $validatedData['availability_schedule'] ?? [],
                'motivation' => $validatedData['motivation'],
                'goals' => $validatedData['goals'],
                'admin_notes' => $validatedData['admin_notes'],
                'referral_source' => $validatedData['referral_source'],
                'referral_code' => $validatedData['referral_code'],
                'referrer_name' => $validatedData['referrer_name'],
                'referrer_contact' => $validatedData['referrer_contact'],
                'referrer_relationship' => $validatedData['referrer_relationship'],
                'meeting_date' => $validatedData['meeting_date'],
                'meeting_location' => $validatedData['meeting_location'],
                'introduction_method' => $validatedData['introduction_method'],
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
                'admin_user' => auth()->id(),
                'status' => $application->application_status
            ]);

            return redirect()
                ->route('admin.partner.applications.show', $application->id)
                ->with('success', '파트너 신청서가 성공적으로 등록되었습니다.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner application creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', '신청서 등록 중 오류가 발생했습니다: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * 신청서 상세보기
     */
    public function show($id)
    {
        $application = PartnerApplication::with(['user', 'approver', 'rejector'])->findOrFail($id);

        // 완성도 점수 계산
        $completenessScore = $this->calculateCompletenessScore($application);

        return view('jiny-partner::admin.partner-applications.show', [
            'title' => '파트너 신청서 상세보기',
            'routePrefix' => 'partner.applications',
            'item' => $application,
            'completenessScore' => $completenessScore
        ]);
    }

    /**
     * 신청서 수정 폼
     */
    public function edit($id)
    {
        $application = PartnerApplication::with(['user', 'referrerPartner'])->findOrFail($id);
        $partnerTiers = PartnerTier::where('is_active', true)->orderBy('priority_level')->get();

        return view('jiny-partner::admin.partner-applications.edit', [
            'title' => '파트너 신청서 수정',
            'routePrefix' => 'partner.applications',
            'item' => $application,
            'partnerTiers' => $partnerTiers,
            'statusOptions' => $this->getStatusOptions()
        ]);
    }

    /**
     * 신청서 수정 처리
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $application = PartnerApplication::findOrFail($id);

            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'user_uuid' => 'nullable|string|max:100',
                'shard_number' => 'nullable|integer',
                'application_status' => 'required|in:submitted,reviewing,interview,approved,rejected',

                // 기타 필드들 (create와 동일한 validation 규칙)
                'personal_info.name' => 'required|string|max:100',
                'personal_info.email' => 'required|email|max:150',
                // ... 나머지 필드들

                'admin_notes' => 'nullable|string|max:2000'
            ]);

            $application->update($validatedData);

            DB::commit();

            Log::info('Partner application updated by admin', [
                'application_id' => $application->id,
                'admin_user' => auth()->id()
            ]);

            return redirect()
                ->route('admin.partner.applications.show', $application->id)
                ->with('success', '파트너 신청서가 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner application update failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', '신청서 수정 중 오류가 발생했습니다.')
                ->withInput();
        }
    }

    /**
     * 신청서 삭제
     */
    public function destroy($id)
    {
        try {
            $application = PartnerApplication::findOrFail($id);

            // 업로드된 파일들 삭제
            if ($application->documents) {
                foreach ($application->documents as $document) {
                    if (isset($document['path'])) {
                        Storage::disk('public')->delete($document['path']);
                    }
                }
            }

            $application->delete();

            Log::info('Partner application deleted by admin', [
                'application_id' => $id,
                'admin_user' => auth()->id()
            ]);

            return redirect()
                ->route('admin.partner.applications.index')
                ->with('success', '파트너 신청서가 삭제되었습니다.');

        } catch (\Exception $e) {
            Log::error('Partner application deletion failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', '신청서 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 상태 옵션 반환
     */
    private function getStatusOptions()
    {
        return [
            'submitted' => '제출됨',
            'reviewing' => '검토중',
            'interview' => '면접예정',
            'approved' => '승인됨',
            'rejected' => '거부됨'
        ];
    }

    /**
     * 신청서 완성도 점수 계산
     */
    private function calculateCompletenessScore($application)
    {
        $score = 0;
        $maxScore = 100;

        // 기본 정보 (30점)
        if (!empty($application->personal_info['name'])) $score += 5;
        if (!empty($application->personal_info['email'])) $score += 5;
        if (!empty($application->personal_info['phone'])) $score += 5;
        if (!empty($application->personal_info['address'])) $score += 5;
        if (!empty($application->personal_info['birth_date'])) $score += 5;
        if (!empty($application->personal_info['emergency_contact'])) $score += 2.5;
        if (!empty($application->personal_info['emergency_phone'])) $score += 2.5;

        // 경력 정보 (25점)
        if (!empty($application->experience_info['total_years'])) $score += 10;
        if (!empty($application->experience_info['achievements'])) $score += 5;
        if (!empty($application->experience_info['education'])) $score += 5;
        if (!empty($application->experience_info['previous_companies'])) $score += 5;

        // 기술 정보 (20점)
        if (!empty($application->skills_info['primary_skills'])) $score += 8;
        if (!empty($application->skills_info['secondary_skills'])) $score += 4;
        if (!empty($application->skills_info['programming_languages'])) $score += 8;

        // 추가 정보 (15점)
        if (!empty($application->expected_hourly_rate)) $score += 5;
        if (!empty($application->motivation)) $score += 5;
        if (!empty($application->goals)) $score += 5;

        // 선호 근무 조건 (10점)
        if (!empty($application->preferred_work_areas)) $score += 5;
        if (!empty($application->availability_schedule)) $score += 5;

        return min(100, round($score));
    }
}