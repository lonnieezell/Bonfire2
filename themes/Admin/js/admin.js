/**
 * Select All checkbox for data tables
 * using plain javascript
 */
function toggleSelectAll(checkbox) {
    var table = checkbox.closest('table')
    var checkboxes = table.getElementsByTagName('input')

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type == 'checkbox') {
            checkboxes[i].checked = checkbox.checked
        }
    }
}

// Check if there is a .select-all element on the page
const selectAllElement = document.querySelector('.select-all');

// Attach the event listener only if the element exists
if (selectAllElement) {
    selectAllElement.addEventListener('click', function (e) {
        toggleSelectAll(e.target);
    });
}
