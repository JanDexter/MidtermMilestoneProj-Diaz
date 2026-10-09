# Kaon — Barangay Recipe Sharing Site

A small recipe-sharing website for a barangay's home cooks. Members sign up, post recipes, browse and search what
others shared, save favorites, and leave comments. Built for the midterm milestone project (see
[requirements.md](requirements.md)) with plain PHP, PDO, MySQL, HTML, CSS and JavaScript — no frameworks.

**Theme:** Filipino home cooking — warm cream and orange, Roboto Light, categories such as *Ulam*, *Kakanin*,
*Merienda* and *Pancit & Noodles*.

## Features

| Requirement | Where it lives |
|---|---|
| Create account, log in, log out; guests are kept out of member pages | [auth.php](auth.php), `Auth::require()` at the top of every member page |
| Share a recipe (title, description, category, ingredients, steps) with add/remove ingredient fields | [recipe_form.php](recipe_form.php) + [assets/js/app.js](assets/js/app.js) |
| Latest recipes first, keyword search, category filter (plus ingredient and "most ingredients" filters) | [index.php](index.php), `Recipe::search()` |
| View a recipe with its comments | [recipe.php](recipe.php) |
| Comment, edit and delete your own comments | [recipe.php](recipe.php) (comments edit in place) |
| Favorites — save and remove without a page reload | [favorites.php](favorites.php) + `fetch()` in [assets/js/app.js](assets/js/app.js) |
| Edit and delete your own recipes; an "edited" tag shows on anything changed after posting | [recipe_form.php](recipe_form.php), [recipe.php](recipe.php), `updated_at` columns |
| **Custom feature:** recipe suggestion from a series of questions | [suggest.php](suggest.php) + [src/Suggester.php](src/Suggester.php) |

### Ingredient suggestions
As you type in an ingredient box on the recipe form, a short list (up to 6) of names already used by other recipes
appears under it. It stays hidden until you type, and it filters instantly in the browser. Use the mouse, or the
arrow keys and Enter (Esc closes it). If a typed name matches one that exists (capitals are ignored), the existing
spelling is saved instead of a new variant, so "SUGAR" and "sugar" don't become two ingredients. A name that
doesn't exist yet can still be typed.

### "What should I cook?" (custom feature)
Three questions, one per page: the kind of dish, an ingredient to use up, and how many ingredients you're up for. Every
choice is built from the real recipes in the database, and each question only offers choices that the earlier answers
leave at least one recipe for, with the recipe count shown next to each. At the end it picks up to three random
matches, and "Pick again" draws new ones.

## Setup (XAMPP)

1. Put this folder in `C:\xampp\htdocs\` (it is already named `MidtermMilestoneProj-Diaz`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`), create a database named **`barangay_recipes`**
   (collation `utf8mb4_unicode_ci`), select it, and **Import** [barangay_recipes.sql](barangay_recipes.sql).
   The dump has the six tables, the starting categories and a few member accounts. It has no recipes, so sign up
   (or use one of those accounts) and share the first one.
4. Browse to `http://localhost/MidtermMilestoneProj-Diaz/` and either sign up or log in.

The database settings (host, name, user, password) are constants at the top of [includes/db.php](includes/db.php).
XAMPP's defaults (`root`, no password) are already set. Change them there and nowhere else.

Requires PHP 8.0 or newer (the code uses `match` and constructor property promotion).

## Project structure

```
auth.php            Log in, sign up, log out (?mode=register shows the sign-up form)
index.php           Browse + search + filters
recipe.php          One recipe, its comments, recipe delete, comment post/edit/delete
recipe_form.php     Share a new recipe / edit your own
favorites.php       Your saved recipes; also receives the save/unsave posts
suggest.php         "What should I cook?" pages

includes/
  db.php            The only place that connects to the database (PDO)
  init.php          Included by every page: session, autoloader, CSRF check
  helpers.php       Small functions: e(), redirect(), flash messages, CSRF, date/amount formatting
  layout.php        Shared HTML: page_header(), page_footer(), recipe_card(), favorite_button()

src/                One class per concept; loaded by the autoloader in init.php
  Auth.php          Who is logged in (session)
  User.php          Create accounts, check logins
  Category.php      Read the categories
  Recipe.php        List, search, find, create, update, delete recipes and their ingredients
  Comment.php       List, create, update, delete comments
  Favorite.php      Save, unsave, list a member's favorites
  Suggester.php     Everything behind the "What should I cook?" feature
  Validator.php     All server-side validation rules

assets/css/style.css   Theme and responsive layout
assets/js/app.js       Favorites without reload, ingredient rows, delete confirmation
barangay_recipes.sql   Database structure + starting data
```

Pages with more than one form (`auth.php`, `recipe.php`) tell them apart with a hidden `action` field, which keeps
related features in one file.

## Database

Exactly the six required tables. Foreign keys keep the data clean:

| Table | Notes |
|---|---|
| `users` | `username` and `email` are `UNIQUE`; only a password hash is stored |
| `categories` | `name` is `UNIQUE`; filled with eight starting categories |
| `recipes` | belongs to a user (`ON DELETE CASCADE`) and a category (cannot delete a category that is in use); `updated_at` is `NULL` until the first edit |
| `ingredients` | one row per ingredient (`amount`, `unit`, `name`); deleted with its recipe |
| `comments` | belongs to a recipe and a user; `updated_at` marks edits; deleted with either |
| `favorites` | composite primary key `(user_id, recipe_id)`, so a recipe can only be saved once per member |

## Validation

Every rule is checked in the browser (HTML attributes and JavaScript, for instant feedback) **and** again on the
server in [src/Validator.php](src/Validator.php), because a browser check can be bypassed.

| Field | Rule |
|---|---|
| Username | 3–30 letters, numbers or underscores; unique |
| Email | valid address, up to 100 characters; unique |
| Password | 8–72 characters with at least one letter and one number |
| Recipe title / description / steps | 3–150 / 10–500 / 10–5000 characters |
| Category | must be one that exists |
| Ingredients | 1 to 30 per recipe; each needs an amount above zero (`2`, `1.5`, `1/2` or `1 1/2`), a unit from the list, and a name up to 100 characters |
| Comment | 2–1000 characters |

## Security

- **Hashed passwords** — `password_hash()` / `password_verify()`; plain passwords are never stored.
- **SQL injection** — every query that touches user input is a PDO prepared statement with real (non-emulated) prepares.
- **Safe output** — everything a member typed goes through `e()` (`htmlspecialchars`) before it is printed.
- **CSRF** — every form carries a per-session token that [includes/init.php](includes/init.php) checks on every POST.
- **Only your own content** — the owner check is part of the SQL (`WHERE id = … AND user_id = …`), not just a hidden button.
- **Sessions** — the id is renewed on login, and the cookie is `HttpOnly` and `SameSite=Lax`.
- **Redirects** — the "go back" address on the favorite button is checked so it can only point at this site.

## Responsive layout

One stylesheet works from phone to desktop. Below 640px the menu collapses into a burger (CSS only, no JavaScript),
cards and forms go full width, and each ingredient row reflows so the name gets its own line.
