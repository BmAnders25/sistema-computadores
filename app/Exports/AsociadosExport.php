<?php

namespace App\Exports;

use App\Models\Asociado;
use App\Models\Economica;
use App\Models\Empresa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithMapping;

class AsociadosExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting, WithMapping
{
    // Método para obtener la colección de datos
    public function collection()
    {
        return Asociado::with('municipio_expedicion', 
            'departamento_expedicion', 
            'municipio_residencia', 
            'departamento_residencia', 
            'municipio_nacimiento', 
            'departamento_nacimiento',
            'economicas.empresas')->get();
    }

    public function map($asociado): array
    {
        return [
            $asociado->id, 
            $asociado->fecha_afiliacion,
            $asociado->tipo_documento,
            $asociado->cedula, 
            $asociado->fecha_expedicion,
            $asociado->municipio_expedicion->nombre ?? 'No definido',
            $asociado->departamento_expedicion->nombre ?? 'No definido',
            $asociado->nombre, 
            $asociado->primer_apellido,
            $asociado->segundo_apellido,
            $asociado->fecha_nacimiento,       
            $asociado->edad,
            $asociado->municipio_nacimiento->nombre ?? 'No definido',
            $asociado->departamento_nacimiento->nombre ?? 'No definido',
            $asociado->nacionalidad,
            $asociado->cedula_representante,
            $asociado->nombre_representante,
            $asociado->edad_representante,
            $asociado->genero,
            $asociado->estado_civil,
            $asociado->personas_adultos,
            $asociado->personas_menores,
            $asociado->cabeza_familia,
            $asociado->direccion,
            $asociado->municipio_residencia->nombre ?? 'No definido',
            $asociado->departamento_residencia->nombre ?? 'No definido',
            $asociado->tipo_vivienda,
            $asociado->estrato,
            $asociado->telefono,
            $asociado->celular,
            $asociado->email,
            $asociado->nivel_educativo,
            $asociado->profesion,
            $asociado->idiomas,
            $asociado->hobbies,
            $asociado->estado,
            optional($asociado->economicas)->empresa,
            optional($asociado->economicas)->empresa_id,
            optional(optional($asociado->economicas)->empresas)->nombre,
            
        ];
    }

    // Definir los encabezados
    public function headings(): array
    {
        return [
            'ID',
            'Fecha de Afiliación',
            'Tipo documento',
            'Cédula',
            'Fecha Expedición',
            'Lugar',
            'Departamento',
            'Nombre',
            'Primer Apellido',
            'Segundo Apellido',
            'Fecha Nacimiento',       
            'Edad',
            'Lugar',
            'Departamento',
            'Nacionalidad',
            'CC Representante',
            'Nombre Representante',
            'Edad Representante',
            'Genero',
            'Estado Civil',
            'Personas Adultos',
            'Personas Menores',
            'Cabeza de Familia',
            'Direccion',
            'Ciudad',
            'Departamento',
            'Tipo vivienda',
            'Estrato',
            'Teléfono',
            'Celular',
            'Email',
            'Nivel Educativo',
            'Profesion',
            'Idiomas',
            'Hobbies',
            'Estado',
            'Empresa',
            'Empresa id',
            'Nombre Empresa'
        ];
    }

    // Dar estilo a las columnas
    public function styles(Worksheet $sheet)
    {
        return [
            // Ajustar el ancho de algunas columnas
            'B' => ['alignment' => ['horizontal' => 'right']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'right']],
            'K' => ['alignment' => ['horizontal' => 'right']],
            'W' => ['alignment' => ['horizontal' => 'center']],
            'AB' => ['alignment' => ['horizontal' => 'center']],
            'AH' => ['alignment' => ['horizontal' => 'center']],
            'AJ' => ['alignment' => ['horizontal' => 'center']],
            //'E' => ['alignment' => ['horizontal' => 'left'], 'width' => 25],

            // Hacer que los encabezados estén en negrita
            1    => ['font' => ['bold' => true],'alignment' => ['horizontal' => 'center']],
        ];
    }

    // Ajuste de ancho de las columnas
    public function columnWidths(): array
    {
        return [
            'A' => 10,    // ID
            'B' => 18,   // Fecha de Afiliación
            'C' => 15,   // Tipo documento
            'D' => 15,   // Cédula
            'E' => 18,   // Fecha de Expedición
            'F' => 30,   // Lugar
            'G' => 30,   // Departamento
            'H' => 30,   // Nombre
            'I' => 20,   // Primer Apellido
            'J' => 20,   // Segundo Apellido
            'K' => 18,   // Fecha de Nacimiento
            'L' => 15,   // Edad
            'M' => 30,   // Lugar
            'N' => 30,   // Departamento
            'O' => 30,   // Nacionalidad
            'P' => 15,   // CC Representante
            'Q' => 30,   // Nombre Representante
            'R' => 15,   // Edad Representante
            'S' => 15,   // Genero
            'T' => 20,   // Estado civil
            'U' => 15,   // No. Adultos
            'V' => 15,   // No. menores
            'W' => 15,   // Cabeza de familia
            'X' => 50,   // Dirección
            'Y' => 30,   // Ciudad
            'Z' => 30,   // Departamento
            'AA' => 15,   // Tipo vivienda
            'AB' => 15,   // Estrato
            'AC' => 15,   // Telefono
            'AD' => 20,   // Célular
            'AE' => 35,   // Email
            'AF' => 20,   // Nivel educativo
            'AG' => 30,   // Profesion
            'AH' => 20,   // Idiomas
            'AI' => 20,   // Hobbies
            'AJ' => 15,   // Estado
            'AK' => 20,   // Empresa
            'AL' => 10,   // eMPRESA
            'AM' => 20,   // eMPRESA
            
        ];
    }

    // Dar formato a algunas columnas
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER, // Cédula como número
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha con formato de fecha
        ];
    }
}