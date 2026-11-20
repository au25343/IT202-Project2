
<?php
session_start();

if (!isset($_SESSION['repID'])) {
    die("Please log in first.");
}

require_once dirname(__DIR__, 2) . "/private_db/db_config.php";

$conn = mysqli_connect(
    $db_host,
    $db_user,
    $db_password,
    $db_name,
    3306
);

if (!$conn) {
    die("Database connection failed.");
}

$repID = $_SESSION['repID'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $clientID = trim($_POST['clientID'] ?? "");
    $movingID = trim($_POST['movingID'] ?? "");

    if ($clientID == "" || !ctype_digit($movingID)) {
        $message = "Please enter valid information.";
    } else {

        $sql = "SELECT MovingID FROM Move
                WHERE MovingID = ?
                AND ClientID = ?
                AND RepID = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, "iss", $movingID, $clientID, $repID
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$exists) {
            $message = "Moving event not found. Check your information.";
        } else {
            try {
                mysqli_begin_transaction($conn);

                $sql = "DELETE FROM SpecialInstructions
                        WHERE MovingID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $movingID);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $sql = "DELETE FROM Move
                        WHERE MovingID = ?
                        AND ClientID = ?
                        AND RepID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param(
                    $stmt, "iss", $movingID, $clientID, $repID
                );
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) != 1) {
                    throw new Exception("Booking not found.");
                }

                mysqli_stmt_close($stmt);
                mysqli_commit($conn);

                $message = "Moving event cancelled successfully!"
                    . " Representative ID: " . $repID
                    . " | Client ID: " . $clientID
                    . " | Moving ID: " . $movingID;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $message = "Cancellation failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cancel Moving Event</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav>
    <a href="index.html">Home / Login</a>
    <a href="book_move.php">Book Moving Event</a>
    <a href="cancel_move.php">Cancel Moving Event</a>
</nav>

<h1>Moving On Up</h1>
<h2>Cancel a Client's Moving Event</h2>

<p>Representative ID:
<?php echo htmlspecialchars($repID); ?>
</p>

<form method="POST" onsubmit="return confirmCancel();">

    <label>Client ID</label>
    <input type="text" name="clientID" required>

    <label>Moving ID</label>
    <input type="number" name="movingID" min="1" required>

    <button type="submit">Cancel Moving Event</button>

</form>

<?php if ($message != "") { ?>
<script>
alert(<?php echo json_encode($message); ?>);
</script>
<p><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<script>
function confirmCancel() {
    return confirm(
        "Are you sure you want to cancel this moving event?\n" +
        "Any special instructions will also be deleted."
    );
}
</script>

</body>
</html>
