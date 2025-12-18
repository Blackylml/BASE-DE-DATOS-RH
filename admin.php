<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - DBRH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div x-data="adminApp()" class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white shadow-lg rounded-lg mb-8">
            <div class="px-8 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Administración DBRH</h1>
                        <p class="text-gray-600 mt-2">Gestión de empleados y campos adicionales</p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="api/empleados.php?action=export" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center text-sm">
                            <i class="fas fa-file-export mr-2"></i>
                            Exportar Lista
                        </a>
                        <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white shadow-lg rounded-lg mb-8">
            <div class="border-b border-gray-200">
                <nav class="flex">
                    <button @click="activeTab = 'empleados'" 
                            :class="activeTab === 'empleados' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition duration-200">
                        <i class="fas fa-users mr-2"></i>
                        Empleados
                    </button>
                    <button @click="activeTab = 'campos'" 
                            :class="activeTab === 'campos' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition duration-200">
                        <i class="fas fa-plus-square mr-2"></i>
                        Campos Adicionales
                    </button>
                </nav>
            </div>
        </div>

        <!-- Empleados Tab -->
        <div x-show="activeTab === 'empleados'" class="bg-white shadow-lg rounded-lg">
            <div class="px-8 py-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Gestión de Empleados</h2>
                    <button @click="showEmpleadoModal = true; editingEmpleado = null; resetEmpleadoForm()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Agregar Empleado
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <input type="text" 
                               x-model="empleadoSearch" 
                               @input="searchEmpleados()"
                               class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Buscar por ficha o nombre...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Empleados Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ficha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="empleado in empleados" :key="empleado.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="empleado.ficha"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="empleado.nombre"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="empleado.puesto"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="empleado.departamento"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button @click="editEmpleado(empleado)" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteEmpleado(empleado.id)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Campos Tab -->
        <div x-show="activeTab === 'campos'" class="bg-white shadow-lg rounded-lg">
            <div class="px-8 py-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Campos Adicionales</h2>
                    <button @click="showCampoModal = true; editingCampo = null; resetCampoForm()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Agregar Campo
                    </button>
                </div>

                <!-- Campos Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="campo in campos" :key="campo.id">
                        <div class="border rounded-lg p-6 hover:shadow-md transition duration-200" :class="campo.activo ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800" x-text="campo.nombre"></h3>
                                    <p class="text-sm text-gray-600 capitalize" x-text="campo.tipo"></p>
                                </div>
                                <div class="flex space-x-2">
                                    <button @click="editCampo(campo)" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="toggleCampo(campo.id)" 
                                            :class="campo.activo ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'">
                                        <i :class="campo.activo ? 'fas fa-pause' : 'fas fa-play'"></i>
                                    </button>
                                    <button @click="deleteCampo(campo.id)" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <p><strong>Usado por:</strong> <span x-text="campo.uso_count"></span> empleados</p>
                                <p><strong>Estado:</strong> 
                                    <span :class="campo.activo ? 'text-green-600' : 'text-red-600'">
                                        <span x-text="campo.activo ? 'Activo' : 'Inactivo'"></span>
                                    </span>
                                </p>
                            </div>
                            
                            <div x-show="campo.opciones && campo.opciones.length > 0" class="mt-3">
                                <p class="text-sm font-medium text-gray-700 mb-1">Opciones:</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="opcion in campo.opciones.slice(0, 3)">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded" x-text="opcion"></span>
                                    </template>
                                    <span x-show="campo.opciones.length > 3" class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">
                                        +<span x-text="campo.opciones.length - 3"></span> más
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal para Empleados -->
        <div x-show="showEmpleadoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-8">
                    <h3 class="text-2xl font-bold mb-6" x-text="editingEmpleado ? 'Editar Empleado' : 'Agregar Empleado'"></h3>
                    
                    <form @submit.prevent="saveEmpleado()" class="space-y-6">
                        <!-- Campos básicos del empleado -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ficha *</label>
                                <input type="text" x-model="empleadoForm.ficha" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                                <input type="text" x-model="empleadoForm.nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <!-- Más campos básicos -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Puesto</label>
                                <input type="text" x-model="empleadoForm.puesto" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                                <input type="text" x-model="empleadoForm.departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
                                <input type="text" x-model="empleadoForm.area" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Información personal -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escolaridad</label>
                                <input type="text" x-model="empleadoForm.escolaridad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                                <select x-model="empleadoForm.sexo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                                <input type="number" x-model="empleadoForm.edad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Año Nacimiento</label>
                                <input type="number" x-model="empleadoForm.ano_nacimiento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Información salarial -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mensual con BD</label>
                                <input type="number" step="0.01" x-model="empleadoForm.mensual_con_bd" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mensual sin BD</label>
                                <input type="number" step="0.01" x-model="empleadoForm.mensual_sin_bd" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">BD</label>
                                <input type="number" step="0.01" x-model="empleadoForm.bd" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Fechas y documentos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cumpleaños</label>
                                <input type="date" x-model="empleadoForm.cumpleanos" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Ingreso</label>
                                <input type="date" x-model="empleadoForm.fecha_ingreso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Documentos oficiales -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CURP</label>
                                <input type="text" x-model="empleadoForm.curp" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">RFC</label>
                                <input type="text" x-model="empleadoForm.rfc" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">IMSS</label>
                                <input type="text" x-model="empleadoForm.imss" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Campos adicionales dinámicos -->
                        <div x-show="camposAdicionales.length > 0">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Campos Adicionales</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <template x-for="campo in camposAdicionales" :key="campo.id">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" x-text="campo.nombre"></label>
                                        <template x-if="campo.tipo === 'texto'">
                                            <input type="text" x-model="empleadoForm.campos_adicionales[campo.id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </template>
                                        <template x-if="campo.tipo === 'numero'">
                                            <input type="number" x-model="empleadoForm.campos_adicionales[campo.id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </template>
                                        <template x-if="campo.tipo === 'fecha'">
                                            <input type="date" x-model="empleadoForm.campos_adicionales[campo.id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </template>
                                        <template x-if="campo.tipo === 'textarea'">
                                            <textarea x-model="empleadoForm.campos_adicionales[campo.id]" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                        </template>
                                        <template x-if="campo.tipo === 'select'">
                                            <select x-model="empleadoForm.campos_adicionales[campo.id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">Seleccionar...</option>
                                                <template x-for="opcion in campo.opciones">
                                                    <option :value="opcion" x-text="opcion"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="campo.tipo === 'archivo'">
                                            <div class="space-y-2">
                                                <input type="file" :id="'archivo_' + campo.id" @change="handleFileUpload($event, campo.id)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                
                                                <!-- Archivo existente -->
                                                <div x-show="empleadoForm.campos_adicionales && empleadoForm.campos_adicionales[campo.id] && (!empleadoForm.archivos || !empleadoForm.archivos[campo.id])" class="flex items-center justify-between p-2 bg-gray-50 rounded border">
                                                    <div class="flex items-center text-sm text-gray-600">
                                                        <i class="fas fa-file mr-2"></i>
                                                        <span x-text="empleadoForm.campos_adicionales[campo.id]"></span>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button type="button" @click="downloadFile(campo.id)" class="text-blue-600 hover:text-blue-800" title="Descargar">
                                                            <i class="fas fa-download text-xs"></i>
                                                        </button>
                                                        <button type="button" @click="removeFile(campo.id)" class="text-red-600 hover:text-red-800" title="Eliminar">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <!-- Archivo nuevo seleccionado -->
                                                <div x-show="empleadoForm.archivos && empleadoForm.archivos[campo.id]" class="flex items-center justify-between p-2 bg-green-50 rounded border border-green-200">
                                                    <div class="flex items-center text-sm text-green-700">
                                                        <i class="fas fa-file mr-2"></i>
                                                        <span x-text="empleadoForm.archivos && empleadoForm.archivos[campo.id] ? empleadoForm.archivos[campo.id].name : ''"></span>
                                                        <span class="ml-1 text-xs">(nuevo)</span>
                                                    </div>
                                                    <button type="button" @click="removeFile(campo.id)" class="text-red-600 hover:text-red-800" title="Cancelar">
                                                        <i class="fas fa-times text-xs"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="campo.tipo === 'multiple'">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Selecciona múltiples opciones:</label>
                                                <template x-for="(opcion, index) in campo.opciones" :key="index">
                                                    <label class="flex items-center space-x-2">
                                                        <input type="checkbox" 
                                                               :value="opcion" 
                                                               @change="handleMultipleChange($event, campo.id, opcion)"
                                                               :checked="isMultipleSelected(campo.id, opcion)"
                                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        <span x-text="opcion" class="text-sm"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" @click="showEmpleadoModal = false" class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                <span x-text="editingEmpleado ? 'Actualizar' : 'Crear'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para Campos -->
        <div x-show="showCampoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div class="bg-white rounded-lg max-w-2xl w-full mx-4">
                <div class="p-8">
                    <h3 class="text-2xl font-bold mb-6" x-text="editingCampo ? 'Editar Campo' : 'Agregar Campo'"></h3>
                    
                    <form @submit.prevent="saveCampo()" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                                <input type="text" x-model="campoForm.nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                                <select x-model="campoForm.tipo" @change="resetOpciones()" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="texto">Texto</option>
                                    <option value="numero">Número</option>
                                    <option value="fecha">Fecha</option>
                                    <option value="textarea">Área de texto</option>
                                    <option value="select">Lista desplegable</option>
                                    <option value="multiple">Múltiples valores</option>
                                    <option value="archivo">Archivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Opciones para select y multiple -->
                        <div x-show="campoForm.tipo === 'select' || campoForm.tipo === 'multiple'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Opciones</label>
                            <div class="space-y-2">
                                <template x-for="(opcion, index) in campoForm.opciones" :key="index">
                                    <div class="flex space-x-2">
                                        <input type="text" x-model="campoForm.opciones[index]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Opción...">
                                        <button type="button" @click="removeOpcion(index)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addOpcion()" class="w-full px-3 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-green-300 hover:text-green-600 transition duration-200">
                                    <i class="fas fa-plus mr-2"></i>
                                    Agregar opción
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" @click="showCampoModal = false" class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                <span x-text="editingCampo ? 'Actualizar' : 'Crear'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function adminApp() {
            return {
                activeTab: 'empleados',
                empleados: [],
                campos: [],
                camposAdicionales: [],
                empleadoSearch: '',
                showEmpleadoModal: false,
                showCampoModal: false,
                editingEmpleado: null,
                editingCampo: null,
                empleadoForm: {},
                campoForm: {},

                init() {
                    this.loadEmpleados();
                    this.loadCampos();
                    this.loadCamposAdicionales();
                },

                loadEmpleados() {
                    fetch('api/empleados.php')
                    .then(response => response.json())
                    .then(data => {
                        this.empleados = data.empleados || [];
                    });
                },

                loadCampos() {
                    fetch('api/campos.php?action=list&include_inactive=true')
                    .then(response => response.json())
                    .then(data => {
                        this.campos = data.campos || [];
                    });
                },

                loadCamposAdicionales() {
                    fetch('api/campos.php?action=list')
                    .then(response => response.json())
                    .then(data => {
                        this.camposAdicionales = data.campos || [];
                    });
                },

                searchEmpleados() {
                    if (!this.empleadoSearch) {
                        this.loadEmpleados();
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'search');
                    // Buscar tanto por ficha como por nombre
                    formData.append('ficha', this.empleadoSearch);
                    formData.append('nombre', this.empleadoSearch);

                    fetch('api/empleados.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.empleados = data.empleados || [];
                    });
                },

                resetEmpleadoForm() {
                    this.empleadoForm = {
                        ficha: '',
                        nombre: '',
                        puesto: '',
                        departamento: '',
                        area: '',
                        campos_adicionales: {},
                        archivos: {}
                    };
                },

                resetCampoForm() {
                    this.campoForm = {
                        nombre: '',
                        tipo: '',
                        opciones: []
                    };
                },

                editEmpleado(empleado) {
                    this.editingEmpleado = empleado;
                    this.empleadoForm = { ...empleado };
                    if (!this.empleadoForm.campos_adicionales) {
                        this.empleadoForm.campos_adicionales = {};
                    }
                    if (!this.empleadoForm.archivos) {
                        this.empleadoForm.archivos = {};
                    }
                    this.showEmpleadoModal = true;
                },

                editCampo(campo) {
                    this.editingCampo = campo;
                    this.campoForm = {
                        id: campo.id,
                        nombre: campo.nombre,
                        tipo: campo.tipo,
                        opciones: [...(campo.opciones || [])]
                    };
                    this.showCampoModal = true;
                },

                saveEmpleado() {
                    const action = this.editingEmpleado ? 'update' : 'create';
                    const formData = new FormData();
                    
                    formData.append('action', action);
                    if (this.editingEmpleado) {
                        formData.append('id', this.editingEmpleado.id);
                    }
                    
                    Object.keys(this.empleadoForm).forEach(key => {
                        if (key === 'campos_adicionales') {
                            Object.keys(this.empleadoForm.campos_adicionales).forEach(campoId => {
                                formData.append(`campos_adicionales[${campoId}]`, this.empleadoForm.campos_adicionales[campoId]);
                            });
                        } else if (key === 'archivos') {
                            // Los archivos se manejan por separado
                            return;
                        } else {
                            formData.append(key, this.empleadoForm[key] || '');
                        }
                    });
                    
                    // Agregar archivos si existen
                    if (this.empleadoForm.archivos) {
                        Object.keys(this.empleadoForm.archivos).forEach(campoId => {
                            formData.append(`archivo_${campoId}`, this.empleadoForm.archivos[campoId]);
                        });
                    }

                    fetch('api/empleados.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showEmpleadoModal = false;
                            this.loadEmpleados();
                            alert(data.message);
                        } else {
                            alert(data.error || 'Error al guardar empleado');
                        }
                    });
                },

                saveCampo() {
                    const action = this.editingCampo ? 'update' : 'create';
                    
                    fetch('api/campos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: action,
                            id: this.editingCampo ? this.editingCampo.id : null,
                            nombre: this.campoForm.nombre,
                            tipo: this.campoForm.tipo,
                            opciones: this.campoForm.opciones.filter(o => o.trim())
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showCampoModal = false;
                            this.loadCampos();
                            this.loadCamposAdicionales();
                            alert(data.message);
                        } else {
                            alert(data.error || 'Error al guardar campo');
                        }
                    });
                },

                deleteEmpleado(id) {
                    if (confirm('¿Está seguro de eliminar este empleado?')) {
                        fetch('api/empleados.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ action: 'delete', id: id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.loadEmpleados();
                                alert(data.message);
                            } else {
                                alert(data.error || 'Error al eliminar empleado');
                            }
                        });
                    }
                },

                deleteCampo(id) {
                    if (confirm('¿Está seguro de eliminar este campo?')) {
                        fetch('api/campos.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ action: 'delete', id: id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.loadCampos();
                                this.loadCamposAdicionales();
                                alert(data.message);
                            } else {
                                alert(data.error || 'Error al eliminar campo');
                            }
                        });
                    }
                },

                toggleCampo(id) {
                    fetch('api/campos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ action: 'toggle', id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.loadCampos();
                            this.loadCamposAdicionales();
                            alert(data.message);
                        } else {
                            alert(data.error || 'Error al cambiar estado del campo');
                        }
                    });
                },

                addOpcion() {
                    this.campoForm.opciones.push('');
                },

                removeOpcion(index) {
                    this.campoForm.opciones.splice(index, 1);
                },

                resetOpciones() {
                    if (!['select', 'multiple'].includes(this.campoForm.tipo)) {
                        this.campoForm.opciones = [];
                    } else if (this.campoForm.opciones.length === 0) {
                        this.campoForm.opciones = [''];
                    }
                },
                handleFileUpload(event, campoId) {
                    const file = event.target.files[0];
                    if (file) {
                        // Validar tamaño máximo (5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('El archivo es demasiado grande. Máximo 5MB.');
                            event.target.value = '';
                            return;
                        }
                        
                        // Almacenar el archivo para enviarlo después
                        if (!this.empleadoForm.archivos) {
                            this.empleadoForm.archivos = {};
                        }
                        this.empleadoForm.archivos[campoId] = file;
                        
                        // Mostrar el nombre del archivo
                        if (!this.empleadoForm.campos_adicionales) {
                            this.empleadoForm.campos_adicionales = {};
                        }
                        this.empleadoForm.campos_adicionales[campoId] = file.name;
                    }
                },
                removeFile(campoId) {
                    if (this.empleadoForm.archivos && this.empleadoForm.archivos[campoId]) {
                        delete this.empleadoForm.archivos[campoId];
                    }
                    if (this.empleadoForm.campos_adicionales) {
                        this.empleadoForm.campos_adicionales[campoId] = '';
                    }
                    
                    // Limpiar el input file
                    const fileInput = document.getElementById('archivo_' + campoId);
                    if (fileInput) {
                        fileInput.value = '';
                    }
                },
                handleMultipleChange(event, campoId, opcion) {
                    if (!this.empleadoForm.campos_adicionales) {
                        this.empleadoForm.campos_adicionales = {};
                    }
                    
                    let currentValues = [];
                    if (this.empleadoForm.campos_adicionales[campoId]) {
                        try {
                            currentValues = JSON.parse(this.empleadoForm.campos_adicionales[campoId]);
                        } catch (e) {
                            currentValues = [];
                        }
                    }
                    
                    if (event.target.checked) {
                        if (!currentValues.includes(opcion)) {
                            currentValues.push(opcion);
                        }
                    } else {
                        currentValues = currentValues.filter(v => v !== opcion);
                    }
                    
                    this.empleadoForm.campos_adicionales[campoId] = JSON.stringify(currentValues);
                },
                isMultipleSelected(campoId, opcion) {
                    if (!this.empleadoForm.campos_adicionales || !this.empleadoForm.campos_adicionales[campoId]) {
                        return false;
                    }
                    
                    try {
                        const currentValues = JSON.parse(this.empleadoForm.campos_adicionales[campoId]);
                        return currentValues.includes(opcion);
                    } catch (e) {
                        return false;
                    }
                },
                downloadFile(campoId) {
                    // Buscar el archivo en la base de datos por empleado y tipo de campo
                    if (this.editingEmpleado && this.editingEmpleado.id) {
                        fetch(`api/empleados.php?action=get_file&empleado_id=${this.editingEmpleado.id}&campo_id=${campoId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.archivo_id) {
                                window.open(`api/download.php?id=${data.archivo_id}`, '_blank');
                            } else {
                                alert('Archivo no encontrado');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al buscar archivo');
                        });
                    }
                }
            }
        }
    </script>
</body>
</html>