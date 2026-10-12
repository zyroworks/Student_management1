<?php
require_once "db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentName = trim($_POST["student_name"] ?? "");
    $dob = $_POST["dob"] ?? "";
    $gender = $_POST["gender"] ?? "";
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $departmentId = (int)($_POST["department_id"] ?? 0);

    if ($studentName === "" || $email === "" || $departmentId <= 0) {
        $message = "Please enter the student name, email and department.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO student
            (student_name, dob, gender, email, phone, address, department_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param(
                "ssssssi",
                $studentName,
                $dob,
                $gender,
                $email,
                $phone,
                $address,
                $departmentId
            );

            if ($stmt->execute()) {
                header("Location: students.php?added=1");
                exit;
            } else {
                $message = "Unable to add student: " . $stmt->error;
                $messageType = "error";
            }

            $stmt->close();
        } else {
            $message = "Database error: " . $conn->error;
            $messageType = "error";
        }
    }
}

$departments = $conn->query(
    "SELECT department_id, department_name
     FROM department
     ORDER BY department_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | StudentHub</title>
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
        <a class="nav-link active" href="add_student.php">＋ Add Student</a>
        <a class="nav-link" href="departments.php">▤ Departments</a>
        <a class="nav-link" href="courses.php">📚 Courses</a>
        <a class="nav-link" href="faculty.php">♙ Faculty</a>

        <div class="sidebar-bottom">
            <p>Student Management System</p>
            <small>DBMS Project · 2026</small>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Add Student</h1>
                <p>Enter the details to register a new student.</p>
            </div>
            <a href="students.php" class="primary-button">
                View Students
            </a>
        </header>

        <section class="form-card">
            <div class="section-heading">
                <h2>Student Information</h2>
                <p>Fill in the student details below.</p>
            </div>

            <?php if ($message !== ""): ?>
                <div class="message <?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_student.php">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="student_name">Full Name *</label>
                        <input type="text" id="student_name"
                               name="student_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email"
                               name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone"
                               maxlength="20">
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department *</label>
                        <select id="department_id" name="department_id" required>
                            <option value="">Select department</option>
                            <?php if ($departments): ?>
                                <?php while ($department = $departments->fetch_assoc()): ?>
                                    <option value="<?= (int)$department["department_id"] ?>">
                                        <?= htmlspecialchars($department["department_name"]) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"
                                  rows="3"></textarea>
                    </div>

                </div>

                <button type="submit" class="submit-btn">
                    Save Student
                </button>
            </form>
        </section>

        <footer class="footer">
            <span>Student Management System</span>
            <span>DBMS PBL Project · 2026</span>
        </footer>

    </main>
</div>

</body>
</html>
