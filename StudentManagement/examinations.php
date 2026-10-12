
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$exam_id = "";
$exam_name = "";
$subject_id = "";
$exam_date = date("Y-m-d");
$total_marks = "";
$exam_type = "";
$max_marks = "";

// ADD OR UPDATE EXAMINATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_exam"])) {
    $exam_id = trim($_POST["exam_id"] ?? "");
    $exam_name = trim($_POST["exam_name"] ?? "");
    $subject_id = trim($_POST["subject_id"] ?? "");
    $exam_date = trim($_POST["exam_date"] ?? "");
    $total_marks = trim($_POST["total_marks"] ?? "");
    $exam_type = trim($_POST["exam_type"] ?? "");
    $max_marks = trim($_POST["max_marks"] ?? "");

    if ($exam_name === "" || $subject_id === "" || $exam_date === "") {
        $message = "Please enter the examination name, subject, and date.";
        $messageType = "error";
    } elseif (
        ($total_marks !== "" && (!is_numeric($total_marks) || (float)$total_marks < 0)) ||
        ($max_marks !== "" && (!is_numeric($max_marks) || (float)$max_marks <= 0))
    ) {
        $message = "Marks must be valid numbers. Maximum marks must be greater than zero.";
        $messageType = "error";
    } elseif (
        $total_marks !== "" && $max_marks !== "" &&
        (float)$total_marks > (float)$max_marks
    ) {
        $message = "Total marks cannot exceed maximum marks.";
        $messageType = "error";
    } else {
        try {
            if ($exam_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE examination
                     SET exam_name = ?,
                         subject_id = ?,
                         exam_date = ?,
                         total_marks = NULLIF(?, ''),
                         exam_type = NULLIF(?, ''),
                         max_marks = NULLIF(?, '')
                     WHERE exam_id = ?"
                );

                $stmt->bind_param(
                    "sissssi",
                    $exam_name,
                    $subject_id,
                    $exam_date,
                    $total_marks,
                    $exam_type,
                    $max_marks,
                    $exam_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: examinations.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO examination
                     (exam_name, subject_id, exam_date, total_marks, exam_type, max_marks)
                     VALUES (?, ?, ?, NULLIF(?, ''),
                             NULLIF(?, ''), NULLIF(?, ''))"
                );

                $stmt->bind_param(
                    "sissss",
                    $exam_name,
                    $subject_id,
                    $exam_date,
                    $total_marks,
                    $exam_type,
                    $max_marks
                );
                $stmt->execute();
                $stmt->close();

                header("Location: examinations.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save the examination. Check the subject and the database constraints.";
            $messageType = "error";
        }
    }
}

// DELETE EXAMINATION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_exam"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM examination WHERE exam_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: examinations.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "This examination cannot be deleted because result records may depend on it. Remove related results first.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Examination added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Examination updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Examination deleted successfully.";
}

// LOAD EXAMINATION FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM examination WHERE exam_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editExam = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editExam) {
        $exam_id = $editExam["exam_id"];
        $exam_name = $editExam["exam_name"];
        $subject_id = $editExam["subject_id"];
        $exam_date = $editExam["exam_date"];
        $total_marks = $editExam["total_marks"] ?? "";
        $exam_type = $editExam["exam_type"] ?? "";
        $max_marks = $editExam["max_marks"] ?? "";
    }
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

// LOAD EXAMINATIONS
$examList = $conn->query(
    "SELECT e.*, s.subject_name
     FROM examination e
     LEFT JOIN subject s ON e.subject_id = s.subject_id
     ORDER BY e.exam_date DESC, e.exam_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examinations | StudentHub</title>
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
        <a class="nav-link" href="attendance.php">📅 Attendance</a>
        <a class="nav-link active" href="examinations.php">📝 Examinations</a>
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
                <h1>Examinations Management</h1>
                <p>Schedule exams and manage subject-wise examination details.</p>
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
                    <h2><?= $exam_id !== "" ? "Edit Examination" : "Add Examination" ?></h2>
                    <p>Enter the examination schedule and marks details.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="examinations.php" class="form-grid">

                    <input type="hidden" name="exam_id"
                           value="<?= htmlspecialchars((string)$exam_id) ?>">

                    <div class="form-group">
                        <label for="exam_name">Examination Name *</label>
                        <input type="text" id="exam_name" name="exam_name"
                               value="<?= htmlspecialchars($exam_name) ?>"
                               placeholder="e.g. DBMS Internal Test 1" required>
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
                        <label for="exam_date">Examination Date *</label>
                        <input type="date" id="exam_date" name="exam_date"
                               value="<?= htmlspecialchars($exam_date) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="exam_type">Examination Type</label>
                        <select id="exam_type" name="exam_type">
                            <option value="">Select Type</option>
                            <?php foreach (["Internal", "Midterm", "Final", "Practical", "Assignment", "Other"] as $type): ?>
                                <option value="<?= $type ?>"
                                    <?= $exam_type === $type ? "selected" : "" ?>>
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks" name="total_marks"
                               value="<?= htmlspecialchars((string)$total_marks) ?>"
                               min="0" step="0.01" placeholder="e.g. 40">
                    </div>

                    <div class="form-group">
                        <label for="max_marks">Maximum Marks</label>
                        <input type="number" id="max_marks" name="max_marks"
                               value="<?= htmlspecialchars((string)$max_marks) ?>"
                               min="0.01" step="0.01" placeholder="e.g. 50">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_exam" class="btn btn-primary">
                            <?= $exam_id !== "" ? "Update Examination" : "Add Examination" ?>
                        </button>

                        <?php if ($exam_id !== ""): ?>
                            <a href="examinations.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Examination Records</h2>
                    <p>View, search, edit, or delete examinations.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="examSearch"
                           placeholder="Search examinations...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="examTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Examination</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Total Marks</th>
                                <th>Maximum Marks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($examList->num_rows > 0): ?>
                            <?php while ($row = $examList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["exam_id"] ?></td>
                                    <td><strong><?= htmlspecialchars($row["exam_name"]) ?></strong></td>
                                    <td><?= htmlspecialchars($row["subject_name"] ?? "Unknown Subject") ?></td>
                                    <td><?= htmlspecialchars($row["exam_date"]) ?></td>
                                    <td><?= htmlspecialchars($row["exam_type"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars((string)($row["total_marks"] ?? "—")) ?></td>
                                    <td><?= htmlspecialchars((string)($row["max_marks"] ?? "—")) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="examinations.php?edit=<?= (int)$row["exam_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="examinations.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this examination?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["exam_id"] ?>">
                                                <button type="submit" name="delete_exam"
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
                                    No examinations found. Add your first examination above.
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
const examSearch = document.getElementById("examSearch");
const examRows = document.querySelectorAll("#examTable tbody tr");

examSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    examRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
