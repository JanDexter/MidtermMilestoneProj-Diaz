<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// Browse page: latest recipes first, with search and filters. Members only.
$userId = Auth::require();

// Read the filters from the address bar (?q=...&category=...). They're just looked at, never trusted,
// so each one is cleaned up right away.
$keyword    = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);
$ingredient = mb_substr(trim((string) ($_GET['ingredient'] ?? '')), 0, 100);
$maxIng     = (int) ($_GET['max_ing'] ?? 0);

// A category number that isn't in the database counts as "all categories",
// and a "most ingredients" outside 1-30 counts as "no limit".
$categories = new Category(db());
$categoryList = $categories->all();
if ($categoryId !== 0 && !in_array($categoryId, $categories->ids(), true)) {
    $categoryId = 0;
}
if ($maxIng < 1 || $maxIng > Validator::MAX_INGREDIENTS) {
    $maxIng = 0;
}

// One query does all the filtering; blank filters are simply skipped (see Recipe::search).
$recipes = (new Recipe(db()))->search($userId, $keyword, $categoryId, $ingredient, $maxIng);

// Where the favorite button sends people back to (keeps their filters).
// array_filter drops the empty ones so the address stays tidy.
$back = 'index.php' . ($_GET ? '?' . http_build_query(array_filter(
    ['q' => $keyword, 'category' => $categoryId ?: '', 'ingredient' => $ingredient, 'max_ing' => $maxIng ?: ''],
    static fn($v) => $v !== ''
)) : '');
$back = safe_back($back); // final safety check on the return address

page_header('Browse recipes');
?>
<h1>Latest recipes</h1>

<?php // Filter form. It uses GET, so a search has its own address that can be bookmarked or shared ?>
<form method="get" class="filter-form">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" maxlength="100" value="<?= e($keyword) ?>" placeholder="Title or description">

    <label for="category">Category</label>
    <select id="category" name="category">
        <option value="0">All categories</option>
        <?php // One option per category; the one currently filtered stays selected ?>
        <?php foreach ($categoryList as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $categoryId ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php // Finds recipes that use this ingredient (matches part of the name, so "chick" finds "chicken") ?>
    <label for="ingredient">Ingredient</label>
    <input type="text" id="ingredient" name="ingredient" maxlength="100"
           value="<?= e($ingredient) ?>" placeholder="e.g. kangkong">

    <?php // Keeps only recipes with this many ingredients or fewer, for quick cooks ?>
    <label for="max_ing">Most ingredients</label>
    <input type="number" id="max_ing" name="max_ing" min="1" max="<?= Validator::MAX_INGREDIENTS ?>"
           value="<?= $maxIng ?: '' ?>" placeholder="Any number">

    <button type="submit" class="btn">Search</button>
    <?php // "Clear" only appears while some filter is active ?>
    <?php if ($keyword !== '' || $categoryId !== 0 || $ingredient !== '' || $maxIng !== 0): ?>
        <a href="index.php" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<?php // The results: a friendly message when nothing matches, otherwise one card per recipe ?>
<?php if (!$recipes): ?>
    <p>No recipes found. <a href="recipe_form.php">Share the first one!</a></p>
<?php else: ?>
    <?php foreach ($recipes as $r): ?>
        <?php recipe_card($r, $back); ?>
    <?php endforeach; ?>
<?php endif; ?>
<?php page_footer(); ?>
