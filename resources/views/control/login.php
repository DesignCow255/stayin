<?php

declare(strict_types=1);

use App\Core\View;

View::start('content');
?>

<section class="si-auth-shell">
    <div class="si-auth-card">
        <div class="si-auth-card__header">
            <p class="si-eyebrow">StayIn Control</p>
            <h1>Administrative access</h1>
            <p>
                Sign in with an authorized administrative account.
            </p>
        </div>

        <form method="POST"
              action="<?= e(url('/control/login')) ?>"
              class="si-form"
              autocomplete="on">

            <?= csrf_field() ?>

            <div class="si-field">
                <label for="control-email">Email address</label>
                <input
                    id="control-email"
                    type="email"
                    name="email"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="si-field">
                <label for="control-password">Password</label>
                <input
                    id="control-password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="si-btn si-btn--primary si-btn--block">
                Sign in to Control
            </button>
        </form>

        <p class="si-caption">
            Restricted system. Authorized administrative personnel only.
        </p>
    </div>
</section>

<?php View::stop(); ?>
