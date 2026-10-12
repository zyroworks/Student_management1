
<?php
require_once "db.php";

// GET LIVE DATABASE COUNTS
function getCount($conn, $table) {
    $allowedTables = [
        "student", "department", "course", "subject", "faculty",
        "enrollment", "attendance", "examination", "result", "fee_payment"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $query = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    return (int)$query->fetch_assoc()["total"];
}

$totalStudents = getCount($conn, "student");
$totalDepartments = getCount($conn, "department");
$totalCourses = getCount($conn, "course");
$totalSubjects = getCount($conn, "subject");
$totalFaculty = getCount($conn, "faculty");
$totalEnrollments = getCount($conn, "enrollment");
$totalAttendance = getCount($conn, "attendance");
$totalExams = getCount($conn, "examination");
$totalResults = getCount($conn, "result");
$totalPayments = getCount($conn, "fee_payment");

// FEE SUMMARY
$feeSummary = $conn->query(
    "SELECT
        COALESCE(SUM(CASE WHEN payment_status = 'Paid'
            THEN amount ELSE 0 END), 0) AS paid_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'Pending'
            THEN amount ELSE 0 END), 0) AS pending_amount
     FROM fee_payment"
)->fetch_assoc();

$paidAmount = (float)$feeSummary["paid_amount"];
$pendingAmount = (float)$feeSummary["pending_amount"];

// RECENT STUDENTS
$recentStudents = $conn->query(
    "SELECT student_id, student_name, email, gender
     FROM student
     ORDER BY student_id DESC
     LIMIT 5"
);

// RECENT ENROLLMENTS
$recentEnrollments = $conn->query(
    "SELECT e.enrollment_id, e.enrollment_date, e.status,
            s.student_name, c.course_name
     FROM enrollment e
     LEFT JOIN student s ON e.student_id = s.student_id
     LEFT JOIN course c ON e.course_id = c.course_id
     ORDER BY e.enrollment_id DESC
     LIMIT 5"
);

// ATTENDANCE SUMMARY
$attendanceSummary = $conn->query(
    "SELECT
        COUNT(*) AS total_records,
        COALESCE(SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END), 0)
            AS present_count,
        COALESCE(SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END), 0)
            AS absent_count
     FROM attendance"
)->fetch_assoc();

$attendanceTotal = (int)$attendanceSummary["total_records"];
$presentCount = (int)$attendanceSummary["present_count"];
$absentCount = (int)$attendanceSummary["absent_count"];

$presentPercentage = $attendanceTotal > 0
    ? round(($presentCount / $attendanceTotal) * 100)
    : 0;

$absentPercentage = $attendanceTotal > 0
    ? round(($absentCount / $attendanceTotal) * 100)
    : 0;

// RECENT PAYMENTS
$recentPayments = $conn->query(
    "SELECT p.payment_id, p.amount, p.payment_date,
            p.payment_status, s.student_name
     FROM fee_payment p
     LEFT JOIN student s ON p.student_id = s.student_id
     ORDER BY p.payment_id DESC
     LIMIT 5"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <h2>StudentHub</h2>
                <small>Management Portal</small>
            </div>
        </div>

        <p class="menu-label">MAIN MENU</p>

        <a class="nav-link active" href="index.php">▦ Dashboard</a>
        <a class="nav-link" href="students.php">♙ Students</a>
        <a class="nav-link" href="departments.php">▤ Departments</a>
        <a class="nav-link" href="courses.php">📚 Courses</a>
        <a class="nav-link" href="subjects.php">📖 Subjects</a>
        <a class="nav-link" href="faculty.php">♙ Faculty</a>
        <a class="nav-link" href="enrollments.php">📝 Enrollments</a>
        <a class="nav-link" href="attendance.php">📅 Attendance</a>
        <a class="nav-link" href="examinations.php">📝 Examinations</a>
        <a class="nav-link" href="results.php">📊 Results</a>
        <a class="nav-link" href="fee_payment.php">💳 Fee Payments</a>

        <div class="sidebar-bottom">
            <p>Student Management System</p>
            <small>DBMS Project · 2026</small>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, Administrator! Here's your system overview.</p>
            </div>

            <div class="topbar-user">
                <span class="user-avatar">A</span>
                <div>
                    <strong>Administrator</strong>
                    <small>Management Portal</small>
                </div>
            </div>
        </header>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Overview</h2>
                    <p>Live statistics from your Student Management System database.</p>
                </div>
            </div>

            <div class="stats-grid">

                <a href="students.php" class="stat-card">
                    <div class="stat-icon">♙</div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value"><?= $totalStudents ?></div>
                    <div class="stat-note">Registered students</div>
                </a>

                <a href="faculty.php" class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-label">Faculty Members</div>
                    <div class="stat-value"><?= $totalFaculty ?></div>
                    <div class="stat-note">Teaching staff</div>
                </a>

                <a href="departments.php" class="stat-card">
                    <div class="stat-icon">▤</div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-value"><?= $totalDepartments ?></div>
                    <div class="stat-note">Academic departments</div>
                </a>

                <a href="courses.php" class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-label">Courses</div>
                    <div class="stat-value"><?= $totalCourses ?></div>
                    <div class="stat-note">Available courses</div>
                </a>

                <a href="subjects.php" class="stat-card">
                    <div class="stat-icon">📖</div>
                    <div class="stat-label">Subjects</div>
                    <div class="stat-value"><?= $totalSubjects ?></div>
                    <div class="stat-note">Academic subjects</div>
                </a>

                <a href="enrollments.php" class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-label">Enrollments</div>
                    <div class="stat-value"><?= $totalEnrollments ?></div>
                    <div class="stat-note">Course registrations</div>
                </a>

                <a href="attendance.php" class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-label">Attendance Records</div>
                    <div class="stat-value"><?= $totalAttendance ?></div>
                    <div class="stat-note">Recorded attendance</div>
                </a>

                <a href="examinations.php" class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-label">Examinations</div>
                    <div class="stat-value"><?= $totalExams ?></div>
                    <div class="stat-note">Scheduled exams</div>
                </a>

                <a href="results.php" class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-label">Results</div>
                    <div class="stat-value"><?= $totalResults ?></div>
                    <div class="stat-note">Recorded results</div>
                </a>

                <a href="fee_payment.php" class="stat-card">
                    <div class="stat-icon">💳</div>
                    <div class="stat-label">Payment Records</div>
                    <div class="stat-value"><?= $totalPayments ?></div>
                    <div class="stat-note">Fee transactions recorded</div>
                </a>

            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Fee Overview</h2>
                    <p>Summary of recorded fee payments.</p>
                </div>
            </div>

            <div class="stats-grid fee-summary-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Fees Paid</div>
                    <div class="stat-value">₹<?= number_format($paidAmount, 2) ?></div>
                    <div class="stat-note">Records marked Paid</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Pending Fees</div>
                    <div class="stat-value">₹<?= number_format($pendingAmount, 2) ?></div>
                    <div class="stat-note">Records marked Pending</div>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Attendance Overview</h2>
                    <p>Summary across all recorded attendance entries.</p>
                </div>
                <a href="attendance.php" class="btn btn-secondary">View Attendance</a>
            </div>

            <div class="panel">
                <div class="attendance-overview">
                    <div class="attendance-item">
                        <span>Present</span>
                        <strong><?= $presentCount ?></strong>
                        <small><?= $presentPercentage ?>% of recorded entries</small>
                    </div>
                    <div class="attendance-item">
                        <span>Absent</span>
                        <strong><?= $absentCount ?></strong>
                        <small><?= $absentPercentage ?>% of recorded entries</small>
                    </div>
                    <div class="attendance-item">
                        <span>Total Records</span>
                        <strong><?= $attendanceTotal ?></strong>
                        <small>All attendance entries</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Recent Students</h2>
                    <p>The latest student records added to the system.</p>
                </div>
                <a href="students.php" class="btn btn-secondary">View All Students</a>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentStudents->num_rows > 0): ?>
                            <?php while ($row = $recentStudents->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["student_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"]) ?></td>
                                    <td><?= htmlspecialchars($row["email"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($row["gender"] ?? "—") ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    No students have been added yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Recent Enrollments</h2>
                    <p>Latest student course registrations.</p>
                </div>
                <a href="enrollments.php" class="btn btn-secondary">View All Enrollments</a>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentEnrollments->num_rows > 0): ?>
                            <?php while ($row = $recentEnrollments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["enrollment_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td><?= htmlspecialchars($row["course_name"] ?? "Unknown Course") ?></td>
                                    <td><?= htmlspecialchars($row["enrollment_date"]) ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($row["status"]) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    No enrollments have been added yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Recent Fee Payments</h2>
                    <p>The latest fee transactions recorded in the database.</p>
                </div>
                <a href="fee_payment.php" class="btn btn-secondary">View All Payments</a>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentPayments->num_rows > 0): ?>
                            <?php while ($row = $recentPayments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["payment_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td>₹<?= number_format((float)$row["amount"], 2) ?></td>
                                    <td><?= htmlspecialchars($row["payment_date"]) ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($row["payment_status"]) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    No fee payments have been recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>
</div>

</body>
</html>
