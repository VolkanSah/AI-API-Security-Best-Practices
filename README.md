# GPT Security Best Practices

[Source of this GPT Security Best Practices](https://github.com/VolkanSah/GPT-Security-Best-Practices/) look for updates before you use these tips if it is forked!

As an AI language model enthusiast, I often find myself alarmed by the way sensitive data is carelessly handled in various applications. While the excitement around GPT is understandable, the improper handling of sensitive information poses significant challenges for administrators and security professionals managing servers rented by clients. This document aims to provide best practices for securely implementing GPT in web applications to prevent security vulnerabilities and protect sensitive data.

## Table of Contents
- [Introduction](#Introduction)
- [Security Risks and Vulnerabilities](#Security-Risks-and-Vulnerabilities)
- [Using Environment Variables in PHP](#Using-Environment-Variables-in-PHP)
- [Best Practices for Implementing GPT](#Best-Practices-for-Implementing-GPT)
- [Choosing the Appropriate API Endpoint](#Choosing-the-Appropriate-API-Endpoint)
- [Code Example](#Code-Example)
- [Credits](#Credits)


## Introduction
The purpose of this document is to outline the security risks and vulnerabilities that may arise when implementing GPT in web applications and to provide best practices for mitigating these risks.

## Security Risks and Vulnerabilities
- Storing sensitive data in JavaScript
- Exposing API keys and request URLs in the browser console

1. Use server-side languages like PHP for handling sensitive data and functions
Instead of using JavaScript to handle sensitive data, use server-side languages like PHP. This will keep the data secure and away from the client-side, where it could be accessed through the browser console.

```php
<?php
$api_key = "your_api_key_here";
$request_url = "https://api.openai.com/v1/engines/davinci-codex/completions";
```

2. Use AJAX for communication between the front-end and back-end
With AJAX, you can asynchronously send data to and retrieve data from the server without exposing sensitive information in the browser console.

Front-end (JavaScript with jQuery)
```javascript
function sendRequest(inputText) {
  $.ajax({
    url: 'backend.php',
    type: 'POST',
    data: { input: inputText },
    success: function(response) {
      // Process and display the response from GPT
    },
    error: function() {
      // Handle error cases
    }
  });
}
```

Back-end (PHP)
```php
<?php
$api_key = "your_api_key_here";
$request_url = "https://api.openai.com/v1/engines/davinci-codex/completions";

$inputText = $_POST['input'];

// Process the input and send a request to GPT

// Return the response to the front-end
```

3. Secure your API key
Store your API key in a secure location, such as an environment variable, and not in the source code. This will prevent accidental exposure of the key in public repositories.

## Using Environment Variables in PHP
You can store your API key as an environment variable by adding it to your server's environment configuration or by using a .env file (with the help of a library like PHP dotenv).

Create a .env file in your project's root directory:

```
GPT_API_KEY=your_api_key_here
```

Install the vlucas/phpdotenv package using Composer:

```
composer require vlucas/phpdotenv
```

Load the environment variables from the .env file in your PHP script:

```php
<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

Access the API key from the environment variables:

```php
<?php
$api_key = getenv('GPT_API_KEY');
$request_url = "https://api.openai.com/v1/engines/davinci-codex/completions";
```

By using environment variables, your API key will be kept secure and separated from your source code. Remember to add the .env file to your .gitignore file to prevent it from being accidentally committed to your public repository.

## Best Practices for Implementing GPT
4. Validate and sanitize user inputs
Ensure that user inputs are validated and sanitized before processing them. This will prevent potential security vulnerabilities, such as XSS attacks.

Back-end (PHP)
```php
<?php
// Sanitize user input before processing
$inputText = filter_input(INPUT_POST, 'input', FILTER_SANITIZE_STRING);
```

5. Use HTTPS for secure communication
When deploying your web application, ensure that you use HTTPS to encrypt the communication between the client and the server, preventing man-in-the-middle attacks.

6. Limit API request rate
To prevent abuse of your GPT API key and control costs, implement rate-limiting on your server-side code. This will limit the number of requests made to the GPT API within a specified time frame.

Back-end (PHP)
```php
<?php
// Implement rate-limiting logic here
// ...

// Only proceed with the request if the rate limit is not exceeded
if ($is_rate_limit_ok) {
  // Send a request to GPT API
}
```

7. Use Content Security Policy (CSP)
Implement CSP headers to prevent XSS attacks and other vulnerabilities by controlling the resources the user agent is allowed to load for a given page.

8. Use Security Headers
Implement security headers such as X-Frame-Options, X-Content-Type-Options, and others to protect your application from common security vulnerabilities.

## Choosing the Appropriate API Endpoint
When implementing GPT, it's crucial to select the appropriate API endpoint based on your specific use case. OpenAI provides various endpoints for different purposes. Here are the current OpenAI endpoints:

ENDPOINT | MODEL NAME
-- | --
/v1/chat/completions | gpt-3.5-turbo, gpt-3.5-turbo-0301, gpt-3.5-turbo-0613, gpt-3.5-turbo-16k, gpt-3.5-turbo-16k-0613, gpt-4, gpt-4-0314, gpt-4-0613, gpt-4-32k, gpt-4-32k-0314, gpt-4o (variants: gpt-4o-2024-05-13, gpt-4o-2024-08-06, gpt-4o-2024-11-20), gpt-4o-audio-preview (e.g. gpt-4o-audio-preview-2024-10-01, gpt-4o-audio-preview-2024-12-17), gpt-4o-realtime-preview (e.g. gpt-4o-realtime-preview-2024-10-01, gpt-4o-realtime-preview-2024-12-17), gpt-4o-mini (gpt-4o-mini-2024-07-18), gpt-4o-mini-audio-preview (gpt-4o-mini-audio-preview-2024-12-17), gpt-4o-mini-realtime-preview (gpt-4o-mini-realtime-preview-2024-12-17), o1 (o1-2024-12-17), o1-mini (o1-mini-2024-09-12), o1-preview (o1-preview-2024-09-12), o3-mini (o3-mini-2025-01-31)
/v1/completions | ada, ada-code-search-code, ada-code-search-text, ada-search-document, ada-search-query, ada-similarity, babbage, babbage-code-search-code, babbage-code-search-text, babbage-search-document, babbage-search-query, babbage-similarity, code-davinci-edit-001, code-search-ada-code-001, code-search-ada-text-001, code-search-babbage-code-001, code-search-babbage-text-001, curie, curie-instruct-beta, curie-search-document, curie-search-query, curie-similarity, davinci, davinci-instruct-beta, davinci-search-document, davinci-search-query, davinci-similarity, text-ada-001, text-babbage-001, text-curie-001, text-davinci-001, text-davinci-002, text-davinci-003, text-davinci-edit-001, text-embedding-ada-002, text-search-ada-doc-001, text-search-ada-query-001, text-search-babbage-doc-001, text-search-babbage-query-001, text-search-curie-doc-001, text-search-curie-query-001, text-search-davinci-doc-001, text-search-davinci-query-001, text-similarity-ada-001, text-similarity-babbage-001, text-similarity-curie-001, text-similarity-davinci-001
/v1/edits | text-davinci-edit-001, code-davinci-edit-001
/v1/audio/transcriptions | whisper-1
/v1/audio/translations | whisper-1
/v1/fine-tunes | davinci, curie, babbage, ada
/v1/embeddings | text-embedding-ada-002, text-search-ada-doc-001, text-search-ada-query-001, text-search-babbage-doc-001, text-search-babbage-query-001, text-search-curie-doc-001, text-search-curie-query-001, text-search-davinci-doc-001, text-search-davinci-query-001
/v1/moderations | text-moderation-latest, text-moderation-stable

### Cost
Different endpoints have varying costs per token or per request. Choose an endpoint that fits within your budget.

### Performance
Some endpoints offer faster response times, while others are more suited for heavy-duty tasks. Consider the performance needs of your application when selecting an endpoint.

### Specific Use Case
Each endpoint has its own strengths and weaknesses. Evaluate the unique requirements of your application and choose the endpoint that best meets those needs.

## Code Example
An example of how to use the /v1/chat/completions endpoint with the gpt-3.5-turbo model in a web application.

Update the $request_url in your back-end PHP script:

```php
<?php
$api_key = getenv('GPT_API_KEY');
$request_url = "https://api.openai.com/v1/chat/completions";
```

Create a function to send a request to the GPT API:

```php
<?php
function send_chat_completion_request($api_key, $request_url, $messages) {
  $ch = curl_init();

  $data = array(
    'model' => 'gpt-3.5-turbo',
    'messages' => $messages
  );

  curl_setopt($ch, CURLOPT_URL, $request_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
  ));

  $response = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return array('response' => $response, 'httpcode' => $httpcode);
}
```

Call the send_chat_completion_request() function and process the GPT API response:

```php
<?php
$inputText = filter_input(INPUT_POST, 'input', FILTER_SANITIZE_STRING);
$messages = array(
  array('role' => 'system', 'content' => 'You are talking to a helpful assistant.'),
  array('role' => 'user', 'content' => $inputText)
);

$result = send_chat_completion_request($api_key, $request_url, $messages);

if ($result['httpcode'] == 200) {
  $json_response = json_decode($result['response'], true);
  $assistant_reply = $json_response['choices'][0]['message']['content'];

  // Return the response to the front-end
  echo $assistant_reply;
} else {
  // Handle error cases
  echo "Error: " . $result['response'];
}
```

This example shows how to use the /v1/chat/completions endpoint with the gpt-3.5-turbo model. The send_chat_completion_request() function sends a request to the API with the input text and receives the generated response. The assistant's reply is then returned to the front-end.

## Other Note
Additional resources and notes that might be helpful for understanding and implementing the best practices mentioned in this document.

- [Secure Implementation of Artificial Intelligence (AI)](https://github.com/VolkanSah/Implementing-AI-Systems-Whitepaper/tree/main)

### Credits
S. Volkan Kücükbudak


## Support

Found this useful?

- ⭐ Star this repository
- 🐛 Report issues
- 💡 Suggest improvements
- 💖 [Sponsor development](https://github.com/sponsors/volkansah)

---

**Stay secure. Stay paranoid. 🔒**


### Other Stuff
##### Security Guides:

- [Security Headers — Complete Implementation Guide](https://github.com/VolkanSah/Security-Headers)
- [Securing FastAPI Applications](https://github.com/VolkanSah/Securing-FastAPI-Applications)
- [ModSecurity Webserver Protection Guide](https://github.com/VolkanSah/ModSecurity-Webserver-Protection-Guide)
- [GPT Security Best Practices](https://github.com/VolkanSah/GPT-Security-Best-Practices)
- [WPScan – WordPress Security Scanner Guide](https://github.com/VolkanSah/WordPress-Security-Scanner-advanced-use)























