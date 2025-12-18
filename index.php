<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBRH - Base de Datos de Empleados</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Solución para el parpadeo de los modales en Alpine.js */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
    <div x-data="empleadoApp()" x-init="init()" class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <header class="bg-white shadow-sm rounded-xl mb-8 border border-slate-200">
            <div class="px-8 py-6">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Sistema DBRH</h1>
                        <p class="text-slate-500 mt-1">Base de Datos de Recursos Humanos</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="api/template_phpspreadsheet.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm">
                            <i class="fas fa-download mr-2"></i>
                            Descargar Plantilla
                        </a>
                        <a href="api/empleados.php?action=export" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm">
                            <i class="fas fa-file-export mr-2"></i>
                            Exportar Lista
                        </a>
                        <button @click="showImportModal = true" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm">
                            <i class="fas fa-file-excel mr-2"></i>
                            Importar Excel
                        </button>
                        <a href="admin.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm">
                            <i class="fas fa-cog mr-2"></i>
                            Administración
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Search Section -->
        <div class="bg-white shadow-sm rounded-xl mb-8 p-8 border border-slate-200">
            <h2 class="text-2xl font-semibold text-slate-900 mb-6">Búsqueda de Empleados</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="searchFicha" class="block text-sm font-medium text-slate-600 mb-2">Número de Ficha</label>
                    <div class="relative">
                        <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchFicha"
                               x-model.debounce.300ms="searchFicha" 
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                               placeholder="Ingrese número de ficha">
                    </div>
                </div>
                <div>
                    <label for="searchNombre" class="block text-sm font-medium text-slate-600 mb-2">Nombre</label>
                    <div class="relative">
                         <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchNombre"
                               x-model.debounce.300ms="searchNombre" 
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                               placeholder="Buscar por nombre">
                    </div>
                </div>
                <div>
                    <label for="searchDepto" class="block text-sm font-medium text-slate-600 mb-2">Departamento</label>
                    <select id="searchDepto" x-model="searchDepartamento" 
                            @change="searchEmployee()"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        <option value="">Todos los departamentos</option>
                        <template x-for="dept in departamentos">
                            <option :value="dept" x-text="dept"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div x-show="empleados.length > 0" class="space-y-6">
            <template x-for="empleado in empleados" :key="empleado.id">
                <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200 hover:shadow-md transition duration-300">
                    <div class="flex flex-col md:flex-row items-start space-y-4 md:space-y-0 md:space-x-6">
                        <!-- Photo -->
                        <div class="flex-shrink-0">
                            <div class="w-28 h-28 bg-slate-200 rounded-full flex items-center justify-center overflow-hidden ring-4 ring-white shadow-inner">
                                <template x-if="empleado.foto">
                                    <img :src="'uploads/fotos/' + empleado.foto" :alt="empleado.nombre" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!empleado.foto">
                                    <i class="fas fa-user text-4xl text-slate-400"></i>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Employee Info -->
                        <div class="flex-1 w-full">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-2xl font-bold text-slate-900" x-text="empleado.nombre"></h4>
                                    <p class="text-lg text-indigo-600 font-semibold">Ficha: <span x-text="empleado.ficha"></span></p>
                                </div>
                                <button @click="showEditModal(empleado)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm whitespace-nowrap">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4 text-sm">
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">Puesto:</span> <span class="text-slate-800" x-text="empleado.puesto || 'N/A'"></span></div>
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">Departamento:</span> <span class="text-slate-800" x-text="empleado.departamento || 'N/A'"></span></div>
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">Área:</span> <span class="text-slate-800" x-text="empleado.area || 'N/A'"></span></div>
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">CURP:</span> <span class="text-slate-800" x-text="empleado.curp || 'N/A'"></span></div>
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">RFC:</span> <span class="text-slate-800" x-text="empleado.rfc || 'N/A'"></span></div>
                                <div class="bg-slate-50 p-3 rounded-lg"><span class="font-medium text-slate-500 block">Salario Real:</span> <span class="text-slate-800 font-semibold" x-text="formatCurrency(empleado.sueldo_real_mas_bd)"></span></div>
                            </div>
                            
                            <div x-show="showDetails[empleado.id]" x-collapse class="mt-4 border-t border-slate-200 pt-4">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                    <div><span class="font-medium text-slate-500">Escolaridad:</span> <span x-text="empleado.escolaridad || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">Sexo:</span> <span x-text="empleado.sexo || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">Edad:</span> <span x-text="empleado.edad || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">Cumpleaños:</span> <span x-text="empleado.cumpleanos || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">Fecha Ingreso:</span> <span x-text="empleado.fecha_ingreso || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">IMSS:</span> <span x-text="empleado.imss || 'N/A'"></span></div>
                                    <div><span class="font-medium text-slate-500">Mensual con BD:</span> <span x-text="formatCurrency(empleado.mensual_con_bd)"></span></div>
                                    <div><span class="font-medium text-slate-500">Mensual sin BD:</span> <span x-text="formatCurrency(empleado.mensual_sin_bd)"></span></div>
                                    <div><span class="font-medium text-slate-500">BD:</span> <span x-text="formatCurrency(empleado.bd)"></span></div>
                                    <div><span class="font-medium text-slate-500">S.D.:</span> <span x-text="formatCurrency(empleado.sd)"></span></div>
                                    <div><span class="font-medium text-slate-500">SDI:</span> <span x-text="formatCurrency(empleado.sdi)"></span></div>
                                    <div><span class="font-medium text-slate-500">Sueldo Microsip:</span> <span x-text="formatCurrency(empleado.sueldo_microsip)"></span></div>
                                </div>
                                
                                <div x-show="empleado.campos_adicionales && empleado.campos_adicionales.length > 0" class="mt-4 pt-4 border-t border-slate-200">
                                    <h5 class="font-semibold text-slate-800 mb-2">Información Adicional:</h5>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                        <template x-for="campo in empleado.campos_adicionales">
                                            <div class="bg-indigo-50 p-3 rounded-lg">
                                                <span class="font-medium text-indigo-800" x-text="campo.nombre + ':'"></span>
                                                <!-- Mostrar imagen si es archivo de imagen -->
                                                <template x-if="isImageFile(campo.valor, campo.tipo)">
                                                    <div class="mt-2">
                                                        <img :src="getImagePath(campo.valor, empleado.id, campo.id)"
                                                             :alt="getFileName(campo.valor, campo.tipo)"
                                                             class="w-32 h-32 object-cover rounded-lg border border-indigo-200 cursor-pointer hover:scale-105 transition-transform shadow-md"
                                                             @click.stop="window.open(getImagePath(campo.valor, empleado.id, campo.id), '_blank')"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div class="hidden text-indigo-700 text-xs">
                                                            📁 <span x-text="getFileName(campo.valor, campo.tipo)"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <!-- Mostrar botón de descarga para archivos no imagen -->
                                                <template x-if="!isImageFile(campo.valor, campo.tipo) && campo.tipo === 'archivo'">
                                                    <div class="mt-2">
                                                        <button @click.stop="downloadFileView(empleado.id, campo.id, getFileName(campo.valor, campo.tipo))"
                                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                            <i class="fas fa-download mr-2"></i>
                                                            <span x-text="getFileName(campo.valor, campo.tipo)"></span>
                                                        </button>
                                                    </div>
                                                </template>
                                                <!-- Mostrar texto normal para otros tipos -->
                                                <template x-if="!isImageFile(campo.valor, campo.tipo) && campo.tipo !== 'archivo'">
                                                    <span class="block text-indigo-700" x-text="formatCampoAdicional(campo.valor, campo.tipo)"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <button @click="toggleDetails(empleado.id)" class="text-indigo-600 hover:text-indigo-800 font-medium mt-4 text-sm">
                                <span x-text="showDetails[empleado.id] ? 'Ocultar detalles' : 'Ver más detalles'"></span>
                                <i class="fas fa-chevron-down ml-1 transition-transform" :class="{'rotate-180': showDetails[empleado.id]}"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- No results message -->
        <div class="bg-white shadow-sm rounded-xl p-12 text-center border border-slate-200" x-show="searchPerformed && empleados.length === 0">
            <i class="fas fa-search text-6xl text-slate-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-slate-700 mb-2">No se encontraron resultados</h3>
            <p class="text-slate-500">Intente con otros criterios de búsqueda.</p>
        </div>
        
        <!-- Edit Employee Modal -->
        <div x-show="editModalOpen" x-cloak
             class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.outside="closeEditModal()" 
                 class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
                 x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <template x-if="editingEmployee">
                <div class="p-8">
                    <h3 class="text-2xl font-bold mb-6 text-slate-900">Editar Empleado</h3>
                    
                    <form @submit.prevent="saveEmployee()" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Ficha *</label>
                                <input type="text" x-model="editForm.ficha" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Nombre *</label>
                                <input type="text" x-model="editForm.nombre" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Puesto</label><input type="text" x-model="editForm.puesto" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Departamento</label><input type="text" x-model="editForm.departamento" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Área</label><input type="text" x-model="editForm.area" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Escolaridad</label><input type="text" x-model="editForm.escolaridad" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Sexo</label><select x-model="editForm.sexo" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option value="">Seleccionar...</option><option value="M">Masculino</option><option value="F">Femenino</option><option value="Otro">Otro</option></select></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Edad</label><input type="number" x-model="editForm.edad" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Año Nacimiento</label><input type="number" x-model="editForm.ano_nacimiento" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                        </div>
                        
                        <!-- Información salarial RESTAURADA -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Mensual con BD</label>
                                <input type="number" step="0.01" x-model="editForm.mensual_con_bd" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Mensual sin BD</label>
                                <input type="number" step="0.01" x-model="editForm.mensual_sin_bd" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">BD</label>
                                <input type="number" step="0.01" x-model="editForm.bd" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Cumpleaños</label><input type="date" x-model="editForm.cumpleanos" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">Fecha Ingreso</label><input type="date" x-model="editForm.fecha_ingreso" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">CURP</label><input type="text" x-model="editForm.curp" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">RFC</label><input type="text" x-model="editForm.rfc" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-sm font-medium text-slate-700 mb-2">IMSS</label><input type="text" x-model="editForm.imss" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                        </div>

                        <div x-show="camposAdicionales.length > 0">
                            <h4 class="text-lg font-semibold text-slate-800 mb-4 border-t pt-4">Campos Adicionales</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <template x-for="campo in camposAdicionales" :key="campo.id">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2" x-text="campo.nombre"></label>
                                        <template x-if="campo.tipo === 'texto'"><input type="text" :value="editForm.campos_adicionales[campo.id] || ''" @input="updateCampoAdicional(campo.id, $event.target.value)" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></template>
                                        <template x-if="campo.tipo === 'numero'"><input type="number" :value="editForm.campos_adicionales[campo.id] || ''" @input="updateCampoAdicional(campo.id, $event.target.value)" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></template>
                                        <template x-if="campo.tipo === 'fecha'"><input type="date" :value="editForm.campos_adicionales[campo.id] || ''" @input="updateCampoAdicional(campo.id, $event.target.value)" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></template>
                                        <template x-if="campo.tipo === 'textarea'"><textarea :value="editForm.campos_adicionales[campo.id] || ''" @input="updateCampoAdicional(campo.id, $event.target.value)" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></template>
                                        <template x-if="campo.tipo === 'select'"><select :value="editForm.campos_adicionales[campo.id] || ''" @change="updateCampoAdicional(campo.id, $event.target.value)" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option value="">Seleccionar...</option><template x-for="opcion in campo.opciones"><option :value="opcion" x-text="opcion"></option></template></select></template>
                                        
                                        <template x-if="campo.tipo === 'archivo'">
                                            <div class="space-y-2">
                                                <input type="file" :id="'archivo_' + campo.id" @change="handleFileUpload($event, campo.id)" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                                <div x-show="editForm.campos_adicionales[campo.id] && (!editForm.archivos || !editForm.archivos[campo.id])" class="flex items-center justify-between p-2 bg-slate-50 rounded-lg border">
                                                    <div class="flex items-center text-sm text-slate-600 truncate"><i class="fas fa-file mr-2"></i> <span class="truncate" x-text="editForm.campos_adicionales[campo.id]"></span></div>
                                                    <div class="flex space-x-2"><button type="button" @click="downloadFile(campo.id)" class="text-blue-600 hover:text-blue-800" title="Descargar"><i class="fas fa-download text-xs"></i></button><button type="button" @click="removeFile(campo.id)" class="text-red-600 hover:text-red-800" title="Eliminar"><i class="fas fa-trash text-xs"></i></button></div>
                                                </div>
                                                <div x-show="editForm.archivos && editForm.archivos[campo.id]" class="flex items-center justify-between p-2 bg-green-50 rounded-lg border border-green-200">
                                                    <div class="flex items-center text-sm text-green-700 truncate"><i class="fas fa-file mr-2"></i> <span class="truncate" x-text="editForm.archivos[campo.id]?.name || 'Archivo'"></span><span class="ml-1 text-xs">(nuevo)</span></div>
                                                    <button type="button" @click="removeFile(campo.id)" class="text-red-600 hover:text-red-800" title="Cancelar"><i class="fas fa-times text-xs"></i></button>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="campo.tipo === 'multiple'">
                                            <div class="space-y-2 p-3 bg-slate-50 rounded-lg border border-slate-200"><template x-for="(opcion, index) in campo.opciones" :key="index"><label class="flex items-center space-x-2"><input type="checkbox" :value="opcion" @change="handleMultipleChange($event, campo.id, opcion)" :checked="isMultipleSelected(campo.id, opcion)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span x-text="opcion" class="text-sm"></span></label></template></div>
                                        </template>

                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" @click="closeEditModal()" class="px-6 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition duration-200 font-semibold">Cancelar</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-semibold flex items-center justify-center min-w-[120px]" :disabled="isSaving">
                                <span x-show="!isSaving">Actualizar</span>
                                <span x-show="isSaving"><i class="fas fa-spinner fa-spin"></i> Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
                </template>
            </div>
        </div>

        <!-- Import Modal -->
        <div x-show="showImportModal" x-cloak 
             class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.outside="showImportModal = false" class="bg-white rounded-xl p-8 max-w-md w-full mx-4"
                 x-show="showImportModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <h3 class="text-xl font-bold mb-4 text-slate-900">📊 Importar Empleados</h3>
                <form @submit.prevent="importExcel">
                    <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <h4 class="text-sm font-semibold text-purple-800 mb-2">🔽 Paso 1: Descargar Plantilla</h4>
                        <p class="text-xs text-purple-700 mb-3">Asegúrate de que tu archivo Excel coincida con el formato de la plantilla oficial.</p>
                        <a href="api/template_phpspreadsheet.php" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center font-semibold">
                            <i class="fas fa-download mr-2"></i> Descargar Plantilla
                        </a>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-green-800 mb-2">📤 Paso 2: Subir Archivo</h4>
                        <label class="block text-sm text-slate-700 mb-2">Selecciona tu archivo Excel (.xlsx)</label>
                        <input type="file" name="excel_file" accept=".xlsx" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer" required>
                        <p class="text-xs text-slate-500 mt-1">Solo archivos .xlsx (máximo 20MB)</p>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 font-semibold">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold flex items-center justify-center min-w-[120px]" :disabled="importing">
                            <span x-show="!importing">Importar</span>
                            <span x-show="importing"><i class="fas fa-spinner fa-spin"></i> Importando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div x-show="imageModalOpen && currentImage.src !== ''"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center p-4"
         style="z-index: 9999;"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.outside="imageModalOpen = false; currentImage.src = ''; currentImage.name = ''" class="bg-white rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900">
                    <i class="fas fa-image text-indigo-600 mr-2"></i>
                    <span x-text="currentImage.name"></span>
                </h3>
                <button @click="imageModalOpen = false; currentImage.src = ''; currentImage.name = ''" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="text-center">
                <img :src="currentImage.src"
                     :alt="currentImage.name"
                     class="max-w-full max-h-[70vh] mx-auto rounded-lg shadow-lg"
                     style="object-fit: contain;">
            </div>

            <div class="flex justify-center mt-6">
                <button @click="imageModalOpen = false; currentImage.src = ''; currentImage.name = ''"
                        class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition duration-200">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        function empleadoApp() {
            return {
                searchFicha: '',
                searchNombre: '',
                searchDepartamento: '',
                empleados: [],
                departamentos: [],
                showDetails: {},
                searchPerformed: false,
                showImportModal: false,
                importing: false,
                editModalOpen: false,
                isSaving: false,
                imageModalOpen: false,
                currentImage: {
                    src: '',
                    name: ''
                },
                editForm: {
                    campos_adicionales: {},
                    archivos: {}
                },
                camposAdicionales: [],
                editingEmployee: null,

                init() {
                    this.loadDepartamentos();
                    this.loadCamposAdicionales();
                    this.$watch('searchFicha', () => this.searchEmployee());
                    this.$watch('searchNombre', () => this.searchEmployee());

                    // Asegurar que el modal esté cerrado al inicializar
                    this.imageModalOpen = false;
                    this.currentImage.src = '';
                    this.currentImage.name = '';
                },

                searchEmployee() {
                    if (!this.searchFicha && !this.searchNombre && !this.searchDepartamento) {
                        this.empleados = [];
                        this.searchPerformed = false;
                        return;
                    }
                    const formData = new FormData();
                    formData.append('action', 'search');
                    formData.append('ficha', this.searchFicha);
                    formData.append('nombre', this.searchNombre);
                    formData.append('departamento', this.searchDepartamento);

                    fetch('api/empleados.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            this.empleados = data.empleados || [];
                            this.searchPerformed = true;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.empleados = [];
                            this.searchPerformed = true;
                        });
                },

                loadDepartamentos() {
                    fetch('api/empleados.php?action=departamentos')
                        .then(response => response.json())
                        .then(data => { this.departamentos = data.departamentos || []; });
                },

                loadCamposAdicionales() {
                    fetch('api/campos.php?action=list')
                        .then(response => response.json())
                        .then(data => { this.camposAdicionales = data.campos || []; });
                },

                loadEmployeeDetails(employeeId) {
                    fetch(`api/empleados.php?id=${employeeId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.empleado) {
                                this.editForm = { ...data.empleado, campos_adicionales: {}, archivos: {} };
                                if (data.empleado.campos_adicionales && data.empleado.campos_adicionales.length > 0) {
                                    data.empleado.campos_adicionales.forEach(campo => {
                                        this.editForm.campos_adicionales[campo.id] = campo.valor;
                                    });
                                }
                                this.editModalOpen = true;
                            } else {
                                alert('No se pudo cargar la información del empleado');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading employee:', error);
                            alert('Error al cargar datos del empleado');
                        });
                },

                toggleDetails(id) {
                    this.showDetails[id] = !this.showDetails[id];
                },

                showEditModal(empleado) {
                    this.editingEmployee = JSON.parse(JSON.stringify(empleado)); // Deep copy
                    this.loadEmployeeDetails(empleado.id);
                },

                saveEmployee() {
                    this.isSaving = true;
                    const formData = new FormData();
                    formData.append('action', 'update');
                    formData.append('id', this.editingEmployee.id);
                    
                    Object.keys(this.editForm).forEach(key => {
                        if (key === 'campos_adicionales') {
                            Object.keys(this.editForm.campos_adicionales).forEach(campoId => {
                                formData.append(`campos_adicionales[${campoId}]`, this.editForm.campos_adicionales[campoId]);
                            });
                        } else if (key !== 'archivos') {
                            formData.append(key, this.editForm[key] || '');
                        }
                    });
                    
                    if (this.editForm.archivos) {
                        Object.keys(this.editForm.archivos).forEach(campoId => {
                            formData.append(`archivo_${campoId}`, this.editForm.archivos[campoId]);
                        });
                    }

                    fetch('api/empleados.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closeEditModal();
                                alert(data.message || 'Empleado actualizado exitosamente.');
                                this.searchEmployee(); // Refresh results
                            } else {
                                alert(data.error || 'Error al actualizar empleado');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al guardar los cambios');
                        })
                        .finally(() => {
                            this.isSaving = false;
                        });
                },

                closeEditModal() {
                    this.editModalOpen = false;
                    setTimeout(() => {
                        this.editingEmployee = null;
                        this.editForm = { campos_adicionales: {}, archivos: {} };
                    }, 300); // Wait for transition
                },

                updateCampoAdicional(campoId, value) {
                    if (!this.editForm.campos_adicionales) this.editForm.campos_adicionales = {};
                    this.editForm.campos_adicionales[campoId] = value;
                },

                handleFileUpload(event, campoId) {
                    const file = event.target.files[0];
                    if (!file) return;
                    if (file.size > 5 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 5MB.');
                        event.target.value = '';
                        return;
                    }
                    if (!this.editForm.archivos) this.editForm.archivos = {};
                    if (!this.editForm.campos_adicionales) this.editForm.campos_adicionales = {};
                    
                    this.editForm.archivos[campoId] = file;
                    this.editForm.campos_adicionales[campoId] = file.name;
                },

                removeFile(campoId) {
                    if (this.editForm.archivos && this.editForm.archivos[campoId]) {
                        delete this.editForm.archivos[campoId];
                    }
                    if (this.editForm.campos_adicionales) {
                       this.editForm.campos_adicionales[campoId] = '';
                    }
                    const fileInput = document.getElementById('archivo_' + campoId);
                    if (fileInput) fileInput.value = '';
                },

                downloadFile(campoId) {
                    if (this.editingEmployee && this.editingEmployee.id) {
                        fetch(`api/empleados.php?action=get_file&empleado_id=${this.editingEmployee.id}&campo_id=${campoId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.archivo_id) {
                                    window.open(`api/download.php?id=${data.archivo_id}`, '_blank');
                                } else {
                                    alert('Archivo no encontrado');
                                }
                            }).catch(error => alert('Error al buscar archivo'));
                    }
                },

                downloadFileView(empleadoId, campoId, fileName) {
                    fetch(`api/empleados.php?action=get_file&empleado_id=${empleadoId}&campo_id=${campoId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.archivo_id) {
                                window.open(`api/download.php?id=${data.archivo_id}`, '_blank');
                            } else {
                                alert('Archivo no encontrado');
                            }
                        })
                        .catch(error => {
                            console.error('Error al buscar archivo:', error);
                            alert('Error al descargar el archivo');
                        });
                },

                handleMultipleChange(event, campoId, opcion) {
                    if (!this.editForm.campos_adicionales) this.editForm.campos_adicionales = {};
                    let currentValues = [];
                    if (this.editForm.campos_adicionales[campoId]) {
                        try {
                            currentValues = JSON.parse(this.editForm.campos_adicionales[campoId]);
                            if (!Array.isArray(currentValues)) currentValues = [];
                        } catch (e) { currentValues = []; }
                    }
                    if (event.target.checked) {
                        if (!currentValues.includes(opcion)) currentValues.push(opcion);
                    } else {
                        currentValues = currentValues.filter(v => v !== opcion);
                    }
                    this.editForm.campos_adicionales[campoId] = JSON.stringify(currentValues);
                },

                isMultipleSelected(campoId, opcion) {
                    if (!this.editForm?.campos_adicionales?.[campoId]) return false;
                    try {
                        const currentValues = JSON.parse(this.editForm.campos_adicionales[campoId]);
                        return Array.isArray(currentValues) && currentValues.includes(opcion);
                    } catch (e) { return false; }
                },

                formatCurrency(amount) {
                    if (amount === null || amount === undefined) return '$0.00';
                    return '$' + parseFloat(amount).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatCampoAdicional(valor, tipo) {
                    if (!valor) return 'N/A';

                    // Si es una cadena que se ve como array JSON, convertirla
                    if (typeof valor === 'string' && (valor.startsWith('[') || valor.includes(','))) {
                        try {
                            const parsed = JSON.parse(valor);
                            if (Array.isArray(parsed)) {
                                return parsed.join(', ');
                            }
                        } catch (e) {
                            // Si no es JSON válido, intentar split por comas
                            if (valor.includes(',')) {
                                return valor.split(',').map(v => v.trim()).join(', ');
                            }
                        }
                    }

                    return valor;
                },

                isImageFile(valor, tipo) {
                    if (tipo !== 'archivo') return false;

                    try {
                        const fileInfo = JSON.parse(valor);
                        return fileInfo.isImage === true;
                    } catch (e) {
                        // Fallback: verificar extensión
                        if (typeof valor === 'string') {
                            const extension = valor.split('.').pop().toLowerCase();
                            return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension);
                        }
                        return false;
                    }
                },

                getFileName(valor, tipo) {
                    if (tipo !== 'archivo') return valor;

                    try {
                        const fileInfo = JSON.parse(valor);
                        return fileInfo.filename;
                    } catch (e) {
                        // Fallback: extraer nombre de la ruta
                        const parts = valor.split('/');
                        return parts[parts.length - 1];
                    }
                },

                getImagePath(valor, empleadoId, campoId) {
                    try {
                        const fileInfo = JSON.parse(valor);
                        // Usar el endpoint para servir imágenes de forma segura
                        return `api/get_image.php?empleado_id=${empleadoId}&campo_id=${campoId}`;
                    } catch (e) {
                        // Fallback para archivos que no son JSON
                        if (campoId) {
                            return `api/get_image.php?empleado_id=${empleadoId}&campo_id=${campoId}`;
                        }
                        return '';
                    }
                },

                showImageModal(imageSrc, imageName) {
                    if (imageSrc && imageSrc !== '') {
                        this.currentImage.src = imageSrc;
                        this.currentImage.name = imageName || 'Imagen';
                        this.imageModalOpen = true;
                    }
                },

                importExcel(event) {
                    const fileInput = event.target.querySelector('input[type="file"]');
                    if (!fileInput.files[0]) {
                        alert('Por favor seleccione un archivo');
                        return;
                    }
                    const file = fileInput.files[0];
                    if (!file.name.endsWith('.xlsx')) {
                        alert('❌ Formato de archivo no válido. Por favor seleccione un archivo Excel (.xlsx).');
                        return;
                    }
                    if (file.size > 20 * 1024 * 1024) {
                        alert('El archivo es muy grande. El tamaño máximo permitido es 20MB');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('excel_file', file);
                    formData.append('action', 'import_excel');
                    this.importing = true;
                    fetch('api/import_phpspreadsheet.php', { method: 'POST', body: formData })
                        .then(response => {
                            if (!response.ok) throw new Error(`Error del servidor: ${response.statusText}`);
                            return response.text();
                        })
                        .then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (data.success) {
                                    let message = `✅ ${data.message}\n\n` +
                                                  `📊 Resumen:\n` +
                                                  `• Total de filas: ${data.total_rows}\n` +
                                                  `• Importados: ${data.imported}\n` +
                                                  `• Omitidos: ${data.skipped}\n`;
                                    if (data.errors && data.errors.length > 0) {
                                        message += `\n⚠️ Errores (${data.errors.length}):\n${data.errors.slice(0, 5).join('\n')}`;
                                        if (data.errors.length > 5) message += `\n... y ${data.errors.length - 5} más`;
                                    }
                                    alert(message);
                                    this.showImportModal = false;
                                    fileInput.value = '';
                                    this.searchEmployee(); // Refresh
                                } else {
                                    alert(`❌ Error: ${data.error || 'Error desconocido al importar'}`);
                                }
                            } catch (parseError) {
                                console.error('JSON Parse Error:', parseError, 'Response text:', text);
                                alert('❌ Error del servidor: La respuesta no es válida.');
                            }
                        })
                        .catch(error => {
                            console.error('Import Error:', error);
                            alert(`❌ Error de conexión: ${error.message}`);
                        })
                        .finally(() => { this.importing = false; });
                }
            }
        }
    </script>
</body>
</html>

