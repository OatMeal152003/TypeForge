<?php
// TypeForge theme toggle (light / dark) - Google Material Symbol button.
// Expects from including scope: $base_prefix, $user_theme, $user_sound.
// Optional: $tf_toggle_class (extra classes), $tf_toggle_floating (fixed variant).
$tf_toggle_class = $tf_toggle_class ?? '';
$tf_toggle_floating = $tf_toggle_floating ?? false;
if (empty($_SESSION['csrf_token']) && function_exists('generateCsrfToken')) {
    generateCsrfToken();
}
$tf_is_light = (($user_theme ?? 'dark') === 'light');
$tf_api = (($base_prefix ?? '') . 'api/update-settings.php');
?>
<button type="button"
    class="tf-theme-toggle<?= $tf_toggle_floating ? ' tf-theme-floating' : '' ?><?= $tf_toggle_class !== '' ? ' ' . sanitize($tf_toggle_class) : '' ?>"
    role="switch" aria-checked="<?= $tf_is_light ? 'true' : 'false' ?>"
    aria-label="Toggle light mode" title="Toggle light / dark mode"
    data-logged="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>"
    data-csrf="<?= sanitize($_SESSION['csrf_token'] ?? '') ?>"
    data-sound="<?= !empty($user_sound) ? '1' : '0' ?>"
    data-api="<?= sanitize($tf_api) ?>">
    <span class="material-symbols-rounded tf-mat" aria-hidden="true"><?= $tf_is_light ? 'dark_mode' : 'light_mode' ?></span>
</button>
<?php unset($tf_toggle_class, $tf_toggle_floating, $tf_is_light, $tf_csrf, $tf_api); ?>
