<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flouci Sandbox</title>
    <style>
        :root {
            --bg: #f5f1e8;
            --panel: #fffdf8;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d6cbb8;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --error: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.14), transparent 26%),
                radial-gradient(circle at bottom right, rgba(180, 83, 9, 0.12), transparent 24%),
                linear-gradient(135deg, #f8f3ea, #efe7d7);
        }

        .wrap {
            width: min(760px, calc(100% - 32px));
            margin: 48px auto;
        }

        .card {
            background: rgba(255, 253, 248, 0.92);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 18px 50px rgba(31, 41, 55, 0.08);
        }

        .eyebrow {
            margin: 0 0 12px;
            color: var(--accent);
            font-size: 0.85rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 5vw, 3.6rem);
            line-height: 0.95;
        }

        p {
            color: var(--muted);
            line-height: 1.7;
        }

        form {
            display: grid;
            gap: 16px;
            margin-top: 28px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.95rem;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            font: inherit;
        }

        button {
            width: fit-content;
            padding: 14px 22px;
            border: 0;
            border-radius: 999px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .helper,
        .alert {
            margin-top: 20px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid var(--line);
        }

        .helper { background: rgba(15, 118, 110, 0.06); }
        .alert {
            background: rgba(185, 28, 28, 0.08);
            color: var(--error);
            border-color: rgba(185, 28, 28, 0.2);
        }

        code {
            padding: 0.1rem 0.35rem;
            background: rgba(17, 24, 39, 0.06);
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <p class="eyebrow">Flouci Sandbox</p>
            <h1>Tester un paiement sandbox depuis le workbench</h1>
            <p>
                Cette interface vit dans le workbench du package. Elle est utile pour verifier l'integration
                localement sans transformer le package en application Laravel complete.
            </p>

            @if (isset($errors) && $errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('flouci.sandbox.checkout') }}">
                @csrf

                <div>
                    <label for="amount">Montant en millimes</label>
                    <input id="amount" name="amount" type="number" min="1" value="{{ old('amount', $defaultAmount) }}" required>
                </div>

                <div>
                    <label for="developer_tracking_id">Tracking ID developpeur</label>
                    <input id="developer_tracking_id" name="developer_tracking_id" type="text" value="{{ old('developer_tracking_id') }}" placeholder="order_1001">
                </div>

                <button type="submit">Lancer le paiement Flouci</button>
            </form>

            <div class="helper">
                Webhook workbench: <code>{{ $webhookUrl }}</code>
            </div>
        </section>
    </main>
</body>
</html>


