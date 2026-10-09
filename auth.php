<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// Account management: log in, sign up, log out.
//   auth.php                -> the log-in form
//   auth.php?mode=register  -> the sign-up form
// Each form posts back here with a hidden "action" field: login, register, or logout (the button in the header).
$action = post_action();

// ----- Log out -----
// Only reachable as a POST with a valid token (checked in init.php), so a stray link can't log anyone out.
if ($action === 'logout') {
    Auth::logout();
    // A fresh, empty session just to carry the goodbye message. It needs a new id: the old one was
    // destroyed, and without a new id PHP sends no new cookie, so the message would be lost.
    session_start();
    session_regenerate_id();
    flash('success', 'You have been logged out.');
    redirect('auth.php');
}

// Already logged in? Skip straight to the recipes.
if (Auth::check()) {
    redirect('index.php');
}

// Which form to show. A failed sign-up stays on the sign-up form.
$mode = ($_GET['mode'] ?? '') === 'register' || $action === 'register' ? 'register' : 'login';

// $old keeps what they typed so the form can refill itself after a mistake (never the password).
$errors = [];
$old    = ['login' => '', 'username' => '', 'email' => ''];

// ----- Log in -----
if ($action === 'login') {
    $old['login'] = trim((string) ($_POST['login'] ?? ''));
    $password     = (string) ($_POST['password'] ?? '');

    if ($old['login'] === '' || $password === '') {
        $errors['login'] = 'Enter your username or email and your password.';
    } else {
        // Look the member up and check the password (User::authenticate compares against the stored hash)
        $user = (new User(db()))->authenticate($old['login'], $password);
        if ($user) {
            Auth::login((int) $user['id'], $user['username']); // also renews the session id
            flash('success', 'Welcome back, ' . $user['username'] . '!');
            redirect('index.php');
        }
        // One vague message on purpose, so nobody can use this form to find out which usernames exist
        $errors['login'] = 'Wrong username/email or password.';
    }
}

// ----- Sign up -----
if ($action === 'register') {
    $old['username'] = trim((string) ($_POST['username'] ?? ''));
    $old['email']    = trim((string) ($_POST['email'] ?? ''));
    $password        = (string) ($_POST['password'] ?? '');
    $confirm         = (string) ($_POST['confirm'] ?? '');

    // Format rules first (length, characters, matching passwords)...
    $errors = Validator::registration($old['username'], $old['email'], $password, $confirm);

    // ...then check that the username and email aren't already taken
    $users = new User(db());
    if (!isset($errors['username']) && $users->usernameExists($old['username'])) {
        $errors['username'] = 'That username is already taken.';
    }
    if (!isset($errors['email']) && $users->emailExists($old['email'])) {
        $errors['email'] = 'That email is already registered.';
    }

    // No problems: create the account (password gets hashed inside User::create), log them in, and send them home
    if (!$errors) {
        try {
            $id = $users->create($old['username'], $old['email'], $password);
            Auth::login($id, $old['username']);
            flash('success', 'Welcome to the barangay kitchen, ' . $old['username'] . '!');
            redirect('index.php');
        } catch (PDOException $e) {
            // Two people can sign up with the same name at the same moment; the UNIQUE keys in the database catch that
            if ($e->getCode() !== '23000') { // duplicate key race; anything else is a real error
                throw $e;
            }
            $errors['username'] = 'That username or email was just taken. Try another.';
        }
    }
}

page_header($mode === 'register' ? 'Sign up' : 'Log in');
?>
<?php if ($mode === 'login'): ?>
<section class="form-card">
    <h1>Log in</h1>
    <?= field_error($errors, 'login') ?>
    <form method="post" action="auth.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login">

        <label for="login">Username or email</label>
        <input type="text" id="login" name="login" required maxlength="100" value="<?= e($old['login']) ?>">

        <?php // The password box is never refilled after a failed attempt ?>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required maxlength="72">

        <button type="submit" class="btn">Log in</button>
    </form>
    <p class="form-alt">New here? <a href="auth.php?mode=register">Create an account</a></p>
</section>

<?php else: ?>
<section class="form-card">
    <h1>Create your account</h1>
    <form method="post" action="auth.php?mode=register">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register">

        <?php // The HTML attributes (required, pattern, minlength...) check the same rules in the browser as Validator does on the server ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required
               pattern="<?= Validator::USERNAME_PATTERN ?>" minlength="3" maxlength="30"
               title="3-30 letters, numbers, or underscores"
               value="<?= e($old['username']) ?>">
        <?= field_error($errors, 'username') ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="100"
               value="<?= e($old['email']) ?>">
        <?= field_error($errors, 'email') ?>

        <?php // The pattern means 8-72 characters with at least one letter and one number ?>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" maxlength="72"
               pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}"
               title="At least 8 characters with a letter and a number">
        <?= field_error($errors, 'password') ?>

        <label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" required minlength="8" maxlength="72">
        <?= field_error($errors, 'confirm') ?>

        <button type="submit" class="btn">Sign up</button>
    </form>
    <p class="form-alt">Already a member? <a href="auth.php">Log in</a></p>
</section>
<?php endif; ?>
<?php page_footer(); ?>
