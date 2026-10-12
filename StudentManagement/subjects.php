
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$subject_id = "";
$subject_name = "";
$subject_code = "";
$credits = "";
$course_id = "";
$faculty_id = "";

// ADD OR UPDATE SUBJECT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_subject"])) {
    $subject_id = trim($_POST["subject_id"] ?? "");
    $subject_name = trim($_POST["subject_name"] ?? "");
    $subject_code = trim($_POST["subject_code"] ?? "");
    $credits = trim($_POST["credits"] ?? "");
    $course_id = trim($_POST["course_id"] ?? "");
    $faculty_id = trim($_POST["faculty_id"] ?? "");

    if ($subject_name === "") {
        $message = "Subject name is required.";
        $messageType = "error";
    } elseif ($credits !== "" && (!ctype_digit($credits) || (int)$credits < 1 || (int)$credits > 10)) {
        $message = "Credits must be a whole number between 1 and 10.";
        $messageType = "error";
    } else {
        try {
            if ($subject_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE subject
                     SET subject_name = ?,
                         subject_code = NULLIF(?, ''),
                         credits = NULLIF(?, ''),
                         course_id = NULLIF(?, ''),
                         faculty_id = NULLIF(?, '')
                     WHERE subject_id = ?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $subject_name,
                    $subject_code,
                    $credits,
                    $course_id,
                    $faculty_id,
                    $subject_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: subjects.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO subject
                     (subject_name, subject_code, credits, course_id, faculty_id)
                     VALUES (?, NULLIF(?, ''), NULLIF(?, ''),
                             NULLIF(?, ''), NULLIF(?, ''))"
                );

                $stmt->bind_param(
                    "sssss",
                    $subject_name,
                    $subject_code,
                    $credits,
                    $course_id,
                    $faculty_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: subjects.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save the subject. Check whether the subject code already exists and whether the selected course and faculty are valid.";
            $messageType = "error";
        }
    }
}

// DELETE SUBJECT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_subject"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM subject WHERE subject_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: subjects.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "This subject cannot be deleted because attendance or examination records may depend on it. Remove the related records first.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Subject added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Subject updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Subject deleted successfully.";
}

// LOAD SUBJECT FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM subject WHERE subject_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editSubject = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editSubject) {
        $subject_id = $editSubject["subject_id"];
        $subject_name = $editSubject["subject_name"];
        $subject_code = $editSubject["subject_code"] ?? "";
        $credits = $editSubject["credits"] ?? "";
        $course_id = $editSubject["course_id"] ?? "";
        $faculty_id = $editSubject["faculty_id"] ?? "";
    }
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

// LOAD FACULTY
$facultyMembers = [];
$result = $conn->query(
    "SELECT faculty_id, faculty_name
     FROM faculty ORDER BY faculty_name"
);
while ($row = $result->fetch_assoc()) {
    $facultyMembers[] = $row;
}

// LOAD SUBJECT LIST
$subjectList = $conn->query(
    "SELECT s.*, c.course_name, f.faculty_name
     FROM subject s
     LEFT JOIN course c ON s.course_id = c.course_id
     LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
     ORDER BY s.subject_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects | StudentHub</title>
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
        <a class="nav-link active" href="subjects.php">📖 Subjects</a>
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
                <h1>Subjects Management</h1>
                <p>Manage subjects, credits, courses, and assigned faculty.</p>
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
                    <h2><?= $subject_id !== "" ? "Edit Subject" : "Add Subject" ?></h2>
                    <p>Enter the subject details below.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="subjects.php" class="form-grid">

                    <input type="hidden" name="subject_id"
                           value="<?= htmlspecialchars((string)$subject_id) ?>">

                    <div class="form-group">
                        <label for="subject_name">Subject Name *</label>
                        <input type="text" id="subject_name" name="subject_name"
                               value="<?= htmlspecialchars($subject_name) ?>"
                               placeholder="e.g. Database Management Systems" required>
                    </div>

                    <div class="form-group">
                        <label for="subject_code">Subject Code</label>
                        <input type="text" id="subject_code" name="subject_code"
                               value="<?= htmlspecialchars($subject_code) ?>"
                               placeholder="e.g. DBMS301">
                    </div>

                    <div class="form-group">
                        <label for="credits">Credits</label>
                        <input type="number" id="credits" name="credits"
                               value="<?= htmlspecialchars((string)$credits) ?>"
                               min="1" max="10" placeholder="e.g. 4">
                    </div>

                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id">
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
                        <label for="faculty_id">Assigned Faculty</label>
                        <select id="faculty_id" name="faculty_id">
                            <option value="">Select Faculty</option>
                            <?php foreach ($facultyMembers as $faculty): ?>
                                <option value="<?= (int)$faculty["faculty_id"] ?>"
                                    <?= (string)$faculty_id === (string)$faculty["faculty_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($faculty["faculty_name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_subject" class="btn btn-primary">
                            <?= $subject_id !== "" ? "Update Subject" : "Add Subject" ?>
                        </button>

                        <?php if ($subject_id !== ""): ?>
                            <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Subject Directory</h2>
                    <p>View, search, edit, or delete subjects.</p>
                </div>

                <div class="search-box">
                    <input type="search" id="subjectSearch"
                           placeholder="Search subjects...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="subjectTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Code</th>
                                <th>Credits</th>
                                <th>Course</th>
                                <th>Faculty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($subjectList->num_rows > 0): ?>
                            <?php while ($row = $subjectList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["subject_id"] ?></td>
                                    <td><strong><?= htmlspecialchars($row["subject_name"]) ?></strong></td>
                                    <td><?= htmlspecialchars($row["subject_code"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars((string)($row["credits"] ?? "—")) ?></td>
                                    <td><?= htmlspecialchars($row["course_name"] ?? "Not Assigned") ?></td>
                                    <td><?= htmlspecialchars($row["faculty_name"] ?? "Not Assigned") ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="subjects.php?edit=<?= (int)$row["subject_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="subjects.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["subject_id"] ?>">
                                                <button type="submit" name="delete_subject"
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
                                <td colspan="7" class="empty-state">
                                    No subjects found. Add your first subject above.
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
const subjectSearch = document.getElementById("subjectSearch");
const subjectRows = document.querySelectorAll("#subjectTable tbody tr");

subjectSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    subjectRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
