<?php

namespace TN\TN_Core\Test;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Response\HTTPResponse;

/**
 * Test client for functional testing
 * 
 * Provides a way to make requests through the framework's routing system
 * without actual HTTP networking, similar to Symfony's and Laravel's test clients.
 */
class TestClient
{
    private array $defaultHeaders;
    private array $defaultCookies;
    private array $defaultServerVars;

    public function __construct()
    {
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $this->defaultCookies = [];

        $this->defaultServerVars = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off'
        ];
    }

    /**
     * Make a GET request
     * 
     * @param string $uri Request URI
     * @param array $headers Additional headers
     * @return TestResponse Response object
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->request('GET', $uri, [], $headers);
    }

    /**
     * Make a POST request
     * 
     * @param string $uri Request URI
     * @param array $data Request data (will be JSON encoded)
     * @param array $headers Additional headers
     * @return TestResponse Response object
     */
    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    /**
     * Make a PUT request
     * 
     * @param string $uri Request URI
     * @param array $data Request data (will be JSON encoded)
     * @param array $headers Additional headers
     * @return TestResponse Response object
     */
    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->request('PUT', $uri, $data, $headers);
    }

    /**
     * Make a DELETE request
     * 
     * @param string $uri Request URI
     * @param array $headers Additional headers
     * @return TestResponse Response object
     */
    public function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->request('DELETE', $uri, [], $headers);
    }

    /**
     * Make a generic HTTP request
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return TestResponse Response object
     */
    public function request(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        // Parse URI to extract path and query parameters
        $parsedUri = parse_url($uri);
        $path = $parsedUri['path'] ?? '/';
        $queryString = $parsedUri['query'] ?? '';

        // Parse query parameters
        $queryParams = [];
        if (!empty($queryString)) {
            parse_str($queryString, $queryParams);
        }

        // Merge headers
        $allHeaders = array_merge($this->defaultHeaders, $headers);

        // Build server variables
        $serverVars = array_merge($this->defaultServerVars, [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $queryString
        ]);

        // Convert headers to server format
        foreach ($allHeaders as $name => $value) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $serverVars[$serverKey] = $value;
        }

        // Prepare request data
        $postData = [];
        $jsonBody = '';

        if (!empty($data)) {
            if ($allHeaders['Content-Type'] === 'application/json') {
                $jsonBody = json_encode($data);
                $serverVars['CONTENT_LENGTH'] = strlen($jsonBody);
            } else {
                $postData = $data;
            }
        }

        // Create mock HTTP request
        $requestOptions = [
            'query' => $queryParams,
            'post' => $postData,
            'cookie' => $this->defaultCookies,
            'request' => array_merge($queryParams, $postData),
            'files' => [],
            'server' => $serverVars,
            'session' => $_SESSION ?? []
        ];

        // Initialize session if not set
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        // Set $_SERVER and $_ENV variables for framework compatibility
        foreach ($serverVars as $key => $value) {
            $_SERVER[$key] = $value;
        }

        // Prevent SITE_MAINTENANCE_MODE warning
        $_ENV['SITE_MAINTENANCE_MODE'] = '0';

        // Ensure test database is set and connections are cleared immediately before API call
        if (isset($_ENV['TEST_DATABASE'])) {
            $_ENV['MYSQL_DB'] = $_ENV['TEST_DATABASE'];
            \TN\TN_Core\Model\Storage\DB::closeConnections();
        }

        // Reset framework state before each request to ensure clean authentication
        \TN\TN_Core\Test\StateManager::resetAll();

        // Create HTTP request and capture response
        $request = HTTPRequest::create($requestOptions);
        $request->path = $path;
        $request->method = strtoupper($method);
        $request->ext = null;

        // Set test request body for JSON requests
        if (!empty($jsonBody)) {
            $request->setTestRequestBody($jsonBody);
        }

        // Capture the response
        return $this->captureResponse($request);
    }

    /**
     * Set default cookies for all requests
     * 
     * @param array $cookies Cookie name => value pairs
     * @return self
     */
    public function withCookies(array $cookies): self
    {
        $this->defaultCookies = array_merge($this->defaultCookies, $cookies);
        return $this;
    }

    /**
     * Set default headers for all requests
     * 
     * @param array $headers Header name => value pairs
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Set authentication token cookie
     * 
     * @param string $token Authentication token
     * @return self
     */
    public function withToken(string $token): self
    {
        return $this->withCookies(['TN_token' => $token]);
    }

    /**
     * Capture the framework's response
     */
    private function captureResponse(HTTPRequest $request): TestResponse
    {
        // Start output buffering to capture response
        ob_start();

        // Capture headers
        $capturedHeaders = [];
        $originalHeadersFunction = null;

        // Override header() function if possible (this is tricky in PHP)
        // For now, we'll work with what we can capture

        try {
            // Let the framework handle the request
            $request->respond();

            // Capture the output
            $content = ob_get_contents();

            // Default to 200 if no errors occurred
            $statusCode = http_response_code() ?: 200;
        } catch (\Exception $e) {
            // If an exception occurred, treat it as a 500 error
            $content = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => get_class($e)
            ]);
            $statusCode = 500;
        } finally {
            ob_end_clean();
        }

        return new TestResponse($statusCode, $content, $capturedHeaders);
    }
}
