if ($reportType === 'sf2') {
    if ($format !== 'excel') {
        throw new Exception('SF2 report is only available in Excel format.');
    }
    if (!$classId || !$month) {
        throw new Exception('Class and month are required for SF2 report.');
    }

    // Fetch class details
    $class_stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = :class_id AND teacher_id = :teacher_id");
    $class_stmt->execute(['class_id' => $classId, 'teacher_id' => $user['teacher_id']]);
    $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class) {
        throw new Exception('Class not found.');
    }

    $gradeLevel = $class['grade_level'];
    $sectionName = $class['section_name'];
    $schoolName = $user['institution'] ?: 'E. A. Remigio ES'; // Fallback to example

    // Month map
    $monthMap = [
        'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'APRIL' => 4, 'MAY' => 5,
        'JUNE' => 6, 'JULY' => 7, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OCTOBER' => 10,
        'NOVEMBER' => 11, 'DECEMBER' => 12
    ];
    if (!isset($monthMap[$month])) {
        throw new Exception('Invalid month selected.');
    }
    $monthNum = $monthMap[$month];

    // Determine school year and month year
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    $schoolStartYear = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
    $schoolYear = $schoolStartYear . ' - ' . ($schoolStartYear + 1);
    $year = ($monthNum >= 6) ? $schoolStartYear : $schoolStartYear + 1;

    // Get school days (Mon-Fri)
    $firstDay = new DateTime("$year-$monthNum-01");
    $lastDay = clone $firstDay;
    $lastDay->modify('last day of this month');
    $schoolDays = [];
    $current = clone $firstDay;
    while ($current <= $lastDay) {
        $dow = (int)$current->format('N'); // 1 (Mon) to 7 (Sun)
        if ($dow >= 1 && $dow <= 5) { // Mon-Fri
            $schoolDays[] = clone $current;
        }
        $current->modify('+1 day');
    }
    $numDays = count($schoolDays);
    if ($numDays > 25) {
        $schoolDays = array_slice($schoolDays, 0, 25); // Truncate to max columns
        $numDays = 25;
    }

    // Define fixed day columns and their abbreviation sequence
    $dayColumns = ['F', 'H', 'I', 'J', 'K', 'L', 'N', 'O', 'P', 'Q', 'R', 'T', 'U', 'V', 'X', 'Z', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AI', 'AJ', 'AK'];
    $fixedAbbrevs = ['M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F'];
    $dayAssignments = [];
    $dayIndex = 0;

    // Determine the starting day of the week for the first school day
    $firstSchoolDay = reset($schoolDays);
    $firstDayOfWeek = (int)$firstSchoolDay->format('N'); // 1 (Mon) to 5 (Fri)
    $dayMap = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F'];
    $startAbbrev = $dayMap[$firstDayOfWeek]; // e.g., 'T' for Tuesday

    // Find the index in fixedAbbrevs where we start
    $startIndex = array_search($startAbbrev, $fixedAbbrevs);
    if ($startIndex === false) {
        throw new Exception('Invalid starting day abbreviation.');
    }

    // Assign days to columns, respecting the fixed abbreviation sequence
    for ($i = 0; $i < 25; $i++) {
        $col = $dayColumns[$i];
        $abbrev = $fixedAbbrevs[$i];
        if ($i < $startIndex) {
            // Columns before the starting day are blank
            $dayAssignments[] = [
                'column' => $col,
                'abbrev' => $abbrev,
                'date' => null,
                'dateNum' => ''
            ];
        } else {
            // Assign school days starting from the first school day
            if ($dayIndex < count($schoolDays)) {
                $dayAssignments[] = [
                    'column' => $col,
                    'abbrev' => $abbrev,
                    'date' => $schoolDays[$dayIndex],
                    'dateNum' => $schoolDays[$dayIndex]->format('j')
                ];
                $dayIndex++;
            } else {
                // No more school days, leave blank
                $dayAssignments[] = [
                    'column' => $col,
                    'abbrev' => $abbrev,
                    'date' => null,
                    'dateNum' => ''
                ];
            }
        }
    }

    // Fetch students
    $males_stmt = $pdo->prepare("SELECT s.* FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id AND LOWER(s.gender) = 'male' ORDER BY s.full_name ASC");
    $males_stmt->execute(['class_id' => $classId]);
    $males = $males_stmt->fetchAll(PDO::FETCH_ASSOC);

    $females_stmt = $pdo->prepare("SELECT s.* FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id AND LOWER(s.gender) = 'female' ORDER BY s.full_name ASC");
    $females_stmt->execute(['class_id' => $classId]);
    $females = $females_stmt->fetchAll(PDO::FETCH_ASSOC);

    $maleCount = count($males);
    $femaleCount = count($females);
    $totalStudents = $maleCount + $femaleCount;

    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('school_form_2_ver2014.2.1.1');

    // Border style
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];

    // Set column widths (based on standard SF2 template, in character units)
    $columnWidths = [
        'A' => 4.14,  // No.
        'B' => 2.86,  // Spacer
        'C' => 30.00, // Name
        'D' => 2.86,  // Spacer
        'E' => 2.86,  // Spacer
        'F' => 4.14,  // Day 1
        'G' => 2.86,  // Spacer
        'H' => 4.14,  // Day 2
        'I' => 4.14,  // Day 3
        'J' => 4.14,  // Day 4
        'K' => 4.14,  // Day 5
        'L' => 4.14,  // Day 6
        'M' => 2.86,  // Spacer
        'N' => 4.14,  // Day 7
        'O' => 4.14,  // Day 8
        'P' => 4.14,  // Day 9
        'Q' => 4.14,  // Day 10
        'R' => 4.14,  // Day 11
        'S' => 2.86,  // Spacer
        'T' => 4.14,  // Day 12
        'U' => 4.14,  // Day 13
        'V' => 4.14,  // Day 14
        'W' => 2.86,  // Spacer
        'X' => 4.14,  // Day 15
        'Y' => 2.86,  // Spacer
        'Z' => 4.14,  // Day 16
        'AA' => 2.86, // Spacer
        'AB' => 4.14, // Day 17
        'AC' => 4.14, // Day 18
        'AD' => 4.14, // Day 19
        'AE' => 4.14, // Day 20
        'AF' => 4.14, // Day 21
        'AG' => 4.14, // Day 22
        'AH' => 2.86, // Spacer
        'AI' => 4.14, // Day 23
        'AJ' => 4.14, // Day 24
        'AK' => 4.14, // Day 25
        'AL' => 2.86, // Spacer
        'AM' => 8.43, // ABSENT
        'AN' => 2.86, // Spacer
        'AO' => 8.43, // PRESENT
        'AP' => 2.86, // Spacer
        'AQ' => 2.86, // Spacer
        'AR' => 30.00, // REMARKS
        'AS' => 2.86  // Spacer
    ];
    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    // Set row heights (based on standard SF2 template, in points)
    $rowHeights = [
        1 => 30,  // Title
        2 => 15,  // Subtitle
        3 => 15,  // School ID, Year, Month
        4 => 15,  // School Name, Grade, Section
        5 => 30,  // Headers
        7 => 15,  // Day abbreviations
    ];
    // Set student rows (8 to 40) and guideline rows
    for ($i = 8; $i <= 74; $i++) {
        $rowHeights[$i] = 15; // Default height for student and guideline rows
    }
    foreach ($rowHeights as $row => $height) {
        $sheet->getRowDimension($row)->setRowHeight($height);
    }

    // Set fixed texts and merge cells (matching the exact SF2 structure)
    $sheet->setCellValue('A1', 'School Form 2 (SF2) Daily Attendance Report of Learners');
    $sheet->mergeCells('A1:AR1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', '(This replaces Form 1, Form 2 & STS Form 4 - Absenteeism and Dropout Profile)');
    $sheet->mergeCells('A2:AR2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A3', 'School ID');
    $sheet->mergeCells('A3:I3');
    $sheet->setCellValue('K3', 'School Year');
    $sheet->mergeCells('K3:M3');
    $sheet->setCellValue('N3', $schoolYear);
    $sheet->mergeCells('N3:S3');
    $sheet->setCellValue('T3', 'Report for the Month of');
    $sheet->mergeCells('T3:AB3');
    $sheet->setCellValue('AC3', $month);
    $sheet->mergeCells('AC3:AK3');
    $sheet->getStyle('A3:AK3')->applyFromArray($borderStyle);

    $sheet->setCellValue('A4', 'Name of School');
    $sheet->mergeCells('A4:E4');
    $sheet->setCellValue('F4', $schoolName);
    $sheet->mergeCells('F4:U4');
    $sheet->setCellValue('V4', 'Grade Level');
    $sheet->mergeCells('V4:AC4');
    $sheet->setCellValue('AD4', $gradeLevel);
    $sheet->mergeCells('AD4:AL4');
    $sheet->setCellValue('AM4', 'Section');
    $sheet->mergeCells('AM4:AQ4');
    $sheet->setCellValue('AR4', $sectionName);
    $sheet->mergeCells('AR4:AS4');
    $sheet->getStyle('A4:AS4')->applyFromArray($borderStyle);

    $sheet->setCellValue('A5', 'No.');
    $sheet->mergeCells('A5:B5');
    $sheet->setCellValue('C5', "NAME\n(Last Name, First Name, Middle Name)");
    $sheet->mergeCells('C5:E5');
    $sheet->setCellValue('AN5', 'Total for the Month');
    $sheet->mergeCells('AN5:AO5');
    $sheet->setCellValue('AR5', 'REMARKS (If NLS, state reason, please refer to legend number 2. If TRANSFERRED IN/OUT, write the name of School.)');
    $sheet->mergeCells('AR5:AS5');
    $sheet->getStyle('A5:AS5')->applyFromArray($borderStyle);
    $sheet->getStyle('C5')->getAlignment()->setWrapText(true);

    $sheet->setCellValue('AM7', 'ABSENT');
    $sheet->setCellValue('AO7', 'PRESENT');
    $sheet->getStyle('AM7:AO7')->applyFromArray($borderStyle);

    // Apply borders and center-align day and abbreviation cells
    foreach ($dayColumns as $col) {
        $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . '5')->applyFromArray($borderStyle);
        $sheet->getStyle($col . '7')->applyFromArray($borderStyle);
    }

    // Populate dates and day abbreviations only in day columns
    foreach ($dayAssignments as $assignment) {
        $col = $assignment['column'];
        $sheet->setCellValue($col . '5', $assignment['dateNum']);
        $sheet->setCellValue($col . '7', $assignment['abbrev']);
        $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . '5')->applyFromArray($borderStyle);
        $sheet->getStyle($col . '7')->applyFromArray($borderStyle);
    }

    // Explicitly clear spacer columns in row 7
    $spacerColumns = ['B', 'D', 'E', 'G', 'M', 'S', 'W', 'Y', 'AA', 'AH', 'AL', 'AN', 'AP', 'AQ', 'AS'];
    foreach ($spacerColumns as $col) {
        $sheet->setCellValue($col . '7', '');
        $sheet->getStyle($col . '7')->applyFromArray($borderStyle);
    }

    // Student rows
    $maleStartRow = 8;
    $studentAttendance = []; // To track per student absences
    $dailyPresent = array_fill(0, $numDays, ['male' => 0, 'female' => 0, 'combined' => 0]);
    $consecutiveAbsentCount = 0;

    // Males
    for ($j = 0; $j < $maleCount; $j++) {
        $row = $maleStartRow + $j;
        $sheet->setCellValue('A' . $row, $j + 1);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $males[$j]['full_name']);
        $sheet->mergeCells('C' . $row . ':E' . $row);
        $lrn = $males[$j]['lrn'];
        $absentCount = 0;
        $statusArray = [];
        $dayIndex = 0;
        foreach ($dayAssignments as $assignment) {
            $col = $assignment['column'];
            if ($assignment['date']) { // Only process columns with valid dates
                $date = $assignment['date']->format('Y-m-d');
                $att_stmt = $pdo->prepare("SELECT attendance_status FROM attendance_tracking WHERE class_id = :class_id AND lrn = :lrn AND attendance_date = :date");
                $att_stmt->execute(['class_id' => $classId, 'lrn' => $lrn, 'date' => $date]);
                $status = $att_stmt->fetchColumn() ?: 'Absent';
                $mark = ($status === 'Absent') ? 'x' : ($status === 'Late' ? 'L' : '');
                $sheet->setCellValue($col . $row, $mark);
                if ($status !== 'Absent') {
                    $dailyPresent[$dayIndex]['male']++;
                    $dailyPresent[$dayIndex]['combined']++;
                }
                if ($mark === 'x') {
                    $absentCount++;
                    $statusArray[] = 1;
                } else {
                    $statusArray[] = 0;
                }
                $dayIndex++;
            } else {
                $sheet->setCellValue($col . $row, '');
                $statusArray[] = 0; // No absence counted for blank dates
            }
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
        }
        $presentCount = $numDays - $absentCount;
        $sheet->setCellValue('AM' . $row, $absentCount);
        $sheet->setCellValue('AO' . $row, $presentCount);
        $sheet->getStyle('A' . $row . ':AS' . $row)->applyFromArray($borderStyle);
        $studentAttendance[] = $statusArray;

        // Check for 5 consecutive absents
        $streak = 0;
        foreach ($statusArray as $s) {
            if ($s === 1) {
                $streak++;
                if ($streak >= 5) {
                    $consecutiveAbsentCount++;
                    break;
                }
            } else {
                $streak = 0;
            }
        }
    }
    $maleTotalRow = $maleStartRow + $maleCount;
    $sheet->setCellValue('C' . $maleTotalRow, '<=== MALE | TOTAL Per Day ===>');
    $sheet->mergeCells('C' . $maleTotalRow . ':E' . $maleTotalRow);
    $sheet->getRowDimension($maleTotalRow)->setRowHeight(15);
    $sheet->getStyle('A' . $maleTotalRow . ':AS' . $maleTotalRow)->applyFromArray($borderStyle);

    // Females
    $femaleStartRow = $maleTotalRow + 1;
    for ($j = 0; $j < $femaleCount; $j++) {
        $row = $femaleStartRow + $j;
        $sheet->setCellValue('A' . $row, $j + 1);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $females[$j]['full_name']);
        $sheet->mergeCells('C' . $row . ':E' . $row);
        $lrn = $females[$j]['lrn'];
        $absentCount = 0;
        $statusArray = [];
        $dayIndex = 0;
        foreach ($dayAssignments as $assignment) {
            $col = $assignment['column'];
            if ($assignment['date']) { // Only process columns with valid dates
                $date = $assignment['date']->format('Y-m-d');
                $att_stmt = $pdo->prepare("SELECT attendance_status FROM attendance_tracking WHERE class_id = :class_id AND lrn = :lrn AND attendance_date = :date");
                $att_stmt->execute(['class_id' => $classId, 'lrn' => $lrn, 'date' => $date]);
                $status = $att_stmt->fetchColumn() ?: 'Absent';
                $mark = ($status === 'Absent') ? 'x' : ($status === 'Late' ? 'L' : '');
                $sheet->setCellValue($col . $row, $mark);
                if ($status !== 'Absent') {
                    $dailyPresent[$dayIndex]['female']++;
                    $dailyPresent[$dayIndex]['combined']++;
                }
                if ($mark === 'x') {
                    $absentCount++;
                    $statusArray[] = 1;
                } else {
                    $statusArray[] = 0;
                }
                $dayIndex++;
            } else {
                $sheet->setCellValue($col . $row, '');
                $statusArray[] = 0; // No absence counted for blank dates
            }
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
        }
        $presentCount = $numDays - $absentCount;
        $sheet->setCellValue('AM' . $row, $absentCount);
        $sheet->setCellValue('AO' . $row, $presentCount);
        $sheet->getStyle('A' . $row . ':AS' . $row)->applyFromArray($borderStyle);
        $studentAttendance[] = $statusArray;

        // Check for 5 consecutive absents
        $streak = 0;
        foreach ($statusArray as $s) {
            if ($s === 1) {
                $streak++;
                if ($streak >= 5) {
                    $consecutiveAbsentCount++;
                    break;
                }
            } else {
                $streak = 0;
            }
        }
    }
    $femaleTotalRow = $femaleStartRow + $femaleCount;
    $sheet->setCellValue('C' . $femaleTotalRow, '<=== FEMALE | TOTAL Per Day ===>');
    $sheet->mergeCells('C' . $femaleTotalRow . ':E' . $femaleTotalRow);
    $sheet->getRowDimension($femaleTotalRow)->setRowHeight(15);
    $sheet->getStyle('A' . $femaleTotalRow . ':AS' . $femaleTotalRow)->applyFromArray($borderStyle);

    $combinedRow = $femaleTotalRow + 1;
    $sheet->setCellValue('C' . $combinedRow, 'Combined TOTAL Per Day');
    $sheet->mergeCells('C' . $combinedRow . ':E' . $combinedRow);
    $sheet->getRowDimension($combinedRow)->setRowHeight(15);
    $sheet->getStyle('A' . $combinedRow . ':AS' . $combinedRow)->applyFromArray($borderStyle);

    // Set daily totals
    $totalAttendance = 0;
    $dayIndex = 0;
    foreach ($dayAssignments as $assignment) {
        $col = $assignment['column'];
        if ($assignment['date']) {
            $sheet->setCellValue($col . $maleTotalRow, $dailyPresent[$dayIndex]['male']);
            $sheet->setCellValue($col . $femaleTotalRow, $dailyPresent[$dayIndex]['female']);
            $sheet->setCellValue($col . $combinedRow, $dailyPresent[$dayIndex]['combined']);
            $totalAttendance += $dailyPresent[$dayIndex]['combined'];
            $dayIndex++;
        } else {
            $sheet->setCellValue($col . $maleTotalRow, '');
            $sheet->setCellValue($col . $femaleTotalRow, '');
            $sheet->setCellValue($col . $combinedRow, '');
        }
        $sheet->getStyle($col . $maleTotalRow)->applyFromArray($borderStyle);
        $sheet->getStyle($col . $femaleTotalRow)->applyFromArray($borderStyle);
        $sheet->getStyle($col . $combinedRow)->applyFromArray($borderStyle);
    }

    // Calculate averages
    $averageDaily = $numDays > 0 ? $totalAttendance / $numDays : 0;
    $percentageAttendance = $totalStudents > 0 ? round(($averageDaily / $totalStudents) * 100, 2) : 0;

    // Guidelines and summary (shifted rows)
    $guidelinesStartRow = $combinedRow + 1;
    $offset = $guidelinesStartRow - 42;

    // Set guidelines texts with merges
    $newRow = 42 + $offset;
    $sheet->setCellValue('A' . $newRow, 'GUIDELINES:');
    $sheet->mergeCells('A' . $newRow . ':W' . $newRow);
    $sheet->setCellValue('X' . $newRow, '1. CODES FOR CHECKING ATTENDANCE');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('AE' . $newRow, 'Month : ' . $month);
    $sheet->mergeCells('AE' . $newRow . ':AG' . $newRow);
    $sheet->setCellValue('AH' . $newRow, 'No. of Days of Classes:');
    $sheet->mergeCells('AH' . $newRow . ':AI' . $newRow);
    $sheet->setCellValue('AJ' . $newRow, $numDays);
    $sheet->setCellValue('AK' . $newRow, 'Summary');
    $sheet->mergeCells('AK' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 43 + $offset;
    $sheet->setCellValue('A' . $newRow, "1. The attendance shall be accomplished daily. Refer to the codes for checking learners' attendance.\n2. Dates shall be written in the columns after Learner's Name.\n3. To compute the following:");
    $sheet->mergeCells('A' . $newRow . ':W' . $newRow);
    $sheet->setCellValue('X' . $newRow, '(blank) - Present; (x)- Absent; Tardy (half shaded= Upper for Late Commer, Lower for Cutting Classes)');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(45);
    $sheet->getStyle('A' . $newRow)->getAlignment()->setWrapText(true);
    $sheet->getStyle('X' . $newRow)->getAlignment()->setWrapText(true);

    $newRow = 44 + $offset;
    $sheet->setCellValue('X' . $newRow, '2. REASONS/CAUSES FOR NLS');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('AE' . $newRow, '* Enrolment as of (1st Friday of the SY)');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, $maleCount);
    $sheet->setCellValue('AH' . $newRow, $femaleCount);
    $sheet->setCellValue('AI' . $newRow, $totalStudents);
    $sheet->mergeCells('AI' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 46 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Late enrolment during the month');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, 0);
    $sheet->setCellValue('AH' . $newRow, 0);
    $sheet->setCellValue('AI' . $newRow, 0);
    $sheet->mergeCells('AI' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 47 + $offset;
    $sheet->setCellValue('A' . $newRow, 'a. Percentage of Enrolment = Registered Learners as of end of the month x 100');
    $sheet->mergeCells('A' . $newRow . ':D' . $newRow);
    $sheet->setCellValue('E' . $newRow, 'Enrolment as of 1st Friday of the school year');
    $sheet->mergeCells('E' . $newRow . ':W' . $newRow);
    $sheet->setCellValue('X' . $newRow, 'a. Domestic-Related Factors');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 49 + $offset;
    $sheet->setCellValue('X' . $newRow, 'a.1. Had to take care of siblings');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('X' . ($newRow + 1), 'a.2. Early marriage/pregnancy');
    $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
    $sheet->setCellValue('X' . ($newRow + 2), 'a.3. Parents\' attitude toward schooling');
    $sheet->mergeCells('X' . ($newRow + 2) . ':AD' . ($newRow + 2));
    $sheet->setCellValue('X' . ($newRow + 3), 'a.4. Family problems');
    $sheet->mergeCells('X' . ($newRow + 3) . ':AD' . ($newRow + 3));
    for ($i = 0; $i <= 3; $i++) {
        $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
        $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
    }

    $newRow = 50 + $offset;
    $sheet->setCellValue('A' . $newRow, 'b. Average Daily Attendance = Total Daily Attendance');
    $sheet->mergeCells('A' . $newRow . ':D' . $newRow);
    $sheet->setCellValue('E' . $newRow, 'Number of School Days in reporting month');
    $sheet->mergeCells('E' . $newRow . ':W' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 52 + $offset;
    $sheet->setCellValue('A' . $newRow, 'c. Percentage of Attendance for the month = Average daily attendance x 100');
    $sheet->mergeCells('A' . $newRow . ':D' . $newRow);
    $sheet->setCellValue('E' . $newRow, 'Registered Learners as of end of the month');
    $sheet->mergeCells('E' . $newRow . ':W' . $newRow);
    $sheet->setCellValue('X' . $newRow, 'b. Individual-Related Factors');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 54 + $offset;
    $sheet->setCellValue('X' . $newRow, 'b.1. Illness');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('X' . ($newRow + 1), 'b.2. Overage');
    $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
    $sheet->setCellValue('X' . ($newRow + 2), 'b.3. Death');
    $sheet->mergeCells('X' . ($newRow + 2) . ':AD' . ($newRow + 2));
    $sheet->setCellValue('X' . ($newRow + 3), 'b.4. Drug Abuse');
    $sheet->mergeCells('X' . ($newRow + 3) . ':AD' . ($newRow + 3));
    $sheet->setCellValue('X' . ($newRow + 4), 'b.5. Poor academic performance');
    $sheet->mergeCells('X' . ($newRow + 4) . ':AD' . ($newRow + 4));
    $sheet->setCellValue('X' . ($newRow + 5), 'b.6. Lack of interest/Distractions');
    $sheet->mergeCells('X' . ($newRow + 5) . ':AD' . ($newRow + 5));
    $sheet->setCellValue('X' . ($newRow + 6), 'b.7. Hunger/Malnutrition');
    $sheet->mergeCells('X' . ($newRow + 6) . ':AD' . ($newRow + 6));
    $sheet->setCellValue('AE' . $newRow, 'Average Daily Attendance');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, $averageDaily);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    for ($i = 0; $i <= 6; $i++) {
        $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
        $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
    }

    $newRow = 56 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Percentage of Attendance for the month');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, $percentageAttendance);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 57 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Number of students absent for 5 consecutive days');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, $consecutiveAbsentCount);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 58 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'NLS');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, 0);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->setCellValue('X' . $newRow, '*Beginning of School Year cut-off report is every 1st Friday of the School Year');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 60 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Transferred out');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, 0);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->setCellValue('X' . $newRow, 'c.1. Teacher Factor');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('X' . ($newRow + 1), 'c.2. Physical condition of classroom');
    $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
    $sheet->setCellValue('X' . ($newRow + 2), 'c.3. Peer influence');
    $sheet->mergeCells('X' . ($newRow + 2) . ':AD' . ($newRow + 2));
    for ($i = 0; $i <= 2; $i++) {
        $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
        $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
    }

    $newRow = 62 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Transferred in');
    $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
    $sheet->setCellValue('AG' . $newRow, 0);
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 65 + $offset;
    $sheet->setCellValue('X' . $newRow, 'd. Geographic/Environmental');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('AE' . $newRow, 'I certify that this is a true and correct report.');
    $sheet->mergeCells('AE' . $newRow . ':AS' . $newRow);
    $sheet->setCellValue('X' . ($newRow + 1), 'd.1. Distance between home and school');
    $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
    $sheet->setCellValue('X' . ($newRow + 2), 'd.2. Armed conflict (incl. Tribal wars & clanfeuds)');
    $sheet->mergeCells('X' . ($newRow + 2) . ':AD' . ($newRow + 2));
    $sheet->setCellValue('X' . ($newRow + 3), 'd.3. Calamities/Disasters');
    $sheet->mergeCells('X' . ($newRow + 3) . ':AD' . ($newRow + 3));
    for ($i = 0; $i <= 3; $i++) {
        $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
        $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
    }

    $newRow = 69 + $offset;
    $sheet->setCellValue('X' . $newRow, 'e. Financial-Related');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('X' . ($newRow + 1), 'e.1. Child labor, work');
    $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
    $sheet->setCellValue('AG' . $newRow, '(Signature of Adviser over Printed Name)');
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    for ($i = 0; $i <= 1; $i++) {
        $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
        $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
    }

    $newRow = 71 + $offset;
    $sheet->setCellValue('AE' . $newRow, 'Attested by:');
    $sheet->mergeCells('AE' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    $newRow = 74 + $offset;
    $sheet->setCellValue('X' . $newRow, 'Generated thru LIS');
    $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
    $sheet->setCellValue('AG' . $newRow, '(Signature of School Head over Printed Name)');
    $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);
    $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
    $sheet->getRowDimension($newRow)->setRowHeight(15);

    // Save
    $filename = "sf2-{$month}-" . date('Y-m-d_H-i-s') . '.xlsx';
    $exportDir = 'exports';
    if (!file_exists($exportDir)) {
        mkdir($exportDir, 0777, true);
        chmod($exportDir, 0777);
    }
    $writer = new Xlsx($spreadsheet);
    $writer->save("$exportDir/$filename");
    chmod("$exportDir/$filename", 0644);
    echo json_encode(['success' => true, 'filename' => $filename]);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
}