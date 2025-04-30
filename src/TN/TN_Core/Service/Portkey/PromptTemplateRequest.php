<?php

namespace TN\TN_Core\Service\Portkey;

class PromptTemplateRequest extends PortkeyRequest
{
    private string $templateId;
    private array $templateInput;

    public function __construct(string $templateId, array $input)
    {
        $this->templateId = $templateId;
        $this->templateInput = $input;
        parent::__construct();
    }

    protected function request(): void
    {
        $this->url = rtrim($_ENV['PORTKEY_API_ENDPOINT'], '/') . '/prompts/' . $this->templateId . '/completions';

        // Convert each input field to a stringified JSON value
        $variables = [];
        foreach ($this->templateInput as $key => $value) {
            $variables[$key] = json_encode($value);
        }

        $payload = [
            'variables' => $variables,
            'stream' => false
        ];

        var_dump("EXACT REQUEST URL:", $this->url);
        var_dump("EXACT REQUEST PAYLOAD:", json_encode($payload, JSON_PRETTY_PRINT));

        $this->request = $payload;  // Store the payload for debugging
        $this->post($this->url, json_encode($payload));
    }

    protected function parseResponse(): void
    {
        // No-op, already handled in base
    }
}
