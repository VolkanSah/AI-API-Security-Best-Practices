"""
Universal AI API Client - Async Implementation
Supports OpenAI, Anthropic Claude, and Google Gemini
Source: github.com/VolkanSah/AI-API-Security-Best-Practices
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
