<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $staffUsers = User::query()
            ->whereIn('role', [UserRole::Approver->value, UserRole::Moderator->value])
            ->orderBy('username')
            ->get();

        return view('pages.admin.roles', compact('staffUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'role' => [
                'required',
                Rule::in([
                    UserRole::Approver->value,
                    UserRole::Moderator->value,
                    UserRole::Member->value,
                ]),
            ],
        ]);

        $user = User::where('username', $validated['username'])->firstOrFail();
        $user->role = UserRole::from($validated['role']);
        $user->save();

        return redirect()->route('admin.roles.index')
            ->with('status', "{$user->username} now has the {$user->role->label()} role.");
    }

    private function ensureAdmin(Request $request): void
    {
        $adminEmail = config('admin.email');

        if (! is_string($adminEmail) || $adminEmail === '' || $request->user()?->email !== $adminEmail) {
            throw new NotFoundHttpException;
        }
    }
}
