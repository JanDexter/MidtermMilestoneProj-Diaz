<?php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

// One recipe in full, plus its comments. Members only.
// Every button on this page posts back here with a hidden "action" field:
//   delete          -> delete this recipe (author only)
//   comment         -> post a new comment
//   comment_edit    -> save changes to your comment (?edit_comment=ID shows that comment as a form)
//   comment_delete  -> delete your comment
$userId   = Auth::require();
$recipes  = new Recipe(db());
$comments = new Comment(db());

// Look the recipe up from ?id=...; a missing or made-up id goes back to the list with a message.
$id = (int) ($_GET['id'] ?? 0);
$r  = $recipes->find($id, $userId);
if (!$r) {
    flash('error', 'That recipe does not exist.');
    redirect('index.php');
}

$recipeUrl = 'recipe.php?id=' . $id;
$action    = post_action();

// ----- Delete the recipe -----
if ($action === 'delete') {
    // The query itself checks ownership, so other members' recipes are never deleted.
    // (Its comments, ingredients and favorites disappear with it through ON DELETE CASCADE in the database.)
    if ($recipes->delete($id, $userId)) {
        flash('success', 'Recipe deleted.');
        redirect('index.php');
    }
    flash('error', 'You can only delete your own recipes.');
    redirect($recipeUrl);
}

// ----- Comments -----
$body       = '';  // the new-comment box
$errors     = [];
$editId     = (int) ($_GET['edit_comment'] ?? 0); // the comment being edited, if any
$editBody   = null; // what was typed in the edit box (null = start with the saved text)
$editErrors = [];

if ($action === 'comment') {
    $body   = trim((string) ($_POST['body'] ?? ''));
    $errors = Validator::comment($body);

    if (!$errors) {
        $comments->create($id, $userId, $body);
        flash('success', 'Comment posted.');
        // Redirect after saving so refreshing the page can't post the same comment twice
        redirect($recipeUrl . '#comments');
    }
    // If there were errors we fall through and redraw the page with the message and the typed text kept
}

if ($action === 'comment_edit') {
    $editId     = (int) ($_POST['comment_id'] ?? 0);
    $editBody   = trim((string) ($_POST['body'] ?? ''));
    $editErrors = Validator::comment($editBody); // same rule as when posting a comment

    if (!$editErrors) {
        // update() only changes the row if it belongs to this member; it also sets updated_at, which shows the "edited" tag
        $saved = $comments->update($editId, $userId, $editBody);
        flash($saved ? 'success' : 'error', $saved ? 'Comment updated.' : 'You can only edit your own comments.');
        redirect($recipeUrl . '#comment-' . $editId);
    }
}

if ($action === 'comment_delete') {
    // Same ownership check inside delete()
    $deleted = $comments->delete((int) ($_POST['comment_id'] ?? 0), $userId);
    flash($deleted ? 'success' : 'error', $deleted ? 'Comment deleted.' : 'You can only delete your own comments.');
    redirect($recipeUrl . '#comments');
}

// Everything the page below needs
$ingredients = $recipes->ingredients($id);
$commentList = $comments->forRecipe($id);
$isOwner     = (int) $r['user_id'] === $userId; // only the author gets the Edit / Delete buttons

page_header($r['title']);
?>
<?php // The recipe itself. Everything members typed goes through e() so it can't inject HTML ?>
<article class="recipe" id="recipe-<?= $id ?>">
    <span class="badge"><?= e($r['category']) ?></span>
    <h1><?= e($r['title']) ?></h1>
    <p class="meta">
        by <?= e($r['username']) ?> &middot; <?= e(format_date($r['created_at'])) ?>
        <?= edited_label($r['updated_at']) ?>
    </p>
    <p><?= e($r['description']) ?></p>

    <div class="actions">
        <?php favorite_button($r, $recipeUrl); ?>
        <?php // Edit and Delete are shown to the author only (the server checks again when they are used) ?>
        <?php if ($isOwner): ?>
            <a href="recipe_form.php?id=<?= $id ?>" class="btn btn-outline">Edit</a>
            <?php // data-confirm makes app.js ask "are you sure?" before the form is sent ?>
            <form method="post" action="<?= e($recipeUrl) ?>" class="inline-form" data-confirm="Delete this recipe? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        <?php endif; ?>
    </div>

    <?php // Ingredient list: amount and unit stand out ("1 1/2 cups"), then the name ?>
    <h2 class="section-title">Ingredients</h2>
    <ul>
        <?php foreach ($ingredients as $row): ?>
            <li>
                <?php if ($row['amount'] !== ''): ?><strong class="amount"><?= e(format_amount($row['amount'], $row['unit'])) ?></strong><?php endif; ?>
                <?= e($row['name']) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php // nl2br keeps the author's line breaks; e() runs first so the text stays safe ?>
    <h2 class="section-title">Steps</h2>
    <p><?= nl2br(e($r['steps'])) ?></p>
</article>

<?php // Comments from other members, oldest first ?>
<section id="comments">
    <h2 class="section-title">Comments (<?= count($commentList) ?>)</h2>

    <?php if (!$commentList): ?>
        <p>No comments yet. Be the first!</p>
    <?php endif; ?>

    <?php foreach ($commentList as $c): ?>
        <?php
        $commentId = (int) $c['id'];
        $isMine    = (int) $c['user_id'] === $userId; // members can only edit or delete their own comments
        ?>
        <div class="comment" id="comment-<?= $commentId ?>">
            <p class="meta">
                <strong><?= e($c['username']) ?></strong> &middot; <?= e(format_date($c['created_at'])) ?>
                <?= edited_label($c['updated_at']) ?>
            </p>

            <?php if ($isMine && $commentId === $editId): ?>
                <?php // Editing this comment: the text becomes a form, in place. Same limits as Validator::comment ?>
                <form method="post" action="<?= e($recipeUrl) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="comment_edit">
                    <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                    <label for="edit-body">Edit your comment</label>
                    <textarea id="edit-body" name="body" rows="4" required minlength="2" maxlength="1000"><?= e($editBody ?? $c['body']) ?></textarea>
                    <?= field_error($editErrors, 'body') ?>
                    <div class="actions">
                        <button type="submit" class="btn">Save changes</button>
                        <a href="<?= e($recipeUrl . '#comment-' . $commentId) ?>" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p><?= nl2br(e($c['body'])) ?></p>
                <?php if ($isMine): ?>
                    <div class="actions">
                        <a href="<?= e($recipeUrl . '&edit_comment=' . $commentId . '#comment-' . $commentId) ?>" class="btn btn-outline">Edit</a>
                        <form method="post" action="<?= e($recipeUrl) ?>" class="inline-form" data-confirm="Delete this comment?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="comment_delete">
                            <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php // New comment form; the length limits here match Validator::comment on the server ?>
    <form method="post" action="<?= e($recipeUrl) ?>" class="comment-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="comment">
        <label for="body">Leave a comment</label>
        <textarea id="body" name="body" rows="3" required minlength="2" maxlength="1000"><?= e($body) ?></textarea>
        <?= field_error($errors, 'body') ?>
        <button type="submit" class="btn">Post comment</button>
    </form>
</section>
<?php page_footer(); ?>
