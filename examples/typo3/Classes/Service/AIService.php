<?php
// Classes/Service/AIService.php
/// Source: https://github.com/VolkanSah/AI-API-Security-Best-Practices
declare(strict_types=1);

namespace Vendor\Extension\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AIService
{
    private string $apiKey;
    private Client $client;
    
    public function __construct(ExtensionConfiguration $extConfig)
    {
        // Load API key from extension configuration or environment
        $this->apiKey = $extConfig->get('your_extension', 'openaiApiKey') 
            ?: getenv('OPENAI_API_KEY');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key not configured');
        }
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true
        ]);
    }
    
    /**
     * Generate content using OpenAI API
     * 
     * @param string $prompt User prompt
     * @return string Generated content
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function generateContent(string $prompt): string
    {
        // Input validation
        if (strlen($prompt) > 4000) {
            throw new \InvalidArgumentException('Prompt exceeds maximum length');
        }
        
        if (empty(trim($prompt))) {
            throw new \InvalidArgumentException('Prompt cannot be empty');
        }
        
        // Rate limiting via cache (1 request per minute per backend user)
        $userId = $GLOBALS['BE_USER']->user['uid'] ?? 0;
        $cacheKey = 'ai_rate_' . $userId;
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        
        if ($cache->has($cacheKey)) {
            throw new \RuntimeException('Rate limit exceeded. Please wait before making another request.');
        }
        
        $cache->set($cacheKey, true, [], 60);
        
        try {
            // Make API call
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'Generate SEO-optimized, professional content.'
                        ],
                        [
                            'role' => 'user', 
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.7
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \RuntimeException('Invalid API response');
            }
            
            // Output sanitization
            $content = $data['choices'][0]['message']['content'];
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            
        } catch (GuzzleException $e) {
            // Log error without exposing API key
            GeneralUtility::sysLog(
                'AI API request failed: ' . $e->getMessage(),
                'your_extension',
                GeneralUtility::SYSLOG_SEVERITY_ERROR
            );
            
            throw new \RuntimeException('AI service temporarily unavailable');
        }
    }
}
