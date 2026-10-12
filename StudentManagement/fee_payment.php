
<?php
require_once "db.php";

$message = "";
$messageType = "success";

$payment_id = "";
$student_id = "";
$amount = "";
$payment_date = date("Y-m-d");
$payment_method = "";
$payment_status = "Pending";
$transaction_reference = "";

// ADD OR UPDATE PAYMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_payment"])) {
    $payment_id = trim($_POST["payment_id"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $amount = trim($_POST["amount"] ?? "");
    $payment_date = trim($_POST["payment_date"] ?? "");
    $payment_method = trim($_POST["payment_method"] ?? "");
    $payment_status = trim($_POST["payment_status"] ?? "Pending");
    $transaction_reference = trim($_POST["transaction_reference"] ?? "");

    $allowedStatuses = ["Pending", "Paid", "Failed"];
    $allowedMethods = ["Cash", "UPI", "Card", "Bank Transfer", "Cheque", "Other"];

    if ($student_id === "" || $amount === "" || $payment_date === "") {
        $message = "Please select a student, enter an amount, and choose a payment date.";
        $messageType = "error";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $message = "Payment amount must be greater than zero.";
        $messageType = "error";
    } elseif (!in_array($payment_status, $allowedStatuses, true)) {
        $message = "Please select a valid payment status.";
        $messageType = "error";
    } elseif ($payment_method !== "" && !in_array($payment_method, $allowedMethods, true)) {
        $message = "Please select a valid payment method.";
        $messageType = "error";
    } else {
        try {
            if ($payment_id !== "") {
                $stmt = $conn->prepare(
                    "UPDATE fee_payment
                     SET student_id = ?,
                         amount = ?,
                         payment_date = ?,
                         payment_method = NULLIF(?, ''),
                         payment_status = ?,
                         transaction_reference = NULLIF(?, '')
                     WHERE payment_id = ?"
                );

                $stmt->bind_param(
                    "idssssi",
                    $student_id,
                    $amount,
                    $payment_date,
                    $payment_method,
                    $payment_status,
                    $transaction_reference,
                    $payment_id
                );
                $stmt->execute();
                $stmt->close();

                header("Location: fee_payment.php?updated=1");
                exit;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO fee_payment
                     (student_id, amount, payment_date, payment_method,
                      payment_status, transaction_reference)
                     VALUES (?, ?, ?, NULLIF(?, ''),
                             ?, NULLIF(?, ''))"
                );

                $stmt->bind_param(
                    "idssss",
                    $student_id,
                    $amount,
                    $payment_date,
                    $payment_method,
                    $payment_status,
                    $transaction_reference
                );
                $stmt->execute();
                $stmt->close();

                header("Location: fee_payment.php?added=1");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to save payment. Check that the student exists and that the payment details satisfy your database constraints.";
            $messageType = "error";
        }
    }
}

// DELETE PAYMENT
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_payment"])) {
    $delete_id = (int)($_POST["delete_id"] ?? 0);

    if ($delete_id > 0) {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM fee_payment WHERE payment_id = ?"
            );
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();

            header("Location: fee_payment.php?deleted=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = "Unable to delete this payment record.";
            $messageType = "error";
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["added"])) {
    $message = "Fee payment added successfully.";
}
if (isset($_GET["updated"])) {
    $message = "Fee payment updated successfully.";
}
if (isset($_GET["deleted"])) {
    $message = "Fee payment deleted successfully.";
}

// LOAD PAYMENT FOR EDITING
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM fee_payment WHERE payment_id = ?"
    );
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editPayment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editPayment) {
        $payment_id = $editPayment["payment_id"];
        $student_id = $editPayment["student_id"];
        $amount = $editPayment["amount"];
        $payment_date = $editPayment["payment_date"];
        $payment_method = $editPayment["payment_method"] ?? "";
        $payment_status = $editPayment["payment_status"];
        $transaction_reference = $editPayment["transaction_reference"] ?? "";
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

// LOAD PAYMENT RECORDS
$paymentList = $conn->query(
    "SELECT p.*, s.student_name
     FROM fee_payment p
     LEFT JOIN student s ON p.student_id = s.student_id
     ORDER BY p.payment_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payments | StudentHub</title>
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
        <a class="nav-link" href="results.php">📊 Results</a>
        <a class="nav-link active" href="fee_payment.php">💳 Fee Payments</a>

        <div class="sidebar-bottom">
            <p>Student Management System</p>
            <small>DBMS Project · 2026</small>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Fee Payments</h1>
                <p>Manage student fee records and payment status.</p>
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
                    <h2><?= $payment_id !== "" ? "Edit Fee Payment" : "Add Fee Payment" ?></h2>
                    <p>Record payment amount, method, and transaction details.</p>
                </div>
            </div>

            <div class="panel">
                <form method="POST" action="fee_payment.php" class="form-grid">

                    <input type="hidden" name="payment_id"
                           value="<?= htmlspecialchars((string)$payment_id) ?>">

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
                        <label for="amount">Amount (₹) *</label>
                        <input type="number" id="amount" name="amount"
                               value="<?= htmlspecialchars((string)$amount) ?>"
                               min="0.01" step="0.01"
                               placeholder="Enter amount" required>
                    </div>

                    <div class="form-group">
                        <label for="payment_date">Payment Date *</label>
                        <input type="date" id="payment_date" name="payment_date"
                               value="<?= htmlspecialchars($payment_date) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method">
                            <option value="">Select Method</option>
                            <?php foreach (["Cash", "UPI", "Card", "Bank Transfer", "Cheque", "Other"] as $method): ?>
                                <option value="<?= $method ?>"
                                    <?= $payment_method === $method ? "selected" : "" ?>>
                                    <?= $method ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_status">Payment Status *</label>
                        <select id="payment_status" name="payment_status" required>
                            <?php foreach (["Pending", "Paid", "Failed"] as $option): ?>
                                <option value="<?= $option ?>"
                                    <?= $payment_status === $option ? "selected" : "" ?>>
                                    <?= $option ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transaction_reference">Transaction Reference</label>
                        <input type="text" id="transaction_reference"
                               name="transaction_reference"
                               value="<?= htmlspecialchars($transaction_reference) ?>"
                               placeholder="e.g. TXN123456">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_payment" class="btn btn-primary">
                            <?= $payment_id !== "" ? "Update Payment" : "Save Payment" ?>
                        </button>

                        <?php if ($payment_id !== ""): ?>
                            <a href="fee_payment.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div>
                    <h2>Payment Records</h2>
                    <p>View, search, edit, or delete payment records.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="paymentSearch"
                           placeholder="Search payments...">
                </div>
            </div>

            <div class="panel table-panel">
                <div class="table-responsive">
                    <table class="data-table" id="paymentTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Transaction Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($paymentList->num_rows > 0): ?>
                            <?php while ($row = $paymentList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row["payment_id"] ?></td>
                                    <td><?= htmlspecialchars($row["student_name"] ?? "Unknown Student") ?></td>
                                    <td>₹<?= number_format((float)$row["amount"], 2) ?></td>
                                    <td><?= htmlspecialchars($row["payment_date"]) ?></td>
                                    <td><?= htmlspecialchars($row["payment_method"] ?? "—") ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($row["payment_status"]) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row["transaction_reference"] ?? "—") ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-small btn-edit"
                                               href="fee_payment.php?edit=<?= (int)$row["payment_id"] ?>">
                                                Edit
                                            </a>

                                            <form method="POST" action="fee_payment.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this payment record?');">
                                                <input type="hidden" name="delete_id"
                                                       value="<?= (int)$row["payment_id"] ?>">
                                                <button type="submit" name="delete_payment"
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
                                    No payment records found. Add your first payment above.
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
const paymentSearch = document.getElementById("paymentSearch");
const paymentRows = document.querySelectorAll("#paymentTable tbody tr");

paymentSearch.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    paymentRows.forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
