<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthFactusController extends Controller
{
  public function auth()
  {
    $response = Http::asForm()->post(config('constants.URL_API') . '/oauth/token', [
      'grant_type' => env('GRANT_TYPE'),
      'client_id' => env('CLIENT_ID'),
      'client_secret' => env('CLIENT_SECRET'),
      'username' => env('EMAIL'),
      'password' => env('PASSWORD')
    ]);

    if ($response->successful()) {
      $data = $response->json();
      $accessToken = $data['access_token'];

      return $accessToken;
    } else {
      return null;
    }
  }
}
