<?php

/**
 * auth() helper — provides a simple interface to the currently
 * authenticated user as set by PermissionMiddleware.
 *
 * Usage:
 *   auth()->user()           → object|null
 *   auth()->user()->id       → users.id  (INT)
 *   auth()->user()->employee_id → employees.id (INT, FK in users table)
 *   auth()->user()->role     → 'admin'|'hr'|'manager'|'employee'|'system'
 *   auth()->user()->email    → string
 *   auth()->check()          → bool
 *   auth()->id()             → int|null
 */

if (!function_exists('auth')) {
    function auth(): object
    {
        return new class {
            /**
             * Return the authenticated user object, or null if not authenticated.
             */
            public function user(): ?object
            {
                $request = service('request');
                $userId = $request->getHeaderLine('X-Auth-User-Id');

                if (empty($userId)) {
                    return null;
                }

                $perms = $request->getHeaderLine('X-Auth-Permissions');

                return (object) [
                    'id'          => (int) $userId,
                    'employee_id' => (int) ($request->getHeaderLine('X-Auth-Employee-Id') ?: $userId),
                    'role'        => $request->getHeaderLine('X-Auth-Role') ?: 'employee',
                    'email'       => $request->getHeaderLine('X-Auth-Email') ?: null,
                    'permissions' => $perms ? json_decode($perms, true) : [],
                ];
            }

            /**
             * Return true if a user is currently authenticated.
             */
            public function check(): bool
            {
                return $this->user() !== null;
            }

            /**
             * Shortcut to get the authenticated user's ID (users.id).
             */
            public function id(): ?int
            {
                return $this->user()?->id;
            }
        };
    }
}
