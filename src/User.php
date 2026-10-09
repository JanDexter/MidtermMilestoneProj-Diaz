<?php
declare(strict_types=1);

// Member accounts: creating them and checking logins.
final class User
{
    public function __construct(private PDO $db)
    {
    }

    // Saves a new member and returns their id.
    public function create(string $username, string $email, string $password): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
        );
        $stmt->execute([
            'username' => $username,
            'email'    => $email,
            // Only a one-way hash is stored, never the real password
            'hash'     => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) $this->db->lastInsertId();
    }

    // Used on sign-up to give a friendly "already taken" message before the database has to complain.
    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    /** Returns the user row when the credentials are right, otherwise null. */
    public function authenticate(string $emailOrUsername, string $password): ?array
    {
        // The login box accepts either a username or an email, so look for both
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash FROM users WHERE email = :email OR username = :username LIMIT 1'
        );
        $stmt->execute(['email' => $emailOrUsername, 'username' => $emailOrUsername]);
        $user = $stmt->fetch();

        // password_verify checks the typed password against the stored hash
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null; // same result for "no such user" and "wrong password", so it doesn't reveal which accounts exist
    }
}
