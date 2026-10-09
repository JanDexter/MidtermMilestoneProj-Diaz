<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// Custom feature: "What should I cook?". Three questions, asked one at a time. Each answer narrows
// the choices offered by the next question, and at the end we pick up to three recipes. Members only.
// This page only shows things; the questions, choices and picks all come from src/Suggester.php.
$userId    = Auth::require();
$suggester = new Suggester(db());

// Answers travel in the address bar (?category=3&ingredient=Chicken), so nothing needs to be stored.
// walk() checks them and says which question to show next ($current = null once all are answered).
['answers' => $answers, 'labels' => $labels, 'current' => $current, 'choices' => $choices, 'error' => $error]
    = $suggester->walk($_GET);

// Every question answered: pick the recipes
$recipes = $current === null ? $suggester->pick($userId, $answers) : [];

$total = count(Suggester::QUESTIONS);
$step  = count($answers) + 1; // number of the question on screen (1, 2 or 3)

// Builds a link to suggest.php carrying the given answers
$urlFor = static fn(array $a): string => 'suggest.php' . ($a ? '?' . http_build_query($a) : '');

// "Back" = the same answers minus the most recent one, which lands on the previous question
$backAnswers = array_slice($answers, 0, -1, true);

// Where the favorite button returns to, so the same answers are still in place afterwards
$back = safe_back($urlFor($answers), 'suggest.php');

// Words next to each choice, e.g. "3 recipes"
$countLabel = static fn(int $n): string => $n === 1 ? '1 recipe' : $n . ' recipes';

page_header('What to cook?');
?>
<h1>What should I cook?</h1>

<?php if ($current !== null): ?>
    <p>Answer a few questions and we'll pick up to three recipes for you.</p>

    <?php // Progress: "Question 2 of 3" and a small bar ?>
    <p class="meta">Question <?= $step ?> of <?= $total ?></p>
    <progress class="wizard-progress" max="<?= $total ?>" value="<?= $step ?>"></progress>

    <?php if (!$choices): ?>
        <?php // No choices means no recipes to build them from, e.g. nobody has shared one yet ?>
        <p>There are no recipes to suggest yet. <a href="recipe_form.php">Share the first one!</a></p>
    <?php else: ?>
        <?php // One question per page. It's a GET form, so the next page's address carries every answer so far ?>
        <form method="get" class="filter-form wizard">
            <?php // The earlier answers ride along as hidden fields ?>
            <?php foreach ($answers as $key => $value): ?>
                <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>

            <fieldset>
                <legend><?= $step ?>. <?= e(Suggester::QUESTIONS[$current]) ?></legend>
                <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>

                <?php // The choices, each with how many recipes it leaves ?>
                <?php foreach ($choices as $c): ?>
                    <label class="choice">
                        <?php // "required" on the radios is the browser-side check: you can't continue without picking one ?>
                        <input type="radio" name="<?= e($current) ?>" value="<?= e($c['value']) ?>" required>
                        <span><?= e($c['label']) ?> <small class="meta">&middot; <?= e($countLabel($c['count'])) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <?php // Back goes to the previous question; it isn't shown on the first one ?>
            <?php if ($answers): ?>
                <a href="<?= e($urlFor($backAnswers)) ?>" class="btn btn-outline">Back</a>
            <?php endif; ?>
            <button type="submit" class="btn"><?= $step === $total ? 'Show me recipes' : 'Next' ?></button>
        </form>
    <?php endif; ?>

<?php else: ?>
    <?php // All questions answered: show a recap and the picks. The order is random, so "Pick again" gives new ideas ?>
    <h2 class="section-title">Our suggestions</h2>
    <p class="meta">Your answers: <?= implode(' &middot; ', array_map('e', $labels)) ?></p>

    <?php foreach ($recipes as $r): ?>
        <?php recipe_card($r, $back); ?>
    <?php endforeach; ?>

    <div class="actions">
        <a href="<?= e($urlFor($answers)) ?>" class="btn btn-outline">Pick again</a>
        <a href="<?= e($urlFor($backAnswers)) ?>" class="btn btn-outline">Change my last answer</a>
        <a href="suggest.php" class="btn btn-outline">Start over</a>
    </div>
<?php endif; ?>
<?php page_footer(); ?>
