<?php
/**
 * Universal AI API Client
 * Source: https://github.com/VolkanSah/AI-API-Security-Best-Practices
 * Supports multiple LLM providers with consistent interface
 */
class UniversalAIClient
{
    private array $config;
    private const TIMEOUT = 30;
    
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
    }
    
    /**
     * Send chat completion request to specified provider
     * 
     * @param string $provider Provider name (openai, claude, gemini)
     * @param string $prompt User prompt
     * @param array $options Additional options
     * @return string AI response
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function chat(string $provider, string $prompt, array $options = []): string
    {
        $handlers = [
            'openai' => fn() => $this->callOpenAI($prompt, $options),
            'claude' => fn() => $this->callClaude($prompt, $options),
            'gemini' => fn() => $this->callGemini($prompt, $options),
        ];
        
        if (!isset($handlers[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: $provider");
        }
        
        return $handlers[$provider]();
    }
    
    /**
     * OpenAI API implementation
     */
    private function callOpenAI(string $prompt, array $options): string
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        $payload = [
            'model' => $options['model'] ?? 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->config['openai_key'],
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("OpenAI request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("OpenAI API error: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException("Invalid OpenAI response format");
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * Anthropic Claude API implementation
     */
    private function callClaude(string $prompt, array $options): string
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        
        $payload = [
            'model' => $options['model'] ?? 'claude-3-5-sonnet-20241022',
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->config['claude_key'],
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("Claude request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Claude API error: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['content'][0]['text'])) {
            throw new \RuntimeException("Invalid Claude response format");
        }
        
        return $data['content'][0]['text'];
    }
    
    /**
     * Google Gemini API implementation
     */
    private function callGemini(string $prompt, array $options): string
    {
        $model = $options['model'] ?? 'gemini-1.5-pro';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->config['gemini_key'];
        
        $ch = curl_init($url);
        
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 1000
            ]
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("Gemini request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Gemini API error: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \RuntimeException("Invalid Gemini response format");
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Validate configuration
     */
    private function validateConfig(array $config): void
    {
        $required = ['openai_key', 'claude_key', 'gemini_key'];
        
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("Missing required config: $key");
            }
        }
    }
}

// Usage example
try {
    $client = new UniversalAIClient([
        'openai_key' => getenv('OPENAI_API_KEY'),
        'claude_key' => getenv('ANTHROPIC_API_KEY'),
        'gemini_key' => getenv('GOOGLE_API_KEY')
    ]);
    
    // OpenAI request
    $response = $client->chat('openai', 'Explain quantum computing in simple terms');
    echo "OpenAI: " . $response . "\n\n";
    
    // Claude request
    $response = $client->chat('claude', 'Write a haiku about security', [
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 100
    ]);
    echo "Claude: " . $response . "\n\n";
    
    // Gemini request
    $response = $client->chat('gemini', 'What are best practices for API security?');
    echo "Gemini: " . $response . "\n";
    
} catch (Exception $e) {
    error_log("AI Client Error: " . $e->getMessage());
    echo "An error occurred. Please try again later.";
}
