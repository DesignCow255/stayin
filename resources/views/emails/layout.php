<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($subject ?? 'StayIn') ?></title>
  <style>
    body { margin: 0; padding: 2rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; }
    .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .header { color: #2563eb; font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; }
    .button { display: inline-block; background: #2563eb; color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 1rem 0; }
    .footer { font-size: 0.875rem; color: #666; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">StayIn</div>
    <p>Hi <?= e($user['name'] ?? 'there') ?>,</p>
    <?= $body ?? '' ?>
    <?php if (!empty($action_url)): ?>
      <div style="text-align: center;">
        <a href="<?= e($action_url) ?>" class="button"><?= e($action_label ?? 'Continue') ?></a>
      </div>
    <?php endif; ?>
    <div class="footer">
      © <?= date('Y') ?> StayIn. All rights reserved.<br>
      If you didn't request this, please ignore this email.
    </div>
  </div>
</body>
</html>