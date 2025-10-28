<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Stream;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['schoolClass.level', 'stream', 'feeAllocations.feeGroup.feeType', 'feeAllocations.feePayments', 'feePayments']);
        
        // Search filter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lin', 'like', "%{$search}%")
                  ->orWhere('registration_no', 'like', "%{$search}%");
            });
        }
        

        
        // Class ID filter
        if ($request->has('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        
        // Stream filter
        if ($request->has('stream_id')) {
            $query->where('stream_id', $request->get('stream_id'));
        }
        
        // Gender filter
        if ($request->has('gender')) {
            $query->where('gender', $request->get('gender'));
        }
        
        // Active status filter
        if ($request->has('active')) {
            $query->where('active', $request->get('active'));
        }
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $students = $query->latest()->paginate($perPage);
        

        
        return response()->json($students);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if (isset($data[0]) && is_array($data[0])) {
            $students = [];
            foreach ($data as $studentData) {
                $validated = validator($studentData, [
                    'lin' => 'required|unique:students',
                    'registration_no' => 'nullable|string|max:255',
                    'first_name' => 'required|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'class_id' => 'required|exists:classes,id',
                    'stream_id' => 'nullable|exists:streams,id',
                    'gender' => 'required|in:male,female',
                    'birthday' => 'nullable|date',
                    'admission_date' => 'required|date',
                    'address' => 'nullable|string|max:500',
                    'guardian_name' => 'required|string|max:255',
                    'guardian_phone' => 'required|string|max:20',
                    'guardian_email' => 'nullable|email|max:255',
                    'guardian_relationship' => 'nullable|string|max:50',
                ])->validate();
                $student = Student::create($validated);
                $students[] = $student->load(['schoolClass', 'stream']);
            }
            return response()->json($students, 201);
        }

        $request->validate([
            'lin' => 'required|unique:students',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'gender' => 'required|in:male,female',
            'birthday' => 'nullable|date',
            'admission_date' => 'required|date',
            'address' => 'nullable|string|max:500',
            'picture' => 'nullable|image|max:2048',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_relationship' => 'nullable|string|max:50',
            'fee_type_ids' => 'nullable|array',
            'fee_type_ids.*' => 'exists:fee_types,id',
            'fee_group_ids' => 'nullable|array',
            'fee_group_ids.*' => 'exists:fee_groups,id',
        ]);
        
        // Validate stream belongs to class
        if ($request->stream_id && $request->class_id) {
            $class = SchoolClass::find($request->class_id);
            if (!$class->streams()->where('streams.id', $request->stream_id)->exists()) {
                return response()->json(['message' => 'Selected stream is not available for this class'], 422);
            }
        }

        $student = Student::create($request->except(['fee_type_ids', 'fee_group_ids']));
        
        if ($request->has('fee_type_ids')) {
            $student->feeTypes()->attach($request->fee_type_ids);
        }
        
        if ($request->has('fee_group_ids')) {
            $student->feeGroups()->attach($request->fee_group_ids);
        }
        
        return response()->json($student->load(['schoolClass', 'stream', 'feeTypes', 'feeGroups']), 201);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'students' => 'required|array',
            'students.*.lin' => 'required|unique:students,lin',
            'students.*.first_name' => 'required|string|max:255',
            'students.*.last_name' => 'required|string|max:255',
            'students.*.class_id' => 'required|exists:classes,id',
            'students.*.gender' => 'required|in:male,female',
            'students.*.admission_date' => 'required|date',
            'students.*.guardian_name' => 'required|string|max:255',
            'students.*.guardian_phone' => 'required|string|max:20',
        ]);

        $students = [];
        foreach ($request->students as $studentData) {
            $student = Student::create($studentData);
            $students[] = $student->load(['schoolClass', 'stream']);
        }

        return response()->json([
            'message' => 'Students created successfully',
            'students' => $students,
            'count' => count($students)
        ], 201);
    }

    public function show(Student $student)
    {
        return response()->json($student->load(['schoolClass.level', 'stream', 'feeAllocations.feeGroup.feeType', 'feePayments']));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'lin' => 'required|unique:students,lin,' . $student->id,
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'gender' => 'required|in:male,female',
            'birthday' => 'nullable|date',
            'admission_date' => 'required|date',
            'address' => 'nullable|string|max:500',
            'picture' => 'nullable|image|max:2048',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_relationship' => 'nullable|string|max:50',
            'fee_type_ids' => 'nullable|array',
            'fee_type_ids.*' => 'exists:fee_types,id',
            'fee_group_ids' => 'nullable|array',
            'fee_group_ids.*' => 'exists:fee_groups,id',
        ]);

        $student->update($request->except(['fee_type_ids', 'fee_group_ids']));
        
        // Sync fee types
        if ($request->has('fee_type_ids')) {
            $student->feeTypes()->sync($request->fee_type_ids);
        }
        
        // Sync fee groups
        if ($request->has('fee_group_ids')) {
            $student->feeGroups()->sync($request->fee_group_ids);
        }
        
        return response()->json($student->load(['schoolClass', 'stream', 'feeTypes', 'feeGroups']));
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }

    public function export(Request $request)
    {
        $students = Student::with(['schoolClass', 'stream'])->get();
        $format = $request->get('format', 'csv'); // Default to CSV
        
        if ($format === 'excel') {
            // Excel export
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = [
                'LIN', 'First Name', 'Middle Name', 'Last Name', 'Class', 'Stream',
                'Gender', 'Birthday', 'Admission Date', 'Address', 'Guardian Name',
                'Guardian Phone', 'Guardian Email', 'Guardian Relationship'
            ];
            
            $sheet->fromArray($headers, null, 'A1');
            
            // Add student data
            $row = 2;
            foreach ($students as $student) {
                $sheet->fromArray([
                    $student->lin ?? $student->registration_no,
                    $student->first_name,
                    $student->middle_name ?? '',
                    $student->last_name,
                    $student->schoolClass->name ?? '',
                    $student->stream->name ?? '',
                    $student->gender,
                    $student->birthday?->format('Y-m-d') ?? '',
                    $student->admission_date?->format('Y-m-d') ?? '',
                    $student->address ?? '',
                    $student->guardian_name,
                    $student->guardian_phone,
                    $student->guardian_email ?? '',
                    $student->guardian_relationship ?? ''
                ], null, 'A' . $row);
                $row++;
            }
            
            $filename = 'students_' . date('Y-m-d') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {
            // CSV export (default)
            $filename = 'students_' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($students) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers
                fputcsv($file, [
                    'LIN', 'Registration No', 'First Name', 'Middle Name', 'Last Name', 'Class ID', 'Stream ID',
                    'Gender', 'Birthday', 'Admission Date', 'Address', 'Picture', 'Guardian Name',
                    'Guardian Phone', 'Guardian Email', 'Guardian Relationship', 'Active'
                ]);
                
                // Add student data
                foreach ($students as $student) {
                    fputcsv($file, [
                        $student->lin,
                        $student->registration_no ?? '',
                        $student->first_name,
                        $student->middle_name ?? '',
                        $student->last_name,
                        $student->class_id ?? '',
                        $student->stream_id ?? '',
                        $student->gender,
                        $student->birthday?->format('Y-m-d') ?? '',
                        $student->admission_date?->format('Y-m-d') ?? '',
                        $student->address ?? '',
                        $student->picture ?? '',
                        $student->guardian_name,
                        $student->guardian_phone,
                        $student->guardian_email ?? '',
                        $student->guardian_relationship ?? '',
                        $student->active ? '1' : '0'
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        }
    }

    public function import(Request $request)
    {
        \Log::info('Import request received');
        
        try {
            $request->validate([
                'file' => 'required|file'
            ]);
            
            $file = $request->file('file');
            \Log::info('File details', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension(),
                'mime' => $file->getMimeType()
            ]);
            
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                \Log::error('Invalid file format: ' . $extension);
                return response()->json([
                    'message' => 'File must be CSV, XLSX, or XLS format',
                    'success_count' => 0,
                    'error_count' => 1,
                    'errors' => [[
                        'row' => 'N/A',
                        'lin' => 'N/A',
                        'message' => 'Invalid file format: ' . $extension
                    ]]
                ]);
            }

            $data = [];
            
            // Handle Excel files
            if (in_array($extension, ['xlsx', 'xls'])) {
                \Log::info('Processing Excel file');
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                    $worksheet = $spreadsheet->getSheetByName('Students') ?: $spreadsheet->getActiveSheet();
                    $highestRow = $worksheet->getHighestRow();
                    
                    \Log::info('Excel sheet info', ['name' => $worksheet->getTitle(), 'rows' => $highestRow]);
                    
                    // Read data starting from row 2 (skip header)
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $rowData = [];
                        for ($col = 'A'; $col <= 'Q'; $col++) {
                            $rowData[] = $worksheet->getCell($col . $row)->getValue();
                        }
                        // Only add non-empty rows
                        if (!empty(array_filter($rowData))) {
                            $data[] = $rowData;
                        }
                    }
                    \Log::info('Excel rows loaded', ['count' => count($data)]);
                } catch (\Exception $e) {
                    \Log::error('Excel processing error: ' . $e->getMessage());
                    throw new \Exception('Could not process Excel file: ' . $e->getMessage());
                }
            } else {
                \Log::info('Processing CSV file');
                $content = file_get_contents($file->getRealPath());
                
                // Detect and convert encoding to UTF-8
                $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }
                
                // Parse CSV from string
                $lines = explode("\n", $content);
                $header = true;
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    $row = str_getcsv($line);
                    if ($header) {
                        $header = false;
                        continue;
                    }
                    $data[] = $row;
                }
                \Log::info('CSV rows loaded', ['count' => count($data)]);
            }
            
            $successCount = 0;
            $errors = [];
            $rowNumber = 2; // Start from row 2 (after header)
            
            foreach ($data as $row) {
                \Log::info('Processing row', ['row' => $rowNumber, 'data' => array_slice($row, 0, 5)]);
                
                // Skip empty rows or rows with invalid data
                if (empty($row) || !isset($row[0]) || empty(trim($row[0])) || str_starts_with(trim($row[0]), '#')) {
                    \Log::info('Skipping empty row', ['row' => $rowNumber]);
                    $rowNumber++;
                    continue;
                }
                
                // Skip rows that don't look like valid student data (check if first cell looks like LIN)
                $firstCell = trim($row[0]);
                if (strlen($firstCell) > 50) {
                    \Log::warning('Skipping invalid row - too long', ['row' => $rowNumber, 'first_cell' => substr($firstCell, 0, 20)]);
                    $rowNumber++;
                    continue;
                }
                
                // Skip rows with binary/special characters (but allow basic punctuation)
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', $firstCell)) {
                    \Log::warning('Skipping invalid row - binary data', ['row' => $rowNumber, 'first_cell' => substr($firstCell, 0, 20)]);
                    $rowNumber++;
                    continue;
                }
                
                try {
                    // Map fields: LIN,Registration No,First Name,Middle Name,Last Name,Class,Stream,Gender,Birthday,Admission Date,Address,Picture,Guardian Name,Guardian Phone,Guardian Email,Guardian Relationship,Active
                    $lin = trim($row[0] ?? '');
                    $registrationNo = trim($row[1] ?? '');
                    $firstName = trim($row[2] ?? '');
                    $middleName = trim($row[3] ?? '');
                    $lastName = trim($row[4] ?? '');
                    $className = trim($row[5] ?? '');
                    $streamName = trim($row[6] ?? '');
                    $gender = trim($row[7] ?? '');
                    $birthday = trim($row[8] ?? '');
                    $admissionDate = trim($row[9] ?? '');
                    $address = trim($row[10] ?? '');
                    $picture = trim($row[11] ?? '');
                    $guardianName = trim($row[12] ?? '');
                    $guardianPhone = trim($row[13] ?? '');
                    $guardianEmail = trim($row[14] ?? '');
                    $guardianRelationship = trim($row[15] ?? '');
                    $active = trim($row[16] ?? '1');
                    
                    if (empty($lin)) throw new \Exception('LIN is required');
                    if (empty($firstName)) throw new \Exception('First name is required');
                    if (empty($lastName)) throw new \Exception('Last name is required');
                    if (empty($className)) throw new \Exception('Class is required');
                    if (empty($guardianName)) throw new \Exception('Guardian name is required');
                    if (empty($guardianPhone)) throw new \Exception('Guardian phone is required');
                    
                    // Find class by name or ID
                    $class = is_numeric($className) ? SchoolClass::find($className) : SchoolClass::where('name', $className)->first();
                    if (!$class) {
                        throw new \Exception('Class "' . $className . '" does not exist');
                    }
                    $classId = $class->id;
                    
                    // Find stream by name or ID if provided
                    $streamId = null;
                    if (!empty($streamName)) {
                        $stream = is_numeric($streamName) ? Stream::find($streamName) : Stream::where('name', $streamName)->first();
                        if (!$stream) {
                            throw new \Exception('Stream "' . $streamName . '" does not exist');
                        }
                        $streamId = $stream->id;
                    }
                    
                    // Check if student already exists
                    if (Student::where('lin', $lin)->exists()) {
                        throw new \Exception('Student with LIN ' . $lin . ' already exists');
                    }
                    
                    Student::create([
                        'lin' => $lin,
                        'registration_no' => $registrationNo ?: $lin,
                        'first_name' => $firstName,
                        'middle_name' => $middleName ?: null,
                        'last_name' => $lastName,
                        'class_id' => $classId,
                        'stream_id' => $streamId ?: null,
                        'gender' => strtolower($gender ?: 'male'),
                        'birthday' => !empty($birthday) ? date('Y-m-d', strtotime($birthday)) : null,
                        'admission_date' => !empty($admissionDate) ? date('Y-m-d', strtotime($admissionDate)) : now(),
                        'address' => $address ?: null,
                        'picture' => $picture ?: null,
                        'guardian_name' => $guardianName,
                        'guardian_phone' => $guardianPhone,
                        'guardian_email' => $guardianEmail ?: null,
                        'guardian_relationship' => $guardianRelationship ?: null,
                        'active' => $active === '1' || strtolower($active) === 'true',
                    ]);
                    
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'lin' => $lin ?? 'N/A',
                        'message' => $e->getMessage()
                    ];
                }
                
                $rowNumber++;
            }
            
            $result = [
                'message' => $successCount > 0 ? "Successfully imported {$successCount} students" : 'No students were imported',
                'success_count' => $successCount,
                'error_count' => count($errors),
                'errors' => $errors
            ];
            
            \Log::info('Import completed', $result);
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error('Import failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
                'success_count' => 0,
                'error_count' => 1,
                'errors' => [[
                    'row' => 'N/A',
                    'lin' => 'N/A',
                    'message' => $e->getMessage()
                ]]
            ]);
        }
    }

    public function getClasses(Request $request)
    {
        $query = SchoolClass::with('streams');
        
        if ($request->has('level_id')) {
            $query->where('level_id', $request->get('level_id'));
        }
        
        $classes = $query->get();
        return response()->json($classes);
    }

    public function getStreams($classId = null)
    {
        $query = Stream::query();
        if ($classId) {
            $query->where('class_id', $classId);
        }
        $streams = $query->get();
        return response()->json($streams);
    }

    public function template()
    {
        $classes = SchoolClass::all();
        $streams = Stream::all();
        
        if ($classes->isEmpty()) {
            $class = SchoolClass::create(['name' => 'S1', 'level_id' => 1]);
            $classes = collect([$class]);
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');
        
        // Headers
        $headers = ['LIN', 'Registration No', 'First Name', 'Middle Name', 'Last Name', 'Class', 'Stream', 'Gender', 'Birthday', 'Admission Date', 'Address', 'Picture', 'Guardian Name', 'Guardian Phone', 'Guardian Email', 'Guardian Relationship', 'Active'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Sample data
        $sampleClassName = $classes->first()->name;
        $sampleStreamName = $streams->first()?->name ?? '';
        $sheet->fromArray(['LIN2025001', 'REG001', 'John', 'Paul', 'Doe', $sampleClassName, $sampleStreamName, 'male', '2010-01-15', '2025-02-01', '123 Main St, Kampala', '', 'Jane Doe', '+256700123456', 'jane.doe@email.com', 'mother', '1'], null, 'A2');
        
        // Create reference sheet for dropdowns
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Reference');
        
        // Add classes to reference sheet
        $refSheet->setCellValue('A1', 'Class Name');
        $row = 2;
        foreach ($classes as $class) {
            $refSheet->setCellValue('A' . $row, $class->name);
            $row++;
        }
        $classRange = 'Reference!$A$2:$A$' . ($row - 1);
        
        // Add streams to reference sheet
        $refSheet->setCellValue('C1', 'Stream Name');
        $row = 2;
        foreach ($streams as $stream) {
            $refSheet->setCellValue('C' . $row, $stream->name);
            $row++;
        }
        $streamRange = 'Reference!$C$2:$C$' . ($row - 1);
        
        // Add gender options
        $refSheet->setCellValue('E1', 'Gender');
        $refSheet->setCellValue('E2', 'male');
        $refSheet->setCellValue('E3', 'female');
        
        // Add relationship options
        $refSheet->setCellValue('G1', 'Relationship');
        $relationships = ['father', 'mother', 'guardian', 'uncle', 'aunt', 'grandparent', 'sibling', 'other'];
        foreach ($relationships as $idx => $rel) {
            $refSheet->setCellValue('G' . ($idx + 2), $rel);
        }
        
        // Add active options
        $refSheet->setCellValue('I1', 'Active');
        $refSheet->setCellValue('I2', '1');
        $refSheet->setCellValue('I3', '0');
        
        // Apply dropdowns to main sheet (rows 2-1000)
        $validation = $sheet->getCell('F2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1($classRange);
        $validation->setShowDropDown(true);
        $sheet->setDataValidation('F2:F1000', clone $validation);
        
        $validation = $sheet->getCell('G2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1($streamRange);
        $validation->setShowDropDown(true);
        $sheet->setDataValidation('G2:G1000', clone $validation);
        
        $validation = $sheet->getCell('H2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('Reference!$E$2:$E$3');
        $validation->setShowDropDown(true);
        $sheet->setDataValidation('H2:H1000', clone $validation);
        
        $validation = $sheet->getCell('P2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('Reference!$G$2:$G$9');
        $validation->setShowDropDown(true);
        $sheet->setDataValidation('P2:P1000', clone $validation);
        
        $validation = $sheet->getCell('Q2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('Reference!$I$2:$I$3');
        $validation->setShowDropDown(true);
        $sheet->setDataValidation('Q2:Q1000', clone $validation);
        
        // Style headers
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
        $sheet->getStyle('A1:Q1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
        
        // Auto-size columns
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $spreadsheet->setActiveSheetIndex(0);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'student_template_' . date('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    

}