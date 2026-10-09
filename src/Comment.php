<?php
declare(strict_types=1);

// Comments on recipes. Anyone can read them; only the author can change or delete their own.
final class Comment
{
    public function __construct(private PDO $db)
    {
    }

    // All comments on a recipe, oldest first so the conversation reads top to bottom.
    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.user_id, c.body, c.created_at, c.updated_at, u.username
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.recipe_id = :recipe
            ORDER BY c.created_at ASC, c.id ASC');
        $stmt->execute(['recipe' => $recipeId]);
        return $stmt->fetchAll();
    }

    public function create(int $recipeId, int $userId, string $body): int
    {
        $stmt = $this->db->prepare('INSERT INTO comments (recipe_id, user_id, body) VALUES (:recipe, :user, :body)');
        $stmt->execute(['recipe' => $recipeId, 'user' => $userId, 'body' => $body]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $body): bool
    {
        // "AND user_id = :user" makes sure you can only change your own comment;
        // updated_at = NOW() is what shows the "edited" tag afterwards
        $stmt = $this->db->prepare(
            'UPDATE comments SET body = :body, updated_at = NOW() WHERE id = :id AND user_id = :user'
        );
        $stmt->execute(['body' => $body, 'id' => $id, 'user' => $userId]);
        return $stmt->rowCount() > 0; // false = nothing matched, i.e. not your comment
    }

    public function delete(int $id, int $userId): bool
    {
        // Same ownership condition as update()
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = :id AND user_id = :user');
        $stmt->execute(['id' => $id, 'user' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
