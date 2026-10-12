
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$result_id = "";
$student_id = "";
$exam_id = "";
$marks_obtained = "";
$grade = "";
$remarks = "";

// ADD OR UPDATE RESULT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_result"])) {
    $result_id = trim($_POST["result_id"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $exam_id = trim($_POST["exam_id"] ?? "");
    $marks_obtained = trim($_POST["marks_obtained"] ?? "");
    $grade = trim($_POST["grade"] ?? "");
    $remarks = trim($_POST["remarks"] ?? "");

    if ($student_id === "" || $exam_id === "" || $marks_obtained === "") {
        $message = "Please select a student and examination and enter the marks obtained.";
        $messageType = "error";
    } elseif (!is_numeric($marks_obtained) || (float)$marks_obtained < 0) {
        $message = "Marks obtained must be a valid non-negative number.";
        $messageType = "error";
    } else {
        try {
            // Check the examination's maximum marks before saving.
            $check = $conn->prepare(
                "SELECT max_marks, total_marks
                 FROM examination
                 WHERE exam_id = ?"
            );
            $check->bind_param("i", $exam_id);
            $check->execute();
            $examInfo = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$examInfo) {
                $message = "The selected examination does not exist.";
                $messageType = "error";
            } else {
                $limit = $examInfo["max_marks"];

                if ($limit === null || $limit === "") {
                    $limit = $examInfo["total_marks"];
                }

                if ($limit !== null && $limit !== "" &&
                    (float)$marks_obtained > (float)$limit) {
                    $message = "Marks obtained cannot exceed the examination's maximum marks.";
                    $messageType = "error";
                } else {
                    if ($result_id !== "") {
                        $stmt = $conn->prepare(
                            "UPDATE result
                             SET student_id = ?,
                                 exam_id = ?,
                                 marks_obtained = ?,
                                 grade = NULLIF(?, ''),
                                 remarks = NULLIF(?, '')
                             WHERE result_id = ?"
                        );

                        $stmt->bind_param(
                            "iidssi",
                            $student_id,
                            $exam_id,
                            $marks_obtained,
                            $grade,
                            $remarks,
                            $result_id
                        );
                        $stmt->execute();
                        $stmt->close();

                        header("Location: results.php?updated=1");
                        exit;
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO result
                             (student_id, exam_id, marks_obtained, grade, remarks)
                             VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
                        );

                        $stmt->bind_param(
                            "iidss",
                            $student_id,
                            $exam_id,
                            $marks_obtained,
                            $grade,
                            $remarks
                        );
                        $stmt->execute();
                        $stmt->close();

                        header("Location: results.php?added=1");
                        exit;
                    }
                }
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save the result. Check the selected student and exam, and ensure a result is not already recorded for the same student and exam.";
            $messageType = "error";
        }
    }
}

// DELETE RESULT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_result"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM result WHERE result_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: results.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to delete this result.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Result added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Result updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Result deleted successfully.";
}

// LOAD RESULT FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM result WHERE result_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editResult) {
        $result_id = $editResult["result_id"];
        $student_id = $editResult["student_id"];
        $exam_id = $editResult["exam_id"];
        $marks_obtained = $editResult["marks_obtained"];
        $grade = $editResult["grade"] ?? "";
        $remarks = $editResult["remarks"] ?? "";
    }
}

// LOAD STUDENTS
$students = [];
$query = $conn->query(
    "SELECT student_id, student_name
     FROM student ORDER BY student_name"
);
while ($row = $query->fetch_assoc()) {
    $students[] = $row;
}

// LOAD EXAMINATIONS
$exams = [];
$query = $conn->query(
    "SELECT e.exam_id, e.exam_name, e.exam_date, s.subject_name
     FROM examination e
     LEFT JOIN subject s ON e.subject_id = s.subject_id
     ORDER BY e.exam_date DESC"
);
while ($row = $query->fetch_assoc()) {
    $exams[] = $row;
}

// LOAD RESULTS
$resultList = $conn->query(
    "SELECT r.*, st.student_name, e.exam_name, e.max_marks,
            e.total_marks, s.subject_name
     FROM result r
     LEFT JOIN student st ON r.student_id = st.student_id
     LEFT JOIN examination e ON r.exam_id = e.exam_id
     LEFT JOIN subject s ON e.subject_id = s.subject_id
     ORDER BY r.result_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | StudentHub</title>
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
        <a class="nav-link" href="examinations.php">📝 Examinations</a>
        <a class="nav-link active" href="results.php">📊 Results</a>
        <a class="nav-link" href="fee_payment.php">💳 Fee Payments</a>

        <div class="sidebar-bottom">
            <p>Student Management System</p>
            <small>DBMS Project · 2026</small>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Results Management</h1>
                <p>Record examination marks, grades, and remarks.</p>
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
                    <h2><?= $result_id !== "" ? "Edit Result" : "Add Result" ?></h2>
                    <p>Enter the student's examination performance.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="results.php" class="form-grid">

                    <input type="hidden" name="result_id"
                           value="<?= htmlspecialchars((string)$result_id) ?>">

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
                        <label for="exam_id">Examination *</label>
                        <select id="exam_id" name="exam_id" required>
                            <option value="">Select Examination</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= (int)$exam["exam_id"] ?>"
                                    <?= (string)$exam_id === (string)$exam["exam_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($exam["exam_name"]) ?>
                                    — <?= htmlspecialchars($exam["subject_name"] ?? "No Subject") ?>
                                    (<?= htmlspecialchars($exam["exam_date"]) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marks_obtained">Marks Obtained *</label>
                        <input type="number" id="marks_obtained"
                               name="marks_obtained"
                               value="<?= htmlspecialchars((string)$marks_obtained) ?>"
                               min="0" step="0.01"
                               placeholder="Enter marks" required>
                    </div>

                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <select id="grade" name="grade">
                            <option value="">Select Grade</option>
                            <?php foreach (["O", "A+", "A", "B+", "B", "C", "D", "F"] as $g): ?>
                                <option value="<?= $g ?>"
                                    <?= $grade === $g ? "selected" : "" ?>>
                                    <?= $g ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <input type="text" id="remarks" name="remarks"
                               value="<?= htmlspecialchars($remarks) ?>"
                               placeholder="e.g. Good performance">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_result" class="btn btn-primary">
                            <?= $result_id !== "" ? "Update Result" : "Save Result" ?>
                        </button>

                        <?php if ($result_id !== ""): ?>
                            <a href="results.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Result Records</h2>
                    <p>View, search, edit, or delete examination results.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="resultSearch"
                           placeholder="Search results...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="resultTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Examination</th>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Maximum</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($resultList->num_rows > 0): ?>
                            <?php while ($row = $resultList->fetch_assoc()): ?>
                                <?php
                                $maximum = $row["max_marks"];
                                if ($maximum === null || $maximum === "") {
                                    $maximum = $row["total_marks"];
                                }
                                ?>
                                <tr>
                                    <td><?= (int)$row["result_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td><?= htmlspecialchars($row["exam_name"] ?? "Unknown Exam") ?></td>
                                    <td><?= htmlspecialchars($row["subject_name"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars((string)$row["marks_obtained"]) ?></td>
                                    <td><?= htmlspecialchars((string)($maximum ?? "—")) ?></td>
                                    <td><?= htmlspecialchars($row["grade"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($row["remarks"] ?? "—") ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="results.php?edit=<?= (int)$row["result_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="results.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this result?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["result_id"] ?>">
                                                <button type="submit" name="delete_result"
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
                                <td colspan="9" class="empty-state">
                                    No results found. Add your first result above.
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
const resultSearch = document.getElementById("resultSearch");
const resultRows = document.querySelectorAll("#resultTable tbody tr");

resultSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    resultRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
