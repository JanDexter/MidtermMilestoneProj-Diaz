// 1. Favorites: save/unsave without reloading the page.
// 2. Recipe form: add/remove ingredient fields.
// 3. Confirm before deleting.

// ----- Form submits: delete confirmation + favorites -----
// One listener on the whole page catches every form submit, so it also works for forms added later.
document.addEventListener('submit', function (e) {
    var form = e.target;

    // Forms with data-confirm (Delete buttons) ask "are you sure?" first; Cancel stops the submit.
    if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
        e.preventDefault();
        return;
    }

    // The heart button: stop the normal page reload and do it in the background instead.
    if (form.classList.contains('fav-form')) {
        e.preventDefault();
        toggleFavorite(form);
    }
});

// Sends the save/unsave to favorites.php with fetch (AJAX) and updates the button in place.
function toggleFavorite(form) {
    var button = form.querySelector('button');
    button.disabled = true; // blocks double-clicks while the request is on its way

    fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),                      // sends the same fields the form would (including the CSRF token)
        headers: { 'X-Requested-With': 'fetch' },      // tells the server to answer with JSON instead of a redirect
        credentials: 'same-origin'                     // include the session cookie so the server knows who is saving
    })
        .then(function (res) {
            if (!res.ok) { throw new Error('Request failed'); }
            return res.json();
        })
        .then(function (data) {
            // The server says whether the recipe is saved now; make the button match.
            var saved = data.favorited;
            form.elements.mode.value = saved ? 'remove' : 'add'; // next click does the opposite
            button.innerHTML = saved ? '&#9829; Saved' : '&#9825; Save';
            button.className = 'btn ' + (saved ? 'btn-fav-on' : 'btn-outline');
            button.disabled = false;

            // On the favorites page, an unsaved recipe disappears from the list.
            if (!saved && form.hasAttribute('data-remove-card')) {
                form.closest('.card').remove();
                // If that was the last card, show the "no favorites yet" message
                var empty = document.getElementById('fav-empty');
                if (empty && !document.querySelector('.card')) { empty.hidden = false; }
            }
        })
        .catch(function () {
            form.submit(); // fall back to a normal page reload
        });
}

// ----- Recipe form: ingredient fields -----
// Only runs on the recipe form, the one page that has #ingredient-list.
var list = document.getElementById('ingredient-list');

if (list) {
    var MAX_INGREDIENTS = 30; // same limit as Validator::MAX_INGREDIENTS on the server
    var addButton = document.getElementById('add-ingredient');

    // Empties every box in a row (used for new rows and for "removing" the last remaining row).
    function clearRow(row) {
        Array.prototype.forEach.call(row.querySelectorAll('input, select'), function (i) { i.value = ''; });
    }

    // Same rule as Validator::quantityValue(): 2, 1.5, 1/2 or 1 1/2, and above zero.
    // Returns the number, or 0 when the text isn't a valid amount.
    function quantityValue(text) {
        var m;
        if (/^\d+(\.\d{1,2})?$/.test(text)) { return parseFloat(text); } // plain number
        m = /^(?:(\d+) )?(\d+)\/(\d+)$/.exec(text);                      // fraction with an optional whole part
        if (m && parseInt(m[3], 10) > 0) {                               // bottom number can't be 0
            return (m[1] ? parseInt(m[1], 10) : 0) + parseInt(m[2], 10) / parseInt(m[3], 10);
        }
        return 0;
    }

    // Browser-side version of the server rules: at least one ingredient,
    // and every ingredient needs an amount (a number above zero), a unit, and a name.
    // setCustomValidity puts the message on the box; the browser then blocks the submit and shows it.
    function checkIngredients() {
        var any = false;
        Array.prototype.forEach.call(list.children, function (row) {
            var amount = row.querySelector('input[name="ingredient_amounts[]"]');
            var unit = row.querySelector('select[name="ingredient_units[]"]');
            var name = row.querySelector('input[name="ingredients[]"]');
            var text = amount.value.trim().replace(/\s+/g, ' ');
            var hasAmount = text !== '';
            var hasUnit = unit.value !== '';
            var hasName = name.value.trim() !== '';
            // A row with something in any of its boxes is "in use", so its other boxes become required.
            // A completely empty row is fine (the server ignores it).
            var filled = hasAmount || hasUnit || hasName;
            any = any || filled;

            var amountMsg = '';
            if (filled && !hasAmount) {
                amountMsg = 'Enter an amount, e.g. 2 or 1/2.';
            } else if (hasAmount && quantityValue(text) <= 0) {
                amountMsg = 'Amount must be a number above zero, like 2, 1.5, 1/2 or 1 1/2.';
            }
            amount.setCustomValidity(amountMsg);                                            // '' means "no problem"
            unit.setCustomValidity(filled && !hasUnit ? 'Choose a unit.' : '');
            name.setCustomValidity(filled && !hasName ? 'Enter the ingredient name.' : '');
        });
        // Every row empty: complain on the first name box so the form can't be sent with no ingredients
        if (!any) {
            list.querySelector('input[name="ingredients[]"]').setCustomValidity('Add at least one ingredient.');
        }
        addButton.disabled = list.children.length >= MAX_INGREDIENTS; // no more rows once the limit is reached
    }

    // "+ Add ingredient": copy the first row (so the dropdown options come along), empty it, and add it to the end.
    addButton.addEventListener('click', function () {
        var row = list.firstElementChild.cloneNode(true);
        clearRow(row);
        list.appendChild(row);
        row.querySelector('input').focus(); // ready to type the amount straight away
        checkIngredients();
    });

    // "Remove" buttons. One listener on the list covers rows added later too.
    list.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-ingredient')) { return; }
        var row = e.target.closest('.ingredient-row');
        if (list.children.length > 1) {
            row.remove();
        } else {
            clearRow(row); // always keep one row on screen, so just empty it
        }
        checkIngredients();
    });

    // Re-check whenever anything changes, then once now for the rows already on the page.
    list.addEventListener('input', checkIngredients);
    list.addEventListener('change', checkIngredients); // selects fire change, not always input
    checkIngredients();

    // ----- Ingredient suggestions -----
    // While the member types in an ingredient name box, a short list of names that other recipes already use
    // appears under it. Nothing shows until something is typed (not on click or focus). The names are already in
    // the page (the JSON block under the form), so matching is instant: no waiting on the server.
    var namesData = document.getElementById('ingredient-names');
    if (namesData) {
        var MAX_SUGGESTIONS = 6;
        // Lower-case copies are made once, so each keystroke only has to compare strings
        var names = JSON.parse(namesData.textContent).map(function (n) { return { text: n, lower: n.toLowerCase() }; });

        // One list shared by every row; it moves to whichever name box is being typed in.
        var popup = document.createElement('ul');
        popup.id = 'ingredient-suggestions';
        popup.className = 'suggestions';
        popup.setAttribute('role', 'listbox');
        popup.hidden = true;
        document.body.appendChild(popup);

        var nameBox = null;  // the box the list belongs to right now
        var current = -1;    // the highlighted suggestion (-1 = none), moved with the arrow keys

        function closeSuggestions() {
            popup.hidden = true;
            current = -1;
            if (nameBox) {
                nameBox.setAttribute('aria-expanded', 'false');
                nameBox.removeAttribute('aria-activedescendant');
            }
        }

        function highlight(index) {
            var items = popup.children;
            if (current >= 0) { items[current].classList.remove('active'); }
            current = index;
            if (index >= 0) {
                items[index].classList.add('active');
                nameBox.setAttribute('aria-activedescendant', items[index].id);
                items[index].scrollIntoView({ block: 'nearest' });
            } else {
                nameBox.removeAttribute('aria-activedescendant');
            }
        }

        function showSuggestions(input) {
            var typed = input.value.trim();
            var query = typed.toLowerCase();
            if (query === '') { closeSuggestions(); return; }

            // Names that start with what was typed come first, then names that only contain it.
            // A name identical to what is already typed is not worth suggesting.
            var starts = [], contains = [];
            names.forEach(function (n) {
                var at = n.lower.indexOf(query);
                if (n.text === typed || at === -1) { return; }
                (at === 0 ? starts : contains).push(n.text);
            });
            var matches = starts.concat(contains).slice(0, MAX_SUGGESTIONS);
            if (!matches.length) { closeSuggestions(); return; }

            popup.textContent = '';
            matches.forEach(function (text, i) {
                var item = document.createElement('li');
                item.id = 'ingredient-suggestion-' + i;
                item.setAttribute('role', 'option');
                item.textContent = text; // textContent, not innerHTML, so a name can never be read as HTML
                popup.appendChild(item);
            });

            // Sit directly under the box, as wide as the box
            var box = input.getBoundingClientRect();
            popup.style.left = (box.left + window.pageXOffset) + 'px';
            popup.style.top = (box.bottom + window.pageYOffset + 2) + 'px';
            popup.style.width = box.width + 'px';

            nameBox = input;
            current = -1;
            popup.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        // Use the chosen name, close the list, and re-run the browser-side checks for the row
        function chooseSuggestion(text) {
            nameBox.value = text;
            closeSuggestions();
            checkIngredients();
        }

        // Typing is the only thing that opens the list. One listener on the rows covers rows added later too.
        list.addEventListener('input', function (e) {
            if (e.target.name === 'ingredients[]') { showSuggestions(e.target); }
        });

        // Arrow keys move through the list, Enter takes the highlighted one (and only then does Enter not submit the form), Esc/Tab close it
        list.addEventListener('keydown', function (e) {
            if (popup.hidden || e.target !== nameBox) { return; }
            var count = popup.children.length;
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight((current + 1) % count); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(current <= 0 ? count - 1 : current - 1); }
            else if (e.key === 'Enter' && current >= 0) { e.preventDefault(); chooseSuggestion(popup.children[current].textContent); }
            else if (e.key === 'Escape' || e.key === 'Tab') { closeSuggestions(); }
        });

        // mousedown (not click) so the box doesn't lose focus first; preventDefault keeps the focus in the box
        popup.addEventListener('mousedown', function (e) {
            var item = e.target.closest('li');
            if (item) { e.preventDefault(); chooseSuggestion(item.textContent); }
        });

        list.addEventListener('focusout', function (e) {
            if (e.target === nameBox) { closeSuggestions(); } // clicked or tabbed away
        });
        window.addEventListener('resize', closeSuggestions); // the list is placed with fixed numbers, so don't leave it behind
    }
}
