# 🛡️ AI API Security Best Practices

**Universal Security Guide for OpenAI, Anthropic Claude, Google Gemini & Other LLM APIs**

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.x-3776AB?logo=python)](https://python.org)
[![Node.js](https://img.shields.io/badge/Node.js-18.x-339933?logo=node.js)](https://nodejs.org)
[![Security](https://img.shields.io/badge/Security-Critical-red)]()

> **2025 Update**: Erweitert für alle großen AI-Provider mit Fokus auf WordPress & TYPO3 Integrationen

## 📋 Inhaltsverzeichnis

- [Einführung](#einführung)
- [Kritische Sicherheitsrisiken](#kritische-sicherheitsrisiken)
- [API-Key Management](#api-key-management)
- [OWASP Top 10 für LLMs](#owasp-top-10-für-llms)
- [Best Practices nach Provider](#best-practices-nach-provider)
- [Framework-spezifische Guides](#framework-spezifische-guides)
- [Code-Beispiele](#code-beispiele)
- [Weitere Ressourcen](#weitere-ressourcen)

---

## 🚨 Einführung

Während der AI-Hype weitergeht, sehen wir katastrophale Security-Fails bei der Integration von LLM-APIs. Diese Best Practices decken **alle großen Provider** ab und sind speziell für Production-Umgebungen optimiert.

### Unterstützte Providers

- **OpenAI** (GPT-4o, o1, o3)
- **Anthropic** (Claude Sonnet 4.5, Opus 4.1)
- **Google** (Gemini 2.0 Flash, Pro)
- **Meta** (Llama 3.x via API)
- **Mistral AI**
- **Andere OpenAI-kompatible APIs**

---

## 🔥 Kritische Sicherheitsrisiken

### Die häufigsten Fuck-Ups

1. **API-Keys im Client-Side Code** 
   - ❌ Keys in JavaScript/HTML
   - ❌ Keys in Git-Repos
   - ❌ Keys in Browser Console sichtbar

2. **Fehlende Input-Validierung**
   - ❌ Direktes Durchreichen von User-Input
   - ❌ Keine Sanitization
   - ❌ Keine Rate-Limiting

3. **Unsichere Output-Verarbeitung**
   - ❌ LLM-Output direkt in DB/Code
   - ❌ Keine XSS-Protection
   - ❌ Keine SQL-Injection-Prevention

### OWASP Top 10 für LLMs (2025)

| Rang | Risiko | Beschreibung |
|------|--------|--------------|
| LLM01 | **Prompt Injection** | Manipulation des LLM durch crafted Inputs |
| LLM02 | **Improper Output Handling** | Ungefilterte LLM-Outputs führen zu XSS/RCE |
| LLM03 | **Data & Model Poisoning** | Manipulation von Training-Data |
| LLM04 | **Unbounded Consumption** | DoS durch Resource-Exhaustion |
| LLM05 | **Supply Chain Vulnerabilities** | Kompromittierte Dependencies |
| LLM06 | **Sensitive Information Disclosure** | Datenlecks durch LLM-Outputs |
| LLM07 | **System Prompt Leakage** | Offenlegung von System-Prompts & Secrets |
| LLM08 | **Vector & Embedding Weaknesses** | RAG/Embedding Security-Issues |
| LLM09 | **Misinformation** | Übermäßiges Vertrauen in LLM-Outputs |
| LLM10 | **Excessive Agency** | Unkontrollierte LLM-Autonomie |

---

## 🔐 API-Key Management

### ⚠️ NIEMALS

```php
// ❌ NIEMALS SO!
$api_key = "sk-proj-abc123...";

// ❌ NIEMALS SO!
const API_KEY = "sk-proj-abc123...";

// ❌ NIEMALS SO!
api_key = "sk-proj-abc123..."
```

### ✅ RICHTIG: Environment Variables

#### PHP mit `vlucas/phpdotenv`

```bash
composer require vlucas/phpdotenv
```

```php
<?php
// .env (NICHT IN GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx
GOOGLE_API_KEY=AIzaSyxxx

// config.php
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$openai_key = $_ENV['OPENAI_API_KEY'];
$claude_key = $_ENV['ANTHROPIC_API_KEY'];
$gemini_key = $_ENV['GOOGLE_API_KEY'];
```

#### Python mit `python-dotenv`

```bash
pip install python-dotenv
```

```python
# .env (NICHT IN GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx

# app.py
import os
from dotenv import load_dotenv

load_dotenv()

openai_key = os.getenv('OPENAI_API_KEY')
claude_key = os.getenv('ANTHROPIC_API_KEY')
```

#### Node.js mit `dotenv`

```bash
npm install dotenv
```

```javascript
// .env (NICHT IN GIT!)
OPENAI_API_KEY=sk-proj-xxx
ANTHROPIC_API_KEY=sk-ant-xxx

// app.js
require('dotenv').config();

const openaiKey = process.env.OPENAI_API_KEY;
const claudeKey = process.env.ANTHROPIC_API_KEY;
```

### 🔒 Zusätzliche Key-Security

```gitignore
# .gitignore
.env
.env.*
!.env.example
*.key
secrets/
credentials/
```

---

## 🏗️ Framework-spezifische Guides

### WordPress Integration

```php
<?php
/**
 * Plugin Name: Secure AI Integration
 * Description: Sichere AI-API Integration für WordPress
 * Version: 1.0.0
 */

// Secrets in wp-config.php
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

// API-Call Wrapper mit Nonce-Verification
add_action('wp_ajax_ai_request', function() {
    check_ajax_referer('ai_nonce', 'nonce');
    
    // Input Sanitization
    $user_input = sanitize_textarea_field($_POST['prompt']);
    
    // Rate Limiting via Transients
    $user_id = get_current_user_id();
    $rate_key = "ai_rate_$user_id";
    
    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'Rate limit exceeded']);
        return;
    }
    
    set_transient($rate_key, true, 60); // 1 req/min
    
    // API Call
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $user_input]
            ]
        ])
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API request failed']);
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Output Sanitization
    $ai_response = wp_kses_post($data['choices'][0]['message']['content']);
    
    wp_send_json_success(['response' => $ai_response]);
});

// Frontend JS mit Nonce
add_action('wp_enqueue_scripts', function() {
    wp_localize_script('main-js', 'aiConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_nonce')
    ]);
});
```

### TYPO3 Extension

```php
<?php
// Configuration/TCA/Overrides/tt_content.php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'AI Content Generator',
        'ai_generator',
        'EXT:your_extension/Resources/Public/Icons/ai.svg'
    ],
    'CType',
    'your_extension'
);

// Classes/Service/AIService.php
namespace Vendor\Extension\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use GuzzleHttp\Client;

class AIService
{
    private string $apiKey;
    private Client $client;
    
    public function __construct(ExtensionConfiguration $extConfig)
    {
        // Assuming configuration is loaded from settings
        $this->apiKey = $extConfig->get('your_extension', 'openaiApiKey');
        $this->client = new Client();
    }
    
    public function generateContent(string $prompt): string
    {
        // Input Validation
        if (strlen($prompt) > 4000) {
            throw new \InvalidArgumentException('Prompt too long');
        }
        
        // Rate Limiting via Cache
        $cacheKey = 'ai_rate_' . $GLOBALS['BE_USER']->user['uid'];
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        
        if ($cache->has($cacheKey)) {
            throw new \RuntimeException('Rate limit exceeded');
        }
        
        $cache->set($cacheKey, true, [], 60);
        
        // API Call
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'Generate SEO-optimized content.'],
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        
        // Output Sanitization
        return htmlspecialchars($data['choices'][0]['message']['content']);
    }
}
```

---

## 💻 Code-Beispiele: Multi-Provider Support

### Universal PHP API Client

```php
<?php
class UniversalAIClient
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function chat(string $provider, string $prompt): string
    {
        $handlers = [
            'openai' => fn() => $this->callOpenAI($prompt),
            'claude' => fn() => $this->callClaude($prompt),
            'gemini' => fn() => $this->callGemini($prompt),
        ];
        
        if (!isset($handlers[$provider])) {
            throw new \InvalidArgumentException("Unknown provider: $provider");
        }
        
        return $handlers[$provider]();
    }
    
    private function callOpenAI(string $prompt): string
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->config['openai_key'],
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("OpenAI API error: $httpCode");
        }
        
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'];
    }
    
    private function callClaude(string $prompt): string
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->config['claude_key'],
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514', // Hypothetical 2025 model
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Claude API error: $httpCode");
        }
        
        $data = json_decode($response, true);
        return $data['content'][0]['text'];
    }
    
    private function callGemini(string $prompt): string
    {
        // Hypothetical 2.0 endpoint
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->config['gemini_key']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Gemini API error: $httpCode");
        }
        
        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
}

// Verwendung
$client = new UniversalAIClient([
    'openai_key' => getenv('OPENAI_API_KEY'),
    'claude_key' => getenv('ANTHROPIC_API_KEY'),
    'gemini_key' => getenv('GOOGLE_API_KEY')
]);

$response = $client->chat('openai', 'Explain quantum computing');
```

### Python Async Implementation

```python
import asyncio
import aiohttp
import os
from typing import Dict, Any

class UniversalAIClient:
    def __init__(self):
        self.config = {
            'openai': {
                'url': 'https://api.openai.com/v1/chat/completions',
                'key': os.getenv('OPENAI_API_KEY'),
                'header': 'Authorization',
                'prefix': 'Bearer '
            },
            'claude': {
                'url': 'https://api.anthropic.com/v1/messages',
                'key': os.getenv('ANTHROPIC_API_KEY'),
                'header': 'x-api-key',
                'prefix': ''
            }
        }
    
    async def chat(self, provider: str, prompt: str) -> str:
        if provider not in self.config:
            raise ValueError(f"Unknown provider: {provider}")
        
        cfg = self.config[provider]
        
        async with aiohttp.ClientSession() as session:
            headers = {
                cfg['header']: f"{cfg['prefix']}{cfg['key']}",
                'Content-Type': 'application/json'
            }
            
            if provider == 'claude':
                headers['anthropic-version'] = '2023-06-01'
            
            payload = self._build_payload(provider, prompt)
            
            async with session.post(cfg['url'], headers=headers, json=payload) as resp:
                if resp.status != 200:
                    raise RuntimeError(f"{provider} API error: {resp.status}")
                
                data = await resp.json()
                return self._extract_response(provider, data)
    
    def _build_payload(self, provider: str, prompt: str) -> Dict[str, Any]:
        if provider == 'openai':
            return {
                'model': 'gpt-4o',
                'messages': [{'role': 'user', 'content': prompt}]
            }
        elif provider == 'claude':
            return {
                'model': 'claude-sonnet-4-20250514', # Hypothetical model
                'max_tokens': 1024,
                'messages': [{'role': 'user', 'content': prompt}]
            }
    
    def _extract_response(self, provider: str, data: Dict[str, Any]) -> str:
        if provider == 'openai':
            return data['choices'][0]['message']['content']
        elif provider == 'claude':
            return data['content'][0]['text']

# Verwendung
async def main():
    client = UniversalAIClient()
    response = await client.chat('openai', 'Explain AI security')
    print(response)

asyncio.run(main())
```

---

## 🛡️ Security Checklist

### Backend

- [ ] API-Keys in Environment Variables
- [ ] Input-Validierung & Sanitization
- [ ] Output-Escaping (XSS-Prevention)
- [ ] Rate-Limiting implementiert
- [ ] HTTPS-only Communication
- [ ] Error-Handling ohne Info-Leaks
- [ ] Logging ohne Secrets
- [ ] CORS richtig konfiguriert

### Frontend

- [ ] Keine API-Keys im Client
- [ ] CSRF-Protection (Nonces/Tokens)
- [ ] Content-Security-Policy Headers
- [ ] XSS-Protection aktiv
- [ ] Sub-Resource Integrity (SRI)

### Production

- [ ] Secrets Management (Vault/AWS Secrets Manager)
- [ ] API-Key Rotation
- [ ] Monitoring & Alerting
- [ ] Budget-Limits bei Providern
- [ ] Audit-Logs aktiviert

---

## 📚 Provider-spezifische Docs

### OpenAI
- [Production Best Practices](https://platform.openai.com/docs/guides/production-best-practices)
- [Safety Best Practices](https://platform.openai.com/docs/guides/safety-best-practices)
- [Rate Limits](https://platform.openai.com/docs/guides/rate-limits)

### Anthropic Claude
- [API Key Best Practices](https://support.claude.com/en/articles/9767949-api-key-best-practices)
- [Safety Guidelines](https://docs.anthropic.com/en/docs/about-claude/use-case-guidelines)

### Google Gemini
- [Security & Compliance](https://cloud.google.com/gemini/docs/codeassist/security-privacy-compliance)
- [Safety Settings](https://ai.google.dev/gemini-api/docs/safety-settings)

---

### 🔗 Additional Security Guides

- [OWASP Top 10 for LLMs](https://owasp.org/www-project-top-10-for-large-language-model-applications/)
- [AI Security Best Practices (NIST)](https://www.nist.gov/itl/ai-risk-management-framework)

---

### 💖 Support & Contributions

Found this useful?

- ⭐ Star this repo
- 🐛 Report Issues
- 💡 Suggest Improvements
- 🔀 Pull Requests welcome

---

## 📝 License

MIT License - see [LICENSE](LICENSE)

---

## ⚠️ Disclaimer

These best practices are recommendations. **You** are responsible for the security of your implementation. No guarantee of completeness. Security is a moving target.

**Stay paranoid. Stay secure. 🔒**

---

<p align="center">
  <sub>Made with 💀 for the WordPress & TYPO3 Community</sub><br>
  <sub>Gewartet von <a href="https://github.com/volkansah">Volkan Kücükbudak</a></sub>
</p>
