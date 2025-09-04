<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\StripeService;
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
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        $subscriptionType = $request->query('plan', 'FREE');
        
        // Validate subscription type
        if (!in_array($subscriptionType, ['FREE', 'MIDI', 'MAXI'])) {
            $subscriptionType = 'FREE';
        }
        
        return Inertia::render('auth/Register', [
            'subscriptionType' => $subscriptionType
        ]);
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
            'subscription_type' => 'required|in:FREE,MIDI,MAXI',
        ]);

        $companyId = null;

        if ($request->company_type === 'create') {
            // Create new company with subscription type
            $company = Company::create([
                'name' => $request->company_name,
                'subscription_type' => $request->subscription_type,
            ]);
            $companyId = $company->id;
        } elseif ($request->company_type === 'join') {
            // Find existing company by code and update subscription type
            $company = Company::where('code', strtoupper($request->company_code))->first();
            $company->update(['subscription_type' => $request->subscription_type]);
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

        // Handle paid subscriptions
        if (in_array($request->subscription_type, ['MIDI', 'MAXI']) && $companyId) {
            try {
                $company = Company::find($companyId);
                $session = $this->stripeService->createCheckoutSession(
                    $company,
                    $request->subscription_type,
                    $user->email,
                    route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    route('subscription.cancel')
                );

                // Store the intended subscription type
                $company->update(['subscription_type' => $request->subscription_type]);

                // Use Inertia's external redirect for Stripe checkout
                return Inertia::location($session->url);
            } catch (\Exception $e) {
                // If Stripe fails, continue to dashboard with FREE plan
                return to_route('dashboard')->with('error', 'Payment setup failed. You have been registered with a FREE plan.');
            }
        }

        return to_route('dashboard');
    }
}
