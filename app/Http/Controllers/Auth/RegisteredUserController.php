<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_type' => 'required|in:create,join,individual',
            'company_name' => 'required_if:company_type,create|string|max:255',
            'company_code' => 'required_if:company_type,join|string|size:8|exists:taskit_companies,code',
        ]);

        $companyId = null;

        if ($request->company_type === 'create') {
            // Create new company
            $company = Company::create([
                'name' => $request->company_name,
            ]);
            $companyId = $company->id;
        } elseif ($request->company_type === 'join') {
            // Find existing company by code
            $company = Company::where('code', strtoupper($request->company_code))->first();
            $companyId = $company->id;
        } elseif ($request->company_type === 'individual') {
            // Individual user - no company association
            $companyId = null;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $companyId,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
