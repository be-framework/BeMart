<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function htmlspecialchars;
use function is_string;
use function str_replace;
use function str_starts_with;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class AppErrorPage extends ResourceObject
{
    /** @param array<string, mixed> $body */
    public function __construct(int $code, array $body, bool $html = false, string $path = '')
    {
        $this->code = $code;
        $this->body = ['code' => $code] + $body + ['path' => $path];
        $this->headers = ['Content-Type' => $html ? 'text/html; charset=utf-8' : 'application/json; charset=utf-8'];
        if ($html) {
            $this->view = $this->html($code, $this->body, $path);
        }
    }

    /** @param array<string, mixed> $body */
    private function html(int $code, array $body, string $path): string
    {
        $title = $this->title($code);
        $publicPath = self::publicPath($path);
        $message = $body['message'] ?? $title;
        $message = is_string($message) && $message !== '' ? $message : $title;
        if ($code === Code::NOT_FOUND && str_starts_with($message, 'page://self/')) {
            $message = '指定されたページは見つかりません。';
        }
        $pathLine = $publicPath !== '' ? '<p class="bemart-error-path">' . self::escape($publicPath) . '</p>' : '';
        $adminLink = match (true) {
            str_starts_with($publicPath, '/admin') && $code === Code::FORBIDDEN => '<a class="bemart-error-link" href="/admin/login">管理ログインへ</a><a class="bemart-error-link" href="/">トップへ</a>',
            str_starts_with($publicPath, '/admin') => '<a class="bemart-error-link" href="/admin/index">管理ホームへ</a><a class="bemart-error-link" href="/admin/login">管理ログインへ</a>',
            default => '<a class="bemart-error-link" href="/">トップへ</a><a class="bemart-error-link" href="/products">商品を見る</a>',
        };

        return '<!doctype html>' . "\n"
            . '<html lang="ja">' . "\n"
            . '<head>' . "\n"
            . '  <meta charset="utf-8">' . "\n"
            . '  <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
            . '  <title>' . self::escape((string) $code . ' ' . $title) . ' - BeMart</title>' . "\n"
            . '  <style>' . self::css() . '</style>' . "\n"
            . '</head>' . "\n"
            . '<body class="bemart-error-page">' . "\n"
            . '  <main class="bemart-error-card" role="main">' . "\n"
            . '    <p class="bemart-error-eyebrow">BeMart</p>' . "\n"
            . '    <h1>' . self::escape((string) $code . ' ' . $title) . '</h1>' . "\n"
            . '    <p class="bemart-error-message">' . self::escape($message) . '</p>' . "\n"
            .      $pathLine . "\n"
            . '    <div class="bemart-error-actions">' . $adminLink . '</div>' . "\n"
            . '  </main>' . "\n"
            . '</body>' . "\n"
            . '</html>';
    }

    private function title(int $code): string
    {
        return match ($code) {
            Code::BAD_REQUEST => 'リクエストを処理できません',
            Code::UNAUTHORIZED => 'ログインが必要です',
            Code::FORBIDDEN => 'アクセスできません',
            Code::NOT_FOUND => 'ページが見つかりません',
            default => (new Code())->statusText[$code] ?? 'エラーが発生しました',
        };
    }


    private static function publicPath(string $path): string
    {
        if (str_starts_with($path, 'page://self')) {
            return str_replace('page://self', '', $path) ?: '/';
        }

        return $path;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function css(): string
    {
        return 'body.bemart-error-page{margin:0;min-height:100vh;display:grid;place-items:center;background:#eff0f4;color:#212529;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.bemart-error-card{width:min(720px,calc(100% - 48px));background:#fff;border-radius:18px;padding:48px;box-shadow:0 18px 48px rgba(0,0,0,.08)}.bemart-error-eyebrow{margin:0 0 12px;color:#437ec4;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.bemart-error-card h1{margin:0 0 16px;font-size:clamp(28px,4vw,48px);line-height:1.1}.bemart-error-message{margin:0 0 16px;color:#54687a;font-size:18px;line-height:1.7}.bemart-error-path{margin:0 0 28px;padding:12px 14px;border-radius:10px;background:#f7f8fa;color:#6c757d;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-all}.bemart-error-actions{display:flex;gap:12px;flex-wrap:wrap}.bemart-error-link{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:999px;background:#437ec4;color:#fff;text-decoration:none;font-weight:700}.bemart-error-link+ .bemart-error-link{background:#fff;color:#437ec4;border:1px solid #b4cbe7}';
    }
}
