<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends AdminController
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::query()->with('roles')->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);
        $user->roles()->sync([$data['role_id']]);

        return back()->with('status', 'Akun panitia ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->is($request->user()) && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang dipakai.']);
        }

        if ($this->removesLastSuperAdmin($user, (int) $data['role_id'], $request->boolean('is_active'))) {
            return back()->withErrors(['role_id' => 'Harus ada minimal satu Super Admin yang aktif.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->roles()->sync([$data['role_id']]);

        return back()->with('status', 'Akun panitia diperbarui.');
    }

    private function removesLastSuperAdmin(User $user, int $roleId, bool $active): bool
    {
        $super = Role::query()->where('slug', 'super-admin')->first();
        if (! $super || ! $user->roles->contains('id', $super->id)) {
            return false;
        }

        $stillSuper = $active && $roleId === $super->id;
        if ($stillSuper) {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $super->id))
            ->doesntExist();
    }
}
