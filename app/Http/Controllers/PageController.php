<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function search(): View
    {
        return view('pages.search');
    }

    public function feed(): View
    {
        return view('pages.feed');
    }

    public function list(): View
    {
        return view('pages.list');
    }

    public function profile(): View
    {
        return view('pages.profile');
    }

    public function settings(): View
    {
        return view('pages.settings');
    }

    public function notifications(): View
    {
        return view('pages.notifications');
    }
}
