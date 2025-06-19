<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class CollaboratorRegistrationController extends Controller
{
    public function create()
    {
        return view('collaborator-register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'collaborateur',
            'fonction' => 'Collaborateur', // Default fonction
            'status' => 'actif', // Default status
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('filament.admin.pages.dashboard'));
    }
}
