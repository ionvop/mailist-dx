<?php

namespace App\Http\Controllers;

use App\Support\DevAuth;
use Illuminate\Http\RedirectResponse;

class DevAuthController extends Controller
{
    /**
     * Flip the development-only auth state and return to the previous page.
     */
    public function toggle(): RedirectResponse
    {
        DevAuth::toggle();

        return back();
    }
}
