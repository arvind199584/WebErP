<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIDrafter\Services;

use GuzzleHttp\Client;
use Exception;

class GeminiService {
    private string $apiKey;
    private string $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";

    // POINTING TO THE NEW DRAFTER BRAIN ON PORT 5001
    private string $pythonUrl = "http://127.0.0.1:5001/orchestrate-prompt";

    public function __construct() {
        $this->apiKey = "AIzaSyAGqjVJYKYrYBWaFyn2JSe7_Y2903WWyz0";
    }

    public function generateDraft(array $params): string {
        $client = new Client();

        try {
            // 1. CALL NEW PYTHON API (PORT 5001)
            $pyResponse = $client->post($this->pythonUrl, [
                'json' => $params,
                'timeout' => 5
            ]);
            $pyBody = json_decode($pyResponse->getBody()->getContents(), true);
            $structuredPrompt = $pyBody['orchestrated_prompt'] ?? null;

            if (!$structuredPrompt) {
                return "Error: Python Orchestrator failed to build prompt.";
            }

            // 2. CALL GEMINI
            $gemResponse = $client->post($this->geminiUrl . "?key=" . $this->apiKey, [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $structuredPrompt]]]
                    ]
                ],
                'http_errors' => false
            ]);

            $statusCode = $gemResponse->getStatusCode();
            $gemBody = json_decode($gemResponse->getBody()->getContents(), true);

            if ($statusCode !== 200) {
                return "Gemini API Error ($statusCode): " . ($gemBody['error']['message'] ?? 'Unknown');
            }

            return $gemBody['candidates'][0]['content']['parts'][0]['text'] ?? "AI failed to generate a response.";

        } catch (Exception $e) {
            return "System Error: " . $e->getMessage() . ". Make sure drafter_brain.py is running on port 5001.";
        }
    }
}
