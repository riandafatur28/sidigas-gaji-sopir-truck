<x-layouts.dashboard
    :title="'Profil'"
    :pageTitle="'Profil'"
    :user="auth()->user()">

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Profil</h1>
                <p class="text-base text-gray-500 mt-1">Informasi akun Anda</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded mb-4 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- INFO PROFIL --}}
        <div class="border border-gray-200 rounded bg-white overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Info Profil</p>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-xl font-bold text-gray-600">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                        <p class="text-sm text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Nama</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Email</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Bergabung</span>
                        <span class="text-sm font-medium text-gray-900">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- EDIT PROFIL --}}
        <div class="border border-gray-200 rounded bg-white overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Edit Profil</p>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('profil.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500">
                    </div>

                    <hr class="border-gray-200">

                    <p class="text-xs text-gray-400 font-medium">Kosongkan jika tidak ingin ganti password</p>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Lama</label>
                        <div class="relative">
                            <input type="password" name="password_lama" id="password_lama" placeholder="Password saat ini"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500">
                            <button type="button" onclick="togPass('password_lama',this)"
                                class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru</label>
                        <div class="relative">
                            <input type="password" name="password_baru" id="password_baru" placeholder="Minimal 6 karakter"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500">
                            <button type="button" onclick="togPass('password_baru',this)"
                                class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input type="password" name="password_baru_confirmation" id="password_baru_confirmation" placeholder="Ketik ulang password baru"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500">
                            <button type="button" onclick="togPass('password_baru_confirmation',this)"
                                class="toggle-pass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-gray-900 text-white rounded text-sm font-semibold px-5 py-3 hover:bg-gray-800 transition">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-layouts.dashboard>

@push('styles')
<style>
    .toggle-pass { cursor: pointer; user-select: none; }
</style>
@endpush

@push('scripts')
<script>
    function togPass(id, btn) {
        const inp = document.getElementById(id);
        const isPass = inp.type === 'password';
        inp.type = isPass ? 'text' : 'password';
        btn.innerHTML = isPass
            ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'
            : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
    }
</script>
@endpush
