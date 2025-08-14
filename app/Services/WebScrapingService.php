<?php

// app/Services/WebScrapingService.php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class WebScrapingService
{
    private $client;
    private $timeout;
    private $maxContentLength;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => config('ai.timeout_seconds', 30),
            'verify' => false, // Disable SSL verification untuk development
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ],
            // Curl options untuk Windows
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
            ]
        ]);
        
        $this->timeout = config('ai.timeout_seconds', 30);
        $this->maxContentLength = config('ai.max_content_length', 50000);
    }

    /**
     * Validate if URL is accessible
     */
    public function validateUrl(string $url): array
    {
        try {
            // Basic URL format validation
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return [
                    'valid' => false,
                    'accessible' => false,
                    'error' => 'Invalid URL format'
                ];
            }

            // Try GET instead of HEAD for better compatibility
            $response = $this->client->get($url, [
                'timeout' => 10, // Shorter timeout for validation
                'allow_redirects' => true
            ]);
            
            $statusCode = $response->getStatusCode();
            
            return [
                'valid' => true,
                'accessible' => $statusCode >= 200 && $statusCode < 400,
                'status_code' => $statusCode,
                'content_type' => $response->getHeader('Content-Type')[0] ?? 'unknown'
            ];
            
        } catch (RequestException $e) {
            // More detailed error handling
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            
            return [
                'valid' => true,
                'accessible' => false,
                'error' => $this->getHumanReadableError($e),
                'status_code' => $statusCode
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'accessible' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
                'status_code' => 0
            ];
        }
    }

    private function getHumanReadableError(\Exception $e): string
    {
        $message = $e->getMessage();
        
        if (strpos($message, 'SSL') !== false) {
            return 'SSL certificate error - site may be using invalid certificate';
        } elseif (strpos($message, 'timeout') !== false) {
            return 'Connection timeout - site may be slow or unreachable';
        } elseif (strpos($message, '404') !== false) {
            return 'Page not found (404)';
        } elseif (strpos($message, '403') !== false) {
            return 'Access forbidden (403) - site may block automated requests';
        } elseif (strpos($message, '500') !== false) {
            return 'Server error (500) - site may be experiencing issues';
        } else {
            return 'Connection failed: ' . $message;
        }
    }

    /**
     * Extract content from URL
     */
    public function extractContent(string $url): array
    {
        try {
            $startTime = microtime(true);
            
            // Validate URL first
            $validation = $this->validateUrl($url);
            if (!$validation['accessible']) {
                return [
                    'success' => false,
                    'error' => $validation['error'] ?? 'URL not accessible',
                    'url' => $url
                ];
            }

            // Fetch HTML content
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            
            // Extract metadata and content
            $extracted = $this->parseHtmlContent($html, $url);
            
            $extracted['success'] = true;
            $extracted['url'] = $url;
            $extracted['extraction_time'] = round((microtime(true) - $startTime) * 1000); // in ms
            $extracted['raw_html_size'] = strlen($html);
            
            Log::info('Content extracted successfully', [
                'url' => $url,
                'title' => $extracted['title'] ?? 'N/A',
                'word_count' => $extracted['word_count'] ?? 0,
                'extraction_time' => $extracted['extraction_time']
            ]);
            
            return $extracted;
            
        } catch (RequestException $e) {
            Log::error('Web scraping failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to fetch content: ' . $e->getMessage(),
                'url' => $url
            ];
        } catch (\Exception $e) {
            Log::error('Content parsing failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to parse content: ' . $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Parse HTML content and extract relevant information
     */
    private function parseHtmlContent(string $html, string $url): array
    {
        // Create DOMDocument for parsing
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        $result = [
            'title' => $this->extractTitle($xpath),
            'content' => $this->extractMainContent($xpath),
            'meta_description' => $this->extractMetaDescription($xpath),
            'author' => $this->extractAuthor($xpath),
            'publish_date' => $this->extractPublishDate($xpath),
            'domain' => parse_url($url, PHP_URL_HOST),
            'language' => $this->detectLanguage($html)
        ];
        
        // Clean and process content
        $result['cleaned_content'] = $this->cleanContent($result['content']);
        $result['word_count'] = str_word_count($result['cleaned_content']);
        $result['excerpt'] = $this->generateExcerpt($result['cleaned_content']);
        
        // Truncate if too long
        if (strlen($result['cleaned_content']) > $this->maxContentLength) {
            $result['cleaned_content'] = substr($result['cleaned_content'], 0, $this->maxContentLength) . '...';
            $result['truncated'] = true;
        }
        
        return $result;
    }

    /**
     * Extract title from various selectors
     */
    private function extractTitle(DOMXPath $xpath): ?string
    {
        $selectors = [
            '//h1[contains(@class, "title")]',
            '//h1[contains(@class, "headline")]',
            '//h1[contains(@class, "entry-title")]',
            '//title',
            '//h1[1]',
            '//meta[@property="og:title"]/@content',
            '//meta[@name="twitter:title"]/@content'
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $title = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                if (!empty($title)) {
                    return $this->cleanText($title);
                }
            }
        }
        
        return null;
    }

    /**
     * Extract main content from article
     */
    private function extractMainContent(DOMXPath $xpath): string
    {
        $selectors = [
            '//article',
            '//div[contains(@class, "content")]',
            '//div[contains(@class, "article")]',
            '//div[contains(@class, "post-content")]',
            '//div[contains(@class, "entry-content")]',
            '//div[contains(@class, "article-body")]',
            '//main',
            '//section[contains(@class, "content")]'
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $content = $this->extractTextFromNode($nodes->item(0));
                if (strlen($content) > 200) { // Minimum content length
                    return $content;
                }
            }
        }
        
        // Fallback: get all paragraph text
        $paragraphs = $xpath->query('//p');
        $content = '';
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if (strlen($text) > 50) { // Filter out short paragraphs
                $content .= $text . "\n\n";
            }
        }
        
        return $content;
    }

    /**
     * Extract meta description
     */
    private function extractMetaDescription(DOMXPath $xpath): ?string
    {
        $selectors = [
            '//meta[@name="description"]/@content',
            '//meta[@property="og:description"]/@content',
            '//meta[@name="twitter:description"]/@content'
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $description = trim($nodes->item(0)->nodeValue);
                if (!empty($description)) {
                    return $this->cleanText($description);
                }
            }
        }
        
        return null;
    }

    /**
     * Extract author information
     */
    private function extractAuthor(DOMXPath $xpath): ?string
    {
        $selectors = [
            '//meta[@name="author"]/@content',
            '//span[contains(@class, "author")]',
            '//div[contains(@class, "author")]',
            '//a[contains(@class, "author")]',
            '//meta[@property="article:author"]/@content'
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $author = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                if (!empty($author)) {
                    return $this->cleanText($author);
                }
            }
        }
        
        return null;
    }

    /**
     * Extract publish date
     */
    private function extractPublishDate(DOMXPath $xpath): ?string
    {
        $selectors = [
            '//time/@datetime',
            '//meta[@property="article:published_time"]/@content',
            '//meta[@name="publish_date"]/@content',
            '//span[contains(@class, "date")]',
            '//div[contains(@class, "date")]'
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $date = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue);
                if (!empty($date)) {
                    return $date;
                }
            }
        }
        
        return null;
    }

    /**
     * Extract text content from DOM node
     */
    private function extractTextFromNode(\DOMNode $node): string
    {
        $text = '';
        
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                // Skip script, style, and navigation elements
                if (!in_array(strtolower($child->nodeName), ['script', 'style', 'nav', 'header', 'footer', 'aside'])) {
                    $text .= $this->extractTextFromNode($child);
                }
            }
        }
        
        return $text;
    }

    /**
     * Clean extracted text
     */
    private function cleanText(string $text): string
    {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Remove common unwanted phrases
        $unwanted = [
            'Klik untuk berbagi',
            'Share this:',
            'Bagikan:',
            'ADVERTISEMENT',
            'Advertisement'
        ];
        
        foreach ($unwanted as $phrase) {
            $text = str_ireplace($phrase, '', $text);
        }
        
        return trim($text);
    }

    /**
     * Clean main content
     */
    private function cleanContent(string $content): string
    {
        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove common unwanted patterns
        $patterns = [
            '/\b(ADVERTISEMENT|Advertisement|Iklan)\b/i',
            '/\b(Klik untuk berbagi|Share this|Bagikan)\b/i',
            '/\b(Baca juga|Lihat juga|Artikel terkait)\b/i',
            '/\b(Loading|Please wait)\b/i',
            '/\[(.*?)\]/', // Remove content in square brackets
            '/\b(Copyright|©|\(c\))\b.*$/im', // Remove copyright notices
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Clean up sentences
        $sentences = explode('.', $content);
        $cleanSentences = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            // Keep sentences that are at least 10 characters and contain Indonesian words
            if (strlen($sentence) > 10 && $this->containsIndonesianWords($sentence)) {
                $cleanSentences[] = $sentence;
            }
        }
        
        return implode('. ', $cleanSentences);
    }

    /**
     * Check if text contains Indonesian words
     */
    private function containsIndonesianWords(string $text): bool
    {
        $indonesianWords = [
            'dan', 'yang', 'di', 'dengan', 'untuk', 'pada', 'dari', 'ke', 'dalam', 'oleh',
            'akan', 'adalah', 'sebagai', 'ini', 'itu', 'juga', 'dapat', 'telah', 'sudah',
            'pemerintah', 'indonesia', 'jakarta', 'presiden', 'menteri', 'negara'
        ];
        
        $text = strtolower($text);
        foreach ($indonesianWords as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detect content language
     */
    private function detectLanguage(string $html): string
    {
        // Check HTML lang attribute
        if (preg_match('/<html[^>]*lang=["\'](.*?)["\']/', $html, $matches)) {
            return $matches[1];
        }
        
        // Simple Indonesian language detection
        $indonesianWords = ['dan', 'yang', 'dengan', 'untuk', 'dari', 'pada', 'di', 'ke', 'dalam', 'oleh'];
        $content = strtolower(strip_tags($html));
        
        $indonesianCount = 0;
        foreach ($indonesianWords as $word) {
            $indonesianCount += substr_count($content, ' ' . $word . ' ');
        }
        
        return $indonesianCount > 5 ? 'id' : 'unknown';
    }

    /**
     * Generate excerpt from content
     */
    private function generateExcerpt(string $content, int $maxLength = 200): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        // Try to cut at sentence boundary
        $excerpt = substr($content, 0, $maxLength);
        $lastPeriod = strrpos($excerpt, '.');
        
        if ($lastPeriod !== false && $lastPeriod > ($maxLength * 0.7)) {
            return substr($excerpt, 0, $lastPeriod + 1);
        }
        
        // Cut at word boundary
        $lastSpace = strrpos($excerpt, ' ');
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }

    /**
     * Extract multiple URLs in batch
     */
    public function extractMultipleUrls(array $urls): array
    {
        $results = [];
        $startTime = microtime(true);
        
        foreach ($urls as $index => $url) {
            //Log::info("Extracting content from URL {$index + 1}/{count($urls)}: {$url}");
            
            $result = $this->extractContent($url);
            $result['index'] = $index;
            $results[] = $result;
            
            // Small delay to be respectful to servers
            if ($index < count($urls) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        $totalTime = round((microtime(true) - $startTime) * 1000);
        
        Log::info('Batch extraction completed', [
            'total_urls' => count($urls),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            'total_time' => $totalTime . 'ms'
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_urls' => count($urls),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success'])),
                'total_time' => $totalTime,
                'average_time' => count($urls) > 0 ? round($totalTime / count($urls)) : 0
            ]
        ];
    }

    /**
     * Get preview information for URL (lightweight)
     */
    public function getUrlPreview(string $url): array
    {
        try {
            // Quick validation
            $validation = $this->validateUrl($url);
            if (!$validation['accessible']) {
                return [
                    'success' => false,
                    'error' => $validation['error'] ?? 'URL not accessible',
                    'url' => $url
                ];
            }

            // Fetch only first part of content for preview
            $response = $this->client->get($url, [
                'headers' => [
                    'Range' => 'bytes=0-8192' // Only get first 8KB for preview
                ]
            ]);
            
            $html = $response->getBody()->getContents();
            
            // Quick parse for basic info
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            
            $xpath = new DOMXPath($dom);
            
            $title = $this->extractTitle($xpath);
            $description = $this->extractMetaDescription($xpath);
            
            return [
                'success' => true,
                'url' => $url,
                'title' => $title ?: 'Tidak dapat mengambil judul',
                'excerpt' => $description ?: 'Preview tidak tersedia',
                'domain' => parse_url($url, PHP_URL_HOST),
                'word_count' => $description ? str_word_count($description) : 0
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Check if content is suitable for AI analysis
     */
    public function isContentSuitable(array $extractedContent): array
    {
        $issues = [];
        $score = 100;
        
        // Check content length
        $wordCount = $extractedContent['word_count'] ?? 0;
        if ($wordCount < 100) {
            $issues[] = 'Konten terlalu pendek (< 100 kata)';
            $score -= 30;
        } elseif ($wordCount < 200) {
            $issues[] = 'Konten agak pendek (< 200 kata)';
            $score -= 15;
        }
        
        // Check if title exists
        if (empty($extractedContent['title'])) {
            $issues[] = 'Judul tidak ditemukan';
            $score -= 20;
        }
        
        // Check language
        if (($extractedContent['language'] ?? 'unknown') !== 'id') {
            $issues[] = 'Bahasa mungkin bukan Indonesia';
            $score -= 25;
        }
        
        // Check if content seems to be an article
        $content = strtolower($extractedContent['cleaned_content'] ?? '');
        $newsKeywords = ['berita', 'laporan', 'mengatakan', 'menyatakan', 'dilaporkan', 'mengumumkan'];
        $hasNewsKeywords = false;
        
        foreach ($newsKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $hasNewsKeywords = true;
                break;
            }
        }
        
        if (!$hasNewsKeywords) {
            $issues[] = 'Konten mungkin bukan artikel berita';
            $score -= 20;
        }
        
        $suitability = $score >= 70 ? 'excellent' : ($score >= 50 ? 'good' : ($score >= 30 ? 'fair' : 'poor'));
        
        return [
            'suitable' => $score >= 30,
            'score' => max(0, $score),
            'suitability' => $suitability,
            'issues' => $issues,
            'recommendations' => $this->getSuitabilityRecommendations($issues)
        ];
    }

    /**
     * Get recommendations based on suitability issues
     */
    private function getSuitabilityRecommendations(array $issues): array
    {
        $recommendations = [];
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'pendek') !== false) {
                $recommendations[] = 'Coba gunakan URL artikel yang lebih panjang dan detail';
            } elseif (strpos($issue, 'judul') !== false) {
                $recommendations[] = 'Pastikan URL mengarah ke halaman artikel, bukan homepage';
            } elseif (strpos($issue, 'bahasa') !== false) {
                $recommendations[] = 'Gunakan artikel berbahasa Indonesia untuk hasil optimal';
            } elseif (strpos($issue, 'berita') !== false) {
                $recommendations[] = 'Pastikan URL mengarah ke artikel berita, bukan halaman lain';
            }
        }
        
        return array_unique($recommendations);
    }
}