# Barangay Recipe Sharing Site — Midterm Milestone Project

## The Scenario

Your barangay's home cooks want a small website where they can share their recipes with each other. Visitors must have an account before they can do anything. Once logged in, members can browse what others have posted, find recipes they like, save favorites, and leave feedback. Members should only be able to change what they themselves posted.

Build this site using what we covered this term:

- **Back end:** plain PHP with sessions, OOP, and PDO
- **Front end:** HTML, CSS, and JavaScript from the prelim
- **No PHP frameworks**

---

## What the Site Should Let Members Do

- **Account management** — Create an account, log in, and log out. Pages meant for members should not be reachable by guests.
- **Share a recipe** — Include a title, a short description, a category, a list of ingredients, and the cooking steps. Since recipes have different numbers of ingredients, the form should let the member add more ingredient fields as needed.
- **Browse and search** — See the latest recipes first, look up recipes by keyword, and narrow them down by category.
- **View a recipe** — Open a recipe to see everything about it, including what other members said.
- **Comment** — Leave feedback on recipes.
- **Favorites** — Save recipes to a personal favorites list, and remove them, without the page reloading.
- **Edit and delete** — Fix or remove their own recipes and comments, and let others see that something was changed after posting.

---

## Your Own Design Decisions

- **Data relationships** — Design how they relate, and which keys and constraints keep your data clean.
- **Class structure** — Decide which parts of your code become classes.
- **Validation rules** — Decide your own validation rules, and enforce them on both the browser side and the server side.
- **Theme** — Pick a theme for your site (e.g., Filipino home cooking, baking, budget meals, regional dishes) and design the look around it.
- **Custom feature** — Add one small feature of your own that is not listed here. _(recipe suggestion based on series of questions)_

---

## Required Database Tables

Your database must use exactly these six tables, with these names:

| # | Table | Description |
|---|-------|-------------|
| 1 | `users` | the members of the site |
| 2 | `categories` | the recipe categories (fill this with your own starting categories) |
| 3 | `recipes` | the recipes members post |
| 4 | `ingredients` | the ingredients of each recipe, one row per ingredient |
| 5 | `comments` | what members say about a recipe |
| 6 | `favorites` | which member saved which recipe |

---

## Non-Negotiables

- **Hashed passwords** — Passwords must never be stored as plain text.
- **Single connection file** — Your database connection lives in one file that the other pages include.
- **SQL injection protection** — Any query that uses user input must be protected against SQL injection.
- **Safe output** — Anything a user typed must be displayed safely.
- **Responsive layout** — The layout must work on both phone and desktop screens.
