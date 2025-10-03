<?php

namespace TN\TN_Core\Test;

/**
 * Test response wrapper for functional testing
 * 
 * Wraps the framework's HTTP response to provide testing utilities
 * similar to Symfony's and Laravel's test response objects.
 */
class TestResponse
{
    private int $statusCode;
    private string $content;
    private array $headers;

    /**
     * @param int $statusCode HTTP status code
     * @param string $content Response body content
     * @param array $headers Response headers
     */
    public function __construct(int $statusCode, string $content, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->content = $content;
        $this->headers = $headers;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response content as string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get response headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Decode JSON response content
     * 
     * @return array|null Decoded JSON data or null if invalid JSON
     */
    public function getJson(): ?array
    {
        $content = $this->content;

        // Handle case where PHP warnings/errors are mixed with JSON response
        // Look for JSON starting with { or [ and extract it
        if (preg_match('/(\{.*\}|\[.*\])$/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Check if response is successful (2xx status code)
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a client error (4xx status code)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx status code)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Assert that response has expected status code
     * 
     * @param int $expectedStatusCode Expected HTTP status code
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertStatus(int $expectedStatusCode): self
    {
        if ($this->statusCode !== $expectedStatusCode) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected status code {$expectedStatusCode}, got {$this->statusCode}. Response: {$this->content}"
            );
        }
        return $this;
    }

    /**
     * Assert that response is successful (2xx)
     * 
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertSuccessful(): self
    {
        if (!$this->isSuccessful()) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected successful status code (2xx), got {$this->statusCode}. Response: {$this->content}"
            );
        }
        return $this;
    }

    /**
     * Assert that response contains JSON data
     * 
     * @param array $expectedData Expected JSON structure (partial match)
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertJson(array $expectedData): self
    {
        $actualData = $this->getJson();

        if ($actualData === null) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Response is not valid JSON. Content: {$this->content}"
            );
        }

        $this->assertArrayContains($expectedData, $actualData);
        return $this;
    }

    /**
     * Assert that JSON response has specific structure
     * 
     * @param array $expectedStructure Expected keys/structure
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertJsonStructure(array $expectedStructure): self
    {
        $actualData = $this->getJson();

        if ($actualData === null) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Response is not valid JSON. Content: {$this->content}"
            );
        }

        $this->validateJsonStructure($expectedStructure, $actualData);
        return $this;
    }

    /**
     * Recursively check if actual array contains expected data
     */
    private function assertArrayContains(array $expected, array $actual, string $path = ''): void
    {
        foreach ($expected as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : $key;

            if (!array_key_exists($key, $actual)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Expected key '{$currentPath}' not found in response"
                );
            }

            if (is_array($value)) {
                if (!is_array($actual[$key])) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        "Expected '{$currentPath}' to be array, got " . gettype($actual[$key])
                    );
                }
                $this->assertArrayContains($value, $actual[$key], $currentPath);
            } else {
                if ($actual[$key] !== $value) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        "Expected '{$currentPath}' to be " . json_encode($value) .
                            ", got " . json_encode($actual[$key])
                    );
                }
            }
        }
    }

    /**
     * Assert that response content contains specific text
     * 
     * @param string $text Text to search for
     * @param bool $escape Whether to HTML escape the text before searching
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertSee(string $text, bool $escape = true): self
    {
        $searchText = $escape ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : $text;

        if (strpos($this->content, $searchText) === false) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected to see '{$text}' in response content"
            );
        }

        return $this;
    }

    /**
     * Assert that response content does not contain specific text
     * 
     * @param string $text Text that should not be present
     * @param bool $escape Whether to HTML escape the text before searching
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertDontSee(string $text, bool $escape = true): self
    {
        $searchText = $escape ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : $text;

        if (strpos($this->content, $searchText) !== false) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Did not expect to see '{$text}' in response content"
            );
        }

        return $this;
    }

    /**
     * Assert that response contains a specific HTML element
     * 
     * @param string $selector CSS selector or element tag
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertElementExists(string $selector): self
    {
        // Simple element existence check - for more complex DOM queries,
        // consider integrating with DOMDocument or a CSS selector library

        if (strpos($selector, '#') === 0) {
            // ID selector
            $id = substr($selector, 1);
            $pattern = '/id=["\']' . preg_quote($id, '/') . '["\']/i';
        } elseif (strpos($selector, '.') === 0) {
            // Class selector
            $class = substr($selector, 1);
            $pattern = '/class=["\'][^"\']*\b' . preg_quote($class, '/') . '\b[^"\']*["\']/i';
        } else {
            // Tag selector
            $pattern = '/<' . preg_quote($selector, '/') . '(\s|>)/i';
        }

        if (!preg_match($pattern, $this->content)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected element '{$selector}' not found in response"
            );
        }

        return $this;
    }

    /**
     * Assert that response does not contain a specific HTML element
     * 
     * @param string $selector CSS selector or element tag
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertElementNotExists(string $selector): self
    {
        if (strpos($selector, '#') === 0) {
            // ID selector
            $id = substr($selector, 1);
            $pattern = '/id=["\']' . preg_quote($id, '/') . '["\']/i';
        } elseif (strpos($selector, '.') === 0) {
            // Class selector
            $class = substr($selector, 1);
            $pattern = '/class=["\'][^"\']*\b' . preg_quote($class, '/') . '\b[^"\']*["\']/i';
        } else {
            // Tag selector
            $pattern = '/<' . preg_quote($selector, '/') . '(\s|>)/i';
        }

        if (preg_match($pattern, $this->content)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Did not expect element '{$selector}' to be found in response"
            );
        }

        return $this;
    }

    /**
     * Assert that response contains a form with specific action
     * 
     * @param string $action Form action URL
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertFormExists(string $action): self
    {
        $pattern = '/<form[^>]*action=["\']' . preg_quote($action, '/') . '["\']/i';

        if (!preg_match($pattern, $this->content)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected form with action '{$action}' not found in response"
            );
        }

        return $this;
    }

    /**
     * Assert that response contains an input field with specific name
     * 
     * @param string $name Input field name
     * @param string|null $value Expected input value (optional)
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertInputExists(string $name, ?string $value = null): self
    {
        $pattern = '/<input[^>]*name=["\']' . preg_quote($name, '/') . '["\']/i';

        if (!preg_match($pattern, $this->content)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected input field '{$name}' not found in response"
            );
        }

        if ($value !== null) {
            $valuePattern = '/<input[^>]*name=["\']' . preg_quote($name, '/') . '["\'][^>]*value=["\']' . preg_quote($value, '/') . '["\']/i';
            if (!preg_match($valuePattern, $this->content)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Expected input field '{$name}' with value '{$value}' not found in response"
                );
            }
        }

        return $this;
    }

    /**
     * Assert that response contains a link with specific href
     * 
     * @param string $href Link URL
     * @param string|null $text Expected link text (optional)
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertLinkExists(string $href, ?string $text = null): self
    {
        $pattern = '/<a[^>]*href=["\']' . preg_quote($href, '/') . '["\']/i';

        if (!preg_match($pattern, $this->content)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected link with href '{$href}' not found in response"
            );
        }

        if ($text !== null) {
            $textPattern = '/<a[^>]*href=["\']' . preg_quote($href, '/') . '["\'][^>]*>([^<]*' . preg_quote($text, '/') . '[^<]*)<\/a>/i';
            if (!preg_match($textPattern, $this->content)) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Expected link with href '{$href}' and text '{$text}' not found in response"
                );
            }
        }

        return $this;
    }

    /**
     * Assert that response has specific title
     * 
     * @param string $title Expected page title
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function assertTitle(string $title): self
    {
        $pattern = '/<title[^>]*>([^<]*)<\/title>/i';

        if (!preg_match($pattern, $this->content, $matches)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "No title tag found in response"
            );
        }

        $actualTitle = trim($matches[1]);
        if ($actualTitle !== $title) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected title '{$title}', got '{$actualTitle}'"
            );
        }

        return $this;
    }

    /**
     * Validate JSON structure recursively
     */
    private function validateJsonStructure(array $structure, array $data, string $path = ''): void
    {
        foreach ($structure as $key => $value) {
            if (is_int($key)) {
                // Numeric key means we're checking for the existence of a key
                $keyName = $value;
                $currentPath = $path ? "{$path}.{$keyName}" : $keyName;

                if (!array_key_exists($keyName, $data)) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        "Expected key '{$currentPath}' not found in response structure"
                    );
                }
            } else {
                // String key with array value means nested structure
                $currentPath = $path ? "{$path}.{$key}" : $key;

                if (!array_key_exists($key, $data)) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        "Expected key '{$currentPath}' not found in response structure"
                    );
                }

                if (is_array($value) && is_array($data[$key])) {
                    $this->validateJsonStructure($value, $data[$key], $currentPath);
                }
            }
        }
    }
}
