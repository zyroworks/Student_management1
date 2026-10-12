
<?php
require_once "db.php";

$message = "";
$messageType = "success";
$editDepartment = null;

// Handle Add, Update and Delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ADD OR UPDATE DEPARTMENT
    if ($action === "add" || $action === "update") {
        $department_name = trim($_POST["department_name"] ?? "");
        $department_code = trim($_POST["department_code"] ?? "");
        $hod = trim($_POST["hod"] ?? "");
        $office_location = trim($_POST["office_location"] ?? "");

        if ($department_name === "") {
            $message = "Department name is required.";
            $messageType = "error";
        } else {
            try {
                if ($action === "add") {
                    $stmt = $conn->prepare(
                        "INSERT INTO department
                         (department_name, department_code, hod, office_location)
                         VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))"
                    );

                    $stmt->bind_param(
                        "ssss",
                        $department_name,
                        $department_code,
                        $hod,
                        $office_location
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: departments.php?status=added");
                    exit;
                }

                if ($action === "update") {
                    $department_id = (int)($_POST["department_id"] ?? 0);

                    if ($department_id <= 0) {
                        throw new Exception("Invalid department ID.");
                    }

                    $stmt = $conn->prepare(
                        "UPDATE department
                         SET department_name = ?,
                             department_code = NULLIF(?, ''),
                             hod = NULLIF(?, ''),
                             office_location = NULLIF(?, '')
                         WHERE department_id = ?"
                    );

                    $stmt->bind_param(
                        "ssssi",
                        $department_name,
                        $department_code,
                        $hod,
                        $office_location,
                        $department_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: departments.php?status=updated");
                    exit;
                }
            } catch (Throwable $e) {
                $message = "Could not save the department. Check whether the department name or code already exists.";
                $messageType = "error";
            }
        }
    }

    // DELETE DEPARTMENT
    if ($action === "delete") {
        $department_id = (int)($_POST["department_id"] ?? 0);

        if ($department_id <= 0) {
            $message = "Invalid department ID.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM department WHERE department_id = ?"
                );

                $stmt->bind_param("i", $department_id);
                $stmt->execute();
                $stmt->close();

                header("Location: departments.php?status=deleted");
                exit;
            } catch (Throwable $e) {
                $message = "This department cannot be deleted because students, courses, or faculty may be linked to it. Reassign or remove the related records first.";
                $messageType = "error";
            }
        }
    }
}

// Success messages
if (isset($_GET["status"])) {
    $status = $_GET["status"];

    if ($status === "added") {
        $message = "Department added successfully!";
    } elseif ($status === "updated") {
        $message = "Department updated successfully!";
    } elseif ($status === "deleted") {
        $message = "Department deleted successfully!";
    } else {
        $message = "";
    }
}

// Load department to edit
if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT department_id, department_name, department_code,
                hod, office_location
         FROM department
         WHERE department_id = ?"
    );

    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editDepartment = $result->fetch_assoc();

    $stmt->close();

    if (!$editDepartment) {
        $message = "Department record not found.";
        $messageType = "error";
    }
}

// Load departments
$departments = $conn->query(
    "SELECT department_id, department_name, department_code,
            hod, office_location
     FROM department
     ORDER BY department_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | StudentHub</title>
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
        <a class="nav-link active" href="departments.php">▤ Departments</a>
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
                <h1>Department Management</h1>
                <p>Add, edit, delete and manage departments.</p>
            </div>

            <a href="departments.php#department-form" class="primary-button">
                + Add Department
            </a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ADD / EDIT FORM -->
        <section class="form-card" id="department-form">
            <div class="section-heading">
                <h2>
                    <?= $editDepartment ? "Edit Department" : "Add New Department" ?>
                </h2>
                <p>Enter the department information below.</p>
            </div>

            <form method="POST" action="departments.php">
                <input
                    type="hidden"
                    name="action"
                    value="<?= $editDepartment ? "update" : "add" ?>"
                >

                <?php if ($editDepartment): ?>
                    <input
                        type="hidden"
                        name="department_id"
                        value="<?= (int)$editDepartment["department_id"] ?>"
                    >
                <?php endif; ?>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="department_name">Department Name *</label>
                        <input
                            type="text"
                            id="department_name"
                            name="department_name"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars($editDepartment["department_name"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="department_code">Department Code</label>
                        <input
                            type="text"
                            id="department_code"
                            name="department_code"
                            maxlength="20"
                            value="<?= htmlspecialchars($editDepartment["department_code"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="hod">Head of Department</label>
                        <input
                            type="text"
                            id="hod"
                            name="hod"
                            maxlength="100"
                            value="<?= htmlspecialchars($editDepartment["hod"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="office_location">Office Location</label>
                        <input
                            type="text"
                            id="office_location"
                            name="office_location"
                            maxlength="100"
                            value="<?= htmlspecialchars($editDepartment["office_location"] ?? "") ?>"
                        >
                    </div>

                </div>

                <button class="submit-btn" type="submit">
                    <?= $editDepartment ? "Save Changes" : "+ Add Department" ?>
                </button>

                <?php if ($editDepartment): ?>
                    <a href="departments.php" class="primary-button">
                        Cancel
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- DEPARTMENT RECORDS -->
        <section class="table-card">
            <div class="section-heading">
                <h2>Department Records</h2>
                <p>View all departments registered in the system.</p>
            </div>

            <div class="search-row">
                <input
                    type="search"
                    id="departmentSearch"
                    class="search-input"
                    placeholder="Search departments..."
                    aria-label="Search departments"
                >
            </div>

            <div class="table-wrap">
                <table class="data-table" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>DEPARTMENT NAME</th>
                            <th>CODE</th>
                            <th>HEAD OF DEPARTMENT</th>
                            <th>OFFICE LOCATION</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($departments && $departments->num_rows > 0): ?>
                        <?php while ($d = $departments->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$d["department_id"] ?></td>
                                <td><?= htmlspecialchars($d["department_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($d["department_code"] ?? "—") ?></td>
                                <td><?= htmlspecialchars($d["hod"] ?? "—") ?></td>
                                <td><?= htmlspecialchars($d["office_location"] ?? "—") ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a
                                            href="departments.php?edit=<?= (int)$d["department_id"] ?>#department-form"
                                            class="primary-button"
                                            style="padding:8px 12px;font-size:12px;"
                                        >Edit</a>

                                        <form
                                            method="POST"
                                            action="departments.php"
                                            onsubmit="return confirm('Delete this department? This may be blocked if records are linked to it.');"
                                            style="display:inline;"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input
                                                type="hidden"
                                                name="department_id"
                                                value="<?= (int)$d["department_id"] ?>"
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
                            <td colspan="6">No departments found. Add your first department above.</td>
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
document.getElementById("departmentSearch").addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#departmentsTable tbody tr");

    rows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(query)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
