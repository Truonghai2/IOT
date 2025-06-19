<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Core\Controller;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SubscriptionController extends Controller
{
    public function store(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        $data = $request->getParsedBody();
        if (!isset($data['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'user_id is required'], 400);
        }

        // Save or update subscription by user_id only
        $existing = Capsule::table('subscriptions')->where('user_id', $data['user_id'])->first();
        if (!$existing) {
            Subscription::create([
                'user_id' => $data['user_id'],
                'endpoint' => null,
                'auth_key' => null,
                'p256dh_key' => null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        }

        return $this->jsonResponse($response, ['message' => 'Subscription saved']);
    }
} 