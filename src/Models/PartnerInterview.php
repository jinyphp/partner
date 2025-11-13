<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PartnerInterview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_interviews';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'shard_number',
        'user_table',
        'email',
        'name',
        'application_id',
        'referrer_partner_id',
        'referrer_code',
        'referrer_name',
        'interview_status',
        'interview_type',
        'interview_round',
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_minutes',
        'interviewer_id',
        'interviewer_name',
        'meeting_location',
        'meeting_url',
        'meeting_password',
        'preparation_notes',
        'technical_score',
        'communication_score',
        'experience_score',
        'attitude_score',
        'overall_score',
        'interview_result',
        'interview_feedback',
        'strengths',
        'weaknesses',
        'recommendations',
        'interviewer_notes',
        'candidate_notes',
        'interview_logs',
        'next_interview_date',
        'next_steps',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_interview_date' => 'datetime',
        'technical_score' => 'decimal:2',
        'communication_score' => 'decimal:2',
        'experience_score' => 'decimal:2',
        'attitude_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'interview_feedback' => 'array',
        'interview_logs' => 'array'
    ];

    /**
     * 지원자 사용자
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 파트너 신청서
     */
    public function application()
    {
        return $this->belongsTo(PartnerApplication::class, 'application_id');
    }

    /**
     * 추천 파트너
     */
    public function referrerPartner()
    {
        return $this->belongsTo(PartnerUser::class, 'referrer_partner_id');
    }

    /**
     * 면접관
     */
    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    /**
     * 생성자
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 수정자
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 예정된 면접만 조회
     */
    public function scopeScheduled($query)
    {
        return $query->where('interview_status', 'scheduled');
    }

    /**
     * 완료된 면접만 조회
     */
    public function scopeCompleted($query)
    {
        return $query->where('interview_status', 'completed');
    }

    /**
     * 진행 중인 면접만 조회
     */
    public function scopeInProgress($query)
    {
        return $query->where('interview_status', 'in_progress');
    }

    /**
     * 취소된 면접만 조회
     */
    public function scopeCancelled($query)
    {
        return $query->where('interview_status', 'cancelled');
    }

    /**
     * 통과한 면접만 조회
     */
    public function scopePassed($query)
    {
        return $query->where('interview_result', 'pass');
    }

    /**
     * 불합격한 면접만 조회
     */
    public function scopeFailed($query)
    {
        return $query->where('interview_result', 'fail');
    }

    /**
     * 특정 면접관의 면접만 조회
     */
    public function scopeByInterviewer($query, $interviewerId)
    {
        return $query->where('interviewer_id', $interviewerId);
    }

    /**
     * 오늘 예정된 면접 조회
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    /**
     * 이번 주 예정된 면접 조회
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * 면접 시작
     */
    public function startInterview($interviewerId = null)
    {
        $this->update([
            'interview_status' => 'in_progress',
            'started_at' => now(),
            'interviewer_id' => $interviewerId ?? $this->interviewer_id
        ]);

        $this->addLog('면접 시작', '면접이 시작되었습니다.');
    }

    /**
     * 면접 완료
     */
    public function completeInterview($scores = [], $result = null, $feedback = [])
    {
        $updateData = [
            'interview_status' => 'completed',
            'completed_at' => now(),
            'duration_minutes' => $this->started_at ?
                now()->diffInMinutes($this->started_at) : null
        ];

        // 점수 업데이트
        if (!empty($scores)) {
            $updateData = array_merge($updateData, $scores);

            // 종합 점수 계산
            $scoreFields = ['technical_score', 'communication_score', 'experience_score', 'attitude_score'];
            $validScores = [];

            foreach ($scoreFields as $field) {
                if (isset($scores[$field]) && $scores[$field] !== null) {
                    $validScores[] = $scores[$field];
                }
            }

            if (!empty($validScores)) {
                $updateData['overall_score'] = round(array_sum($validScores) / count($validScores), 2);
            }
        }

        // 결과 업데이트
        if ($result) {
            $updateData['interview_result'] = $result;
        }

        // 피드백 업데이트
        if (!empty($feedback)) {
            $updateData['interview_feedback'] = $feedback;
        }

        $this->update($updateData);

        $this->addLog('면접 완료', '면접이 완료되었습니다.');
    }

    /**
     * 면접 취소
     */
    public function cancelInterview($reason = null)
    {
        $this->update([
            'interview_status' => 'cancelled'
        ]);

        $this->addLog('면접 취소', $reason ?? '면접이 취소되었습니다.');
    }

    /**
     * 면접 일정 변경
     */
    public function rescheduleInterview($newDateTime, $reason = null)
    {
        $oldDateTime = $this->scheduled_at;

        $this->update([
            'interview_status' => 'rescheduled',
            'scheduled_at' => $newDateTime
        ]);

        $message = "면접 일정이 변경되었습니다. {$oldDateTime} → {$newDateTime}";
        if ($reason) {
            $message .= " (사유: {$reason})";
        }

        $this->addLog('일정 변경', $message);
    }

    /**
     * 면접 로그 추가
     */
    public function addLog($action, $message = null, $data = null)
    {
        $logs = $this->interview_logs ?? [];

        $logs[] = [
            'action' => $action,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name
        ];

        $this->update(['interview_logs' => $logs]);
    }

    /**
     * 평균 점수 계산
     */
    public function getAverageScoreAttribute()
    {
        $scores = [
            $this->technical_score,
            $this->communication_score,
            $this->experience_score,
            $this->attitude_score
        ];

        $validScores = array_filter($scores, fn($score) => $score !== null);

        if (empty($validScores)) {
            return null;
        }

        return round(array_sum($validScores) / count($validScores), 2);
    }

    /**
     * 면접 상태 한글 표시
     */
    public function getStatusLabelAttribute()
    {
        return match($this->interview_status) {
            'scheduled' => '예정',
            'in_progress' => '진행중',
            'completed' => '완료',
            'cancelled' => '취소',
            'rescheduled' => '재일정',
            'no_show' => '불참',
            default => $this->interview_status
        };
    }

    /**
     * 면접 결과 한글 표시
     */
    public function getResultLabelAttribute()
    {
        return match($this->interview_result) {
            'pass' => '통과',
            'fail' => '불합격',
            'pending' => '검토중',
            'hold' => '보류',
            'next_round' => '다음단계',
            default => $this->interview_result
        };
    }

    /**
     * 면접 타입 한글 표시
     */
    public function getTypeLabelAttribute()
    {
        return match($this->interview_type) {
            'phone' => '전화면접',
            'video' => '화상면접',
            'in_person' => '대면면접',
            'written' => '서면면접',
            default => $this->interview_type
        };
    }

    /**
     * 면접 라운드 한글 표시
     */
    public function getRoundLabelAttribute()
    {
        return match($this->interview_round) {
            'first' => '1차면접',
            'second' => '2차면접',
            'final' => '최종면접',
            default => $this->interview_round
        };
    }
}