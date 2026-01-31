/**
 * Universal AI API Client - Node.js Implementation
 * Supports OpenAI, Anthropic Claude, and Google Gemini
 * github.com/VolkanSah/AI-API-Security-Best-Practices
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
