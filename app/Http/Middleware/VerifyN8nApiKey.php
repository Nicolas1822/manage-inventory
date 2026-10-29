<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nApiKey
{
    /**
     * Valida la clave de API enviada por n8n en los encabezados o como Bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.n8n.api_key', env('N8N_API_KEY'));

        // Si no se ha configurado ninguna clave en .env, permitir el acceso (útil en desarrollo)
        if (empty($configuredKey)) {
            return $next($request);
        }

        $apiKey = $request->header('X-API-KEY') ?? $request->bearerToken() ?? $request->query('api_key');

        if ($apiKey !== $configuredKey) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado: API Key no proporcionada o no coincide.',
            ], 401);
        }

        return $next($request);
    }
}
