<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerAuthController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('player')->logout();
        $request->session()->forget('otp_email');

        return redirect()->route('home');
    }
}
