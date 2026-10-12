
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$faculty_id = "";
$faculty_name = "";
$email = "";
$phone = "";
$designation = "";
$department_id = "";

// ADD OR UPDATE FACULTY
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_faculty"])) {
    $faculty_id = trim($_POST["faculty_id"] ?? "");
    $faculty_name = trim($_POST["faculty_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $designation = trim($_POST["designation"] ?? "");
    $department_id = trim($_POST["department_id"] ?? "");

    if ($faculty_name === "") {
        $message = "Faculty name is required.";
        $messageType = "error";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        try {
            if ($faculty_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE faculty
                     SET faculty_name = ?,
                         email = NULLIF(?, ''),
                         phone = NULLIF(?, ''),
                         designation = NULLIF(?, ''),
                         department_id = NULLIF(?, '')
                     WHERE faculty_id = ?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $faculty_name,
                    $email,
                    $phone,
                    $designation,
                    $department_id,
                    $faculty_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: faculty.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO faculty
                     (faculty_name, email, phone, designation, department_id)
                     VALUES (?, NULLIF(?, ''), NULLIF(?, ''),
                             NULLIF(?, ''), NULLIF(?, ''))"
                );

                $stmt->bind_param(
                    "sssss",
                    $faculty_name,
                    $email,
                    $phone,
                    $designation,
                    $department_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: faculty.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save faculty. Check whether the email already exists and whether the department is valid.";
            $messageType = "error";
        }
    }
}

// DELETE FACULTY
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_faculty"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM faculty WHERE faculty_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: faculty.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "This faculty member cannot be deleted because they may be linked to subjects. Remove the related records first.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Faculty member added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Faculty details updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Faculty member deleted successfully.";
}

// LOAD FACULTY FOR EDITING
$editFaculty = null;

if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM faculty WHERE faculty_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editFaculty = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editFaculty) {
        $faculty_id = $editFaculty["faculty_id"];
        $faculty_name = $editFaculty["faculty_name"];
        $email = $editFaculty["email"] ?? "";
        $phone = $editFaculty["phone"] ?? "";
        $designation = $editFaculty["designation"] ?? "";
        $department_id = $editFaculty["department_id"] ?? "";
    }
}

// LOAD DEPARTMENTS FOR DROPDOWN
$departments = [];
$departmentResult = $conn->query(
    "SELECT department_id, department_name
     FROM department
     ORDER BY department_name"
);

while ($row = $departmentResult->fetch_assoc()) {
    $departments[] = $row;
}

// LOAD FACULTY LIST
$facultyList = $conn->query(
    "SELECT f.*, d.department_name
     FROM faculty f
     LEFT JOIN department d
       ON f.department_id = d.department_id
     ORDER BY f.faculty_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty | StudentHub</title>
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
        <a class="nav-link active" href="faculty.php">♙ Faculty</a>
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
                <h1>Faculty Management</h1>
                <p>Manage faculty members and department assignments.</p>
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
                    <h2><?= $faculty_id !== "" ? "Edit Faculty Member" : "Add Faculty Member" ?></h2>
                    <p>Enter the faculty member's details below.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="faculty.php" class="form-grid">

                    <input type="hidden" name="faculty_id"
                           value="<?= htmlspecialchars((string)$faculty_id) ?>">

                    <div class="form-group">
                        <label for="faculty_name">Faculty Name *</label>
                        <input type="text" id="faculty_name" name="faculty_name"
                               value="<?= htmlspecialchars($faculty_name) ?>"
                               placeholder="Enter full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($email) ?>"
                               placeholder="faculty@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= htmlspecialchars($phone) ?>"
                               placeholder="Enter phone number">
                    </div>

                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" id="designation" name="designation"
                               value="<?= htmlspecialchars($designation) ?>"
                               placeholder="e.g. Assistant Professor">
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option
                                    value="<?= (int)$dept["department_id"] ?>"
                                    <?= (string)$department_id === (string)$dept["department_id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($dept["department_name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_faculty" class="btn btn-primary">
                            <?= $faculty_id !== "" ? "Update Faculty" : "Add Faculty" ?>
                        </button>

                        <?php if ($faculty_id !== ""): ?>
                            <a href="faculty.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Faculty Directory</h2>
                    <p>View, search, edit, or delete faculty records.</p>
                </div>

                <div class="search-box">
                    <input type="search" id="facultySearch"
                           placeholder="Search faculty...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="facultyTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Faculty Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($facultyList->num_rows > 0): ?>
                            <?php while ($row = $facultyList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["faculty_id"] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row["faculty_name"]) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row["email"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($row["phone"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($row["designation"] ?? "—") ?></td>
                                    <td><?= htmlspecialchars($row["department_name"] ?? "Not Assigned") ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="faculty.php?edit=<?= (int)$row["faculty_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="faculty.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this faculty member?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["faculty_id"] ?>">
                                                <button type="submit" name="delete_faculty"
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
                                    No faculty records found. Add your first faculty member above.
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
const facultySearch = document.getElementById("facultySearch");
const facultyRows = document.querySelectorAll("#facultyTable tbody tr");

facultySearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    facultyRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
