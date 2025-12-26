<?php

/**
 * Forked and improved
 * Original by West from TechGate Studio
 * Date of original creation: 12.06.2019
 * Purpose: Redirect non-admin users from any admin.php URL to public index
 */

namespace West\ProtectACP;


class Listener
{
    /**
     * Handle admin request start: redirect non-admin users to public index.
     *
     * @param \XF\App $app
     */
    public static function onAdminStart(\XF\App $app)
    {
        // Get request path (ignore query string)
        $requestUri = $app->request()->getRequestUri();
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '';

        // Only proceed for root-level admin.php requests
        if (!strlen($path) || !preg_match('#^/+admin\.php(?:$|/)#i', $path)) {
            return;
        }

        // Obtain the session (may throw)
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

        // Load user from session (if any)
        $userId = intval($session->userId ?: 0);
        $user = $userId ? $app->em()->find('XF:User', $userId) : null;

        // Redirect non-admin users
        if (!$user || !$user->is_admin) {
            $url = $app->router('public')->buildLink('index');
            $response = $app->response();
            $response->redirect($url);
            // Persist session cookies and other response changes
            $app->complete($response);
            $response->send($app->request());
            exit;
        }
    }
}
