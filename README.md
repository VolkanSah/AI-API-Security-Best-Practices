# 🛡️ AI API Security Best Practices

**Universal Security Guide for OpenAI, Anthropic Claude, Google Gemini & Other LLM APIs**

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.x-3776AB?logo=python)](https://python.org)
[![Node.js](https://img.shields.io/badge/Node.js-18.x-339933?logo=node.js)](https://nodejs.org)
[![Security](https://img.shields.io/badge/Security-Critical-red)]()

> **2025 Update**: Expanded for all major AI providers with a focus on WordPress & TYPO3 integrations

---

### Table of Contents

- [Introduction](#-introduction)
- [Critical Security Risks](#-critical-security-risks)
  - [Common Mistakes](#common-mistakes)
  - [OWASP Top 10 for LLMs](#owasp-top-10-for-llms-2025)
- [API Key Management](#-api-key-management)
  - [Never Do This](#️-never)
  - [Environment Variables](#-correct-environment-variables)
  - [Additional Key Security](#-additional-key-security)
- [Framework-Specific Guides](#️-framework-specific-guides)
  - [WordPress Integration](#wordpress-integration)
  - [TYPO3 Extension](#typo3-extension)
- [Code Examples: Multi-Provider Support](#-code-examples-multi-provider-support)
  - [Universal PHP API Client](#universal-php-api-client)
  - [Python Async Implementation](#python-async-implementation)
- [Security Checklist](#️-security-checklist)
- [Provider-Specific Documentation](#-provider-specific-documentation)
- [Additional Security Resources](#-additional-security-resources)
- [Support & Contributions](#-support--contributions)
- [License](#-license)
- [Disclaimer](#️-disclaimer)

---

## Introduction

While the AI hype continues, we are witnessing catastrophic security failures in LLM API integrations. These best practices cover **all major providers** and are specifically optimized for production environments.

### Supported Providers

- **OpenAI** 
- **Anthropic** 
- **Google**
- **Meta**
- **Mistral AI**
- **Other OpenAI-compatible APIs**

---

### 🔥 Critical Security Risks

### Common Mistakes

#### 1. API Keys in Client-Side Code

- ❌ Keys embedded in JavaScript/HTML
- ❌ Keys committed to Git repositories
- ❌ Keys visible in Browser Console/Network tab

#### 2. Missing Input Validation

- ❌ Direct pass-through of user input to API
- ❌ No sanitization or filtering
- ❌ No rate limiting implementation

#### 3. Insecure Output Handling

- ❌ LLM output directly inserted into database/code
- ❌ No XSS protection
- ❌ No SQL injection prevention

### OWASP Top 10 for LLMs (2025)

| Rank | Risk | Description |
|------|------|-------------|
| **LLM01** | **Prompt Injection** | Manipulation of the LLM through crafted inputs |
| **LLM02** | **Improper Output Handling** | Unfiltered LLM outputs leading to XSS/RCE |
| **LLM03** | **Data & Model Poisoning** | Manipulation of training data |
| **LLM04** | **Unbounded Consumption** | DoS through resource exhaustion |
| **LLM05** | **Supply Chain Vulnerabilities** | Compromised dependencies |
| **LLM06** | **Sensitive Information Disclosure** | Data leaks via LLM outputs |
| **LLM07** | **System Prompt Leakage** | Exposure of system prompts & secrets |
| **LLM08** | **Vector & Embedding Weaknesses** | RAG/Embedding security issues |
| **LLM09** | **Misinformation** | Over-reliance on LLM outputs |
| **LLM10** | **Excessive Agency** | Uncontrolled LLM autonomy |

---

### API Key Management

#### ⚠️ NEVER

```php
// ❌ NEVER DO THIS!
$api_key = "sk-proj-abc123...";
```

```javascript
// ❌ NEVER DO THIS!
const API_KEY = "sk-proj-abc123...";
```

```python
# ❌ NEVER DO THIS!
api_key = "sk-proj-abc123..."
```

#### ✅ CORRECT: Environment Variables

#### PHP with `vlucas/phpdotenv`

```bash
composer require vlucas/phpdotenv
```

```env
# .env (DO NOT COMMIT TO GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx
GOOGLE_API_KEY=AIzaSyxxx
```

```php
<?php
// config.php
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$openai_key = $_ENV['OPENAI_API_KEY'];
$claude_key = $_ENV['ANTHROPIC_API_KEY'];
$gemini_key = $_ENV['GOOGLE_API_KEY'];
```

#### Python with `python-dotenv`

```bash
pip install python-dotenv
```

```env
# .env (DO NOT COMMIT TO GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx
```

```python
# app.py
import os
from dotenv import load_dotenv

load_dotenv()

openai_key = os.getenv('OPENAI_API_KEY')
claude_key = os.getenv('ANTHROPIC_API_KEY')
```

#### Node.js with `dotenv`

```bash
npm install dotenv
```

```env
# .env (DO NOT COMMIT TO GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx
```

```javascript
// app.js
require('dotenv').config();

const openaiKey = process.env.OPENAI_API_KEY;
const claudeKey = process.env.ANTHROPIC_API_KEY;
```

### 🔒 Additional Key Security

```gitignore
# .gitignore
.env
.env.*
!.env.example
*.key
secrets/
credentials/
```

**Always create a `.env.example` file:**

```env
# .env.example
OPENAI_API_KEY=your_openai_key_here
ANTHROPIC_API_KEY=your_anthropic_key_here
GOOGLE_API_KEY=your_google_key_here
```

---

### Framework-Specific Guides

### WordPress Integration

```php
<?php
/**
 * Plugin Name: Secure AI Integration
 * Description: Secure AI API Integration for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: secure-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load API key from environment or wp-config.php
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

/**
 * Handle AJAX request for AI generation
 */
add_action('wp_ajax_ai_request', 'handle_ai_request');

function handle_ai_request() {
    // Verify nonce for CSRF protection
    check_ajax_referer('ai_nonce', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Input sanitization
    $user_input = sanitize_textarea_field($_POST['prompt']);
    
    // Validate input length
    if (strlen($user_input) > 4000) {
        wp_send_json_error(['message' => 'Prompt too long']);
        return;
    }
    
    // Rate limiting via transients (1 request per minute per user)
    $user_id = get_current_user_id();
    $rate_key = "ai_rate_$user_id";
    
    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'Rate limit exceeded. Please wait.']);
        return;
    }
    
    set_transient($rate_key, true, 60); // 60 seconds
    
    // Make API call
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $user_input]
            ],
            'max_tokens' => 1000
        ])
    ]);
    
    // Error handling
    if (is_wp_error($response)) {
        error_log('AI API Error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'API request failed']);
        return;
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        error_log('AI API HTTP Error: ' . $http_code);
        wp_send_json_error(['message' => 'API returned error']);
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Output sanitization (allow safe HTML tags)
    $ai_response = wp_kses_post($data['choices'][0]['message']['content']);
    
    wp_send_json_success(['response' => $ai_response]);
}

/**
 * Enqueue scripts with nonce
 */
add_action('wp_enqueue_scripts', 'enqueue_ai_scripts');

function enqueue_ai_scripts() {
    wp_enqueue_script('ai-handler', plugins_url('js/ai-handler.js', __FILE__), ['jquery'], '1.0.0', true);
    
    wp_localize_script('ai-handler', 'aiConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_nonce')
    ]);
}
```

**Frontend JavaScript (ai-handler.js):**

```javascript
jQuery(document).ready(function($) {
    $('#ai-submit').on('click', function(e) {
        e.preventDefault();
        
        const prompt = $('#ai-prompt').val();
        const $button = $(this);
        const $result = $('#ai-result');
        
        $button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: aiConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_request',
                nonce: aiConfig.nonce,
                prompt: prompt
            },
            success: function(response) {
                if (response.success) {
                    $result.html(response.data.response);
                } else {
                    $result.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $result.html('<p class="error">Request failed. Please try again.</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Generate');
            }
        });
    });
});
```

#### TYPO3 Extension

```php
<?php
// Configuration/TCA/Overrides/tt_content.php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:ai_generator',
        'ai_generator',
        'EXT:your_extension/Resources/Public/Icons/ai.svg'
    ],
    'CType',
    'your_extension'
);
```

```php
<?php
// Classes/Service/AIService.php
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
```

```php
<?php
// Classes/Controller/AIController.php
declare(strict_types=1);

namespace Vendor\Extension\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Vendor\Extension\Service\AIService;

class AIController extends ActionController
{
    private AIService $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    public function generateAction(): ResponseInterface
    {
        $prompt = $this->request->getArgument('prompt');
        
        try {
            $content = $this->aiService->generateContent($prompt);
            $this->view->assign('content', $content);
            $this->view->assign('success', true);
        } catch (\Exception $e) {
            $this->view->assign('error', $e->getMessage());
            $this->view->assign('success', false);
        }
        
        return $this->htmlResponse();
    }
}
```

---

### Code Examples: Multi-Provider Support

### Universal PHP API Client

```php
<?php
/**
 * Universal AI API Client
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
```

### Python Async Implementation

```python
"""
Universal AI API Client - Async Implementation
Supports OpenAI, Anthropic Claude, and Google Gemini
"""
import asyncio
import aiohttp
import os
from typing import Dict, Any, Optional
from dataclasses import dataclass

@dataclass
class ProviderConfig:
    """Configuration for AI provider"""
    url: str
    key: str
    header: str
    prefix: str = ''
    version_header: Optional[str] = None

class UniversalAIClient:
    """Universal async client for multiple AI providers"""
    
    def __init__(self):
        self.config = {
            'openai': ProviderConfig(
                url='https://api.openai.com/v1/chat/completions',
                key=os.getenv('OPENAI_API_KEY', ''),
                header='Authorization',
                prefix='Bearer '
            ),
            'claude': ProviderConfig(
                url='https://api.anthropic.com/v1/messages',
                key=os.getenv('ANTHROPIC_API_KEY', ''),
                header='x-api-key',
                prefix='',
                version_header='2023-06-01'
            ),
            'gemini': ProviderConfig(
                url='https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
                key=os.getenv('GOOGLE_API_KEY', ''),
                header='',
                prefix=''
            )
        }
        
        self._validate_config()
    
    def _validate_config(self) -> None:
        """Validate that all API keys are configured"""
        for provider, config in self.config.items():
            if not config.key:
                raise ValueError(f"Missing API key for {provider}")
    
    async def chat(
        self, 
        provider: str, 
        prompt: str, 
        model: Optional[str] = None,
        max_tokens: int = 1000,
        temperature: float = 0.7
    ) -> str:
        """
        Send chat completion request to specified provider
        
        Args:
            provider: Provider name (openai, claude, gemini)
            prompt: User prompt
            model: Optional model override
            max_tokens: Maximum tokens in response
            temperature: Creativity parameter (0-1)
            
        Returns:
            AI response text
            
        Raises:
            ValueError: If provider is unknown
            RuntimeError: If API request fails
        """
        if provider not in self.config:
            raise ValueError(f"Unknown provider: {provider}")
        
        cfg = self.config[provider]
        
        async with aiohttp.ClientSession() as session:
            headers = self._build_headers(provider, cfg)
            payload = self._build_payload(
                provider, prompt, model, max_tokens, temperature
            )
            
            # Add API key to URL for Gemini
            url = cfg.url
            if provider == 'gemini':
                url = f"{url}?key={cfg.key}"
            
            async with session.post(url, headers=headers, json=payload, timeout=30) as resp:
                if resp.status != 200:
                    error_text = await resp.text()
                    raise RuntimeError(
                        f"{provider} API error: HTTP {resp.status} - {error_text}"
                    )
                
                data = await resp.json()
                return self._extract_response(provider, data)
    
    def _build_headers(self, provider: str, cfg: ProviderConfig) -> Dict[str, str]:
        """Build request headers for provider"""
        headers = {'Content-Type': 'application/json'}
        
        if provider == 'openai':
            headers[cfg.header] = f"{cfg.prefix}{cfg.key}"
        elif provider == 'claude':
            headers[cfg.header] = cfg.key
            headers['anthropic-version'] = cfg.version_header
        # Gemini uses API key in URL, not headers
        
        return headers
    
    def _build_payload(
        self, 
        provider: str, 
        prompt: str,
        model: Optional[str],
        max_tokens: int,
        temperature: float
    ) -> Dict[str, Any]:
        """Build request payload for provider"""
        if provider == 'openai':
            return {
                'model': model or 'gpt-4o',
                'messages': [{'role': 'user', 'content': prompt}],
                'max_tokens': max_tokens,
                'temperature': temperature
            }
        
        elif provider == 'claude':
            return {
                'model': model or 'claude-3-5-sonnet-20241022',
                'max_tokens': max_tokens,
                'messages': [{'role': 'user', 'content': prompt}],
                'temperature': temperature
            }
        
        elif provider == 'gemini':
            return {
                'contents': [{'parts': [{'text': prompt}]}],
                'generationConfig': {
                    'temperature': temperature,
                    'maxOutputTokens': max_tokens
                }
            }
        
        raise ValueError(f"Unknown provider: {provider}")
    
    def _extract_response(self, provider: str, data: Dict[str, Any]) -> str:
        """Extract response text from provider response"""
        try:
            if provider == 'openai':
                return data['choices'][0]['message']['content']
            elif provider == 'claude':
                return data['content'][0]['text']
            elif provider == 'gemini':
                return data['candidates'][0]['content']['parts'][0]['text']
        except (KeyError, IndexError) as e:
            raise RuntimeError(f"Invalid {provider} response format: {e}")
        
        raise ValueError(f"Unknown provider: {provider}")

# Usage example
async def main():
    """Example usage of UniversalAIClient"""
    try:
        client = UniversalAIClient()
        
        # OpenAI request
        print("Requesting from OpenAI...")
        response = await client.chat('openai', 'Explain AI security in one sentence')
        print(f"OpenAI: {response}\n")
        
        # Claude request
        print("Requesting from Claude...")
        response = await client.chat(
            'claude', 
            'Write a haiku about cybersecurity',
            max_tokens=100
        )
        print(f"Claude: {response}\n")
        
        # Gemini request
        print("Requesting from Gemini...")
        response = await client.chat('gemini', 'What is prompt injection?')
        print(f"Gemini: {response}\n")
        
    except ValueError as e:
        print(f"Configuration error: {e}")
    except RuntimeError as e:
        print(f"API error: {e}")
    except Exception as e:
        print(f"Unexpected error: {e}")

if __name__ == '__main__':
    asyncio.run(main())
```

### Node.js Implementation

```javascript
/**
 * Universal AI API Client - Node.js Implementation
 * Supports OpenAI, Anthropic Claude, and Google Gemini
 */
const axios = require('axios');
require('dotenv').config();

class UniversalAIClient {
    constructor() {
        this.config = {
            openai: {
                url: 'https://api.openai.com/v1/chat/completions',
                key: process.env.OPENAI_API_KEY,
                headers: {
                    'Authorization': `Bearer ${process.env.OPENAI_API_KEY}`,
                    'Content-Type': 'application/json'
                }
            },
            claude: {
                url: 'https://api.anthropic.com/v1/messages',
                key: process.env.ANTHROPIC_API_KEY,
                headers: {
                    'x-api-key': process.env.ANTHROPIC_API_KEY,
                    'anthropic-version': '2023-06-01',
                    'Content-Type': 'application/json'
                }
            },
            gemini: {
                url: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
                key: process.env.GOOGLE_API_KEY
            }
        };
        
        this.validateConfig();
    }
    
    validateConfig() {
        for (const [provider, config] of Object.entries(this.config)) {
            if (!config.key) {
                throw new Error(`Missing API key for ${provider}`);
            }
        }
    }
    
    async chat(provider, prompt, options = {}) {
        if (!this.config[provider]) {
            throw new Error(`Unknown provider: ${provider}`);
        }
        
        const handlers = {
            openai: () => this.callOpenAI(prompt, options),
            claude: () => this.callClaude(prompt, options),
            gemini: () => this.callGemini(prompt, options)
        };
        
        return await handlers[provider]();
    }
    
    async callOpenAI(prompt, options) {
        const payload = {
            model: options.model || 'gpt-4o',
            messages: [{ role: 'user', content: prompt }],
            max_tokens: options.max_tokens || 1000,
            temperature: options.temperature || 0.7
        };
        
        try {
            const response = await axios.post(
                this.config.openai.url,
                payload,
                { headers: this.config.openai.headers, timeout: 30000 }
            );
            
            return response.data.choices[0].message.content;
        } catch (error) {
            throw new Error(`OpenAI API error: ${error.message}`);
        }
    }
    
    async callClaude(prompt, options) {
        const payload = {
            model: options.model || 'claude-3-5-sonnet-20241022',
            max_tokens: options.max_tokens || 1024,
            messages: [{ role: 'user', content: prompt }]
        };
        
        try {
            const response = await axios.post(
                this.config.claude.url,
                payload,
                { headers: this.config.claude.headers, timeout: 30000 }
            );
            
            return response.data.content[0].text;
        } catch (error) {
            throw new Error(`Claude API error: ${error.message}`);
        }
    }
    
    async callGemini(prompt, options) {
        const payload = {
            contents: [{ parts: [{ text: prompt }] }],
            generationConfig: {
                temperature: options.temperature || 0.7,
                maxOutputTokens: options.max_tokens || 1000
            }
        };
        
        const url = `${this.config.gemini.url}?key=${this.config.gemini.key}`;
        
        try {
            const response = await axios.post(
                url,
                payload,
                { headers: { 'Content-Type': 'application/json' }, timeout: 30000 }
            );
            
            return response.data.candidates[0].content.parts[0].text;
        } catch (error) {
            throw new Error(`Gemini API error: ${error.message}`);
        }
    }
}

// Usage example
(async () => {
    try {
        const client = new UniversalAIClient();
        
        // OpenAI
        console.log('Requesting from OpenAI...');
        const openaiResponse = await client.chat('openai', 'Explain AI security briefly');
        console.log(`OpenAI: ${openaiResponse}\n`);
        
        // Claude
        console.log('Requesting from Claude...');
        const claudeResponse = await client.chat('claude', 'Write a haiku about security');
        console.log(`Claude: ${claudeResponse}\n`);
        
        // Gemini
        console.log('Requesting from Gemini...');
        const geminiResponse = await client.chat('gemini', 'What is XSS?');
        console.log(`Gemini: ${geminiResponse}\n`);
        
    } catch (error) {
        console.error(`Error: ${error.message}`);
    }
})();

module.exports = UniversalAIClient;
```

---

### 🛡️ Security Checklist

### Backend Security

- [ ] **API Keys in Environment Variables** - Never hardcode keys in source code
- [ ] **Input Validation & Sanitization** - Validate all user inputs before processing
- [ ] **Output Escaping (XSS Prevention)** - Sanitize LLM outputs before displaying
- [ ] **Rate Limiting Implemented** - Prevent abuse and control costs
- [ ] **HTTPS-only Communication** - Enforce encrypted connections
- [ ] **Error Handling without Info Leaks** - Don't expose sensitive error details
- [ ] **Logging without Secrets** - Never log API keys or sensitive data
- [ ] **CORS Configured Correctly** - Whitelist only trusted domains
- [ ] **Timeout Settings** - Prevent hanging requests
- [ ] **Request Size Limits** - Prevent oversized payloads

### Frontend Security

- [ ] **No API Keys in Client** - All API calls go through backend
- [ ] **CSRF Protection (Nonces/Tokens)** - Implement anti-CSRF measures
- [ ] **Content Security Policy Headers** - Restrict resource loading
- [ ] **XSS Protection Active** - Escape all user-generated content
- [ ] **Sub-Resource Integrity (SRI)** - Verify external script integrity
- [ ] **No Sensitive Data in LocalStorage** - Use secure session storage
- [ ] **Input Length Limits** - Client-side validation before submission

### Production Security

- [ ] **Secrets Management** - Use Vault, AWS Secrets Manager, or similar
- [ ] **API Key Rotation** - Regularly rotate keys
- [ ] **Monitoring & Alerting** - Track API usage and errors
- [ ] **Budget Limits at Providers** - Set spending caps
- [ ] **Audit Logs Enabled** - Track all API requests
- [ ] **Dependency Scanning** - Regular security audits of packages
- [ ] **WAF/DDoS Protection** - Implement web application firewall
- [ ] **Backup & Disaster Recovery** - Plan for service outages

---

### Provider-Specific Documentation

### OpenAI
- [Production Best Practices](https://platform.openai.com/docs/guides/production-best-practices)
- [Safety Best Practices](https://platform.openai.com/docs/guides/safety-best-practices)
- [Rate Limits](https://platform.openai.com/docs/guides/rate-limits)

### Anthropic Claude
- [API Key Best Practices](https://docs.anthropic.com/claude/docs/api-key-management)
- [Safety Guidelines](https://docs.anthropic.com/claude/docs/safety)
- [Rate Limits](https://docs.anthropic.com/claude/reference/rate-limits)

### Google Gemini
- [Security & Compliance](https://ai.google.dev/gemini-api/docs/security)
- [Safety Settings](https://ai.google.dev/gemini-api/docs/safety-settings)
- [API Quotas](https://ai.google.dev/gemini-api/docs/quota)

---

## 🔗 Additional Security Resources

### OWASP Resources
- [OWASP Top 10 for LLMs](https://owasp.org/www-project-top-10-for-large-language-model-applications/)
- [OWASP AI Security and Privacy Guide](https://owasp.org/www-project-ai-security-and-privacy-guide/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

### Standards & Frameworks
- [NIST AI Risk Management Framework](https://www.nist.gov/itl/ai-risk-management-framework)
- [ISO/IEC 42001 - AI Management System](https://www.iso.org/standard/81230.html)

### Security Tools
- [Gitleaks](https://github.com/gitleaks/gitleaks) - Scan for secrets in git repos
- [TruffleHog](https://github.com/trufflesecurity/trufflehog) - Find leaked credentials
- [Dependabot](https://github.com/dependabot) - Automated dependency updates

---

## 💖 Support & Contributions

Found this useful?

- ⭐ **Star this repo** to show support
- 🐛 **Report Issues** via GitHub Issues
- 💡 **Suggest Improvements** through Pull Requests
- 🔀 **Contribute** - PRs are welcome!
- 📢 **Share** with your developer community

### Contributors

Thank you to all contributors who help improve this guide!

---

## 📝 License

MIT License - see [LICENSE](LICENSE)

Copyright (c) 2025 Volkan Kücükbudak

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

## ⚠️ Disclaimer

These best practices are **recommendations based on current industry standards**. 

**You are responsible for:**
- The security of your implementation
- Compliance with applicable laws and regulations
- Regular security audits and updates
- Monitoring and responding to security incidents

**No guarantees:**
- This guide does not guarantee complete security
- Security is a continuously evolving field
- New vulnerabilities may emerge
- Always stay informed about latest threats

**Professional advice:**
- For production systems handling sensitive data, consult security professionals
- Conduct regular penetration testing
- Implement defense-in-depth strategies
- Have an incident response plan

---

**Stay vigilant. Stay secure. 🔒**

<p align="center">
  <sub>Made with 💀 for the WordPress & TYPO3 Community</sub><br>
  <sub>Maintained by <a href="https://github.com/volkansah">Volkan Kücükbudak</a></sub><br>
  <sub>Last Updated: December 2025</sub>
</p>
