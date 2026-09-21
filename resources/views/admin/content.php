<?php

declare(strict_types=1);

$slides = $slides ?? [];
$pages = $pages ?? [];
?>

<section class="si-section">
  <div class="si-container">

    <div class="si-stack si-stack--sm">
      <span class="si-eyebrow">Administration</span>
      <h1>Content Studio</h1>
      <p>Manage website pages and homepage hero content.</p>
    </div>

    <?php if (!empty($status['message'])): ?>
      <div class="si-alert si-alert--success">
        <?= e((string) $status['message']) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="si-alert si-alert--error">
        <strong>Please review the form.</strong>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= e(is_array($error) ? implode(', ', $error) : (string) $error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>


    <!-- =========================================================
         WEBSITE PAGES
         ========================================================= -->

    <section class="si-section">
      <div class="si-stack si-stack--xs">
        <span class="si-eyebrow">Website</span>
        <h2>Pages</h2>
        <p>Edit existing website page content.</p>
      </div>

      <?php if ($pages === []): ?>

        <div class="si-card">
          <div class="si-card__body">
            <p>No editable pages were found.</p>
          </div>
        </div>

      <?php else: ?>

        <div class="si-stack">
          <?php foreach ($pages as $page): ?>

            <article class="si-card">
              <div class="si-card__body">

                <form method="POST" action="<?= e(url('/admin/content')) ?>" class="si-stack">

                  <?= csrf_field() ?>

                  <input type="hidden" name="kind" value="page">
                  <input type="hidden" name="id" value="<?= e((string) ($page['id'] ?? '')) ?>">

                  <div class="si-grid si-grid--2">

                    <label class="si-field">
                      <span class="si-field__label">English title</span>
                      <input
                        class="si-input"
                        type="text"
                        name="title_en"
                        maxlength="255"
                        required
                        value="<?= e((string) ($page['title_en'] ?? '')) ?>"
                      >
                    </label>

                    <label class="si-field">
                      <span class="si-field__label">Swahili title</span>
                      <input
                        class="si-input"
                        type="text"
                        name="title_sw"
                        maxlength="255"
                        required
                        value="<?= e((string) ($page['title_sw'] ?? '')) ?>"
                      >
                    </label>

                  </div>

                  <label class="si-field">
                    <span class="si-field__label">English content</span>
                    <textarea
                      class="si-input"
                      name="body_en"
                      rows="10"
                      maxlength="30000"
                      required
                    ><?= e((string) ($page['body_en'] ?? '')) ?></textarea>
                  </label>

                  <label class="si-field">
                    <span class="si-field__label">Swahili content</span>
                    <textarea
                      class="si-input"
                      name="body_sw"
                      rows="10"
                      maxlength="30000"
                    ><?= e((string) ($page['body_sw'] ?? '')) ?></textarea>
                  </label>

                  <label class="si-field">
                    <span class="si-field__label">Status</span>

                    <?php $pageStatus = (string) ($page['status'] ?? 'draft'); ?>

                    <select class="si-input" name="status" required>
                      <option value="draft"<?= $pageStatus === 'draft' ? ' selected' : '' ?>>
                        Draft
                      </option>
                      <option value="published"<?= $pageStatus === 'published' ? ' selected' : '' ?>>
                        Published
                      </option>
                    </select>
                  </label>

                  <div>
                    <button class="si-btn si-btn--primary" type="submit">
                      Save page
                    </button>
                  </div>

                </form>

              </div>
            </article>

          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </section>


    <!-- =========================================================
         HOMEPAGE HERO
         ========================================================= -->

    <section class="si-section">

      <div class="si-stack si-stack--xs">
        <span class="si-eyebrow">Homepage</span>
        <h2>Hero Slides</h2>
        <p>Manage existing homepage hero slides and calls to action.</p>
      </div>

      <?php if ($slides === []): ?>

        <div class="si-card">
          <div class="si-card__body">
            <p>No hero slides were found.</p>
          </div>
        </div>

      <?php else: ?>

        <div class="si-stack">

          <?php foreach ($slides as $slide): ?>

            <article class="si-card">
              <div class="si-card__body">

                <form method="POST" action="<?= e(url('/admin/content')) ?>" class="si-stack">

                  <?= csrf_field() ?>

                  <input type="hidden" name="kind" value="hero">
                  <input type="hidden" name="id" value="<?= e((string) ($slide['id'] ?? '')) ?>">

                  <div class="si-grid si-grid--2">

                    <label class="si-field">
                      <span class="si-field__label">English title</span>
                      <input
                        class="si-input"
                        name="title_en"
                        maxlength="255"
                        required
                        value="<?= e((string) ($slide['title_en'] ?? '')) ?>"
                      >
                    </label>

                    <label class="si-field">
                      <span class="si-field__label">Swahili title</span>
                      <input
                        class="si-input"
                        name="title_sw"
                        maxlength="255"
                        required
                        value="<?= e((string) ($slide['title_sw'] ?? '')) ?>"
                      >
                    </label>

                  </div>


                  <div class="si-grid si-grid--2">

                    <label class="si-field">
                      <span class="si-field__label">English description</span>
                      <textarea
                        class="si-input"
                        name="description_en"
                        rows="4"
                        maxlength="500"
                      ><?= e((string) ($slide['description_en'] ?? '')) ?></textarea>
                    </label>

                    <label class="si-field">
                      <span class="si-field__label">Swahili description</span>
                      <textarea
                        class="si-input"
                        name="description_sw"
                        rows="4"
                        maxlength="500"
                      ><?= e((string) ($slide['description_sw'] ?? '')) ?></textarea>
                    </label>

                  </div>


                  <label class="si-field">
                    <span class="si-field__label">Image URL</span>
                    <input
                      class="si-input"
                      type="url"
                      name="image_url"
                      maxlength="500"
                      required
                      value="<?= e((string) ($slide['image_url'] ?? '')) ?>"
                    >
                  </label>


                  <div class="si-grid si-grid--2">

                    <label class="si-field">
                      <span class="si-field__label">English CTA text</span>
                      <input
                        class="si-input"
                        name="cta_text_en"
                        maxlength="100"
                        required
                        value="<?= e((string) ($slide['cta_text_en'] ?? '')) ?>"
                      >
                    </label>

                    <label class="si-field">
                      <span class="si-field__label">Swahili CTA text</span>
                      <input
                        class="si-input"
                        name="cta_text_sw"
                        maxlength="100"
                        required
                        value="<?= e((string) ($slide['cta_text_sw'] ?? '')) ?>"
                      >
                    </label>

                  </div>


                  <label class="si-field">
                    <span class="si-field__label">CTA destination</span>
                    <input
                      class="si-input"
                      name="cta_link"
                      maxlength="500"
                      required
                      placeholder="/search"
                      value="<?= e((string) ($slide['cta_link'] ?? '')) ?>"
                    >
                  </label>


                  <div class="si-grid si-grid--2">

                    <label class="si-field">
                      <span class="si-field__label">Display order</span>
                      <input
                        class="si-input"
                        type="number"
                        name="display_order"
                        min="0"
                        max="100"
                        required
                        value="<?= e((string) ($slide['display_order'] ?? '0')) ?>"
                      >
                    </label>

                    <label class="si-field">
                      <span class="si-field__label">Status</span>

                      <?php $active = (string) ($slide['is_active'] ?? '0'); ?>

                      <select class="si-input" name="is_active" required>
                        <option value="1"<?= $active === '1' ? ' selected' : '' ?>>
                          Active
                        </option>
                        <option value="0"<?= $active === '0' ? ' selected' : '' ?>>
                          Inactive
                        </option>
                      </select>
                    </label>

                  </div>


                  <div>
                    <button class="si-btn si-btn--primary" type="submit">
                      Save hero slide
                    </button>
                  </div>

                </form>

              </div>
            </article>

          <?php endforeach; ?>

        </div>

      <?php endif; ?>

    </section>

  </div>
</section>
