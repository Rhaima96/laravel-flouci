# Laravel Flouci

[![Tests](https://github.com/Rhaima96/laravel-flouci/actions/workflows/tests.yml/badge.svg)](https://github.com/Rhaima96/laravel-flouci/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)
[![Total Downloads](https://img.shields.io/packagist/dt/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)
[![License](https://img.shields.io/packagist/l/rhaima/laravel-flouci.svg)](https://packagist.org/packages/rhaima/laravel-flouci)

`rhaima/laravel-flouci` est un package Laravel pour integrer Flouci dans des applications tunisiennes.

## Compatibilite

- Laravel 12
- Laravel 13
- PHP 8.2+

## Installation

```bash
composer require rhaima/laravel-flouci
```

Le package utilise l'auto-discovery Laravel. Si tu preferes une declaration manuelle, ajoute le provider suivant:

```php
Flouci\Laravel\FlouciServiceProvider::class,
```

## Configuration

Publier la configuration:

```bash
php artisan vendor:publish --tag=flouci-config
```

Variables attendues:

```env
FLOUCI_BASE_URL=https://developers.flouci.com/api
FLOUCI_PUBLIC_KEY=
FLOUCI_PRIVATE_KEY=
FLOUCI_SUCCESS_LINK=${APP_URL}/payment/success
FLOUCI_FAIL_LINK=${APP_URL}/payment/fail
FLOUCI_CARD_PAYMENT=true
FLOUCI_IMAGE_URL=
FLOUCI_TIMEOUT=15
FLOUCI_WEBHOOK_URL=
FLOUCI_SESSION_TIMEOUT=
FLOUCI_MERCHANT_ID=
```

## Options de configuration

- `base_url`: URL de base de l'API Flouci
- `public_key`: cle publique Flouci
- `private_key`: cle privee Flouci
- `success_link`: URL de retour en cas de succes
- `fail_link`: URL de retour en cas d'echec
- `card_payment`: valeur par defaut envoyee comme `accept_card` lors de `generatePayment()`
- `image_url`: URL d'image par defaut envoyee lors de `generatePayment()`
- `timeout`: timeout HTTP en secondes (defaut: 15)
- `webhook`: URL de webhook par defaut envoyee lors de `generatePayment()`
- `session_timeout`: duree de la session de paiement en secondes, envoyee comme `session_timeout_secs` (defaut Flouci: 1200)
- `merchant_id`: identifiant marchand utilise par defaut par `transactionHistory()`

## Utilisation

```php
use Flouci\Laravel\Facades\Flouci;

$payment = Flouci::generatePayment([
    'amount' => 10000,
    'developer_tracking_id' => 'order_1001',
]);

return redirect()->away($payment['result']['link']);
```

Verifier un paiement (page de retour ou webhook):

```php
use Flouci\Laravel\Enums\PaymentStatus;

$verification = Flouci::verifyPayment($paymentId);
$status = PaymentStatus::fromVerification($verification);

if ($status?->isPaid()) {
    // marquer la commande comme payee
}
```

`PaymentStatus`: `Success`, `Pending`, `Expired`, `Failure`, `PreauthSuccess`, `SystemFailure`.
`isFinal()` renvoie `false` pour `Pending` et `PreauthSuccess`.

Pour forcer des valeurs sur un appel precis:

```php
$payment = Flouci::generatePayment([
    'amount' => 10000,
    'developer_tracking_id' => 'order_1002',
    'accept_card' => false,
    'image_url' => 'https://example.com/logo.png',
]);
```

Le montant `amount` est exprime en **millimes** (`10000` = 10 TND).

## Remboursement

```php
$refund = Flouci::refund($paymentId); // remboursement total
```

Flouci ne rembourse que les paiements termines et pas encore rembourses. Une erreur de remboursement leve
toujours une `FlouciException`, meme si Flouci repond en HTTP 200 avec `"status": "error"`.

## Historique des transactions

```php
$history = Flouci::transactionHistory([
    'start_date' => '2026-09-01T00:00:00Z', // ISO-8601
    'end_date' => '2026-09-30T23:59:59Z',
    'type' => 'online',                     // ou pos
]);
```

Les parametres sont transmis tels quels a `GET /api/developers/history`
([doc](https://docs.flouci.com/api-reference/transaction-history)). `merchant_id` est requis:
passe-le dans la requete ou via `FLOUCI_MERCHANT_ID`.

## Webhook

Enregistrer la route (dans `routes/web.php` ou `routes/api.php`, la protection CSRF est retiree automatiquement):

```php
Route::flouciWebhook();                        // POST /flouci/webhook, nommee flouci.webhook
Route::flouciWebhook('payments/flouci/hook');  // URI personnalisee
Route::flouciWebhook()->middleware('throttle:60,1');
```

Puis pointer `FLOUCI_WEBHOOK_URL` vers cette URL (ou passer `webhook` a `generatePayment()`).

Flouci ne signe pas ses webhooks: le package ne fait confiance qu'au `payment_id` recu, et lit toujours
le statut via `verifyPayment()`. Il declenche ensuite un event:

| Statut Flouci | Event |
|---|---|
| `SUCCESS` | `Flouci\Laravel\Events\PaymentSucceeded` |
| `FAILURE`, `SYSTEM_FAILURE` | `Flouci\Laravel\Events\PaymentFailed` |
| `EXPIRED` | `Flouci\Laravel\Events\PaymentExpired` |
| `PENDING`, `PREAUTH_SUCCESS` | aucun |

```php
use Flouci\Laravel\Events\PaymentSucceeded;

Event::listen(function (PaymentSucceeded $event) {
    $order = Order::where('reference', $event->trackingId())->firstOrFail();

    if ($order->amount_millimes !== $event->amount()) {
        return; // montant inattendu: ne pas valider la commande
    }

    $order->markAsPaid($event->paymentId);
});
```

Chaque event expose `paymentId`, `status` (`PaymentStatus`), `verification` (reponse brute), `trackingId()` et `amount()`.
Toutes ces classes heritent de `PaymentEvent` pour ecouter tous les cas d'un coup.

A savoir:
- Un meme webhook rejoue ne declenche l'event qu'une fois (cle en cache pendant 24h). Utilise un store de cache
  partage (redis, database) en production, et garde un controle d'idempotence cote commande.
- Si l'API Flouci est injoignable pendant la verification, la route repond en 5xx.
- Sans `payment_id` dans le payload, la route repond `422`.

## Gestion des erreurs

Toute erreur (reponse HTTP en echec, timeout, reponse illisible) leve une `FlouciException`:

```php
use Flouci\Laravel\Exceptions\FlouciException;

try {
    $payment = Flouci::generatePayment(['amount' => 10000]);
} catch (FlouciException $e) {
    $e->getCode();                         // statut HTTP (0 si erreur reseau)
    $e->response?->json('result.message'); // corps de la reponse Flouci
}
```

## Developpement du package

Le depot contient maintenant:

- `src/` pour le code publiable du package
- `config/` pour la configuration publiee
- `tests/` pour les tests package-first avec Pest + Testbench
- `workbench/` pour les essais locaux (page sandbox)

Lancer les tests:

```bash
composer test
```

## References Flouci

- Introduction: https://docs.flouci.com/introduction
- Test environment: https://docs.flouci.com/essentials/testing
