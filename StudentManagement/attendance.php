
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$attendance_id = "";
$student_id = "";
$subject_id = "";
$attendance_date = date("Y-m-d");
$status = "Present";

// ADD OR UPDATE ATTENDANCE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_attendance"])) {
    $attendance_id = trim($_POST["attendance_id"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $subject_id = trim($_POST["subject_id"] ?? "");
    $attendance_date = trim($_POST["attendance_date"] ?? "");
    $status = trim($_POST["status"] ?? "Present");

    $allowedStatuses = ["Present", "Absent", "Late"];

    if ($student_id === "" || $subject_id === "" || $attendance_date === "") {
        $message = "Please select a student, subject, and attendance date.";
        $messageType = "error";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $message = "Please select a valid attendance status.";
        $messageType = "error";
    } else {
        try {
            if ($attendance_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE attendance
                     SET student_id = ?,
                         subject_id = ?,
                         attendance_date = ?,
                         status = ?
                     WHERE attendance_id = ?"
                );

                $stmt->bind_param(
                    "iissi",
                    $student_id,
                    $subject_id,
                    $attendance_date,
                    $status,
                    $attendance_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: attendance.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO attendance
                     (student_id, subject_id, attendance_date, status)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiss",
                    $student_id,
                    $subject_id,
                    $attendance_date,
                    $status
                );
                $stmt->execute();
                $stmt->close();

                header("Location: attendance.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save attendance. Check that the selected student and subject exist and that the database constraints are satisfied.";
            $messageType = "error";
        }
    }
}

// DELETE ATTENDANCE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_attendance"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM attendance WHERE attendance_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: attendance.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to delete this attendance record.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Attendance record added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Attendance record updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Attendance record deleted successfully.";
}

// LOAD RECORD FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM attendance WHERE attendance_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editAttendance = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editAttendance) {
        $attendance_id = $editAttendance["attendance_id"];
        $student_id = $editAttendance["student_id"];
        $subject_id = $editAttendance["subject_id"];
        $attendance_date = $editAttendance["attendance_date"];
        $status = $editAttendance["status"];
    }
}

// LOAD STUDENTS
$students = [];
$result = $conn->query(
    "SELECT student_id, student_name
     FROM student ORDER BY student_name"
);
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// LOAD SUBJECTS
$subjects = [];
$result = $conn->query(
    "SELECT subject_id, subject_name, subject_code
     FROM subject ORDER BY subject_name"
);
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// LOAD ATTENDANCE RECORDS
$attendanceList = $conn->query(
    "SELECT a.*, s.student_name, sub.subject_name
     FROM attendance a
     LEFT JOIN student s ON a.student_id = s.student_id
     LEFT JOIN subject sub ON a.subject_id = sub.subject_id
     ORDER BY a.attendance_date DESC, a.attendance_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | StudentHub</title>
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

        <a class="nav-link" href="index.php">▦ Dashboard</a>
        <a class="nav-link" href="students.php">♙ Students</a>
        <a class="nav-link" href="departments.php">▤ Departments</a>
        <a class="nav-link" href="courses.php">📚 Courses</a>
        <a class="nav-link" href="subjects.php">📖 Subjects</a>
        <a class="nav-link" href="faculty.php">♙ Faculty</a>
        <a class="nav-link" href="enrollments.php">📝 Enrollments</a>
        <a class="nav-link active" href="attendance.php">📅 Attendance</a>
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
                <h1>Attendance Management</h1>
                <p>Record and manage student attendance.</p>
            </div>
            <div class="topbar-user">
                <span class="user-avatar">A</span>
                <div>
                    <strong>Administrator</strong>
                    <small>Management Portal</small>
                </div>
            </div>
        </header>

        <?php if ($message !== ""): ?>
            <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2><?= $attendance_id !== "" ? "Edit Attendance" : "Mark Attendance" ?></h2>
                    <p>Select the student, subject, date, and attendance status.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="attendance.php" class="form-grid">

                    <input type="hidden" name="attendance_id"
                           value="<?= htmlspecialchars((string)$attendance_id) ?>">

                    <div class="form-group">
                        <label for="student_id">Student *</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= (int)$student["student_id"] ?>"
                                    <?= (string)$student_id === (string)$student["student_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($student["student_name"]) ?>
                                    (ID: <?= (int)$student["student_id"] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= (int)$subject["subject_id"] ?>"
                                    <?= (string)$subject_id === (string)$subject["subject_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($subject["subject_name"]) ?>
                                    <?= !empty($subject["subject_code"]) ? " (" . htmlspecialchars($subject["subject_code"]) . ")" : "" ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="attendance_date">Attendance Date *</label>
                        <input type="date" id="attendance_date"
                               name="attendance_date"
                               value="<?= htmlspecialchars($attendance_date) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="status">Attendance Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach (["Present", "Absent", "Late"] as $option): ?>
                                <option value="<?= $option ?>"
                                    <?= $status === $option ? "selected" : "" ?>>
                                    <?= $option ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_attendance"
                                class="btn btn-primary">
                            <?= $attendance_id !== "" ? "Update Attendance" : "Save Attendance" ?>
                        </button>

                        <?php if ($attendance_id !== ""): ?>
                            <a href="attendance.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Attendance Records</h2>
                    <p>View, search, edit, or delete attendance records.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="attendanceSearch"
                           placeholder="Search attendance...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($attendanceList->num_rows > 0): ?>
                            <?php while ($row = $attendanceList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["attendance_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td><?= htmlspecialchars($row["subject_name"] ?? "Unknown Subject") ?></td>
                                    <td><?= htmlspecialchars($row["attendance_date"]) ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($row["status"]) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="attendance.php?edit=<?= (int)$row["attendance_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="attendance.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this attendance record?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["attendance_id"] ?>">
                                                <button type="submit"
                                                        name="delete_attendance"
                                                        class="btn btn-small btn-delete">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    No attendance records found. Mark your first attendance above.
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

<script>
const attendanceSearch = document.getElementById("attendanceSearch");
const attendanceRows = document.querySelectorAll("#attendanceTable tbody tr");

attendanceSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    attendanceRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
