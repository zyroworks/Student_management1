
<?php
require_once "db.php";

$message = "";
$messageType = "success";
$editCourse = null;

// Handle Add, Update and Delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ADD OR UPDATE COURSE
    if ($action === "add" || $action === "update") {
        $course_name = trim($_POST["course_name"] ?? "");
        $course_code = trim($_POST["course_code"] ?? "");
        $duration = trim($_POST["duration"] ?? "");
        $department_id = trim($_POST["department_id"] ?? "");

        if ($course_name === "") {
            $message = "Course name is required.";
            $messageType = "error";
        } elseif (
            $duration !== "" &&
            (!ctype_digit($duration) ||
             (int)$duration < 1 ||
             (int)$duration > 10)
        ) {
            $message = "Duration must be between 1 and 10 years.";
            $messageType = "error";
        } else {
            try {
                if ($action === "add") {
                    $stmt = $conn->prepare(
                        "INSERT INTO course
                         (course_name, course_code, duration, department_id)
                         VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))"
                    );

                    $stmt->bind_param(
                        "ssss",
                        $course_name,
                        $course_code,
                        $duration,
                        $department_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: courses.php?status=added");
                    exit;
                }

                if ($action === "update") {
                    $course_id = (int)($_POST["course_id"] ?? 0);

                    if ($course_id <= 0) {
                        throw new Exception("Invalid course ID.");
                    }

                    $stmt = $conn->prepare(
                        "UPDATE course
                         SET course_name = ?,
                             course_code = NULLIF(?, ''),
                             duration = NULLIF(?, ''),
                             department_id = NULLIF(?, '')
                         WHERE course_id = ?"
                    );

                    $stmt->bind_param(
                        "ssssi",
                        $course_name,
                        $course_code,
                        $duration,
                        $department_id,
                        $course_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: courses.php?status=updated");
                    exit;
                }
            } catch (Throwable $e) {
                $message = "Could not save the course. Check whether its code already exists and the selected department is valid.";
                $messageType = "error";
            }
        }
    }

    // DELETE COURSE
    if ($action === "delete") {
        $course_id = (int)($_POST["course_id"] ?? 0);

        if ($course_id <= 0) {
            $message = "Invalid course ID.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM course WHERE course_id = ?"
                );

                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $stmt->close();

                header("Location: courses.php?status=deleted");
                exit;
            } catch (Throwable $e) {
                $message = "This course cannot be deleted because subjects or other records may be linked to it. Reassign or remove the related records first.";
                $messageType = "error";
            }
        }
    }
}

// Success messages
if (isset($_GET["status"])) {
    $status = $_GET["status"];

    if ($status === "added") {
        $message = "Course added successfully!";
    } elseif ($status === "updated") {
        $message = "Course updated successfully!";
    } elseif ($status === "deleted") {
        $message = "Course deleted successfully!";
    } else {
        $message = "";
    }
}

// Load course for editing
if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT course_id, course_name, course_code,
                duration, department_id
         FROM course
         WHERE course_id = ?"
    );

    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editCourse = $result->fetch_assoc();

    $stmt->close();

    if (!$editCourse) {
        $message = "Course record not found.";
        $messageType = "error";
    }
}

// Load departments for dropdown
$departments = $conn->query(
    "SELECT department_id, department_name
     FROM department
     ORDER BY department_name"
);

// Load course records
$courses = $conn->query(
    "SELECT c.course_id, c.course_name, c.course_code,
            c.duration, c.department_id, d.department_name
     FROM course c
     LEFT JOIN department d
        ON c.department_id = d.department_id
     ORDER BY c.course_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses | StudentHub</title>
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
        <a class="nav-link active" href="courses.php">📚 Courses</a>
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
                <h1>Course Management</h1>
                <p>Add, edit, delete and manage courses.</p>
            </div>

            <a href="courses.php#course-form" class="primary-button">
                + Add Course
            </a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ADD / EDIT COURSE -->
        <section class="form-card" id="course-form">
            <div class="section-heading">
                <h2><?= $editCourse ? "Edit Course" : "Add New Course" ?></h2>
                <p>Enter course details and assign a department.</p>
            </div>

            <form method="POST" action="courses.php">
                <input
                    type="hidden"
                    name="action"
                    value="<?= $editCourse ? "update" : "add" ?>"
                >

                <?php if ($editCourse): ?>
                    <input
                        type="hidden"
                        name="course_id"
                        value="<?= (int)$editCourse["course_id"] ?>"
                    >
                <?php endif; ?>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input
                            type="text"
                            id="course_name"
                            name="course_name"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars($editCourse["course_name"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="course_code">Course Code</label>
                        <input
                            type="text"
                            id="course_code"
                            name="course_code"
                            maxlength="20"
                            value="<?= htmlspecialchars($editCourse["course_code"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (years)</label>
                        <input
                            type="number"
                            id="duration"
                            name="duration"
                            min="1"
                            max="10"
                            value="<?= htmlspecialchars((string)($editCourse["duration"] ?? "")) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id">
                            <option value="">Select department</option>

                            <?php if ($departments): ?>
                                <?php while ($d = $departments->fetch_assoc()): ?>
                                    <option
                                        value="<?= (int)$d["department_id"] ?>"
                                        <?= ((string)($editCourse["department_id"] ?? "") === (string)$d["department_id"]) ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($d["department_name"]) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                </div>

                <button class="submit-btn" type="submit">
                    <?= $editCourse ? "Save Changes" : "+ Add Course" ?>
                </button>

                <?php if ($editCourse): ?>
                    <a href="courses.php" class="primary-button">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- COURSE RECORDS -->
        <section class="table-card">
            <div class="section-heading">
                <h2>Course Records</h2>
                <p>View and manage all registered courses.</p>
            </div>

            <div class="search-row">
                <input
                    type="search"
                    id="courseSearch"
                    class="search-input"
                    placeholder="Search courses..."
                    aria-label="Search courses"
                >
            </div>

            <div class="table-wrap">
                <table class="data-table" id="coursesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>COURSE NAME</th>
                            <th>CODE</th>
                            <th>DURATION</th>
                            <th>DEPARTMENT</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($courses && $courses->num_rows > 0): ?>
                        <?php while ($c = $courses->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$c["course_id"] ?></td>
                                <td><?= htmlspecialchars($c["course_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($c["course_code"] ?? "—") ?></td>
                                <td>
                                    <?= htmlspecialchars((string)($c["duration"] ?? "—")) ?>
                                    <?= !empty($c["duration"]) ? " years" : "" ?>
                                </td>
                                <td><?= htmlspecialchars($c["department_name"] ?? "—") ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a
                                            href="courses.php?edit=<?= (int)$c["course_id"] ?>#course-form"
                                            class="primary-button"
                                            style="padding:8px 12px;font-size:12px;"
                                        >Edit</a>

                                        <form
                                            method="POST"
                                            action="courses.php"
                                            onsubmit="return confirm('Delete this course? Related subjects or records may prevent deletion.');"
                                            style="display:inline;"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input
                                                type="hidden"
                                                name="course_id"
                                                value="<?= (int)$c["course_id"] ?>"
                                            >
                                            <button
                                                type="submit"
                                                class="submit-btn"
                                                style="margin:0;padding:8px 12px;background:#dc2626;font-size:12px;"
                                            >Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No courses found. Add your first course above.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="footer">
            <span>Student Management System</span>
            <span>DBMS PBL Project · 2026</span>
        </footer>

    </main>
</div>

<script>
document.getElementById("courseSearch").addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#coursesTable tbody tr");

    rows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(query)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
