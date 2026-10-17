@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Akun panitia</h1>
    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-5">
        @csrf
        <input class="rounded-xl border px-3 py-2" name="name" placeholder="Nama" required>
        <input class="rounded-xl border px-3 py-2" type="email" name="email" placeholder="Email" required>
        <input class="rounded-xl border px-3 py-2" type="password" name="password" placeholder="Kata sandi" required>
        <select class="rounded-xl border px-3 py-2" name="role_id">
            @foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
        </select>
        <button class="rounded-xl bg-nu px-3 py-2 text-sm font-semibold text-white">Tambah</button>
    </form>
    <div class="mt-4 space-y-3">
        @foreach ($users as $user)
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-6">
                @csrf @method('PATCH')
                <input class="rounded-xl border px-3 py-2" name="name" value="{{ $user->name }}">
                <input class="rounded-xl border px-3 py-2" type="email" name="email" value="{{ $user->email }}">
                <input class="rounded-xl border px-3 py-2" name="phone" value="{{ $user->phone }}" placeholder="Telepon">
                <input class="rounded-xl border px-3 py-2" type="password" name="password" placeholder="Ganti sandi">
                <select class="rounded-xl border px-3 py-2" name="role_id">
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected($user->roles->contains('id', $role->id))>{{ $role->name }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Aktif</label>
                <button class="rounded-xl border px-3 py-2 text-sm md:col-span-6">Simpan</button>
            </form>
        @endforeach
    </div>
@endsection
