<?php
declare(strict_types=1);

/**
 * Server-side rules. The same limits are mirrored as HTML attributes
 * (required / minlength / maxlength / pattern) for browser-side checks.
 * Each method returns [field => message]; empty array means valid.
 */
final class Validator
{
    // Shared with the sign-up form in auth.php, which puts it in the input's pattern attribute so the browser checks the same thing.
    public const USERNAME_PATTERN = '[A-Za-z0-9_]{3,30}';

    // Sign-up rules. Browser checks are only a convenience, so everything is checked again here.
    public static function registration(string $username, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if (!preg_match('/^' . self::USERNAME_PATTERN . '$/', $username)) {
            $errors['username'] = 'Username must be 3-30 letters, numbers, or underscores.';
        }
        if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address (max 100 characters).';
        }
        // 72 is the longest password bcrypt actually uses, so anything longer would be silently cut off
        if (strlen($password) < 8 || strlen($password) > 72
            || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must be 8-72 characters with at least one letter and one number.';
        }
        if ($password !== $confirm) {
            $errors['confirm'] = 'Passwords do not match.';
        }

        return $errors;
    }

    // A recipe can list up to 30 ingredients.
    public const MAX_INGREDIENTS = 30;

    /** Units offered in the recipe form's dropdown. */
    public const UNITS = ['tsp', 'tbsp', 'cup', 'ml', 'L', 'g', 'kg', 'oz', 'lb', 'pc', 'clove', 'pinch', 'can', 'pack', 'bunch'];

    /** Same rule as quantityValue(), as an HTML pattern attribute: 2, 1.5, 1/2, or 1 1/2. */
    public const AMOUNT_PATTERN = '\d+(\.\d{1,2})?|\d+/\d+|\d+ \d+/\d+';

    /** Numeric value of an amount like "2", "1.5", "1/2" or "1 1/2"; null if it isn't one or isn't above zero. */
    public static function quantityValue(string $amount): ?float
    {
        if (preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            // plain number: 2 or 1.5
            $value = (float) $amount;
        } elseif (preg_match('/^(?:(\d+) )?(\d+)\/(\d+)$/', $amount, $m) && (int) $m[3] > 0) {
            // fraction, with an optional whole part in front: whole + top / bottom (the > 0 check avoids dividing by zero)
            $value = (int) $m[1] + (int) $m[2] / (int) $m[3];
        } else {
            return null;
        }
        return $value > 0 ? $value : null; // "0" and "0/5" are not real amounts
    }

    /**
     * @param int[] $validCategoryIds
     * @param array<int, array{amount: string, unit: string, name: string}> $ingredients trimmed; fully blank rows already dropped
     */
    public static function recipe(
        string $title,
        string $description,
        int $categoryId,
        array $ingredients,
        string $steps,
        array $validCategoryIds
    ): array {
        $errors = [];

        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
            $errors['title'] = 'Title must be 3-150 characters.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 500) {
            $errors['description'] = 'Description must be 10-500 characters.';
        }
        // The category must be one that really exists, not just any number someone posted
        if (!in_array($categoryId, $validCategoryIds, true)) {
            $errors['category_id'] = 'Choose a category.';
        }

        // Ingredients: how many, then every row needs amount + unit + name, each of them valid.
        // Only the first problem is reported, which keeps the message short.
        if (count($ingredients) < 1) {
            $errors['ingredients'] = 'Add at least one ingredient.';
        } elseif (count($ingredients) > self::MAX_INGREDIENTS) {
            $errors['ingredients'] = 'A recipe can have at most ' . self::MAX_INGREDIENTS . ' ingredients.';
        } else {
            foreach ($ingredients as $row) {
                if ($row['name'] === '' || $row['amount'] === '' || $row['unit'] === '') {
                    $errors['ingredients'] = 'Each ingredient needs an amount, a unit, and a name.';
                    break;
                }
                if (mb_strlen($row['amount']) > 10 || self::quantityValue($row['amount']) === null) {
                    $errors['ingredients'] = 'Each amount must be a number above zero, like 2, 1.5, 1/2 or 1 1/2.';
                    break;
                }
                // The unit must come from our list, because a posted form can contain anything
                if (!in_array($row['unit'], self::UNITS, true)) {
                    $errors['ingredients'] = 'Choose each unit from the list.';
                    break;
                }
                if (mb_strlen($row['name']) > 100) {
                    $errors['ingredients'] = 'Each ingredient name must be 100 characters or fewer.';
                    break;
                }
            }
        }
        if (mb_strlen($steps) < 10 || mb_strlen($steps) > 5000) {
            $errors['steps'] = 'Steps must be 10-5000 characters.';
        }

        return $errors;
    }

    // One rule for comments, both when posting and when editing.
    public static function comment(string $body): array
    {
        $length = mb_strlen($body);
        if ($length < 2 || $length > 1000) {
            return ['body' => 'Comment must be 2-1000 characters.'];
        }
        return [];
    }
}
