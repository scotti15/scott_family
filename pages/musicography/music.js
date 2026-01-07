// Load DataTables dynamically
const loadScripts = [
    "https://code.jquery.com/jquery-3.7.1.min.js",
    "https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js",
    "https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"
];

function loadAllScripts(list, callback) {
    let loaded = 0;
    list.forEach(src => {
        const s = document.createElement("script");
        s.src = src;
        s.onload = () => {
            if (++loaded === list.length) callback();
        };
        document.body.appendChild(s);
    });
}

loadAllScripts(loadScripts, () => {
    $(document).ready(function() {

        let table = $('#musicTable').DataTable({
            pageLength: 25,
            responsive: true,
            // Your original order was [0,3,1]. After inserting Type at the front,
            // every index increments by 1 -> [1,4,2]
            order: [
                [1, 'asc'],  // Artist (now column 1)
                [4, 'asc'],  // Album  (now column 4)
                [2, 'asc']   // Track  (now column 2)
            ]
        });

        // Filter bindings (column indexes after Type insertion)
        $('#filterType').on('keyup', () => table.column(0).search($('#filterType').val()).draw());   // Type
        $('#filterArtist').on('keyup', () => table.column(1).search($('#filterArtist').val()).draw()); // Artist
        $('#filterTrack').on('keyup', () => table.column(2).search($('#filterTrack').val()).draw());   // Track
        $('#filterAlbum').on('keyup', () => table.column(4).search($('#filterAlbum').val()).draw());   // Album
        $('#filterGenre').on('keyup', () => table.column(7).search($('#filterGenre').val()).draw());   // Genre (shifted +1)

        // Clear filters helper button
        $('#clearFilters').on('click', function() {
            $('#filterType, #filterArtist, #filterTrack, #filterAlbum, #filterGenre').val('');
            table.search('').columns().search('').draw();
        });

    });
});
