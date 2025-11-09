<?php

namespace Jiny\Partner\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerFileUploadService
{
    /**
     * 파트너 신청서 관련 파일 업로드
     * UUID 기반 디렉토리 구조: /storage/public/partner/{user_uuid}/
     */
    public function uploadPartnerFiles(string $userUuid, array $files): array
    {
        $uploadResults = [];
        $baseDirectory = "partner/{$userUuid}";

        try {
            // 기본 디렉토리 생성
            $this->ensureDirectoryExists($baseDirectory);

            foreach ($files as $fileType => $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $result = $this->uploadSingleFile($file, $baseDirectory, $fileType);
                    if ($result) {
                        $uploadResults[$fileType] = $result;
                    }
                } elseif (is_array($file)) {
                    // 여러 파일 업로드 (자격증 등)
                    $uploadResults[$fileType] = [];
                    foreach ($file as $index => $singleFile) {
                        if ($singleFile instanceof UploadedFile && $singleFile->isValid()) {
                            $result = $this->uploadSingleFile($singleFile, $baseDirectory, $fileType, $index);
                            if ($result) {
                                $uploadResults[$fileType][] = $result;
                            }
                        }
                    }
                }
            }

            Log::info('Partner files uploaded successfully', [
                'user_uuid' => $userUuid,
                'uploaded_files' => array_keys($uploadResults),
                'base_directory' => $baseDirectory
            ]);

            return $uploadResults;

        } catch (\Exception $e) {
            Log::error('Partner file upload failed', [
                'user_uuid' => $userUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 업로드 실패 시 이미 업로드된 파일들 정리
            $this->cleanupUploadedFiles($uploadResults);

            throw $e;
        }
    }

    /**
     * 단일 파일 업로드
     */
    private function uploadSingleFile(UploadedFile $file, string $baseDirectory, string $fileType, ?int $index = null): ?array
    {
        try {
            // 파일 타입별 서브디렉토리 설정
            $subDirectory = $this->getSubDirectory($fileType);
            $fullDirectory = "{$baseDirectory}/{$subDirectory}";

            // 안전한 파일명 생성
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = $this->generateSafeFileName($originalName, $extension, $index);

            // 파일 저장
            $filePath = $file->storeAs($fullDirectory, $safeName, 'public');

            if (!$filePath) {
                throw new \Exception("Failed to store file: {$originalName}");
            }

            // 파일 정보 반환
            $fileInfo = [
                'original_name' => $originalName,
                'stored_name' => $safeName,
                'file_path' => $filePath,
                'public_url' => Storage::url($filePath),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'uploaded_at' => now()->toISOString()
            ];

            // 파일 유효성 검사
            $this->validateUploadedFile($fileInfo, $fileType);

            Log::info('Single file uploaded', [
                'file_type' => $fileType,
                'original_name' => $originalName,
                'stored_path' => $filePath,
                'file_size' => $file->getSize()
            ]);

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('Single file upload failed', [
                'file_type' => $fileType,
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 파일 타입별 서브디렉토리 반환
     */
    private function getSubDirectory(string $fileType): string
    {
        $directories = [
            'resume' => 'resume',
            'portfolio' => 'portfolio',
            'certificates' => 'certificates',
            'additional' => 'additional',
            'identification' => 'identification',
            'education' => 'education',
            'experience' => 'experience'
        ];

        return $directories[$fileType] ?? 'misc';
    }

    /**
     * 안전한 파일명 생성
     */
    private function generateSafeFileName(string $originalName, string $extension, ?int $index = null): string
    {
        // 파일명에서 안전하지 않은 문자 제거
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9가-힣\-_\.]/', '_', $baseName);
        $safeName = preg_replace('/_{2,}/', '_', $safeName); // 연속된 언더스코어 제거

        // 최대 길이 제한
        if (strlen($safeName) > 100) {
            $safeName = substr($safeName, 0, 100);
        }

        // 인덱스가 있는 경우 (여러 파일)
        $indexSuffix = $index !== null ? "_{$index}" : '';

        // 타임스탬프 추가로 중복 방지
        $timestamp = now()->format('Ymd_His');

        // 최종 파일명 구성
        $finalName = "{$safeName}{$indexSuffix}_{$timestamp}";

        // 확장자 추가
        return $extension ? "{$finalName}.{$extension}" : $finalName;
    }

    /**
     * 업로드된 파일 유효성 검사
     */
    private function validateUploadedFile(array $fileInfo, string $fileType): void
    {
        // 파일 크기 검사 (기본 10MB 제한)
        $maxSizes = [
            'resume' => 10 * 1024 * 1024,      // 10MB
            'portfolio' => 50 * 1024 * 1024,   // 50MB
            'certificates' => 5 * 1024 * 1024, // 5MB
            'additional' => 10 * 1024 * 1024   // 10MB
        ];

        $maxSize = $maxSizes[$fileType] ?? 10 * 1024 * 1024;
        if ($fileInfo['file_size'] > $maxSize) {
            throw new \Exception("File size exceeds limit for {$fileType}: " . number_format($fileInfo['file_size'] / 1024 / 1024, 2) . 'MB');
        }

        // MIME 타입 검사
        $allowedMimeTypes = [
            'resume' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'portfolio' => ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/zip'],
            'certificates' => ['application/pdf', 'image/jpeg', 'image/png'],
            'additional' => ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ];

        $allowedTypes = $allowedMimeTypes[$fileType] ?? $allowedMimeTypes['additional'];
        if (!in_array($fileInfo['mime_type'], $allowedTypes)) {
            throw new \Exception("Invalid file type for {$fileType}: {$fileInfo['mime_type']}");
        }

        // 실제 파일이 저장되었는지 확인
        if (!Storage::disk('public')->exists($fileInfo['file_path'])) {
            throw new \Exception("File was not properly stored: {$fileInfo['file_path']}");
        }
    }

    /**
     * 디렉토리 존재 여부 확인 및 생성
     */
    private function ensureDirectoryExists(string $directory): void
    {
        $subdirectories = ['resume', 'portfolio', 'certificates', 'additional'];

        foreach ($subdirectories as $subdir) {
            $fullPath = "{$directory}/{$subdir}";
            if (!Storage::disk('public')->exists($fullPath)) {
                Storage::disk('public')->makeDirectory($fullPath);
            }
        }
    }

    /**
     * 업로드 실패 시 파일 정리
     */
    private function cleanupUploadedFiles(array $uploadResults): void
    {
        foreach ($uploadResults as $fileType => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (isset($file['file_path']) && Storage::disk('public')->exists($file['file_path'])) {
                        Storage::disk('public')->delete($file['file_path']);
                    }
                }
            } elseif (isset($files['file_path']) && Storage::disk('public')->exists($files['file_path'])) {
                Storage::disk('public')->delete($files['file_path']);
            }
        }

        Log::info('Cleaned up uploaded files due to upload failure', [
            'cleaned_files' => count($uploadResults)
        ]);
    }

    /**
     * 기존 파일 삭제
     */
    public function deletePartnerFiles(string $userUuid, array $filePaths = null): bool
    {
        try {
            if ($filePaths) {
                // 특정 파일들만 삭제
                foreach ($filePaths as $filePath) {
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            } else {
                // 해당 사용자의 모든 파일 삭제
                $userDirectory = "partner/{$userUuid}";
                if (Storage::disk('public')->exists($userDirectory)) {
                    Storage::disk('public')->deleteDirectory($userDirectory);
                }
            }

            Log::info('Partner files deleted', [
                'user_uuid' => $userUuid,
                'specific_files' => $filePaths ? count($filePaths) : 'all'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete partner files', [
                'user_uuid' => $userUuid,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 파일 URL 생성
     */
    public function getFileUrl(string $filePath): string
    {
        return Storage::url($filePath);
    }

    /**
     * 파일 다운로드 가능 여부 확인
     */
    public function canDownloadFile(string $filePath, string $userUuid): bool
    {
        // 파일 경로가 해당 사용자의 디렉토리 내에 있는지 확인
        $userDirectory = "partner/{$userUuid}";
        return strpos($filePath, $userDirectory) === 0 && Storage::disk('public')->exists($filePath);
    }

    /**
     * 디스크 사용량 조회
     */
    public function getDirectorySize(string $userUuid): array
    {
        $userDirectory = "partner/{$userUuid}";
        $totalSize = 0;
        $fileCount = 0;

        try {
            $files = Storage::disk('public')->allFiles($userDirectory);
            foreach ($files as $file) {
                $totalSize += Storage::disk('public')->size($file);
                $fileCount++;
            }

            return [
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'file_count' => $fileCount,
                'directory' => $userDirectory
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate directory size', [
                'user_uuid' => $userUuid,
                'error' => $e->getMessage()
            ]);

            return [
                'total_size_bytes' => 0,
                'total_size_mb' => 0,
                'file_count' => 0,
                'directory' => $userDirectory
            ];
        }
    }
}