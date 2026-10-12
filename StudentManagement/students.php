<?php
require_once "db.php";

$message = "";
$messageType = "success";
$editStudent = null;

// Handle Add, Update and Delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ADD STUDENT
    if ($action === "add" || $action === "update") {
        $student_name = trim($_POST["student_name"] ?? "");
        $dob = trim($_POST["dob"] ?? "");
        $gender = trim($_POST["gender"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $department_id = trim($_POST["department_id"] ?? "");

        if ($student_name === "") {
            $message = "Student name is required.";
            $messageType = "error";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            try {
                if ($action === "add") {
                    $stmt = $conn->prepare(
                        "INSERT INTO student
                        (student_name, dob, gender, email, phone, address, department_id)
                        VALUES (?, NULLIF(?, ''), NULLIF(?, ''),
                                NULLIF(?, ''), NULLIF(?, ''),
                                NULLIF(?, ''), NULLIF(?, ''))"
                    );

                    $stmt->bind_param(
                        "sssssss",
                        $student_name,
                        $dob,
                        $gender,
                        $email,
                        $phone,
                        $address,
                        $department_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: students.php?status=added");
                    exit;
                }

                if ($action === "update") {
                    $student_id = (int)($_POST["student_id"] ?? 0);

                    if ($student_id <= 0) {
                        throw new Exception("Invalid student ID.");
                    }

                    $stmt = $conn->prepare(
                        "UPDATE student
                         SET student_name = ?,
                             dob = NULLIF(?, ''),
                             gender = NULLIF(?, ''),
                             email = NULLIF(?, ''),
                             phone = NULLIF(?, ''),
                             address = NULLIF(?, ''),
                             department_id = NULLIF(?, '')
                         WHERE student_id = ?"
                    );

                    $stmt->bind_param(
                        "sssssssi",
                        $student_name,
                        $dob,
                        $gender,
                        $email,
                        $phone,
                        $address,
                        $department_id,
                        $student_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: students.php?status=updated");
                    exit;
                }
            } catch (Throwable $e) {
                $message = "Could not save the student. Check the entered details and database constraints.";
                $messageType = "error";
            }
        }
    }

    // DELETE STUDENT
    if ($action === "delete") {
        $student_id = (int)($_POST["student_id"] ?? 0);

        if ($student_id <= 0) {
            $message = "Invalid student ID.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM student WHERE student_id = ?"
                );

                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->close();

                header("Location: students.php?status=deleted");
                exit;
            } catch (Throwable $e) {
                $message = "This student could not be deleted. The student may have attendance, enrollment, result, or fee records linked to them. Remove or reassign those related records first.";
                $messageType = "error";
            }
        }
    }
}

// Success messages
if (isset($_GET["status"])) {
    $status = $_GET["status"];

    if ($status === "added") {
        $message = "Student added successfully!";
    } elseif ($status === "updated") {
        $message = "Student updated successfully!";
    } elseif ($status === "deleted") {
        $message = "Student deleted successfully!";
    } else {
        $message = "";
    }
}

// Load student for editing
if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT student_id, student_name, dob, gender, email,
                phone, address, department_id
         FROM student
         WHERE student_id = ?"
    );

    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editStudent = $result->fetch_assoc();

    $stmt->close();

    if (!$editStudent) {
        $message = "Student record not found.";
        $messageType = "error";
    }
}

// Load departments for the form
$departments = $conn->query(
    "SELECT department_id, department_name
     FROM department
     ORDER BY department_name"
);

// Load all students
$students = $conn->query(
    "SELECT s.student_id, s.student_name, s.dob, s.gender,
            s.email, s.phone, s.address, s.department_id,
            d.department_name
     FROM student s
     LEFT JOIN department d
        ON s.department_id = d.department_id
     ORDER BY s.student_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | StudentHub</title>
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
        <a class="nav-link active" href="students.php">♙ Students</a>
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
                <h1>Student Management</h1>
                <p>Add, edit, delete and manage student records.</p>
            </div>

            <a href="students.php#student-form" class="primary-button">
                + Add Student
            </a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ADD / EDIT FORM -->
        <section class="form-card" id="student-form">
            <div class="section-heading">
                <h2>
                    <?= $editStudent ? "Edit Student" : "Add New Student" ?>
                </h2>
                <p>
                    <?= $editStudent
                        ? "Update the student's information below."
                        : "Enter the student's information below." ?>
                </p>
            </div>

            <form method="POST" action="students.php">
                <input
                    type="hidden"
                    name="action"
                    value="<?= $editStudent ? "update" : "add" ?>"
                >

                <?php if ($editStudent): ?>
                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= (int)$editStudent["student_id"] ?>"
                    >
                <?php endif; ?>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="student_name">Full Name *</label>
                        <input
                            type="text"
                            id="student_name"
                            name="student_name"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars($editStudent["student_name"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="100"
                            value="<?= htmlspecialchars($editStudent["email"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            maxlength="20"
                            value="<?= htmlspecialchars($editStudent["phone"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input
                            type="date"
                            id="dob"
                            name="dob"
                            value="<?= htmlspecialchars($editStudent["dob"] ?? "") ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select gender</option>
                            <?php foreach (["Male", "Female", "Other"] as $g): ?>
                                <option
                                    value="<?= $g ?>"
                                    <?= (($editStudent["gender"] ?? "") === $g) ? "selected" : "" ?>
                                >
                                    <?= $g ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id">
                            <option value="">Select department</option>

                            <?php if ($departments): ?>
                                <?php while ($d = $departments->fetch_assoc()): ?>
                                    <option
                                        value="<?= (int)$d["department_id"] ?>"
                                        <?= ((string)($editStudent["department_id"] ?? "") === (string)$d["department_id"]) ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($d["department_name"]) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            maxlength="500"
                        ><?= htmlspecialchars($editStudent["address"] ?? "") ?></textarea>
                    </div>

                </div>

                <button class="submit-btn" type="submit">
                    <?= $editStudent ? "Save Changes" : "+ Add Student" ?>
                </button>

                <?php if ($editStudent): ?>
                    <a href="students.php" class="primary-button">
                        Cancel
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- STUDENT RECORDS -->
        <section class="table-card">
            <div class="section-heading">
                <h2>All Students</h2>
                <p>Student information and contact details.</p>
            </div>

            <div class="search-row">
                <input
                    type="search"
                    id="studentSearch"
                    class="search-input"
                    placeholder="Search by name, ID, email or phone..."
                    aria-label="Search students"
                >
            </div>

            <div class="table-wrap">
                <table class="data-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>FULL NAME</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>GENDER</th>
                            <th>DEPARTMENT</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($students && $students->num_rows > 0): ?>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$s["student_id"] ?></td>
                                <td><?= htmlspecialchars($s["student_name"] ?? "") ?></td>
                                <td><?= htmlspecialchars($s["email"] ?? "") ?></td>
                                <td><?= htmlspecialchars($s["phone"] ?? "") ?></td>
                                <td><?= htmlspecialchars($s["gender"] ?? "—") ?></td>
                                <td><?= htmlspecialchars($s["department_name"] ?? "—") ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a
                                            href="students.php?edit=<?= (int)$s["student_id"] ?>#student-form"
                                            class="primary-button"
                                            style="padding:8px 12px;font-size:12px;"
                                        >Edit</a>

                                        <form
                                            method="POST"
                                            action="students.php"
                                            onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone.');"
                                            style="display:inline;"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input
                                                type="hidden"
                                                name="student_id"
                                                value="<?= (int)$s["student_id"] ?>"
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
                            <td colspan="7">No students found. Add your first student above.</td>
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
document.getElementById("studentSearch").addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#studentsTable tbody tr");

    rows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(query)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
