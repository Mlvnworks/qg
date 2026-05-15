<?php

class GeminiService
{
    private string $lastError = '';

    public function isConfigured(): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $apiKey = trim((string) (GEMINI_API_KEY ?? ''));

        if ($apiKey === '' || str_contains(strtolower($apiKey), 'your-')) {
            return false;
        }

        return true;
    }

    public function generateQuestions(string $topic, string $difficulty, int $questionCount, ?string $sourceText = null, ?array $sourceFile = null): array
    {
        $this->lastError = '';
        $payload = $this->requestPayload($topic, $difficulty, $questionCount, $sourceText, $sourceFile);

        if ($this->isConfigured()) {
            $remote = $this->callGemini($payload, $sourceFile);

            if ($remote['success']) {
                return $remote['questions'];
            }

            $this->lastError = $remote['error'] ?? 'Gemini request failed.';
            return [];
        }

        return $this->fallbackQuestions($topic, $difficulty, $questionCount, $sourceText);
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    private function requestPayload(string $topic, string $difficulty, int $questionCount, ?string $sourceText = null, ?array $sourceFile = null): string
    {
        $topicLine = $topic !== '' ? $topic : 'Use the uploaded source as the topic.';
        $sourceSummary = $sourceText !== null && $sourceText !== ''
            ? "Primary source material:\n" . $sourceText . "\n"
            : '';
        $sourceFileNote = $sourceFile !== null
            ? 'Uploaded source file: ' . (string) ($sourceFile['original_filename'] ?? 'source file') . "\n"
            : '';

        return <<<PROMPT
Generate {$questionCount} multiple-choice quiz questions in JSON.

Topic hint: {$topicLine}
Difficulty: {$difficulty}
{$sourceFileNote}
{$sourceSummary}
Rules:
- Base every question on the uploaded source when source material is provided.
- If the typed topic conflicts with the uploaded source, follow the uploaded source.
- Do not invent facts, concepts, names, or examples that are not supported by the source.
- Keep questions tightly focused on the source content, not just the file name or broad topic.
- If the source is an image, use the visible content of the image.

Return a JSON array only. Each item must have:
- question
- choices (array with exactly 4 strings)
- correct_answer
- explanation
- difficulty

Make the correct_answer exactly match one of the choices.
PROMPT;
    }

    private function callGemini(string $prompt, ?array $sourceFile = null): array
    {
        $url = GEMINI_API_URL . '/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
        $parts = [
            ['text' => $prompt],
        ];

        if ($sourceFile !== null && $this->canSendInlineFile($sourceFile)) {
            $binary = @file_get_contents($sourceFile['absolute_path']);

            if (is_string($binary) && $binary !== '') {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $sourceFile['mime_type'],
                        'data' => base64_encode($binary),
                    ],
                ];
            }
        }

        $body = json_encode([
            'contents' => [
                [
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'responseMimeType' => 'application/json',
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-goog-api-key: ' . (string) GEMINI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $error !== '' || $status >= 400) {
            $errorMessage = 'Gemini request failed.';

            if ($error !== '') {
                $errorMessage .= ' cURL: ' . $error;
            }

            if ($status >= 400 && is_string($response) && $response !== '') {
                $decodedError = json_decode($response, true);
                $apiMessage = $decodedError['error']['message'] ?? null;

                if (is_string($apiMessage) && $apiMessage !== '') {
                    $errorMessage .= ' API: ' . $apiMessage;
                } else {
                    $errorMessage .= ' HTTP ' . $status . '.';
                }
            }

            return ['success' => false, 'questions' => [], 'error' => $errorMessage];
        }

        $decoded = json_decode($response, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text)) {
            return ['success' => false, 'questions' => [], 'error' => 'Gemini returned an unexpected response structure.'];
        }

        $questions = json_decode($text, true);

        if (!is_array($questions)) {
            return ['success' => false, 'questions' => [], 'error' => 'Gemini did not return valid JSON quiz data.'];
        }

        return ['success' => true, 'questions' => $this->normalizeQuestions($questions)];
    }

    private function canSendInlineFile(array $sourceFile): bool
    {
        $mimeType = (string) ($sourceFile['mime_type'] ?? '');
        $absolutePath = (string) ($sourceFile['absolute_path'] ?? '');

        if ($absolutePath === '' || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return false;
        }

        return in_array($mimeType, [
            'image/png',
            'image/jpeg',
            'image/webp',
        ], true);
    }

    private function fallbackQuestions(string $topic, string $difficulty, int $questionCount, ?string $sourceText = null): array
    {
        $focus = $topic !== '' ? $topic : 'the selected topic';
        $templates = [
            [
                'question' => 'Which statement best describes %s?',
                'choices' => [
                    '%s is a core concept related to the quiz topic.',
                    '%s is unrelated to the quiz topic.',
                    '%s can never be explained in a classroom setting.',
                    '%s only applies after a quiz is completed.',
                ],
                'correct' => 0,
                'explanation' => 'This fallback question is based on the typed topic because Gemini is not configured.',
            ],
            [
                'question' => 'Why is %s important in this topic?',
                'choices' => [
                    'It helps explain the main idea of %s.',
                    'It proves %s should be ignored completely.',
                    'It means %s has no real-world use.',
                    'It shows %s is never connected to other ideas.',
                ],
                'correct' => 0,
                'explanation' => 'Fallback mode uses simple topic-based questions until Gemini is available.',
            ],
            [
                'question' => 'Which example is most likely connected to %s?',
                'choices' => [
                    'An example that demonstrates %s in practice.',
                    'An example that removes %s from the discussion.',
                    'An example that says %s cannot be studied.',
                    'An example that proves %s is always false.',
                ],
                'correct' => 0,
                'explanation' => 'This is a topic-only fallback question, not AI-generated content.',
            ],
        ];

        $questions = [];

        for ($index = 1; $index <= $questionCount; $index++) {
            $template = $templates[($index - 1) % count($templates)];
            $choices = [];

            foreach ($template['choices'] as $choiceTemplate) {
                $choices[] = sprintf($choiceTemplate, $focus);
            }

            $questions[] = [
                'question' => sprintf($template['question'], $focus),
                'choices' => $choices,
                'correct_answer' => $choices[$template['correct']],
                'explanation' => $template['explanation'],
                'difficulty' => ucfirst(strtolower($difficulty)),
            ];
        }

        return $questions;
    }

    private function normalizeQuestions(array $questions): array
    {
        $normalized = [];

        foreach ($questions as $question) {
            $choices = array_values(array_filter($question['choices'] ?? [], 'is_string'));

            if (!is_string($question['question'] ?? null) || count($choices) !== 4) {
                continue;
            }

            $correctAnswer = (string) ($question['correct_answer'] ?? '');

            if (!in_array($correctAnswer, $choices, true)) {
                $correctAnswer = $choices[0];
            }

            $normalized[] = [
                'question' => trim($question['question']),
                'choices' => array_map('trim', $choices),
                'correct_answer' => trim($correctAnswer),
                'explanation' => trim((string) ($question['explanation'] ?? '')),
                'difficulty' => trim((string) ($question['difficulty'] ?? 'Medium')),
            ];
        }

        return $normalized;
    }
}
