<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Msdev2\Shopify\Http\Controllers\BaseController;

class AuthController extends BaseController
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('msdev2.admin.login');
    }
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('msdev2::admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->route('msdev2.admin.login');
    }
}