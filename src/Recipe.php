<?php
declare(strict_types=1);

// Everything about recipes and their ingredients: listing, searching, viewing, saving, editing, deleting.
// Every query uses placeholders (:name), so user input never becomes part of the SQL itself.
final class Recipe
{
    // Most recipes shown on one list page.
    private const LIST_LIMIT = 60;

    // The shared top half of every recipe list: recipe + author + category,
    // plus is_fav, which says whether the logged-in member (:viewer) already saved it.
    // Each list adds its own WHERE / ORDER BY to this (here, in Favorite and in Suggester).
    public const LIST_SELECT = '
        SELECT r.id, r.title, r.description, r.created_at, r.updated_at,
               u.username, c.name AS category,
               EXISTS (SELECT 1 FROM favorites f WHERE f.recipe_id = r.id AND f.user_id = :viewer) AS is_fav
        FROM recipes r
        JOIN users u      ON u.id = r.user_id
        JOIN categories c ON c.id = r.category_id';

    // The database connection is handed in, not created here, so the class doesn't depend on db.php.
    public function __construct(private PDO $db)
    {
    }

    /**
     * Latest first, optionally filtered by keyword, category, an ingredient name,
     * and/or a maximum number of ingredients (0 = no limit).
     */
    public function search(
        int $viewerId,
        string $keyword = '',
        int $categoryId = 0,
        string $ingredient = '',
        int $maxIngredients = 0
    ): array {
        // Each filter is written as "(:x_empty = 1 OR <real test>)": when that filter is blank,
        // the first half is true and the filter is skipped. That keeps this one query for every filter combination.
        $sql = self::LIST_SELECT . '
            WHERE (:kw_empty = 1 OR r.title LIKE :kw_title OR r.description LIKE :kw_desc)
              AND (:cat_all = 1 OR r.category_id = :cat)
              AND (:ing_empty = 1 OR EXISTS (
                    SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.name LIKE :ing))
              AND (:max_all = 1 OR (SELECT COUNT(*) FROM ingredients i2 WHERE i2.recipe_id = r.id) <= :max_ing)
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT ' . self::LIST_LIMIT;

        // Escape % and _ so a keyword like "100%" is searched literally instead of acting as a wildcard.
        $like = '%' . addcslashes($keyword, '%_\\') . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'viewer'    => $viewerId,
            'kw_empty'  => $keyword === '' ? 1 : 0,
            'kw_title'  => $like,
            'kw_desc'   => $like,
            'cat_all'   => $categoryId === 0 ? 1 : 0,
            'cat'       => $categoryId,
            'ing_empty' => $ingredient === '' ? 1 : 0,
            'ing'       => '%' . addcslashes($ingredient, '%_\\') . '%',
            'max_all'   => $maxIngredients === 0 ? 1 : 0,
            'max_ing'   => $maxIngredients,
        ]);
        return $stmt->fetchAll();
    }

    // One recipe with everything the detail page and edit form need, or null if the id doesn't exist.
    public function find(int $id, int $viewerId = 0): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.id, r.user_id, r.category_id, r.title, r.description, r.steps,
                   r.created_at, r.updated_at, u.username, c.name AS category,
                   EXISTS (SELECT 1 FROM favorites f WHERE f.recipe_id = r.id AND f.user_id = :viewer) AS is_fav
            FROM recipes r
            JOIN users u      ON u.id = r.user_id
            JOIN categories c ON c.id = r.category_id
            WHERE r.id = :id');
        $stmt->execute(['viewer' => $viewerId, 'id' => $id]);
        return $stmt->fetch() ?: null; // fetch() returns false when nothing matched
    }

    /** @return array<int, array{amount: string, unit: string, name: string}> */
    public function ingredients(int $recipeId): array
    {
        // ORDER BY id keeps the ingredients in the order the author typed them
        $stmt = $this->db->prepare('SELECT amount, unit, name FROM ingredients WHERE recipe_id = :id ORDER BY id');
        $stmt->execute(['id' => $recipeId]);
        return $stmt->fetchAll();
    }

    /** @param array<int, array{amount: string, unit: string, name: string}> $ingredients */
    public function create(int $userId, int $categoryId, string $title, string $description, string $steps, array $ingredients): int
    {
        // A recipe and its ingredients are saved together in a transaction:
        // either all of it is stored or none of it, so we never end up with a recipe missing its ingredients.
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                INSERT INTO recipes (user_id, category_id, title, description, steps)
                VALUES (:user, :cat, :title, :descr, :steps)');
            $stmt->execute([
                'user' => $userId, 'cat' => $categoryId, 'title' => $title,
                'descr' => $description, 'steps' => $steps,
            ]);
            $recipeId = (int) $this->db->lastInsertId(); // the id MySQL just gave the new recipe
            $this->insertIngredients($recipeId, $ingredients);
            $this->db->commit();
            return $recipeId;
        } catch (Throwable $e) {
            $this->db->rollBack(); // something failed: undo the half-finished save
            throw $e;
        }
    }

    /** Only the owner can update. Returns false if the recipe isn't theirs. */
    public function update(int $id, int $userId, int $categoryId, string $title, string $description, string $steps, array $ingredients): bool
    {
        $this->db->beginTransaction();
        try {
            // "AND user_id = :user" is the ownership check: someone else's recipe matches no rows.
            // updated_at = NOW() is what makes the "edited ..." label appear.
            $stmt = $this->db->prepare('
                UPDATE recipes
                SET category_id = :cat, title = :title, description = :descr, steps = :steps, updated_at = NOW()
                WHERE id = :id AND user_id = :user');
            $stmt->execute([
                'cat' => $categoryId, 'title' => $title, 'descr' => $description,
                'steps' => $steps, 'id' => $id, 'user' => $userId,
            ]);
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // Simplest way to apply edited ingredients: delete the old rows and insert the new list
            $del = $this->db->prepare('DELETE FROM ingredients WHERE recipe_id = :id');
            $del->execute(['id' => $id]);
            $this->insertIngredients($id, $ingredients);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Only the owner can delete. Comments/ingredients/favorites go via ON DELETE CASCADE. */
    public function delete(int $id, int $userId): bool
    {
        // Same ownership trick as update(): the user_id condition lives inside the query itself
        $stmt = $this->db->prepare('DELETE FROM recipes WHERE id = :id AND user_id = :user');
        $stmt->execute(['id' => $id, 'user' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Every ingredient name already used by some recipe, A to Z, each spelled once. Fills the recipe
     * form's suggestion dropdown. The `ingredients` table has one row per recipe ingredient, so the first
     * spelling ever saved for a name (the lowest id) is the one that counts; "Sugar" and "sugar" are one name.
     *
     * @return string[]
     */
    public function ingredientNames(): array
    {
        // No user input in this query, so a plain query() is fine here
        return $this->db->query('
            SELECT name FROM ingredients
            WHERE id IN (SELECT MIN(id) FROM ingredients GROUP BY name)
            ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    }

    // Used by create() and update(). One prepared statement, run once per ingredient row.
    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        $insert = $this->db->prepare('INSERT INTO ingredients (recipe_id, amount, unit, name) VALUES (:recipe, :amount, :unit, :name)');
        // An ingredient that already exists is reused as it is spelled in the database, not added as a new
        // variant. "=" ignores capitals (the column's collation), so typing "SUGAR" stores "sugar".
        $existing = $this->db->prepare('SELECT name FROM ingredients WHERE name = :name ORDER BY id LIMIT 1');
        foreach ($ingredients as $row) {
            $existing->execute(['name' => $row['name']]);
            $name = $existing->fetchColumn();
            $insert->execute([
                'recipe' => $recipeId, 'amount' => $row['amount'], 'unit' => $row['unit'],
                'name'   => $name !== false ? $name : $row['name'],
            ]);
        }
    }
}
