/**
 * Adds event listeners to table rows to display context-specific actions in a command line element.
 *
 * This script waits for the DOM to be fully loaded, then it selects all table rows (`<tr>`) within a table body (`<tbody>`) and adds a `mouseover` event listener to each row.
 * When a row is hovered over, it updates a `.command-line` element with context-specific text based on the row's content and class.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get all the <tr> elements within the <tbody>
    const rows = document.querySelectorAll('tbody tr');

    // Add a mouseover event listener to each row
    rows.forEach(row => {
        row.addEventListener('mouseover', function() {
            // Get the text content of the first <td> within the hovered <tr>
            const firstCellText = row.querySelector('td').textContent.trim();

            // Determine the action based on the class of the <tr>
            let actionText = '';
            if (row.classList.contains('file')) {
                actionText = 'Download file';
            } else if (row.classList.contains('dir')) {
                actionText = 'Navigate to folder...';
            }

            // Update the .command-line element with the text and action
            const commandLine = document.querySelector('.command-line');
            commandLine.textContent = `- ${firstCellText} | ${actionText}`;
        });
    });
});
