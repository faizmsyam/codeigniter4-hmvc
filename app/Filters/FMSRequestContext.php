<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

/**
 * Holds per-request authentication context resolved by authentication filters.
 *
 * The registry is keyed by the request object identity so a controller or a
 * later filter can read the verified subject without mutating the framework
 * request class, and without relying on dynamic properties (deprecated in
 * PHP 8.2 and later).
 */
final class FMSRequestContext
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private static array $authenticatedSubjects = [];

    /**
     * @var array<int, string>
     */
    private static array $requestIdentifiers = [];

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public static function setAuthenticatedSubject(RequestInterface $request, array $authenticatedSubject): void
    {
        self::$authenticatedSubjects[spl_object_id($request)] = $authenticatedSubject;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function authenticatedSubject(RequestInterface $request): ?array
    {
        return self::$authenticatedSubjects[spl_object_id($request)] ?? null;
    }

    public static function hasAuthenticatedSubject(RequestInterface $request): bool
    {
        return isset(self::$authenticatedSubjects[spl_object_id($request)]);
    }

    public static function setRequestIdentifier(RequestInterface $request, string $requestIdentifier): void
    {
        self::$requestIdentifiers[spl_object_id($request)] = $requestIdentifier;
    }

    public static function requestIdentifier(RequestInterface $request): ?string
    {
        return self::$requestIdentifiers[spl_object_id($request)] ?? null;
    }

    public static function forget(RequestInterface $request): void
    {
        $requestIdentifier = spl_object_id($request);

        unset(self::$authenticatedSubjects[$requestIdentifier], self::$requestIdentifiers[$requestIdentifier]);
    }

    public static function forgetAll(): void
    {
        self::$authenticatedSubjects = [];
        self::$requestIdentifiers    = [];
    }
}
