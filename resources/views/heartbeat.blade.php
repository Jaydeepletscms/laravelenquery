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
        /* Custom Scrollbar for the table */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased h-screen flex overflow-hidden">

    <aside class="w-64 bg-slate-800 text-white flex-shrink-0 hidden md:flex flex-col">
        <div class="p-6 border-b border-slate-700 flex items-center gap-3">
            <i class="fa-solid fa-robot text-2xl text-blue-400"></i>
            <span class="text-xl font-bold tracking-wider">Agent<span class="text-blue-400">CMS</span></span>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-6">
            
            <div>
                <h3 class="text-xs uppercase text-slate-400 font-semibold mb-3 px-2">Eloquent Models</h3>
                <ul class="space-y-1">
                    @forelse ($models as $model)
                    <li>
                        <button onclick="loadModelData('{{ $model }}')" 
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-700 transition flex items-center gap-2 group">
                            <i class="fa-solid fa-cube text-slate-500 group-hover:text-blue-400"></i>
                            <span>{{ $model }}</span>
                        </button>
                    </li>
                    @empty
                    <li class="px-3 text-sm text-slate-500">No models found</li>
                    @endforelse
                </ul>
            </div>

            <div>
                <h3 class="text-xs uppercase text-slate-400 font-semibold mb-3 px-2">Database Tables</h3>
                <ul class="space-y-1">
                    @foreach ($tables as $table)
                    <li class="px-3 py-2 text-sm text-slate-400 flex items-center gap-2">
                        <i class="fa-solid fa-table text-slate-600"></i>
                        {{ $table }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        
        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center gap-2 text-sm text-green-400">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                System Online
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
    
    <header class="bg-white shadow-sm h-16 flex items-center px-8 justify-between z-10">
        <h2 class="text-2xl font-semibold text-gray-800" id="page-title">Dashboard Overview</h2>
        <div class="flex items-center gap-4">
            <button onclick="openCreateModal()" id="btn-create" class="hidden bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                <i class="fa-solid fa-plus mr-1"></i> Create New
            </button>
            <button onclick="refreshData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                <i class="fa-solid fa-rotate-right mr-1"></i> Refresh
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8 relative">
        
        <div id="loading" class="hidden h-full flex flex-col items-center justify-center text-gray-400">
             <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500 mb-4"></i>
             <p>Processing...</p>
        </div>

        <div id="empty-state" class="h-full flex flex-col items-center justify-center text-gray-400">
             <i class="fa-solid fa-chart-simple text-6xl mb-4 text-gray-300"></i>
             <h3 class="text-xl font-medium text-gray-600">Select a Model</h3>
        </div>

        <div id="data-container" class="hidden">
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
    </div>

    <div id="crud-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800" id="modal-title">Edit Record</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form id="dynamic-form" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    </form>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button onclick="submitForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
            </div>
        </div>
    </div>

</main>

<script>
    let currentModel = null;
    let currentSchema = []; // Store column names

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // 1. Load Data
    function loadModelData(modelName) {
        currentModel = modelName;
        $('#loading').removeClass('hidden');
        $('#empty-state').addClass('hidden');
        $('#data-container').addClass('hidden');
        $('#btn-create').removeClass('hidden'); // Show create button
        $('#page-title').text('Model: ' + modelName);

        // Get Schema first (to know columns if table is empty), then get Data
        $.when(
            $.post("{{ route('agent.schema') }}", { modelName: modelName }),
            $.post("{{ route('agent.fetch') }}", { modelName: modelName })
        ).done(function(schemaResp, dataResp) {
            $('#loading').addClass('hidden');
            
            // schemaResp[0] is the JSON response
            currentSchema = schemaResp[0].columns;
            renderTable(dataResp[0].data, dataResp[0].count);

        }).fail(function() {
            $('#loading').addClass('hidden');
            alert('Error loading model data.');
        });
    }

    function refreshData() {
        if(currentModel) loadModelData(currentModel);
    }

    // 2. Render Table with Actions
    function renderTable(data, count) {
        $('#table-heading').text(currentModel);
        $('#record-count').text(count + ' Records');
        
        let headerHtml = '<tr><th class="px-6 py-3">Actions</th>'; // Add Actions Column
        currentSchema.forEach(col => {
            headerHtml += `<th class="px-6 py-3 whitespace-nowrap">${col}</th>`;
        });
        headerHtml += '</tr>';

        let bodyHtml = '';
        if (data.length === 0) {
            bodyHtml = `<tr><td colspan="${currentSchema.length + 1}" class="px-6 py-4 text-center">No data found</td></tr>`;
        } else {
            data.forEach(row => {
                // Convert row object to safe JSON string for the Edit button
                let rowJson = JSON.stringify(row).replace(/'/g, "&apos;");
                
                bodyHtml += `<tr class="bg-white border-b hover:bg-gray-50">`;
                // Edit Button
                bodyHtml += `
                    <td class="px-6 py-4">
                        <button onclick='openEditModal(${rowJson})' class="text-blue-600 hover:text-blue-900 font-bold">
                            Edit
                        </button>
                        <button onclick='deleteRow(${row.id})' class="text-red-600 hover:text-red-900 font-bold ml-2">
                            Delete
                        </button>
                    </td>`;
                
                currentSchema.forEach(col => {
                    let val = row[col] === null ? '' : row[col];
                    if (typeof val === 'object') val = '[Object]';
                    // Limit text length
                    if (String(val).length > 50) val = String(val).substring(0, 50) + '...';
                    bodyHtml += `<td class="px-6 py-4 whitespace-nowrap">${val}</td>`;
                });
                bodyHtml += `</tr>`;
            });
        }

        $('#table-head').html(headerHtml);
        $('#table-body').html(bodyHtml);
        $('#data-container').removeClass('hidden');
    }

    // 3. Modal Logic
    function closeModal() {
        $('#crud-modal').addClass('hidden');
    }

    // Generate Form Fields Dynamically
    function buildForm(data = null) {
        let html = '';
        // Hidden input for Model Name
        html += `<input type="hidden" name="model_name" value="${currentModel}">`;
        
        // If editing, add ID
        if(data) {
            html += `<input type="hidden" name="id" value="${data.id}">`;
        }

        currentSchema.forEach(col => {
            // Skip internal timestamps if creating
            if(['created_at', 'updated_at'].includes(col)) return;

            let value = (data && data[col]) ? data[col] : '';
            let isReadOnly = (col === 'id') ? 'readonly class="bg-gray-100 cursor-not-allowed"' : '';
            
            html += `
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1 capitalize">${col.replace(/_/g, ' ')}</label>
                    <input type="text" name="${col}" value="${value}" ${isReadOnly} 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            `;
        });
        $('#dynamic-form').html(html);
    }

    function openCreateModal() {
        $('#modal-title').text('Create New ' + currentModel);
        buildForm(null); // Pass null for empty form
        $('#crud-modal').removeClass('hidden');
        $('#dynamic-form').data('mode', 'create');
    }

    function openEditModal(rowData) {
        $('#modal-title').text('Edit ' + currentModel + ' #' + rowData.id);
        buildForm(rowData); // Pass row data to fill inputs
        $('#crud-modal').removeClass('hidden');
        $('#dynamic-form').data('mode', 'update');
    }

    function deleteRow(id) {
        if(!confirm('Are you sure you want to delete this record?')) return;

        $.ajax({
            url: "{{ route('agent.delete') }}",
            type: "POST",
            data: { model_name: currentModel, id: id },
            success: function(response) {
                if (response.status === 'success') {
                    alert('Record deleted successfully.');
                    refreshData();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('System Error: ' + xhr.responseText);
            }
        });
    }

    // 4. Submit Logic
    function submitForm() {
        let mode = $('#dynamic-form').data('mode');
        let formData = $('#dynamic-form').serialize();
        let url = (mode === 'create') ? "{{ route('agent.store') }}" : "{{ route('agent.update') }}";

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    closeModal();
                    // Show small notification or alert
                    refreshData(); // Reload table
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('System Error: ' + xhr.responseText);
            }
        });
    }
</script>

    
</body>
</html>