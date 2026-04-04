<?php

namespace Workbench\App\Http\Controllers;

use Flouci\Laravel\Facades\Flouci;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlouciSandboxController
{
    public function index(): View
    {
        return view('flouci.sandbox', [
            'defaultAmount' => 1000,
            'webhookUrl' => route('flouci.webhook'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'developer_tracking_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = Flouci::generatePayment([
            'amount' => $validated['amount'],
            'developer_tracking_id' => $validated['developer_tracking_id'] ?: 'sandbox_'.now()->timestamp,
            'success_link' => route('flouci.sandbox.success'),
            'fail_link' => route('flouci.sandbox.fail'),
            'webhook' => route('flouci.webhook'),
            'accept_card' => true,
        ]);

        $paymentUrl = data_get($payment, 'result.link')
            ?? data_get($payment, 'result.payment_url')
            ?? data_get($payment, 'link')
            ?? data_get($payment, 'payment_url');

        abort_unless(is_string($paymentUrl) && $paymentUrl !== '', 500, 'Flouci payment URL was not returned.');

        return redirect()->away($paymentUrl);
    }

    public function success(Request $request): View
    {
        return $this->paymentResultView($request, 'success');
    }

    public function fail(Request $request): View
    {
        return $this->paymentResultView($request, 'fail');
    }

    protected function paymentResultView(Request $request, string $status): View
    {
        $paymentId = $request->string('payment_id')->toString();

        $verification = null;
        $error = null;

        if ($paymentId !== '') {
            try {
                $verification = Flouci::verifyPayment($paymentId);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        } else {
            $error = 'No payment_id was provided by the return URL.';
        }

        return view('flouci.result', [
            'status' => $status,
            'paymentId' => $paymentId,
            'verification' => $verification,
            'error' => $error,
        ]);
    }
}
