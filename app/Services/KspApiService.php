<?php
// app/Services/KspApiService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class KspApiService
{
    private $apiUrl;
    private $apiKey;
    private $timeout;
    private $verifySSL;
    private $debugEnabled;
    
    public function __construct()
    {
        // Multi-source configuration untuk backward compatibility
        $this->apiUrl = config('services.ksp_api.url') 
                     ?: env('KSP_API_URL', 'https://layanan-api.ksp.go.id/index.php/login');
        
        $this->apiKey = config('services.ksp_api.key') 
                     ?: env('KSP_API_KEY', 'e7f0s9Cc9feBf61d49i3Kz5');
        
        $this->timeout = config('services.ksp_api.timeout') 
                      ?: env('KSP_API_TIMEOUT', 30);
        
        $this->verifySSL = config('services.ksp_api.verify_ssl') 
                        ?: env('KSP_API_VERIFY_SSL', false);
        
        $this->debugEnabled = config('services.ksp_api.debug_enabled') 
                           ?: config('app.debug', false);

        // Enhanced initialization logging
        Log::info('KSP API Service initialized', [
            'api_url' => $this->apiUrl,
            'api_key_present' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey ?? ''),
            'timeout' => $this->timeout,
            'verify_ssl' => $this->verifySSL,
            'debug_enabled' => $this->debugEnabled,
        ]);
    }
    
    /**
     * Authenticate user via KSP API
     * Enhanced untuk kompatibilitas dengan AuthController
     */
    public function authenticate(string $username, string $password): array
    {
        $startTime = microtime(true);
        
        try {
            if ($this->debugEnabled) {
                Log::info('=== KSP API AUTHENTICATION STARTED ===', [
                    'username' => $username,
                    'password_length' => strlen($password),
                    'api_url' => $this->apiUrl,
                    'api_key_present' => !empty($this->apiKey),
                ]);
            }
            
            // Validasi konfigurasi
            if (empty($this->apiUrl)) {
                throw new Exception('KSP API URL not configured');
            }
            
            if (empty($this->apiKey)) {
                throw new Exception('KSP API Key not configured');
            }
            
            // Prepare request body
            $body = [
                'username' => trim($username),
                'password' => $password
            ];
            
            // Setup HTTP client
            $httpClient = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'User-Agent' => 'MediaMonitoring/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->timeout($this->timeout);
            
            // SSL verification setting
            if (!$this->verifySSL) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            // Send request
            $response = $httpClient->post($this->apiUrl, $body);
            $result = $response->json();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Enhanced response logging
            Log::info('KSP API Response received', [
                'username' => $username,
                'status_code' => $response->status(),
                'execution_time_ms' => $executionTime,
                'response_success' => $response->successful(),
                'api_status' => $result['status'] ?? 'unknown',
                'response_structure' => $this->getResponseStructure($result),
            ]);
            
            // Check HTTP response
            if (!$response->successful()) {
                Log::error('KSP API HTTP Error', [
                    'username' => $username,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'execution_time_ms' => $executionTime,
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'message' => "KSP API Server Error (HTTP {$response->status()})",
                    'response_code' => $response->status(),
                    'execution_time_ms' => $executionTime,
                ];
            }
            
            // Parse and validate JSON response
            if (!is_array($result)) {
                Log::error('KSP API invalid JSON response', [
                    'username' => $username,
                    'response_body' => $response->body(),
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid API response format',
                    'execution_time_ms' => $executionTime,
                ];
            }
            
            // Check authentication status
            $isAuthenticated = ($result['status'] ?? false) === true;
            
            if ($isAuthenticated) {
                Log::info('KSP API Authentication SUCCESS', [
                    'username' => $username,
                    'execution_time_ms' => $executionTime,
                    'user_data_present' => isset($result['data']),
                    'user_data_keys' => isset($result['data']) ? array_keys($result['data']) : [],
                ]);
                
                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'message' => $result['message'] ?? 'Authentication successful',
                    'execution_time_ms' => $executionTime,
                ];
            } else {
                // Authentication failed
                $errorMessage = $result['message'] ?? 'Invalid credentials';
                
                Log::warning('KSP API Authentication FAILED', [
                    'username' => $username,
                    'api_error_message' => $errorMessage,
                    'execution_time_ms' => $executionTime,
                    'api_response' => $result,
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'message' => $errorMessage,
                    'execution_time_ms' => $executionTime,
                ];
            }
            
        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('KSP API Authentication Exception', [
                'username' => $username,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'execution_time_ms' => $executionTime,
                'stack_trace' => $this->debugEnabled ? $e->getTraceAsString() : 'Debug disabled',
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'Koneksi ke server KSP gagal: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'execution_time_ms' => $executionTime,
            ];
        }
    }
    
    /**
     * Test connection to KSP API server
     * Enhanced untuk debugging
     */
    public function testConnection(): array
    {
        Log::info('Testing KSP API connection', [
            'api_url' => $this->apiUrl,
            'api_key_present' => !empty($this->apiKey),
        ]);
        
        try {
            if (empty($this->apiUrl)) {
                return [
                    'success' => false,
                    'message' => 'API URL not configured',
                    'config' => $this->getConfigInfo(),
                ];
            }
            
            // Test dengan dummy request untuk check connectivity
            $httpClient = Http::withHeaders([
                'User-Agent' => 'MediaMonitoring/1.0 (Connection Test)',
            ])
            ->timeout(10);
            
            if (!$this->verifySSL) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            // Simple HEAD request to test connectivity
            $response = $httpClient->head($this->apiUrl);
            
            $connectionStatus = [
                'success' => true,
                'message' => 'Connection test completed',
                'status_code' => $response->status(),
                'response_time_ms' => $response->transferStats ? 
                    round($response->transferStats->getTransferTime() * 1000, 2) : null,
                'config' => $this->getConfigInfo(),
            ];
            
            Log::info('KSP API connection test result', $connectionStatus);
            
            return $connectionStatus;
            
        } catch (Exception $e) {
            $errorResult = [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'config' => $this->getConfigInfo(),
            ];
            
            Log::error('KSP API connection test failed', $errorResult);
            
            return $errorResult;
        }
    }
    
    /**
     * Validate API configuration
     * Enhanced untuk comprehensive validation
     */
    public function validateConfig(): array
    {
        $issues = [];
        $warnings = [];
        
        // URL validation
        if (empty($this->apiUrl)) {
            $issues[] = 'API URL not configured';
        } elseif (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            $issues[] = 'API URL format invalid';
        } elseif (!str_starts_with($this->apiUrl, 'https://')) {
            $warnings[] = 'API URL is not using HTTPS (security risk)';
        }
        
        // API Key validation
        if (empty($this->apiKey)) {
            $issues[] = 'API Key not configured';
        } elseif (strlen($this->apiKey) < 10) {
            $warnings[] = 'API Key seems too short (possibly invalid)';
        }
        
        // Timeout validation
        if ($this->timeout < 5) {
            $warnings[] = 'Timeout is very low (< 5 seconds)';
        } elseif ($this->timeout > 60) {
            $warnings[] = 'Timeout is very high (> 60 seconds)';
        }
        
        $validation = [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'config' => $this->getConfigInfo(),
        ];
        
        Log::info('KSP API configuration validation', $validation);
        
        return $validation;
    }
    
    /**
     * Get configuration info (safe untuk logging)
     */
    private function getConfigInfo(): array
    {
        return [
            'api_url' => $this->apiUrl,
            'api_key_present' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey ?? ''),
            'timeout' => $this->timeout,
            'verify_ssl' => $this->verifySSL,
            'debug_enabled' => $this->debugEnabled,
        ];
    }
    
    /**
     * Get response structure untuk debugging
     */
    private function getResponseStructure($response): array
    {
        if (!is_array($response)) {
            return [gettype($response)];
        }
        
        $structure = [];
        foreach ($response as $key => $value) {
            if (is_array($value)) {
                $structure[$key] = 'array(' . count($value) . ')';
            } else {
                $structure[$key] = gettype($value);
            }
        }
        
        return $structure;
    }
    
    /**
     * Check if service is properly configured and available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }
    
    /**
     * Get service status untuk monitoring
     */
    public function getStatus(): array
    {
        return [
            'service_available' => $this->isAvailable(),
            'last_check' => now()->toISOString(),
            'config_valid' => $this->validateConfig()['valid'],
            'configuration' => $this->getConfigInfo(),
        ];
    }
}