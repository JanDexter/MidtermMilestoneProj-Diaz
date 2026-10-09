<?php
declare(strict_types=1);

// The HTML pieces every page shares: the header and footer around each page, the recipe card,
// the favorite button, and the small labels under fields and posts.
// Everything a member typed goes through e() so it shows as plain text, never as HTML.

// Top of every page: <head>, the site header with the menu, and any flash messages. Opens <main>.
function page_header(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php // e() escapes the title, since some titles are recipe names typed by members ?>
    <title><?= e($title) ?> | Kaon</title>
    <?php // Roboto Light from Google Fonts, then our own stylesheet ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <?php // Clicking the logo goes to the recipes if you're logged in, otherwise to the login page ?>
        <a class="brand" href="<?= Auth::check() ? 'index.php' : 'auth.php' ?>">Kaon</a>

        <?php // Phone menu: a hidden checkbox + its label (the burger icon) toggle the menu with CSS only, no JavaScript ?>
        <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
        <label for="nav-toggle" class="nav-burger" aria-hidden="true"><span></span></label>

        <nav class="site-nav">
            <?php if (Auth::check()): ?>
                <?php // Members see the full menu ?>
                <a href="index.php">Browse</a>
                <a href="suggest.php">What to cook?</a>
                <a href="favorites.php">Favorites</a>
                <a href="recipe_form.php">Share a recipe</a>
                <?php // Logging out is a POST form with a CSRF token, so another site can't log you out with a link ?>
                <form method="post" action="auth.php" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="link-button">Log out (<?= e(Auth::username()) ?>)</button>
                </form>
            <?php else: ?>
                <?php // Guests only get the two ways in ?>
                <a href="auth.php">Log in</a>
                <a href="auth.php?mode=register">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php // One-time messages ("Recipe shared!", errors) saved by flash() on the previous page; showing them also clears them ?>
    <?php foreach (pull_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?php
}

// Bottom of every page: closes <main>, then the footer and the page script.
function page_footer(): void
{
    ?>
</main>
<footer class="site-footer">
    <div class="container">
        <p>Kaon &mdash; recipes shared by our home cooks.</p>
    </div>
</footer>
<?php // Loaded last so the page is already built when the script looks for its elements ?>
<script src="assets/js/app.js"></script>
</body>
</html>
    <?php
}

/**
 * One recipe summary card, shared by the browse, favorites and "what to cook?" pages.
 * $r is a row from Recipe::search, Favorite::listFor or Suggester::pick; $back is the page to return to.
 */
function recipe_card(array $r, string $back, bool $removeOnUnsave = false): void
{
    ?>
<article class="card" id="recipe-<?= (int) $r['id'] ?>">
    <span class="badge"><?= e($r['category']) ?></span>
    <h3><a href="recipe.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></h3>
    <p class="card-desc"><?= e($r['description']) ?></p>
    <?php // Author and date, plus an "edited ..." tag if the recipe was changed after posting ?>
    <p class="meta">
        by <?= e($r['username']) ?> &middot; <?= e(format_date($r['created_at'])) ?>
        <?= edited_label($r['updated_at']) ?>
    </p>
    <?php favorite_button($r, $back, $removeOnUnsave); ?>
</article>
    <?php
}

/**
 * The save/unsave heart. $r needs 'id' and 'is_fav' (from the SQL: has the logged-in member saved it?).
 * $removeOnUnsave = true on the favorites page, where unsaving takes the card off the list.
 */
function favorite_button(array $r, string $back, bool $removeOnUnsave = false): void
{
    $isFav = (bool) $r['is_fav'];
    // Works with no JavaScript (normal form post + redirect); app.js upgrades it to save/unsave without a page reload
    ?>
<form method="post" action="favorites.php" class="fav-form"<?= $removeOnUnsave ? ' data-remove-card' : '' ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="recipe_id" value="<?= (int) $r['id'] ?>">
    <?php // "mode" is what the click will do next: unsave if already saved, otherwise save ?>
    <input type="hidden" name="mode" value="<?= $isFav ? 'remove' : 'add' ?>">
    <?php // Where to send the member back to afterwards (same page, same filters, scrolled to this recipe) ?>
    <input type="hidden" name="back" value="<?= e($back . '#recipe-' . (int) $r['id']) ?>">
    <button type="submit" class="btn <?= $isFav ? 'btn-fav-on' : 'btn-outline' ?>">
        <?= $isFav ? '&#9829; Saved' : '&#9825; Save' ?>
    </button>
</form>
    <?php
}

/** "Edited on ..." marker so readers can tell something changed after posting. */
function edited_label(?string $updatedAt): string
{
    // updated_at stays empty until the first edit, so no edit means no label
    return $updatedAt ? '<span class="edited">edited ' . e(format_date($updatedAt)) . '</span>' : '';
}

// The red message under a form field, or nothing when that field has no error.
function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<p class="field-error">' . e($errors[$field]) . '</p>' : '';
}
