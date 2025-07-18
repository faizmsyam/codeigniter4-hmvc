<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?php echo esc($meta['title'] ?? lang('Errors.badRequest')) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --color-primary: #2563eb;
      --color-bg: #f9fafb;
      --color-text: #374151;
      --color-muted: #9ca3af;
      --color-danger: #ef4444;
      --radius: 1rem;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: system-ui, sans-serif;
      background-color: var(--color-bg);
      color: var(--color-text);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
    }

    .container {
      max-width: 500px;
      width: 100%;
      background: #fff;
      border-radius: var(--radius);
      padding: 2rem;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
      text-align: center;
    }

    .status {
      font-size: 4rem;
      font-weight: 700;
      color: var(--color-danger);
      margin-bottom: 0.5rem;
    }

    .title {
      font-size: 1.75rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
    }

    .message {
      font-size: 1rem;
      color: var(--color-muted);
      white-space: pre-wrap;
    }

    .footer {
      margin-top: 2rem;
      font-size: 0.875rem;
      color: var(--color-muted);
    }

    a {
      color: var(--color-primary);
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="status">400</div>
    <div class="title"><?php echo lang('Errors.badRequest') ?></div>
    <div class="message">
      <?php if (ENVIRONMENT !== 'production') : ?>
        <?php echo nl2br(esc($message)) ?>
      <?php else : ?>
        <?php echo lang('Errors.sorryBadRequest') ?>
      <?php endif; ?>
    </div>

    <div class="footer">
      &larr; <a href="<?php echo base_url() ?>">Kembali ke Beranda</a>
    </div>
  </div>
</body>

</html>