<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $admins = User::when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $totalAdmin = User::count();
        $totalSuperadmin = User::where('role', 'superadmin')->count();

        return view('admins.index', compact('admins', 'search', 'totalAdmin', 'totalSuperadmin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,superadmin',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
        ]);

        return redirect()->back()->with('success', 'Admin berhasil ditambahkan!');
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,superadmin',
        ]);

        if ($user->id === auth()->id() && $request->role !== 'superadmin') {
            return redirect()->back()->with('error', 'Tidak dapat menurunkan role akun sendiri!');
        }

        if ($user->role === 'superadmin' && $request->role !== 'superadmin'
            && User::where('role', 'superadmin')->count() <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada 1 superadmin!');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Data admin berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus akun sendiri!');
        }

        if ($user->role === 'superadmin' && User::where('role', 'superadmin')->count() <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada 1 superadmin!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Data admin berhasil dihapus!');
    }
}
