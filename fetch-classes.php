<?php
require 'config.php';
$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT c.class_id, c.section_name, c.grade_level, c.room, c.attendance_percentage, c.status,
           s.subject_id, s.subject_code, s.subject_name,
           GROUP_CONCAT(JSON_OBJECT('day', sch.day, 'start', sch.start_time, 'end', sch.end_time)) as schedule,
           GROUP_CONCAT(JSON_OBJECT('lrn', st.lrn, 'first_name', st.first_name, 'last_name', st.last_name, 'email', st.email)) as students
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN schedules sch ON c.class_id = sch.class_id
    LEFT JOIN class_students cs ON c.class_id = cs.class_id
    LEFT JOIN students st ON cs.lrn = st.lrn
    WHERE c.teacher_id = :teacher_id
    GROUP BY c.class_id
");
$stmt->execute(['teacher_id' => $_SESSION['teacher_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($classes as &$class) {
    $class['schedule'] = $class['schedule'] ? json_decode('[' . $class['schedule'] . ']', true) : [];
    $class['students'] = $class['students'] ? json_decode('[' . $class['students'] . ']', true) : [];
}

header('Content-Type: application/json');
echo json_encode($classes);
?>