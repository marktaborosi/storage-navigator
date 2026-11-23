<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Security;

use Marktaborosi\StorageNavigator\Exception\AccessDeniedException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Stateless CSRF protection for the write operations (signed double-submit cookie).
 *
 * The token is a random value plus its HMAC under the configured secret. It is set as an
 * HttpOnly, SameSite=Strict cookie and embedded in the page; a write request must send the
 * same token in the X-CSRF-Token header or the "_token" form field. No session is needed.
 */
final readonly class CsrfGuard
{
    public const string COOKIE = 'sn_csrf';
    public const string FIELD = '_token';
    public const string HEADER = 'X-CSRF-Token';

    public function __construct(#[\SensitiveParameter] private string $secret)
    {
    }

    /**
     * The token for the page being rendered, and the Set-Cookie header value when a new
     * cookie has to be issued.
     *
     * @return array{token: string, setCookie: string|null}
     */
    public function issue(ServerRequestInterface $request): array
    {
        $existing = $request->getCookieParams()[self::COOKIE] ?? null;
        if (is_string($existing) && $this->isAuthentic($existing)) {
            return ['token' => $existing, 'setCookie' => null];
        }

        $nonce = self::base64Url(random_bytes(32));
        $token = $nonce . '.' . $this->sign($nonce);
        $cookie = sprintf('%s=%s; Path=/; HttpOnly; SameSite=Strict', self::COOKIE, $token);
        if ($request->getUri()->getScheme() === 'https') {
            $cookie .= '; Secure';
        }

        return ['token' => $token, 'setCookie' => $cookie];
    }

    /**
     * @throws AccessDeniedException
     */
    public function validate(ServerRequestInterface $request): void
    {
        $cookie = $request->getCookieParams()[self::COOKIE] ?? null;
        $submitted = $request->getHeaderLine(self::HEADER);
        if ($submitted === '') {
            $body = $request->getParsedBody();
            $submitted = is_array($body) && is_string($body[self::FIELD] ?? null) ? $body[self::FIELD] : '';
        }

        if (!is_string($cookie) || $submitted === '' || !hash_equals($cookie, $submitted) || !$this->isAuthentic($cookie)) {
            throw new AccessDeniedException('The security token is missing or expired. Reload the page and try again.');
        }
    }

    private function isAuthentic(string $token): bool
    {
        $parts = explode('.', $token);

        return count($parts) === 2 && strlen($parts[0]) === 43 && hash_equals($this->sign($parts[0]), $parts[1]);
    }

    private function sign(string $nonce): string
    {
        return self::base64Url(hash_hmac('sha256', $nonce, $this->secret, true));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
