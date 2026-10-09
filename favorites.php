<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// Everything about favorites. Members only.
//   GET  favorites.php  -> the member's saved recipes
//   POST favorites.php  -> save or unsave one recipe (the heart button on any page posts here)
$userId    = Auth::require();
$favorites = new Favorite(db());

// ----- Save / unsave -----
// app.js sends this in the background; with JavaScript off it is a normal form post.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipeId = (int) ($_POST['recipe_id'] ?? 0);
    $mode     = (string) ($_POST['mode'] ?? '');

    // The recipe has to exist and the mode has to be one of the two we know
    if ((new Recipe(db()))->find($recipeId) === null || !in_array($mode, ['add', 'remove'], true)) {
        http_response_code(404);
        exit('Recipe not found.');
    }

    if ($mode === 'add') {
        $favorites->add($userId, $recipeId);
    } else {
        $favorites->remove($userId, $recipeId);
    }

    // The "fetch" header is how we tell app.js apart from a plain form post.
    // The JSON answer tells app.js what state the button is in now.
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
        header('Content-Type: application/json');
        echo json_encode(['favorited' => $mode === 'add']);
        exit;
    }

    // No JavaScript: go back to the page they came from (safe_back refuses addresses outside our site)
    $back = $_POST['back'] ?? null;
    redirect(safe_back(is_string($back) ? $back : null));
}

// ----- The list -----
$recipes = $favorites->listFor($userId);

page_header('My favorites');
?>
<h1>My favorites</h1>

<?php // Same card as the browse page; the last argument makes unsaving a recipe here remove its card ?>
<?php foreach ($recipes as $r): ?>
    <?php recipe_card($r, 'favorites.php', true); ?>
<?php endforeach; ?>

<?php // Hidden while there are cards. app.js un-hides it when the last card is unsaved, with no page reload ?>
<p id="fav-empty"<?= $recipes ? ' hidden' : '' ?>>
    You haven't saved any recipes yet. <a href="index.php">Browse recipes</a>.
</p>
<?php page_footer(); ?>
