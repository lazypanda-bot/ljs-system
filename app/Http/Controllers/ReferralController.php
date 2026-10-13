<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use Illuminate\Http\RedirectResponse;

class ReferralController extends Controller
{
    public function open(string $code): RedirectResponse
    {
        $referralCode = ReferralCode::query()
            ->with(['property', 'user'])
            ->where('code', $code)
            ->firstOrFail();

        abort_unless(
            $referralCode->property?->approval_status === 'Approved'
                && ! in_array($referralCode->property?->property_status, ['Sold', 'Unavailable'], true)
                && $referralCode->user?->account_status === 'Active',
            404
        );

        return redirect()->route('properties.show', [
            'id' => $referralCode->property->property_id,
            'ref' => $referralCode->code,
        ]);
    }
}
