// Configuración global de DataTables en español
// Esto se aplica a todas las inicializaciones de DataTables

if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
  (function($){
    $.extend(true, $.fn.dataTable.defaults, {
      language: {
        emptyTable: "No hay datos disponibles en la tabla",
        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
        infoFiltered: "(filtrado de _MAX_ entradas totales)",
        lengthMenu: "Mostrar _MENU_ entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes",
        paginate: {
          first: "Primero",
          previous: "Anterior",
          next: "Siguiente",
          last: "Último"
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendente",
          sortDescending: ": activar para ordenar la columna descendente"
        }
      }
    });
  })(jQuery);
} else {
  console.warn('DataTables no está cargado aún — la configuración de idioma se aplicará cuando se cargue.');
}