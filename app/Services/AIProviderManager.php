<?php
// app/Services/AIProviderManager.php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    private $providers = [];
    private $defaultProvider;

    public function __construct()
    {
        $this->loadProviders();
        $this->defaultProvider = config('ai.default_provider', 'groq');
    }

    /**
     * Load available providers
     */
    private function loadProviders(): void
    {
        $providerConfigs = config('ai.providers', []);
        
        foreach ($providerConfigs as $name => $config) {
            if ($config['enabled']) {
                try {
                    $this->providers[$name] = app($config['service']);
                    Log::info("AI Provider loaded: {$name}");
                } catch (\Exception $e) {
                    Log::warning("Failed to load AI provider: {$name}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Get provider by name with fallback
     */
    public function getProvider(string $providerName = null)
    {
        $provider = $providerName ?: $this->defaultProvider;
        
        // Primary provider
        if (isset($this->providers[$provider])) {
            return $this->providers[$provider];
        }
        
        // Fallback to any available provider
        if (!empty($this->providers)) {
            $fallbackProvider = array_key_first($this->providers);
            Log::warning("Provider {$provider} not available, using fallback: {$fallbackProvider}");
            return $this->providers[$fallbackProvider];
        }
        
        throw new \Exception('No AI providers available');
    }

    /**
     * Analyze with automatic failover
     */
    public function analyzeContent(array $combinedContent, int $userId, string $preferredProvider = null): array
    {
        $providers = $this->getProvidersByPriority($preferredProvider);
        
        foreach ($providers as $providerName => $provider) {
            try {
                Log::info("Attempting analysis with provider: {$providerName}");
                
                $result = $provider->analyzeContent($combinedContent, $userId);
                $result['provider_used'] = $providerName;
                
                return $result;
                
            } catch (\Exception $e) {
                Log::warning("Provider {$providerName} failed", [
                    'error' => $e->getMessage()
                ]);
                
                // Continue to next provider
                continue;
            }
        }
        
        throw new \Exception('All AI providers failed');
    }

    /**
     * Get providers sorted by priority
     */
    private function getProvidersByPriority(string $preferredProvider = null): array
    {
        $providers = $this->providers;
        
        // Put preferred provider first
        if ($preferredProvider && isset($providers[$preferredProvider])) {
            $preferred = [$preferredProvider => $providers[$preferredProvider]];
            unset($providers[$preferredProvider]);
            $providers = $preferred + $providers;
        }
        
        return $providers;
    }

    /**
     * Test all providers
     */
    public function testAllProviders(): array
    {
        $results = [];
        
        foreach ($this->providers as $name => $provider) {
            try {
                $results[$name] = $provider->testConnection();
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'provider' => $name
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get provider status
     */
    public function getProviderStatus(): array
    {
        return [
            'default_provider' => $this->defaultProvider,
            'available_providers' => array_keys($this->providers),
            'total_providers' => count($this->providers)
        ];
    }
}