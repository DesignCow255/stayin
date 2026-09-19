<?php
/** @var string $__code
 *  @var string $__name
 *  @var string $__email
 *  @var string $__url
 */
ob_start();
$body = '<p>Your verification code is:</p><p style="font-size:2rem;font-weight:700;letter-spacing:0.25em;color:#2563eb;background:#eff6ff;padding:1rem;border-radius:8px;text-align:center;margin:1.5rem 0;">' . e($__code) . '</p><p style="font-size:0.875rem;color:#666;">This code expires in 15 minutes.</p>';
?>
<?= View::fragment('emails/layout', compact('body', 'subject', 'user')) ?>