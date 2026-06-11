// Search, filter and sort for gymnast table
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchInput');
    const filterProgram = document.getElementById('filterProgram');
    const filterStatus = document.getElementById('filterStatus');
    const tableBody = document.querySelector('#gymnastsTable tbody');

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const programValue = filterProgram.value.toLowerCase();
        const statusValue = filterStatus.value.toLowerCase();

        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(function (row) {
            const name = row.cells[0].textContent.toLowerCase();
            const membershipID = row.cells[1].textContent.toLowerCase();
            const program = row.cells[2].textContent.toLowerCase();
            const status = row.cells[4].textContent.toLowerCase();

            const matchesSearch = name.includes(searchValue) || membershipID.includes(searchValue);
            const matchesProgram = programValue === '' || program.includes(programValue);
            const matchesStatus = statusValue === '' || status.includes(statusValue);

            if (matchesSearch && matchesProgram && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Run filter when anything changes
    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterProgram) filterProgram.addEventListener('change', filterTable);
    if (filterStatus) filterStatus.addEventListener('change', filterTable);

});