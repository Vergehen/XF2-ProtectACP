<?php

declare(strict_types=1);

namespace West\ProtectACP;

/** Protect admin area: redirect non-admins to public index. */
class Listener
{
    /**
     * Redirect non-admins accessing admin.php to public index.
     *
     * @param \XF\App $app
     */
    public static function onAdminStart(\XF\App $app)
    {
        // Request URI
        $requestUri = $app->request()->getRequestUri();

        // Extract path; parse_url may be null for protocol-relative URIs
        $path = parse_url($requestUri, PHP_URL_PATH);
        $isAdminRequest = false;

        if (is_string($path) && strlen($path)) {
            // e.g. "/admin.php"
            $isAdminRequest = preg_match('#^/+admin\.php(?:$|/)#i', $path) === 1;
        } else {
            // Fallback: strip query/fragment and check raw URI
            $cleanUri = preg_replace('/[?#].*$/', '', $requestUri);
            $isAdminRequest = preg_match('#(?:^|/+)admin\.php(?:$|/)#i', $cleanUri) === 1;
        }

        // Only proceed if request targets admin.php
        if (!$isAdminRequest) {
            return;
        }

        // Get session (may throw)
        try {
            $session = $app->session();
        } catch (\Exception $e) {
            // On session error, redirect to public index
            $response = $app->response();
            $response->redirect($app->router('public')->buildLink('index'));
            $app->complete($response);
            $response->send($app->request());
            exit;
        }

        // Load user from session
        $userId = intval($session->userId ?: 0);
        $user = $userId ? $app->em()->find('XF:User', $userId) : null;

        // Redirect if not admin
        if (!$user || !$user->is_admin) {
            $url = $app->router('public')->buildLink('index');
            $response = $app->response();
            $response->redirect($url);
            // Finalize response
            $app->complete($response);
            $response->send($app->request());
            exit;
        }
    }
}
