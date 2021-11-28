/**
 * If exist url param r, change selected option
 */
function onloadPage(selectElement) {

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const opts = selectElement.options;

    for (var opt, j = 0; opt = opts[j]; j++) {

        if (opt.value == urlParams.get('r')) {
            selectElement.selectedIndex = j;
            break;
        }

    }

}

window.addEventListener('load', (event) => {

    //Select dropdown
    const selectElement = document.getElementsByName('resource')[0];

    onloadPage(selectElement)

    //On change, redirect to selected resource
    selectElement.addEventListener('change', (event) => {
        location.href = "?r=" + event.target.value ;
    });



});
