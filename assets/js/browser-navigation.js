/**
 * Creates a hidden input element and appends it to the specified form.
 *
 * This function is used to dynamically add hidden inputs to a form, which can be useful for submitting data
 * without displaying the inputs to the user.
 *
 * @param {HTMLFormElement} form - The form element to which the hidden input should be appended.
 * @param {string} name - The name attribute for the hidden input.
 * @param {string} value - The value attribute for the hidden input.
 */
function createFormInput(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

/**
 * Submits a form to initiate a file download.
 *
 * This function creates a form with hidden inputs to specify the file to be downloaded and an action type,
 * and then submits the form to trigger the download. It prevents the default form submission behavior,
 * dynamically appends the form to the document, submits it, and then removes it.
 *
 * @param {Event} event - The event object representing the form submission event.
 * @param {string} filename - The name of the file to be downloaded.
 */
function submitDownloadFile(event, filename) {
    event.preventDefault();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    createFormInput(form, 'file', filename);
    createFormInput(form, 'action', 'downloadFile');

    document.body.appendChild(form);

    form.submit();

    document.body.removeChild(form);
}

/**
 * Submits a form to change the directory path.
 *
 * This function creates a form with hidden inputs to specify the new directory path and an action type,
 * and then submits the form to request the directory change. It prevents the default form submission behavior,
 * dynamically appends the form to the document, submits it, and then removes it.
 *
 * @param {Event} event - The event object representing the form submission event.
 * @param {string} path - The path of the directory to change to.
 */
function submitChangeDirectory(event, path) {
    event.preventDefault();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    createFormInput(form, 'path', path);
    createFormInput(form, 'action', 'changePath');

    document.body.appendChild(form);

    form.submit();

    document.body.removeChild(form);
}
