<?php

namespace App\Services;

use App\Support\GeminiApiKeys;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService
{
    protected ?int $lastHttpStatus = null;

    protected string $lastError = '';

    public const FALLBACK_MODEL = 'gemini-3.6-flash';

    public function __construct(
        protected string $configKeyPath = 'services.gemini.key',
        protected string $configModelPath = 'services.gemini.model',
        protected string $logTag = 'GeminiService',
    ) {}

    public function lastHttpStatus(): ?int
    {
        return $this->lastHttpStatus;
    }

    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * @param  array<string, mixed>|null  $generationConfig
     */
    public function chat(string $material, string $instruction, int $timeoutSeconds = 180, ?array $generationConfig = null): ?string
    {
        $this->lastHttpStatus = null;
        $this->lastError = '';
        $instruction = trim($instruction);
        $material = trim($material);

        if ($instruction === '' || $material === '') {
            $this->lastError = 'Пустая инструкция или текст.';

            return null;
        }

        $keys = GeminiApiKeys::fromConfig($this->configKeyPath);
        if ($keys === []) {
            $this->lastError = 'Не задан GEMINI_API_KEY в .env';
            Log::error('['.$this->logTag.'] нет GEMINI_API_KEY');

            return null;
        }

        $model = trim((string) config($this->configModelPath, self::FALLBACK_MODEL));
        if ($model === '') {
            $model = self::FALLBACK_MODEL;
        }

        $models = array_values(array_unique(array_filter([$model, self::FALLBACK_MODEL])));
        // Сначала с переданным generationConfig, потом без responseMimeType (некоторые ключи/модели падают)
        $configs = [$generationConfig];
        if (is_array($generationConfig) && isset($generationConfig['responseMimeType'])) {
            $fallbackConfig = $generationConfig;
            unset($fallbackConfig['responseMimeType']);
            $configs[] = $fallbackConfig === [] ? null : $fallbackConfig;
        }
        $configs[] = null;

        $seen = [];
        foreach ($models as $tryModel) {
            foreach ($configs as $tryConfig) {
                foreach ($keys as $apiKey) {
                    $sig = $tryModel.'|'.md5(json_encode($tryConfig).'|'.substr($apiKey, 0, 12));
                    if (isset($seen[$sig])) {
                        continue;
                    }
                    $seen[$sig] = true;

                    $text = $this->requestGenerateContent(
                        $apiKey,
                        $tryModel,
                        $material,
                        $instruction,
                        $timeoutSeconds,
                        $tryConfig
                    );

                    if ($text !== null) {
                        return $text;
                    }

                    // 400 на конфиге — пробуем другой config; 401/403 — следующий ключ; 429/503 — следующий ключ
                    $status = $this->lastHttpStatus;
                    if ($status === 400) {
                        break; // следующий config
                    }
                    if (in_array($status, [401, 403], true)) {
                        continue;
                    }
                    if (in_array($status, [429, 500, 503, 504], true)) {
                        usleep(250000);
                        continue;
                    }
                    if ($status === 404) {
                        break 2; // следующая модель
                    }
                }
            }
        }

        if ($this->lastError === '') {
            $this->lastError = 'Gemini не вернул текст (HTTP '.($this->lastHttpStatus ?? '—').').';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $generationConfig
     */
    private function requestGenerateContent(
        string $apiKey,
        string $model,
        string $material,
        string $instruction,
        int $timeoutSeconds,
        ?array $generationConfig,
    ): ?string {
        $userContent = $instruction."\n\n--- SOURCE TEXT ---\n".$material;
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            .rawurlencode($model)
            .':generateContent?key='.$apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userContent]],
                ],
            ],
        ];

        $mergedConfig = is_array($generationConfig) ? $generationConfig : [];
        if (str_starts_with($model, 'gemini-3') && ! isset($mergedConfig['thinkingConfig'])) {
            $mergedConfig['thinkingConfig'] = ['thinkingLevel' => 'minimal'];
        }
        if ($mergedConfig !== []) {
            $payload['generationConfig'] = $mergedConfig;
        }

        try {
            $response = Http::timeout(max(30, $timeoutSeconds))
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (Throwable $e) {
            $this->lastHttpStatus = null;
            $this->lastError = 'Сеть/таймаут: '.$e->getMessage();
            Log::error('['.$this->logTag.'] HTTP: '.$e->getMessage());

            return null;
        }

        $this->lastHttpStatus = $response->status();
        $body = $response->body();

        if (! $response->successful()) {
            $this->lastError = 'HTTP '.$this->lastHttpStatus.': '.mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 220);
            Log::error('['.$this->logTag.'] status='.$this->lastHttpStatus.' key='.substr($apiKey, 0, 8).'… body='.mb_substr($body, 0, 500));

            return null;
        }

        $json = $response->json();
        $text = $this->extractTextFromResponse($json);
        if ($text === null) {
            $finish = is_array($json) ? (string) ($json['candidates'][0]['finishReason'] ?? '') : '';
            $this->lastError = 'Пустой ответ Gemini'.($finish !== '' ? ' (finishReason='.$finish.')' : '');
            Log::warning('['.$this->logTag.'] empty candidates finish='.$finish.' body='.mb_substr($body, 0, 800));
        }

        return $text;
    }

    private function extractTextFromResponse(mixed $json): ?string
    {
        if (! is_array($json)) {
            return null;
        }

        $parts = $json['candidates'][0]['content']['parts'] ?? null;
        if (! is_array($parts) || $parts === []) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (! is_array($part) || ! empty($part['thought'])) {
                continue;
            }
            $piece = $part['text'] ?? null;
            if (is_string($piece) && trim($piece) !== '') {
                $chunks[] = trim($piece);
            }
        }

        if ($chunks === []) {
            return null;
        }

        $text = trim(implode("\n", $chunks));

        return $text !== '' ? $text : null;
    }
}
