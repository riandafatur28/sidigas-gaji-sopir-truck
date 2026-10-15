<x-layouts.dashboard :title="'Kelola Admin'" :pageTitle="'Kelola Admin'">

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Admin</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Tambah, ubah, dan hapus akun admin. Hanya superadmin yang dapat mengakses halaman ini.</p>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error mb-4">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-error mb-4"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    {{-- STATS --}}
    <div class="card mb-6">
        <div class="grid grid-cols-2">
            <div style="padding:15px 20px" class="ledger-cell sm:border-r">
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL ADMIN</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25">{{ $totalAdmin }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">terdaftar</p>
            </div>
            <div style="padding:15px 20px" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SUPERADMIN</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25">{{ $totalSuperadmin }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">akses penuh</p>
            </div>
        </div>
    </div>

    {{-- FORM TAMBAH ADMIN --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Tambah Admin Baru</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama <span class="text-red-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Nama lengkap" minlength="3">
                    </div>
                    <div>
                        <label class="form-label">Email <span class="text-red-600">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="form-label">Password <span class="text-red-600">*</span></label>
                        <input type="password" name="password" required class="form-input" placeholder="Minimal 8 karakter" minlength="8">
                    </div>
                    <div>
                        <label class="form-label">Role <span class="text-red-600">*</span></label>
                        <select name="role" required class="form-input bg-white">
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="superadmin" {{ old('role') === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span>Tambah</span>
                </button>
            </form>
        </div>
    </div>

    {{-- TABEL ADMIN --}}
    <div class="card mb-6">
        <div class="border-b border-gray-200 px-5 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Admin</h3>
                <form method="GET" action="{{ route('admin.index') }}" class="relative w-full sm:w-64">
                    <input type="text" name="search" value="{{ $search }}" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded text-sm bg-white" placeholder="Cari nama atau email..." autocomplete="off">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="w-full">
                <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"><span class="text-gray-700 font-bold text-xs">{{ substr($admin->name, 0, 1) }}</span></div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $admin->name }}{{ $admin->id === auth()->id() ? ' (Anda)' : '' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $admin->email }}</td>
                            <td class="px-4 py-3">
                                @if($admin->role === 'superadmin')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-purple-50 text-purple-600 text-xs font-semibold">Superadmin</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-50 text-green-600 text-xs font-semibold">Admin</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <button onclick="openEditAdmin({{ $admin->id }})" data-name="{{ $admin->name }}" data-email="{{ $admin->email }}" data-role="{{ $admin->role }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-200 rounded hover:bg-gray-50">Edit</button>
                                @if($admin->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.destroy', $admin->id) }}" class="inline" onsubmit="return confirm('Hapus admin {{ $admin->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded hover:bg-red-50">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">Belum ada data admin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 px-5 pt-3">Menampilkan {{ $admins->firstItem() ?? 0 }} - {{ $admins->lastItem() ?? 0 }} dari {{ $admins->total() }} data</p>
        <x-shared.pagination :paginator="$admins" />
    </div>

    {{-- MODAL EDIT --}}
    <div id="editAdminModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Edit Admin</h3>
                <button onclick="closeEditAdmin()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form id="editAdminForm" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="form-label">Nama <span class="text-red-600">*</span></label>
                    <input type="text" id="edit_name" name="name" required class="form-input" minlength="3">
                </div>
                <div>
                    <label class="form-label">Email <span class="text-red-600">*</span></label>
                    <input type="email" id="edit_email" name="email" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Password Baru <span class="text-xs text-gray-400">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" id="edit_password" name="password" class="form-input" placeholder="Minimal 8 karakter" minlength="8">
                </div>
                <div>
                    <label class="form-label">Role <span class="text-red-600">*</span></label>
                    <select id="edit_role" name="role" required class="form-input bg-white">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditAdmin()" class="px-4 py-2 text-sm border border-gray-200 rounded hover:bg-gray-50">Batal</button>
                    <button type="submit" class="btn btn-primary"><span>Simpan</span></button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditAdmin(id){
        var btn = document.querySelector('button[onclick="openEditAdmin(' + id + ')"]');
        document.getElementById('editAdminForm').action = '{{ url('/admin') }}/' + id;
        document.getElementById('edit_name').value = btn ? btn.getAttribute('data-name') : '';
        document.getElementById('edit_email').value = btn ? btn.getAttribute('data-email') : '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role').value = btn ? btn.getAttribute('data-role') : 'admin';
        var m = document.getElementById('editAdminModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeEditAdmin(){
        var m = document.getElementById('editAdminModal');
        m.classList.remove('flex');
        m.classList.add('hidden');
    }
    document.getElementById('editAdminModal').addEventListener('click', function(e){
        if (e.target === this) closeEditAdmin();
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') closeEditAdmin();
    });
    </script>

</x-layouts.dashboard>
