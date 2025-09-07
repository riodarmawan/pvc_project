<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GeminiChatController extends Controller
{
    public function showChatView(int $branch_id)
    {
        Log::info("Show chat for branch_id={$branch_id}");
        $branch = DB::table('branches')->where('id', $branch_id)->first();
        if (!$branch) abort(404, 'Cabang tidak ditemukan.');
        return view('chat.index', ['branch' => $branch]);
    }

    public function handleChatMessage(Request $request, int $branch_id)
    {
        Log::info("===== CHAT BARU (branch {$branch_id}) =====");

        $validated = $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
            'image'   => 'nullable|string',
        ]);

        // Pastikan cabang ada
        if (!DB::table('branches')->where('id', $branch_id)->exists()) {
            return response()->json(['error' => 'Cabang tidak ditemukan.'], 404);
        }

        // Panggil FastAPI /chat
        $svc = config('services.catalog', [
            'url'     => env('CATALOG_SERVICE_URL', 'http://127.0.0.1:9000'),
            'token'   => env('CATALOG_SERVICE_TOKEN', 'super-secret-token'),
            'timeout' => (int) env('CATALOG_SERVICE_TIMEOUT', 20),
        ]);
        $chatUrl = rtrim($svc['url'] ?? 'http://127.0.0.1:9000', '/') . '/chat';

        try {
            $payload = [
                'branch_id' => (int) $branch_id,
                'message'   => $validated['message'],
                'history'   => $validated['history'] ?? [],
                'image'     => $validated['image'] ?? null,
            ];

            $resp = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    ])
                    ->timeout($svc['timeout'])
                    ->connectTimeout(3)
                    ->withHeaders(['X-Internal-Token' => $svc['token'] ?? ''])
                    ->post($chatUrl, $payload);

            if ($resp->failed()) {
                Log::error("FastAPI /chat gagal", ['status'=>$resp->status(),'body'=>$resp->body()]);
                return response()->json(['error' => 'Gagal berkomunikasi dengan layanan AI.'], 502);
            }

            $json = $resp->json();
            return response()->json(['reply' => (string)($json['reply'] ?? '')]);
        } catch (\Throwable $e) {
            Log::error("Exception panggil FastAPI /chat: ".$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal berkomunikasi dengan layanan AI.'], 502);
        }
    }
}
