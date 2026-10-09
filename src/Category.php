<?php
declare(strict_types=1);

// The recipe categories (Ulam, Kakanin, ...). They are stored in the database and only read here.
final class Category
{
    public function __construct(private PDO $db)
    {
    }

    // Every category, A to Z. Fills the category dropdowns.
    public function all(): array
    {
        return $this->db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    /** @return int[] */
    public function ids(): array
    {
        // A plain list of valid ids, used to reject a category number that doesn't exist
        return array_map('intval', array_column($this->all(), 'id'));
    }
}
