<?php

namespace Jiny\Partner\Console\Commands;

use Illuminate\Console\Command;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Support\Facades\Log;

/**
 * 파트너 통계 데이터 업데이트 명령
 *
 * 용도:
 * - 모든 파트너의 캐시된 통계 데이터를 실시간 데이터로 업데이트
 * - 성능 향상을 위해 주기적으로 실행 (cron job)
 * - 대량 데이터 처리 시 청크 방식 사용
 */
class UpdatePartnerStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partner:update-statistics
                            {--chunk=100 : Number of partners to process per batch}
                            {--force : Force update even if recently updated}
                            {--partner= : Update specific partner by ID}
                            {--status=active : Only update partners with specific status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cached statistics for all partners (sales, commissions, balance)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('파트너 통계 업데이트를 시작합니다...');
        $startTime = now();

        // 옵션 값 가져오기
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');
        $partnerId = $this->option('partner');
        $status = $this->option('status');

        // 특정 파트너만 업데이트하는 경우
        if ($partnerId) {
            return $this->updateSinglePartner($partnerId, $force);
        }

        // 전체 파트너 업데이트
        return $this->updateAllPartners($chunkSize, $force, $status);
    }

    /**
     * 특정 파트너 통계 업데이트
     */
    private function updateSinglePartner($partnerId, $force = false)
    {
        try {
            $partner = PartnerUser::findOrFail($partnerId);

            $this->info("파트너 '{$partner->name}' (ID: {$partnerId}) 통계를 업데이트 중...");

            $stats = $partner->updateCachedStatistics();

            $this->info("업데이트 완료:");
            $this->line("  - 총 매출액: " . number_format($stats['total_sales']) . "원");
            $this->line("  - 총 커미션: " . number_format($stats['total_commissions']) . "원");
            $this->line("  - 팀 매출액: " . number_format($stats['team_total_sales']) . "원");
            $this->line("  - 커미션 잔액: " . number_format($stats['commission_balance']) . "원");

            return 0;

        } catch (\Exception $e) {
            $this->error("파트너 {$partnerId} 업데이트 실패: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 전체 파트너 통계 업데이트
     */
    private function updateAllPartners($chunkSize, $force, $status)
    {
        $query = PartnerUser::query();

        // 상태 필터 적용
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // 강제 업데이트가 아닌 경우 최근 업데이트되지 않은 파트너만 처리
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('statistics_updated_at')
                  ->orWhere('statistics_updated_at', '<', now()->subHour());
            });
        }

        $totalPartners = $query->count();

        if ($totalPartners === 0) {
            $this->info('업데이트할 파트너가 없습니다.');
            return 0;
        }

        $this->info("총 {$totalPartners}명의 파트너 통계를 업데이트합니다.");
        $this->info("청크 크기: {$chunkSize}");

        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;

        // 프로그레스 바 생성
        $progressBar = $this->output->createProgressBar($totalPartners);
        $progressBar->start();

        // 청크 단위로 처리
        $query->chunk($chunkSize, function ($partners) use (&$processedCount, &$successCount, &$errorCount, $progressBar) {
            foreach ($partners as $partner) {
                try {
                    $partner->updateCachedStatistics();
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Partner statistics update failed", [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'error' => $e->getMessage()
                    ]);
                }

                $processedCount++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // 결과 요약
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->info("=== 업데이트 완료 ===");
        $this->line("처리된 파트너: {$processedCount}명");
        $this->line("성공: {$successCount}명");

        if ($errorCount > 0) {
            $this->warn("실패: {$errorCount}명");
            $this->line("오류 상세는 로그를 확인하세요.");
        }

        $this->line("소요 시간: {$duration}초");

        // 성능 지표
        if ($duration > 0) {
            $throughput = round($processedCount / $duration, 1);
            $this->line("처리 속도: {$throughput}명/초");
        }

        // 전체 통계 샘플 표시
        $this->displayOverallStatistics();

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * 전체 통계 요약 표시
     */
    private function displayOverallStatistics()
    {
        $this->newLine();
        $this->info("=== 전체 통계 요약 ===");

        try {
            // 실시간 계산으로 정확한 통계 표시
            $totalSales = \Jiny\Partner\Models\PartnerSales::where('status', 'confirmed')->sum('amount');
            $totalCommissions = \Jiny\Partner\Models\PartnerCommission::where('status', '!=', 'cancelled')->sum('commission_amount');
            $paidCommissions = \Jiny\Partner\Models\PartnerCommission::where('status', 'paid')->sum('commission_amount');
            $pendingCommissions = $totalCommissions - $paidCommissions;

            $activePartners = PartnerUser::where('status', 'active')->count();
            $commissionRate = $totalSales > 0 ? round(($totalCommissions / $totalSales) * 100, 2) : 0;

            $this->line("활성 파트너: " . number_format($activePartners) . "명");
            $this->line("총 매출액: " . number_format($totalSales) . "원");
            $this->line("총 커미션: " . number_format($totalCommissions) . "원 ({$commissionRate}%)");
            $this->line("지급 완료: " . number_format($paidCommissions) . "원");
            $this->line("지급 대기: " . number_format($pendingCommissions) . "원");

        } catch (\Exception $e) {
            $this->warn("전체 통계 계산 실패: " . $e->getMessage());
        }
    }
}