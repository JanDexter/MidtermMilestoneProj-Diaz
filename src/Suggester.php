<?php
declare(strict_types=1);

// Custom feature: "What should I cook?" - everything behind suggest.php lives here.
// The member answers three questions, one at a time, and gets up to three random recipes that fit.
//
// Nothing here is typed in by hand: every choice is built from what is really in the database, and each
// question's choices depend on the answers before it, so a member can never pick something that leads to zero recipes.
// Every choice is a small array: ['value' => what the form sends, 'label' => what the member reads,
// 'count' => how many recipes that choice leaves].
final class Suggester
{
    // The questions, in the order they are asked (answer key => question).
    public const QUESTIONS = [
        'category'   => 'What kind of dish are you in the mood for?',
        'ingredient' => 'Is there an ingredient you want to use up?',
        'max_ing'    => 'How many ingredients are you up for?',
    ];

    // Question 2 offers only the most-used ingredients, so the list stays short.
    private const MAX_INGREDIENT_CHOICES = 12;

    // Question 3 offers at most this many "no more than N ingredients" limits (plus "any number").
    private const MAX_LIMIT_CHOICES = 3;

    // The filter behind the picks AND the counts, so the number shown next to a choice always matches
    // what that choice will really give. Each part is skipped when its "_all"/"_empty" flag is 1 (no preference).
    // The ingredient must match a name exactly, because the member picks it from a list of real names.
    private const WHERE = '
        (:cat_all = 1 OR r.category_id = :cat)
        AND (:ing_empty = 1 OR EXISTS (
              SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.name = :ing))
        AND (:max_all = 1 OR (SELECT COUNT(*) FROM ingredients i2 WHERE i2.recipe_id = r.id) <= :max_ing)';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Works out where the member is, from the answers in the address bar (?category=3&ingredient=Chicken).
     * Walks through the questions in order and stops at the first one without a valid answer: that is the
     * question to show, so the page always sits on the right step, even if the address was edited by hand.
     *
     * @return array{answers: array<string, string>, labels: array<string, string>, current: ?string, choices: array, error: string}
     *         current = the question to show now, or null once every question has an answer
     */
    public function walk(array $query): array
    {
        $answers = []; // answers accepted so far, in question order (key => value)
        $labels  = []; // the words for those answers, shown in the recap (key => label)

        foreach (array_keys(self::QUESTIONS) as $key) {
            $choices = $this->choicesFor($key, $answers);
            $offered = array_column($choices, null, 'value'); // index by value so an answer can be looked up

            // Server-side check: the answer must be one of the choices we actually offered.
            // A value that isn't plain text (like ?category[]=1) counts as "not answered".
            $given = isset($query[$key]) && is_string($query[$key]) ? $query[$key] : null;
            if ($given !== null && isset($offered[$given])) {
                $answers[$key] = $given;
                $labels[$key]  = $offered[$given]['label'];
                continue; // accepted, on to the next question
            }

            $error = $given !== null ? 'That choice is not available. Please pick one from the list.' : '';
            return ['answers' => $answers, 'labels' => $labels, 'current' => $key, 'choices' => $choices, 'error' => $error];
        }

        return ['answers' => $answers, 'labels' => $labels, 'current' => null, 'choices' => [], 'error' => ''];
    }

    /** Up to three random recipes that fit every answer (walk() must have accepted all of them). */
    public function pick(int $viewerId, array $answers): array
    {
        // RAND() with LIMIT 3 gives three random matches, so "Pick again" shows new ideas
        $stmt = $this->db->prepare(Recipe::LIST_SELECT . ' WHERE ' . self::WHERE . ' ORDER BY RAND() LIMIT 3');
        $stmt->execute(['viewer' => $viewerId]
            + self::params((int) $answers['category'], $answers['ingredient'], (int) $answers['max_ing']));
        return $stmt->fetchAll();
    }

    // Each question builds its choices from the answers given before it.
    private function choicesFor(string $key, array $answers): array
    {
        return match ($key) {
            'category'   => $this->categoryChoices(),
            'ingredient' => $this->ingredientChoices((int) $answers['category']),
            'max_ing'    => $this->effortChoices((int) $answers['category'], $answers['ingredient']),
        };
    }

    /** Question 1 - the kind of dish: "Anything" plus every category that has at least one recipe. */
    private function categoryChoices(): array
    {
        // No user input in this query, so a plain query() is fine here
        $rows = $this->db->query('
            SELECT c.id, c.name, COUNT(r.id) AS total
            FROM categories c
            JOIN recipes r ON r.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name')->fetchAll();

        if (!$rows) {
            return []; // nobody has shared a recipe yet, so there is nothing to ask about
        }

        $choices = [['value' => '0', 'label' => 'Anything', 'count' => (int) array_sum(array_column($rows, 'total'))]];
        foreach ($rows as $row) {
            $choices[] = ['value' => (string) $row['id'], 'label' => $row['name'], 'count' => (int) $row['total']];
        }
        return $choices;
    }

    /** Question 2 - an ingredient to use up: the most common ones in the category chosen in question 1. */
    private function ingredientChoices(int $categoryId): array
    {
        // Same name typed in different capitals counts as one ingredient (the column's collation ignores case).
        // total = in how many recipes it appears; the most-used come first.
        $stmt = $this->db->prepare('
            SELECT MIN(i.name) AS label, COUNT(DISTINCT i.recipe_id) AS total
            FROM ingredients i
            JOIN recipes r ON r.id = i.recipe_id
            WHERE (:cat_all = 1 OR r.category_id = :cat)
            GROUP BY i.name
            ORDER BY total DESC, label
            LIMIT ' . self::MAX_INGREDIENT_CHOICES);
        $stmt->execute(['cat_all' => $categoryId === 0 ? 1 : 0, 'cat' => $categoryId]);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            return []; // the recipes that are left have no ingredients on record
        }

        // The empty value means "no preference"
        $choices = [['value' => '', 'label' => 'Any ingredient', 'count' => $this->count($categoryId, '', 0)]];
        foreach ($rows as $row) {
            $choices[] = ['value' => $row['label'], 'label' => $row['label'], 'count' => (int) $row['total']];
        }
        return $choices;
    }

    /**
     * Question 3 - how many ingredients: "No more than N" limits taken from the real ingredient counts of
     * the recipes left after questions 1 and 2, plus "any number". The value is the limit (0 = no limit).
     */
    private function effortChoices(int $categoryId, string $ingredient): array
    {
        // How many ingredients each remaining recipe has
        $stmt = $this->db->prepare('
            SELECT (SELECT COUNT(*) FROM ingredients ic WHERE ic.recipe_id = r.id) AS n
            FROM recipes r
            WHERE ' . self::WHERE);
        $stmt->execute(self::params($categoryId, $ingredient, 0));
        $sizes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if (!$sizes) {
            return [];
        }

        // Candidate limits: each different ingredient count that is smaller than the biggest recipe.
        // (The biggest is left out because "any number" already covers it.)
        $largest = max($sizes);
        $limits  = array_values(array_unique(array_filter($sizes, fn(int $n): bool => $n >= 1 && $n < $largest)));
        sort($limits);

        // Too many to list? Keep a few, spread evenly from the smallest to the biggest.
        $found = count($limits);
        if ($found > self::MAX_LIMIT_CHOICES) {
            $spread = [];
            for ($k = 1; $k <= self::MAX_LIMIT_CHOICES; $k++) {
                $spread[] = $limits[intdiv($k * $found, self::MAX_LIMIT_CHOICES + 1)];
            }
            $limits = $spread;
        }

        $choices = [];
        foreach ($limits as $limit) {
            $choices[] = [
                'value' => (string) $limit,
                'label' => 'No more than ' . $limit . ($limit === 1 ? ' ingredient' : ' ingredients'),
                'count' => count(array_filter($sizes, fn(int $n): bool => $n <= $limit)),
            ];
        }
        $choices[] = ['value' => '0', 'label' => 'Any number of ingredients', 'count' => count($sizes)];
        return $choices;
    }

    // How many recipes pass the very same filter pick() uses, so the numbers on screen are honest.
    private function count(int $categoryId, string $ingredient, int $maxIngredients): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM recipes r WHERE ' . self::WHERE);
        $stmt->execute(self::params($categoryId, $ingredient, $maxIngredients));
        return (int) $stmt->fetchColumn();
    }

    /** The values for the placeholders in WHERE. Category 0 / empty ingredient / 0 max = no preference. */
    private static function params(int $categoryId, string $ingredient, int $maxIngredients): array
    {
        return [
            'cat_all'   => $categoryId === 0 ? 1 : 0,
            'cat'       => $categoryId,
            'ing_empty' => $ingredient === '' ? 1 : 0,
            'ing'       => $ingredient,
            'max_all'   => $maxIngredients === 0 ? 1 : 0,
            'max_ing'   => $maxIngredients,
        ];
    }
}
