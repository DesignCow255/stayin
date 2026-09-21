<?php

declare(strict_types=1);

$accounts = $accounts ?? [];
?>

<section class="si-section">
  <div class="si-container">

    <div class="si-stack">
      <div>
        <span class="si-eyebrow">StayIn Control</span>
        <h1>Administrative Accounts</h1>
        <p>
          Manage the privileged accounts that can access the StayIn
          administrative environment.
        </p>
      </div>

      <?php if (!empty($status['message'])): ?>
        <div class="si-alert si-alert--success">
          <?= e((string) $status['message']) ?>
        </div>
      <?php endif; ?>

      <div>
        <a
          class="si-btn si-btn--primary"
          href="<?= e(url('/control/accounts/create')) ?>"
        >
          Create administrative account
        </a>

        <a
          class="si-btn"
          href="<?= e(url('/admin')) ?>"
        >
          Control centre
        </a>
      </div>
    </div>

    <section class="si-section">

      <div class="si-stack si-stack--xs">
        <span class="si-eyebrow">Security</span>
        <h2>Privileged Users</h2>
        <p>
          Only Administrator and Super Administrator accounts are shown here.
        </p>
      </div>

      <?php if ($accounts === []): ?>

        <div class="si-card">
          <div class="si-card__body">
            <p>No administrative accounts were found.</p>
          </div>
        </div>

      <?php else: ?>

        <div style="overflow-x:auto">

          <table class="si-table">
            <thead>
              <tr>
                <th scope="col">Account</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Verification</th>
                <th scope="col">Last login</th>
                <th scope="col">Created</th>
              </tr>
            </thead>

            <tbody>

              <?php foreach ($accounts as $account): ?>

                <?php
                $role = (string) ($account['role'] ?? '');
                $statusValue = (string) ($account['status'] ?? '');

                $name = trim(
                    (string) ($account['first_name'] ?? '') .
                    ' ' .
                    (string) ($account['last_name'] ?? '')
                );
                ?>

                <tr>

                  <td>
                    <strong><?= e($name !== '' ? $name : 'Administrative user') ?></strong>
                    <div>
                      <?= e((string) ($account['email'] ?? '')) ?>
                    </div>
                  </td>

                  <td>
                    <?= e(
                        $role === 'super_admin'
                            ? 'Super Administrator'
                            : 'Administrator'
                    ) ?>
                  </td>

                  <td>
                    <?= e(ucfirst($statusValue)) ?>
                  </td>

                  <td>
                    <?= !empty($account['email_verified_at'])
                        ? 'Verified'
                        : 'Not verified' ?>
                  </td>

                  <td>
                    <?= e(
                        !empty($account['last_login_at'])
                            ? (string) $account['last_login_at']
                            : 'Never'
                    ) ?>
                  </td>

                  <td>
                    <?= e((string) ($account['created_at'] ?? '')) ?>
                  </td>

                </tr>

              <?php endforeach; ?>

            </tbody>
          </table>

        </div>

      <?php endif; ?>

    </section>

    <div class="si-card">
      <div class="si-card__body">
        <strong>Security notice</strong>
        <p>
          Administrative access should only be issued to authorised personnel.
          Super Administrator accounts have unrestricted platform privileges.
        </p>
      </div>
    </div>

  </div>
</section>
