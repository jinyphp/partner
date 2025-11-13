<?php

namespace Jiny\Partner\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * JWT 인증 확인
     *
     * @param Request $request
     * @return object|null 사용자 객체 또는 null
     */
    protected function auth(Request $request)
    {
        // 세션 기반 인증 확인
        if (Auth::check()) {
            return Auth::user();
        }

        // JWT 토큰 확인 (헤더에서)
        $token = $request->bearerToken() ?: $request->header('Authorization');

        if ($token) {
            try {
                // JWT 토큰이 있는 경우도 세션 인증 사용자 반환
                // (실제 JWT 구현 시 여기를 수정)
                if (Auth::check()) {
                    return Auth::user();
                }
            } catch (\Exception $e) {
                // JWT 토큰 검증 실패
            }
        }

        return null;
    }

    /**
     * 응답 형식 통일
     */
    protected function jsonResponse($data = null, $message = '', $success = true, $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * 에러 응답
     */
    protected function errorResponse($message = 'An error occurred', $data = null, $status = 400)
    {
        return $this->jsonResponse($data, $message, false, $status);
    }

    /**
     * 성공 응답
     */
    protected function successResponse($data = null, $message = 'Success', $status = 200)
    {
        return $this->jsonResponse($data, $message, true, $status);
    }
}