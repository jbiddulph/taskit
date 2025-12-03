<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RedemptionCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RedemptionController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('auth/LtdRedeem', [
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'code' => ['required', 'string'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $codeValue = trim($data['code']);

        $code = RedemptionCode::with('tier')
            ->where('code', $codeValue)
            ->lockForUpdate()
            ->first();

        if (! $code || ! $code->tier) {
            return back()->withErrors([
                'code' => 'This redemption code is invalid.',
            ])->withInput();
        }

        if ($code->redeemed) {
            return back()->withErrors([
                'code' => 'This redemption code has already been redeemed.',
            ])->withInput();
        }

        DB::transaction(function () use ($code, $user, $data) {
            $subscriptionType = $code->tier->subscription_type;

            // Ensure the user has a company for non-solo LTD tiers.
            if ($subscriptionType !== 'LTD_SOLO') {
                $company = $user->company;

                if (! $company) {
                    $companyName = $data['company_name'] ?? ($user->name . '\'s Company');

                    $company = Company::create([
                        'name' => $companyName,
                        'owner_id' => $user->id,
                        'subscription_type' => $subscriptionType,
                        'subscription_status' => 'active',
                    ]);

                    $user->company_id = $company->id;
                    $user->save();
                } else {
                    $company->subscription_type = $subscriptionType;
                    $company->subscription_status = 'active';
                    $company->save();
                }
            }

            $code->redeemed = true;
            $code->user_id = $user->id;
            $code->redeemed_at = now();
            $code->save();
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your lifetime deal has been successfully redeemed.');
    }
}


