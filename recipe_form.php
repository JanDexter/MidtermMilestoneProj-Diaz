<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// One page for both sharing a new recipe and editing an existing one. Members only.
$userId  = Auth::require();
$recipes = new Recipe(db());

$categories   = new Category(db());
$categoryList = $categories->all();

// ?id=... means we are editing; only the owner may do that.
$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $existing = $recipes->find($id);
    if (!$existing || (int) $existing['user_id'] !== $userId) {
        flash('error', 'You can only edit your own recipes.');
        redirect('index.php');
    }
}

// $old holds what the form fields show. It starts empty, or filled with the saved recipe when editing.
$errors = [];
$old = ['title' => '', 'description' => '', 'category_id' => 0, 'steps' => '', 'ingredients' => []];
if ($id) {
    $old = [
        'title'       => $existing['title'],
        'description' => $existing['description'],
        'category_id' => (int) $existing['category_id'],
        'steps'       => $existing['steps'],
        'ingredients' => $recipes->ingredients($id),
    ];
}

// The form was submitted: clean the input, validate it, then save.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Each row arrives as ingredient_amounts[i] + ingredient_units[i] + ingredients[i]. Drop rows
    // left fully blank and anything that isn't text; half-filled rows stay so the validator can flag them.
    $amounts     = (array) ($_POST['ingredient_amounts'] ?? []);
    $units       = (array) ($_POST['ingredient_units'] ?? []);
    $ingredients = [];
    foreach ((array) ($_POST['ingredients'] ?? []) as $i => $name) {
        $amount = $amounts[$i] ?? '';
        $unit   = $units[$i] ?? '';
        if (!is_string($name) || !is_string($amount) || !is_string($unit)) {
            continue;
        }
        $name   = trim($name);
        $amount = preg_replace('/\s+/', ' ', trim($amount)); // "1  1/2" -> "1 1/2"
        $unit   = trim($unit);
        if ($name !== '' || $amount !== '' || $unit !== '') {
            $ingredients[] = ['amount' => $amount, 'unit' => $unit, 'name' => $name];
        }
    }

    // What they typed replaces $old, so the form refills itself if validation fails
    $old = [
        'title'       => trim((string) ($_POST['title'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'steps'       => trim((string) ($_POST['steps'] ?? '')),
        'ingredients' => $ingredients,
    ];

    // Server-side validation (the HTML attributes below are only the browser's first line of defence)
    $errors = Validator::recipe(
        $old['title'], $old['description'], $old['category_id'],
        $ingredients, $old['steps'], $categories->ids()
    );

    // All good: save, tell the member, and go to the recipe page
    if (!$errors) {
        if ($id) {
            $recipes->update($id, $userId, $old['category_id'], $old['title'], $old['description'], $old['steps'], $ingredients);
            flash('success', 'Recipe updated.');
            redirect('recipe.php?id=' . $id);
        }
        $newId = $recipes->create($userId, $old['category_id'], $old['title'], $old['description'], $old['steps'], $ingredients);
        flash('success', 'Recipe shared!');
        redirect('recipe.php?id=' . $newId);
    }
}

// Ingredient rows to draw: a new recipe starts with three empty ones
$blankRow = ['amount' => '', 'unit' => '', 'name' => ''];
$fields   = $old['ingredients'] ?: [$blankRow, $blankRow, $blankRow];

page_header($id ? 'Edit recipe' : 'Share a recipe');
?>
<section class="form-card form-wide">
    <h1><?= $id ? 'Edit your recipe' : 'Share a recipe' ?></h1>
    <form method="post" id="recipe-form">
        <?= csrf_field() ?>

        <?php // required / minlength / maxlength give instant browser-side feedback; they mirror Validator::recipe ?>
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required minlength="3" maxlength="150"
               value="<?= e($old['title']) ?>">
        <?= field_error($errors, 'title') ?>

        <label for="description">Short description</label>
        <textarea id="description" name="description" rows="3" required minlength="10" maxlength="500"><?= e($old['description']) ?></textarea>
        <?= field_error($errors, 'description') ?>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <option value="">Choose a category</option>
            <?php foreach ($categoryList as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $old['category_id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'category_id') ?>

        <?php // Ingredients: one row = amount + unit + name. app.js adds and removes rows; the [] in the names sends them as lists ?>
        <label>Ingredients</label>
        <div id="ingredient-list">
            <?php foreach ($fields as $row): ?>
                <div class="ingredient-row">
                    <?php // The pattern attribute is the browser-side copy of Validator::quantityValue (2, 1.5, 1/2 or 1 1/2) ?>
                    <input type="text" name="ingredient_amounts[]" maxlength="10" aria-label="Amount"
                           inputmode="decimal" pattern="<?= e(Validator::AMOUNT_PATTERN) ?>"
                           title="A number like 2, 1.5, 1/2 or 1 1/2"
                           placeholder="Amount" value="<?= e($row['amount']) ?>">
                    <?php // The unit is its own dropdown, filled from the same list the server validates against ?>
                    <select name="ingredient_units[]" aria-label="Unit">
                        <option value="">Unit</option>
                        <?php foreach (Validator::UNITS as $unit): ?>
                            <option value="<?= e($unit) ?>" <?= $unit === $row['unit'] ? 'selected' : '' ?>><?= e($unit) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php // The role / aria attributes tell screen readers that app.js shows a suggestion list for this box while typing ?>
                    <input type="text" name="ingredients[]" maxlength="100" aria-label="Ingredient" autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-controls="ingredient-suggestions" aria-expanded="false"
                           placeholder="Ingredient" value="<?= e($row['name']) ?>">
                    <button type="button" class="btn btn-outline remove-ingredient">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php // Ingredient names other recipes already use. app.js reads them to suggest names while the member types; a name that is not here yet is still allowed. ?>
        <?php // Names were typed by members, so the JSON_HEX_* flags escape < > & ' " and nothing can break out of the script tag ?>
        <script type="application/json" id="ingredient-names"><?= json_encode($recipes->ingredientNames(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
        <?= field_error($errors, 'ingredients') ?>
        <button type="button" id="add-ingredient" class="btn btn-outline">+ Add ingredient</button>

        <label for="steps">Cooking steps</label>
        <textarea id="steps" name="steps" rows="8" required minlength="10" maxlength="5000"><?= e($old['steps']) ?></textarea>
        <?= field_error($errors, 'steps') ?>

        <button type="submit" class="btn"><?= $id ? 'Save changes' : 'Share recipe' ?></button>
        <a href="<?= $id ? 'recipe.php?id=' . $id : 'index.php' ?>" class="btn btn-outline">Cancel</a>
    </form>
</section>
<?php page_footer(); ?>
