function checkStrength() {

    let input = document.getElementById('password');
    let meter = document.getElementById('pass-meter');
    let suggestBox = document.getElementById('pass-suggestions');
    let info = zxcvbn(input.value);
    let state = null;

    // Remove previous states
    meter.classList.remove('bad', 'warn', 'good', 'str-1', 'str-2', 'str-3', 'str-4');

    switch(info.score) {
        case 1:
            state = 'bad';
            break;
        case 2:
        case 3:
            state = 'warn';
            break;
        case 4:
            state = 'good';
    }

    let score = 'str-'+ info.score.toString();

    meter.classList.add(state);
    meter.classList.add(score);
    suggestBox.innerText = info.feedback.suggestions.join(' ');
}

function checkPasswordMatch() {
    let origPass = document.getElementById('password').value;
    let thisPass = document.getElementById('pass_confirm').value;

    console.log(origPass, thisPass);

    if(thisPass == null) {
        document.getElementById('pass-match').style.display = 'none';
        document.getElementById('pass-not-match').style.display = 'none';
    } else if (thisPass === origPass) {
        document.getElementById('pass-match').style.display = 'inline-block';
        document.getElementById('pass-not-match').style.display = 'none';
    } else {
        document.getElementById('pass-match').style.display = 'none';
        document.getElementById('pass-not-match').style.display = 'inline-block';
    }
}
