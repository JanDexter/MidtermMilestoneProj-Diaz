<?php
declare(strict_types=1);

// A member's personal list of saved recipes. One row in `favorites` = one saved recipe.
final class Favorite
{
    public function __construct(private PDO $db)
    {
    }

    public function add(int $userId, int $recipeId): void
    {
        // Composite primary key (user_id, recipe_id); IGNORE makes double-clicks harmless.
        $stmt = $this->db->prepare('INSERT IGNORE INTO favorites (user_id, recipe_id) VALUES (:user, :recipe)');
        $stmt->execute(['user' => $userId, 'recipe' => $recipeId]);
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->db->prepare('DELETE FROM favorites WHERE user_id = :user AND recipe_id = :recipe');
        $stmt->execute(['user' => $userId, 'recipe' => $recipeId]);
    }

    // The member's saved recipes, most recently saved first. Same columns as every other recipe list.
    public function listFor(int $userId): array
    {
        $sql = Recipe::LIST_SELECT . '
            JOIN favorites fav ON fav.recipe_id = r.id AND fav.user_id = :owner
            ORDER BY fav.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['viewer' => $userId, 'owner' => $userId]);
        return $stmt->fetchAll();
    }
}
