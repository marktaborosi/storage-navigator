<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Security;

use Marktaborosi\StorageNavigator\Exception\AccessDeniedException;
use Marktaborosi\StorageNavigator\Security\CsrfGuard;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class CsrfGuardTest extends TestCase
{
    private CsrfGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new CsrfGuard(str_repeat('s', 32));
    }

    public function testIssuesAHardenedCookie(): void
    {
        $issued = $this->guard->issue(new ServerRequest('GET', 'https://example.com/files'));

        self::assertNotNull($issued['setCookie']);
        self::assertStringContainsString('HttpOnly', $issued['setCookie']);
        self::assertStringContainsString('SameSite=Strict', $issued['setCookie']);
        self::assertStringContainsString('Secure', $issued['setCookie']);
        self::assertStringNotContainsString('Secure', (string) $this->guard->issue(new ServerRequest('GET', 'http://localhost/'))['setCookie']);
    }

    public function testReusesAValidCookie(): void
    {
        $token = $this->guard->issue(new ServerRequest('GET', '/'))['token'];
        $again = $this->guard->issue((new ServerRequest('GET', '/'))->withCookieParams([CsrfGuard::COOKIE => $token]));

        self::assertSame($token, $again['token']);
        self::assertNull($again['setCookie']);
    }

    public function testAcceptsHeaderOrField(): void
    {
        $token = $this->guard->issue(new ServerRequest('GET', '/'))['token'];
        $base = (new ServerRequest('POST', '/'))->withCookieParams([CsrfGuard::COOKIE => $token]);

        $this->guard->validate($base->withHeader(CsrfGuard::HEADER, $token));
        $this->guard->validate($base->withParsedBody([CsrfGuard::FIELD => $token]));
        $this->addToAssertionCount(2);
    }

    public function testRejectsMissingToken(): void
    {
        $token = $this->guard->issue(new ServerRequest('GET', '/'))['token'];

        $this->expectException(AccessDeniedException::class);
        $this->guard->validate((new ServerRequest('POST', '/'))->withCookieParams([CsrfGuard::COOKIE => $token]));
    }

    public function testRejectsTokenSignedWithAnotherSecret(): void
    {
        $foreign = (new CsrfGuard(str_repeat('x', 32)))->issue(new ServerRequest('GET', '/'))['token'];

        $this->expectException(AccessDeniedException::class);
        $this->guard->validate((new ServerRequest('POST', '/'))
            ->withCookieParams([CsrfGuard::COOKIE => $foreign])
            ->withHeader(CsrfGuard::HEADER, $foreign));
    }

    public function testRejectsMismatch(): void
    {
        $a = $this->guard->issue(new ServerRequest('GET', '/'))['token'];
        $b = $this->guard->issue(new ServerRequest('GET', '/'))['token'];

        $this->expectException(AccessDeniedException::class);
        $this->guard->validate((new ServerRequest('POST', '/'))->withCookieParams([CsrfGuard::COOKIE => $a])->withHeader(CsrfGuard::HEADER, $b));
    }
}
