<?php
declare(strict_types=1);
// TypeForge post-auth transition: small video loading icon before dashboard.
// Used ONLY after successful login / register. Landing intro loader is separate.
function renderAuthTransition(string $targetUrl, string $heading = 'Signing you in', string $subtext = 'Loading your dashboard...'): void {
    // Same-origin guard: relative paths only, no scheme, no protocol-relative.
    $target = trim($targetUrl);
    if ($target === '' || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $target) || str_starts_with($target, '//')) {
        $target = 'dashboard.php';
    }
    $safeTarget = htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeSub = htmlspecialchars($subtext, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    http_response_code(200);
    ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $safeHeading ?> — TypeForge</title>
<meta http-equiv="refresh" content="3;url=<?= $safeTarget ?>">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  html, body { background: #06090e; }
  .tf-icon-wrap {
    width: 96px; height: 96px; border-radius: 9999px; overflow: hidden;
    background: #06090e; border: 1px solid rgba(52,211,153,.3);
    box-shadow: 0 12px 32px rgba(16,185,129,.25);
  }
  .tf-icon-wrap video {
    width: 100%; height: 100%; object-fit: cover; display: block;
  }
  .tf-bar { width: 200px; height: 4px; background: #21262d; border-radius: 9999px; overflow: hidden; margin: 1.25rem auto 0; }
  .tf-bar > span { display: block; height: 100%; width: 40%; border-radius: 9999px;
    background: linear-gradient(to right, #34d399, #99f6e4);
    animation: tf-slide 1.1s ease-in-out infinite; }
  @keyframes tf-slide { 0% { margin-left: -40%; } 100% { margin-left: 100%; } }
  @media (prefers-reduced-motion: reduce) { .tf-bar > span { animation: none; width: 100%; margin: 0; } }
</style>
</head>
<body class="min-h-screen flex items-center justify-center text-center font-sans antialiased">
<main role="status" aria-label="<?= $safeHeading ?>">
  <div class="tf-icon-wrap mx-auto" style="margin-top:1rem">
    <video src="video/loading-typeforge.mp4" muted autoplay loop playsinline preload="auto" aria-hidden="true"></video>
  </div>
  <h1 class="mt-5 text-lg font-bold text-white font-mono">Type<span class="text-emerald-400">Forge</span></h1>
  <p class="mt-1 text-sm text-gray-300"><?= $safeHeading ?>…</p>
  <p class="mt-1 text-xs text-gray-500"><?= $safeSub ?></p>
  <div class="tf-bar"><span></span></div>
  <p class="mt-4 text-xs text-gray-500"><a class="text-emerald-400 hover:underline" href="<?= $safeTarget ?>">Continue to dashboard</a></p>
</main>
<script>
(function () {
  var target = <?= json_encode($target, JSON_UNESCAPED_SLASHES) ?>;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.setTimeout(function () { window.location.replace(target); }, reduced ? 300 : 2000);
})();
</script>
</body>
</html>
    <?php
    exit();
}
