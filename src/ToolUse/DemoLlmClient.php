<?php

declare(strict_types=1);

namespace MyVendor\BeMart\ToolUse;

use BEAR\ToolUse\Dispatch\ToolCall;
use BEAR\ToolUse\Llm\LlmClientInterface;
use BEAR\ToolUse\Llm\LlmResponse;
use BEAR\ToolUse\Runtime\Message;
use BEAR\ToolUse\Schema\Tool;
use Override;

use function array_key_exists;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;
use function str_contains;
use function trim;

/**
 * Deterministic LLM adapter for local BEAR.ToolUse smoke tests.
 *
 * The application can override LlmClientInterface with a real LLM client. This
 * client keeps BeMart usable without external credentials and exercises the
 * Agent-as-Tool loop added in BEAR.ToolUse PR #22.
 */
final class DemoLlmClient implements LlmClientInterface
{
    /**
     * @param list<Message> $messages
     * @param list<Tool>    $tools
     */
    #[Override]
    public function chat(string $system, array $messages, array $tools): LlmResponse
    {
        unset($system);

        $lastToolResult = $this->lastToolResultMessage($messages);
        if ($lastToolResult instanceof Message) {
            return $this->text($this->summarizeToolResults($lastToolResult));
        }

        $prompt = $this->lastUserText($messages);
        $toolNames = array_map(static fn (Tool $tool): string => $tool->name, $tools);

        if (in_array('ask_catalog', $toolNames, true)) {
            return $this->toolUse('ask_catalog', [
                'message' => $prompt,
                'context' => ['application' => 'BeMart'],
            ]);
        }

        if (in_array('product_lookup', $toolNames, true) && preg_match('/\b(?:CODE\d{6}|IDEA\d{6}|sample-\d{3}|admin-active-\d{3})\b/', $prompt, $matches) === 1) {
            return $this->toolUse('product_lookup', ['productCode' => $matches[0]]);
        }

        if (in_array('catalog_search', $toolNames, true)) {
            return $this->toolUse('catalog_search', [
                'nameKeyword' => $this->keyword($prompt),
                'limit' => 5,
            ]);
        }

        return $this->text('利用できるBeMart tool がありません。');
    }

    private function text(string $text): LlmResponse
    {
        return new LlmResponse(
            stopReason: 'end_turn',
            content: [['type' => 'text', 'text' => $text]],
            toolCalls: [],
        );
    }

    /** @param array<string, mixed> $input */
    private function toolUse(string $name, array $input): LlmResponse
    {
        $id = 'toolu_' . $name;

        return new LlmResponse(
            stopReason: 'tool_use',
            content: [[
                'type' => 'tool_use',
                'id' => $id,
                'name' => $name,
                'input' => $input,
            ]],
            toolCalls: [new ToolCall($id, $name, $input)],
        );
    }

    /** @param list<Message> $messages */
    private function lastUserText(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = $messages[$i];
            if (! $message instanceof Message || $message->role !== 'user') {
                continue;
            }

            foreach ($message->content as $block) {
                if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    $text = $block['text'];
                    if (str_contains($text, 'Application semantics from ALPS:')) {
                        continue;
                    }

                    return $text;
                }
            }
        }

        return '';
    }

    /** @param list<Message> $messages */
    private function lastToolResultMessage(array $messages): Message|null
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = $messages[$i];
            if (! $message instanceof Message) {
                continue;
            }

            if ($this->hasToolResult($message)) {
                return $message;
            }
        }

        return null;
    }

    private function hasToolResult(Message $message): bool
    {
        foreach ($message->content as $block) {
            if (($block['type'] ?? null) === 'tool_result') {
                return true;
            }
        }

        return false;
    }

    private function summarizeToolResults(Message $message): string
    {
        $summaries = [];
        foreach ($message->content as $block) {
            if (($block['type'] ?? null) !== 'tool_result') {
                continue;
            }

            $content = is_string($block['content'] ?? null) ? $block['content'] : '';
            $summaries[] = $this->summarizeContent($content, (bool) ($block['is_error'] ?? false));
        }

        return implode("\n", $summaries);
    }

    private function summarizeContent(string $content, bool $isError): string
    {
        if ($isError) {
            return 'tool error: ' . $content;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return $content;
        }

        if (array_key_exists('products', $decoded) && is_array($decoded['products'])) {
            $names = [];
            foreach ($decoded['products'] as $product) {
                if (! is_array($product)) {
                    continue;
                }

                $names[] = sprintf(
                    '%s %s ¥%s',
                    (string) ($product['productCode'] ?? ''),
                    (string) ($product['productName'] ?? ''),
                    (string) ($product['price02'] ?? ''),
                );
            }

            return 'カタログ候補: ' . implode(' / ', $names);
        }

        if (isset($decoded['productCode'], $decoded['productName'])) {
            return sprintf(
                '%s は「%s」です。価格は¥%s、在庫は%sです。%s',
                (string) $decoded['productCode'],
                (string) $decoded['productName'],
                (string) ($decoded['price02'] ?? ''),
                ($decoded['stockFind'] ?? false) ? 'あり' : 'なし',
                trim((string) ($decoded['description'] ?? '')),
            );
        }

        return $content;
    }

    private function keyword(string $prompt): string|null
    {
        foreach (['ジェラート', 'CUBE', '彩', 'アイス', '抹茶', 'チョコ', 'IDEA'] as $keyword) {
            if (str_contains($prompt, $keyword)) {
                return $keyword;
            }
        }

        return null;
    }
}
