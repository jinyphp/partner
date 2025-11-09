<?php

namespace Jiny\Partner\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTraining;
use Jiny\Partner\Models\PartnerTrainingEnrollment;
use Exception;

class PartnerTrainingService
{
    /**
     * Create a new training program
     */
    public function createTraining(array $trainingData): ?PartnerTraining
    {
        try {
            $training = PartnerTraining::create([
                'title' => $trainingData['title'],
                'description' => $trainingData['description'],
                'training_type' => $trainingData['training_type'] ?? 'online',
                'required_tier' => $trainingData['required_tier'] ?? 'Bronze',
                'max_participants' => $trainingData['max_participants'] ?? null,
                'duration_hours' => $trainingData['duration_hours'] ?? 1,
                'difficulty_level' => $trainingData['difficulty_level'] ?? 'beginner',
                'instructor_name' => $trainingData['instructor_name'] ?? null,
                'training_materials' => $trainingData['training_materials'] ?? null,
                'scheduled_start' => $trainingData['scheduled_start'] ?? null,
                'scheduled_end' => $trainingData['scheduled_end'] ?? null,
                'location' => $trainingData['location'] ?? null,
                'meeting_url' => $trainingData['meeting_url'] ?? null,
                'prerequisites' => $trainingData['prerequisites'] ?? null,
                'learning_objectives' => $trainingData['learning_objectives'] ?? null,
                'certification_offered' => $trainingData['certification_offered'] ?? false,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Training program created", [
                'training_id' => $training->id,
                'title' => $training->title,
                'type' => $training->training_type
            ]);

            return $training;

        } catch (Exception $e) {
            Log::error("Failed to create training program", [
                'error' => $e->getMessage(),
                'training_data' => $trainingData
            ]);
            return null;
        }
    }

    /**
     * Enroll partner in training
     */
    public function enrollPartner(int $trainingId, string $partnerUuid, bool $isRequired = false): bool
    {
        try {
            // Check if partner exists
            $partner = PartnerUser::where('user_uuid', $partnerUuid)->first();
            if (!$partner) {
                Log::warning("Partner not found for enrollment", ['partner_uuid' => $partnerUuid]);
                return false;
            }

            // Check if training exists
            $training = PartnerTraining::find($trainingId);
            if (!$training) {
                Log::warning("Training not found", ['training_id' => $trainingId]);
                return false;
            }

            // Check if already enrolled
            $existingEnrollment = PartnerTrainingEnrollment::where('training_id', $trainingId)
                ->where('partner_uuid', $partnerUuid)
                ->first();

            if ($existingEnrollment) {
                Log::info("Partner already enrolled in training", [
                    'training_id' => $trainingId,
                    'partner_uuid' => $partnerUuid
                ]);
                return true;
            }

            // Check capacity
            if ($training->max_participants) {
                $currentEnrollments = PartnerTrainingEnrollment::where('training_id', $trainingId)
                    ->count();
                if ($currentEnrollments >= $training->max_participants) {
                    Log::warning("Training at capacity", [
                        'training_id' => $trainingId,
                        'current_enrollments' => $currentEnrollments,
                        'max_participants' => $training->max_participants
                    ]);
                    return false;
                }
            }

            // Check tier requirements
            if ($training->required_tier && !$this->checkTierRequirement($partner->tier_name, $training->required_tier)) {
                Log::warning("Partner does not meet tier requirements", [
                    'partner_tier' => $partner->tier_name,
                    'required_tier' => $training->required_tier,
                    'partner_uuid' => $partnerUuid
                ]);
                return false;
            }

            // Create enrollment
            PartnerTrainingEnrollment::create([
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'enrolled_at' => now(),
                'status' => 'enrolled',
                'is_required' => $isRequired,
                'progress_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Partner enrolled in training", [
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'is_required' => $isRequired,
                'training_title' => $training->title
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to enroll partner in training", [
                'error' => $e->getMessage(),
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid
            ]);
            return false;
        }
    }

    /**
     * Update training progress
     */
    public function updateProgress(int $trainingId, string $partnerUuid, int $progressPercentage, ?array $progressData = null): bool
    {
        try {
            $enrollment = PartnerTrainingEnrollment::where('training_id', $trainingId)
                ->where('partner_uuid', $partnerUuid)
                ->first();

            if (!$enrollment) {
                Log::warning("Enrollment not found for progress update", [
                    'training_id' => $trainingId,
                    'partner_uuid' => $partnerUuid
                ]);
                return false;
            }

            $previousProgress = $enrollment->progress_percentage;
            $updateData = [
                'progress_percentage' => max(0, min(100, $progressPercentage)),
                'updated_at' => now()
            ];

            // Update status based on progress
            if ($progressPercentage >= 100) {
                $updateData['status'] = 'completed';
                $updateData['completed_at'] = now();
            } elseif ($progressPercentage > 0) {
                $updateData['status'] = 'in_progress';
                if (!$enrollment->started_at) {
                    $updateData['started_at'] = now();
                }
            }

            // Add progress data if provided
            if ($progressData) {
                $updateData['progress_data'] = $progressData;
            }

            $enrollment->update($updateData);

            Log::info("Training progress updated", [
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'previous_progress' => $previousProgress,
                'new_progress' => $progressPercentage,
                'status' => $updateData['status'] ?? $enrollment->status
            ]);

            // Handle completion
            if ($progressPercentage >= 100) {
                $this->handleTrainingCompletion($enrollment);
            }

            return true;

        } catch (Exception $e) {
            Log::error("Failed to update training progress", [
                'error' => $e->getMessage(),
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'progress_percentage' => $progressPercentage
            ]);
            return false;
        }
    }

    /**
     * Record training assessment score
     */
    public function recordAssessmentScore(int $trainingId, string $partnerUuid, float $score, int $maxScore = 100, ?array $assessmentData = null): bool
    {
        try {
            $enrollment = PartnerTrainingEnrollment::where('training_id', $trainingId)
                ->where('partner_uuid', $partnerUuid)
                ->first();

            if (!$enrollment) {
                Log::warning("Enrollment not found for assessment score", [
                    'training_id' => $trainingId,
                    'partner_uuid' => $partnerUuid
                ]);
                return false;
            }

            $normalizedScore = ($score / $maxScore) * 100;
            $passed = $normalizedScore >= 70; // 70% passing score

            $enrollment->update([
                'assessment_score' => $normalizedScore,
                'assessment_passed' => $passed,
                'assessment_data' => $assessmentData,
                'assessed_at' => now(),
                'updated_at' => now()
            ]);

            // Update completion status if assessment is passed
            if ($passed && $enrollment->progress_percentage >= 100) {
                $enrollment->update([
                    'status' => 'passed',
                    'completed_at' => now()
                ]);

                $this->handleTrainingCompletion($enrollment);
            } elseif (!$passed) {
                $enrollment->update(['status' => 'failed']);
            }

            Log::info("Assessment score recorded", [
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'score' => $normalizedScore,
                'passed' => $passed
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to record assessment score", [
                'error' => $e->getMessage(),
                'training_id' => $trainingId,
                'partner_uuid' => $partnerUuid,
                'score' => $score
            ]);
            return false;
        }
    }

    /**
     * Get partner's training history
     */
    public function getPartnerTrainingHistory(string $partnerUuid): array
    {
        try {
            $enrollments = PartnerTrainingEnrollment::where('partner_uuid', $partnerUuid)
                ->with('training')
                ->orderBy('enrolled_at', 'desc')
                ->get();

            $history = [];
            foreach ($enrollments as $enrollment) {
                $training = $enrollment->training;
                $history[] = [
                    'training_id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                    'training_type' => $training->training_type,
                    'difficulty_level' => $training->difficulty_level,
                    'duration_hours' => $training->duration_hours,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'started_at' => $enrollment->started_at,
                    'completed_at' => $enrollment->completed_at,
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'assessment_score' => $enrollment->assessment_score,
                    'assessment_passed' => $enrollment->assessment_passed,
                    'is_required' => $enrollment->is_required,
                    'certification_earned' => $enrollment->certification_earned
                ];
            }

            return $history;

        } catch (Exception $e) {
            Log::error("Failed to get partner training history", [
                'error' => $e->getMessage(),
                'partner_uuid' => $partnerUuid
            ]);
            return [];
        }
    }

    /**
     * Get available trainings for partner
     */
    public function getAvailableTrainings(string $partnerUuid): array
    {
        try {
            $partner = PartnerUser::where('user_uuid', $partnerUuid)->first();
            if (!$partner) {
                return [];
            }

            // Get trainings that partner hasn't enrolled in yet
            $enrolledTrainingIds = PartnerTrainingEnrollment::where('partner_uuid', $partnerUuid)
                ->pluck('training_id')
                ->toArray();

            $availableTrainings = PartnerTraining::whereNotIn('id', $enrolledTrainingIds)
                ->where('status', 'scheduled')
                ->where(function ($query) use ($partner) {
                    $query->whereNull('required_tier')
                        ->orWhere('required_tier', $partner->tier_name)
                        ->orWhere(function ($subQuery) use ($partner) {
                            // Allow higher tier partners to access lower tier trainings
                            $tierHierarchy = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
                            $partnerTierLevel = $tierHierarchy[$partner->tier_name] ?? 1;

                            foreach ($tierHierarchy as $tier => $level) {
                                if ($level <= $partnerTierLevel) {
                                    $subQuery->orWhere('required_tier', $tier);
                                }
                            }
                        });
                })
                ->orderBy('scheduled_start')
                ->get()
                ->toArray();

            return $availableTrainings;

        } catch (Exception $e) {
            Log::error("Failed to get available trainings", [
                'error' => $e->getMessage(),
                'partner_uuid' => $partnerUuid
            ]);
            return [];
        }
    }

    /**
     * Get training statistics for admin
     */
    public function getTrainingStatistics(): array
    {
        try {
            return [
                'total_trainings' => PartnerTraining::count(),
                'active_trainings' => PartnerTraining::where('status', 'scheduled')->count(),
                'completed_trainings' => PartnerTraining::where('status', 'completed')->count(),
                'total_enrollments' => PartnerTrainingEnrollment::count(),
                'completed_enrollments' => PartnerTrainingEnrollment::where('status', 'passed')->count(),
                'average_completion_rate' => $this->calculateAverageCompletionRate(),
                'training_types' => PartnerTraining::select('training_type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('training_type')
                    ->pluck('count', 'training_type')
                    ->toArray(),
                'tier_participation' => $this->getTierParticipationStats(),
                'recent_completions' => $this->getRecentCompletions(10)
            ];

        } catch (Exception $e) {
            Log::error("Failed to get training statistics", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Bulk enroll partners in required training
     */
    public function bulkEnrollRequiredTraining(int $trainingId, ?string $tierRequirement = null): array
    {
        try {
            $training = PartnerTraining::find($trainingId);
            if (!$training) {
                return ['success' => false, 'message' => 'Training not found'];
            }

            $query = PartnerUser::query();
            if ($tierRequirement) {
                $query->where('tier_name', $tierRequirement);
            }

            $partners = $query->get();
            $enrolled = 0;
            $failed = 0;

            foreach ($partners as $partner) {
                if ($this->enrollPartner($trainingId, $partner->user_uuid, true)) {
                    $enrolled++;
                } else {
                    $failed++;
                }
            }

            Log::info("Bulk enrollment completed", [
                'training_id' => $trainingId,
                'training_title' => $training->title,
                'tier_requirement' => $tierRequirement,
                'total_partners' => $partners->count(),
                'enrolled' => $enrolled,
                'failed' => $failed
            ]);

            return [
                'success' => true,
                'total_partners' => $partners->count(),
                'enrolled' => $enrolled,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            Log::error("Failed to bulk enroll partners", [
                'error' => $e->getMessage(),
                'training_id' => $trainingId,
                'tier_requirement' => $tierRequirement
            ]);
            return ['success' => false, 'message' => 'Bulk enrollment failed'];
        }
    }

    /**
     * Helper methods
     */
    private function checkTierRequirement(string $partnerTier, string $requiredTier): bool
    {
        $tierHierarchy = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
        $partnerLevel = $tierHierarchy[$partnerTier] ?? 1;
        $requiredLevel = $tierHierarchy[$requiredTier] ?? 1;

        return $partnerLevel >= $requiredLevel;
    }

    private function handleTrainingCompletion(PartnerTrainingEnrollment $enrollment): void
    {
        try {
            $training = $enrollment->training;

            // Award certification if offered
            if ($training->certification_offered && $enrollment->assessment_passed) {
                $enrollment->update([
                    'certification_earned' => true,
                    'certification_date' => now()
                ]);

                Log::info("Certification awarded", [
                    'training_id' => $training->id,
                    'partner_uuid' => $enrollment->partner_uuid,
                    'training_title' => $training->title
                ]);
            }

            // Record performance metric
            app(PartnerPerformanceService::class)->recordMetric(
                $enrollment->partner_uuid,
                'training_completed',
                1,
                [
                    'training_id' => $training->id,
                    'training_title' => $training->title,
                    'duration_hours' => $training->duration_hours,
                    'assessment_score' => $enrollment->assessment_score,
                    'certification_earned' => $enrollment->certification_earned
                ]
            );

        } catch (Exception $e) {
            Log::error("Failed to handle training completion", [
                'error' => $e->getMessage(),
                'enrollment_id' => $enrollment->id
            ]);
        }
    }

    private function calculateAverageCompletionRate(): float
    {
        $totalEnrollments = PartnerTrainingEnrollment::count();
        if ($totalEnrollments === 0) {
            return 0.0;
        }

        $completedEnrollments = PartnerTrainingEnrollment::whereIn('status', ['passed', 'completed'])->count();
        return ($completedEnrollments / $totalEnrollments) * 100;
    }

    private function getTierParticipationStats(): array
    {
        return DB::table('partner_training_enrollments as pte')
            ->join('partner_users as pu', 'pte.partner_uuid', '=', 'pu.user_uuid')
            ->select('pu.tier_name')
            ->selectRaw('COUNT(*) as enrollment_count')
            ->groupBy('pu.tier_name')
            ->pluck('enrollment_count', 'tier_name')
            ->toArray();
    }

    private function getRecentCompletions(int $limit): array
    {
        return PartnerTrainingEnrollment::where('status', 'passed')
            ->with(['training'])
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'partner_uuid' => $enrollment->partner_uuid,
                    'training_title' => $enrollment->training->title,
                    'completed_at' => $enrollment->completed_at,
                    'assessment_score' => $enrollment->assessment_score,
                    'certification_earned' => $enrollment->certification_earned
                ];
            })
            ->toArray();
    }
}