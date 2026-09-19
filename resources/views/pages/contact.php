<?php
use App\Core\View;
/** @var string $csrfToken */
/** @var bool $errors */
View::start('content');
?>
<section class="page-section">
  <div class="container">
    <h1>Contact StayIn</h1>
    <p class="text-muted">We'd love to hear from you.</p>
    <?php if (!empty($errors)): ?>
      <div class="alert alert--error"><?= e(implode(' ', (array) $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="/contact" class="contact-form" data-validate style="max-width:var(--container-sm);">
      <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
      <div class="form-row"><input type="text" name="name" required placeholder="Your name" class="input"></div>
      <div class="form-row"><input type="email" name="email" required placeholder="Your email" class="input"></div>
      <div class="form-row"><textarea name="message" required rows="5" placeholder="Your message" class="input"></textarea></div>
      <button type="submit" class="btn btn--primary"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send message</button>
    </form>
    <p style="margin-top:var(--space-4);"><i class="fa-solid fa-whatsapp" aria-hidden="true"></i> WhatsApp: <a href="https://wa.me/255000000000">Click to chat</a></p>
  </div>
</section>
<?php View::stop() ?>
