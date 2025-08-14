<?php

// app/Http/Controllers/AIAnalysisController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AIAnalysisService;
use App\Services\WebScrapingService;
use App\Models\AIAnalysisResult;
use App\Models\Isu;
use App\Models\RefSkala;
use App\Models\RefTone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AIAnalysisController extends Controller
{
    private $aiAnalysisService;
    private $webScrapingService;

    public function __construct(
        AIAnalysisService $aiAnalysisService,
        WebScrapingService $webScrapingService
    ) {
        $this->aiAnalysisService = $aiAnalysisService;
        $this->webScrapingService = $webScrapingService;
        $this->middleware('auth');
    }

    /**
     * Show AI Analysis Create Interface
     */
    public function create()
    {
        // Get reference data for dropdowns
        $skalaList = RefSkala::where('aktif', true)->orderBy('urutan')->get();
        $toneList = RefTone::where('aktif', true)->orderBy('urutan')->get();
        
        return view('isu.ai-create', compact('skalaList', 'toneList'));
    }

    /**
     * Process AI Analysis (AJAX endpoint)
     */
    public function analyze(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'urls' => 'required|array|min:1|max:5',
                'urls.*' => 'required|url|max:2000',
                'analysis_type' => 'in:comprehensive,quick',
                'include_sentiment' => 'boolean',
                'custom_instructions' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $urls = $request->input('urls');
            $options = [
                'analysis_type' => $request->input('analysis_type', 'comprehensive'),
                'include_sentiment' => $request->input('include_sentiment', true),
                'custom_instructions' => $request->input('custom_instructions')
            ];

            // Start AI analysis
            $sessionId = $this->aiAnalysisService->analyzeUrls(
                $urls, 
                Auth::id(), 
                $options
            );

            Log::info('AI analysis started', [
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'urls_count' => count($urls)
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Analisis AI dimulai',
                'redirect_url' => route('ai.results', $sessionId)
            ]);

        } catch (\Exception $e) {
            Log::error('AI analysis failed to start', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai analisis AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show AI Analysis Results
     */
    public function results($sessionId = null)
    {
        if (!$sessionId) {
            return redirect()->route('ai.create')
                ->with('error', 'Session ID tidak ditemukan');
        }

        // Get analysis result
        $analysisResult = AIAnalysisResult::where('session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$analysisResult) {
            return redirect()->route('ai.create')
                ->with('error', 'Hasil analisis tidak ditemukan');
        }

        // Get reference data for forms
        $skalaList = RefSkala::where('aktif', true)->orderBy('urutan')->get();
        $toneList = RefTone::where('aktif', true)->orderBy('urutan')->get();

        return view('isu.ai-results', compact(
            'analysisResult', 
            'skalaList', 
            'toneList'
        ));
    }

    /**
     * Get AI Analysis Progress (AJAX endpoint)
     */
    public function progress($sessionId)
    {
        try {
            $analysisResult = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$analysisResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }

            $progress = $this->aiAnalysisService->getAnalysisProgress($sessionId);

            return response()->json([
                'success' => true,
                'status' => $analysisResult->processing_status,
                'progress' => $progress,
                'created_at' => $analysisResult->created_at->format('H:i:s'),
                'estimated_completion' => $progress['time_remaining'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get AI progress', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan progress'
            ], 500);
        }
    }

    /**
     * Store AI Results as Isu
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'judul' => 'required|string|max:500',
                'rangkuman' => 'required|string',
                'narasi_positif' => 'nullable|string',
                'narasi_negatif' => 'nullable|string',
                'tone' => 'nullable|integer|exists:ref_tones,id',
                'skala' => 'nullable|integer|exists:ref_skalas,id',
                'isu_strategis' => 'boolean',
                'send_for_verification' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Store isu from AI results
            $result = $this->aiAnalysisService->storeIsuFromResults(
                $request->all(),
                $request->input('session_id')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to store AI results as isu', [
                'session_id' => $request->input('session_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan isu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate More Title Suggestions (AJAX endpoint)
     */
    public function generateMoreTitles(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'count' => 'integer|min:1|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->aiAnalysisService->generateMoreTitles(
                $request->input('session_id'),
                $request->input('count', 5)
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to generate more titles', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate judul tambahan'
            ], 500);
        }
    }

    /**
     * Preview URL Content (AJAX endpoint)
     */
    public function previewUrl(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'url' => 'required|url|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->webScrapingService->extractContent(
                $request->input('url')
            );

            return response()->json([
                'success' => true,
                'preview' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to preview URL', [
                'url' => $request->input('url'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal preview URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel AI Analysis (AJAX endpoint)
     */
    public function cancel($sessionId)
    {
        try {
            $analysisResult = AIAnalysisResult::where('session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$analysisResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }

            if (in_array($analysisResult->processing_status, ['completed', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analisis sudah selesai, tidak bisa dibatalkan'
                ], 400);
            }

            $analysisResult->update([
                'processing_status' => 'cancelled',
                'error_message' => 'Dibatalkan oleh user'
            ]);

            Log::info('AI analysis cancelled', [
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Analisis dibatalkan'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel AI analysis', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan analisis'
            ], 500);
        }
    }

    /**
     * Get AI Analysis Dashboard Data
     */
    public function dashboard()
    {
        try {
            $statistics = $this->aiAnalysisService->getAnalysisStatistics(Auth::id());
            
            return view('ai.dashboard', compact('statistics'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load AI dashboard', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Gagal memuat dashboard AI');
        }
    }
}