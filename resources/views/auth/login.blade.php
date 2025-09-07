@extends('layouts.app', ['title' => 'Login'])

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded-lg p-6">
  <h1 class="text-xl font-semibold mb-4">Login</h1>

  @if ($errors->any())
    <div class="mb-3 rounded p-3 text-sm bg-red-50 text-red-700">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('login.do') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm mb-1">Username</label>
      <input type="text" name="username" value="{{ old('username') }}" required
             class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
    </div>
    <div>
      <label class="block text-sm mb-1">Password</label>
      <input type="password" name="password" required
             class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
    </div>
    <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Masuk</button>
  </form>
</div>
@endsection
