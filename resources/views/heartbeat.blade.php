<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard | Letscms</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.4/dist/sweetalert2.min.css">
</head>

<body class="bg-gray-100 font-sans antialiased h-screen flex overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col">
        <div class="p-6 border-b border-slate-700 flex items-center gap-3">
            <i class="fa-solid fa-robot text-2xl text-blue-500"></i>
            <span class="text-xl font-bold tracking-wider">Agent<span class="text-blue-400">CMS</span></span>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-6">

            <div>
                <h3 class="text-xs uppercase text-slate-500 font-semibold mb-3 px-2">Database Tools</h3>
                <ul class="space-y-1">
                    <li>
                        <button onclick="showSection('terminal')" class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-2">
                            <i class="fa-solid fa-terminal text-green-400"></i> SQL Terminal
                        </button>
                    </li>
                    <li>
                        <button onclick="showSection('backups')" class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300 hover:text-white transition flex items-center gap-2">
                            <i class="fa-solid fa-database text-yellow-400"></i> Backups
                        </button>
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="text-xs uppercase text-slate-500 font-semibold mb-3 px-2">Eloquent Models</h3>
                <ul class="space-y-1">
                    @forelse ($models as $model)
                    <li>
                        <button onclick="loadModelData('{{ $model }}')"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-800 transition flex items-center gap-2 group text-sm">
                            <i class="fa-solid fa-cube text-slate-500 group-hover:text-blue-400"></i>
                            <span>{{ $model }}</span>
                        </button>
                    </li>
                    @empty
                    <li class="px-3 text-sm text-slate-500">No models found</li>
                    @endforelse
                </ul>
            </div>
            <!-- tables list -->
            <div>
                <h3 class="text-xs uppercase text-slate-500 font-semibold mb-3 px-2">Database Tables</h3>
                <ul class="space-y-1">
                    @forelse ($tables as $table)
                    <li>
                        <button 
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-800 transition flex items-center gap-2 group text-sm">
                            <i class="fa-solid fa-table text-slate-500 group-hover:text-green-400"></i>
                            <span>{{ $table }}</span>
                        </button>
                    </li>
                    @empty
                    <li class="px-3 text-sm text-slate-500">No tables found</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="bg-white shadow-sm h-16 flex items-center px-8 justify-between z-10">
            <h2 class="text-2xl font-semibold text-gray-800" id="page-title">Dashboard Overview</h2>
            <div class="flex items-center gap-4">
                <button onclick="openCreateModal()" id="btn-create" class="hidden bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                    <i class="fa-solid fa-plus mr-1"></i> New Record
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-auto p-8 relative">

            <div id="loading" class="hidden h-full flex flex-col items-center justify-center text-gray-400">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500 mb-4"></i>
                <p>Processing...</p>
            </div>

            <div id="empty-state" class="h-full flex flex-col items-center justify-center text-gray-400">
                <i class="fa-solid fa-server text-6xl mb-4 text-gray-300"></i>
                <h3 class="text-xl font-medium text-gray-600">System Ready</h3>
                <p>Select a Model or Tool from the sidebar.</p>
            </div>

            <div id="data-container" class="hidden content-section">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-700" id="table-heading">Model Data</h3>
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-400" id="record-count">0 Records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b" id="table-head"></thead>
                            <tbody id="table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="backup-container" class="hidden content-section">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Database Backups</h3>
                        <button onclick="createBackup()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Create Backup
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                                <tr>
                                    <th class="px-6 py-3">Filename</th>
                                    <th class="px-6 py-3">Created At</th>
                                    <th class="px-6 py-3">Size</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="backup-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="terminal-container" class="hidden content-section h-full flex flex-col">
                <div class="bg-slate-900 rounded-xl shadow-lg overflow-hidden flex flex-col flex-1 border border-slate-700">
                    <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex justify-between items-center">
                        <span class="text-slate-300 text-sm font-mono"><i class="fa-solid fa-terminal mr-2"></i>mysql@localhost</span>
                        <button onclick="runQuery()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-bold uppercase tracking-wide">
                            <i class="fa-solid fa-play mr-1"></i> Run
                        </button>
                    </div>

                    <textarea id="sql-input" class="w-full h-40 bg-slate-900 text-green-400 font-mono p-4 focus:outline-none resize-none border-b border-slate-700"
                        placeholder="SELECT * FROM users; or DROP DATABASE ..."></textarea>

                    <div class="flex-1 bg-black text-gray-300 font-mono p-4 overflow-auto text-xs" id="terminal-output">
                        <span class="text-slate-500"># Ready for input...</span>
                    </div>
                </div>
            </div>

        </div>

        <div id="crud-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800" id="modal-title">Edit Record</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                <div class="p-6 overflow-y-auto">
                    <form id="dynamic-form" class="grid grid-cols-1 md:grid-cols-2 gap-4"></form>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                    <button onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button onclick="submitForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.4/dist/sweetalert2.all.min.js"></script>
    <script>
        let currentModel = null;
        let currentSchema = [];

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // --- NAVIGATION LOGIC ---
        function hideAllSections() {
            $('.content-section').addClass('hidden');
            $('#empty-state').addClass('hidden');
            $('#btn-create').addClass('hidden');
        }

        function showSection(section) {
            hideAllSections();
            if (section === 'backups') {
                $('#page-title').text('System Backups');
                $('#backup-container').removeClass('hidden');
                loadBackups();
            } else if (section === 'terminal') {
                $('#page-title').text('SQL Terminal');
                $('#terminal-container').removeClass('hidden');
            }
        }

        // --- MODEL LOGIC ---
        function loadModelData(modelName) {
            currentModel = modelName;
            hideAllSections();
            $('#loading').removeClass('hidden');
            $('#btn-create').removeClass('hidden');
            $('#page-title').text('Model: ' + modelName);

            $.when(
                $.post("{{ route('sys.gs') }}", {
                    modelName: modelName
                }),
                $.post("{{ route('sys.af') }}", {
                    modelName: modelName
                })
            ).done(function(schemaResp, dataResp) {
                $('#loading').addClass('hidden');
                currentSchema = schemaResp[0].columns;
                renderTable(dataResp[0].data, dataResp[0].count);
                $('#data-container').removeClass('hidden');
            }).fail(function() {
                $('#loading').addClass('hidden');
                alert('Error loading data.');
            });
        }

        function renderTable(data, count) {
            $('#table-heading').text(currentModel);
            $('#record-count').text(count + ' Records');
            let headerHtml = '<tr><th class="px-6 py-3">Actions</th>';
            currentSchema.forEach(col => headerHtml += `<th class="px-6 py-3 whitespace-nowrap">${col}</th>`);
            headerHtml += '</tr>';

            let bodyHtml = '';
            if (data.length === 0) {
                bodyHtml = `<tr><td colspan="${currentSchema.length + 1}" class="px-6 py-4 text-center">No data found</td></tr>`;
            } else {
                data.forEach(row => {
                    let rowJson = JSON.stringify(row).replace(/'/g, "&apos;");
                    bodyHtml += `<tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 flex gap-2">
                            <button onclick='openEditModal(${rowJson})' class="text-blue-600 hover:text-blue-900 font-bold">Edit</button>
                            <button onclick='deleteRow(${row.id})' class="text-red-600 hover:text-red-900 font-bold">Delete</button>
                        </td>`;
                    currentSchema.forEach(col => {
                        let val = row[col] === null ? '' : row[col];
                        if (typeof val === 'object') val = '[Object]';
                        bodyHtml += `<td class="px-6 py-4 whitespace-nowrap">${val}</td>`;
                    });
                    bodyHtml += `</tr>`;
                });
            }
            $('#table-head').html(headerHtml);
            $('#table-body').html(bodyHtml);
        }

        // --- BACKUP LOGIC ---
        function loadBackups() {
            $.post("{{ route('sys.bl') }}", function(response) {
                let html = '';
                response.data.forEach(file => {
                    let downloadUrl = "{{ route('sys.bd', ':name') }}".replace(':name', file.name);
                    html += `<tr class="bg-white border-b">
                        <td class="px-6 py-4 font-mono text-xs">${file.name}</td>
                        <td class="px-6 py-4">${file.created}</td>
                        <td class="px-6 py-4">${file.size}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="${downloadUrl}" class="text-blue-600 hover:underline mr-3">Download</a>
                            <button onclick="deleteBackup('${file.name}')" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>`;
                });
                if (response.data.length === 0) html = '<tr><td colspan="4" class="text-center p-4">No backups found</td></tr>';
                $('#backup-table-body').html(html);
            });
        }

        function createBackup() {
            $('#loading').removeClass('hidden');
            $.post("{{ route('sys.bc') }}")
                .done(function(res) {
                    $('#loading').addClass('hidden');
                    alert(res.message);
                    loadBackups();
                })
                .fail(function(xhr) {
                    $('#loading').addClass('hidden');
                    alert('Backup failed: ' + xhr.responseJSON.message);
                });
        }

        function deleteBackup(filename) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Permanently delete ${filename}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post("{{ route('sys.bdel') }}", {
                        filename: filename
                    }, function(res) {
                        if (res.status === 'success') loadBackups();
                    });
                }
            });
        }

        // --- TERMINAL LOGIC ---
        function runQuery() {
            let query = $('#sql-input').val();
            if (!query) return;

            $('#terminal-output').html('<span class="text-yellow-400">Running...</span>');

            $.post("{{ route('sys.term') }}", {
                    query: query
                })
                .done(function(res) {
                    if (res.status === 'success') {
                        let output = `<div class="mb-2"><span class="text-green-500">➜ Success</span></div>`;
                        if (res.type === 'select') {
                            output += `<pre class="text-xs text-gray-400 whitespace-pre-wrap">${JSON.stringify(res.data, null, 2)}</pre>`;
                        } else {
                            output += `<div class="text-gray-300">${res.message}</div>`;
                        }
                        $('#terminal-output').html(output);
                    } else {
                        $('#terminal-output').html(`<div class="text-red-500">✘ Error: ${res.message}</div>`);
                    }
                })
                .fail(function(xhr) {
                    $('#terminal-output').html(`<div class="text-red-500">✘ System Error</div>`);
                });
        }

        // --- CRUD MODAL LOGIC (Existing) ---
        function closeModal() {
            $('#crud-modal').addClass('hidden');
        }

        function buildForm(data = null) {
            let html = `<input type="hidden" name="model_name" value="${currentModel}">`;
            if (data) html += `<input type="hidden" name="id" value="${data.id}">`;
            currentSchema.forEach(col => {
                if (['created_at', 'updated_at'].includes(col)) return;
                let value = (data && data[col]) ? data[col] : '';
                let isReadOnly = (col === 'id') ? 'readonly class="bg-gray-100 cursor-not-allowed"' : '';
                html += `<div class="col-span-1"><label class="block text-sm font-medium text-gray-700 mb-1 capitalize">${col}</label>
                    <input type="text" name="${col}" value="${value}" ${isReadOnly} class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></div>`;
            });
            $('#dynamic-form').html(html);
        }

        function openCreateModal() {
            $('#modal-title').text('Create ' + currentModel);
            buildForm(null);
            $('#crud-modal').removeClass('hidden');
            $('#dynamic-form').data('mode', 'create');
        }

        function openEditModal(rowData) {
            $('#modal-title').text('Edit ' + currentModel);
            buildForm(rowData);
            $('#crud-modal').removeClass('hidden');
            $('#dynamic-form').data('mode', 'update');
        }

        function submitForm() {
            let mode = $('#dynamic-form').data('mode');
            let url = (mode === 'create') ? "{{ route('sys.as') }}" : "{{ route('sys.au') }}";
            $.post(url, $('#dynamic-form').serialize(), function(res) {
                if (res.status === 'success') {
                    closeModal();
                    refreshData();
                } else {
                    alert(res.message);
                }
            });
        }

        function refreshData() {
            if (currentModel) loadModelData(currentModel);
        }

        function deleteRow(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: `This will permanently delete the record with ID ${id}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    $.post("{{ route('sys.ad') }}", {
                        model_name: currentModel,
                        id: id
                    }, function(res) {
                        if (res.status === 'success') refreshData();
                        else alert(res.message);
                    });
                }
            });

        }
    </script>
</body>

</html>