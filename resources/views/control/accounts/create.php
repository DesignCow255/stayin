<?php

declare(strict_types=1);
?>

<section class="si-section">
  <div class="si-container">

    <div class="si-stack">

      <div>
        <span class="si-eyebrow">StayIn Control</span>
        <h1>Create Administrative Account</h1>
        <p>
          Create a privileged account for an authorised StayIn administrator.
        </p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="si-alert si-alert--error">
          <strong>Please review the information below.</strong>

          <ul>
            <?php foreach ($errors as $error): ?>
              <li>
                <?= e(
                    is_array($error)
                        ? implode(', ', $error)
                        : (string) $error
                ) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="si-card">

        <div class="si-card__body">

          <form
            method="POST"
            action="<?= e(url('/control/accounts')) ?>"
            class="si-stack"
            autocomplete="off"
          >

            <?= csrf_field() ?>

            <div class="si-grid si-grid--2">

              <label class="si-field">
                <span class="si-field__label">First name</span>

                <input
                  class="si-input"
                  type="text"
                  name="first_name"
                  maxlength="100"
                  autocomplete="off"
                  required
                >
              </label>

              <label class="si-field">
                <span class="si-field__label">Last name</span>

                <input
                  class="si-input"
                  type="text"
                  name="last_name"
                  maxlength="100"
                  autocomplete="off"
                  required
                >
              </label>

            </div>


            <label class="si-field">
              <span class="si-field__label">Email address</span>

              <input
                class="si-input"
                type="email"
                name="email"
                maxlength="190"
                autocomplete="off"
                required
              >
            </label>


            <label class="si-field">
              <span class="si-field__label">Administrative role</span>

              <select
                class="si-input"
                name="role"
                required
              >
                <option value="admin">
                  Administrator
                </option>

                <option value="super_admin">
                  Super Administrator
                </option>
              </select>
            </label>


            <div class="si-card">
              <div class="si-card__body">

                <strong>Role guidance</strong>

                <p>
                  Administrators can operate the administrative platform
                  according to their configured permissions.
                </p>

                <p>
                  Super Administrators have unrestricted platform privileges
                  and can create additional privileged accounts.
                </p>

              </div>
            </div>


            <div class="si-grid si-grid--2">

              <label class="si-field">
                <span class="si-field__label">Temporary password</span>

                <input
                  class="si-input"
                  type="password"
                  name="password"
                  minlength="12"
                  maxlength="255"
                  autocomplete="new-password"
                  required
                >
              </label>

              <label class="si-field">
                <span class="si-field__label">Confirm password</span>

                <input
                  class="si-input"
                  type="password"
                  name="password_confirmation"
                  minlength="12"
                  maxlength="255"
                  autocomplete="new-password"
                  required
                >
              </label>

            </div>

            <p>
              Passwords must contain at least 12 characters, including
              uppercase, lowercase and a number.
            </p>

            <div>
              <button
                class="si-btn si-btn--primary"
                type="submit"
              >
                Create administrative account
              </button>

              <a
                class="si-btn"
                href="<?= e(url('/control/accounts')) ?>"
              >
                Cancel
              </a>
            </div>

          </form>

        </div>
      </div>

    </div>

  </div>
</section>
