
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$enrollment_id = "";
$student_id = "";
$course_id = "";
$enrollment_date = date("Y-m-d");
$academic_year = "";
$status = "Active";
$semester = "";

// ADD OR UPDATE ENROLLMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_enrollment"])) {
    $enrollment_id = trim($_POST["enrollment_id"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $course_id = trim($_POST["course_id"] ?? "");
    $enrollment_date = trim($_POST["enrollment_date"] ?? "");
    $academic_year = trim($_POST["academic_year"] ?? "");
    $status = trim($_POST["status"] ?? "Active");
    $semester = trim($_POST["semester"] ?? "");

    $allowedStatuses = ["Active", "Completed", "Cancelled"];

    if ($student_id === "" || $course_id === "" || $enrollment_date === "") {
        $message = "Please select a student, course, and enrollment date.";
        $messageType = "error";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $message = "Please select a valid enrollment status.";
        $messageType = "error";
    } else {
        try {
            if ($enrollment_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE enrollment
                     SET student_id = ?,
                         course_id = ?,
                         enrollment_date = ?,
                         academic_year = NULLIF(?, ''),
                         status = ?,
                         semester = NULLIF(?, '')
                     WHERE enrollment_id = ?"
                );

                $stmt->bind_param(
                    "iissssi",
                    $student_id,
                    $course_id,
                    $enrollment_date,
                    $academic_year,
                    $status,
                    $semester,
                    $enrollment_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: enrollments.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO enrollment
                     (student_id, course_id, enrollment_date, academic_year, status, semester)
                     VALUES (?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''))"
                );

                $stmt->bind_param(
                    "iissss",
                    $student_id,
                    $course_id,
                    $enrollment_date,
                    $academic_year,
                    $status,
                    $semester
                );
                $stmt->execute();
                $stmt->close();

                header("Location: enrollments.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save enrollment. Check that the selected student and course exist and that the database constraints are satisfied.";
            $messageType = "error";
        }
    }
}

// DELETE ENROLLMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_enrollment"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM enrollment WHERE enrollment_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: enrollments.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to delete this enrollment because related records may depend on it.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Enrollment added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Enrollment updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Enrollment deleted successfully.";
}

// LOAD ENROLLMENT FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM enrollment WHERE enrollment_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editEnrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editEnrollment) {
        $enrollment_id = $editEnrollment["enrollment_id"];
        $student_id = $editEnrollment["student_id"];
        $course_id = $editEnrollment["course_id"];
        $enrollment_date = $editEnrollment["enrollment_date"];
        $academic_year = $editEnrollment["academic_year"] ?? "";
        $status = $editEnrollment["status"];
        $semester = $editEnrollment["semester"] ?? "";
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

// LOAD COURSES
$courses = [];
$result = $conn->query(
    "SELECT course_id, course_name, course_code
     FROM course ORDER BY course_name"
);
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// LOAD ENROLLMENTS
$enrollmentList = $conn->query(
    "SELECT e.*, s.student_name, c.course_name
     FROM enrollment e
     LEFT JOIN student s ON e.student_id = s.student_id
     LEFT JOIN course c ON e.course_id = c.course_id
     ORDER BY e.enrollment_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments | StudentHub</title>
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
        <a class="nav-link active" href="enrollments.php">📝 Enrollments</a>
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
                <h1>Enrollment Management</h1>
                <p>Manage student course registrations and enrollment status.</p>
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
                    <h2><?= $enrollment_id !== "" ? "Edit Enrollment" : "Add Enrollment" ?></h2>
                    <p>Register a student for a course.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="enrollments.php" class="form-grid">

                    <input type="hidden" name="enrollment_id"
                           value="<?= htmlspecialchars((string)$enrollment_id) ?>">

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
                        <label for="course_id">Course *</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int)$course["course_id"] ?>"
                                    <?= (string)$course_id === (string)$course["course_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($course["course_name"]) ?>
                                    <?= !empty($course["course_code"]) ? " (" . htmlspecialchars($course["course_code"]) . ")" : "" ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="enrollment_date">Enrollment Date *</label>
                        <input type="date" id="enrollment_date" name="enrollment_date"
                               value="<?= htmlspecialchars($enrollment_date) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year"
                               value="<?= htmlspecialchars($academic_year) ?>"
                               placeholder="e.g. 2026-2027">
                    </div>

                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester">
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>"
                                    <?= (string)$semester === (string)$i ? "selected" : "" ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (["Active", "Completed", "Cancelled"] as $option): ?>
                                <option value="<?= $option ?>"
                                    <?= $status === $option ? "selected" : "" ?>>
                                    <?= $option ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_enrollment" class="btn btn-primary">
                            <?= $enrollment_id !== "" ? "Update Enrollment" : "Add Enrollment" ?>
                        </button>

                        <?php if ($enrollment_id !== ""): ?>
                            <a href="enrollments.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Enrollment Records</h2>
                    <p>View, search, edit, or delete enrollments.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="enrollmentSearch"
                           placeholder="Search enrollments...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="enrollmentTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Enrollment Date</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($enrollmentList->num_rows > 0): ?>
                            <?php while ($row = $enrollmentList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["enrollment_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td><?= htmlspecialchars($row["course_name"] ?? "Unknown Course") ?></td>
                                    <td><?= htmlspecialchars($row["enrollment_date"]) ?></td>
                                    <td><?= htmlspecialchars($row["academic_year"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars((string)($row["semester"] ?? "—")) ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($row["status"]) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="enrollments.php?edit=<?= (int)$row["enrollment_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="enrollments.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this enrollment?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["enrollment_id"] ?>">
                                                <button type="submit" name="delete_enrollment"
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
                                <td colspan="8" class="empty-state">
                                    No enrollments found. Add your first enrollment above.
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
const enrollmentSearch = document.getElementById("enrollmentSearch");
const enrollmentRows = document.querySelectorAll("#enrollmentTable tbody tr");

enrollmentSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    enrollmentRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
