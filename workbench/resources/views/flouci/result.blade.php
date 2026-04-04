<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flouci Payment Result</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: #1f2937;
            background: linear-gradient(180deg, #f7f3ea, #efe6d3);
        }

        .wrap {
            width: min(760px, calc(100% - 32px));
            margin: 48px auto;
        }

        .card {
            background: #fffdfa;
            border: 1px solid #d6cbb8;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 18px 50px rgba(31, 41, 55, 0.08);
        }

        .badge {
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            color: #fff;
            background: {{ $status === 'success' ? "'#166534'" : "'#b91c1c'" }};
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel {
            margin-top: 22px;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #d6cbb8;
            background: rgba(255, 255, 255, 0.7);
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <span class="badge">{{ $status }}</span>
            <h1>Retour de paiement Flouci</h1>

            <div class="panel">
                <strong>Payment ID:</strong> {{ $paymentId !== '' ? $paymentId : 'non fourni' }}
            </div>

            @if ($error)
                <div class="panel">
                    <strong>Erreur:</strong>
                    <pre>{{ $error }}</pre>
                </div>
            @endif

            @if ($verification)
                <div class="panel">
                    <strong>Verification API:</strong>
                    <pre>{{ json_encode($verification, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
